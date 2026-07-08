@extends('layouts.manager')

@section('title', $opportunity->title ?? 'Opportunity #' . $opportunity->id)

@section('content')
@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;

    $stageLabels = $stageLabels ?? [];
    $opportunityStages = $opportunityStages ?? array_keys($stageLabels);

    $stageLabel = function ($stage) use ($stageLabels) {
        return $stageLabels[$stage] ?? Str::headline((string) $stage);
    };

    $stageClass = function ($stage) {
        return match ((string) $stage) {
            'new' => 'badge-soft-primary',
            'attempting_contact' => 'badge-soft-warning',
            'appointment' => 'badge-soft-info',
            'offer' => 'badge-soft-purple',
            'manager_confirmation_pending' => 'badge-soft-orange',
            'booking_confirmed' => 'badge-soft-success',
            'closed_lost' => 'badge-soft-danger',
            default => 'badge-soft-muted',
        };
    };

    $statusClass = function ($status) {
        return match (strtolower((string) $status)) {
            'won', 'booking_confirmed', 'active' => 'badge-soft-success',
            'lost', 'closed_lost' => 'badge-soft-danger',
            'open' => 'badge-soft-primary',
            default => 'badge-soft-muted',
        };
    };

    $formatDate = function ($value, $fallback = 'Not set') {
        if (! $value) {
            return $fallback;
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d M Y');
        } catch (\Throwable $e) {
            return $value;
        }
    };

    $formatDateTime = function ($value, $fallback = 'Not available') {
        if (! $value) {
            return $fallback;
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d M Y, h:i A');
        } catch (\Throwable $e) {
            return $value;
        }
    };

    $customerName = $opportunity->client?->name
        ?? $opportunity->lead?->name
        ?? $opportunity->customer_name
        ?? $opportunity->client_name
        ?? $opportunity->name
        ?? $opportunity->title
        ?? 'Customer not linked';

    $customerPhone = $opportunity->client?->phone
        ?? $opportunity->client?->whatsapp
        ?? $opportunity->lead?->phone
        ?? $opportunity->phone
        ?? $opportunity->mobile
        ?? $opportunity->phone_number
        ?? $opportunity->whatsapp_number
        ?? 'No phone';

    $customerEmail = $opportunity->client?->email
        ?? $opportunity->lead?->email
        ?? $opportunity->email
        ?? 'No email';

    $vehicleLabel = $opportunity->vehicle_label
        ?? trim(collect([
            $opportunity->vehicle?->year,
            $opportunity->vehicleMake?->name ?? $opportunity->other_make ?? $opportunity->vehicle_make,
            $opportunity->vehicleModel?->name ?? $opportunity->other_model ?? $opportunity->vehicle_model,
        ])->filter()->implode(' '))
        ?: 'Vehicle not linked';

    $linkedBookings = $opportunity->bookings ?? collect();
    $linkedJobs = $opportunity->jobs ?? collect();
    $linkedInvoices = $opportunity->invoices ?? collect();
    $hasBooking = $linkedBookings->count() > 0;
    $notes = $opportunity->manager_notes ?? $opportunity->internal_notes ?? $opportunity->notes ?? null;
    $currentStage = $opportunity->stage ?? 'new';
    $currentStatus = $opportunity->status ?? 'active';
    $closedLostReasons = [
        'not_interested' => 'Not interested',
        'price_not_accepted' => 'Price not accepted',
        'customer_cancelled' => 'Customer cancelled',
        'unreachable_after_follow_up' => 'Unreachable after follow-up',
        'service_not_required' => 'Service no longer required',
        'service_not_offered' => 'Service not offered',
        'duplicate' => 'Duplicate opportunity',
        'booked_elsewhere' => 'Booked elsewhere',
        'spam_or_test' => 'Spam / test',
        'other' => 'Other',
    ];
@endphp

<div class="manager-opportunity-show-page">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <div class="sf-kicker">Opportunity Review</div>
            <h1 class="sf-page-title mt-2">{{ $opportunity->title ?? 'Opportunity #' . $opportunity->id }}</h1>
            <p class="sf-page-subtitle">
                Review customer context, update the pipeline stage, and schedule the next booking step.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @if(Route::has('manager.opportunities.index'))
                <a href="{{ route('manager.opportunities.index') }}" class="sf-action-button light">Back to Opportunities</a>
            @endif

            @if(Route::has('manager.dashboard'))
                <a href="{{ route('manager.dashboard') }}" class="sf-action-button primary">Dashboard</a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-4">
            <p class="fw-bold mb-2">Please check this opportunity action.</p>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="sf-panel mb-4">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">Opportunity Summary</h2>
                        <p class="sf-panel-subtitle">Current pipeline state and commercial context.</p>
                    </div>

                    <span class="manager-badge {{ $stageClass($currentStage) }}">{{ $stageLabel($currentStage) }}</span>
                </div>

                <div class="sf-panel-body">
                    <div class="opportunity-summary-grid">
                        <div class="detail-card">
                            <span class="detail-label">Customer</span>
                            <span class="detail-value">{{ $customerName }}</span>
                        </div>

                        <div class="detail-card">
                            <span class="detail-label">Phone</span>
                            <span class="detail-value">{{ $customerPhone }}</span>
                        </div>

                        <div class="detail-card">
                            <span class="detail-label">Email</span>
                            <span class="detail-value">{{ $customerEmail }}</span>
                        </div>

                        <div class="detail-card">
                            <span class="detail-label">Vehicle</span>
                            <span class="detail-value">{{ $vehicleLabel }}</span>
                        </div>

                        <div class="detail-card">
                            <span class="detail-label">Service Type</span>
                            <span class="detail-value">{{ $opportunity->service_type ?: 'Not set' }}</span>
                        </div>

                        <div class="detail-card">
                            <span class="detail-label">Value</span>
                            <span class="detail-value">{{ number_format((float) ($opportunity->value ?? 0), 2) }}</span>
                        </div>

                        <div class="detail-card">
                            <span class="detail-label">Priority</span>
                            <span class="detail-value">{{ Str::headline($opportunity->priority ?? 'medium') }}</span>
                        </div>

                        <div class="detail-card">
                            <span class="detail-label">Assigned To</span>
                            <span class="detail-value">{{ $opportunity->assignee?->name ?? 'Unassigned' }}</span>
                        </div>
                    </div>

                    @if($notes)
                        <div class="notes-box mt-4">
                            <span class="detail-label">Latest Notes</span>
                            <div class="notes-content">{{ $notes }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="sf-panel mb-4">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">Linked Work</h2>
                        <p class="sf-panel-subtitle">Bookings, jobs, invoices, and lead origin tied to this opportunity.</p>
                    </div>
                </div>

                <div class="sf-panel-body">
                    <div class="opportunity-linked-grid">
                        <div class="linked-record-card">
                            <span>Lead</span>
                            <strong>{{ $opportunity->lead ? 'Lead #' . $opportunity->lead->id : 'No lead linked' }}</strong>
                            @if($opportunity->lead && Route::has('manager.leads.index'))
                                <a href="{{ route('manager.leads.index', ['q' => $opportunity->lead->phone ?? $opportunity->lead->name]) }}">Find in leads</a>
                            @endif
                        </div>

                        <div class="linked-record-card">
                            <span>Bookings</span>
                            <strong>{{ number_format($linkedBookings->count()) }}</strong>
                            @if($linkedBookings->first() && Route::has('manager.bookings.show'))
                                <a href="{{ route('manager.bookings.show', $linkedBookings->first()) }}">Open latest booking</a>
                            @endif
                        </div>

                        <div class="linked-record-card">
                            <span>Jobs</span>
                            <strong>{{ number_format($linkedJobs->count()) }}</strong>
                            @if($linkedJobs->first() && Route::has('manager.jobs.show'))
                                <a href="{{ route('manager.jobs.show', $linkedJobs->first()) }}">Open latest job</a>
                            @endif
                        </div>

                        <div class="linked-record-card">
                            <span>Invoices</span>
                            <strong>{{ number_format($linkedInvoices->count()) }}</strong>
                            @if($linkedInvoices->first() && Route::has('manager.invoices.show'))
                                <a href="{{ route('manager.invoices.show', $linkedInvoices->first()->id) }}">Open latest invoice</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="sf-panel">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">Timeline</h2>
                        <p class="sf-panel-subtitle">Operational timestamps available for this opportunity.</p>
                    </div>
                </div>

                <div class="sf-panel-body">
                    <div class="timeline-list">
                        <div class="timeline-item">
                            <span class="timeline-dot active"></span>
                            <div>
                                <span class="timeline-label">Created</span>
                                <span class="timeline-value">{{ $formatDateTime($opportunity->created_at ?? null) }}</span>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <span class="timeline-dot {{ $opportunity->next_follow_up || $opportunity->follow_up_date ? 'active' : '' }}"></span>
                            <div>
                                <span class="timeline-label">Next Follow-up</span>
                                <span class="timeline-value">{{ $formatDate($opportunity->next_follow_up ?? $opportunity->follow_up_date ?? null) }}</span>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <span class="timeline-dot {{ $opportunity->expected_close_date ? 'active' : '' }}"></span>
                            <div>
                                <span class="timeline-label">Expected Close</span>
                                <span class="timeline-value">{{ $formatDate($opportunity->expected_close_date ?? null) }}</span>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <span class="timeline-dot {{ $opportunity->won_at ? 'active' : '' }}"></span>
                            <div>
                                <span class="timeline-label">Booking Confirmed</span>
                                <span class="timeline-value">{{ $formatDateTime($opportunity->won_at ?? null, 'Not confirmed') }}</span>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <span class="timeline-dot {{ $opportunity->lost_at ? 'active' : '' }}"></span>
                            <div>
                                <span class="timeline-label">Closed Lost</span>
                                <span class="timeline-value">{{ $formatDateTime($opportunity->lost_at ?? null, 'Not closed lost') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="sf-panel mb-4">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">Stage Action</h2>
                        <p class="sf-panel-subtitle">Move the opportunity through the manager pipeline.</p>
                    </div>
                </div>

                <div class="sf-panel-body">
                    @if(Route::has('manager.opportunities.stage'))
                        <form method="POST" action="{{ route('manager.opportunities.stage', $opportunity) }}">
                            @csrf
                            @method('PATCH')

                            <div class="mb-3">
                                <label class="form-label">Stage</label>
                                <select name="stage" class="form-select" required>
                                    @foreach($opportunityStages as $value)
                                        <option value="{{ $value }}" @selected($currentStage === $value)>{{ $stageLabel($value) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Booking Date</label>
                                    <input type="date" name="booking_date" class="form-control" min="{{ now()->format('Y-m-d') }}">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">Booking Slot</label>
                                    <select name="booking_slot" class="form-select">
                                        <option value="">Select slot</option>
                                        <option value="morning">Morning</option>
                                        <option value="afternoon">Afternoon</option>
                                        <option value="evening">Evening</option>
                                        <option value="full_day">Full Day</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="form-label">Service Type</label>
                                <input type="text" name="service_type" class="form-control" value="{{ $opportunity->service_type }}" placeholder="Service required">
                            </div>

                            <div class="mt-3">
                                <label class="form-label">Closed Lost Reason</label>
                                <select name="stage_sub_status" class="form-select">
                                    <option value="">Select when closing lost</option>
                                    @foreach($closedLostReasons as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mt-3">
                                <label class="form-label">Reason / Notes</label>
                                <textarea name="stage_reason" rows="2" class="form-control" placeholder="Reason when closing lost"></textarea>
                            </div>

                            <div class="mt-3">
                                <textarea name="notes" rows="2" class="form-control" placeholder="Optional manager note"></textarea>
                            </div>

                            <button type="submit" class="action-btn action-primary mt-3">Update Stage</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="sf-panel mb-4">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">Schedule Booking</h2>
                        <p class="sf-panel-subtitle">Create a confirmed booking from this opportunity.</p>
                    </div>
                </div>

                <div class="sf-panel-body">
                    @if(Route::has('manager.opportunities.schedule-booking'))
                        <form method="POST" action="{{ route('manager.opportunities.schedule-booking', $opportunity) }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Booking Date</label>
                                <input type="date" name="booking_date" class="form-control" min="{{ now()->format('Y-m-d') }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Booking Time</label>
                                <input type="time" name="booking_time" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Slot</label>
                                <select name="slot" class="form-select">
                                    <option value="">Select slot</option>
                                    <option value="morning">Morning</option>
                                    <option value="afternoon">Afternoon</option>
                                    <option value="evening">Evening</option>
                                    <option value="full_day">Full Day</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Service Type</label>
                                <input type="text" name="service_type" class="form-control" value="{{ $opportunity->service_type }}" placeholder="Service required">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Booking Notes</label>
                                <textarea name="notes" rows="3" class="form-control" placeholder="Customer instructions, pickup notes, service details"></textarea>
                            </div>

                            <button type="submit" class="action-btn action-orange">Create Booking</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="sf-panel mb-4">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">Follow-up & Assignment</h2>
                        <p class="sf-panel-subtitle">Keep ownership and next action clear.</p>
                    </div>
                </div>

                <div class="sf-panel-body">
                    @if(Route::has('manager.opportunities.follow-up'))
                        <form method="POST" action="{{ route('manager.opportunities.follow-up', $opportunity) }}" class="mb-4">
                            @csrf
                            @method('PATCH')

                            <label class="form-label">Follow-up Date</label>
                            <input type="date" name="follow_up_date" class="form-control mb-3" value="{{ $opportunity->follow_up_date ? \Carbon\Carbon::parse($opportunity->follow_up_date)->format('Y-m-d') : '' }}">

                            <label class="inline-check mb-3">
                                <input type="checkbox" name="follow_up_required" value="1" @checked((bool) ($opportunity->follow_up_required ?? false))>
                                Follow-up required
                            </label>

                            <textarea name="notes" rows="2" class="form-control mb-3" placeholder="Optional follow-up note"></textarea>
                            <button type="submit" class="action-btn action-light">Save Follow-up</button>
                        </form>
                    @endif

                    @if(Route::has('manager.opportunities.assign') && ($managers ?? collect())->count())
                        <form method="POST" action="{{ route('manager.opportunities.assign', $opportunity) }}">
                            @csrf
                            @method('PATCH')

                            <label class="form-label">Assign To</label>
                            <select name="assigned_to" class="form-select mb-3" required>
                                <option value="">Select manager</option>
                                @foreach($managers as $manager)
                                    <option value="{{ $manager->id }}" @selected((int) ($opportunity->assigned_to ?? 0) === (int) $manager->id)>{{ $manager->name }}</option>
                                @endforeach
                            </select>

                            <button type="submit" class="action-btn action-dark">Update Assignment</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="sf-panel">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">Close Outcome</h2>
                        <p class="sf-panel-subtitle">Use manager-safe final actions.</p>
                    </div>
                </div>

                <div class="sf-panel-body">
                    @if($hasBooking && Route::has('manager.opportunities.mark-won'))
                        <form method="POST" action="{{ route('manager.opportunities.mark-won', $opportunity) }}" class="mb-3">
                            @csrf
                            @method('PATCH')
                            <textarea name="notes" rows="2" class="form-control mb-3" placeholder="Optional booking-confirmed note"></textarea>
                            <button type="submit" class="action-btn action-success">Mark Booking Confirmed</button>
                        </form>
                    @else
                        <div class="empty-mini mb-3">Schedule a booking before marking this opportunity as booking confirmed.</div>
                    @endif

                    @if(Route::has('manager.opportunities.mark-lost'))
                        <form method="POST" action="{{ route('manager.opportunities.mark-lost', $opportunity) }}">
                            @csrf
                            @method('PATCH')

                            <label class="form-label">Lost Reason</label>
                            <select name="lost_reason" class="form-select mb-3" required>
                                <option value="">Select reason</option>
                                @foreach($closedLostReasons as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>

                            <textarea name="notes" rows="2" class="form-control mb-3" placeholder="Optional close note"></textarea>
                            <button type="submit" class="action-btn action-danger">Close Lost</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .manager-opportunity-show-page {
        width: 100%;
    }

    .opportunity-summary-grid,
    .opportunity-linked-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .linked-record-card {
        min-height: 118px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        border-radius: 16px;
        border: 1px solid var(--sf-border-light);
        padding: 16px;
        background: var(--sf-surface-soft);
    }

    .linked-record-card span,
    .detail-label,
    .timeline-label {
        color: var(--sf-muted);
        font-size: 11px;
        font-weight: 950;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .linked-record-card strong,
    .detail-value,
    .timeline-value {
        color: var(--sf-text-strong);
        font-weight: 900;
        overflow-wrap: anywhere;
    }

    .linked-record-card a {
        color: var(--sf-orange);
        font-size: 12px;
        font-weight: 950;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .detail-card {
        min-height: 88px;
        border-radius: 16px;
        border: 1px solid var(--sf-border-light);
        padding: 16px;
        background: var(--sf-surface-soft);
    }

    .detail-label,
    .detail-value {
        display: block;
    }

    .detail-label {
        margin-bottom: 6px;
    }

    .notes-box {
        padding-top: 20px;
        border-top: 1px solid var(--sf-border-light);
    }

    .notes-content {
        margin-top: 8px;
        border-radius: 16px;
        border: 1px solid rgba(249, 115, 22, 0.24);
        padding: 16px;
        background: var(--sf-orange-soft);
        color: var(--sf-text);
        font-weight: 750;
        white-space: pre-line;
    }

    .timeline-list {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .timeline-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .timeline-dot {
        width: 12px;
        height: 12px;
        margin-top: 4px;
        border-radius: 999px;
        background: var(--sf-muted);
        box-shadow: 0 0 0 3px var(--sf-surface-soft);
        flex: 0 0 auto;
    }

    .timeline-dot.active {
        background: var(--sf-primary);
    }

    .empty-mini {
        border-radius: 14px;
        border: 1px dashed var(--sf-border-light);
        padding: 16px;
        background: var(--sf-surface-soft);
        color: var(--sf-muted);
        font-size: 13px;
        font-weight: 800;
        text-align: center;
    }

    .inline-check {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        color: var(--sf-text);
        font-size: 13px;
        font-weight: 850;
    }

    .inline-check input {
        width: auto;
        min-height: auto;
    }

    @media (max-width: 768px) {
        .opportunity-summary-grid,
        .opportunity-linked-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush
