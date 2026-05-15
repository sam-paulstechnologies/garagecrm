@extends('layouts.app')

@section('title', 'Archived Opportunities')

@section('content')
@php
    $stageBadge = function ($stage) {
        $stage = strtolower((string) $stage);

        return match ($stage) {
            'new' => 'sf-badge-blue',
            'attempting_contact' => 'sf-badge-yellow',
            'manager_confirmation_pending' => 'sf-badge-orange',
            'appointment', 'offer' => 'sf-badge-blue',
            'closed_won', 'won' => 'sf-badge-green',
            'closed_lost', 'lost' => 'sf-badge-red',
            default => 'sf-badge-slate',
        };
    };

    $priorityBadge = function ($priority) {
        $priority = strtolower((string) $priority);

        return match ($priority) {
            'high', 'urgent' => 'sf-badge-red',
            'medium' => 'sf-badge-orange',
            'low' => 'sf-badge-slate',
            default => 'sf-badge-slate',
        };
    };
@endphp

<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Sales Pipeline Archive
            </div>

            <h1 class="sf-page-title mt-3">
                Archived Opportunities
            </h1>

            <p class="sf-page-subtitle">
                Review deleted opportunities and restore them back into the active pipeline when needed.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.opportunities.index') }}" class="sf-btn-secondary">
                ← Back to Opportunities
            </a>
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

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Archived Opportunities
            </div>

            <div class="sf-stat-value text-orange-300">
                {{ method_exists($opportunities, 'total') ? $opportunities->total() : $opportunities->count() }}
            </div>

            <div class="sf-stat-note">
                Deleted from active pipeline
            </div>
        </div>

        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Available Action
            </div>

            <div class="mt-3 text-lg font-extrabold text-white">
                Restore Opportunity
            </div>

            <div class="sf-stat-note">
                Move back to active opportunities
            </div>
        </div>

        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Archive Purpose
            </div>

            <div class="mt-3 text-lg font-extrabold text-white">
                Pipeline Cleanup
            </div>

            <div class="sf-stat-note">
                Keep lost or deleted records safely stored
            </div>
        </div>
    </div>

    {{-- Archived Opportunities Table --}}
    <div class="sf-table-wrap">
        <div class="sf-table-scroll">
            <table class="sf-table">
                <thead>
                    <tr>
                        <th class="w-[26%]">Title</th>
                        <th class="w-[18%]">Client</th>
                        <th class="w-[14%]">Stage</th>
                        <th class="w-[12%]">Priority</th>
                        <th class="w-[12%]">Value</th>
                        <th class="w-[12%]">Deleted At</th>
                        <th class="w-[6%] text-right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($opportunities as $opportunity)
                        <tr>
                            {{-- Title --}}
                            <td>
                                <div class="font-extrabold text-white">
                                    {{ $opportunity->title ?? 'Untitled Opportunity' }}
                                </div>

                                <div class="mt-1 text-xs font-medium text-slate-500">
                                    Opportunity ID: #{{ $opportunity->id }}
                                </div>
                            </td>

                            {{-- Client --}}
                            <td>
                                <div class="font-bold text-slate-200">
                                    {{ $opportunity->client->name ?? 'N/A' }}
                                </div>

                                @if(!empty($opportunity->client?->phone))
                                    <div class="mt-1 text-xs font-medium text-slate-500">
                                        {{ $opportunity->client->phone }}
                                    </div>
                                @endif
                            </td>

                            {{-- Stage --}}
                            <td>
                                <span class="{{ $stageBadge($opportunity->stage) }}">
                                    {{ ucfirst(str_replace('_', ' ', $opportunity->stage ?? '—')) }}
                                </span>
                            </td>

                            {{-- Priority --}}
                            <td>
                                <span class="{{ $priorityBadge($opportunity->priority) }}">
                                    {{ ucfirst($opportunity->priority ?? '—') }}
                                </span>
                            </td>

                            {{-- Value --}}
                            <td>
                                <div class="font-extrabold text-orange-300">
                                    AED {{ number_format((float) ($opportunity->value ?? 0), 2) }}
                                </div>
                            </td>

                            {{-- Deleted At --}}
                            <td>
                                <div class="font-bold text-slate-200">
                                    {{ $opportunity->deleted_at?->format('d M Y') ?? '—' }}
                                </div>

                                <div class="text-xs text-slate-500">
                                    {{ $opportunity->deleted_at?->format('h:i A') ?? '' }}
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="text-right">
                                <form action="{{ route('admin.opportunities.restore', $opportunity->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Restore this opportunity?');">
                                    @csrf
                                    @method('PUT')

                                    <button type="submit" class="sf-link">
                                        Restore
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="sf-empty">
                                    No archived opportunities found.
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