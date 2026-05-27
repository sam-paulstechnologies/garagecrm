@extends('layouts.manager')

@section('title', 'Manager Opportunities')

@section('content')
@php
    use Illuminate\Support\Facades\Route;

    $stageLabels = $stageLabels ?? [
        'new' => 'New',
        'attempting_contact' => 'Attempting Contact',
        'collecting_details' => 'Collecting Details',
        'manager_confirmation_pending' => 'Manager Confirmation Pending',
        'appointment' => 'Appointment',
        'offer' => 'Offer',
        'closed_won' => 'Closed Won',
        'closed_lost' => 'Closed Lost',
    ];

    $opportunityStages = $opportunityStages ?? array_keys($stageLabels);

    $normalizeStage = function ($stage) {
        $stage = strtolower(trim((string) $stage));
        $stage = str_replace(['-', ' '], '_', $stage);

        return match ($stage) {
            'new' => 'new',
            'attempting_contact', 'attempting', 'contacting', 'contacted' => 'attempting_contact',
            'collecting_details', 'collecting', 'details', 'details_collection' => 'collecting_details',
            'manager_confirmation_pending', 'manager_confirmation', 'confirmation_pending' => 'manager_confirmation_pending',
            'appointment', 'scheduled', 'booking_scheduled' => 'appointment',
            'offer', 'quotation', 'quote', 'follow_up' => 'offer',
            'closed_won', 'won' => 'closed_won',
            'closed_lost', 'lost' => 'closed_lost',
            default => $stage,
        };
    };

    $stageLabel = function ($value) use ($stageLabels, $normalizeStage) {
        $normalized = $normalizeStage($value);

        return $stageLabels[$normalized] ?? ucwords(str_replace('_', ' ', $normalized ?: 'new'));
    };

    $opportunityName = function ($opportunity) {
        return $opportunity->title
            ?? $opportunity->name
            ?? $opportunity->customer_name
            ?? $opportunity->client_name
            ?? 'Opportunity #' . $opportunity->id;
    };

    $opportunityPhone = function ($opportunity) {
        return $opportunity->phone
            ?? $opportunity->mobile
            ?? $opportunity->phone_number
            ?? $opportunity->whatsapp_number
            ?? '-';
    };

    $opportunityVehicle = function ($opportunity) {
        $make = $opportunity->vehicle_make ?? $opportunity->make ?? null;
        $model = $opportunity->vehicle_model ?? $opportunity->model ?? null;

        return trim(($make ?? '') . ' ' . ($model ?? '')) ?: '-';
    };

    $assignedValue = function ($opportunity) {
        return $opportunity->assigned_to
            ?? $opportunity->assigned_to_id
            ?? $opportunity->assigned_user_id
            ?? $opportunity->manager_id
            ?? $opportunity->owner_id
            ?? $opportunity->user_id
            ?? null;
    };

    $statusClass = function ($status) {
        $status = strtolower((string) $status);

        return match ($status) {
            'won', 'closed_won' => 'badge-soft-success',
            'lost', 'closed_lost' => 'badge-soft-danger',
            'open', 'active' => 'badge-soft-primary',
            default => 'badge-soft-muted',
        };
    };

    $stageClass = function ($stage) use ($normalizeStage) {
        $stage = $normalizeStage($stage);

        return match ($stage) {
            'new' => 'badge-soft-primary',
            'attempting_contact' => 'badge-soft-warning',
            'collecting_details' => 'badge-soft-info',
            'manager_confirmation_pending' => 'badge-soft-orange',
            'appointment' => 'badge-soft-info',
            'offer' => 'badge-soft-purple',
            'closed_won' => 'badge-soft-success',
            'closed_lost' => 'badge-soft-danger',
            default => 'badge-soft-muted',
        };
    };
@endphp

<div class="manager-opportunities-page">

    {{-- Header --}}
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <div class="sf-kicker">
                Manager Sales Pipeline
            </div>

            <h1 class="sf-page-title mt-2">
                Opportunities
            </h1>

            <p class="sf-page-subtitle">
                Move opportunities into scheduled bookings by selecting date, time, slot, and service details.
            </p>
        </div>

        @if(Route::has('manager.dashboard'))
            <a href="{{ route('manager.dashboard') }}"
               class="sf-action-button light">
                Back to Dashboard
            </a>
        @endif
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-4">
            <p class="fw-bold mb-2">Please check the form below.</p>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Filters --}}
    <div class="sf-panel mb-4">
        <div class="sf-panel-header">
            <div>
                <h2 class="sf-panel-title">Filter Opportunities</h2>
                <p class="sf-panel-subtitle">Search by customer, phone, email, vehicle, notes, stage, or status.</p>
            </div>
        </div>

        <div class="sf-panel-body">
            <form method="GET" action="{{ route('manager.opportunities.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg-5">
                        <label class="form-label fw-bold small text-muted">
                            Search
                        </label>
                        <input
                            type="text"
                            name="q"
                            value="{{ $q ?? request('q') }}"
                            class="form-control"
                            placeholder="Name, phone, email, vehicle, notes"
                        >
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label fw-bold small text-muted">
                            Stage
                        </label>
                        <select name="stage" class="form-select">
                            <option value="">All Stages</option>
                            @foreach($opportunityStages as $value)
                                <option value="{{ $value }}" @selected($normalizeStage($stage ?? request('stage')) === $normalizeStage($value))>
                                    {{ $stageLabel($value) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-6 col-lg-2">
                        <label class="form-label fw-bold small text-muted">
                            Status
                        </label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="active" @selected(($status ?? request('status')) === 'active')>Active</option>
                            <option value="open" @selected(($status ?? request('status')) === 'open')>Open</option>
                            <option value="won" @selected(($status ?? request('status')) === 'won')>Won</option>
                            <option value="lost" @selected(($status ?? request('status')) === 'lost')>Lost</option>
                        </select>
                    </div>

                    <div class="col-12 col-lg-2">
                        <button type="submit" class="sf-action-button primary w-100">
                            Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Opportunities Table --}}
    <div class="sf-panel overflow-hidden">
        <div class="sf-panel-header">
            <div>
                <h2 class="sf-panel-title">
                    Open Opportunities
                </h2>
                <p class="sf-panel-subtitle">
                    Use Schedule Booking when the customer confirms date and time.
                </p>
            </div>

            <span class="manager-count-pill">
                {{ method_exists($opportunities, 'total') ? $opportunities->total() : $opportunities->count() }} opportunity(s)
            </span>
        </div>

        @if($opportunities->count())
            <div class="opportunity-table-wrap">
                <table class="table table-hover manager-opportunities-table mb-0">
                    <thead>
                        <tr>
                            <th>Opportunity</th>
                            <th>Contact</th>
                            <th>Vehicle</th>
                            <th>Stage</th>
                            <th>Status</th>
                            <th>Follow-up</th>
                            <th>Assign</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($opportunities as $opportunity)
                            <tr>
                                <td>
                                    <div class="fw-black text-dark opportunity-title">
                                        {{ $opportunityName($opportunity) }}
                                    </div>

                                    <div class="small text-muted mt-1">
                                        #{{ $opportunity->id }}
                                        @if(!empty($opportunity->email))
                                            · {{ $opportunity->email }}
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <div class="fw-bold text-dark">
                                        {{ $opportunityPhone($opportunity) }}
                                    </div>
                                </td>

                                <td class="text-muted">
                                    {{ $opportunityVehicle($opportunity) }}
                                </td>

                                <td class="manager-stage-cell">
                                    @if(Route::has('manager.opportunities.stage'))
                                        <form method="POST" action="{{ route('manager.opportunities.stage', $opportunity) }}">
                                            @csrf
                                            @method('PATCH')

                                            <select
                                                name="stage"
                                                class="form-select form-select-sm"
                                                onchange="this.form.submit()"
                                            >
                                                @foreach($opportunityStages as $value)
                                                    <option value="{{ $value }}" @selected($normalizeStage($opportunity->stage ?? '') === $normalizeStage($value))>
                                                        {{ $stageLabel($value) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @else
                                        <span class="manager-badge {{ $stageClass($opportunity->stage ?? 'new') }}">
                                            {{ $stageLabel($opportunity->stage ?? 'new') }}
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <span class="manager-badge {{ $statusClass($opportunity->status ?? 'active') }}">
                                        {{ ucfirst(str_replace('_', ' ', $opportunity->status ?? 'active')) }}
                                    </span>
                                </td>

                                <td class="manager-followup-cell">
                                    @if(Route::has('manager.opportunities.follow-up'))
                                        <form method="POST"
                                              action="{{ route('manager.opportunities.follow-up', $opportunity) }}"
                                              class="followup-form">
                                            @csrf
                                            @method('PATCH')

                                            <input
                                                type="date"
                                                name="follow_up_date"
                                                value="{{ !empty($opportunity->follow_up_date) ? \Carbon\Carbon::parse($opportunity->follow_up_date)->format('Y-m-d') : '' }}"
                                                class="form-control form-control-sm"
                                            >

                                            <input type="hidden" name="follow_up_required" value="1">

                                            <button class="btn btn-sm btn-outline-primary">
                                                Save
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted">
                                            {{ !empty($opportunity->follow_up_date) ? \Carbon\Carbon::parse($opportunity->follow_up_date)->format('d M Y') : '-' }}
                                        </span>
                                    @endif
                                </td>

                                <td class="manager-assign-cell">
                                    @if(Route::has('manager.opportunities.assign') && ($managers ?? collect())->count())
                                        <form method="POST" action="{{ route('manager.opportunities.assign', $opportunity) }}">
                                            @csrf
                                            @method('PATCH')

                                            <select
                                                name="assigned_to"
                                                class="form-select form-select-sm"
                                                onchange="this.form.submit()"
                                            >
                                                <option value="">Select</option>
                                                @foreach($managers as $manager)
                                                    <option value="{{ $manager->id }}" @selected((string) $assignedValue($opportunity) === (string) $manager->id)>
                                                        {{ $manager->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td class="text-end manager-actions-cell">
                                    <div class="manager-actions-grid">

                                        @if(Route::has('manager.opportunities.schedule-booking'))
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-schedule action-span-2"
                                                data-bs-toggle="modal"
                                                data-bs-target="#scheduleBookingModal{{ $opportunity->id }}"
                                            >
                                                Schedule Booking
                                            </button>
                                        @endif

                                        @if(Route::has('manager.opportunities.show'))
                                            <a href="{{ route('manager.opportunities.show', $opportunity) }}"
                                               class="btn btn-sm btn-outline-secondary">
                                                View
                                            </a>
                                        @endif

                                        @if(Route::has('manager.opportunities.mark-won'))
                                            <form method="POST" action="{{ route('manager.opportunities.mark-won', $opportunity) }}">
                                                @csrf
                                                @method('PATCH')

                                                <button class="btn btn-sm btn-outline-success">
                                                    Won
                                                </button>
                                            </form>
                                        @endif

                                        @if(Route::has('manager.opportunities.mark-lost'))
                                            <form method="POST" action="{{ route('manager.opportunities.mark-lost', $opportunity) }}" class="action-span-2">
                                                @csrf
                                                @method('PATCH')

                                                <button class="btn btn-sm btn-outline-danger">
                                                    Lost
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            @if(!empty($opportunity->notes) || !empty($opportunity->manager_notes) || !empty($opportunity->internal_notes))
                                <tr class="manager-notes-row">
                                    <td colspan="8">
                                        <div class="small text-muted fw-bold text-uppercase">
                                            Latest Notes
                                        </div>
                                        <div class="mt-1 text-dark">
                                            {{ \Illuminate\Support\Str::limit($opportunity->manager_notes ?? $opportunity->internal_notes ?? $opportunity->notes, 220) }}
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="sf-empty">
                <h3>No open opportunities found</h3>
                <p>Opportunities needing manager follow-up will appear here.</p>
            </div>
        @endif

        @if(method_exists($opportunities, 'links'))
            <div class="manager-pagination">
                {{ $opportunities->links() }}
            </div>
        @endif
    </div>

</div>

{{-- Schedule Booking Modals --}}
@if($opportunities->count() && Route::has('manager.opportunities.schedule-booking'))
    @foreach($opportunities as $opportunity)
        <div
            class="modal fade"
            id="scheduleBookingModal{{ $opportunity->id }}"
            tabindex="-1"
            aria-labelledby="scheduleBookingModalLabel{{ $opportunity->id }}"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content schedule-modal-content">
                    <form method="POST" action="{{ route('manager.opportunities.schedule-booking', $opportunity) }}">
                        @csrf

                        <div class="modal-header schedule-modal-header">
                            <div>
                                <h5 class="modal-title" id="scheduleBookingModalLabel{{ $opportunity->id }}">
                                    Schedule Booking
                                </h5>
                                <p class="mb-0 schedule-modal-subtitle">
                                    {{ $opportunityName($opportunity) }} · {{ $opportunityPhone($opportunity) }}
                                </p>
                            </div>

                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body schedule-modal-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label">
                                        Booking Date <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        name="booking_date"
                                        class="form-control"
                                        min="{{ now()->format('Y-m-d') }}"
                                        required
                                    >
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">
                                        Booking Time
                                    </label>
                                    <input
                                        type="time"
                                        name="booking_time"
                                        class="form-control"
                                    >
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">
                                        Slot
                                    </label>
                                    <select name="slot" class="form-select">
                                        <option value="">Select Slot</option>
                                        <option value="morning">Morning</option>
                                        <option value="afternoon">Afternoon</option>
                                        <option value="evening">Evening</option>
                                        <option value="full_day">Full Day</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">
                                        Service Type
                                    </label>
                                    <input
                                        type="text"
                                        name="service_type"
                                        class="form-control"
                                        value="{{ $opportunity->service_type ?? '' }}"
                                        placeholder="Oil change, inspection, repair..."
                                    >
                                </div>

                                <div class="col-12">
                                    <label class="form-label">
                                        Booking Notes
                                    </label>
                                    <textarea
                                        name="notes"
                                        rows="4"
                                        class="form-control"
                                        placeholder="Add pickup notes, customer instructions, service details..."
                                    ></textarea>
                                </div>
                            </div>

                            <div class="schedule-summary mt-4">
                                <div class="row g-3">
                                    <div class="col-12 col-md-4">
                                        <span class="summary-label">Customer</span>
                                        <span class="summary-value">{{ $opportunityName($opportunity) }}</span>
                                    </div>

                                    <div class="col-12 col-md-4">
                                        <span class="summary-label">Phone</span>
                                        <span class="summary-value">{{ $opportunityPhone($opportunity) }}</span>
                                    </div>

                                    <div class="col-12 col-md-4">
                                        <span class="summary-label">Vehicle</span>
                                        <span class="summary-value">{{ $opportunityVehicle($opportunity) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer schedule-modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                Cancel
                            </button>

                            <button type="submit" class="btn btn-primary">
                                Confirm & Create Booking
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endif
@endsection

@push('styles')
<style>
    .manager-opportunities-page {
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

    .fw-black {
        font-weight: 950;
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

    .sf-action-button {
        min-height: 42px;
        padding: 0 18px;
        border-radius: 11px;
        font-size: 13px;
        font-weight: 950;
        letter-spacing: -0.01em;
    }

    .sf-action-button.primary {
        color: #ffffff;
        background: #2563eb;
        box-shadow: 0 10px 22px rgba(37, 99, 235, 0.22);
    }

    .sf-action-button.primary:hover {
        background: #1d4ed8;
    }

    .sf-action-button.light {
        color: #0f172a;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
    }

    .sf-action-button.light:hover {
        background: #f8fafc;
        border-color: #94a3b8;
    }

    .manager-count-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 9px 14px;
        color: #0f172a;
        background: #eaf1ff;
        border: 1px solid #bfdbfe;
        font-size: 12px;
        font-weight: 950;
        white-space: nowrap;
    }

    .opportunity-table-wrap {
        width: 100%;
        overflow-x: hidden;
    }

    .manager-opportunities-table {
        width: 100%;
        max-width: 100%;
        margin-bottom: 0;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
    }

    .manager-opportunities-table th,
    .manager-opportunities-table td {
        white-space: normal !important;
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .manager-opportunities-table th:nth-child(1),
    .manager-opportunities-table td:nth-child(1) {
        width: 13%;
    }

    .manager-opportunities-table th:nth-child(2),
    .manager-opportunities-table td:nth-child(2) {
        width: 8%;
    }

    .manager-opportunities-table th:nth-child(3),
    .manager-opportunities-table td:nth-child(3) {
        width: 8%;
    }

    .manager-opportunities-table th:nth-child(4),
    .manager-opportunities-table td:nth-child(4) {
        width: 16%;
    }

    .manager-opportunities-table th:nth-child(5),
    .manager-opportunities-table td:nth-child(5) {
        width: 8%;
    }

    .manager-opportunities-table th:nth-child(6),
    .manager-opportunities-table td:nth-child(6) {
        width: 14%;
    }

    .manager-opportunities-table th:nth-child(7),
    .manager-opportunities-table td:nth-child(7) {
        width: 13%;
    }

    .manager-opportunities-table th:nth-child(8),
    .manager-opportunities-table td:nth-child(8) {
        width: 20%;
    }

    .manager-opportunities-table thead th {
        padding: 15px 14px;
        color: #020617;
        background: #eef2f7;
        border-bottom: 1px solid #cbd5e1;
        font-size: 11px;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: 0.045em;
    }

    .manager-opportunities-table tbody td {
        padding: 18px 14px;
        vertical-align: top;
        color: #0f172a;
        background: #ffffff;
        border-bottom: 1px solid #e5e7eb;
        font-size: 13px;
        font-weight: 650;
    }

    .manager-opportunities-table tbody tr:hover td {
        background: #f8fafc;
    }

    .manager-opportunities-table tbody tr:nth-child(even) td {
        background: #fcfdff;
    }

    .manager-opportunities-table tbody tr:nth-child(even):hover td {
        background: #f8fafc;
    }

    .manager-opportunities-table .text-muted {
        color: #475569 !important;
        font-weight: 700;
    }

    .manager-opportunities-table .small {
        font-size: 11px;
    }

    .manager-opportunities-table .fw-bold,
    .manager-opportunities-table .fw-black {
        color: #020617 !important;
    }

    .opportunity-title {
        line-height: 1.35;
    }

    .manager-stage-cell,
    .manager-followup-cell,
    .manager-assign-cell,
    .manager-actions-cell {
        min-width: 0;
    }

    .manager-stage-cell .form-select,
    .manager-assign-cell .form-select,
    .manager-followup-cell .form-control {
        width: 100%;
        min-width: 0;
        min-height: 38px;
        font-size: 12px;
        font-weight: 800;
        border-color: #aebacc;
        padding-left: 10px;
        padding-right: 28px;
    }

    .followup-form {
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px;
    }

    .manager-followup-cell .btn {
        width: 100%;
        min-height: 36px;
        font-size: 12px;
        font-weight: 950;
        padding: 0 10px;
        border-radius: 9px;
    }

    .manager-actions-grid {
        width: 100%;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .manager-actions-grid .btn,
    .manager-actions-grid form,
    .manager-actions-grid form button {
        width: 100%;
    }

    .action-span-2 {
        grid-column: span 2;
    }

    .manager-opportunities-table .btn {
        border-radius: 10px;
        font-size: 12px;
        font-weight: 950;
        padding: 8px 10px;
        white-space: normal;
    }

    .btn-schedule {
        color: #ffffff;
        background: #ea580c;
        border-color: #ea580c;
        box-shadow: 0 8px 18px rgba(234, 88, 12, 0.22);
    }

    .btn-schedule:hover {
        color: #ffffff;
        background: #c2410c;
        border-color: #c2410c;
    }

    .manager-opportunities-table .btn-primary {
        background: #2563eb;
        border-color: #2563eb;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.20);
    }

    .manager-opportunities-table .btn-primary:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }

    .manager-opportunities-table .btn-outline-secondary {
        color: #0f172a;
        border-color: #cbd5e1;
        background: #ffffff;
    }

    .manager-opportunities-table .btn-outline-secondary:hover {
        color: #ffffff;
        background: #0f172a;
        border-color: #0f172a;
    }

    .manager-opportunities-table .btn-outline-success {
        color: #15803d;
        border-color: #86efac;
        background: #f0fdf4;
    }

    .manager-opportunities-table .btn-outline-success:hover {
        color: #ffffff;
        background: #15803d;
        border-color: #15803d;
    }

    .manager-opportunities-table .btn-outline-danger {
        color: #b91c1c;
        border-color: #fca5a5;
        background: #fef2f2;
    }

    .manager-opportunities-table .btn-outline-danger:hover {
        color: #ffffff;
        background: #b91c1c;
        border-color: #b91c1c;
    }

    .manager-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 7px 10px;
        font-size: 11px;
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

    .badge-soft-info {
        color: #0369a1;
        background: #f0f9ff;
        border-color: #7dd3fc;
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

    .badge-soft-orange {
        color: #c2410c;
        background: #fff7ed;
        border-color: #fdba74;
    }

    .badge-soft-danger {
        color: #b91c1c;
        background: #fef2f2;
        border-color: #fca5a5;
    }

    .badge-soft-purple {
        color: #7e22ce;
        background: #faf5ff;
        border-color: #d8b4fe;
    }

    .badge-soft-muted {
        color: #334155;
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .manager-notes-row td {
        background: #fff7ed !important;
        padding: 16px 20px !important;
        border-bottom: 1px solid #fed7aa !important;
    }

    .manager-notes-row .text-muted {
        color: #9a3412 !important;
        font-size: 11px;
        font-weight: 950;
        letter-spacing: 0.05em;
    }

    .manager-notes-row .text-dark {
        color: #431407 !important;
        font-size: 13px;
        font-weight: 750;
    }

    .sf-empty {
        padding: 64px 20px;
        text-align: center;
        background: #ffffff;
    }

    .sf-empty h3 {
        color: #020617;
        font-size: 20px;
        font-weight: 950;
        margin: 0;
    }

    .sf-empty p {
        color: #475569;
        font-size: 14px;
        font-weight: 750;
        margin: 8px 0 0;
    }

    .manager-pagination {
        padding: 18px 22px;
        border-top: 1px solid #e5eaf1;
        background: #ffffff;
    }

    .manager-pagination nav {
        display: flex;
        justify-content: flex-end;
    }

    .schedule-modal-content {
        border: 0;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 28px 80px rgba(2, 6, 23, 0.36);
    }

    .schedule-modal-header {
        color: #ffffff;
        background:
            radial-gradient(circle at top left, rgba(234, 88, 12, 0.45), transparent 34%),
            linear-gradient(135deg, #060b16, #0f172a);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding: 22px 24px;
    }

    .schedule-modal-header .modal-title {
        font-size: 22px;
        font-weight: 950;
        letter-spacing: -0.035em;
    }

    .schedule-modal-subtitle {
        color: #cbd5e1;
        font-size: 13px;
        font-weight: 700;
        margin-top: 4px;
    }

    .schedule-modal-body {
        padding: 24px;
        background: #ffffff;
    }

    .schedule-modal-footer {
        padding: 18px 24px;
        border-top: 1px solid #e5eaf1;
        background: #f8fafc;
    }

    .schedule-summary {
        border-radius: 16px;
        padding: 18px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .summary-label {
        display: block;
        color: #64748b;
        font-size: 11px;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 4px;
    }

    .summary-value {
        display: block;
        color: #020617;
        font-size: 13px;
        font-weight: 900;
    }

    @media (max-width: 992px) {
        .manager-opportunities-table th:nth-child(2),
        .manager-opportunities-table td:nth-child(2),
        .manager-opportunities-table th:nth-child(3),
        .manager-opportunities-table td:nth-child(3) {
            display: none;
        }

        .manager-opportunities-table th:nth-child(1),
        .manager-opportunities-table td:nth-child(1) {
            width: 18%;
        }

        .manager-opportunities-table th:nth-child(4),
        .manager-opportunities-table td:nth-child(4) {
            width: 18%;
        }

        .manager-opportunities-table th:nth-child(5),
        .manager-opportunities-table td:nth-child(5) {
            width: 10%;
        }

        .manager-opportunities-table th:nth-child(6),
        .manager-opportunities-table td:nth-child(6) {
            width: 18%;
        }

        .manager-opportunities-table th:nth-child(7),
        .manager-opportunities-table td:nth-child(7) {
            width: 16%;
        }

        .manager-opportunities-table th:nth-child(8),
        .manager-opportunities-table td:nth-child(8) {
            width: 20%;
        }
    }

    @media (max-width: 768px) {
        .sf-page-title {
            font-size: 30px !important;
        }

        .sf-panel-header,
        .sf-panel-body {
            padding: 18px;
        }

        .manager-opportunities-table thead th {
            font-size: 10px;
            padding: 12px 8px;
        }

        .manager-opportunities-table tbody td {
            font-size: 12px;
            padding: 14px 8px;
        }

        .manager-opportunities-table th:nth-child(5),
        .manager-opportunities-table td:nth-child(5),
        .manager-opportunities-table th:nth-child(7),
        .manager-opportunities-table td:nth-child(7) {
            display: none;
        }

        .manager-opportunities-table th:nth-child(1),
        .manager-opportunities-table td:nth-child(1) {
            width: 22%;
        }

        .manager-opportunities-table th:nth-child(4),
        .manager-opportunities-table td:nth-child(4) {
            width: 24%;
        }

        .manager-opportunities-table th:nth-child(6),
        .manager-opportunities-table td:nth-child(6) {
            width: 22%;
        }

        .manager-opportunities-table th:nth-child(8),
        .manager-opportunities-table td:nth-child(8) {
            width: 32%;
        }

        .manager-actions-grid {
            grid-template-columns: 1fr;
        }

        .action-span-2 {
            grid-column: span 1;
        }
    }
</style>
@endpush