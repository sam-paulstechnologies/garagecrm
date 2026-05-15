@extends('layouts.app')

@section('title', 'Potential Duplicates')

@section('content')
<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Lead Quality
            </div>

            <h1 class="sf-page-title mt-3">
                Potential Duplicates
            </h1>

            <p class="sf-page-subtitle">
                Review leads that may have been captured more than once within the configured duplicate detection window.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.leads.index') }}" class="sf-btn-secondary">
                ← Back to Leads
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

    @if($errors->any())
        <div class="sf-alert-danger">
            <div class="mb-2 font-extrabold">
                Please fix the following:
            </div>

            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Window Settings --}}
    <form action="{{ route('admin.leads.duplicates.update-window') }}" method="POST" class="sf-card">
        @csrf

        <div class="sf-card-header">
            <h2 class="sf-section-title">
                Duplicate Detection Window
            </h2>

            <p class="sf-section-subtitle">
                Set how many days the system should look back when detecting possible duplicate leads.
            </p>
        </div>

        <div class="sf-card-body">
            <div class="flex flex-col gap-3 md:flex-row md:items-end">
                <div class="w-full md:w-64">
                    <label class="sf-label">
                        Duplicate window days
                    </label>

                    <input type="number"
                           name="window_days"
                           min="1"
                           max="365"
                           value="{{ $windowDays }}"
                           class="sf-input" />

                    <p class="sf-help">
                        Allowed range: 1 to 365 days.
                    </p>
                </div>

                <button type="submit" class="sf-btn-primary">
                    Save Window
                </button>
            </div>
        </div>
    </form>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Potential Duplicates
            </div>

            <div class="sf-stat-value text-orange-300">
                {{ method_exists($dupes, 'total') ? $dupes->total() : $dupes->count() }}
            </div>

            <div class="sf-stat-note">
                Records needing review
            </div>
        </div>

        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Detection Window
            </div>

            <div class="sf-stat-value text-blue-300">
                {{ $windowDays }}
            </div>

            <div class="sf-stat-note">
                Days configured
            </div>
        </div>

        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Match Criteria
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                <span class="sf-badge-blue">Email</span>
                <span class="sf-badge-orange">Phone</span>
            </div>

            <div class="sf-stat-note">
                Based on available duplicate rules
            </div>
        </div>

        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Review Action
            </div>

            <div class="mt-3 text-lg font-extrabold text-white">
                Open Primary Lead
            </div>

            <div class="sf-stat-note">
                Compare before updating
            </div>
        </div>
    </div>

    {{-- Duplicates Table --}}
    <div class="sf-table-wrap">
        <div class="sf-table-scroll">
            <table class="sf-table">
                <thead>
                    <tr>
                        <th class="w-[18%]">Detected</th>
                        <th class="w-[16%]">Matched On</th>
                        <th class="w-[24%]">Duplicate Lead</th>
                        <th class="w-[14%]">Window</th>
                        <th class="w-[18%]">Reason</th>
                        <th class="w-[10%] text-right">Primary</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($dupes as $d)
                        <tr>
                            {{-- Detected --}}
                            <td>
                                <div class="font-bold text-white">
                                    {{ optional($d->detected_at)->format('d M Y') ?? '—' }}
                                </div>

                                <div class="text-xs font-medium text-slate-500">
                                    {{ optional($d->detected_at)->format('h:i A') ?? '' }}
                                </div>
                            </td>

                            {{-- Matched On --}}
                            <td>
                                @php
                                    $matchedOn = strtolower((string) ($d->matched_on ?? ''));

                                    $matchedClass = match ($matchedOn) {
                                        'phone' => 'sf-badge-orange',
                                        'email' => 'sf-badge-blue',
                                        default => 'sf-badge-slate',
                                    };
                                @endphp

                                <span class="{{ $matchedClass }}">
                                    {{ ucfirst($d->matched_on ?? '—') }}
                                </span>
                            </td>

                            {{-- Duplicate Lead --}}
                            <td>
                                <div class="font-extrabold text-white">
                                    {{ $d->name ?? 'Unnamed Lead' }}
                                </div>

                                <div class="mt-1 text-xs font-medium text-slate-400">
                                    {{ $d->email ?? 'No email' }}
                                </div>

                                <div class="mt-1 text-sm font-bold text-slate-300">
                                    {{ $d->phone ?? 'No phone' }}
                                </div>
                            </td>

                            {{-- Window --}}
                            <td>
                                <span class="sf-badge-slate">
                                    {{ $d->window_days }} days
                                </span>
                            </td>

                            {{-- Reason --}}
                            <td>
                                <div class="text-sm font-medium leading-6 text-slate-300">
                                    {{ $d->reason ?? '—' }}
                                </div>
                            </td>

                            {{-- Primary Lead --}}
                            <td class="text-right">
                                @if($d->primary)
                                    <a class="sf-link"
                                       href="{{ route('admin.leads.show', $d->primary->id) }}">
                                        #{{ $d->primary->id }}
                                    </a>

                                    <div class="mt-1 max-w-[160px] truncate text-xs font-medium text-slate-500">
                                        {{ $d->primary->name }}
                                    </div>
                                @else
                                    <span class="text-slate-600">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="sf-empty">
                                    No duplicates found.
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
        {{ $dupes->links() }}
    </div>

</div>
@endsection