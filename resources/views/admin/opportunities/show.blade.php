{{-- resources/views/admin/opportunities/show.blade.php --}}
@extends('layouts.app')

@section('title', $opportunity->title ?? 'Opportunity Details')

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

    $vehicleLabel = trim(
        ($opportunity->vehicleMake?->name ?? $opportunity->other_make ?? '') . ' ' .
        ($opportunity->vehicleModel?->name ?? $opportunity->other_model ?? '')
    );

    $services = collect();

    if (!empty($opportunity->service_type)) {
        $services = is_array($opportunity->service_type)
            ? collect($opportunity->service_type)
            : collect(explode(',', (string) $opportunity->service_type));
    } elseif (!empty($opportunity->services)) {
        $services = is_array($opportunity->services)
            ? collect($opportunity->services)
            : collect(explode(',', (string) $opportunity->services));
    }

    $services = $services
        ->map(fn ($item) => trim((string) $item))
        ->filter()
        ->values();

    $nextAction = match ((string) $opportunity->stage) {
        'new' => 'Assign owner and attempt contact',
        'attempting_contact' => 'Continue follow-up',
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
    $currentIndex = array_search($currentStage, array_column($timelineItems, 'stage'), true);
    $currentIndex = $currentIndex === false ? 0 : $currentIndex;

    $value = $opportunity->value
        ?? $opportunity->estimated_value
        ?? $opportunity->amount
        ?? 0;
@endphp

<div class="sf-page space-y-6">

    {{-- Back --}}
    <div>
        <a href="{{ route('admin.opportunities.index') }}" class="sf-link">
            ← Back to Opportunities
        </a>
    </div>

    {{-- Header --}}
    <div class="sf-hero-panel">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="sf-kicker">
                        Opportunity Profile
                    </div>

                    <span class="{{ $stageBadge($opportunity->stage) }}">
                        {{ $stageLabel($opportunity->stage) }}
                    </span>

                    <span class="{{ $priorityBadge($opportunity->priority ?? 'medium') }}">
                        {{ ucfirst($opportunity->priority ?? 'Medium') }}
                    </span>
                </div>

                <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-white">
                    {{ $opportunity->title ?? 'Untitled Opportunity' }}
                </h1>

                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-sm font-medium text-slate-400">
                    <span>👤 {{ $opportunity->client?->name ?? 'No client' }}</span>
                    <span>🚗 {{ $vehicleLabel !== '' ? $vehicleLabel : 'No vehicle' }}</span>
                    <span>💰 AED {{ number_format((float) $value, 2) }}</span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                @if(Route::has('admin.opportunities.edit'))
                    <a href="{{ route('admin.opportunities.edit', $opportunity) }}" class="sf-btn-primary">
                        Edit Opportunity
                    </a>
                @endif

                @if($opportunity->client_id && Route::has('admin.clients.show'))
                    <a href="{{ route('admin.clients.show', $opportunity->client_id) }}" class="sf-btn-secondary">
                        View Client
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Pipeline Guide --}}
    <div class="sf-card">
        <div class="sf-card-header flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="sf-section-title">
                    Pipeline Status
                </h2>

                <p class="sf-section-subtitle">
                    Appointment Planned means timing is being discussed. Booking Confirmed means the customer agreed to proceed.
                </p>
            </div>

            <div class="rounded-2xl border border-orange-400/20 bg-orange-500/10 px-4 py-3 text-sm font-bold text-orange-200">
                Next Action: <span class="text-white">{{ $nextAction }}</span>
            </div>
        </div>

        <div class="sf-card-body">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
                @foreach($timelineItems as $index => $item)
                    @php
                        $active = $currentStage === $item['stage'];
                        $done = $currentIndex >= $index;
                    @endphp

                    <div class="rounded-2xl border px-4 py-4 text-sm
                        {{ $active ? 'border-orange-400/40 bg-orange-500/10 text-orange-200 ring-1 ring-orange-400/20' : ($done ? 'border-green-400/20 bg-green-500/10 text-green-200' : 'border-white/10 bg-slate-950/60 text-slate-500') }}">
                        <div class="font-extrabold">
                            {{ $item['label'] }}
                        </div>

                        <div class="mt-1 text-xs font-medium opacity-80">
                            {{ $active ? 'Current' : ($done ? 'Completed' : 'Pending') }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Main Grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Left Column --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- Opportunity Summary --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Opportunity Summary
                    </h2>

                    <p class="sf-section-subtitle">
                        Core commercial and operational details.
                    </p>
                </div>

                <div class="sf-card-body">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Opportunity ID
                            </div>

                            <div class="mt-1 font-extrabold text-white">
                                #{{ $opportunity->id }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Source
                            </div>

                            <div class="mt-1 font-extrabold text-white">
                                {{ $opportunity->source ?? '—' }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-orange-400/20 bg-orange-500/10 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-orange-300">
                                Estimated Value
                            </div>

                            <div class="mt-1 font-extrabold text-white">
                                AED {{ number_format((float) $value, 2) }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-blue-400/20 bg-blue-500/10 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-blue-300">
                                Appointment / Expected Close Date
                            </div>

                            <div class="mt-1 font-extrabold text-white">
                                {{ optional($opportunity->expected_close_date)->format('d M Y') ?? '—' }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Assigned To
                            </div>

                            <div class="mt-1 font-extrabold text-white">
                                {{ $opportunity->assignee?->name ?? 'Unassigned' }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Converted?
                            </div>

                            <div class="mt-1">
                                <span class="{{ $opportunity->is_converted ? 'sf-badge-green' : 'sf-badge-slate' }}">
                                    {{ $opportunity->is_converted ? 'Yes' : 'No' }}
                                </span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Services --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Service Type(s)
                    </h2>

                    <p class="sf-section-subtitle">
                        Services discussed or requested under this opportunity.
                    </p>
                </div>

                <div class="sf-card-body">
                    @if($services->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach($services as $service)
                                <span class="sf-badge-blue">
                                    {{ $service }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <div class="sf-empty">
                            No service type added yet.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Notes --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Notes
                    </h2>
                </div>

                <div class="sf-card-body">
                    @if($opportunity->notes)
                        <div class="whitespace-pre-line text-sm font-medium leading-7 text-slate-300">
                            {{ $opportunity->notes }}
                        </div>
                    @else
                        <div class="sf-empty">
                            No notes added.
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- Right Column --}}
        <div class="space-y-6">

            {{-- Customer --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Customer
                    </h2>
                </div>

                <div class="sf-card-body space-y-4 text-sm">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Client
                        </div>

                        <div class="mt-1 font-extrabold text-white">
                            {{ $opportunity->client?->name ?? 'N/A' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Phone
                        </div>

                        <div class="mt-1 font-bold text-slate-200">
                            {{ $opportunity->client?->phone ?? $opportunity->client?->whatsapp ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Email
                        </div>

                        <div class="mt-1 break-words font-bold text-slate-200">
                            {{ $opportunity->client?->email ?? '—' }}
                        </div>
                    </div>

                    @if($opportunity->client_id && Route::has('admin.clients.show'))
                        <a href="{{ route('admin.clients.show', $opportunity->client_id) }}" class="sf-btn-secondary w-full">
                            Open Client Profile
                        </a>
                    @endif
                </div>
            </div>

            {{-- Vehicle --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Vehicle
                    </h2>
                </div>

                <div class="sf-card-body space-y-4 text-sm">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Vehicle
                        </div>

                        <div class="mt-1 font-extrabold text-white">
                            {{ $vehicleLabel !== '' ? $vehicleLabel : 'No vehicle added' }}
                        </div>
                    </div>

                    @if(!empty($opportunity->vehicle_year))
                        <div>
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                                Year
                            </div>

                            <div class="mt-1 font-bold text-slate-200">
                                {{ $opportunity->vehicle_year }}
                            </div>
                        </div>
                    @endif

                    @if(!empty($opportunity->plate_number))
                        <div>
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                                Plate Number
                            </div>

                            <div class="mt-1 font-bold text-slate-200">
                                {{ $opportunity->plate_number }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- System --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        System Details
                    </h2>
                </div>

                <div class="sf-card-body space-y-4 text-sm">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Created At
                        </div>

                        <div class="mt-1 font-bold text-slate-200">
                            {{ $opportunity->created_at?->format('d M Y, h:i A') ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Last Updated
                        </div>

                        <div class="mt-1 font-bold text-slate-200">
                            {{ $opportunity->updated_at?->format('d M Y, h:i A') ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Company ID
                        </div>

                        <div class="mt-1 font-bold text-slate-200">
                            {{ $opportunity->company_id }}
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection