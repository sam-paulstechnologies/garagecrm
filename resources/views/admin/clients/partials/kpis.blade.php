{{-- resources/views/admin/clients/partials/kpis.blade.php --}}

@php
/**
 * ------------------------------------------------------------
 * KPI Defensive Contract (SINGLE SOURCE OF TRUTH)
 * ------------------------------------------------------------
 */

$kpis = array_merge([
    'cars'          => 0,
    'ltv'           => 0,
    'avg_spend'     => 0,
    'last_service'  => null,
    'next_service'  => null,
    'profile_pct'   => 0,
    'missing_items' => [],
], is_array($kpis ?? null) ? $kpis : []);

$aed = fn ($n) => 'AED ' . number_format((float) $n, 2);

$lastService = !empty($kpis['last_service'])
    ? \Illuminate\Support\Carbon::parse($kpis['last_service'])->format('d M Y')
    : '—';

$nextService = !empty($kpis['next_service'])
    ? \Illuminate\Support\Carbon::parse($kpis['next_service'])->format('d M Y')
    : '—';

$profilePct = max(0, min(100, (int) $kpis['profile_pct']));
$missingItems = is_array($kpis['missing_items'] ?? null) ? $kpis['missing_items'] : [];

$profileGradient = match (true) {
    $profilePct >= 80 => 'from-green-500 to-emerald-400',
    $profilePct >= 50 => 'from-yellow-500 to-orange-400',
    default => 'from-red-500 to-orange-500',
};

$profileBadge = match (true) {
    $profilePct >= 80 => 'sf-badge-green',
    $profilePct >= 50 => 'sf-badge-yellow',
    default => 'sf-badge-red',
};
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="sf-section-title">
                Client KPIs
            </h3>

            <p class="sf-section-subtitle">
                Snapshot of vehicles, spend, service dates, and profile health.
            </p>
        </div>

        <span class="{{ $profileBadge }}">
            Profile {{ $profilePct }}%
        </span>
    </div>

    {{-- KPI Row 1 --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-lg shadow-black/10">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-extrabold uppercase tracking-wide text-blue-300">
                        Number of Cars
                    </div>

                    <div class="mt-2 text-3xl font-extrabold text-white">
                        {{ $kpis['cars'] }}
                    </div>
                </div>

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-500/10 text-xl text-blue-300 ring-1 ring-blue-400/20">
                    🚗
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-orange-400/20 bg-orange-500/10 p-5 shadow-lg shadow-black/10">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-orange-300">
                        Lifetime Value
                    </div>

                    <div class="mt-2 truncate text-2xl font-extrabold text-white">
                        {{ $aed($kpis['ltv']) }}
                    </div>
                </div>

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-orange-500/10 text-xl text-orange-300 ring-1 ring-orange-400/20">
                    💰
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-green-400/20 bg-green-500/10 p-5 shadow-lg shadow-black/10">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-green-300">
                        Avg Spend / Visit
                    </div>

                    <div class="mt-2 truncate text-2xl font-extrabold text-white">
                        {{ $aed($kpis['avg_spend']) }}
                    </div>
                </div>

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-green-500/10 text-xl text-green-300 ring-1 ring-green-400/20">
                    📈
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Row 2 --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-5 shadow-lg shadow-black/10">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                        Last Service
                    </div>

                    <div class="mt-2 text-xl font-extrabold text-white">
                        {{ $lastService }}
                    </div>
                </div>

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/5 text-xl text-slate-300 ring-1 ring-white/10">
                    🛠️
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-yellow-400/20 bg-yellow-500/10 p-5 shadow-lg shadow-black/10">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-extrabold uppercase tracking-wide text-yellow-300">
                        Upcoming Service
                    </div>

                    <div class="mt-2 text-xl font-extrabold text-white">
                        {{ $nextService }}
                    </div>
                </div>

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-yellow-500/10 text-xl text-yellow-300 ring-1 ring-yellow-400/20">
                    📅
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-purple-400/20 bg-purple-500/10 p-5 shadow-lg shadow-black/10">
            <div class="flex items-start justify-between gap-3">
                <div class="w-full min-w-0">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-purple-300">
                            Profile Completion
                        </div>

                        <div class="text-xs font-extrabold text-white">
                            {{ $profilePct }}%
                        </div>
                    </div>

                    <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-950/70 ring-1 ring-white/10">
                        <div
                            class="h-full rounded-full bg-gradient-to-r {{ $profileGradient }}"
                            style="width: {{ $profilePct }}%;"
                        ></div>
                    </div>

                    @if(count($missingItems))
                        <div class="mt-4">
                            <div class="mb-2 text-xs font-extrabold uppercase tracking-wide text-slate-400">
                                Missing
                            </div>

                            <div class="flex flex-wrap gap-1">
                                @foreach(array_slice($missingItems, 0, 5) as $item)
                                    <span class="inline-flex rounded-full bg-red-500/10 px-2 py-0.5 text-[11px] font-bold text-red-300 ring-1 ring-red-400/20">
                                        {{ $item }}
                                    </span>
                                @endforeach

                                @if(count($missingItems) > 5)
                                    <span class="sf-badge-slate">
                                        +{{ count($missingItems) - 5 }} more
                                    </span>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="mt-3 text-xs font-bold text-green-300">
                            Profile complete
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>