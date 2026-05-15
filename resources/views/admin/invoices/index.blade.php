@extends('layouts.app')

@section('title', 'Invoices')

@section('content')
<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Revenue Tracking
            </div>

            <h1 class="sf-page-title mt-3">
                Invoices
            </h1>

            <p class="sf-page-subtitle">
                Lightweight invoice tracking for revenue capture and future ROI reporting.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.jobs.index') }}" class="sf-btn-secondary">
                Open Jobs
            </a>

            @if(\Illuminate\Support\Facades\Route::has('admin.jobs.completed'))
                <a href="{{ route('admin.jobs.completed') }}" class="sf-btn-secondary">
                    Completed Jobs
                </a>
            @endif

            <a href="{{ route('admin.invoices.create') }}" class="sf-btn-primary">
                + Create Invoice
            </a>
        </div>
    </div>

    @php
        $stats = $stats ?? [
            'total' => $invoices->total(),
            'paid' => 0,
            'pending' => 0,
            'overdue' => 0,
            'roi_revenue' => 0,
        ];

        $currentStatus = $status ?? request('status', '');
        $currentSearch = $q ?? request('q', request('search', ''));

        $statusBadgeClass = function ($statusValue) {
            return match($statusValue) {
                'paid' => 'sf-badge-green',
                'overdue' => 'sf-badge-red',
                default => 'sf-badge-yellow',
            };
        };
    @endphp

    {{-- Summary Tiles --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">

        <a href="{{ route('admin.invoices.index') }}" class="sf-stat-card">
            <div class="sf-stat-label">
                Total Invoices
            </div>

            <div class="sf-stat-value">
                {{ $stats['total'] ?? 0 }}
            </div>

            <div class="sf-stat-note">
                All captured invoices
            </div>
        </a>

        <a href="{{ route('admin.invoices.index', ['status' => 'paid']) }}" class="sf-stat-card">
            <div class="sf-stat-label">
                Paid
            </div>

            <div class="sf-stat-value text-green-300">
                {{ $stats['paid'] ?? 0 }}
            </div>

            <div class="sf-stat-note">
                Revenue-ready
            </div>
        </a>

        <a href="{{ route('admin.invoices.index', ['status' => 'pending']) }}" class="sf-stat-card">
            <div class="sf-stat-label">
                Pending
            </div>

            <div class="sf-stat-value text-yellow-300">
                {{ $stats['pending'] ?? 0 }}
            </div>

            <div class="sf-stat-note">
                Awaiting payment
            </div>
        </a>

        <a href="{{ route('admin.invoices.index', ['status' => 'overdue']) }}" class="sf-stat-card">
            <div class="sf-stat-label">
                Overdue
            </div>

            <div class="sf-stat-value text-red-300">
                {{ $stats['overdue'] ?? 0 }}
            </div>

            <div class="sf-stat-note">
                Needs attention
            </div>
        </a>

        <div class="sf-stat-card">
            <div class="sf-stat-label">
                ROI Revenue
            </div>

            <div class="mt-2 text-2xl font-extrabold tracking-tight text-orange-300">
                AED {{ number_format((float) ($stats['roi_revenue'] ?? 0), 2) }}
            </div>

            <div class="sf-stat-note">
                Paid invoice value
            </div>
        </div>

    </div>

    {{-- Info Note --}}
    <div class="rounded-3xl border border-orange-400/20 bg-orange-500/10 p-5 shadow-xl shadow-black/20">
        <div class="font-extrabold text-orange-300">
            ROI-focused invoice tracking
        </div>

        <p class="mt-2 text-sm font-medium leading-6 text-orange-100/80">
            SayaraForce does not need itemized garage billing here. We only need invoice number, invoice amount, client and job link so revenue can later be connected to Meta, WhatsApp, lead source and campaign ROI.
        </p>
    </div>

    {{-- Success / Warning --}}
    @if(session('success'))
        <div class="sf-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="sf-alert-warning">
            {{ session('warning') }}
        </div>
    @endif

    @if(session('error'))
        <div class="sf-alert-danger">
            {{ session('error') }}
        </div>
    @endif

    {{-- Toolbar --}}
    <form method="GET" action="{{ route('admin.invoices.index') }}" class="sf-card">
        <div class="sf-card-body">
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">

                <div class="lg:col-span-7">
                    <label class="sf-label">
                        Search
                    </label>

                    <input type="text"
                           name="q"
                           value="{{ $currentSearch }}"
                           placeholder="Search invoice no, client, phone, job code, amount..."
                           class="sf-input" />
                </div>

                <div class="lg:col-span-3">
                    <label class="sf-label">
                        Status
                    </label>

                    @php
                        $statuses = [
                            '' => 'All Invoices',
                            'paid' => 'Paid',
                            'pending' => 'Pending',
                            'overdue' => 'Overdue',
                        ];
                    @endphp

                    <select name="status" class="sf-select">
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ $currentStatus === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2 lg:col-span-2">
                    <button class="sf-btn-primary w-full">
                        Apply
                    </button>

                    <a href="{{ route('admin.invoices.index') }}" class="sf-btn-secondary">
                        Reset
                    </a>
                </div>

            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="sf-table-wrap">
        <div class="sf-table-scroll">
            <table class="sf-table">

                <thead>
                    <tr>
                        <th class="w-[16%]">Invoice</th>
                        <th class="w-[16%]">Client</th>
                        <th class="w-[20%]">Linked Job</th>
                        <th class="w-[13%]">Amount</th>
                        <th class="w-[11%]">Status</th>
                        <th class="w-[14%]">ROI Status</th>
                        <th class="w-[10%] text-right">Actions</th>
                    </tr>
                </thead>

                <tbody>

                @forelse($invoices as $invoice)

                    @php
                        $invoiceNumber = $invoice->invoice_number
                            ?? $invoice->number
                            ?? 'INV-' . str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT);

                        $statusValue = $invoice->status ?? 'pending';

                        $hasRevenue = (float) ($invoice->amount ?? 0) > 0;
                        $hasJob = !empty($invoice->job_id);

                        $roiReady = $statusValue === 'paid' && $hasRevenue && $hasJob;
                    @endphp

                    <tr>

                        {{-- Invoice --}}
                        <td>
                            <div class="font-extrabold text-white">
                                {{ $invoiceNumber }}
                            </div>

                            <div class="mt-1 text-xs font-medium text-slate-500">
                                {{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') : 'No invoice date' }}
                            </div>
                        </td>

                        {{-- Client --}}
                        <td>
                            <div class="font-bold text-slate-200">
                                {{ $invoice->client?->name ?? 'N/A' }}
                            </div>

                            <div class="mt-1 text-xs font-medium text-slate-500">
                                {{ $invoice->client?->phone ?? $invoice->client?->phone_norm ?? 'No phone' }}
                            </div>
                        </td>

                        {{-- Job --}}
                        <td>
                            @if($invoice->job)
                                <div class="font-bold text-slate-200">
                                    {{ $invoice->job->job_code ?? 'Job #' . $invoice->job->id }}
                                </div>

                                <div class="mt-1 max-w-[260px] text-xs font-medium text-slate-500">
                                    <span class="block truncate" title="{{ $invoice->job->description }}">
                                        {{ $invoice->job->description ?: 'No job description' }}
                                    </span>
                                </div>
                            @else
                                <span class="font-medium text-slate-500">
                                    Not linked
                                </span>
                            @endif
                        </td>

                        {{-- Amount --}}
                        <td>
                            <div class="font-extrabold text-orange-300">
                                {{ $invoice->currency ?? 'AED' }}
                                {{ number_format((float) ($invoice->amount ?? 0), 2) }}
                            </div>
                        </td>

                        {{-- Status --}}
                        <td>
                            <span class="{{ $statusBadgeClass($statusValue) }}">
                                {{ ucwords($statusValue) }}
                            </span>
                        </td>

                        {{-- ROI Status --}}
                        <td>
                            @if($roiReady)
                                <span class="sf-badge-orange">
                                    ROI Ready
                                </span>

                                <div class="mt-1 text-xs font-medium text-slate-500">
                                    Job + paid revenue available
                                </div>
                            @elseif(!$hasJob)
                                <span class="sf-badge-slate">
                                    Link Job
                                </span>

                                <div class="mt-1 text-xs font-medium text-slate-500">
                                    Needed for attribution
                                </div>
                            @elseif(!$hasRevenue)
                                <span class="sf-badge-red">
                                    Missing Amount
                                </span>
                            @else
                                <span class="sf-badge-yellow">
                                    Not Paid
                                </span>

                                <div class="mt-1 text-xs font-medium text-slate-500">
                                    Revenue not confirmed
                                </div>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="text-right">
                            <div class="flex justify-end gap-3 whitespace-nowrap">

                                <a href="{{ route('admin.invoices.show', $invoice) }}" class="sf-link">
                                    View
                                </a>

                                <a href="{{ route('admin.invoices.edit', $invoice) }}" class="sf-link">
                                    Edit
                                </a>

                                @if($invoice->file_path && \Illuminate\Support\Facades\Route::has('admin.invoices.download'))
                                    <a href="{{ route('admin.invoices.download', $invoice) }}" class="sf-link">
                                        Download
                                    </a>
                                @endif

                            </div>
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="7">
                            <div class="sf-empty">
                                <div class="text-lg font-extrabold text-white">
                                    No invoices found
                                </div>

                                <p class="mt-2 text-sm font-medium text-slate-500">
                                    Invoices will appear here after jobs are closed or invoices are created manually.
                                </p>

                                <a href="{{ route('admin.invoices.create') }}" class="sf-btn-primary mt-4">
                                    + Create Invoice
                                </a>
                            </div>
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="text-slate-300">
        {{ $invoices->links() }}
    </div>

</div>
@endsection