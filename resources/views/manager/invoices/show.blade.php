@extends('layouts.manager')

@section('title', ($invoice->invoice_number ?? $invoice->reference_number ?? $invoice->number ?? 'Invoice #' . $invoice->id))

@section('content')
@php
    use Illuminate\Support\Facades\Route;

    $invoiceNumber = $invoice->invoice_number
        ?? $invoice->reference_number
        ?? $invoice->number
        ?? 'Invoice #' . $invoice->id;

    $labourAmount = $invoice->labour_amount
        ?? $invoice->labor_amount
        ?? 0;

    $partsAmount = $invoice->parts_amount
        ?? 0;

    $subtotal = $invoice->subtotal
        ?? $invoice->sub_total
        ?? ((float) $labourAmount + (float) $partsAmount);

    $discountAmount = $invoice->discount_amount
        ?? 0;

    $vatRate = $invoice->vat_rate
        ?? $invoice->tax_rate
        ?? 5;

    $vatAmount = $invoice->vat_amount
        ?? $invoice->tax_amount
        ?? 0;

    $totalAmount = $invoice->total_amount
        ?? $invoice->grand_total
        ?? $invoice->amount
        ?? 0;

    $status = $invoice->payment_status
        ?? $invoice->status
        ?? 'issued';

    $notes = $invoice->invoice_notes
        ?? $invoice->notes
        ?? null;

    $invoiceDate = $invoice->issued_at
        ?? $invoice->invoice_date
        ?? $invoice->created_at
        ?? null;

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

    $statusClass = match(strtolower((string) $status)) {
        'paid' => 'badge-soft-success',
        'unpaid', 'issued', 'open' => 'badge-soft-warning',
        'cancelled', 'canceled', 'void' => 'badge-soft-danger',
        default => 'badge-soft-muted',
    };
@endphp

<div class="manager-invoice-show-page">

    {{-- Header --}}
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <div class="sf-kicker">
                Invoice Review
            </div>

            <h1 class="sf-page-title mt-2">
                {{ $invoiceNumber }}
            </h1>

            <p class="sf-page-subtitle">
                Review invoice details, linked job, booking, and payment status.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @if(Route::has('manager.invoices.index'))
                <a href="{{ route('manager.invoices.index') }}"
                   class="sf-action-button light">
                    Back to Invoices
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

        <div class="col-12 col-xl-8">

            {{-- Invoice Summary --}}
            <div class="sf-panel mb-4">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">
                            Invoice Summary
                        </h2>
                        <p class="sf-panel-subtitle">
                            Amount breakdown and current invoice status.
                        </p>
                    </div>

                    <span class="manager-badge {{ $statusClass }}">
                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                    </span>
                </div>

                <div class="sf-panel-body">
                    <div class="invoice-total-box mb-4">
                        <span>Total Amount</span>
                        <strong>{{ number_format((float) $totalAmount, 2) }}</strong>
                    </div>

                    <div class="row g-4">

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Invoice Number</span>
                                <span class="detail-value">{{ $invoiceNumber }}</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Invoice Date</span>
                                <span class="detail-value">{{ $formatDateTime($invoiceDate, 'No date') }}</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Labour Amount</span>
                                <span class="detail-value">{{ number_format((float) $labourAmount, 2) }}</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Parts Amount</span>
                                <span class="detail-value">{{ number_format((float) $partsAmount, 2) }}</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Subtotal</span>
                                <span class="detail-value">{{ number_format((float) $subtotal, 2) }}</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Discount</span>
                                <span class="detail-value">{{ number_format((float) $discountAmount, 2) }}</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">VAT Rate</span>
                                <span class="detail-value">{{ number_format((float) $vatRate, 2) }}%</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">VAT Amount</span>
                                <span class="detail-value">{{ number_format((float) $vatAmount, 2) }}</span>
                            </div>
                        </div>

                    </div>

                    @if($notes)
                        <div class="notes-box mt-4">
                            <span class="detail-label">Invoice Notes</span>
                            <div class="notes-content">
                                {{ $notes }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Linked Records --}}
            <div class="sf-panel">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">
                            Linked Records
                        </h2>
                        <p class="sf-panel-subtitle">
                            Related client, job, and booking details.
                        </p>
                    </div>
                </div>

                <div class="sf-panel-body">
                    <div class="row g-4">

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Client</span>
                                <span class="detail-value">
                                    {{ $client->name ?? 'Client not linked' }}
                                </span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Client Phone</span>
                                <span class="detail-value">
                                    {{ $client->phone ?? $client->whatsapp ?? 'No phone' }}
                                </span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Job</span>
                                <span class="detail-value">
                                    {{ $job->job_code ?? (!empty($invoice->job_id) ? 'Job #' . $invoice->job_id : 'No job linked') }}
                                </span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="detail-card">
                                <span class="detail-label">Booking</span>
                                <span class="detail-value">
                                    {{ !empty($invoice->booking_id) ? 'Booking #' . $invoice->booking_id : 'No booking linked' }}
                                </span>
                            </div>
                        </div>

                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        @if($job && Route::has('manager.jobs.show'))
                            <a href="{{ route('manager.jobs.show', $job->id) }}"
                               class="action-btn action-light action-inline">
                                Open Job
                            </a>
                        @endif

                        @if($booking && Route::has('manager.bookings.show'))
                            <a href="{{ route('manager.bookings.show', $booking->id) }}"
                               class="action-btn action-light action-inline">
                                Open Booking
                            </a>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        {{-- Actions --}}
        <div class="col-12 col-xl-4">

            <div class="sf-panel mb-4">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">
                            Payment Actions
                        </h2>
                        <p class="sf-panel-subtitle">
                            Update invoice payment status.
                        </p>
                    </div>
                </div>

                <div class="sf-panel-body">
                    @if(Route::has('manager.invoices.mark-paid'))
                        <form method="POST"
                              action="{{ route('manager.invoices.mark-paid', $invoice->id) }}"
                              class="mb-3"
                              onsubmit="return confirm('Mark this invoice as paid?');">
                            @csrf
                            @method('PATCH')

                            <button type="submit" class="action-btn action-success">
                                Mark as Paid
                            </button>
                        </form>
                    @endif

                    @if(Route::has('manager.invoices.mark-unpaid'))
                        <form method="POST"
                              action="{{ route('manager.invoices.mark-unpaid', $invoice->id) }}"
                              onsubmit="return confirm('Mark this invoice as unpaid?');">
                            @csrf
                            @method('PATCH')

                            <button type="submit" class="action-btn action-orange">
                                Mark as Unpaid
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="sf-panel">
                <div class="sf-panel-header">
                    <div>
                        <h2 class="sf-panel-title">
                            Timeline
                        </h2>
                        <p class="sf-panel-subtitle">
                            Invoice timestamps.
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
                                    {{ $formatDateTime($invoice->created_at ?? null, 'Not available') }}
                                </span>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <span class="timeline-dot {{ !empty($invoice->issued_at) ? 'active' : '' }}"></span>
                            <div>
                                <span class="timeline-label">Issued</span>
                                <span class="timeline-value">
                                    {{ $formatDateTime($invoice->issued_at ?? null, 'Not available') }}
                                </span>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <span class="timeline-dot {{ !empty($invoice->paid_at) ? 'active' : '' }}"></span>
                            <div>
                                <span class="timeline-label">Paid</span>
                                <span class="timeline-value">
                                    {{ $formatDateTime($invoice->paid_at ?? null, 'Not paid') }}
                                </span>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <span class="timeline-dot {{ !empty($invoice->updated_at) ? 'active' : '' }}"></span>
                            <div>
                                <span class="timeline-label">Updated</span>
                                <span class="timeline-value">
                                    {{ $formatDateTime($invoice->updated_at ?? null, 'Not available') }}
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
    .manager-invoice-show-page {
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

    .invoice-total-box {
        padding: 24px;
        border-radius: 20px;
        color: #ffffff;
        background:
            radial-gradient(circle at top left, rgba(234, 88, 12, 0.45), transparent 35%),
            linear-gradient(135deg, #020617, #0f172a);
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.18);
    }

    .invoice-total-box span {
        display: block;
        color: #cbd5e1;
        font-size: 13px;
        font-weight: 850;
    }

    .invoice-total-box strong {
        display: block;
        margin-top: 8px;
        color: #ffffff;
        font-size: 38px;
        line-height: 1;
        font-weight: 950;
        letter-spacing: -0.05em;
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

    .action-success {
        color: #ffffff;
        background: #16a34a;
        border-color: #16a34a;
        box-shadow: 0 10px 22px rgba(22, 163, 74, 0.18);
    }

    .action-orange {
        color: #ffffff;
        background: #ea580c;
        border-color: #ea580c;
        box-shadow: 0 10px 22px rgba(234, 88, 12, 0.18);
    }

    .action-light {
        color: #0f172a;
        background: #ffffff;
        border-color: #cbd5e1;
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

        .action-inline {
            width: 100%;
        }
    }
</style>
@endpush
