@extends('layouts.manager')

@section('title', 'Manager Jobs')

@section('content')
@php
    use Illuminate\Support\Facades\Route;

    $jobTitle = function ($job) {
        return $job->job_code
            ?: 'Job #' . $job->id;
    };

    $jobDescription = function ($job) {
        return \Illuminate\Support\Str::limit(
            $job->description
                ?? $job->summary
                ?? $job->work_summary
                ?? 'Service job',
            70
        );
    };

    $customerName = function ($job) {
        return $job->client?->name
            ?? $job->booking?->client?->name
            ?? $job->booking?->customer_name
            ?? $job->booking?->name
            ?? 'Customer not linked';
    };

    $customerPhone = function ($job) {
        return $job->client?->phone
            ?? $job->client?->whatsapp
            ?? $job->booking?->client?->phone
            ?? $job->booking?->client?->whatsapp
            ?? $job->booking?->phone
            ?? $job->booking?->whatsapp_number
            ?? 'No phone';
    };

    $bookingDate = function ($booking) {
        if (! $booking) {
            return 'No date';
        }

        $date = $booking->booking_date
            ?? $booking->scheduled_date
            ?? $booking->date
            ?? null;

        if (! $date) {
            return 'No date';
        }

        try {
            return \Carbon\Carbon::parse($date)->format('d M Y');
        } catch (\Throwable $e) {
            return $date;
        }
    };

    $bookingSlot = function ($booking) {
        if (! $booking) {
            return null;
        }

        return $booking->slot
            ?? $booking->time_slot
            ?? null;
    };

    $statusClass = function ($status) {
        $status = strtolower((string) $status);

        return match ($status) {
            'pending' => 'badge-soft-warning',
            'in_progress' => 'badge-soft-primary',
            'completed' => 'badge-soft-success',
            default => 'badge-soft-muted',
        };
    };
@endphp

<div class="manager-jobs-page">

    {{-- Header --}}
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <div class="sf-kicker">
                Manager Job Queue
            </div>

            <h1 class="sf-page-title mt-2">
                Jobs
            </h1>

            <p class="sf-page-subtitle">
                Track jobs, assign team members, update progress, and complete work.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @if(Route::has('manager.jobs.completed'))
                <a href="{{ route('manager.jobs.completed') }}"
                   class="sf-action-button light">
                    Completed Jobs
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


    {{-- Stat Cards --}}
    <div class="row g-4 mb-4">

        <div class="col-12 col-sm-6 col-xl-4">
            <a href="{{ route('manager.jobs.index', ['status' => 'pending']) }}"
               class="job-stat-card warning {{ ($status ?? request('status')) === 'pending' ? 'active' : '' }}">
                <span class="job-stat-label">Pending</span>
                <span class="job-stat-value">{{ $counts['pending'] ?? 0 }}</span>
                <span class="job-stat-note">Waiting to start</span>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-xl-4">
            <a href="{{ route('manager.jobs.index', ['status' => 'in_progress']) }}"
               class="job-stat-card primary {{ ($status ?? request('status')) === 'in_progress' ? 'active' : '' }}">
                <span class="job-stat-label">In Progress</span>
                <span class="job-stat-value">{{ $counts['in_progress'] ?? 0 }}</span>
                <span class="job-stat-note">Currently being worked on</span>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-xl-4">
            <a href="{{ route('manager.jobs.index', ['status' => 'completed']) }}"
               class="job-stat-card success {{ ($status ?? request('status')) === 'completed' ? 'active' : '' }}">
                <span class="job-stat-label">Completed</span>
                <span class="job-stat-value">{{ $counts['completed'] ?? 0 }}</span>
                <span class="job-stat-note">Feedback should trigger</span>
            </a>
        </div>

    </div>


    {{-- Filters --}}
    <div class="sf-panel mb-4">
        <div class="sf-panel-header">
            <div>
                <h2 class="sf-panel-title">Filter Jobs</h2>
                <p class="sf-panel-subtitle">
                    Search by job code, customer, booking, status, or work summary.
                </p>
            </div>
        </div>

        <div class="sf-panel-body">
            <form method="GET" action="{{ route('manager.jobs.index') }}">
                <div class="row g-3 align-items-end">

                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-bold small text-muted">
                            Search
                        </label>
                        <input
                            type="text"
                            name="q"
                            value="{{ $q ?? request('q') }}"
                            placeholder="Search job code, customer, status, summary..."
                            class="form-control"
                        >
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label fw-bold small text-muted">
                            Status
                        </label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="pending" @selected(($status ?? request('status')) === 'pending')>Pending</option>
                            <option value="in_progress" @selected(($status ?? request('status')) === 'in_progress')>In Progress</option>
                            <option value="completed" @selected(($status ?? request('status')) === 'completed')>Completed</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="sf-action-button primary flex-fill">
                                Apply
                            </button>

                            <a href="{{ route('manager.jobs.index') }}"
                               class="sf-action-button light">
                                Reset
                            </a>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>


    {{-- Jobs Table --}}
    <div class="sf-panel overflow-hidden">
        <div class="sf-panel-header">
            <div>
                <h2 class="sf-panel-title">
                    Job List
                </h2>
                <p class="sf-panel-subtitle">
                    Jobs created from bookings and assigned service work.
                </p>
            </div>

            <span class="manager-count-pill">
                {{ method_exists($jobs, 'total') ? $jobs->total() : $jobs->count() }} job(s)
            </span>
        </div>

        @if($jobs->count())
            <div class="job-table-wrap">
                <table class="table table-hover manager-jobs-table mb-0">
                    <thead>
                        <tr>
                            <th>Job</th>
                            <th>Customer</th>
                            <th>Booking</th>
                            <th>Assigned To</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($jobs as $job)
                            @php
                                $statusValue = $job->status ?? 'pending';
                                $slotValue = $bookingSlot($job->booking);
                            @endphp

                            <tr>
                                <td>
                                    <div class="fw-black text-dark">
                                        {{ $jobTitle($job) }}
                                    </div>

                                    <div class="small text-muted mt-1">
                                        {{ $jobDescription($job) }}
                                    </div>
                                </td>

                                <td>
                                    <div class="fw-bold text-dark">
                                        {{ $customerName($job) }}
                                    </div>

                                    <div class="small text-muted mt-1">
                                        {{ $customerPhone($job) }}
                                    </div>
                                </td>

                                <td>
                                    @if($job->booking)
                                        <div class="fw-bold text-dark">
                                            Booking #{{ $job->booking->id }}
                                        </div>

                                        <div class="small text-muted mt-1">
                                            {{ $bookingDate($job->booking) }}

                                            @if($slotValue)
                                                · {{ ucfirst(str_replace('_', ' ', $slotValue)) }}
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">No booking linked</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="fw-bold text-dark">
                                        {{ $job->assignedUser?->name ?? 'Not assigned' }}
                                    </div>

                                    @if($job->assignedUser?->role)
                                        <div class="small text-muted mt-1">
                                            {{ ucfirst($job->assignedUser->role) }}
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    <span class="manager-badge {{ $statusClass($statusValue) }}">
                                        {{ ucfirst(str_replace('_', ' ', $statusValue)) }}
                                    </span>
                                </td>

                                <td class="text-end manager-actions-cell">
                                    <div class="job-actions">
                                        @if(Route::has('manager.jobs.show'))
                                            <a href="{{ route('manager.jobs.show', $job) }}"
                                               class="btn btn-sm btn-primary">
                                                Open
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="sf-empty">
                                        <h3>No jobs found</h3>
                                        <p>Jobs converted from bookings will appear here.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="sf-empty">
                <h3>No jobs found</h3>
                <p>Jobs converted from bookings will appear here.</p>
            </div>
        @endif

        @if(method_exists($jobs, 'links'))
            <div class="manager-pagination">
                {{ $jobs->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

@push('styles')
<style>
    .manager-jobs-page {
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
        color: #ffffff;
        background: #1d4ed8;
    }

    .sf-action-button.light {
        color: #0f172a;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
    }

    .sf-action-button.light:hover {
        color: #0f172a;
        background: #f8fafc;
        border-color: #94a3b8;
    }

    /*
    |--------------------------------------------------------------------------
    | Stat Cards
    |--------------------------------------------------------------------------
    */

    .job-stat-card {
        min-height: 132px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        border-radius: 20px;
        padding: 20px 22px;
        border: 1px solid #e5e7eb;
        background: #ffffff;
        color: #0f172a;
        text-decoration: none;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
        transition: all 0.15s ease;
    }

    .job-stat-card:hover {
        color: #0f172a;
        transform: translateY(-1px);
        box-shadow: 0 20px 48px rgba(15, 23, 42, 0.13);
    }

    .job-stat-card.active {
        border-color: rgba(234, 88, 12, 0.45);
        box-shadow: 0 18px 46px rgba(234, 88, 12, 0.14);
    }

    .job-stat-card.warning {
        background: linear-gradient(135deg, #fffbeb, #ffffff);
    }

    .job-stat-card.primary {
        background: linear-gradient(135deg, #eff6ff, #ffffff);
    }

    .job-stat-card.success {
        background: linear-gradient(135deg, #f0fdf4, #ffffff);
    }

    .job-stat-card.danger {
        background: linear-gradient(135deg, #fef2f2, #ffffff);
    }

    .job-stat-label {
        color: #64748b;
        font-size: 13px;
        font-weight: 900;
    }

    .job-stat-value {
        margin-top: 9px;
        color: #020617;
        font-size: 34px;
        line-height: 1;
        font-weight: 950;
        letter-spacing: -0.05em;
    }

    .job-stat-note {
        margin-top: 8px;
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
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

    /*
    |--------------------------------------------------------------------------
    | No horizontal scrollbar table
    |--------------------------------------------------------------------------
    */

    .job-table-wrap {
        width: 100%;
        overflow-x: hidden;
    }

    .manager-jobs-table {
        width: 100%;
        max-width: 100%;
        margin-bottom: 0;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
    }

    .manager-jobs-table th,
    .manager-jobs-table td {
        white-space: normal !important;
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .manager-jobs-table th:nth-child(1),
    .manager-jobs-table td:nth-child(1) {
        width: 24%;
    }

    .manager-jobs-table th:nth-child(2),
    .manager-jobs-table td:nth-child(2) {
        width: 24%;
    }

    .manager-jobs-table th:nth-child(3),
    .manager-jobs-table td:nth-child(3) {
        width: 18%;
    }

    .manager-jobs-table th:nth-child(4),
    .manager-jobs-table td:nth-child(4) {
        width: 16%;
    }

    .manager-jobs-table th:nth-child(5),
    .manager-jobs-table td:nth-child(5) {
        width: 10%;
    }

    .manager-jobs-table th:nth-child(6),
    .manager-jobs-table td:nth-child(6) {
        width: 8%;
    }

    .manager-jobs-table thead th {
        padding: 15px 14px;
        color: #020617;
        background: #eef2f7;
        border-bottom: 1px solid #cbd5e1;
        font-size: 11px;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: 0.045em;
    }

    .manager-jobs-table tbody td {
        padding: 18px 14px;
        vertical-align: top;
        color: #0f172a;
        background: #ffffff;
        border-bottom: 1px solid #e5e7eb;
        font-size: 13px;
        font-weight: 650;
    }

    .manager-jobs-table tbody tr:hover td {
        background: #f8fafc;
    }

    .manager-jobs-table tbody tr:nth-child(even) td {
        background: #fcfdff;
    }

    .manager-jobs-table tbody tr:nth-child(even):hover td {
        background: #f8fafc;
    }

    .manager-jobs-table .text-muted {
        color: #475569 !important;
        font-weight: 700;
    }

    .manager-jobs-table .small {
        font-size: 11px;
    }

    .manager-jobs-table .fw-bold,
    .manager-jobs-table .fw-black {
        color: #020617 !important;
    }

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    .manager-actions-cell {
        min-width: 0;
    }

    .job-actions {
        width: 100%;
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px;
    }

    .job-actions .btn {
        width: 100%;
    }

    .manager-jobs-table .btn {
        border-radius: 10px;
        font-size: 12px;
        font-weight: 950;
        padding: 8px 10px;
        white-space: normal;
    }

    .manager-jobs-table .btn-primary {
        color: #ffffff;
        background: #2563eb;
        border-color: #2563eb;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.20);
    }

    .manager-jobs-table .btn-primary:hover {
        color: #ffffff;
        background: #1d4ed8;
        border-color: #1d4ed8;
    }

    /*
    |--------------------------------------------------------------------------
    | Badges
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Empty / Pagination
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Mobile
    |--------------------------------------------------------------------------
    */

    @media (max-width: 992px) {
        .manager-jobs-table th:nth-child(3),
        .manager-jobs-table td:nth-child(3),
        .manager-jobs-table th:nth-child(4),
        .manager-jobs-table td:nth-child(4) {
            display: none;
        }

        .manager-jobs-table th:nth-child(1),
        .manager-jobs-table td:nth-child(1) {
            width: 34%;
        }

        .manager-jobs-table th:nth-child(2),
        .manager-jobs-table td:nth-child(2) {
            width: 34%;
        }

        .manager-jobs-table th:nth-child(5),
        .manager-jobs-table td:nth-child(5) {
            width: 18%;
        }

        .manager-jobs-table th:nth-child(6),
        .manager-jobs-table td:nth-child(6) {
            width: 14%;
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

        .job-stat-card {
            min-height: 112px;
        }

        .manager-jobs-table thead th {
            font-size: 10px;
            padding: 12px 8px;
        }

        .manager-jobs-table tbody td {
            font-size: 12px;
            padding: 14px 8px;
        }

        .manager-jobs-table th:nth-child(5),
        .manager-jobs-table td:nth-child(5) {
            display: none;
        }

        .manager-jobs-table th:nth-child(1),
        .manager-jobs-table td:nth-child(1) {
            width: 42%;
        }

        .manager-jobs-table th:nth-child(2),
        .manager-jobs-table td:nth-child(2) {
            width: 42%;
        }

        .manager-jobs-table th:nth-child(6),
        .manager-jobs-table td:nth-child(6) {
            width: 16%;
        }
    }
</style>
@endpush
