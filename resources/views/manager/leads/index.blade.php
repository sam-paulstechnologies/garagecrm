@extends('layouts.manager')

@section('title', 'Manager Leads')

@section('content')
@php
    use Illuminate\Support\Facades\Route;

    $leadStatuses = $leadStatuses ?? [
        'new',
        'attempting_contact',
        'qualified',
        'converted',
        'lost',
    ];

    $statusLabels = $statusLabels ?? [
        'new' => 'New',
        'attempting_contact' => 'Attempting Contact',
        'qualified' => 'Qualified',
        'converted' => 'Converted',
        'lost' => 'Lost',
    ];

    $normalizeStatus = function ($status) {
        $status = strtolower(trim((string) $status));
        $status = str_replace(['-', ' '], '_', $status);

        return match ($status) {
            'new' => 'new',
            'attempting_contact', 'attempting', 'contacting', 'contacted', 'assigned', 'on_hold', 'contact_on_hold' => 'attempting_contact',
            'qualified' => 'qualified',
            'converted', 'converted_to_opportunity' => 'converted',
            'lost', 'disqualified', 'closed_lost', 'closed' => 'lost',
            default => $status,
        };
    };

    $statusLabel = function ($status) use ($statusLabels, $normalizeStatus) {
        $normalized = $normalizeStatus($status);

        return $statusLabels[$normalized] ?? ucwords(str_replace('_', ' ', $normalized ?: 'new'));
    };

    $leadName = function ($lead) {
        return $lead->name
            ?? $lead->full_name
            ?? $lead->customer_name
            ?? $lead->client_name
            ?? 'Lead #' . $lead->id;
    };

    $leadPhone = function ($lead) {
        return $lead->phone
            ?? $lead->mobile
            ?? $lead->phone_number
            ?? $lead->whatsapp_number
            ?? '-';
    };

    $leadVehicle = function ($lead) {
        $make = $lead->vehicle_make ?? $lead->make ?? null;
        $model = $lead->vehicle_model ?? $lead->model ?? null;

        return trim(($make ?? '') . ' ' . ($model ?? '')) ?: '-';
    };

    $assignedValue = function ($lead) {
        return $lead->assigned_to
            ?? $lead->assigned_to_id
            ?? $lead->assigned_user_id
            ?? $lead->manager_id
            ?? $lead->user_id
            ?? null;
    };

    $statusClass = function ($status) use ($normalizeStatus) {
        $status = $normalizeStatus($status);

        return match ($status) {
            'new' => 'badge-soft-primary',
            'attempting_contact' => 'badge-soft-warning',
            'qualified' => 'badge-soft-success',
            'converted' => 'badge-soft-info',
            'lost' => 'badge-soft-danger',
            default => 'badge-soft-muted',
        };
    };
@endphp

<div class="manager-leads-page">

    {{-- Header --}}
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <div class="sf-kicker">
                Manager Action Queue
            </div>

            <h1 class="sf-page-title mt-2">
                Leads
            </h1>

            <p class="sf-page-subtitle">
                Manager action queue for open leads, follow-ups, assignment, and qualification.
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
                <h2 class="sf-panel-title">Filter Leads</h2>
                <p class="sf-panel-subtitle">Search by lead, contact, vehicle, source, notes, or status.</p>
            </div>
        </div>

        <div class="sf-panel-body">
            <form method="GET" action="{{ route('manager.leads.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg-4">
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
                            Status
                        </label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            @foreach($leadStatuses as $item)
                                @php($itemValue = $normalizeStatus($item))
                                <option value="{{ $itemValue }}" @selected($normalizeStatus($status ?? request('status')) === $itemValue)>
                                    {{ $statusLabels[$itemValue] ?? $statusLabel($itemValue) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label fw-bold small text-muted">
                            Source
                        </label>
                        <select name="source" class="form-select">
                            <option value="">All Sources</option>
                            @foreach(($sources ?? collect()) as $item)
                                <option value="{{ $item }}" @selected(($source ?? request('source')) === $item)>
                                    {{ ucfirst($item) }}
                                </option>
                            @endforeach
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

    {{-- Leads Table --}}
    <div class="sf-panel overflow-hidden">
        <div class="sf-panel-header">
            <div>
                <h2 class="sf-panel-title">
                    Open Leads
                </h2>
                <p class="sf-panel-subtitle">
                    Showing manager-actionable leads only.
                </p>
            </div>

            <span class="manager-count-pill">
                {{ method_exists($leads, 'total') ? $leads->total() : $leads->count() }} lead(s)
            </span>
        </div>

        @if($leads->count())
            <div class="table-responsive">
                <table class="table table-hover manager-leads-table mb-0">
                    <thead>
                        <tr>
                            <th>Lead</th>
                            <th>Contact</th>
                            <th>Vehicle</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Follow-up</th>
                            <th>Assign</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($leads as $lead)
                            <tr>
                                <td>
                                    <div class="fw-black text-dark">
                                        {{ $leadName($lead) }}
                                    </div>

                                    <div class="small text-muted mt-1">
                                        #{{ $lead->id }}
                                        @if(!empty($lead->email))
                                            · {{ $lead->email }}
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <div class="fw-bold text-dark">
                                        {{ $leadPhone($lead) }}
                                    </div>

                                    @if(!empty($lead->preferred_channel))
                                        <div class="small text-muted mt-1">
                                            Preferred: {{ $lead->preferred_channel }}
                                        </div>
                                    @endif
                                </td>

                                <td class="text-muted">
                                    {{ $leadVehicle($lead) }}
                                </td>

                                <td>
                                    <span class="manager-badge badge-soft-muted">
                                        {{ $lead->source ?? '-' }}
                                    </span>
                                </td>

                                <td class="manager-status-cell">
                                    @if(Route::has('manager.leads.status'))
                                        <form method="POST" action="{{ route('manager.leads.status', $lead) }}">
                                            @csrf
                                            @method('PATCH')

                                            <select
                                                name="status"
                                                class="form-select form-select-sm"
                                                onchange="this.form.submit()"
                                            >
                                                @foreach($leadStatuses as $item)
                                                    @php($itemValue = $normalizeStatus($item))
                                                    <option value="{{ $itemValue }}" @selected($normalizeStatus($lead->status ?? '') === $itemValue)>
                                                        {{ $statusLabels[$itemValue] ?? $statusLabel($itemValue) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @else
                                        <span class="manager-badge {{ $statusClass($lead->status ?? 'new') }}">
                                            {{ $statusLabel($lead->status ?? 'new') }}
                                        </span>
                                    @endif
                                </td>

                                <td class="manager-followup-cell">
                                    @if(Route::has('manager.leads.follow-up'))
                                        <form method="POST"
                                              action="{{ route('manager.leads.follow-up', $lead) }}"
                                              class="d-flex align-items-center gap-2">
                                            @csrf
                                            @method('PATCH')

                                            <input
                                                type="date"
                                                name="follow_up_date"
                                                value="{{ !empty($lead->follow_up_date) ? \Carbon\Carbon::parse($lead->follow_up_date)->format('Y-m-d') : '' }}"
                                                class="form-control form-control-sm"
                                            >

                                            <input type="hidden" name="follow_up_required" value="1">

                                            <button class="btn btn-sm btn-outline-primary">
                                                Save
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted">
                                            {{ !empty($lead->follow_up_date) ? \Carbon\Carbon::parse($lead->follow_up_date)->format('d M Y') : '-' }}
                                        </span>
                                    @endif
                                </td>

                                <td class="manager-assign-cell">
                                    @if(Route::has('manager.leads.assign') && ($managers ?? collect())->count())
                                        <form method="POST" action="{{ route('manager.leads.assign', $lead) }}">
                                            @csrf
                                            @method('PATCH')

                                            <select
                                                name="assigned_to"
                                                class="form-select form-select-sm"
                                                onchange="this.form.submit()"
                                            >
                                                <option value="">Select</option>
                                                @foreach($managers as $manager)
                                                    <option value="{{ $manager->id }}" @selected((string) $assignedValue($lead) === (string) $manager->id)>
                                                        {{ $manager->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    <div class="d-inline-flex align-items-center gap-2">
                                        @if(Route::has('manager.leads.show'))
                                            <a href="{{ route('manager.leads.show', $lead) }}"
                                               class="btn btn-sm btn-outline-secondary">
                                                View
                                            </a>
                                        @endif

                                        @if(Route::has('manager.conversation'))
                                            <a href="{{ route('manager.conversation', $lead) }}"
                                               class="btn btn-sm btn-primary">
                                                Conversation
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            @if(!empty($lead->notes) || !empty($lead->manager_notes) || !empty($lead->internal_notes))
                                <tr class="manager-notes-row">
                                    <td colspan="8">
                                        <div class="small text-muted fw-bold text-uppercase">
                                            Latest Notes
                                        </div>
                                        <div class="mt-1 text-dark">
                                            {{ \Illuminate\Support\Str::limit($lead->manager_notes ?? $lead->internal_notes ?? $lead->notes, 220) }}
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
                <h3>No open leads found</h3>
                <p>Leads needing manager action will appear here.</p>
            </div>
        @endif

        @if(method_exists($leads, 'links'))
            <div class="manager-pagination">
                {{ $leads->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

@push('styles')
<style>
    .manager-leads-page {
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

    .manager-leads-table {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }

    .manager-leads-table thead th {
        padding: 15px 16px;
        color: #020617;
        background: #eef2f7;
        border-bottom: 1px solid #cbd5e1;
        font-size: 12px;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: 0.045em;
        white-space: nowrap;
    }

    .manager-leads-table tbody td {
        padding: 18px 16px;
        vertical-align: top;
        color: #0f172a;
        background: #ffffff;
        border-bottom: 1px solid #e5e7eb;
        font-size: 14px;
        font-weight: 650;
    }

    .manager-leads-table tbody tr:hover td {
        background: #f8fafc;
    }

    .manager-leads-table tbody tr:nth-child(even) td {
        background: #fcfdff;
    }

    .manager-leads-table tbody tr:nth-child(even):hover td {
        background: #f8fafc;
    }

    .manager-leads-table .text-muted {
        color: #475569 !important;
        font-weight: 700;
    }

    .manager-leads-table .small {
        font-size: 12px;
    }

    .manager-leads-table .fw-bold,
    .manager-leads-table .fw-black {
        color: #020617 !important;
    }

    .manager-status-cell {
        min-width: 190px;
    }

    .manager-followup-cell {
        min-width: 245px;
    }

    .manager-assign-cell {
        min-width: 210px;
    }

    .manager-status-cell .form-select,
    .manager-assign-cell .form-select,
    .manager-followup-cell .form-control {
        min-height: 38px;
        font-size: 13px;
        font-weight: 800;
        border-color: #aebacc;
    }

    .manager-followup-cell .btn {
        min-height: 38px;
        font-size: 12px;
        font-weight: 950;
        padding: 0 12px;
        border-radius: 9px;
    }

    .manager-leads-table .btn {
        border-radius: 10px;
        font-size: 12px;
        font-weight: 950;
        padding: 8px 12px;
    }

    .manager-leads-table .btn-primary {
        background: #2563eb;
        border-color: #2563eb;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.20);
    }

    .manager-leads-table .btn-primary:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }

    .manager-leads-table .btn-outline-secondary {
        color: #0f172a;
        border-color: #cbd5e1;
        background: #ffffff;
    }

    .manager-leads-table .btn-outline-secondary:hover {
        color: #ffffff;
        background: #0f172a;
        border-color: #0f172a;
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

    @media (max-width: 768px) {
        .sf-page-title {
            font-size: 30px !important;
        }

        .manager-followup-cell,
        .manager-assign-cell,
        .manager-status-cell {
            min-width: 190px;
        }

        .sf-panel-header,
        .sf-panel-body {
            padding: 18px;
        }
    }
</style>
@endpush