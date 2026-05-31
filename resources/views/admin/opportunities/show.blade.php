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
        return match (strtolower((string) $stage)) {
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
        return match (strtolower((string) $priority)) {
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

    $services = $services->map(fn ($item) => trim((string) $item))->filter()->values();

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

    $value = $opportunity->value ?? $opportunity->estimated_value ?? $opportunity->amount ?? 0;
@endphp

    @include('admin.opportunities.show-partials._styles')

    <div class="sf-page sf-opportunity-show-page mx-auto max-w-7xl px-4 py-6 space-y-6">
        @include('admin.opportunities.show-partials._back_link')
        @include('admin.opportunities.show-partials._header')
        @include('admin.opportunities.show-partials._pipeline')

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                @include('admin.opportunities.show-partials._summary')
                @include('admin.opportunities.show-partials._services')
                @include('admin.opportunities.show-partials._notes')
            </div>

            <div class="space-y-6">
                @include('admin.opportunities.show-partials._customer_panel')
                @include('admin.opportunities.show-partials._vehicle_panel')
                @include('admin.opportunities.show-partials._system_panel')
            </div>
        </div>
    </div>
@endsection
