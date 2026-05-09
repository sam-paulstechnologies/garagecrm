{{-- resources/views/admin/opportunities/show.blade.php --}}
@extends('layouts.app')

@section('content')
@php
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

    $vehicleLabel = $opportunity->vehicle_label
        ?? trim(($opportunity->vehicleMake?->name ?? $opportunity->other_make ?? '') . ' ' . ($opportunity->vehicleModel?->name ?? $opportunity->other_model ?? ''));

    $vehicleLabel = trim($vehicleLabel);

    $services = collect(explode(',', (string) $opportunity->service_type))
        ->map(fn ($service) => trim($service))
        ->filter()
        ->values();

    $nextAction = match ((string) $opportunity->stage) {
        'new' => 'Contact customer',
        'attempting_contact' => 'Follow up with customer',
        'manager_confirmation_pending' => 'Manager confirmation required',
        'appointment' => 'Confirm booking',
        'closed_won' => 'Check booking / job',
        'closed_lost' => 'No action required',
        default => 'Review opportunity',
    };

    $timelineItems = [
        ['label' => 'New', 'stage' => 'new'],
        ['label' => 'Attempting Contact', 'stage' => 'attempting_contact'],
        ['label' => 'Manager Confirmation', 'stage' => 'manager_confirmation_pending'],
        ['label' => 'Appointment Planned', 'stage' => 'appointment'],
        ['label' => 'Booking Confirmed', 'stage' => 'closed_won'],
    ];

    $currentStage = (string) $opportunity->stage;
@endphp

<div class="bg-gray-50 min-h-screen">
    <div class="max-w-6xl mx-auto px-6 py-6 space-y-6">

        {{-- Back --}}
        <div>
            <a href="{{ route('admin.opportunities.index') }}"
               class="text-sm text-blue-600 hover:underline">
                ← Back to Opportunities
            </a>
        </div>

        {{-- Header --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold text-gray-900">
                            {{ $opportunity->title ?? 'Untitled Opportunity' }}
                        </h1>

                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $stageBadge($opportunity->stage) }}">
                            {{ $stageLabel($opportunity->stage) }}
                        </span>

                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $priorityBadge($opportunity->priority) }}">
                            {{ ucfirst($opportunity->priority ?? 'Medium') }}
                        </span>
                    </div>

                    <div class="flex flex-wrap gap-3 text-sm text-gray-500 mt-2">
                        <span>👤 {{ $opportunity->client?->name ?? 'No client' }}</span>
                        <span>🚗 {{ $vehicleLabel !== '' ? $vehicleLabel : 'No vehicle' }}</span>
                        <span>💰 AED {{ number_format((float) ($opportunity->value ?? 0), 2) }}</span>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.opportunities.edit', $opportunity) }}"
                       class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                        Edit Opportunity
                    </a>

                    @if($opportunity->client_id && Route::has('admin.clients.show'))
                        <a href="{{ route('admin.clients.show', $opportunity->client_id) }}"
                           class="inline-flex items-center px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                            View Client
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Pipeline Guide --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Pipeline Status</h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Appointment Planned means timing is being discussed. Booking Confirmed means the customer agreed to proceed.
                    </p>
                </div>

                <div class="text-sm font-medium text-gray-700">
                    Next Action: <span class="text-blue-700">{{ $nextAction }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                @foreach($timelineItems as $item)
                    @php
                        $active = $currentStage === $item['stage'];
                        $done = array_search($currentStage, array_column($timelineItems, 'stage'), true) >= array_search($item['stage'], array_column($timelineItems, 'stage'), true);
                    @endphp

                    <div class="rounded-lg border px-3 py-3 text-sm
                        {{ $active ? 'border-blue-200 bg-blue-50 text-blue-900' : ($done ? 'border-green-100 bg-green-50 text-green-800' : 'border-gray-100 bg-gray-50 text-gray-500') }}">
                        <div class="font-semibold">
                            {{ $item['label'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Main Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Left Column --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Opportunity Summary --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        Opportunity Summary
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="rounded-lg bg-gray-50 border border-gray-100 p-4">
                            <div class="text-xs text-gray-500 mb-1">Opportunity ID</div>
                            <div class="font-semibold text-gray-900">#{{ $opportunity->id }}</div>
                        </div>

                        <div class="rounded-lg bg-gray-50 border border-gray-100 p-4">
                            <div class="text-xs text-gray-500 mb-1">Source</div>
                            <div class="font-semibold text-gray-900">{{ $opportunity->source ?? '—' }}</div>
                        </div>

                        <div class="rounded-lg bg-gray-50 border border-gray-100 p-4">
                            <div class="text-xs text-gray-500 mb-1">Estimated Value</div>
                            <div class="font-semibold text-gray-900">
                                AED {{ number_format((float) ($opportunity->value ?? 0), 2) }}
                            </div>
                        </div>

                        <div class="rounded-lg bg-gray-50 border border-gray-100 p-4">
                            <div class="text-xs text-gray-500 mb-1">Appointment / Expected Close Date</div>
                            <div class="font-semibold text-gray-900">
                                {{ optional($opportunity->expected_close_date)->format('d M Y') ?? '—' }}
                            </div>
                        </div>

                        <div class="rounded-lg bg-gray-50 border border-gray-100 p-4">
                            <div class="text-xs text-gray-500 mb-1">Assigned To</div>
                            <div class="font-semibold text-gray-900">
                                {{ $opportunity->assignee?->name ?? 'Unassigned' }}
                            </div>
                        </div>

                        <div class="rounded-lg bg-gray-50 border border-gray-100 p-4">
                            <div class="text-xs text-gray-500 mb-1">Converted?</div>
                            <div class="font-semibold text-gray-900">
                                {{ $opportunity->is_converted ? 'Yes' : 'No' }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Services --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        Service Type(s)
                    </h2>

                    @if($services->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach($services as $service)
                                <span class="inline-flex px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-sm font-medium">
                                    {{ $service }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No service type added yet.</p>
                    @endif
                </div>

                {{-- Notes --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        Notes
                    </h2>

                    <div class="text-sm text-gray-700 whitespace-pre-line">
                        {{ $opportunity->notes ?: 'No notes added.' }}
                    </div>
                </div>
            </div>

            {{-- Right Column --}}
            <div class="space-y-6">

                {{-- Customer --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        Customer
                    </h2>

                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="text-xs text-gray-500">Client</div>
                            <div class="font-medium text-gray-900">
                                {{ $opportunity->client?->name ?? 'N/A' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-gray-500">Lead</div>
                            <div class="font-medium text-gray-900">
                                {{ $opportunity->lead?->name ?? '—' }}
                            </div>
                        </div>

                        @if($opportunity->client?->phone)
                            <div>
                                <div class="text-xs text-gray-500">Phone</div>
                                <div class="font-medium text-gray-900">
                                    {{ $opportunity->client->phone }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Vehicle --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        Vehicle
                    </h2>

                    <div class="text-sm text-gray-700">
                        {{ $vehicleLabel !== '' ? $vehicleLabel : 'No vehicle linked.' }}
                    </div>
                </div>

                {{-- Meta --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        Record Info
                    </h2>

                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="text-xs text-gray-500">Created At</div>
                            <div class="font-medium text-gray-900">
                                {{ $opportunity->created_at?->format('d M Y, h:i A') ?? '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-gray-500">Last Updated</div>
                            <div class="font-medium text-gray-900">
                                {{ $opportunity->updated_at?->format('d M Y, h:i A') ?? '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-gray-500">Company ID</div>
                            <div class="font-medium text-gray-900">
                                {{ $opportunity->company_id }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection