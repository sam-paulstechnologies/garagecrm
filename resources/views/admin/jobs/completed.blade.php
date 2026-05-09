@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Completed Jobs</h2>
            <p class="text-sm text-gray-500 mt-1">
                Closed jobs with invoice value captured for ROI reporting.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.jobs.index') }}"
               class="inline-flex items-center justify-center border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg shadow-sm text-sm font-medium">
                Open Jobs
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.jobs.completed') }}" class="bg-white border rounded-xl p-4 shadow-sm mb-5">
        <div class="flex gap-2">
            <input type="text"
                   name="q"
                   value="{{ $q ?? '' }}"
                   placeholder="Search job code, client, phone, service..."
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-500" />

            <button class="bg-gray-900 hover:bg-gray-800 text-white rounded-lg px-4 py-2 text-sm font-medium">
                Search
            </button>

            <a href="{{ route('admin.jobs.completed') }}"
               class="border rounded-lg px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                Reset
            </a>
        </div>
    </form>

    <div class="overflow-x-auto bg-white rounded-xl border shadow-sm">

        <table class="min-w-full text-sm">

            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Job</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Client</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Service</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Invoice No.</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Invoice Amount</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">ROI Status</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>

            <tbody>

            @forelse($jobs as $job)

                @php
                    $invoice = $job->invoice ?? $job->invoices?->first();

                    $invoiceNumber = $invoice?->invoice_number
                        ?? $invoice?->number
                        ?? $job->invoice_no
                        ?? '—';

                    $invoiceAmount = $invoice?->amount ?? null;

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
                @endphp

                <tr class="border-t hover:bg-gray-50 align-top">

                    <td class="px-4 py-4">
                        <div class="font-semibold text-gray-900">
                            {{ $job->job_code ?? '—' }}
                        </div>

                        <div class="text-xs text-gray-500 mt-1 max-w-[260px]">
                            <span class="block truncate" title="{{ $job->description }}">
                                {{ $job->description ?: 'No description added' }}
                            </span>
                        </div>
                    </td>

                    <td class="px-4 py-4">
                        <div class="font-medium text-gray-900">
                            {{ $job->client?->name ?? 'N/A' }}
                        </div>

                        <div class="text-xs text-gray-500 mt-1">
                            {{ $job->client?->phone ?: $job->client?->phone_norm ?: 'No phone' }}
                        </div>
                    </td>

                    <td class="px-4 py-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $serviceBadge }}">
                            {{ $serviceBucket }}
                        </span>
                    </td>

                    <td class="px-4 py-4">
                        <span class="font-medium text-gray-900">
                            {{ $invoiceNumber }}
                        </span>
                    </td>

                    <td class="px-4 py-4">
                        <span class="font-semibold text-gray-900">
                            {{ $invoiceAmount ? 'AED ' . number_format((float) $invoiceAmount, 2) : '—' }}
                        </span>
                    </td>

                    <td class="px-4 py-4">
                        @if($invoiceAmount)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-800 border border-green-100">
                                Ready for ROI
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-800 border border-red-100">
                                Missing amount
                            </span>
                        @endif
                    </td>

                    <td class="px-4 py-4">
                        <div class="flex justify-end gap-3 whitespace-nowrap">

                            <a href="{{ route('admin.jobs.show', $job->id) }}"
                               class="text-blue-600 hover:text-blue-800 hover:underline font-medium">
                                View
                            </a>

                            <a href="{{ route('admin.jobs.edit', $job->id) }}"
                               class="text-green-600 hover:text-green-800 hover:underline font-medium">
                                Edit
                            </a>

                        </div>
                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="7" class="px-4 py-12 text-center">
                        <div class="text-lg font-semibold text-gray-800">
                            No completed jobs found
                        </div>

                        <p class="text-sm text-gray-500 mt-1">
                            Jobs will appear here after they are closed with invoice number and amount.
                        </p>

                        <a href="{{ route('admin.jobs.index') }}"
                           class="inline-flex mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                            Go to Open Jobs
                        </a>
                    </td>
                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

    <div class="mt-4">
        {{ $jobs->links() }}
    </div>

</div>
@endsection