@extends('layouts.app')

@section('title', 'Opportunities')

@section('content')
@php
    $q = $q ?? request('q', '');
    $stage = $stage ?? request('stage', '');
    $priority = $priority ?? request('priority', '');
    $bucket = $bucket ?? request('bucket', '');

    $opportunityCounts = array_merge([
        'open' => 0,
        'appointment' => 0,
        'missed_appointment' => 0,
        'won' => 0,
        'lost' => 0,
    ], $opportunityCounts ?? []);

    $bucketCounts = array_merge([
        'new' => 0,
        'attempting_contact' => 0,
        'manager_confirmation_pending' => 0,
        'appointment' => 0,
        'missed_appointment' => 0,
        'high_priority' => 0,
        'unassigned' => 0,
        'no_vehicle' => 0,
        'no_value' => 0,
    ], $bucketCounts ?? []);

    $stageBadge = function ($stage) {
        $stage = strtolower((string) $stage);

        return match ($stage) {
            'new' => 'sf-badge-blue',
            'attempting_contact' => 'sf-badge-yellow',
            'manager_confirmation_pending' => 'sf-badge-orange',
            'appointment' => 'sf-badge-blue',
            'closed_won', 'won' => 'sf-badge-green',
            'closed_lost', 'lost' => 'sf-badge-red',
            default => 'sf-badge-slate',
        };
    };

    $priorityBadge = function ($priority) {
        $priority = strtolower((string) $priority);

        return match ($priority) {
            'urgent' => 'sf-badge-red',
            'high' => 'sf-badge-orange',
            'medium' => 'sf-badge-yellow',
            'low' => 'sf-badge-slate',
            default => 'sf-badge-slate',
        };
    };

    $bucketCardClass = function ($key) use ($bucket) {
        return $bucket === $key
            ? 'border-orange-400/40 bg-orange-500/10 ring-1 ring-orange-400/30'
            : 'border-white/10 bg-slate-950/60 hover:border-orange-400/30 hover:bg-slate-900';
    };

    $stageLabel = function ($value) {
        return match ((string) $value) {
            'new' => 'New',
            'attempting_contact' => 'Attempting Contact',
            'manager_confirmation_pending' => 'Manager Confirmation Pending',
            'appointment' => 'Appointment Planned',
            'closed_won' => 'Booking Confirmed',
            'closed_lost' => 'Closed Lost',
            default => ucwords(str_replace('_', ' ', (string) $value)),
        };
    };

    $bucketCards = [
        [
            'key' => 'new',
            'title' => 'New',
            'count' => $bucketCounts['new'] ?? 0,
            'note' => 'Fresh opportunities',
            'emoji' => '🆕',
        ],
        [
            'key' => 'attempting_contact',
            'title' => 'Attempting Contact',
            'count' => $bucketCounts['attempting_contact'] ?? 0,
            'note' => 'Need follow-up',
            'emoji' => '📞',
        ],
        [
            'key' => 'manager_confirmation_pending',
            'title' => 'Manager Pending',
            'count' => $bucketCounts['manager_confirmation_pending'] ?? 0,
            'note' => 'Manager action needed',
            'emoji' => '👨‍💼',
        ],
        [
            'key' => 'appointment',
            'title' => 'Appointment',
            'count' => $bucketCounts['appointment'] ?? 0,
            'note' => 'Appointment planned',
            'emoji' => '📅',
        ],
        [
            'key' => 'missed_appointment',
            'title' => 'Missed',
            'count' => $bucketCounts['missed_appointment'] ?? 0,
            'note' => 'Past appointment',
            'emoji' => '⚠️',
        ],
        [
            'key' => 'high_priority',
            'title' => 'High Priority',
            'count' => $bucketCounts['high_priority'] ?? 0,
            'note' => 'High / urgent',
            'emoji' => '🚨',
        ],
        [
            'key' => 'unassigned',
            'title' => 'Unassigned',
            'count' => $bucketCounts['unassigned'] ?? 0,
            'note' => 'No owner',
            'emoji' => '👤',
        ],
        [
            'key' => 'no_vehicle',
            'title' => 'No Vehicle',
            'count' => $bucketCounts['no_vehicle'] ?? 0,
            'note' => 'Missing vehicle',
            'emoji' => '🚗',
        ],
        [
            'key' => 'no_value',
            'title' => 'No Value',
            'count' => $bucketCounts['no_value'] ?? 0,
            'note' => 'Missing value',
            'emoji' => '💰',
        ],
    ];

    $clearUrl = route('admin.opportunities.index');
@endphp

<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Sales Pipeline
            </div>

            <h1 class="sf-page-title mt-3">
                Opportunities
            </h1>

            <p class="sf-page-subtitle">
                Track opportunities from lead qualification to appointment, booking, job, and invoice.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(Route::has('admin.opportunities.create'))
                <a href="{{ route('admin.opportunities.create') }}" class="sf-btn-primary">
                    + Create Opportunity
                </a>
            @endif
        </div>
    </div>

    {{-- Pipeline Guide --}}
    <div class="sf-soft-panel">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-extrabold text-white">
                    Pipeline Guide
                </h2>

                <p class="mt-2 text-sm font-medium leading-6 text-slate-300">
                    New → Attempting Contact → Manager Confirmation Pending → Appointment Planned → Booking Confirmed → Booking → Job → Invoice
                </p>
            </div>

            <div class="rounded-2xl border border-orange-400/20 bg-orange-500/10 px-4 py-3 text-sm font-bold text-orange-200">
                Appointment Planned ≠ Booking. Booking Confirmed = customer has agreed to proceed.
            </div>
        </div>
    </div>

    {{-- Alerts --}}
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

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <a href="{{ route('admin.opportunities.index') }}"
           class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:border-blue-400/40 hover:bg-blue-500/20">
            <div class="text-sm font-bold text-blue-300">
                Open Opportunities
            </div>

            <div class="mt-2 text-3xl font-extrabold text-white">
                {{ $opportunityCounts['open'] ?? 0 }}
            </div>

            <div class="mt-1 text-xs font-medium text-blue-100/70">
                Active pipeline
            </div>
        </a>

        <a href="{{ route('admin.opportunities.index', ['bucket' => 'appointment']) }}"
           class="rounded-3xl border border-orange-400/20 bg-orange-500/10 p-5 shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:border-orange-400/40 hover:bg-orange-500/20">
            <div class="text-sm font-bold text-orange-300">
                Appointment Planned
            </div>

            <div class="mt-2 text-3xl font-extrabold text-white">
                {{ $opportunityCounts['appointment'] ?? 0 }}
            </div>

            <div class="mt-1 text-xs font-medium text-orange-100/70">
                Ready to confirm
            </div>
        </a>

        <a href="{{ route('admin.opportunities.index', ['bucket' => 'missed_appointment']) }}"
           class="rounded-3xl border border-red-400/20 bg-red-500/10 p-5 shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:border-red-400/40 hover:bg-red-500/20">
            <div class="text-sm font-bold text-red-300">
                Missed Appointments
            </div>

            <div class="mt-2 text-3xl font-extrabold text-white">
                {{ $opportunityCounts['missed_appointment'] ?? 0 }}
            </div>

            <div class="mt-1 text-xs font-medium text-red-100/70">
                Past appointment date
            </div>
        </a>

        <a href="{{ route('admin.opportunities.index', ['stage' => 'closed_won']) }}"
           class="rounded-3xl border border-green-400/20 bg-green-500/10 p-5 shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:border-green-400/40 hover:bg-green-500/20">
            <div class="text-sm font-bold text-green-300">
                Booking Confirmed
            </div>

            <div class="mt-2 text-3xl font-extrabold text-white">
                {{ $opportunityCounts['won'] ?? 0 }}
            </div>

            <div class="mt-1 text-xs font-medium text-green-100/70">
                Converted to booking
            </div>
        </a>

        <a href="{{ route('admin.opportunities.index', ['stage' => 'closed_lost']) }}"
           class="rounded-3xl border border-red-400/20 bg-red-500/10 p-5 shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:border-red-400/40 hover:bg-red-500/20">
            <div class="text-sm font-bold text-red-300">
                Closed Lost
            </div>

            <div class="mt-2 text-3xl font-extrabold text-white">
                {{ $opportunityCounts['lost'] ?? 0 }}
            </div>

            <div class="mt-1 text-xs font-medium text-red-100/70">
                Lost opportunities
            </div>
        </a>
    </div>

    {{-- Opportunity Buckets --}}
    <div class="sf-card">
        <div class="sf-card-header flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="sf-section-title">
                    Opportunity Buckets
                </h2>

                <p class="sf-section-subtitle">
                    Quick filters for active pipeline stages, priority, and missing data.
                </p>
            </div>

            @if($bucket || $stage || $priority || $q)
                <a href="{{ $clearUrl }}" class="sf-link shrink-0">
                    Clear filters
                </a>
            @endif
        </div>

        <div class="sf-card-body">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                @foreach($bucketCards as $card)
                    <a href="{{ route('admin.opportunities.index', ['bucket' => $card['key']]) }}"
                       class="rounded-2xl border p-4 shadow-xl shadow-black/10 transition {{ $bucketCardClass($card['key']) }}">
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-xl">
                                {{ $card['emoji'] }}
                            </div>

                            <div class="text-2xl font-extrabold text-white">
                                {{ $card['count'] }}
                            </div>
                        </div>

                        <div class="mt-3 text-sm font-extrabold text-white">
                            {{ $card['title'] }}
                        </div>

                        <div class="mt-1 text-xs font-medium text-slate-500">
                            {{ $card['note'] }}
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.opportunities.index') }}" class="sf-card">
        <div class="sf-card-body">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <div class="md:col-span-1">
                    <label class="sf-label">
                        Search
                    </label>

                    <input type="text"
                           name="q"
                           value="{{ $q }}"
                           placeholder="Search title, client, vehicle..."
                           class="sf-input">
                </div>

                <div>
                    <label class="sf-label">
                        Stage
                    </label>

                    <select name="stage" class="sf-select">
                        <option value="">All stages</option>
                        @foreach(['new', 'attempting_contact', 'manager_confirmation_pending', 'appointment', 'closed_won', 'closed_lost'] as $stageOption)
                            <option value="{{ $stageOption }}" @selected($stage === $stageOption)>
                                {{ $stageLabel($stageOption) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="sf-label">
                        Priority
                    </label>

                    <select name="priority" class="sf-select">
                        <option value="">All priorities</option>
                        @foreach(['urgent', 'high', 'medium', 'low'] as $priorityOption)
                            <option value="{{ $priorityOption }}" @selected($priority === $priorityOption)>
                                {{ ucfirst($priorityOption) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    @if($bucket)
                        <input type="hidden" name="bucket" value="{{ $bucket }}">
                    @endif

                    <button type="submit" class="sf-btn-primary w-full">
                        Filter
                    </button>

                    @if($bucket || $stage || $priority || $q)
                        <a href="{{ $clearUrl }}" class="sf-btn-secondary">
                            Reset
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    {{-- Opportunities Table --}}
    <div class="sf-table-wrap">
        <div class="sf-table-scroll">
            <table class="sf-table">
                <thead>
                    <tr>
                        <th class="w-[26%]">Opportunity</th>
                        <th class="w-[20%]">Client / Vehicle</th>
                        <th class="w-[14%]">Stage</th>
                        <th class="w-[12%]">Priority</th>
                        <th class="w-[12%]">Value</th>
                        <th class="w-[10%]">Date</th>
                        <th class="w-[6%] text-right">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($opportunities as $opportunity)
                        @php
                            $vehicleLabel = trim(
                                ($opportunity->vehicleMake?->name ?? $opportunity->other_make ?? '') . ' ' .
                                ($opportunity->vehicleModel?->name ?? $opportunity->other_model ?? '')
                            );

                            $value = $opportunity->value
                                ?? $opportunity->estimated_value
                                ?? $opportunity->amount
                                ?? 0;
                        @endphp

                        <tr>
                            {{-- Opportunity --}}
                            <td>
                                <div class="font-extrabold text-white">
                                    {{ $opportunity->title ?? 'Untitled Opportunity' }}
                                </div>

                                <div class="mt-1 text-xs font-medium text-slate-500">
                                    #{{ $opportunity->id }}
                                    @if($opportunity->source)
                                        · {{ $opportunity->source }}
                                    @endif
                                </div>
                            </td>

                            {{-- Client / Vehicle --}}
                            <td>
                                <div class="font-bold text-slate-200">
                                    {{ $opportunity->client?->name ?? 'No client' }}
                                </div>

                                <div class="mt-1 text-xs font-medium text-slate-500">
                                    🚗 {{ $vehicleLabel !== '' ? $vehicleLabel : 'No vehicle' }}
                                </div>
                            </td>

                            {{-- Stage --}}
                            <td>
                                <span class="{{ $stageBadge($opportunity->stage) }}">
                                    {{ $stageLabel($opportunity->stage) }}
                                </span>
                            </td>

                            {{-- Priority --}}
                            <td>
                                <span class="{{ $priorityBadge($opportunity->priority ?? 'medium') }}">
                                    {{ ucfirst($opportunity->priority ?? 'Medium') }}
                                </span>
                            </td>

                            {{-- Value --}}
                            <td>
                                <div class="font-extrabold text-orange-300">
                                    AED {{ number_format((float) $value, 2) }}
                                </div>
                            </td>

                            {{-- Date --}}
                            <td>
                                <div class="font-bold text-slate-300">
                                    {{ optional($opportunity->expected_close_date)->format('d M Y') ?? optional($opportunity->created_at)->format('d M Y') ?? '—' }}
                                </div>

                                <div class="text-xs text-slate-500">
                                    {{ optional($opportunity->created_at)->format('h:i A') ?? '' }}
                                </div>
                            </td>

                            {{-- Action --}}
                            <td class="text-right">
                                @if(Route::has('admin.opportunities.show'))
                                    <a href="{{ route('admin.opportunities.show', $opportunity) }}" class="sf-link">
                                        View
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="sf-empty">
                                    No opportunities found.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if(method_exists($opportunities, 'links'))
        <div class="text-slate-300">
            {{ $opportunities->links() }}
        </div>
    @endif

</div>
@endsection