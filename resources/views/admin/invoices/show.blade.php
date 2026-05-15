@extends('layouts.app')

@section('title', 'Invoice Details')

@section('content')
<div class="sf-page space-y-6">

    @php
        $invoiceNumber = $invoice->invoice_number
            ?? $invoice->number
            ?? 'INV-' . str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT);

        $statusValue = $invoice->status ?? 'pending';

        $statusBadge = match($statusValue) {
            'paid' => 'sf-badge-green',
            'overdue' => 'sf-badge-red',
            default => 'sf-badge-yellow',
        };

        $amount = (float) ($invoice->amount ?? 0);
        $currency = $invoice->currency ?? 'AED';

        $hasRevenue = $amount > 0;
        $hasJob = !empty($invoice->job_id);
        $roiReady = $statusValue === 'paid' && $hasRevenue && $hasJob;

        $sourceLabel = $invoice->source
            ? ucwords(str_replace('_', ' ', $invoice->source))
            : 'Generated';
    @endphp

    {{-- Header --}}
    <div class="sf-hero-panel">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">

            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="sf-kicker">
                        Invoice Profile
                    </div>

                    <span class="{{ $statusBadge }}">
                        {{ ucwords($statusValue) }}
                    </span>

                    @if($roiReady)
                        <span class="sf-badge-orange">
                            ROI Ready
                        </span>
                    @else
                        <span class="sf-badge-slate">
                            ROI Pending
                        </span>
                    @endif
                </div>

                <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-white">
                    Invoice {{ $invoiceNumber }}
                </h1>

                <p class="mt-2 text-sm font-medium text-slate-400">
                    Lightweight invoice record used for revenue and campaign ROI reporting.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.invoices.edit', $invoice) }}" class="sf-btn-primary">
                    Edit Invoice
                </a>

                <a href="{{ route('admin.invoices.index') }}" class="sf-btn-secondary">
                    Back to Invoices
                </a>
            </div>

        </div>
    </div>

    {{-- Success --}}
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

    {{-- Top Cards --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">

        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Invoice Amount
            </div>

            <div class="mt-2 text-2xl font-extrabold text-white">
                {{ $currency }} {{ number_format($amount, 2) }}
            </div>

            <div class="sf-stat-note">
                Revenue value captured
            </div>
        </div>

        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Status
            </div>

            <div class="mt-2 text-2xl font-extrabold text-white">
                {{ ucwords($statusValue) }}
            </div>

            <div class="sf-stat-note">
                Payment / invoice state
            </div>
        </div>

        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Linked Job
            </div>

            <div class="mt-2 text-2xl font-extrabold {{ $hasJob ? 'text-green-300' : 'text-red-300' }}">
                {{ $hasJob ? 'Yes' : 'No' }}
            </div>

            <div class="sf-stat-note">
                Needed for attribution
            </div>
        </div>

        <div class="sf-stat-card">
            <div class="sf-stat-label">
                ROI Status
            </div>

            @if($roiReady)
                <div class="mt-2 text-2xl font-extrabold text-orange-300">
                    Ready
                </div>

                <div class="sf-stat-note">
                    Job + paid revenue available
                </div>
            @else
                <div class="mt-2 text-2xl font-extrabold text-yellow-300">
                    Pending
                </div>

                <div class="sf-stat-note">
                    Missing job, paid status, or amount
                </div>
            @endif
        </div>

    </div>

    {{-- ROI Note --}}
    <div class="rounded-3xl border border-orange-400/20 bg-orange-500/10 p-5 shadow-xl shadow-black/20">
        <div class="font-extrabold text-orange-300">
            ROI-focused invoice tracking
        </div>

        <p class="mt-2 text-sm font-medium leading-6 text-orange-100/80">
            This invoice value can later be connected to lead source, Meta campaigns, WhatsApp campaigns, opportunities, bookings and jobs.
            No itemized billing is required inside SayaraForce.
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- LEFT --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- Invoice Details --}}
            <div class="sf-card">

                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Invoice Details
                    </h2>
                </div>

                <div class="sf-card-body">
                    <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">

                        <div>
                            <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Invoice Number
                            </dt>

                            <dd class="mt-1 font-extrabold text-white">
                                {{ $invoiceNumber }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Amount
                            </dt>

                            <dd class="mt-1 font-extrabold text-orange-300">
                                {{ $currency }} {{ number_format($amount, 2) }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Status
                            </dt>

                            <dd class="mt-2">
                                <span class="{{ $statusBadge }}">
                                    {{ ucwords($statusValue) }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Source
                            </dt>

                            <dd class="mt-1 font-bold text-slate-200">
                                {{ $sourceLabel }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Invoice Date
                            </dt>

                            <dd class="mt-1 font-bold text-slate-200">
                                {{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') : '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Due Date
                            </dt>

                            <dd class="mt-1 font-bold text-slate-200">
                                {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('Y-m-d') : '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Created
                            </dt>

                            <dd class="mt-1 font-bold text-slate-200">
                                {{ $invoice->created_at?->format('Y-m-d H:i') ?? '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Last Updated
                            </dt>

                            <dd class="mt-1 font-bold text-slate-200">
                                {{ $invoice->updated_at?->format('Y-m-d H:i') ?? '—' }}
                            </dd>
                        </div>

                    </dl>
                </div>

            </div>

            {{-- Linked Job --}}
            <div class="sf-card">

                <div class="sf-card-header flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="sf-section-title">
                            Linked Job
                        </h2>
                    </div>

                    @if($invoice->job)
                        <a href="{{ route('admin.jobs.show', $invoice->job) }}" class="sf-link">
                            View Job
                        </a>
                    @endif
                </div>

                <div class="sf-card-body">
                    @if($invoice->job)
                        <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">

                            <div>
                                <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                    Job Code
                                </dt>

                                <dd class="mt-1 font-extrabold text-white">
                                    {{ $invoice->job->job_code ?? 'Job #' . $invoice->job->id }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                    Job Status
                                </dt>

                                <dd class="mt-1 font-bold text-slate-200">
                                    {{ ucwords(str_replace('_', ' ', $invoice->job->status ?? '—')) }}
                                </dd>
                            </div>

                            <div class="sm:col-span-2">
                                <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                    Job Description
                                </dt>

                                <dd class="mt-1 font-bold leading-6 text-slate-200">
                                    {{ $invoice->job->description ?: '—' }}
                                </dd>
                            </div>

                        </dl>
                    @else
                        <div class="rounded-2xl border border-yellow-400/20 bg-yellow-500/10 p-5">
                            <div class="font-extrabold text-yellow-300">
                                No job linked
                            </div>

                            <p class="mt-2 text-sm font-medium leading-6 text-yellow-100/80">
                                Link this invoice to a job so it can be used properly for campaign ROI attribution.
                            </p>

                            <a href="{{ route('admin.invoices.edit', $invoice) }}" class="sf-btn-primary mt-4">
                                Link Job
                            </a>
                        </div>
                    @endif
                </div>

            </div>

            {{-- Legacy File --}}
            @if($invoice->file_path)
                <div class="sf-card">

                    <div class="sf-card-header">
                        <h2 class="sf-section-title">
                            Uploaded Invoice File
                        </h2>

                        <p class="sf-section-subtitle">
                            File upload is legacy support only. SayaraForce now uses invoice number and amount for ROI tracking.
                        </p>
                    </div>

                    <div class="sf-card-body">
                        <a href="{{ route('admin.invoices.download', $invoice) }}" class="sf-btn-primary">
                            Download File
                        </a>
                    </div>

                </div>
            @endif

        </div>

        {{-- RIGHT --}}
        <aside class="space-y-6">

            {{-- Client --}}
            <div class="sf-card">

                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Client
                    </h2>
                </div>

                <div class="sf-card-body space-y-4 text-sm">

                    <div>
                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                            Name
                        </div>

                        <div class="mt-1 font-extrabold text-white">
                            {{ $invoice->client?->name ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                            Phone
                        </div>

                        <div class="mt-1 font-bold text-slate-200">
                            {{ $invoice->client?->phone ?: $invoice->client?->phone_norm ?: '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                            Email
                        </div>

                        <div class="mt-1 break-words font-bold text-slate-200">
                            {{ $invoice->client?->email ?: '—' }}
                        </div>
                    </div>

                </div>

            </div>

            {{-- ROI Readiness --}}
            <div class="sf-card">

                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        ROI Readiness
                    </h2>
                </div>

                <div class="sf-card-body space-y-4 text-sm">

                    <div class="flex items-center justify-between gap-4">
                        <span class="font-medium text-slate-400">
                            Invoice amount
                        </span>

                        @if($hasRevenue)
                            <span class="font-extrabold text-green-300">
                                Available
                            </span>
                        @else
                            <span class="font-extrabold text-red-300">
                                Missing
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span class="font-medium text-slate-400">
                            Paid status
                        </span>

                        @if($statusValue === 'paid')
                            <span class="font-extrabold text-green-300">
                                Paid
                            </span>
                        @else
                            <span class="font-extrabold text-yellow-300">
                                Not paid
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span class="font-medium text-slate-400">
                            Linked job
                        </span>

                        @if($hasJob)
                            <span class="font-extrabold text-green-300">
                                Linked
                            </span>
                        @else
                            <span class="font-extrabold text-red-300">
                                Missing
                            </span>
                        @endif
                    </div>

                    <div class="sf-divider"></div>

                    @if($roiReady)
                        <div class="rounded-2xl border border-green-400/20 bg-green-500/10 p-4">
                            <div class="font-extrabold text-green-300">
                                Ready for ROI
                            </div>

                            <p class="mt-2 text-xs font-medium leading-5 text-green-100/80">
                                This invoice can be included in campaign revenue reporting.
                            </p>
                        </div>
                    @else
                        <div class="rounded-2xl border border-yellow-400/20 bg-yellow-500/10 p-4">
                            <div class="font-extrabold text-yellow-300">
                                ROI pending
                            </div>

                            <p class="mt-2 text-xs font-medium leading-5 text-yellow-100/80">
                                Make sure the invoice is paid, has amount, and is linked to a job.
                            </p>
                        </div>
                    @endif

                </div>

            </div>

        </aside>

    </div>

</div>
@endsection