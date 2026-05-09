@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    @php
        $status = $job->status ?? 'pending';

        $statusBadge = match($status) {
            'completed' => 'bg-green-100 text-green-800 border-green-200',
            'in_progress' => 'bg-blue-100 text-blue-800 border-blue-200',
            default => 'bg-yellow-100 text-yellow-800 border-yellow-200',
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
            'Oil Service' => 'bg-amber-50 text-amber-800 border-amber-100',
            'Battery Service' => 'bg-purple-50 text-purple-800 border-purple-100',
            'Tyre Service' => 'bg-slate-50 text-slate-800 border-slate-200',
            'AC Service' => 'bg-cyan-50 text-cyan-800 border-cyan-100',
            'Brake Service' => 'bg-red-50 text-red-800 border-red-100',
            'Car Wash / Detailing' => 'bg-green-50 text-green-800 border-green-100',
            default => 'bg-gray-50 text-gray-800 border-gray-200',
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
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-2xl font-bold text-gray-900">
                    {{ $job->job_code ?? 'Job' }}
                </h2>

                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $statusBadge }}">
                    {{ ucwords(str_replace('_', ' ', $status)) }}
                </span>

                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $serviceBadge }}">
                    {{ $serviceBucket }}
                </span>
            </div>

            <p class="text-sm text-gray-500 mt-1">
                Job created for
                <span class="font-medium text-gray-700">
                    {{ $job->client?->name ?? 'N/A' }}
                </span>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.jobs.edit', $job->id) }}"
               class="inline-flex items-center justify-center px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg text-sm font-medium">
                Edit Job
            </a>

            <a href="{{ route('admin.jobs.index') }}"
               class="inline-flex items-center justify-center px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                Back to Jobs
            </a>
        </div>

    </div>

    {{-- Top Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <div class="bg-white border rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Current Stage</p>
            <p class="text-xl font-bold text-gray-900 mt-1">
                {{ ucwords(str_replace('_', ' ', $status)) }}
            </p>
            <p class="text-xs text-gray-400 mt-1">
                Operational job status
            </p>
        </div>

        <div class="bg-white border rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Service Bucket</p>
            <p class="text-xl font-bold text-gray-900 mt-1">
                {{ $serviceBucket }}
            </p>
            <p class="text-xs text-gray-400 mt-1">
                Used later for WhatsApp follow-up
            </p>
        </div>

        <div class="bg-white border rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Closure / ROI</p>

            @if($status === 'completed')
                <p class="text-xl font-bold text-green-700 mt-1">
                    Closed
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    Revenue available for ROI reporting
                </p>
            @else
                <p class="text-xl font-bold text-purple-700 mt-1">
                    Invoice Required
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    Invoice no. + amount required to close
                </p>
            @endif
        </div>

    </div>

    {{-- Customer Update Suggestion --}}
    <div class="bg-blue-50 border border-blue-100 rounded-xl p-5">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-xs">
                WA
            </div>

            <div>
                <p class="text-sm font-semibold text-blue-900">
                    Customer update suggestion
                </p>

                <p class="text-sm text-blue-800 mt-1">
                    {{ $customerUpdateNow }}
                </p>

                @if($status !== 'completed')
                    <p class="text-xs text-blue-700 mt-2">
                        Once the job is completed with invoice number and amount, feedback can be triggered and invoice value can be used for campaign ROI.
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- LEFT --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Job Details --}}
            <div class="bg-white rounded-xl border p-5 shadow-sm">

                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-semibold text-gray-900">
                            Job Details
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">
                            Only service information required for customer visibility and future follow-up.
                        </p>
                    </div>
                </div>

                <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">

                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">Service / Job Description</dt>
                        <dd class="font-medium text-gray-900 mt-1">
                            {{ $job->description ?: '—' }}
                        </dd>
                    </div>

                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">Work Summary</dt>
                        <dd class="font-medium text-gray-900 mt-1">
                            {{ $job->work_summary ?: '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-gray-500">Issues Found</dt>
                        <dd class="font-medium text-gray-900 mt-1">
                            {{ $job->issues_found ?: '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-gray-500">Parts Used</dt>
                        <dd class="font-medium text-gray-900 mt-1">
                            {{ $job->parts_used ?: '—' }}
                        </dd>
                    </div>

                </dl>

            </div>

            {{-- Service Signal --}}
            <div class="bg-white rounded-xl border p-5 shadow-sm">

                <h3 class="font-semibold text-gray-900">
                    Service Signal
                </h3>

                <p class="text-sm text-gray-500 mt-1">
                    This job is currently detected under the following service bucket.
                </p>

                <div class="mt-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border {{ $serviceBadge }}">
                        {{ $serviceBucket }}
                    </span>
                </div>

                <p class="text-xs text-gray-500 mt-3">
                    This helps SayaraForce prepare the correct WhatsApp follow-up once the job is closed.
                </p>

            </div>

        </div>

        {{-- RIGHT --}}
        <aside class="space-y-6">

            {{-- Client --}}
            <div class="bg-white rounded-xl border p-5 shadow-sm">

                <h3 class="font-semibold text-gray-900 mb-4">
                    Client
                </h3>

                <div class="text-sm space-y-3">

                    <div>
                        <p class="text-xs text-gray-500">Name</p>
                        <p class="font-medium text-gray-900">
                            {{ $job->client?->name ?? '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500">Phone</p>
                        <p class="font-medium text-gray-900">
                            {{ $job->client?->phone ?: $job->client?->phone_norm ?: '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500">Email</p>
                        <p class="font-medium text-gray-900">
                            {{ $job->client?->email ?: '—' }}
                        </p>
                    </div>

                </div>

            </div>

            {{-- Closure & ROI --}}
            <div class="bg-white rounded-xl border p-5 shadow-sm">

                <h3 class="font-semibold text-gray-900 mb-4">
                    Closure & ROI
                </h3>

                <div class="space-y-3 text-sm">

                    <div>
                        <p class="text-xs text-gray-500">Invoice Number</p>
                        <p class="font-medium text-gray-900">
                            {{ $invoiceNumber ?: 'Not captured yet' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500">Invoice Amount</p>
                        <p class="font-medium text-gray-900">
                            {{ $invoiceAmount ? 'AED ' . number_format((float) $invoiceAmount, 2) : 'Not captured yet' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500">ROI Status</p>

                        @if($status === 'completed')
                            <span class="inline-flex items-center mt-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-800 border border-green-100">
                                {{ $roiStatus }}
                            </span>
                        @else
                            <span class="inline-flex items-center mt-1 px-2.5 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-800 border border-purple-100">
                                {{ $roiStatus }}
                            </span>
                        @endif
                    </div>

                    <div class="pt-3 border-t">
                        @if($status === 'completed')
                            <div class="bg-green-50 border border-green-100 rounded-lg p-3">
                                <p class="text-sm font-medium text-green-800">
                                    Job closed
                                </p>
                                <p class="text-xs text-green-700 mt-1">
                                    This invoice value can now be used for Meta / WhatsApp campaign ROI reporting.
                                </p>
                            </div>
                        @else
                            <div class="bg-purple-50 border border-purple-100 rounded-lg p-3">
                                <p class="text-sm font-medium text-purple-800">
                                    Invoice required before closing
                                </p>
                                <p class="text-xs text-purple-700 mt-1">
                                    Only invoice number and amount are needed. No itemized bill or job card upload required.
                                </p>
                            </div>

                            <a href="{{ route('admin.jobs.edit', $job->id) }}"
                               class="mt-3 inline-flex w-full justify-center px-4 py-2 bg-purple-700 hover:bg-purple-800 text-white rounded-lg text-sm font-medium">
                                Add Invoice / Close Job
                            </a>
                        @endif
                    </div>

                </div>

            </div>

        </aside>

    </div>

</div>
@endsection