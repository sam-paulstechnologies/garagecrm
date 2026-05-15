@extends('layouts.manager')

@section('title', 'Manager Invoices')

@section('content')
@php
    use Illuminate\Support\Facades\Route;

    $invoiceNumber = function ($invoice) {
        return $invoice->invoice_number
            ?? $invoice->reference_number
            ?? 'Invoice #' . $invoice->id;
    };

    $invoiceAmount = function ($invoice) {
        return $invoice->total_amount
            ?? $invoice->grand_total
            ?? $invoice->amount
            ?? 0;
    };

    $invoiceStatus = function ($invoice) {
        return $invoice->payment_status
            ?? $invoice->status
            ?? 'issued';
    };

    $invoiceDate = function ($invoice) {
        $date = $invoice->issued_at
            ?? $invoice->invoice_date
            ?? $invoice->created_at
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

    $statusClass = function ($status) {
        $status = strtolower((string) $status);

        return match ($status) {
            'paid' => 'badge-soft-success',
            'unpaid', 'issued', 'open' => 'badge-soft-warning',
            'cancelled', 'canceled', 'void' => 'badge-soft-danger',
            default => 'badge-soft-muted',
        };
    };

    $clientName = function ($invoice) use ($clients) {
        if (! empty($invoice->client_id) && isset($clients[$invoice->client_id])) {
            return $clients[$invoice->client_id]->name ?? 'Client #' . $invoice->client_id;
        }

        return 'Client not linked';
    };

    $jobLabel = function ($invoice) use ($jobs) {
        if (! empty($invoice->job_id) && isset($jobs[$invoice->job_id])) {
            return $jobs[$invoice->job_id]->job_code ?? 'Job #' . $invoice->job_id;
        }

        if (! empty($invoice->job_id)) {
            return 'Job #' . $invoice->job_id;
        }

        return 'No job linked';
    };

    $bookingLabel = function ($invoice) use ($bookings) {
        if (! empty($invoice->booking_id) && isset($bookings[$invoice->booking_id])) {
            return 'Booking #' . $invoice->booking_id;
        }

        if (! empty($invoice->booking_id)) {
            return 'Booking #' . $invoice->booking_id;
        }

        return 'No booking linked';
    };
@endphp

<div class="manager-invoices-page">

    {{-- Header --}}
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <div class="sf-kicker">
                Manager Invoice Desk
            </div>

            <h1 class="sf-page-title mt-2">
                Invoices
            </h1>

            <p class="sf-page-subtitle">
                View invoices created from completed jobs and track payment status.
            </p>
        </div>

        @if(Route::has('manager.dashboard'))
            <a href="{{ route('manager.dashboard') }}"
               class="sf-action-button primary">
                Dashboard
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

        <div class="col-12 col-sm-6 col-xl-3">
            <a href="{{ route('manager.invoices.index') }}"
               class="invoice-stat-card primary">
                <span class="invoice-stat-label">Total Invoices</span>
                <span class="invoice-stat-value">{{ $counts['total'] ?? 0 }}</span>
                <span class="invoice-stat-note">All generated invoices</span>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <a href="{{ route('manager.invoices.index', ['payment_status' => 'unpaid']) }}"
               class="invoice-stat-card warning {{ ($paymentStatus ?? request('payment_status')) === 'unpaid' ? 'active' : '' }}">
                <span class="invoice-stat-label">Unpaid</span>
                <span class="invoice-stat-value">{{ $counts['unpaid'] ?? 0 }}</span>
                <span class="invoice-stat-note">Payment pending</span>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <a href="{{ route('manager.invoices.index', ['payment_status' => 'paid']) }}"
               class="invoice-stat-card success {{ ($paymentStatus ?? request('payment_status')) === 'paid' ? 'active' : '' }}">
                <span class="invoice-stat-label">Paid</span>
                <span class="invoice-stat-value">{{ $counts['paid'] ?? 0 }}</span>
                <span class="invoice-stat-note">Payment received</span>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="invoice-stat-card dark">
                <span class="invoice-stat-label">Total Value</span>
                <span class="invoice-stat-value">{{ number_format((float) ($counts['total_amount'] ?? 0), 2) }}</span>
                <span class="invoice-stat-note">Invoice amount total</span>
            </div>
        </div>

    </div>

    {{-- Filters --}}
    <div class="sf-panel mb-4">
        <div class="sf-panel-header">
            <div>
                <h2 class="sf-panel-title">Filter Invoices</h2>
                <p class="sf-panel-subtitle">
                    Search by invoice number, job, booking, status, or notes.
                </p>
            </div>
        </div>

        <div class="sf-panel-body">
            <form method="GET" action="{{ route('manager.invoices.index') }}">
                <div class="row g-3 align-items-end">

                    <div class="col-12 col-lg-5">
                        <label class="form-label fw-bold small text-muted">
                            Search
                        </label>
                        <input
                            type="text"
                            name="q"
                            value="{{ $q ?? request('q') }}"
                            placeholder="Invoice number, job id, booking id, notes..."
                            class="form-control"
                        >
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <label class="form-label fw-bold small text-muted">
                            Invoice Status
                        </label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="issued" @selected(($status ?? request('status')) === 'issued')>Issued</option>
                            <option value="paid" @selected(($status ?? request('status')) === 'paid')>Paid</option>
                            <option value="void" @selected(($status ?? request('status')) === 'void')>Void</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6 col-lg-2">
                        <label class="form-label fw-bold small text-muted">
                            Payment
                        </label>
                        <select name="payment_status" class="form-select">
                            <option value="">All</option>
                            <option value="unpaid" @selected(($paymentStatus ?? request('payment_status')) === 'unpaid')>Unpaid</option>
                            <option value="paid" @selected(($paymentStatus ?? request('payment_status')) === 'paid')>Paid</option>
                        </select>
                    </div>

                    <div class="col-12 col-lg-2">
                        <div class="d-flex gap-2">
                            <button type="submit" class="sf-action-button primary flex-fill">
                                Apply
                            </button>

                            <a href="{{ route('manager.invoices.index') }}"
                               class="sf-action-button light">
                                Reset
                            </a>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="sf-panel overflow-hidden">
        <div class="sf-panel-header">
            <div>
                <h2 class="sf-panel-title">
                    Invoice List
                </h2>
                <p class="sf-panel-subtitle">
                    Invoices generated when jobs are completed.
                </p>
            </div>

            <span class="manager-count-pill">
                {{ method_exists($invoices, 'total') ? $invoices->total() : $invoices->count() }} invoice(s)
            </span>
        </div>

        @if($invoices->count())
            <div class="invoice-table-wrap">
                <table class="table table-hover manager-invoices-table mb-0">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Client</th>
                            <th>Job</th>
                            <th>Booking</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($invoices as $invoice)
                            @php
                                $currentStatus = $invoiceStatus($invoice);
                            @endphp

                            <tr>
                                <td>
                                    <div class="fw-black text-dark">
                                        {{ $invoiceNumber($invoice) }}
                                    </div>
                                    <div class="small text-muted mt-1">
                                        {{ $invoiceDate($invoice) }}
                                    </div>
                                </td>

                                <td>
                                    <div class="fw-bold text-dark">
                                        {{ $clientName($invoice) }}
                                    </div>
                                    @if(!empty($invoice->client_id))
                                        <div class="small text-muted mt-1">
                                            Client #{{ $invoice->client_id }}
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    <div class="fw-bold text-dark">
                                        {{ $jobLabel($invoice) }}
                                    </div>
                                </td>

                                <td>
                                    <div class="fw-bold text-dark">
                                        {{ $bookingLabel($invoice) }}
                                    </div>
                                </td>

                                <td>
                                    <div class="fw-black text-dark">
                                        {{ number_format((float) $invoiceAmount($invoice), 2) }}
                                    </div>
                                </td>

                                <td>
                                    <span class="manager-badge {{ $statusClass($currentStatus) }}">
                                        {{ ucfirst(str_replace('_', ' ', $currentStatus)) }}
                                    </span>
                                </td>

                                <td class="text-end">
                                    <div class="invoice-actions">
                                        @if(Route::has('manager.invoices.show'))
                                            <a href="{{ route('manager.invoices.show', $invoice->id) }}"
                                               class="btn btn-sm btn-primary">
                                                Open
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="sf-empty">
                <h3>No invoices found</h3>
                <p>Invoices created from completed jobs will appear here.</p>
            </div>
        @endif

        @if(method_exists($invoices, 'links'))
            <div class="manager-pagination">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

@push('styles')
<style>
    .manager-invoices-page {
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

    .sf-action-button.light {
        color: #0f172a;
        background: #ffffff;
        border: 1px solid #cbd5e1;
    }

    .invoice-stat-card {
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

    .invoice-stat-card:hover {
        color: #0f172a;
        transform: translateY(-1px);
        box-shadow: 0 20px 48px rgba(15, 23, 42, 0.13);
    }

    .invoice-stat-card.active {
        border-color: rgba(234, 88, 12, 0.45);
        box-shadow: 0 18px 46px rgba(234, 88, 12, 0.14);
    }

    .invoice-stat-card.primary {
        background: linear-gradient(135deg, #eff6ff, #ffffff);
    }

    .invoice-stat-card.warning {
        background: linear-gradient(135deg, #fffbeb, #ffffff);
    }

    .invoice-stat-card.success {
        background: linear-gradient(135deg, #f0fdf4, #ffffff);
    }

    .invoice-stat-card.dark {
        background: linear-gradient(135deg, #0f172a, #1e293b);
    }

    .invoice-stat-card.dark .invoice-stat-label,
    .invoice-stat-card.dark .invoice-stat-note {
        color: #cbd5e1;
    }

    .invoice-stat-card.dark .invoice-stat-value {
        color: #ffffff;
    }

    .invoice-stat-label {
        color: #64748b;
        font-size: 13px;
        font-weight: 900;
    }

    .invoice-stat-value {
        margin-top: 9px;
        color: #020617;
        font-size: 34px;
        line-height: 1;
        font-weight: 950;
        letter-spacing: -0.05em;
    }

    .invoice-stat-note {
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

    .invoice-table-wrap {
        width: 100%;
        overflow-x: hidden;
    }

    .manager-invoices-table {
        width: 100%;
        max-width: 100%;
        margin-bottom: 0;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
    }

    .manager-invoices-table th,
    .manager-invoices-table td {
        white-space: normal !important;
        word-break: break-word;
        overflow-wrap: anywhere;
    }

    .manager-invoices-table th:nth-child(1),
    .manager-invoices-table td:nth-child(1) {
        width: 18%;
    }

    .manager-invoices-table th:nth-child(2),
    .manager-invoices-table td:nth-child(2) {
        width: 20%;
    }

    .manager-invoices-table th:nth-child(3),
    .manager-invoices-table td:nth-child(3) {
        width: 16%;
    }

    .manager-invoices-table th:nth-child(4),
    .manager-invoices-table td:nth-child(4) {
        width: 16%;
    }

    .manager-invoices-table th:nth-child(5),
    .manager-invoices-table td:nth-child(5) {
        width: 12%;
    }

    .manager-invoices-table th:nth-child(6),
    .manager-invoices-table td:nth-child(6) {
        width: 10%;
    }

    .manager-invoices-table th:nth-child(7),
    .manager-invoices-table td:nth-child(7) {
        width: 8%;
    }

    .manager-invoices-table thead th {
        padding: 15px 14px;
        color: #020617;
        background: #eef2f7;
        border-bottom: 1px solid #cbd5e1;
        font-size: 11px;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: 0.045em;
    }

    .manager-invoices-table tbody td {
        padding: 18px 14px;
        vertical-align: top;
        color: #0f172a;
        background: #ffffff;
        border-bottom: 1px solid #e5e7eb;
        font-size: 13px;
        font-weight: 650;
    }

    .manager-invoices-table tbody tr:hover td {
        background: #f8fafc;
    }

    .manager-invoices-table .text-muted {
        color: #475569 !important;
        font-weight: 700;
    }

    .manager-invoices-table .small {
        font-size: 11px;
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

    .invoice-actions .btn {
        width: 100%;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 950;
        padding: 8px 10px;
    }

    .invoice-actions .btn-primary {
        color: #ffffff;
        background: #2563eb;
        border-color: #2563eb;
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

    @media (max-width: 768px) {
        .sf-page-title {
            font-size: 30px !important;
        }

        .sf-panel-header,
        .sf-panel-body {
            padding: 18px;
        }
    }
</style>
@endpush