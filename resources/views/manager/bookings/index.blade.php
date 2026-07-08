@extends('layouts.manager')

@section('title', 'Manager Bookings')

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

    $bookingDate = function ($booking) {
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

    $bookingTime = function ($booking) {
        return $booking->booking_time
            ?? $booking->scheduled_time
            ?? $booking->time
            ?? null;
    };

    $bookingSlot = function ($booking) {
        return $booking->slot
            ?? $booking->time_slot
            ?? null;
    };

    $statusClass = function ($status) {
        $status = strtolower((string) $status);

        return match ($status) {
            'pending' => 'badge-soft-warning',
            'scheduled', 'confirmed' => 'badge-soft-primary',
            'reschedule_required' => 'badge-soft-danger',
            'converted_to_job' => 'badge-soft-success',
            'lost', 'rejected', 'cancelled', 'canceled' => 'badge-soft-danger',
            default => 'badge-soft-muted',
        };
    };

    $statusLabel = function ($status) {
        return match (strtolower((string) $status)) {
            'pending' => 'Manager Confirmation',
            'scheduled', 'confirmed' => 'Booking Confirmed',
            'reschedule_required' => 'Rescheduling Required',
            'converted_to_job' => 'Converted To Job',
            'lost' => 'Lost',
            default => ucfirst(str_replace('_', ' ', (string) $status)),
        };
    };
@endphp

<div class="manager-bookings-page">

    {{-- Header --}}
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <div class="sf-kicker">
                Manager Booking Queue
            </div>

            <h1 class="sf-page-title mt-2">
                Bookings
            </h1>

            <p class="sf-page-subtitle">
                Confirm customer bookings, reject invalid requests, or convert confirmed bookings into jobs.
            </p>
        </div>

        @if(Route::has('manager.dashboard'))
            <a href="{{ route('manager.dashboard') }}"
               class="sf-action-button light">
                Back to Dashboard
            </a>
        @endif
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

        <div class="col-12 col-sm-6 col-xl">
            <a href="{{ route('manager.bookings.index', ['status' => 'pending']) }}"
               class="booking-stat-card warning {{ ($status ?? request('status')) === 'pending' ? 'active' : '' }}">
                <span class="booking-stat-label">Manager Confirmation</span>
                <span class="booking-stat-value">{{ $counts['pending'] ?? 0 }}</span>
                <span class="booking-stat-note">Needs manager action</span>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <a href="{{ route('manager.bookings.index', ['status' => 'scheduled']) }}"
               class="booking-stat-card primary {{ ($status ?? request('status')) === 'scheduled' ? 'active' : '' }}">
                <span class="booking-stat-label">Booking Confirmed</span>
                <span class="booking-stat-value">{{ $counts['scheduled'] ?? 0 }}</span>
                <span class="booking-stat-note">Confirmed bookings</span>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <a href="{{ route('manager.bookings.index', ['status' => 'reschedule_required']) }}"
               class="booking-stat-card danger {{ ($status ?? request('status')) === 'reschedule_required' ? 'active' : '' }}">
                <span class="booking-stat-label">Rescheduling Required</span>
                <span class="booking-stat-value">{{ $counts['reschedule_required'] ?? 0 }}</span>
                <span class="booking-stat-note">Needs new slot</span>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <a href="{{ route('manager.bookings.index', ['status' => 'converted_to_job']) }}"
               class="booking-stat-card success {{ ($status ?? request('status')) === 'converted_to_job' ? 'active' : '' }}">
                <span class="booking-stat-label">Converted</span>
                <span class="booking-stat-value">{{ $counts['converted_to_job'] ?? 0 }}</span>
                <span class="booking-stat-note">Converted to job</span>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <a href="{{ route('manager.bookings.index', ['status' => 'lost']) }}"
               class="booking-stat-card danger {{ ($status ?? request('status')) === 'lost' ? 'active' : '' }}">
                <span class="booking-stat-label">Lost</span>
                <span class="booking-stat-value">{{ $counts['lost'] ?? 0 }}</span>
                <span class="booking-stat-note">Rejected / cancelled</span>
            </a>
        </div>

    </div>


    {{-- Filters --}}
    <div class="sf-panel mb-4">
        <div class="sf-panel-header">
            <div>
                <h2 class="sf-panel-title">Filter Bookings</h2>
                <p class="sf-panel-subtitle">
                    Search by customer, vehicle, status, service type, or booking details.
                </p>
            </div>
        </div>

        <div class="sf-panel-body">
            <form method="GET" action="{{ route('manager.bookings.index') }}">
                <div class="row g-3 align-items-end">

                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-bold small text-muted">
                            Search
                        </label>
                        <input
                            type="text"
                            name="q"
                            value="{{ $q ?? request('q') }}"
                            placeholder="Search customer, vehicle, service, status..."
                            class="form-control"
                        >
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label fw-bold small text-muted">
                            Status
                        </label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="pending" @selected(($status ?? request('status')) === 'pending')>Manager Confirmation</option>
                            <option value="scheduled" @selected(($status ?? request('status')) === 'scheduled')>Booking Confirmed</option>
                            <option value="reschedule_required" @selected(($status ?? request('status')) === 'reschedule_required')>Rescheduling Required</option>
                            <option value="converted_to_job" @selected(($status ?? request('status')) === 'converted_to_job')>Converted To Job</option>
                            <option value="lost" @selected(($status ?? request('status')) === 'lost')>Lost</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="sf-action-button primary flex-fill">
                                Apply
                            </button>

                            <a href="{{ route('manager.bookings.index') }}"
                               class="sf-action-button light">
                                Reset
                            </a>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>


    {{-- Bookings Table --}}
    <div class="sf-panel overflow-hidden">
        <div class="sf-panel-header">
            <div>
                <h2 class="sf-panel-title">
                    Booking List
                </h2>
                <p class="sf-panel-subtitle">
                    Scheduled bookings from opportunities and customer booking requests.
                </p>
            </div>

            <span class="manager-count-pill">
                {{ method_exists($bookings, 'total') ? $bookings->total() : $bookings->count() }} booking(s)
            </span>
        </div>

        @if($bookings->count())
            <div class="booking-table-wrap">
                <table class="table table-hover manager-bookings-table mb-0">
                    <thead>
                        <tr>
                            <th>Booking</th>
                            <th>Customer</th>
                            <th>Vehicle</th>
                            <th>Date / Slot</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($bookings as $booking)
                            @php
                                $statusValue = $booking->status ?? 'pending';
                                $slotValue = $bookingSlot($booking);
                                $timeValue = $bookingTime($booking);
                            @endphp

                            <tr>
                                <td>
                                    <div class="fw-black text-dark">
                                        #{{ $booking->id }}
                                    </div>

                                    <div class="small text-muted mt-1">
                                        {{ $booking->service_type ?: 'Service booking' }}
                                    </div>

                                    @if(!empty($booking->opportunity_id))
                                        <div class="small text-orange mt-1">
                                            From Opportunity #{{ $booking->opportunity_id }}
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    <div class="fw-bold text-dark">
                                        {{ $bookingName($booking) }}
                                    </div>

                                    <div class="small text-muted mt-1">
                                        {{ $bookingPhone($booking) }}
                                    </div>
                                </td>

                                <td>
                                    <div class="fw-bold text-dark">
                                        {{ $bookingVehicle($booking) }}
                                    </div>

                                    @if($booking->vehicleData?->plate_number || !empty($booking->plate_number))
                                        <div class="small text-muted mt-1">
                                            Plate: {{ $booking->vehicleData?->plate_number ?? $booking->plate_number }}
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    <div class="fw-bold text-dark">
                                        {{ $bookingDate($booking) }}
                                    </div>

                                    <div class="small text-muted mt-1">
                                        @if($timeValue)
                                            {{ $timeValue }}
                                        @endif

                                        @if($slotValue)
                                            @if($timeValue) &middot; @endif
                                            {{ ucfirst(str_replace('_', ' ', $slotValue)) }}
                                        @endif

                                        @if(! $timeValue && ! $slotValue)
                                            No slot
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <span class="manager-badge {{ $statusClass($statusValue) }}">
                                        {{ $statusLabel($statusValue) }}
                                    </span>
                                </td>

                                <td class="text-end manager-actions-cell">
                                    <div class="booking-actions">
                                        @if(Route::has('manager.bookings.show'))
                                            <a href="{{ route('manager.bookings.show', $booking) }}"
                                               class="btn btn-sm btn-primary">
                                                Open
                                            </a>
                                        @endif

                                        @if(in_array($statusValue, ['pending'], true) && Route::has('manager.bookings.confirm'))
                                            <form method="POST" action="{{ route('manager.bookings.confirm', $booking) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                    Confirm
                                                </button>
                                            </form>
                                        @endif

                                        @if(in_array($statusValue, ['pending', 'scheduled', 'confirmed'], true) && Route::has('manager.bookings.reject'))
                                            <form method="POST" action="{{ route('manager.bookings.reject', $booking) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    Reject
                                                </button>
                                            </form>
                                        @endif

                                        @if(in_array($statusValue, ['scheduled', 'confirmed'], true) && Route::has('manager.bookings.convert-to-job'))
                                            <form method="POST" action="{{ route('manager.bookings.convert-to-job', $booking) }}" class="action-span-2">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-schedule">
                                                    Convert to Job
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="sf-empty">
                                        <h3>No bookings found</h3>
                                        <p>Bookings created from opportunities will appear here.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="sf-empty">
                <h3>No bookings found</h3>
                <p>Bookings created from opportunities will appear here.</p>
            </div>
        @endif

        @if(method_exists($bookings, 'links'))
            <div class="manager-pagination">
                {{ $bookings->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

@push('styles')
<style>
    .manager-bookings-page {
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

    .text-orange {
        color: #ea580c !important;
        font-weight: 850;
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

    .booking-stat-card {
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

    .booking-stat-card:hover {
        color: #0f172a;
        transform: translateY(-1px);
        box-shadow: 0 20px 48px rgba(15, 23, 42, 0.13);
    }

    .booking-stat-card.active {
        border-color: rgba(234, 88, 12, 0.45);
        box-shadow: 0 18px 46px rgba(234, 88, 12, 0.14);
    }

    .booking-stat-card.warning {
        background: linear-gradient(135deg, #fffbeb, #ffffff);
    }

    .booking-stat-card.primary {
        background: linear-gradient(135deg, #eff6ff, #ffffff);
    }

    .booking-stat-card.success {
        background: linear-gradient(135deg, #f0fdf4, #ffffff);
    }

    .booking-stat-card.danger {
        background: linear-gradient(135deg, #fef2f2, #ffffff);
    }

    .booking-stat-label {
        color: #64748b;
        font-size: 13px;
        font-weight: 900;
    }

    .booking-stat-value {
        margin-top: 9px;
        color: #020617;
        font-size: 34px;
        line-height: 1;
        font-weight: 950;
        letter-spacing: -0.05em;
    }

    .booking-stat-note {
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

    .booking-table-wrap {
        width: 100%;
        overflow-x: hidden;
    }

    .manager-bookings-table {
        width: 100%;
        max-width: 100%;
        margin-bottom: 0;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
    }

    .manager-bookings-table th,
    .manager-bookings-table td {
        white-space: normal !important;
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .manager-bookings-table th:nth-child(1),
    .manager-bookings-table td:nth-child(1) {
        width: 15%;
    }

    .manager-bookings-table th:nth-child(2),
    .manager-bookings-table td:nth-child(2) {
        width: 22%;
    }

    .manager-bookings-table th:nth-child(3),
    .manager-bookings-table td:nth-child(3) {
        width: 20%;
    }

    .manager-bookings-table th:nth-child(4),
    .manager-bookings-table td:nth-child(4) {
        width: 16%;
    }

    .manager-bookings-table th:nth-child(5),
    .manager-bookings-table td:nth-child(5) {
        width: 12%;
    }

    .manager-bookings-table th:nth-child(6),
    .manager-bookings-table td:nth-child(6) {
        width: 15%;
    }

    .manager-bookings-table thead th {
        padding: 15px 14px;
        color: #020617;
        background: #eef2f7;
        border-bottom: 1px solid #cbd5e1;
        font-size: 11px;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: 0.045em;
    }

    .manager-bookings-table tbody td {
        padding: 18px 14px;
        vertical-align: top;
        color: #0f172a;
        background: #ffffff;
        border-bottom: 1px solid #e5e7eb;
        font-size: 13px;
        font-weight: 650;
    }

    .manager-bookings-table tbody tr:hover td {
        background: #f8fafc;
    }

    .manager-bookings-table tbody tr:nth-child(even) td {
        background: #fcfdff;
    }

    .manager-bookings-table tbody tr:nth-child(even):hover td {
        background: #f8fafc;
    }

    .manager-bookings-table .text-muted {
        color: #475569 !important;
        font-weight: 700;
    }

    .manager-bookings-table .small {
        font-size: 11px;
    }

    .manager-bookings-table .fw-bold,
    .manager-bookings-table .fw-black {
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

    .booking-actions {
        width: 100%;
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px;
    }

    .booking-actions .btn,
    .booking-actions form,
    .booking-actions form button {
        width: 100%;
    }

    .action-span-2 {
        grid-column: span 1;
    }

    .manager-bookings-table .btn {
        border-radius: 10px;
        font-size: 12px;
        font-weight: 950;
        padding: 8px 10px;
        white-space: normal;
    }

    .manager-bookings-table .btn-primary {
        color: #ffffff;
        background: #2563eb;
        border-color: #2563eb;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.20);
    }

    .manager-bookings-table .btn-primary:hover {
        color: #ffffff;
        background: #1d4ed8;
        border-color: #1d4ed8;
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

    .manager-bookings-table .btn-outline-success {
        color: #15803d;
        border-color: #86efac;
        background: #f0fdf4;
    }

    .manager-bookings-table .btn-outline-success:hover {
        color: #ffffff;
        background: #15803d;
        border-color: #15803d;
    }

    .manager-bookings-table .btn-outline-danger {
        color: #b91c1c;
        border-color: #fca5a5;
        background: #fef2f2;
    }

    .manager-bookings-table .btn-outline-danger:hover {
        color: #ffffff;
        background: #b91c1c;
        border-color: #b91c1c;
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
        .manager-bookings-table th:nth-child(3),
        .manager-bookings-table td:nth-child(3) {
            display: none;
        }

        .manager-bookings-table th:nth-child(1),
        .manager-bookings-table td:nth-child(1) {
            width: 18%;
        }

        .manager-bookings-table th:nth-child(2),
        .manager-bookings-table td:nth-child(2) {
            width: 26%;
        }

        .manager-bookings-table th:nth-child(4),
        .manager-bookings-table td:nth-child(4) {
            width: 20%;
        }

        .manager-bookings-table th:nth-child(5),
        .manager-bookings-table td:nth-child(5) {
            width: 16%;
        }

        .manager-bookings-table th:nth-child(6),
        .manager-bookings-table td:nth-child(6) {
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

        .booking-stat-card {
            min-height: 112px;
        }

        .manager-bookings-table thead th {
            font-size: 10px;
            padding: 12px 8px;
        }

        .manager-bookings-table tbody td {
            font-size: 12px;
            padding: 14px 8px;
        }

        .manager-bookings-table th:nth-child(5),
        .manager-bookings-table td:nth-child(5) {
            display: none;
        }

        .manager-bookings-table th:nth-child(1),
        .manager-bookings-table td:nth-child(1) {
            width: 22%;
        }

        .manager-bookings-table th:nth-child(2),
        .manager-bookings-table td:nth-child(2) {
            width: 30%;
        }

        .manager-bookings-table th:nth-child(4),
        .manager-bookings-table td:nth-child(4) {
            width: 24%;
        }

        .manager-bookings-table th:nth-child(6),
        .manager-bookings-table td:nth-child(6) {
            width: 24%;
        }
    }
</style>
@endpush
