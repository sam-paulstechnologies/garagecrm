@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Open Jobs</h2>
            <p class="text-sm text-gray-500 mt-1">
                Cars currently in service, grouped by the next useful service signal.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.jobs.completed') }}"
               class="inline-flex items-center justify-center border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg shadow-sm text-sm font-medium">
                Completed Jobs
            </a>

            <a href="{{ route('admin.jobs.create') }}"
               class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-sm text-sm font-medium">
                + Create Job
            </a>
        </div>
    </div>

    @php
        $visibleJobs = collect($jobs->items());

        $detectServiceSignal = function ($job) {
            $jobText = strtolower(trim(
                ($job->description ?? '') . ' ' .
                ($job->work_summary ?? '') . ' ' .
                ($job->issues_found ?? '') . ' ' .
                ($job->parts_used ?? '')
            ));

            if (str_contains($jobText, 'oil')) {
                return 'Oil Service';
            }

            if (str_contains($jobText, 'battery')) {
                return 'Battery Service';
            }

            if (str_contains($jobText, 'tyre') || str_contains($jobText, 'tire')) {
                return 'Tyre Service';
            }

            if (str_contains($jobText, 'ac') || str_contains($jobText, 'a/c') || str_contains($jobText, 'air condition')) {
                return 'AC Service';
            }

            if (str_contains($jobText, 'brake')) {
                return 'Brake Service';
            }

            if (str_contains($jobText, 'wash') || str_contains($jobText, 'detailing')) {
                return 'Car Wash / Detailing';
            }

            return 'General Service';
        };

        $bucketCounts = [
            'General Service' => 0,
            'Oil Service' => 0,
            'Battery Service' => 0,
            'Tyre Service' => 0,
            'AC Service' => 0,
            'Brake Service' => 0,
            'Car Wash / Detailing' => 0,
        ];

        foreach ($visibleJobs as $visibleJob) {
            $signal = $detectServiceSignal($visibleJob);
            $bucketCounts[$signal] = ($bucketCounts[$signal] ?? 0) + 1;
        }

        $stats = $stats ?? [
            'open_jobs' => $jobs->total(),
            'pending' => $visibleJobs->where('status', 'pending')->count(),
            'in_progress' => $visibleJobs->where('status', 'in_progress')->count(),
        ];
    @endphp

    {{-- Main Status Tiles --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">

        <a href="{{ route('admin.jobs.index') }}"
           class="bg-white border rounded-xl p-5 shadow-sm hover:shadow-md transition">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Open Jobs</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['open_jobs'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">Cars currently in service</p>
        </a>

        <a href="{{ route('admin.jobs.index', ['status' => 'pending']) }}"
           class="bg-white border rounded-xl p-5 shadow-sm hover:shadow-md transition">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Pending</p>
            <p class="text-3xl font-bold text-yellow-700 mt-1">{{ $stats['pending'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">Waiting to start</p>
        </a>

        <a href="{{ route('admin.jobs.index', ['status' => 'in_progress']) }}"
           class="bg-white border rounded-xl p-5 shadow-sm hover:shadow-md transition">
            <p class="text-xs text-gray-500 uppercase tracking-wide">In Progress</p>
            <p class="text-3xl font-bold text-blue-700 mt-1">{{ $stats['in_progress'] ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-1">Work active now</p>
        </a>

    </div>

    {{-- Service Buckets --}}
    <div class="mb-6">
        <div class="mb-3">
            <h3 class="text-sm font-semibold text-gray-900">
                Cars in Service by Service Bucket
            </h3>
            <p class="text-xs text-gray-500">
                These buckets show what kind of future WhatsApp follow-up can be prepared once the job is closed.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-3">

            <div class="bg-white border rounded-xl p-4 shadow-sm">
                <p class="text-xs text-gray-500 uppercase tracking-wide">General</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $bucketCounts['General Service'] ?? 0 }}</p>
                <p class="text-xs text-gray-400 mt-1">Service reminder</p>
            </div>

            <div class="bg-white border rounded-xl p-4 shadow-sm">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Oil</p>
                <p class="text-2xl font-bold text-amber-700 mt-1">{{ $bucketCounts['Oil Service'] ?? 0 }}</p>
                <p class="text-xs text-gray-400 mt-1">Oil follow-up</p>
            </div>

            <div class="bg-white border rounded-xl p-4 shadow-sm">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Battery</p>
                <p class="text-2xl font-bold text-purple-700 mt-1">{{ $bucketCounts['Battery Service'] ?? 0 }}</p>
                <p class="text-xs text-gray-400 mt-1">Battery check</p>
            </div>

            <div class="bg-white border rounded-xl p-4 shadow-sm">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Tyres</p>
                <p class="text-2xl font-bold text-slate-700 mt-1">{{ $bucketCounts['Tyre Service'] ?? 0 }}</p>
                <p class="text-xs text-gray-400 mt-1">Tyre reminder</p>
            </div>

            <div class="bg-white border rounded-xl p-4 shadow-sm">
                <p class="text-xs text-gray-500 uppercase tracking-wide">AC</p>
                <p class="text-2xl font-bold text-cyan-700 mt-1">{{ $bucketCounts['AC Service'] ?? 0 }}</p>
                <p class="text-xs text-gray-400 mt-1">AC follow-up</p>
            </div>

            <div class="bg-white border rounded-xl p-4 shadow-sm">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Brakes</p>
                <p class="text-2xl font-bold text-red-700 mt-1">{{ $bucketCounts['Brake Service'] ?? 0 }}</p>
                <p class="text-xs text-gray-400 mt-1">Safety check</p>
            </div>

            <div class="bg-white border rounded-xl p-4 shadow-sm">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Wash</p>
                <p class="text-2xl font-bold text-green-700 mt-1">{{ $bucketCounts['Car Wash / Detailing'] ?? 0 }}</p>
                <p class="text-xs text-gray-400 mt-1">Promo ready</p>
            </div>

        </div>
    </div>

    {{-- Info Note --}}
    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-5">
        <p class="text-sm font-semibold text-blue-900">
            Open jobs only
        </p>
        <p class="text-sm text-blue-800 mt-1">
            This page is for cars currently being worked on. Jobs should be closed only after invoice number and invoice amount are captured, so revenue can be used later for ROI reporting.
        </p>
    </div>

    {{-- Toolbar --}}
    <form method="GET" action="{{ route('admin.jobs.index') }}" class="bg-white border rounded-xl p-4 shadow-sm mb-5">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">

            <div class="lg:col-span-7">
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    Search
                </label>

                <input type="text"
                       name="q"
                       value="{{ $q ?? '' }}"
                       placeholder="Search job code, client, service, description..."
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-500" />
            </div>

            <div class="lg:col-span-3">
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    Status
                </label>

                @php
                    $statuses = [
                        '' => 'All Open Jobs',
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                    ];
                @endphp

                <select name="status"
                        class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ ($status ?? '') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2 flex items-end gap-2">
                <button class="w-full bg-gray-900 hover:bg-gray-800 text-white rounded-lg px-4 py-2 text-sm font-medium">
                    Apply
                </button>

                <a href="{{ route('admin.jobs.index') }}"
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
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Job</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Client</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Service Bucket</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Current Stage</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Customer Update Now</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Closure / ROI Status</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>

            <tbody>

            @forelse($jobs as $job)

                @php
                    $serviceSignal = $detectServiceSignal($job);

                    $statusBadge = match($job->status) {
                        'in_progress' => 'bg-blue-100 text-blue-800 border-blue-200',
                        default => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                    };

                    $serviceBadge = match($serviceSignal) {
                        'Oil Service' => 'bg-amber-50 text-amber-800 border-amber-100',
                        'Battery Service' => 'bg-purple-50 text-purple-800 border-purple-100',
                        'Tyre Service' => 'bg-slate-50 text-slate-800 border-slate-200',
                        'AC Service' => 'bg-cyan-50 text-cyan-800 border-cyan-100',
                        'Brake Service' => 'bg-red-50 text-red-800 border-red-100',
                        'Car Wash / Detailing' => 'bg-green-50 text-green-800 border-green-100',
                        default => 'bg-gray-50 text-gray-800 border-gray-200',
                    };

                    $customerUpdate = match($job->status) {
                        'pending' => 'Send start or inspection update once work begins.',
                        'in_progress' => 'Send progress update if customer needs visibility.',
                        default => 'Update customer when job changes.',
                    };
                @endphp

                <tr class="border-t hover:bg-gray-50 align-top">

                    {{-- Job --}}
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

                    {{-- Client --}}
                    <td class="px-4 py-4">
                        <div class="font-medium text-gray-900">
                            {{ $job->client?->name ?? 'N/A' }}
                        </div>
                    </td>

                    {{-- Service Bucket --}}
                    <td class="px-4 py-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $serviceBadge }}">
                            {{ $serviceSignal }}
                        </span>
                    </td>

                    {{-- Current Stage --}}
                    <td class="px-4 py-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $statusBadge }}">
                            {{ ucwords(str_replace('_', ' ', $job->status)) }}
                        </span>
                    </td>

                    {{-- Customer Update Now --}}
                    <td class="px-4 py-4 max-w-[300px]">
                        <div class="text-gray-900">
                            {{ $customerUpdate }}
                        </div>
                    </td>

                    {{-- Closure / ROI Status --}}
                    <td class="px-4 py-4">
                        <div class="font-medium text-gray-900">
                            Invoice required to close
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            Capture invoice no. + amount for campaign ROI.
                        </div>
                    </td>

                    {{-- Actions --}}
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
                        <div class="max-w-md mx-auto">
                            <div class="text-lg font-semibold text-gray-800">
                                No open jobs found
                            </div>

                            <p class="text-sm text-gray-500 mt-1">
                                Open jobs will appear here when bookings are converted to jobs or when a new job is created.
                            </p>

                            <a href="{{ route('admin.jobs.create') }}"
                               class="inline-flex mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                + Create Job
                            </a>
                        </div>
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