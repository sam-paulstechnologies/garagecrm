@extends('layouts.app')

@section('title', $job->job_code ?? 'Job Details')

@section('content')
<div class="sf-page space-y-6">

    @php
        $status = $job->status ?? 'pending';

        $statusBadge = match($status) {
            'completed' => 'sf-badge-green',
            'in_progress' => 'sf-badge-blue',
            default => 'sf-badge-yellow',
        };

        $jobText = strtolower(trim(
            ($job->description ?? '') . ' ' .
            ($job->work_summary ?? '') . ' ' .
            ($job->issues_found ?? '') . ' ' .
            ($job->parts_used ?? '')
        ));

        $serviceBucket = 'General Service';

        if (str_contains($jobText, 'oil')) {
            $serviceBucket = 'Oil Service';
        } elseif (str_contains($jobText, 'battery')) {
            $serviceBucket = 'Battery Service';
        } elseif (str_contains($jobText, 'tyre') || str_contains($jobText, 'tire')) {
            $serviceBucket = 'Tyre Service';
        } elseif (str_contains($jobText, 'ac') || str_contains($jobText, 'a/c') || str_contains($jobText, 'air condition')) {
            $serviceBucket = 'AC Service';
        } elseif (str_contains($jobText, 'brake')) {
            $serviceBucket = 'Brake Service';
        } elseif (str_contains($jobText, 'wash') || str_contains($jobText, 'detailing')) {
            $serviceBucket = 'Car Wash / Detailing';
        }

        $serviceBadge = match($serviceBucket) {
            'Oil Service' => 'sf-badge-orange',
            'Battery Service' => 'sf-badge-blue',
            'Tyre Service' => 'sf-badge-slate',
            'AC Service' => 'sf-badge-blue',
            'Brake Service' => 'sf-badge-red',
            'Car Wash / Detailing' => 'sf-badge-green',
            default => 'sf-badge-slate',
        };

        $customerUpdateNow = match($status) {
            'pending' => 'Customer can be updated once inspection or work begins.',
            'in_progress' => 'Customer can be sent a progress update if visibility is needed.',
            'completed' => 'Feedback request can be triggered after job closure.',
            default => 'Customer can be updated when the job status changes.',
        };

        $invoice = $job->invoice ?? $job->primaryInvoice ?? $job->invoices?->first();

        $invoiceNumber = $invoice?->invoice_number
            ?? $invoice?->number
            ?? $job->invoice_no
            ?? null;

        $invoiceAmount = $invoice?->amount ?? null;

        $roiStatus = $status === 'completed'
            ? 'Ready for ROI reporting'
            : 'Invoice required before closing';
    @endphp

    {{-- Header --}}
    <div class="sf-hero-panel">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">

            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="sf-kicker">
                        Job Profile
                    </div>

                    <span class="{{ $statusBadge }}">
                        {{ ucwords(str_replace('_', ' ', $status)) }}
                    </span>

                    <span class="{{ $serviceBadge }}">
                        {{ $serviceBucket }}
                    </span>
                </div>

                <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-white">
                    {{ $job->job_code ?? 'Job' }}
                </h1>

                <p class="mt-2 text-sm font-medium text-slate-400">
                    Job created for
                    <span class="font-extrabold text-white">
                        {{ $job->client?->name ?? 'N/A' }}
                    </span>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.jobs.edit', $job->id) }}" class="sf-btn-primary">
                    Edit Job
                </a>

                <a href="{{ route('admin.jobs.index') }}" class="sf-btn-secondary">
                    Back to Jobs
                </a>
            </div>

        </div>
    </div>

    {{-- Top Cards --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Current Stage
            </div>

            <div class="mt-2 text-xl font-extrabold text-white">
                {{ ucwords(str_replace('_', ' ', $status)) }}
            </div>

            <div class="sf-stat-note">
                Operational job status
            </div>
        </div>

        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Service Bucket
            </div>

            <div class="mt-2 text-xl font-extrabold text-white">
                {{ $serviceBucket }}
            </div>

            <div class="sf-stat-note">
                Used later for WhatsApp follow-up
            </div>
        </div>

        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Closure / ROI
            </div>

            @if($status === 'completed')
                <div class="mt-2 text-xl font-extrabold text-green-300">
                    Closed
                </div>

                <div class="sf-stat-note">
                    Revenue available for ROI reporting
                </div>
            @else
                <div class="mt-2 text-xl font-extrabold text-orange-300">
                    Invoice Required
                </div>

                <div class="sf-stat-note">
                    Invoice no. + amount required to close
                </div>
            @endif
        </div>

    </div>

    {{-- Customer Update Suggestion --}}
    <div class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-xl shadow-black/20">
        <div class="flex items-start gap-4">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-500/20 text-xs font-extrabold text-blue-200 ring-1 ring-blue-400/20">
                WA
            </div>

            <div>
                <div class="font-extrabold text-blue-300">
                    Customer update suggestion
                </div>

                <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
                    {{ $customerUpdateNow }}
                </p>

                @if($status !== 'completed')
                    <p class="mt-2 text-xs font-medium leading-5 text-blue-100/70">
                        Once the job is completed with invoice number and amount, feedback can be triggered and invoice value can be used for campaign ROI.
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- LEFT --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- Job Details --}}
            <div class="sf-card">

                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Job Details
                    </h2>

                    <p class="sf-section-subtitle">
                        Only service information required for customer visibility and future follow-up.
                    </p>
                </div>

                <div class="sf-card-body">
                    <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">

                        <div class="sm:col-span-2">
                            <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Service / Job Description
                            </dt>

                            <dd class="mt-1 font-bold leading-6 text-slate-200">
                                {{ $job->description ?: '—' }}
                            </dd>
                        </div>

                        <div class="sm:col-span-2">
                            <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Work Summary
                            </dt>

                            <dd class="mt-1 font-bold leading-6 text-slate-200">
                                {{ $job->work_summary ?: '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Issues Found
                            </dt>

                            <dd class="mt-1 font-bold leading-6 text-slate-200">
                                {{ $job->issues_found ?: '—' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Parts Used
                            </dt>

                            <dd class="mt-1 font-bold leading-6 text-slate-200">
                                {{ $job->parts_used ?: '—' }}
                            </dd>
                        </div>

                    </dl>
                </div>

            </div>

            {{-- Service Signal --}}
            <div class="sf-card">

                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Service Signal
                    </h2>

                    <p class="sf-section-subtitle">
                        This job is currently detected under the following service bucket.
                    </p>
                </div>

                <div class="sf-card-body">
                    <span class="{{ $serviceBadge }}">
                        {{ $serviceBucket }}
                    </span>

                    <p class="mt-4 text-xs font-medium leading-5 text-slate-500">
                        This helps SayaraForce prepare the correct WhatsApp follow-up once the job is closed.
                    </p>
                </div>

            </div>

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
                            {{ $job->client?->name ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                            Phone
                        </div>

                        <div class="mt-1 font-bold text-slate-200">
                            {{ $job->client?->phone ?: $job->client?->phone_norm ?: '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                            Email
                        </div>

                        <div class="mt-1 break-words font-bold text-slate-200">
                            {{ $job->client?->email ?: '—' }}
                        </div>
                    </div>

                </div>

            </div>

            {{-- Closure & ROI --}}
            <div class="sf-card">

                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Closure & ROI
                    </h2>
                </div>

                <div class="sf-card-body space-y-4 text-sm">

                    <div>
                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                            Invoice Number
                        </div>

                        <div class="mt-1 font-extrabold text-white">
                            {{ $invoiceNumber ?: 'Not captured yet' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                            Invoice Amount
                        </div>

                        <div class="mt-1 font-extrabold text-white">
                            {{ $invoiceAmount ? 'AED ' . number_format((float) $invoiceAmount, 2) : 'Not captured yet' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                            ROI Status
                        </div>

                        @if($status === 'completed')
                            <span class="sf-badge-green mt-2">
                                {{ $roiStatus }}
                            </span>
                        @else
                            <span class="sf-badge-orange mt-2">
                                {{ $roiStatus }}
                            </span>
                        @endif
                    </div>

                    <div class="sf-divider"></div>

                    @if($status === 'completed')
                        <div class="rounded-2xl border border-green-400/20 bg-green-500/10 p-4">
                            <div class="font-extrabold text-green-300">
                                Job closed
                            </div>

                            <p class="mt-2 text-xs font-medium leading-5 text-green-100/80">
                                This invoice value can now be used for Meta / WhatsApp campaign ROI reporting.
                            </p>
                        </div>
                    @else
                        <div class="rounded-2xl border border-orange-400/20 bg-orange-500/10 p-4">
                            <div class="font-extrabold text-orange-300">
                                Invoice required before closing
                            </div>

                            <p class="mt-2 text-xs font-medium leading-5 text-orange-100/80">
                                Only invoice number and amount are needed. No itemized bill or job card upload required.
                            </p>
                        </div>

                        <a href="{{ route('admin.jobs.edit', $job->id) }}" class="sf-btn-primary w-full">
                            Add Invoice / Close Job
                        </a>
                    @endif

                </div>

            </div>

        </aside>

    </div>

</div>
@endsection