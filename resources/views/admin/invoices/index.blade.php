@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Invoices</h2>
            <p class="text-sm text-gray-500 mt-1">
                Lightweight invoice tracking for revenue capture and future ROI reporting.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.jobs.index') }}"
               class="inline-flex items-center justify-center border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg shadow-sm text-sm font-medium">
                Open Jobs
            </a>

            @if(\Illuminate\Support\Facades\Route::has('admin.jobs.completed'))
                <a href="{{ route('admin.jobs.completed') }}"
                   class="inline-flex items-center justify-center border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg shadow-sm text-sm font-medium">
                    Completed Jobs
                </a>
            @endif

            <a href="{{ route('admin.invoices.create') }}"
               class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-sm text-sm font-medium">
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
    @endphp

    {{-- Summary Tiles --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-6">

        <a href="{{ route('admin.invoices.index') }}"
           class="bg-white border rounded-xl p-5 shadow-sm hover:shadow-md transition">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Invoices</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['total'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">All captured invoices</p>
        </a>

        <a href="{{ route('admin.invoices.index', ['status' => 'paid']) }}"
           class="bg-white border rounded-xl p-5 shadow-sm hover:shadow-md transition">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Paid</p>
            <p class="text-3xl font-bold text-green-700 mt-1">{{ $stats['paid'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">Revenue-ready</p>
        </a>

        <a href="{{ route('admin.invoices.index', ['status' => 'pending']) }}"
           class="bg-white border rounded-xl p-5 shadow-sm hover:shadow-md transition">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Pending</p>
            <p class="text-3xl font-bold text-yellow-700 mt-1">{{ $stats['pending'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">Awaiting payment</p>
        </a>

        <a href="{{ route('admin.invoices.index', ['status' => 'overdue']) }}"
           class="bg-white border rounded-xl p-5 shadow-sm hover:shadow-md transition">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Overdue</p>
            <p class="text-3xl font-bold text-red-700 mt-1">{{ $stats['overdue'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">Needs attention</p>
        </a>

        <div class="bg-white border rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 uppercase tracking-wide">ROI Revenue</p>
            <p class="text-2xl font-bold text-purple-700 mt-1">
                AED {{ number_format((float) ($stats['roi_revenue'] ?? 0), 2) }}
            </p>
            <p class="text-xs text-gray-400 mt-1">Paid invoice value</p>
        </div>

    </div>

    {{-- Info Note --}}
    <div class="bg-purple-50 border border-purple-100 rounded-xl p-4 mb-5">
        <p class="text-sm font-semibold text-purple-900">
            ROI-focused invoice tracking
        </p>
        <p class="text-sm text-purple-800 mt-1">
            SayaraForce does not need itemized garage billing here. We only need invoice number, invoice amount, client and job link so revenue can later be connected to Meta, WhatsApp, lead source and campaign ROI.
        </p>
    </div>

    {{-- Success / Warning --}}
    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-100 text-green-800 p-3 rounded-xl text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="mb-4 bg-yellow-50 border border-yellow-100 text-yellow-800 p-3 rounded-xl text-sm">
            {{ session('warning') }}
        </div>
    @endif

    {{-- Toolbar --}}
    <form method="GET" action="{{ route('admin.invoices.index') }}" class="bg-white border rounded-xl p-4 shadow-sm mb-5">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">

            <div class="lg:col-span-7">
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    Search
                </label>

                <input type="text"
                       name="q"
                       value="{{ $currentSearch }}"
                       placeholder="Search invoice no, client, phone, job code, amount..."
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-500" />
            </div>

            <div class="lg:col-span-3">
                <label class="block text-xs font-medium text-gray-500 mb-1">
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

                <select name="status"
                        class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ $currentStatus === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2 flex items-end gap-2">
                <button class="w-full bg-gray-900 hover:bg-gray-800 text-white rounded-lg px-4 py-2 text-sm font-medium">
                    Apply
                </button>

                <a href="{{ route('admin.invoices.index') }}"
                   class="border rounded-lg px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    Reset
                </a>
            </div>

        </div>
    </form>

    {{-- Table --}}
    <div class="overflow-x-auto bg-white rounded-xl border shadow-sm">

        <table class="min-w-full text-sm">

            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Invoice</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Client</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Linked Job</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Amount</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">ROI Status</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>

            <tbody>

            @forelse($invoices as $invoice)

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

                    $hasRevenue = (float) ($invoice->amount ?? 0) > 0;
                    $hasJob = !empty($invoice->job_id);

                    $roiReady = $statusValue === 'paid' && $hasRevenue && $hasJob;
                @endphp

                <tr class="border-t hover:bg-gray-50 align-top">

                    {{-- Invoice --}}
                    <td class="px-4 py-4">
                        <div class="font-semibold text-gray-900">
                            {{ $invoiceNumber }}
                        </div>

                        <div class="text-xs text-gray-500 mt-1">
                            {{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') : 'No invoice date' }}
                        </div>
                    </td>

                    {{-- Client --}}
                    <td class="px-4 py-4">
                        <div class="font-medium text-gray-900">
                            {{ $invoice->client?->name ?? 'N/A' }}
                        </div>

                        <div class="text-xs text-gray-500 mt-1">
                            {{ $invoice->client?->phone ?? $invoice->client?->phone_norm ?? 'No phone' }}
                        </div>
                    </td>

                    {{-- Job --}}
                    <td class="px-4 py-4">
                        @if($invoice->job)
                            <div class="font-medium text-gray-900">
                                {{ $invoice->job->job_code ?? 'Job #' . $invoice->job->id }}
                            </div>

                            <div class="text-xs text-gray-500 mt-1 max-w-[260px]">
                                <span class="block truncate" title="{{ $invoice->job->description }}">
                                    {{ $invoice->job->description ?: 'No job description' }}
                                </span>
                            </div>
                        @else
                            <span class="text-gray-500">Not linked</span>
                        @endif
                    </td>

                    {{-- Amount --}}
                    <td class="px-4 py-4">
                        <div class="font-semibold text-gray-900">
                            {{ $invoice->currency ?? 'AED' }}
                            {{ number_format((float) ($invoice->amount ?? 0), 2) }}
                        </div>
                    </td>

                    {{-- Status --}}
                    <td class="px-4 py-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $statusBadge }}">
                            {{ ucwords($statusValue) }}
                        </span>
                    </td>

                    {{-- ROI Status --}}
                    <td class="px-4 py-4">
                        @if($roiReady)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-800 border border-purple-100">
                                ROI Ready
                            </span>
                            <div class="text-xs text-gray-500 mt-1">
                                Job + paid revenue available
                            </div>
                        @elseif(!$hasJob)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-50 text-gray-700 border border-gray-200">
                                Link Job
                            </span>
                            <div class="text-xs text-gray-500 mt-1">
                                Needed for attribution
                            </div>
                        @elseif(!$hasRevenue)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-100">
                                Missing Amount
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-50 text-yellow-800 border border-yellow-100">
                                Not Paid
                            </span>
                            <div class="text-xs text-gray-500 mt-1">
                                Revenue not confirmed
                            </div>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="px-4 py-4">
                        <div class="flex justify-end gap-3 whitespace-nowrap">

                            <a href="{{ route('admin.invoices.show', $invoice) }}"
                               class="text-blue-600 hover:text-blue-800 hover:underline font-medium">
                                View
                            </a>

                            <a href="{{ route('admin.invoices.edit', $invoice) }}"
                               class="text-green-600 hover:text-green-800 hover:underline font-medium">
                                Edit
                            </a>

                            @if($invoice->file_path && \Illuminate\Support\Facades\Route::has('admin.invoices.download'))
                                <a href="{{ route('admin.invoices.download', $invoice) }}"
                                   class="text-purple-600 hover:text-purple-800 hover:underline font-medium">
                                    Download
                                </a>
                            @endif

                        </div>
                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="7" class="px-4 py-12 text-center">
                        <div class="max-w-md mx-auto">
                            <div class="text-lg font-semibold text-gray-800">
                                No invoices found
                            </div>

                            <p class="text-sm text-gray-500 mt-1">
                                Invoices will appear here after jobs are closed or invoices are created manually.
                            </p>

                            <a href="{{ route('admin.invoices.create') }}"
                               class="inline-flex mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                + Create Invoice
                            </a>
                        </div>
                    </td>
                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

    <div class="mt-4">
        {{ $invoices->links() }}
    </div>

</div>
@endsection