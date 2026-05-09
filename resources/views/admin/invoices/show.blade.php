@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-6">

    @php
        $invoiceNumber = $invoice->invoice_number
            ?? $invoice->number
            ?? 'INV-' . str_pad((string) $invoice->id, 5, '0', STR_PAD_LEFT);

        $statusValue = $invoice->status ?? 'pending';

        $statusBadge = match($statusValue) {
            'paid' => 'bg-green-50 text-green-800 border-green-100',
            'overdue' => 'bg-red-50 text-red-800 border-red-100',
            default => 'bg-yellow-50 text-yellow-800 border-yellow-100',
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
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-2xl font-bold text-gray-900">
                    Invoice {{ $invoiceNumber }}
                </h2>

                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $statusBadge }}">
                    {{ ucwords($statusValue) }}
                </span>

                @if($roiReady)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-800 border border-purple-100">
                        ROI Ready
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-50 text-gray-700 border border-gray-200">
                        ROI Pending
                    </span>
                @endif
            </div>

            <p class="text-sm text-gray-500 mt-1">
                Lightweight invoice record used for revenue and campaign ROI reporting.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.invoices.edit', $invoice) }}"
               class="inline-flex items-center justify-center px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg text-sm font-medium">
                Edit Invoice
            </a>

            <a href="{{ route('admin.invoices.index') }}"
               class="inline-flex items-center justify-center px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                Back to Invoices
            </a>
        </div>

    </div>

    {{-- Success --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-100 text-green-800 p-3 rounded-xl text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Top Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

        <div class="bg-white border rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Invoice Amount</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">
                {{ $currency }} {{ number_format($amount, 2) }}
            </p>
            <p class="text-xs text-gray-400 mt-1">
                Revenue value captured
            </p>
        </div>

        <div class="bg-white border rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Status</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">
                {{ ucwords($statusValue) }}
            </p>
            <p class="text-xs text-gray-400 mt-1">
                Payment / invoice state
            </p>
        </div>

        <div class="bg-white border rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Linked Job</p>
            <p class="text-2xl font-bold {{ $hasJob ? 'text-green-700' : 'text-red-700' }} mt-1">
                {{ $hasJob ? 'Yes' : 'No' }}
            </p>
            <p class="text-xs text-gray-400 mt-1">
                Needed for attribution
            </p>
        </div>

        <div class="bg-white border rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 uppercase tracking-wide">ROI Status</p>
            @if($roiReady)
                <p class="text-2xl font-bold text-purple-700 mt-1">
                    Ready
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    Job + paid revenue available
                </p>
            @else
                <p class="text-2xl font-bold text-yellow-700 mt-1">
                    Pending
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    Missing job, paid status, or amount
                </p>
            @endif
        </div>

    </div>

    {{-- ROI Note --}}
    <div class="bg-purple-50 border border-purple-100 rounded-xl p-4">
        <p class="text-sm font-semibold text-purple-900">
            ROI-focused invoice tracking
        </p>
        <p class="text-sm text-purple-800 mt-1">
            This invoice value can later be connected to lead source, Meta campaigns, WhatsApp campaigns, opportunities, bookings and jobs.
            No itemized billing is required inside SayaraForce.
        </p>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- LEFT --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Invoice Details --}}
            <div class="bg-white rounded-xl border p-5 shadow-sm">

                <h3 class="font-semibold text-gray-900 mb-4">
                    Invoice Details
                </h3>

                <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">

                    <div>
                        <dt class="text-gray-500">Invoice Number</dt>
                        <dd class="font-medium text-gray-900 mt-1">
                            {{ $invoiceNumber }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-gray-500">Amount</dt>
                        <dd class="font-medium text-gray-900 mt-1">
                            {{ $currency }} {{ number_format($amount, 2) }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-gray-500">Status</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $statusBadge }}">
                                {{ ucwords($statusValue) }}
                            </span>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-gray-500">Source</dt>
                        <dd class="font-medium text-gray-900 mt-1">
                            {{ $sourceLabel }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-gray-500">Invoice Date</dt>
                        <dd class="font-medium text-gray-900 mt-1">
                            {{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') : '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-gray-500">Due Date</dt>
                        <dd class="font-medium text-gray-900 mt-1">
                            {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('Y-m-d') : '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-gray-500">Created</dt>
                        <dd class="font-medium text-gray-900 mt-1">
                            {{ $invoice->created_at?->format('Y-m-d H:i') ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-gray-500">Last Updated</dt>
                        <dd class="font-medium text-gray-900 mt-1">
                            {{ $invoice->updated_at?->format('Y-m-d H:i') ?? '—' }}
                        </dd>
                    </div>

                </dl>

            </div>

            {{-- Linked Job --}}
            <div class="bg-white rounded-xl border p-5 shadow-sm">

                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900">
                        Linked Job
                    </h3>

                    @if($invoice->job)
                        <a href="{{ route('admin.jobs.show', $invoice->job) }}"
                           class="text-sm text-blue-600 hover:underline font-medium">
                            View Job
                        </a>
                    @endif
                </div>

                @if($invoice->job)
                    <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">

                        <div>
                            <dt class="text-gray-500">Job Code</dt>
                            <dd class="font-medium text-gray-900 mt-1">
                                {{ $invoice->job->job_code ?? 'Job #' . $invoice->job->id }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Job Status</dt>
                            <dd class="font-medium text-gray-900 mt-1">
                                {{ ucwords(str_replace('_', ' ', $invoice->job->status ?? '—')) }}
                            </dd>
                        </div>

                        <div class="sm:col-span-2">
                            <dt class="text-gray-500">Job Description</dt>
                            <dd class="font-medium text-gray-900 mt-1">
                                {{ $invoice->job->description ?: '—' }}
                            </dd>
                        </div>

                    </dl>
                @else
                    <div class="bg-yellow-50 border border-yellow-100 rounded-lg p-4">
                        <p class="text-sm font-medium text-yellow-800">
                            No job linked
                        </p>
                        <p class="text-xs text-yellow-700 mt-1">
                            Link this invoice to a job so it can be used properly for campaign ROI attribution.
                        </p>

                        <a href="{{ route('admin.invoices.edit', $invoice) }}"
                           class="inline-flex mt-3 px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg text-sm font-medium">
                            Link Job
                        </a>
                    </div>
                @endif

            </div>

            {{-- Legacy File --}}
            @if($invoice->file_path)
                <div class="bg-white rounded-xl border p-5 shadow-sm">

                    <h3 class="font-semibold text-gray-900 mb-3">
                        Uploaded Invoice File
                    </h3>

                    <p class="text-sm text-gray-500 mb-4">
                        File upload is legacy support only. SayaraForce now uses invoice number and amount for ROI tracking.
                    </p>

                    <a href="{{ route('admin.invoices.download', $invoice) }}"
                       class="inline-flex px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium">
                        Download File
                    </a>

                </div>
            @endif

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
                            {{ $invoice->client?->name ?? '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500">Phone</p>
                        <p class="font-medium text-gray-900">
                            {{ $invoice->client?->phone ?: $invoice->client?->phone_norm ?: '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500">Email</p>
                        <p class="font-medium text-gray-900">
                            {{ $invoice->client?->email ?: '—' }}
                        </p>
                    </div>

                </div>

            </div>

            {{-- ROI Readiness --}}
            <div class="bg-white rounded-xl border p-5 shadow-sm">

                <h3 class="font-semibold text-gray-900 mb-4">
                    ROI Readiness
                </h3>

                <div class="space-y-3 text-sm">

                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Invoice amount</span>
                        @if($hasRevenue)
                            <span class="text-green-700 font-medium">Available</span>
                        @else
                            <span class="text-red-700 font-medium">Missing</span>
                        @endif
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Paid status</span>
                        @if($statusValue === 'paid')
                            <span class="text-green-700 font-medium">Paid</span>
                        @else
                            <span class="text-yellow-700 font-medium">Not paid</span>
                        @endif
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Linked job</span>
                        @if($hasJob)
                            <span class="text-green-700 font-medium">Linked</span>
                        @else
                            <span class="text-red-700 font-medium">Missing</span>
                        @endif
                    </div>

                    <div class="pt-3 border-t">
                        @if($roiReady)
                            <div class="bg-green-50 border border-green-100 rounded-lg p-3">
                                <p class="text-sm font-medium text-green-800">
                                    Ready for ROI
                                </p>
                                <p class="text-xs text-green-700 mt-1">
                                    This invoice can be included in campaign revenue reporting.
                                </p>
                            </div>
                        @else
                            <div class="bg-yellow-50 border border-yellow-100 rounded-lg p-3">
                                <p class="text-sm font-medium text-yellow-800">
                                    ROI pending
                                </p>
                                <p class="text-xs text-yellow-700 mt-1">
                                    Make sure the invoice is paid, has amount, and is linked to a job.
                                </p>
                            </div>
                        @endif
                    </div>

                </div>

            </div>

        </aside>

    </div>

</div>
@endsection