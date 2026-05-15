@extends('layouts.app')

@section('title', 'Open Jobs')

@section('content')
<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Job Command Center
            </div>

            <h1 class="sf-page-title mt-3">
                Open Jobs
            </h1>

            <p class="sf-page-subtitle">
                Cars currently in service, grouped by the next useful service signal.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.jobs.completed') }}" class="sf-btn-secondary">
                Completed Jobs
            </a>

            <a href="{{ route('admin.jobs.create') }}" class="sf-btn-primary">
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

        $statusBadge = function ($status) {
            return match($status) {
                'in_progress' => 'sf-badge-blue',
                'completed' => 'sf-badge-green',
                default => 'sf-badge-yellow',
            };
        };

        $serviceBadge = function ($serviceSignal) {
            return match($serviceSignal) {
                'Oil Service' => 'sf-badge-orange',
                'Battery Service' => 'sf-badge-blue',
                'Tyre Service' => 'sf-badge-slate',
                'AC Service' => 'sf-badge-blue',
                'Brake Service' => 'sf-badge-red',
                'Car Wash / Detailing' => 'sf-badge-green',
                default => 'sf-badge-slate',
            };
        };
    @endphp

    {{-- Main Status Tiles --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

        <a href="{{ route('admin.jobs.index') }}" class="sf-stat-card">
            <div class="sf-stat-label">
                Open Jobs
            </div>

            <div class="sf-stat-value">
                {{ $stats['open_jobs'] ?? 0 }}
            </div>

            <div class="sf-stat-note">
                Cars currently in service
            </div>
        </a>

        <a href="{{ route('admin.jobs.index', ['status' => 'pending']) }}" class="sf-stat-card">
            <div class="sf-stat-label">
                Pending
            </div>

            <div class="sf-stat-value text-yellow-300">
                {{ $stats['pending'] ?? 0 }}
            </div>

            <div class="sf-stat-note">
                Waiting to start
            </div>
        </a>

        <a href="{{ route('admin.jobs.index', ['status' => 'in_progress']) }}" class="sf-stat-card">
            <div class="sf-stat-label">
                In Progress
            </div>

            <div class="sf-stat-value text-blue-300">
                {{ $stats['in_progress'] ?? 0 }}
            </div>

            <div class="sf-stat-note">
                Work active now
            </div>
        </a>

    </div>

    {{-- Service Buckets --}}
    <div class="sf-card">
        <div class="sf-card-header">
            <h2 class="sf-section-title">
                Cars in Service by Service Bucket
            </h2>

            <p class="sf-section-subtitle">
                These buckets show what kind of future WhatsApp follow-up can be prepared once the job is closed.
            </p>
        </div>

        <div class="sf-card-body">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">

                <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                        General
                    </div>

                    <div class="mt-2 text-2xl font-extrabold text-white">
                        {{ $bucketCounts['General Service'] ?? 0 }}
                    </div>

                    <div class="mt-1 text-xs font-medium text-slate-500">
                        Service reminder
                    </div>
                </div>

                <div class="rounded-2xl border border-orange-400/20 bg-orange-500/10 p-4">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-orange-300">
                        Oil
                    </div>

                    <div class="mt-2 text-2xl font-extrabold text-white">
                        {{ $bucketCounts['Oil Service'] ?? 0 }}
                    </div>

                    <div class="mt-1 text-xs font-medium text-orange-100/70">
                        Oil follow-up
                    </div>
                </div>

                <div class="rounded-2xl border border-blue-400/20 bg-blue-500/10 p-4">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-blue-300">
                        Battery
                    </div>

                    <div class="mt-2 text-2xl font-extrabold text-white">
                        {{ $bucketCounts['Battery Service'] ?? 0 }}
                    </div>

                    <div class="mt-1 text-xs font-medium text-blue-100/70">
                        Battery check
                    </div>
                </div>

                <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-slate-400">
                        Tyres
                    </div>

                    <div class="mt-2 text-2xl font-extrabold text-white">
                        {{ $bucketCounts['Tyre Service'] ?? 0 }}
                    </div>

                    <div class="mt-1 text-xs font-medium text-slate-500">
                        Tyre reminder
                    </div>
                </div>

                <div class="rounded-2xl border border-blue-400/20 bg-blue-500/10 p-4">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-blue-300">
                        AC
                    </div>

                    <div class="mt-2 text-2xl font-extrabold text-white">
                        {{ $bucketCounts['AC Service'] ?? 0 }}
                    </div>

                    <div class="mt-1 text-xs font-medium text-blue-100/70">
                        AC follow-up
                    </div>
                </div>

                <div class="rounded-2xl border border-red-400/20 bg-red-500/10 p-4">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-red-300">
                        Brakes
                    </div>

                    <div class="mt-2 text-2xl font-extrabold text-white">
                        {{ $bucketCounts['Brake Service'] ?? 0 }}
                    </div>

                    <div class="mt-1 text-xs font-medium text-red-100/70">
                        Safety check
                    </div>
                </div>

                <div class="rounded-2xl border border-green-400/20 bg-green-500/10 p-4">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-green-300">
                        Wash
                    </div>

                    <div class="mt-2 text-2xl font-extrabold text-white">
                        {{ $bucketCounts['Car Wash / Detailing'] ?? 0 }}
                    </div>

                    <div class="mt-1 text-xs font-medium text-green-100/70">
                        Promo ready
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Info Note --}}
    <div class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-xl shadow-black/20">
        <div class="font-extrabold text-blue-300">
            Open jobs only
        </div>

        <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
            This page is for cars currently being worked on. Jobs should be closed only after invoice number and invoice amount are captured, so revenue can be used later for ROI reporting.
        </p>
    </div>

    {{-- Toolbar --}}
    <form method="GET" action="{{ route('admin.jobs.index') }}" class="sf-card">
        <div class="sf-card-body">
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">

                <div class="lg:col-span-7">
                    <label class="sf-label">
                        Search
                    </label>

                    <input type="text"
                           name="q"
                           value="{{ $q ?? '' }}"
                           placeholder="Search job code, client, service, description..."
                           class="sf-input" />
                </div>

                <div class="lg:col-span-3">
                    <label class="sf-label">
                        Status
                    </label>

                    @php
                        $statuses = [
                            '' => 'All Open Jobs',
                            'pending' => 'Pending',
                            'in_progress' => 'In Progress',
                        ];
                    @endphp

                    <select name="status" class="sf-select">
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ ($status ?? '') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2 lg:col-span-2">
                    <button class="sf-btn-primary w-full">
                        Apply
                    </button>

                    <a href="{{ route('admin.jobs.index') }}" class="sf-btn-secondary">
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
                        <th class="w-[18%]">Job</th>
                        <th class="w-[14%]">Client</th>
                        <th class="w-[14%]">Service Bucket</th>
                        <th class="w-[12%]">Current Stage</th>
                        <th class="w-[20%]">Customer Update Now</th>
                        <th class="w-[16%]">Closure / ROI Status</th>
                        <th class="w-[6%] text-right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($jobs as $job)

                        @php
                            $serviceSignal = $detectServiceSignal($job);

                            $customerUpdate = match($job->status) {
                                'pending' => 'Send start or inspection update once work begins.',
                                'in_progress' => 'Send progress update if customer needs visibility.',
                                default => 'Update customer when job changes.',
                            };
                        @endphp

                        <tr>

                            {{-- Job --}}
                            <td>
                                <div class="font-extrabold text-white">
                                    {{ $job->job_code ?? '—' }}
                                </div>

                                <div class="mt-1 max-w-[260px] text-xs font-medium text-slate-500">
                                    <span class="block truncate" title="{{ $job->description }}">
                                        {{ $job->description ?: 'No description added' }}
                                    </span>
                                </div>
                            </td>

                            {{-- Client --}}
                            <td>
                                <div class="font-bold text-slate-200">
                                    {{ $job->client?->name ?? 'N/A' }}
                                </div>
                            </td>

                            {{-- Service Bucket --}}
                            <td>
                                <span class="{{ $serviceBadge($serviceSignal) }}">
                                    {{ $serviceSignal }}
                                </span>
                            </td>

                            {{-- Current Stage --}}
                            <td>
                                <span class="{{ $statusBadge($job->status) }}">
                                    {{ ucwords(str_replace('_', ' ', $job->status)) }}
                                </span>
                            </td>

                            {{-- Customer Update Now --}}
                            <td>
                                <div class="font-medium leading-6 text-slate-300">
                                    {{ $customerUpdate }}
                                </div>
                            </td>

                            {{-- Closure / ROI Status --}}
                            <td>
                                <div class="font-extrabold text-orange-300">
                                    Invoice required to close
                                </div>

                                <div class="mt-1 text-xs font-medium text-slate-500">
                                    Capture invoice no. + amount for campaign ROI.
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="text-right">
                                <div class="flex justify-end gap-3 whitespace-nowrap">

                                    <a href="{{ route('admin.jobs.show', $job->id) }}" class="sf-link">
                                        View
                                    </a>

                                    <a href="{{ route('admin.jobs.edit', $job->id) }}" class="sf-link">
                                        Edit
                                    </a>

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="7">
                                <div class="sf-empty">
                                    <div class="text-lg font-extrabold text-white">
                                        No open jobs found
                                    </div>

                                    <p class="mt-2 text-sm font-medium text-slate-500">
                                        Open jobs will appear here when bookings are converted to jobs or when a new job is created.
                                    </p>

                                    <a href="{{ route('admin.jobs.create') }}" class="sf-btn-primary mt-4">
                                        + Create Job
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
        {{ $jobs->links() }}
    </div>

</div>
@endsection