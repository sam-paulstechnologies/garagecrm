{{-- resources/views/admin/opportunities/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Opportunities')

@section('content')
@php
    $q = $q ?? request('q', '');
    $stage = $stage ?? request('stage', '');
    $priority = $priority ?? request('priority', '');
    $bucket = $bucket ?? request('bucket', '');
    $pipelineStatus = $pipelineStatus ?? request('pipeline_status', ($stage === '' ? 'open' : ''));
    $selectedBucket = $selectedBucket ?? $bucket;
    $pageTitle = $pageTitle ?? 'Open Opportunities';
    $pageSubtitle = $pageSubtitle ?? 'Active pipeline opportunities that still need follow-up, confirmation, or conversion.';

    $opportunityCounts = array_merge([
        'open' => 0,
        'appointment' => 0,
        'missed_appointment' => 0,
        'won' => 0,
        'lost' => 0,
    ], $opportunityCounts ?? []);

    $bucketCounts = array_merge([
        'high_priority' => 0,
        'follow_up_due' => 0,
        'no_follow_up' => 0,
        'unassigned' => 0,
        'no_vehicle' => 0,
        'missing_service' => 0,
        'missing_close_date' => 0,
        'no_value' => 0,
        'stale_open' => 0,
    ], $bucketCounts ?? []);

    $stageBadge = function ($stage) {
        return match (\App\Models\Client\Opportunity::normalizeStage($stage)) {
            'new' => 'sf-badge-blue',
            'attempting_contact' => 'sf-badge-yellow',
            'appointment' => 'sf-badge-blue',
            'offer' => 'sf-badge-orange',
            'manager_confirmation_pending' => 'sf-badge-orange',
            'booking_confirmed' => 'sf-badge-green',
            'closed_lost' => 'sf-badge-red',
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

    $bucketCardClass = function ($key) use ($selectedBucket) {
        return $selectedBucket === $key
            ? 'sf-opportunity-bucket-active'
            : 'sf-opportunity-bucket-idle';
    };

    $statusCardClass = function ($key) use ($pipelineStatus) {
        return $pipelineStatus === $key
            ? 'sf-opportunity-bucket-active'
            : 'sf-opportunity-bucket-idle';
    };

    $stageLabel = function ($value) {
        return \App\Models\Client\Opportunity::stageLabel($value);
    };

    $bucketCards = [
        ['key' => 'high_priority', 'title' => 'High Priority', 'count' => $bucketCounts['high_priority'] ?? 0, 'note' => 'High / urgent'],
        ['key' => 'follow_up_due', 'title' => 'Follow-up Due', 'count' => $bucketCounts['follow_up_due'] ?? 0, 'note' => 'Due today or overdue'],
        ['key' => 'no_follow_up', 'title' => 'No Follow-up Set', 'count' => $bucketCounts['no_follow_up'] ?? 0, 'note' => 'Needs next action date'],
        ['key' => 'unassigned', 'title' => 'Unassigned', 'count' => $bucketCounts['unassigned'] ?? 0, 'note' => 'No owner'],
        ['key' => 'no_vehicle', 'title' => 'No Vehicle', 'count' => $bucketCounts['no_vehicle'] ?? 0, 'note' => 'Missing vehicle'],
        ['key' => 'missing_service', 'title' => 'Missing Service', 'count' => $bucketCounts['missing_service'] ?? 0, 'note' => 'No service selected'],
        ['key' => 'missing_close_date', 'title' => 'No Target Date', 'count' => $bucketCounts['missing_close_date'] ?? 0, 'note' => 'No expected close date'],
        ['key' => 'no_value', 'title' => 'No Value', 'count' => $bucketCounts['no_value'] ?? 0, 'note' => 'Missing value'],
        ['key' => 'stale_open', 'title' => 'Stale Open', 'count' => $bucketCounts['stale_open'] ?? 0, 'note' => 'No update in 7+ days'],
    ];

    $clearUrl = route('admin.opportunities.index');
@endphp

    @include('admin.opportunities.index-partials._styles')

    <div class="sf-page sf-opportunities-page w-full px-4 py-6 space-y-6 sm:px-6 lg:px-8">
        <div class="sf-index-sticky-panel space-y-6">
            @include('admin.opportunities.index-partials._hero')

            {{-- Search and filter first --}}
            @include('admin.opportunities.index-partials._filters')

            {{-- Opportunity buckets second --}}
            @include('admin.opportunities.index-partials._bucket_cards')

            {{-- KPI tiles third --}}
            @include('admin.opportunities.index-partials._stats')
        </div>

        {{-- Alerts if any --}}
        @include('admin.opportunities.index-partials._alerts')

        {{-- Table --}}
        @include('admin.opportunities.index-partials._table')

        @include('admin.opportunities.index-partials._pagination')
    </div>
@endsection
