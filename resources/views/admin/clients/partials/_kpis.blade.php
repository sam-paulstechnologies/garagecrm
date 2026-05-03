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

{{-- ================= KPI CARDS ================= --}}

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="bg-gray-50 border rounded p-4">
        <div class="text-xs text-gray-500 mb-1">Number of Cars</div>
        <div class="text-xl font-semibold">{{ $kpis['cars'] }}</div>
    </div>

    <div class="bg-gray-50 border rounded p-4">
        <div class="text-xs text-gray-500 mb-1">Lifetime Value</div>
        <div class="text-xl font-semibold">{{ $aed($kpis['ltv']) }}</div>
    </div>

    <div class="bg-gray-50 border rounded p-4">
        <div class="text-xs text-gray-500 mb-1">Avg Spend / Visit</div>
        <div class="text-xl font-semibold">{{ $aed($kpis['avg_spend']) }}</div>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
    <div class="bg-gray-50 border rounded p-4">
        <div class="text-xs text-gray-500 mb-1">Last Service</div>
        <div class="text-base font-medium">{{ $lastService }}</div>
    </div>

    <div class="bg-gray-50 border rounded p-4">
        <div class="text-xs text-gray-500 mb-1">Upcoming Service</div>
        <div class="text-base font-medium">{{ $nextService }}</div>
    </div>

    <div class="bg-gray-50 border rounded p-4">
        <div class="text-xs text-gray-500 mb-1">Profile Completion</div>

        <div class="w-full h-2 bg-gray-200 rounded overflow-hidden mt-2">
            <div
                class="h-full bg-indigo-600 transition-all"
                style="width: {{ $profilePct }}%;"
            ></div>
        </div>

        <div class="text-right text-xs text-gray-600 mt-1">
            {{ $profilePct }}%
        </div>
    </div>
</div>
