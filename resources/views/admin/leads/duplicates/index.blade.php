@extends('layouts.app')

@section('title', 'Potential Duplicates')

@section('content')
@php
    $duplicateCount = method_exists($dupes, 'total') ? $dupes->total() : $dupes->count();
@endphp

<div class="sf-page space-y-6">

    <div class="sf-page-header">
        <div class="min-w-0">
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
                Back to Leads
            </a>
        </div>
    </div>

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

    <form action="{{ route('admin.leads.duplicates.update-window') }}" method="POST" class="sf-card">
        @csrf

        <div class="sf-card-header flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="sf-section-title">
                    Duplicate Detection Window
                </h2>

                <p class="sf-section-subtitle">
                    Set how many days the system should look back when detecting possible duplicate leads.
                </p>
            </div>

            <span class="inline-flex w-fit rounded-full border border-orange-300/40 bg-orange-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-orange-700 dark:border-orange-400/30 dark:text-orange-200">
                Lead capture quality
            </span>
        </div>

        <div class="sf-card-body">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,18rem)_auto_minmax(0,1fr)] lg:items-end">
                <div>
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

                <button type="submit" class="sf-btn-primary w-full lg:w-auto">
                    Save Window
                </button>

                <p class="text-sm font-medium leading-6 text-slate-600 dark:text-slate-400">
                    Shorter windows reduce noise. Longer windows help catch duplicate submissions from slower campaign follow-up.
                </p>
            </div>
        </div>
    </form>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Potential Duplicates
            </div>

            <div class="sf-stat-value text-orange-700 dark:text-orange-200">
                {{ $duplicateCount }}
            </div>

            <div class="sf-stat-note">
                Records needing review
            </div>
        </div>

        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Detection Window
            </div>

            <div class="sf-stat-value text-blue-700 dark:text-blue-200">
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
                <span class="rounded-full border border-blue-300/50 bg-blue-500/10 px-3 py-1 text-xs font-extrabold text-blue-700 dark:border-blue-400/30 dark:text-blue-200">
                    Email
                </span>
                <span class="rounded-full border border-orange-300/50 bg-orange-500/10 px-3 py-1 text-xs font-extrabold text-orange-700 dark:border-orange-400/30 dark:text-orange-200">
                    Phone
                </span>
            </div>

            <div class="sf-stat-note">
                Based on available duplicate rules
            </div>
        </div>

        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Review Action
            </div>

            <div class="mt-3 text-lg font-extrabold text-slate-900 dark:text-white">
                Open Primary Lead
            </div>

            <div class="sf-stat-note">
                Compare before updating
            </div>
        </div>
    </div>

    <div class="sf-card">
        <div class="sf-card-header flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="sf-section-title">
                    Duplicate Review List
                </h2>

                <p class="sf-section-subtitle">
                    Review each captured duplicate against its primary lead before taking action.
                </p>
            </div>

            <span class="inline-flex w-fit rounded-full border border-slate-300 bg-slate-50 px-3 py-1 text-xs font-extrabold text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-200">
                {{ $duplicateCount }} total
            </span>
        </div>

        <div class="sf-card-body">
            <div class="space-y-4">
                @forelse($dupes as $d)
                    @php
                        $matchedOn = strtolower((string) ($d->matched_on ?? ''));
                        $matchedBadgeClass = match ($matchedOn) {
                            'phone' => 'border-orange-300/50 bg-orange-500/10 text-orange-700 dark:border-orange-400/30 dark:text-orange-200',
                            'email' => 'border-blue-300/50 bg-blue-500/10 text-blue-700 dark:border-blue-400/30 dark:text-blue-200',
                            default => 'border-slate-300 bg-slate-50 text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-200',
                        };
                    @endphp

                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-orange-300/60 hover:shadow-md dark:border-white/10 dark:bg-slate-950/45 dark:hover:border-orange-400/30 sm:p-5">
                        <div class="grid gap-5 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.45fr)_minmax(14rem,0.75fr)] lg:items-center">
                            <div class="min-w-0">
                                <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                    Detected
                                </p>

                                <p class="mt-2 text-sm font-extrabold text-slate-900 dark:text-white">
                                    {{ optional($d->detected_at)->format('d M Y') ?? '-' }}
                                </p>

                                <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                    {{ optional($d->detected_at)->format('h:i A') ?? 'No time recorded' }}
                                </p>

                                <span class="mt-3 inline-flex rounded-full border px-3 py-1 text-xs font-extrabold {{ $matchedBadgeClass }}">
                                    Matched on {{ $d->matched_on ? ucfirst($d->matched_on) : 'Unknown' }}
                                </span>
                            </div>

                            <div class="min-w-0">
                                <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                    Duplicate lead
                                </p>

                                <h3 class="mt-2 truncate text-lg font-black text-slate-950 dark:text-white">
                                    {{ $d->name ?? 'Unnamed Lead' }}
                                </h3>

                                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm font-semibold text-slate-600 dark:text-slate-300">
                                    <span class="break-all">
                                        {{ $d->phone ?: 'No phone' }}
                                    </span>

                                    <span class="break-all">
                                        {{ $d->email ?: 'No email' }}
                                    </span>
                                </div>

                                <p class="mt-3 text-sm font-medium leading-6 text-slate-600 dark:text-slate-300">
                                    {{ $d->reason ?: 'No reason recorded.' }}
                                </p>
                            </div>

                            <div class="min-w-0 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-white/10 dark:bg-white/5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full border border-slate-300 bg-white px-2.5 py-1 text-xs font-extrabold text-slate-700 dark:border-white/10 dark:bg-slate-950/60 dark:text-slate-200">
                                        {{ $d->window_days }} days
                                    </span>

                                    <span class="rounded-full border border-orange-300/40 bg-orange-500/10 px-2.5 py-1 text-xs font-extrabold text-orange-700 dark:border-orange-400/30 dark:text-orange-200">
                                        Primary lead
                                    </span>
                                </div>

                                @if($d->primary)
                                    <a class="mt-3 block truncate text-sm font-extrabold text-orange-700 hover:text-orange-800 dark:text-orange-200 dark:hover:text-orange-100"
                                       href="{{ route('admin.leads.show', $d->primary->id) }}">
                                        #{{ $d->primary->id }} {{ $d->primary->name }}
                                    </a>
                                @else
                                    <p class="mt-3 text-sm font-semibold text-slate-500 dark:text-slate-400">
                                        No primary lead linked
                                    </p>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="sf-empty">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-500/10 text-orange-700 dark:text-orange-200">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>

                        <p class="mt-4 text-base font-extrabold text-slate-900 dark:text-white">
                            No potential duplicates found
                        </p>

                        <p class="mt-2 text-sm font-medium text-slate-600 dark:text-slate-400">
                            New duplicate submissions will appear here when they match the configured phone or email rules.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    @if(method_exists($dupes, 'links') && $dupes->hasPages())
        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-700 shadow-sm dark:border-white/10 dark:bg-slate-950/45 dark:text-slate-200">
            {{ $dupes->links() }}
        </div>
    @endif
</div>
@endsection
