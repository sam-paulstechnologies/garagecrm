@extends('layouts.manager')

@section('title', 'Booking #' . $booking->id)

@section('content')
@php
    use Illuminate\Support\Facades\Route;

    $bookingName = function ($booking) {
        return $booking->client?->name
            ?? $booking->customer_name
            ?? $booking->name
            ?? 'Customer';
    };

    $bookingPhone = function ($booking) {
        return $booking->client?->phone
            ?? $booking->client?->whatsapp
            ?? $booking->phone
            ?? $booking->mobile
            ?? $booking->phone_number
            ?? $booking->whatsapp_number
            ?? 'No phone';
    };

    $bookingEmail = function ($booking) {
        return $booking->client?->email
            ?? $booking->email
            ?? 'No email';
    };

    $bookingVehicle = function ($booking) {
        $make = $booking->vehicleData?->make?->name
            ?? $booking->vehicle_make
            ?? $booking->make
            ?? null;

        $model = $booking->vehicleData?->model?->name
            ?? $booking->vehicle_model
            ?? $booking->model
            ?? null;

        $vehicle = trim(implode(' ', array_filter([$make, $model])));

        return $vehicle ?: 'Vehicle not linked';
    };

    $bookingPlate = function ($booking) {
        return $booking->vehicleData?->plate_number
            ?? $booking->plate_number
            ?? 'Not available';
    };

    $bookingVin = function ($booking) {
        return $booking->vehicleData?->vin
            ?? $booking->vin
            ?? 'Not available';
    };

    $bookingYear = function ($booking) {
        return $booking->vehicleData?->year
            ?? $booking->vehicle_year
            ?? $booking->year
            ?? 'Not available';
    };

    $formatDate = function ($value, $fallback = 'Not available') {
        if (! $value) {
            return $fallback;
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d M Y');
        } catch (\Throwable $e) {
            return $value;
        }
    };

    $formatInputDate = function ($value) {
        if (! $value) {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
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

    $bookingDateValue = $booking->booking_date
        ?? $booking->scheduled_date
        ?? $booking->date
        ?? null;

    $bookingTimeValue = $booking->booking_time
        ?? $booking->scheduled_time
        ?? $booking->time
        ?? null;

    $bookingSlotValue = $booking->slot
        ?? $booking->time_slot
        ?? null;

    $statusValue = $booking->status ?? 'pending';

    $statusClass = match(strtolower((string) $statusValue)) {
        'pending' => 'badge-soft-warning',
        'scheduled', 'confirmed' => 'badge-soft-primary',
        'reschedule_required' => 'badge-soft-danger',
        'converted_to_job' => 'badge-soft-success',
        'lost', 'rejected', 'cancelled', 'canceled' => 'badge-soft-danger',
        default => 'badge-soft-muted',
    };

    $statusLabel = match(strtolower((string) $statusValue)) {
        'pending' => 'Manager Confirmation',
        'scheduled', 'confirmed' => 'Booking Confirmed',
        'reschedule_required' => 'Rescheduling Required',
        'converted_to_job' => 'Converted To Job',
        'lost' => 'Lost',
        default => ucfirst(str_replace('_', ' ', $statusValue)),
    };

    $canConfirm = $statusValue === 'pending';
    $canReschedule = in_array($statusValue, ['pending', 'scheduled', 'confirmed'], true);
    $canConvertToJob = in_array($statusValue, ['pending', 'scheduled', 'confirmed'], true);
    $canReject = ! in_array($statusValue, ['lost', 'converted_to_job'], true);
@endphp

<div class="manager-booking-show-page">

    {{-- Header --}}
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <div class="sf-kicker">
                Booking Review
            </div>

            <h1 class="sf-page-title mt-2">
                Booking #{{ $booking->id }}
            </h1>

            <p class="sf-page-subtitle">
                Review customer booking details and take manager action.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @if(Route::has('manager.bookings.index'))
                <a href="{{ route('manager.bookings.index') }}"
                   class="sf-action-button light">
                    Back to Bookings
                </a>
            @endif

            @if(Route::has('manager.dashboard'))
                <a href="{{ route('manager.dashboard') }}"
                   class="sf-action-button primary">
                    Dashboard
                </a>
            @endif
        </div>
    </div>


    {{-- Flash --}}
    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    {{-- Errors --}}
    @if($errors->any())
        <div class="alert alert-danger mb-4">
            <p class="fw-bold mb-2">Please fix the following:</p>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <div class="row g-4">

        {{-- Main Details --}}
        <div class="col-12 col-xl-8">

            {{-- Booking Summary --}}
            <div class="sf-panel mb-4">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">
                            Booking Summary
                        </h2>
                        <p class="sf-panel-subtitle">
                            Current booking and customer request details.
                        </p>
                    </div>

                    <span class="manager-badge {{ $statusClass }}">
                        {{ $statusLabel }}
                    </span>
                </div>

                <div class="sf-panel-body">
                    <div class="row g-4">

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Customer</span>
                                <span class="detail-value">{{ $bookingName($booking) }}</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Phone</span>
                                <span class="detail-value">{{ $bookingPhone($booking) }}</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Email</span>
                                <span class="detail-value">{{ $bookingEmail($booking) }}</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Booking Date</span>
                                <span class="detail-value">{{ $formatDate($bookingDateValue, 'No date') }}</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Booking Time</span>
                                <span class="detail-value">{{ $bookingTimeValue ?: 'No time' }}</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Slot</span>
                                <span class="detail-value">
                                    {{ $bookingSlotValue ? ucfirst(str_replace('_', ' ', $bookingSlotValue)) : 'No slot' }}
                                </span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Service Type</span>
                                <span class="detail-value">{{ $booking->service_type ?: 'Service booking' }}</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Priority</span>
                                <span class="detail-value">{{ ucfirst($booking->priority ?? 'medium') }}</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Assigned To</span>
                                <span class="detail-value">{{ $booking->assignedUser?->name ?? 'Not assigned' }}</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Expected Close Date</span>
                                <span class="detail-value">
                                    {{ $formatDate($booking->expected_close_date ?? null, 'Not set') }}
                                </span>
                            </div>
                        </div>

                    </div>

                    @if($booking->notes || $booking->manager_notes)
                        <div class="notes-box mt-4">
                            <span class="detail-label">Notes</span>
                            <div class="notes-content">
                                {{ $booking->manager_notes ?? $booking->notes }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>


            {{-- Vehicle --}}
            <div class="sf-panel mb-4">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">
                            Vehicle
                        </h2>
                        <p class="sf-panel-subtitle">
                            Vehicle details linked with this booking.
                        </p>
                    </div>
                </div>

                <div class="sf-panel-body">
                    <div class="row g-4">

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Vehicle</span>
                                <span class="detail-value">{{ $bookingVehicle($booking) }}</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Plate Number</span>
                                <span class="detail-value">{{ $bookingPlate($booking) }}</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">VIN</span>
                                <span class="detail-value">{{ $bookingVin($booking) }}</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Year</span>
                                <span class="detail-value">{{ $bookingYear($booking) }}</span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>


            {{-- Opportunity --}}
            <div class="sf-panel">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">
                            Opportunity
                        </h2>
                        <p class="sf-panel-subtitle">
                            Opportunity linked with this booking.
                        </p>
                    </div>
                </div>

                <div class="sf-panel-body">
                    @if($booking->opportunity)
                        <div class="row g-4">

                            <div class="col-12 col-md-6">
                                <div class="detail-card">
                                    <span class="detail-label">Opportunity</span>
                                    <span class="detail-value">
                                        {{ $booking->opportunity->title ?? $booking->opportunity->name ?? 'Opportunity #' . $booking->opportunity->id }}
                                    </span>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="detail-card">
                                    <span class="detail-label">Stage</span>
                                    <span class="detail-value">
                                        {{ ucfirst(str_replace('_', ' ', $booking->opportunity->stage ?? '')) ?: 'Not set' }}
                                    </span>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="detail-card">
                                    <span class="detail-label">Converted</span>
                                    <span class="detail-value">
                                        {{ $booking->opportunity->is_converted ? 'Yes' : 'No' }}
                                    </span>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="detail-card">
                                    <span class="detail-label">Source</span>
                                    <span class="detail-value">
                                        {{ ucfirst($booking->opportunity->source ?? 'Unknown') }}
                                    </span>
                                </div>
                            </div>

                        </div>
                    @else
                        <div class="empty-mini">
                            No opportunity linked.
                        </div>
                    @endif
                </div>
            </div>

        </div>


        {{-- Actions --}}
        <div class="col-12 col-xl-4">

            <div class="sf-panel mb-4">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">
                            Manager Actions
                        </h2>
                        <p class="sf-panel-subtitle">
                            Move this booking forward.
                        </p>
                    </div>
                </div>

                <div class="sf-panel-body">

                    @if($canConfirm && Route::has('manager.bookings.confirm'))
                        <form method="POST"
                              action="{{ route('manager.bookings.confirm', $booking) }}"
                              onsubmit="return confirm('Confirm this booking and notify the customer?');"
                              class="mb-3">
                            @csrf

                            <button type="submit" class="action-btn action-primary">
                                Confirm Booking
                            </button>
                        </form>
                    @endif

                    @if($canReschedule && Route::has('manager.bookings.reschedule'))
                        <div class="reschedule-box mb-3">
                            <h3>
                                Reschedule Booking
                            </h3>

                            <form method="POST"
                                  action="{{ route('manager.bookings.reschedule', $booking) }}"
                                  onsubmit="return confirm('Reschedule this booking?');">
                                @csrf
                                @method('PATCH')

                                <div class="mb-3">
                                    <label class="form-label">New Booking Date</label>
                                    <input
                                        type="date"
                                        name="booking_date"
                                        value="{{ $formatInputDate($bookingDateValue) }}"
                                        min="{{ now()->format('Y-m-d') }}"
                                        class="form-control"
                                        required
                                    >
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">New Booking Time</label>
                                    <input
                                        type="time"
                                        name="booking_time"
                                        value="{{ $bookingTimeValue }}"
                                        class="form-control"
                                    >
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Slot</label>
                                    <select name="slot" class="form-select">
                                        <option value="">Select Slot</option>
                                        <option value="morning" @selected($bookingSlotValue === 'morning')>Morning</option>
                                        <option value="afternoon" @selected($bookingSlotValue === 'afternoon')>Afternoon</option>
                                        <option value="evening" @selected($bookingSlotValue === 'evening')>Evening</option>
                                        <option value="pickup" @selected($bookingSlotValue === 'pickup')>Pickup</option>
                                        <option value="dropoff" @selected($bookingSlotValue === 'dropoff')>Dropoff</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Reason / Notes</label>
                                    <textarea
                                        name="notes"
                                        rows="3"
                                        class="form-control"
                                        placeholder="Why is this booking being rescheduled?"
                                    ></textarea>
                                </div>

                                <button type="submit" class="action-btn action-orange">
                                    Reschedule Booking
                                </button>
                            </form>
                        </div>
                    @endif

                    @if($canConvertToJob && Route::has('manager.bookings.convert-to-job'))
                        <form method="POST"
                              action="{{ route('manager.bookings.convert-to-job', $booking) }}"
                              onsubmit="return confirm('Convert this booking into a job?');"
                              class="mb-3">
                            @csrf

                            <button type="submit" class="action-btn action-success">
                                Convert To Job
                            </button>
                        </form>
                    @endif

                    @if($job)
                        <div class="linked-job-box mb-3">
                            This booking is already linked to Job #{{ $job->id }}.
                        </div>

                        @if(Route::has('manager.jobs.show'))
                            <a href="{{ route('manager.jobs.show', $job) }}"
                               class="action-btn action-light mb-3">
                                Open Job
                            </a>
                        @endif
                    @endif

                    @if($canReject && Route::has('manager.bookings.reject'))
                        <div class="reject-box">
                            <h3>
                                Reject / Mark Lost
                            </h3>

                            <form method="POST"
                                  action="{{ route('manager.bookings.reject', $booking) }}"
                                  onsubmit="return confirm('Reject this booking?');">
                                @csrf

                                <div class="mb-3">
                                    <label class="form-label">Reason</label>
                                    <select name="lost_reason"
                                            class="form-select"
                                            required>
                                        <option value="">Select reason</option>
                                        <option value="cancelled_by_customer">Cancelled by customer</option>
                                        <option value="rejected_by_garage">Rejected by garage</option>
                                        <option value="no_show">No show</option>
                                        <option value="slot_unavailable">Slot unavailable</option>
                                        <option value="duplicate">Duplicate</option>
                                        <option value="wrong_booking">Wrong booking</option>
                                        <option value="price_issue">Price issue</option>
                                        <option value="customer_postponed">Customer postponed</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Optional Note</label>
                                    <textarea name="notes"
                                              rows="3"
                                              class="form-control"
                                              placeholder="Optional note"></textarea>
                                </div>

                                <button type="submit" class="action-btn action-danger">
                                    Reject Booking
                                </button>
                            </form>
                        </div>
                    @endif

                    @if(! $canConfirm && ! $canReschedule && ! $canConvertToJob && ! $canReject && ! $job)
                        <div class="empty-mini">
                            No manager actions available for this status.
                        </div>
                    @endif

                </div>
            </div>


            <div class="sf-panel">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">
                            Status Timeline
                        </h2>
                        <p class="sf-panel-subtitle">
                            Booking state history.
                        </p>
                    </div>
                </div>

                <div class="sf-panel-body">
                    <div class="timeline-list">

                        <div class="timeline-item">
                            <span class="timeline-dot active"></span>
                            <div>
                                <span class="timeline-label">Created</span>
                                <span class="timeline-value">
                                    {{ $formatDateTime($booking->created_at ?? null, 'Not available') }}
                                </span>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <span class="timeline-dot {{ $booking->confirmed_at ? 'active' : '' }}"></span>
                            <div>
                                <span class="timeline-label">Confirmed At</span>
                                <span class="timeline-value">
                                    {{ $formatDateTime($booking->confirmed_at ?? null, 'Not confirmed') }}
                                </span>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <span class="timeline-dot {{ $booking->completed_at ? 'active' : '' }}"></span>
                            <div>
                                <span class="timeline-label">Completed At</span>
                                <span class="timeline-value">
                                    {{ $formatDateTime($booking->completed_at ?? null, 'Not completed') }}
                                </span>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <span class="timeline-dot {{ $booking->state_changed_at ? 'active' : '' }}"></span>
                            <div>
                                <span class="timeline-label">Last Changed</span>
                                <span class="timeline-value">
                                    {{ $formatDateTime($booking->state_changed_at ?? null, 'Not available') }}
                                </span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection

@push('styles')
<style>
    .manager-booking-show-page {
        width: 100%;
    }

    .sf-kicker {
        display: inline-flex;
        align-items: center;
        width: max-content;
        border-radius: 999px;
        padding: 7px 12px;
        color: #ffffff;
        background: linear-gradient(135deg, #ea580c, #f97316);
        border: 1px solid rgba(251, 191, 36, 0.35);
        font-size: 11px;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        box-shadow: 0 10px 24px rgba(234, 88, 12, 0.24);
    }

    .sf-page-title {
        color: #ffffff !important;
        font-size: 38px !important;
        line-height: 1.05;
        font-weight: 950 !important;
        letter-spacing: -0.045em;
        margin-bottom: 8px;
    }

    .sf-page-subtitle {
        color: #cbd5e1 !important;
        font-size: 15px;
        font-weight: 700;
    }

    .sf-panel {
        background: #ffffff;
        border: 1px solid #d9e1ec;
        border-radius: 20px;
        box-shadow: 0 22px 55px rgba(0, 0, 0, 0.22);
    }

    .sf-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 22px 24px;
        border-bottom: 1px solid #e5eaf1;
        background: linear-gradient(180deg, #ffffff, #f8fafc);
    }

    .sf-panel-title {
        color: #020617;
        font-size: 18px;
        font-weight: 950;
        letter-spacing: -0.025em;
        margin: 0;
    }

    .sf-panel-subtitle {
        color: #475569;
        font-size: 13px;
        font-weight: 750;
        margin-top: 4px;
    }

    .sf-panel-body {
        padding: 24px;
    }

    .fw-black {
        font-weight: 950;
    }

    .detail-card {
        min-height: 88px;
        padding: 16px;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
    }

    .detail-label {
        display: block;
        color: #64748b;
        font-size: 11px;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 6px;
    }

    .detail-value {
        display: block;
        color: #020617;
        font-size: 15px;
        font-weight: 900;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .notes-box {
        padding-top: 20px;
        border-top: 1px solid #e5eaf1;
    }

    .notes-content {
        margin-top: 8px;
        padding: 16px;
        border-radius: 16px;
        border: 1px solid #fed7aa;
        background: #fff7ed;
        color: #431407;
        font-size: 14px;
        font-weight: 700;
        white-space: pre-line;
    }

    .empty-mini {
        padding: 18px;
        border-radius: 14px;
        border: 1px dashed #cbd5e1;
        background: #f8fafc;
        color: #475569;
        font-size: 14px;
        font-weight: 750;
        text-align: center;
    }

    .manager-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 950;
        line-height: 1;
        border: 1px solid transparent;
        white-space: nowrap;
        text-transform: capitalize;
    }

    .badge-soft-primary {
        color: #1d4ed8;
        background: #eff6ff;
        border-color: #93c5fd;
    }

    .badge-soft-success {
        color: #15803d;
        background: #ecfdf5;
        border-color: #86efac;
    }

    .badge-soft-warning {
        color: #92400e;
        background: #fffbeb;
        border-color: #facc15;
    }

    .badge-soft-danger {
        color: #b91c1c;
        background: #fef2f2;
        border-color: #fca5a5;
    }

    .badge-soft-muted {
        color: #334155;
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .action-btn {
        width: 100%;
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        padding: 10px 14px;
        font-size: 13px;
        font-weight: 950;
        text-decoration: none;
        border: 1px solid transparent;
    }

    .action-primary {
        color: #ffffff;
        background: #2563eb;
        border-color: #2563eb;
        box-shadow: 0 10px 22px rgba(37, 99, 235, 0.22);
    }

    .action-primary:hover {
        color: #ffffff;
        background: #1d4ed8;
        border-color: #1d4ed8;
    }

    .action-success {
        color: #ffffff;
        background: #16a34a;
        border-color: #16a34a;
        box-shadow: 0 10px 22px rgba(22, 163, 74, 0.18);
    }

    .action-success:hover {
        color: #ffffff;
        background: #15803d;
        border-color: #15803d;
    }

    .action-orange {
        color: #ffffff;
        background: #ea580c;
        border-color: #ea580c;
        box-shadow: 0 10px 22px rgba(234, 88, 12, 0.18);
    }

    .action-orange:hover {
        color: #ffffff;
        background: #c2410c;
        border-color: #c2410c;
    }

    .action-danger {
        color: #ffffff;
        background: #dc2626;
        border-color: #dc2626;
        box-shadow: 0 10px 22px rgba(220, 38, 38, 0.18);
    }

    .action-danger:hover {
        color: #ffffff;
        background: #b91c1c;
        border-color: #b91c1c;
    }

    .action-light {
        color: #0f172a;
        background: #ffffff;
        border-color: #cbd5e1;
    }

    .action-light:hover {
        color: #0f172a;
        background: #f8fafc;
        border-color: #94a3b8;
    }

    .linked-job-box {
        padding: 14px;
        border-radius: 14px;
        color: #166534;
        background: #f0fdf4;
        border: 1px solid #86efac;
        font-size: 13px;
        font-weight: 850;
    }

    .reschedule-box {
        padding: 16px;
        border-radius: 16px;
        background: #fff7ed;
        border: 1px solid #fed7aa;
    }

    .reschedule-box h3,
    .reject-box h3 {
        color: #020617;
        font-size: 15px;
        font-weight: 950;
        margin: 0 0 12px;
    }

    .reject-box {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #e5eaf1;
    }

    .form-label {
        color: #0f172a !important;
        font-size: 12px;
        font-weight: 950 !important;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .form-control,
    .form-select {
        min-height: 44px;
        border-radius: 10px;
        border: 1px solid #b8c3d1;
        color: #020617;
        background-color: #ffffff;
        font-size: 14px;
        font-weight: 700;
    }

    .form-control::placeholder {
        color: #64748b;
        font-weight: 650;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.15);
    }

    .timeline-list {
        position: relative;
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
        background: #cbd5e1;
        border: 2px solid #ffffff;
        box-shadow: 0 0 0 3px #e2e8f0;
        flex: 0 0 auto;
    }

    .timeline-dot.active {
        background: #2563eb;
        box-shadow: 0 0 0 3px #bfdbfe;
    }

    .timeline-label {
        display: block;
        color: #64748b;
        font-size: 11px;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 3px;
    }

    .timeline-value {
        display: block;
        color: #020617;
        font-size: 13px;
        font-weight: 850;
    }

    @media (max-width: 768px) {
        .sf-page-title {
            font-size: 30px !important;
        }

        .sf-panel-header,
        .sf-panel-body {
            padding: 18px;
        }

        .detail-card {
            min-height: auto;
        }
    }
</style>
@endpush
