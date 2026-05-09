@extends('layouts.app')

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
            'new' => 'bg-blue-100 text-blue-800',
            'attempting_contact' => 'bg-yellow-100 text-yellow-800',
            'manager_confirmation_pending' => 'bg-orange-100 text-orange-800',
            'appointment' => 'bg-indigo-100 text-indigo-800',
            'closed_won' => 'bg-green-100 text-green-800',
            'closed_lost' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-700',
        };
    };

    $priorityBadge = function ($priority) {
        $priority = strtolower((string) $priority);

        return match ($priority) {
            'urgent' => 'bg-red-100 text-red-800',
            'high' => 'bg-orange-100 text-orange-800',
            'medium' => 'bg-yellow-100 text-yellow-800',
            'low' => 'bg-gray-100 text-gray-700',
            default => 'bg-gray-100 text-gray-700',
        };
    };

    $bucketCardClass = function ($key) use ($bucket) {
        return $bucket === $key
            ? 'border-blue-200 bg-blue-50'
            : 'border-gray-100 bg-white hover:bg-gray-50';
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

    $clearUrl = route('admin.opportunities.index');
@endphp

<div class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-6 py-6 space-y-6">

        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">
                    Opportunities
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Track opportunities from lead qualification to appointment, booking, job, and invoice.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.opportunities.create') }}"
                   class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                    + Create Opportunity
                </a>
            </div>
        </div>

        {{-- Pipeline Guide --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold text-gray-900">
                        Pipeline Guide
                    </div>

                    <div class="text-sm text-gray-500 mt-1">
                        New → Attempting Contact → Manager Confirmation Pending → Appointment Planned → Booking Confirmed → Booking → Job → Invoice
                    </div>
                </div>

                <div class="text-xs text-gray-500 bg-gray-50 border rounded-lg px-3 py-2">
                    Appointment Planned ≠ Booking. Booking Confirmed = customer has agreed to proceed.
                </div>
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-100 text-green-800 px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="rounded-lg bg-yellow-50 border border-yellow-100 text-yellow-800 px-4 py-3 text-sm">
                {{ session('warning') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-lg bg-red-50 border border-red-100 text-red-800 px-4 py-3 text-sm">
                {{ session('error') }}
            </div>
        @endif

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
            <a href="{{ route('admin.opportunities.index') }}"
               class="rounded-xl border border-blue-100 bg-blue-50 p-5 block hover:bg-blue-100 transition">
                <div class="text-sm font-medium text-blue-700">
                    Open Opportunities
                </div>
                <div class="text-3xl font-bold text-blue-900 mt-2">
                    {{ $opportunityCounts['open'] ?? 0 }}
                </div>
                <div class="text-xs text-blue-700 mt-1">
                    Active pipeline
                </div>
            </a>

            <a href="{{ route('admin.opportunities.index', ['bucket' => 'appointment']) }}"
               class="rounded-xl border p-5 block transition {{ $bucketCardClass('appointment') }}">
                <div class="text-sm font-medium text-gray-500">
                    Appointment Planned
                </div>
                <div class="text-3xl font-bold text-gray-900 mt-2">
                    {{ $opportunityCounts['appointment'] ?? 0 }}
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    Ready to confirm
                </div>
            </a>

            <a href="{{ route('admin.opportunities.index', ['bucket' => 'missed_appointment']) }}"
               class="rounded-xl border border-red-100 bg-red-50 p-5 block hover:bg-red-100 transition">
                <div class="text-sm font-medium text-red-700">
                    Missed Appointments
                </div>
                <div class="text-3xl font-bold text-red-900 mt-2">
                    {{ $opportunityCounts['missed_appointment'] ?? 0 }}
                </div>
                <div class="text-xs text-red-700 mt-1">
                    Past appointment date
                </div>
            </a>

            <a href="{{ route('admin.opportunities.index', ['stage' => 'closed_won']) }}"
               class="rounded-xl border border-green-100 bg-green-50 p-5 block hover:bg-green-100 transition">
                <div class="text-sm font-medium text-green-700">
                    Booking Confirmed
                </div>
                <div class="text-3xl font-bold text-green-900 mt-2">
                    {{ $opportunityCounts['won'] ?? 0 }}
                </div>
                <div class="text-xs text-green-700 mt-1">
                    Converted to booking
                </div>
            </a>

            <a href="{{ route('admin.opportunities.index', ['stage' => 'closed_lost']) }}"
               class="rounded-xl border border-red-100 bg-red-50 p-5 block hover:bg-red-100 transition">
                <div class="text-sm font-medium text-red-700">
                    Closed Lost
                </div>
                <div class="text-3xl font-bold text-red-900 mt-2">
                    {{ $opportunityCounts['lost'] ?? 0 }}
                </div>
                <div class="text-xs text-red-700 mt-1">
                    Lost opportunities
                </div>
            </a>
        </div>

        {{-- Opportunity Buckets --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">
                        Opportunity Buckets
                    </h2>
                    <p class="text-sm text-gray-500">
                        Quick filters for active pipeline stages, priority, and missing data.
                    </p>
                </div>

                @if($bucket || $stage || $priority || $q)
                    <a href="{{ $clearUrl }}"
                       class="text-sm text-blue-600 hover:underline shrink-0">
                        Clear filters
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                <a href="{{ route('admin.opportunities.index', ['bucket' => 'new']) }}"
                   class="rounded-xl border p-4 transition {{ $bucketCardClass('new') }}">
                    <div class="flex items-center justify-between">
                        <div class="text-xl">🆕</div>
                        <div class="text-xl font-bold">{{ $bucketCounts['new'] ?? 0 }}</div>
                    </div>
                    <div class="text-sm font-semibold mt-2">New</div>
                    <div class="text-xs text-gray-500">Fresh opportunities</div>
                </a>

                <a href="{{ route('admin.opportunities.index', ['bucket' => 'attempting_contact']) }}"
                   class="rounded-xl border p-4 transition {{ $bucketCardClass('attempting_contact') }}">
                    <div class="flex items-center justify-between">
                        <div class="text-xl">📞</div>
                        <div class="text-xl font-bold">{{ $bucketCounts['attempting_contact'] ?? 0 }}</div>
                    </div>
                    <div class="text-sm font-semibold mt-2">Attempting Contact</div>
                    <div class="text-xs text-gray-500">Follow-up needed</div>
                </a>

                <a href="{{ route('admin.opportunities.index', ['bucket' => 'manager_confirmation_pending']) }}"
                   class="rounded-xl border p-4 transition {{ $bucketCardClass('manager_confirmation_pending') }}">
                    <div class="flex items-center justify-between">
                        <div class="text-xl">👤</div>
                        <div class="text-xl font-bold">{{ $bucketCounts['manager_confirmation_pending'] ?? 0 }}</div>
                    </div>
                    <div class="text-sm font-semibold mt-2">Manager Confirmation</div>
                    <div class="text-xs text-gray-500">Needs manager action</div>
                </a>

                <a href="{{ route('admin.opportunities.index', ['bucket' => 'appointment']) }}"
                   class="rounded-xl border p-4 transition {{ $bucketCardClass('appointment') }}">
                    <div class="flex items-center justify-between">
                        <div class="text-xl">📅</div>
                        <div class="text-xl font-bold">{{ $bucketCounts['appointment'] ?? 0 }}</div>
                    </div>
                    <div class="text-sm font-semibold mt-2">Appointment Planned</div>
                    <div class="text-xs text-gray-500">Ready to confirm</div>
                </a>

                <a href="{{ route('admin.opportunities.index', ['bucket' => 'missed_appointment']) }}"
                   class="rounded-xl border p-4 transition {{ $bucketCardClass('missed_appointment') }}">
                    <div class="flex items-center justify-between">
                        <div class="text-xl">⏰</div>
                        <div class="text-xl font-bold">{{ $bucketCounts['missed_appointment'] ?? 0 }}</div>
                    </div>
                    <div class="text-sm font-semibold mt-2">Missed Appointment</div>
                    <div class="text-xs text-gray-500">Past appointment date</div>
                </a>

                <a href="{{ route('admin.opportunities.index', ['bucket' => 'high_priority']) }}"
                   class="rounded-xl border p-4 transition {{ $bucketCardClass('high_priority') }}">
                    <div class="flex items-center justify-between">
                        <div class="text-xl">🔥</div>
                        <div class="text-xl font-bold">{{ $bucketCounts['high_priority'] ?? 0 }}</div>
                    </div>
                    <div class="text-sm font-semibold mt-2">High Priority</div>
                    <div class="text-xs text-gray-500">High / urgent</div>
                </a>

                <a href="{{ route('admin.opportunities.index', ['bucket' => 'unassigned']) }}"
                   class="rounded-xl border p-4 transition {{ $bucketCardClass('unassigned') }}">
                    <div class="flex items-center justify-between">
                        <div class="text-xl">👥</div>
                        <div class="text-xl font-bold">{{ $bucketCounts['unassigned'] ?? 0 }}</div>
                    </div>
                    <div class="text-sm font-semibold mt-2">Unassigned</div>
                    <div class="text-xs text-gray-500">No owner assigned</div>
                </a>

                <a href="{{ route('admin.opportunities.index', ['bucket' => 'no_vehicle']) }}"
                   class="rounded-xl border p-4 transition {{ $bucketCardClass('no_vehicle') }}">
                    <div class="flex items-center justify-between">
                        <div class="text-xl">🚗</div>
                        <div class="text-xl font-bold">{{ $bucketCounts['no_vehicle'] ?? 0 }}</div>
                    </div>
                    <div class="text-sm font-semibold mt-2">No Vehicle</div>
                    <div class="text-xs text-gray-500">Missing vehicle link</div>
                </a>

                <a href="{{ route('admin.opportunities.index', ['bucket' => 'no_value']) }}"
                   class="rounded-xl border p-4 transition {{ $bucketCardClass('no_value') }}">
                    <div class="flex items-center justify-between">
                        <div class="text-xl">💰</div>
                        <div class="text-xl font-bold">{{ $bucketCounts['no_value'] ?? 0 }}</div>
                    </div>
                    <div class="text-sm font-semibold mt-2">No Value</div>
                    <div class="text-xs text-gray-500">AED value missing</div>
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.opportunities.index') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                <div class="md:col-span-5">
                    <label class="block text-xs font-medium text-gray-500 mb-1">
                        Search
                    </label>
                    <input
                        name="q"
                        value="{{ $q }}"
                        placeholder="Search title, client, lead, phone, vehicle, service..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    />
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-500 mb-1">
                        Stage
                    </label>
                    <select name="stage"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                        <option value="">All stages</option>
                        @foreach(($stages ?? []) as $stageOption)
                            @continue($stageOption === 'offer')
                            <option value="{{ $stageOption }}" @selected($stage === $stageOption)>
                                {{ $stageLabel($stageOption) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-500 mb-1">
                        Priority
                    </label>
                    <select name="priority"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                        <option value="">All</option>
                        <option value="urgent" @selected($priority === 'urgent')>Urgent</option>
                        <option value="high" @selected($priority === 'high')>High</option>
                        <option value="medium" @selected($priority === 'medium')>Medium</option>
                        <option value="low" @selected($priority === 'low')>Low</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <button type="submit"
                            class="w-full px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">
                        Search
                    </button>
                </div>

                @if($bucket)
                    <input type="hidden" name="bucket" value="{{ $bucket }}">
                @endif
            </div>
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Opportunity</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Client / Lead</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Vehicle</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Stage</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Priority</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Value</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Next Action</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Assigned</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Created</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    @forelse($opportunities as $opportunity)
                        @php
                            $vehicleLabel = $opportunity->vehicle_label
                                ?? trim(($opportunity->vehicleMake?->name ?? '') . ' ' . ($opportunity->vehicleModel?->name ?? ''));

                            $vehicleLabel = trim($vehicleLabel);

                            $nextAction = match ((string) $opportunity->stage) {
                                'new' => 'Contact customer',
                                'attempting_contact' => 'Follow up',
                                'manager_confirmation_pending' => 'Manager confirmation',
                                'appointment' => 'Confirm booking',
                                'closed_won' => 'Check booking/job',
                                'closed_lost' => 'No action',
                                default => 'Review',
                            };
                        @endphp

                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.opportunities.show', $opportunity->id) }}"
                                   class="font-medium text-blue-600 hover:underline">
                                    {{ $opportunity->title ?? 'Untitled Opportunity' }}
                                </a>

                                @if($opportunity->service_type)
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ $opportunity->service_type }}
                                    </div>
                                @endif

                                @if($opportunity->stage === 'closed_lost' && $opportunity->close_reason)
                                    <div class="text-xs text-red-600 mt-1">
                                        Lost reason: {{ $opportunity->close_reason }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">
                                    {{ $opportunity->client?->name ?? '—' }}
                                </div>

                                <div class="text-xs text-gray-500">
                                    Lead: {{ $opportunity->lead?->name ?? '—' }}
                                </div>

                                @if($opportunity->client?->phone)
                                    <div class="text-xs text-gray-400">
                                        {{ $opportunity->client->phone }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-gray-700">
                                {{ $vehicleLabel !== '' ? $vehicleLabel : '—' }}
                            </td>

                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $stageBadge($opportunity->stage) }}">
                                    {{ $stageLabel($opportunity->stage) }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $priorityBadge($opportunity->priority) }}">
                                    {{ ucfirst($opportunity->priority ?? 'medium') }}
                                </span>
                            </td>

                            <td class="px-4 py-3 font-medium text-gray-900">
                                AED {{ number_format((float) ($opportunity->value ?? 0), 2) }}
                            </td>

                            <td class="px-4 py-3">
                                <span class="text-xs text-gray-700">
                                    {{ $nextAction }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-gray-700">
                                {{ $opportunity->assignee?->name ?? 'Unassigned' }}
                            </td>

                            <td class="px-4 py-3 text-gray-500">
                                {{ optional($opportunity->created_at)->format('d M Y') ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.opportunities.show', $opportunity->id) }}"
                                   class="text-blue-600 hover:underline text-sm">
                                   View
                                </a>

                                <span class="text-gray-300 mx-1">|</span>

                                <a href="{{ route('admin.opportunities.edit', $opportunity->id) }}"
                                   class="text-gray-700 hover:underline text-sm">
                                   Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-10 text-center text-gray-400">
                                No opportunities found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div>
            {{ $opportunities->links() }}
        </div>
    </div>
</div>
@endsection