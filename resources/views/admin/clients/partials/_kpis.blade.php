@php
/**
 * ------------------------------------------------------------
 * KPI Defensive Contract (SINGLE SOURCE OF TRUTH)
 * ------------------------------------------------------------
 * This ensures the view NEVER crashes due to missing keys,
 * regardless of what the controller passes.
 */

$kpis = array_merge([
    // Volume / Value
    'cars'        => 0,
    'ltv'         => 0,
    'avg_spend'   => 0,

    // Timeline
    'last_service' => null,
    'next_service' => null,

    // Profile
    'profile_pct' => 0,
], is_array($kpis ?? null) ? $kpis : []);

// Helper to render AED consistently
$aed = fn ($n) => 'AED ' . number_format((float) $n, 2);

// Safe date formatting
$lastService = !empty($kpis['last_service'])
    ? \Illuminate\Support\Carbon::parse($kpis['last_service'])->format('d M Y')
    : '—';

$nextService = !empty($kpis['next_service'])
    ? \Illuminate\Support\Carbon::parse($kpis['next_service'])->format('d M Y')
    : '—';

// Clamp profile percentage between 0–100
$profilePct = max(0, min(100, (int) $kpis['profile_pct']));
@endphp

<div class="space-y-5">

    {{-- Section Header --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="sf-section-title">
                Client KPIs
            </h3>

            <p class="sf-section-subtitle">
                Quick snapshot of vehicles, lifetime value, service timeline, and profile completion.
            </p>
        </div>

        <span class="sf-badge-orange">
            Profile {{ $profilePct }}%
        </span>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">

        {{-- Number of Cars --}}
        <div class="rounded-2xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-lg shadow-black/10">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-extrabold uppercase tracking-wide text-blue-300">
                        Number of Cars
                    </div>

                    <div class="mt-2 text-3xl font-extrabold text-white">
                        {{ $kpis['cars'] }}
                    </div>

                    <div class="mt-1 text-xs font-medium text-blue-100/70">
                        Vehicles linked to this client
                    </div>
                </div>

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-500/10 text-xl text-blue-300 ring-1 ring-blue-400/20">
                    🚗
                </div>
            </div>
        </div>

        {{-- Lifetime Value --}}
        <div class="rounded-2xl border border-orange-400/20 bg-orange-500/10 p-5 shadow-lg shadow-black/10">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-orange-300">
                        Lifetime Value
                    </div>

                    <div class="mt-2 truncate text-2xl font-extrabold text-white">
                        {{ $aed($kpis['ltv']) }}
                    </div>

                    <div class="mt-1 text-xs font-medium text-orange-100/70">
                        Total revenue from this client
                    </div>
                </div>

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-orange-500/10 text-xl text-orange-300 ring-1 ring-orange-400/20">
                    💰
                </div>
            </div>
        </div>

        {{-- Avg Spend --}}
        <div class="rounded-2xl border border-green-400/20 bg-green-500/10 p-5 shadow-lg shadow-black/10">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-green-300">
                        Avg Spend / Visit
                    </div>

                    <div class="mt-2 truncate text-2xl font-extrabold text-white">
                        {{ $aed($kpis['avg_spend']) }}
                    </div>

                    <div class="mt-1 text-xs font-medium text-green-100/70">
                        Average invoice value
                    </div>
                </div>

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-green-500/10 text-xl text-green-300 ring-1 ring-green-400/20">
                    📈
                </div>
            </div>
        </div>

        {{-- Last Service --}}
        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-5 shadow-lg shadow-black/10">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                        Last Service
                    </div>

                    <div class="mt-2 text-xl font-extrabold text-white">
                        {{ $lastService }}
                    </div>

                    <div class="mt-1 text-xs font-medium text-slate-500">
                        Most recent completed visit
                    </div>
                </div>

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/5 text-xl text-slate-300 ring-1 ring-white/10">
                    🛠️
                </div>
            </div>
        </div>

        {{-- Next Service --}}
        <div class="rounded-2xl border border-yellow-400/20 bg-yellow-500/10 p-5 shadow-lg shadow-black/10">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-extrabold uppercase tracking-wide text-yellow-300">
                        Upcoming Service
                    </div>

                    <div class="mt-2 text-xl font-extrabold text-white">
                        {{ $nextService }}
                    </div>

                    <div class="mt-1 text-xs font-medium text-yellow-100/70">
                        Next expected service date
                    </div>
                </div>

                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-yellow-500/10 text-xl text-yellow-300 ring-1 ring-yellow-400/20">
                    📅
                </div>
            </div>
        </div>

        {{-- Profile Completion --}}
        <div class="rounded-2xl border border-purple-400/20 bg-purple-500/10 p-5 shadow-lg shadow-black/10">
            <div class="flex items-start justify-between gap-3">
                <div class="w-full">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-purple-300">
                        Profile Completion
                    </div>

                    <div class="mt-2 flex items-center justify-between gap-3">
                        <div class="text-2xl font-extrabold text-white">
                            {{ $profilePct }}%
                        </div>

                        <span class="sf-badge-slate">
                            CRM Profile
                        </span>
                    </div>

                    <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-950/70 ring-1 ring-white/10">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-orange-500 to-purple-400 transition-all"
                            style="width: {{ $profilePct }}%;"
                        ></div>
                    </div>

                    <div class="mt-2 text-xs font-medium text-purple-100/70">
                        Based on available client data
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>