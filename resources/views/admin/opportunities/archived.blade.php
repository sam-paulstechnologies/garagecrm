@extends('layouts.app')

@section('title', 'Archived Opportunities')

@section('content')
@php
    $stageBadge = function ($stage) {
        return match (\App\Models\Client\Opportunity::normalizeStage($stage)) {
            'new' => 'sf-badge-blue',
            'attempting_contact' => 'sf-badge-yellow',
            'manager_confirmation_pending' => 'sf-badge-orange',
            'appointment', 'offer' => 'sf-badge-blue',
            'booking_confirmed' => 'sf-badge-green',
            'closed_lost' => 'sf-badge-red',
            default => 'sf-badge-slate',
        };
    };

    $priorityBadge = function ($priority) {
        return match (strtolower((string) $priority)) {
            'high', 'urgent' => 'sf-badge-red',
            'medium' => 'sf-badge-orange',
            'low' => 'sf-badge-slate',
            default => 'sf-badge-slate',
        };
    };
@endphp

    @include('admin.opportunities.archive-partials._styles')

    <div class="sf-page sf-opportunities-archive-page mx-auto max-w-7xl px-4 py-6 space-y-6">
        @include('admin.opportunities.archive-partials._hero')
        @include('admin.opportunities.archive-partials._alerts')
        @include('admin.opportunities.archive-partials._stats')
        @include('admin.opportunities.archive-partials._table')
        @include('admin.opportunities.archive-partials._pagination')
    </div>
@endsection
