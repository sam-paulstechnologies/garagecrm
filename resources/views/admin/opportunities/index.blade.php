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

    $bucketCardClass = function ($key) use ($bucket) {
        return $bucket === $key
            ? 'sf-opportunity-bucket-active'
            : 'sf-opportunity-bucket-idle';
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
        ['key' => 'new', 'title' => 'New', 'count' => $bucketCounts['new'] ?? 0, 'note' => 'Fresh opportunities'],
        ['key' => 'attempting_contact', 'title' => 'Attempting Contact', 'count' => $bucketCounts['attempting_contact'] ?? 0, 'note' => 'Need follow-up'],
        ['key' => 'manager_confirmation_pending', 'title' => 'Manager Pending', 'count' => $bucketCounts['manager_confirmation_pending'] ?? 0, 'note' => 'Manager action needed'],
        ['key' => 'appointment', 'title' => 'Appointment', 'count' => $bucketCounts['appointment'] ?? 0, 'note' => 'Appointment planned'],
        ['key' => 'missed_appointment', 'title' => 'Missed', 'count' => $bucketCounts['missed_appointment'] ?? 0, 'note' => 'Past appointment'],
        ['key' => 'high_priority', 'title' => 'High Priority', 'count' => $bucketCounts['high_priority'] ?? 0, 'note' => 'High / urgent'],
        ['key' => 'unassigned', 'title' => 'Unassigned', 'count' => $bucketCounts['unassigned'] ?? 0, 'note' => 'No owner'],
        ['key' => 'no_vehicle', 'title' => 'No Vehicle', 'count' => $bucketCounts['no_vehicle'] ?? 0, 'note' => 'Missing vehicle'],
        ['key' => 'no_value', 'title' => 'No Value', 'count' => $bucketCounts['no_value'] ?? 0, 'note' => 'Missing value'],
    ];

    $clearUrl = route('admin.opportunities.index');
@endphp

    @include('admin.opportunities.index-partials._styles')

    <div class="sf-page sf-opportunities-page mx-auto max-w-7xl px-4 py-6 space-y-6">
        @include('admin.opportunities.index-partials._hero')
        @include('admin.opportunities.index-partials._pipeline_guide')
        @include('admin.opportunities.index-partials._alerts')
        @include('admin.opportunities.index-partials._stats')
        @include('admin.opportunities.index-partials._bucket_cards')
        @include('admin.opportunities.index-partials._filters')
        @include('admin.opportunities.index-partials._table')
        @include('admin.opportunities.index-partials._pagination')
    </div>
@endsection
