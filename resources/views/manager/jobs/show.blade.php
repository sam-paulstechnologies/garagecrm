@extends('layouts.manager')

@section('title', $job->job_code ?: 'Job #' . $job->id)

@section('content')
@php
    use Illuminate\Support\Facades\Route;

    $statusValue = $job->status ?? 'pending';

    $statusClass = match(strtolower((string) $statusValue)) {
        'pending' => 'badge-soft-warning',
        'in_progress' => 'badge-soft-primary',
        'completed' => 'badge-soft-success',
        default => 'badge-soft-muted',
    };

    $booking = $job->booking;

    $customerName = $job->client?->name
        ?? $booking?->client?->name
        ?? $booking?->customer_name
        ?? $booking?->name
        ?? 'Customer not linked';

    $customerPhone = $job->client?->phone
        ?? $job->client?->whatsapp
        ?? $booking?->client?->phone
        ?? $booking?->client?->whatsapp
        ?? $booking?->phone
        ?? $booking?->whatsapp_number
        ?? 'No phone';

    $make = $booking?->vehicleData?->make?->name
        ?? $booking?->vehicle_make
        ?? $booking?->make
        ?? null;

    $model = $booking?->vehicleData?->model?->name
        ?? $booking?->vehicle_model
        ?? $booking?->model
        ?? null;

    $vehicle = trim(implode(' ', array_filter([$make, $model])));

    $bookingDateValue = $booking?->booking_date
        ?? $booking?->scheduled_date
        ?? $booking?->date
        ?? null;

    $bookingSlotValue = $booking?->slot
        ?? $booking?->time_slot
        ?? null;

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

    $invoiceNumber = $invoice->invoice_number
        ?? $invoice->reference_number
        ?? null;

    $invoiceTotal = $invoice->total_amount
        ?? $invoice->grand_total
        ?? $invoice->amount
        ?? null;

    $invoiceStatus = $invoice->status
        ?? $invoice->payment_status
        ?? null;
@endphp

<div class="manager-job-show-page">

    {{-- Header --}}
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <div class="sf-kicker">
                Job Review
            </div>

            <h1 class="sf-page-title mt-2">
                {{ $job->job_code ?: 'Job #' . $job->id }}
            </h1>

            <p class="sf-page-subtitle">
                Review job details, capture invoice, update progress, and complete the job.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @if(Route::has('manager.jobs.index'))
                <a href="{{ route('manager.jobs.index') }}"
                   class="sf-action-button light">
                    Back to Jobs
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

            {{-- Job Summary --}}
            <div class="sf-panel mb-4">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">
                            Job Summary
                        </h2>
                        <p class="sf-panel-subtitle">
                            Current job status and linked customer information.
                        </p>
                    </div>

                    <span class="manager-badge {{ $statusClass }}">
                        {{ ucfirst(str_replace('_', ' ', $statusValue)) }}
                    </span>
                </div>

                <div class="sf-panel-body">
                    <div class="row g-4">

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Customer</span>
                                <span class="detail-value">
                                    {{ $customerName }}
                                </span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Phone</span>
                                <span class="detail-value">
                                    {{ $customerPhone }}
                                </span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Assigned To</span>
                                <span class="detail-value">
                                    {{ $job->assignedUser?->name ?? 'Not assigned' }}
                                </span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Created</span>
                                <span class="detail-value">
                                    {{ $formatDateTime($job->created_at ?? null, 'Not available') }}
                                </span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Start Time</span>
                                <span class="detail-value">
                                    {{ $formatDateTime($job->start_time ?? null, 'Not started') }}
                                </span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">End Time</span>
                                <span class="detail-value">
                                    {{ $formatDateTime($job->end_time ?? null, 'Not completed') }}
                                </span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Vehicle Mileage</span>
                                <span class="detail-value">
                                    {{ $job->vehicle_mileage ? number_format($job->vehicle_mileage) . ' km' : 'Not entered' }}
                                </span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Total Time</span>
                                <span class="detail-value">
                                    {{ $job->total_time_minutes ? $job->total_time_minutes . ' minutes' : 'Not entered' }}
                                </span>
                            </div>
                        </div>

                    </div>

                    <div class="notes-box mt-4">
                        <span class="detail-label">Description</span>
                        <div class="notes-content">
                            {{ $job->description ?: 'No description added.' }}
                        </div>
                    </div>
                </div>
            </div>


            {{-- Booking / Vehicle --}}
            <div class="sf-panel mb-4">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">
                            Linked Booking & Vehicle
                        </h2>
                        <p class="sf-panel-subtitle">
                            Booking and vehicle details connected with this job.
                        </p>
                    </div>
                </div>

                <div class="sf-panel-body">
                    @if($booking)
                        <div class="row g-4">

                            <div class="col-12 col-md-6">
                                <div class="detail-card">
                                    <span class="detail-label">Booking</span>
                                    <span class="detail-value">
                                        Booking #{{ $booking->id }}
                                    </span>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="detail-card">
                                    <span class="detail-label">Booking Status</span>
                                    <span class="detail-value">
                                        {{ ucfirst(str_replace('_', ' ', $booking->status ?? '')) ?: 'Not set' }}
                                    </span>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="detail-card">
                                    <span class="detail-label">Booking Date</span>
                                    <span class="detail-value">
                                        {{ $formatDate($bookingDateValue, 'No date') }}
                                    </span>
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
                                    <span class="detail-label">Vehicle</span>
                                    <span class="detail-value">
                                        {{ $vehicle ?: 'Vehicle not linked' }}
                                    </span>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="detail-card">
                                    <span class="detail-label">Plate Number</span>
                                    <span class="detail-value">
                                        {{ $booking->vehicleData?->plate_number ?? $booking->plate_number ?? 'Not available' }}
                                    </span>
                                </div>
                            </div>

                        </div>

                        <div class="mt-4">
                            @if(Route::has('manager.bookings.show'))
                                <a href="{{ route('manager.bookings.show', $booking) }}"
                                   class="action-btn action-light action-inline">
                                    Open Booking
                                </a>
                            @endif
                        </div>
                    @else
                        <div class="empty-mini">
                            No booking linked to this job.
                        </div>
                    @endif
                </div>
            </div>


            {{-- Work Details Form --}}
            <div class="sf-panel">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">
                            Work Details
                        </h2>
                        <p class="sf-panel-subtitle">
                            Update job notes, issues found, parts used, mileage, and time spent.
                        </p>
                    </div>
                </div>

                <div class="sf-panel-body">
                    <form method="POST"
                          action="{{ route('manager.jobs.work-details', $job) }}">

                        @csrf
                        @method('PATCH')

                        <div class="mb-3">
                            <label class="form-label">
                                Description
                            </label>
                            <textarea name="description"
                                      rows="3"
                                      class="form-control">{{ old('description', $job->description) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Work Summary
                            </label>
                            <textarea name="work_summary"
                                      rows="4"
                                      class="form-control">{{ old('work_summary', $job->work_summary) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Issues Found
                            </label>
                            <textarea name="issues_found"
                                      rows="3"
                                      class="form-control">{{ old('issues_found', $job->issues_found) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Parts Used
                            </label>
                            <textarea name="parts_used"
                                      rows="3"
                                      class="form-control">{{ old('parts_used', $job->parts_used) }}</textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">
                                    Vehicle Mileage
                                </label>
                                <input type="number"
                                       name="vehicle_mileage"
                                       min="0"
                                       class="form-control"
                                       value="{{ old('vehicle_mileage', $job->vehicle_mileage) }}">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">
                                    Total Time Minutes
                                </label>
                                <input type="number"
                                       name="total_time_minutes"
                                       min="0"
                                       class="form-control"
                                       value="{{ old('total_time_minutes', $job->total_time_minutes) }}">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="action-btn action-primary action-inline">
                                Save Work Details
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>


        {{-- Actions --}}
        <div class="col-12 col-xl-4">

            {{-- Invoice --}}
            <div class="sf-panel mb-4">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">
                            Invoice
                        </h2>
                        <p class="sf-panel-subtitle">
                            Capture invoice before completing the job.
                        </p>
                    </div>
                </div>

                <div class="sf-panel-body">
                    @if($invoice)
                        <div class="invoice-box mb-3">
                            <div class="invoice-row">
                                <span>Invoice</span>
                                <strong>{{ $invoiceNumber ?? 'Invoice #' . $invoice->id }}</strong>
                            </div>

                            <div class="invoice-row">
                                <span>Total</span>
                                <strong>{{ $invoiceTotal !== null ? number_format((float) $invoiceTotal, 2) : '0.00' }}</strong>
                            </div>

                            <div class="invoice-row">
                                <span>Status</span>
                                <strong>{{ $invoiceStatus ? ucfirst(str_replace('_', ' ', $invoiceStatus)) : 'Issued' }}</strong>
                            </div>
                        </div>
                    @else
                        <div class="empty-mini mb-3">
                            No invoice captured yet.
                        </div>
                    @endif

                    @if($statusValue !== 'completed' && Route::has('manager.jobs.complete-with-invoice'))
                        <button
                            type="button"
                            class="action-btn action-orange"
                            data-bs-toggle="modal"
                            data-bs-target="#completeWithInvoiceModal"
                        >
                            Complete Job + Capture Invoice
                        </button>
                    @elseif($statusValue === 'completed')
                        <div class="linked-job-box">
                            Job is completed. Feedback flow should be triggered if WhatsApp and queue are active.
                        </div>
                    @endif
                </div>
            </div>


            {{-- Status --}}
            <div class="sf-panel mb-4">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">
                            Job Status
                        </h2>
                        <p class="sf-panel-subtitle">
                            Complete the job using the invoice completion action.
                        </p>
                    </div>
                </div>

                <div class="sf-panel-body">

                    @if(Route::has('manager.jobs.status'))
                        <form method="POST"
                              action="{{ route('manager.jobs.status', $job) }}"
                              onsubmit="return confirm('Update this job status?');"
                              class="mb-3">

                            @csrf
                            @method('PATCH')

                            <div class="mb-3">
                                <label class="form-label">
                                    Status
                                </label>

                                <select name="status"
                                        class="form-select"
                                        required>
                                    <option value="pending" @selected($statusValue === 'pending')>Pending</option>
                                    <option value="in_progress" @selected($statusValue === 'in_progress')>In Progress</option>
                                </select>
                            </div>

                            <button type="submit" class="action-btn action-primary">
                                Update Status
                            </button>

                        </form>
                    @endif

                </div>
            </div>


            {{-- Assignment --}}
            <div class="sf-panel mb-4">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">
                            Assignment
                        </h2>
                        <p class="sf-panel-subtitle">
                            Assign this job to a team member.
                        </p>
                    </div>
                </div>

                <div class="sf-panel-body">
                    @if(Route::has('manager.jobs.assign'))
                        <form method="POST"
                              action="{{ route('manager.jobs.assign', $job) }}">

                            @csrf
                            @method('PATCH')

                            <div class="mb-3">
                                <label class="form-label">
                                    Team Member
                                </label>

                                <select name="assigned_to"
                                        class="form-select">
                                    <option value="">Unassigned</option>

                                    @foreach($teamMembers ?? [] as $member)
                                        <option value="{{ $member->id }}"
                                            @selected((int) old('assigned_to', $job->assigned_to) === (int) $member->id)>
                                            {{ $member->name }} @if($member->role) — {{ ucfirst($member->role) }} @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="action-btn action-dark">
                                Update Assignment
                            </button>

                        </form>
                    @else
                        <div class="empty-mini">
                            Assignment route not available.
                        </div>
                    @endif
                </div>
            </div>


            {{-- Timeline --}}
            <div class="sf-panel">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">
                            Timeline
                        </h2>
                        <p class="sf-panel-subtitle">
                            Job state history.
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
                                    {{ $formatDateTime($job->created_at ?? null, 'Not available') }}
                                </span>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <span class="timeline-dot {{ $job->start_time ? 'active' : '' }}"></span>
                            <div>
                                <span class="timeline-label">Started</span>
                                <span class="timeline-value">
                                    {{ $formatDateTime($job->start_time ?? null, 'Not started') }}
                                </span>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <span class="timeline-dot {{ $job->end_time ? 'active' : '' }}"></span>
                            <div>
                                <span class="timeline-label">Completed</span>
                                <span class="timeline-value">
                                    {{ $formatDateTime($job->end_time ?? null, 'Not completed') }}
                                </span>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <span class="timeline-dot {{ $job->updated_at ? 'active' : '' }}"></span>
                            <div>
                                <span class="timeline-label">Last Updated</span>
                                <span class="timeline-value">
                                    {{ $formatDateTime($job->updated_at ?? null, 'Not available') }}
                                </span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>

    </div>

</div>


{{-- Complete With Invoice Modal --}}
@if($statusValue !== 'completed' && Route::has('manager.jobs.complete-with-invoice'))
    <div class="modal fade" id="completeWithInvoiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content invoice-modal-content">
                <form method="POST"
                      action="{{ route('manager.jobs.complete-with-invoice', $job) }}"
                      onsubmit="return confirm('Complete this job and create/update the invoice?');">
                    @csrf
                    @method('PATCH')

                    <div class="modal-header invoice-modal-header">
                        <div>
                            <h5 class="modal-title">
                                Complete Job + Capture Invoice
                            </h5>
                            <p class="invoice-modal-subtitle mb-0">
                                {{ $job->job_code ?: 'Job #' . $job->id }} · {{ $customerName }}
                            </p>
                        </div>

                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body invoice-modal-body">
                        <div class="row g-3">

                            <div class="col-12 col-md-6">
                                <label class="form-label">Labour Amount</label>
                                <input type="number" step="0.01" min="0" name="labour_amount" class="form-control" value="0">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Parts Amount</label>
                                <input type="number" step="0.01" min="0" name="parts_amount" class="form-control" value="0">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Discount Amount</label>
                                <input type="number" step="0.01" min="0" name="discount_amount" class="form-control" value="0">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">VAT Rate %</label>
                                <input type="number" step="0.01" min="0" max="100" name="vat_rate" class="form-control" value="5">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Work Summary</label>
                                <textarea name="work_summary" rows="3" class="form-control">{{ old('work_summary', $job->work_summary) }}</textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Issues Found</label>
                                <textarea name="issues_found" rows="3" class="form-control">{{ old('issues_found', $job->issues_found) }}</textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Parts Used</label>
                                <textarea name="parts_used" rows="3" class="form-control">{{ old('parts_used', $job->parts_used) }}</textarea>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Vehicle Mileage</label>
                                <input type="number" min="0" name="vehicle_mileage" class="form-control" value="{{ old('vehicle_mileage', $job->vehicle_mileage) }}">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Total Time Minutes</label>
                                <input type="number" min="0" name="total_time_minutes" class="form-control" value="{{ old('total_time_minutes', $job->total_time_minutes) }}">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Invoice Notes</label>
                                <textarea name="invoice_notes" rows="3" class="form-control" placeholder="Payment notes, invoice remarks, customer instructions..."></textarea>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer invoice-modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-primary">
                            Complete Job & Save Invoice
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@push('styles')
<style>
    .manager-job-show-page {
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

    textarea.form-control {
        min-height: auto;
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

    .action-inline {
        width: auto;
        min-width: 170px;
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

    .action-dark {
        color: #ffffff;
        background: #0f172a;
        border-color: #0f172a;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.18);
    }

    .action-dark:hover {
        color: #ffffff;
        background: #020617;
        border-color: #020617;
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

    .invoice-box {
        padding: 16px;
        border-radius: 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .invoice-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 8px 0;
        border-bottom: 1px solid #e2e8f0;
        font-size: 13px;
    }

    .invoice-row:last-child {
        border-bottom: 0;
    }

    .invoice-row span {
        color: #64748b;
        font-weight: 850;
    }

    .invoice-row strong {
        color: #020617;
        font-weight: 950;
        text-align: right;
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

    .invoice-modal-content {
        border: 0;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 28px 80px rgba(2, 6, 23, 0.36);
    }

    .invoice-modal-header {
        color: #ffffff;
        background:
            radial-gradient(circle at top left, rgba(234, 88, 12, 0.45), transparent 34%),
            linear-gradient(135deg, #060b16, #0f172a);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding: 22px 24px;
    }

    .invoice-modal-header .modal-title {
        font-size: 22px;
        font-weight: 950;
        letter-spacing: -0.035em;
    }

    .invoice-modal-subtitle {
        color: #cbd5e1;
        font-size: 13px;
        font-weight: 700;
        margin-top: 4px;
    }

    .invoice-modal-body {
        padding: 24px;
        background: #ffffff;
    }

    .invoice-modal-footer {
        padding: 18px 24px;
        border-top: 1px solid #e5eaf1;
        background: #f8fafc;
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

        .action-inline {
            width: 100%;
        }
    }
</style>
@endpush
