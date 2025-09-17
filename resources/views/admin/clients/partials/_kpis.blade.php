@php
    // Helper to render AED consistently
    $aed = fn($n) => 'AED ' . number_format((float) $n, 2);

    $lastService = $kpis['last_service'] ? \Illuminate\Support\Carbon::parse($kpis['last_service'])->format('d M Y') : '—';
    $nextService = $kpis['next_service'] ? \Illuminate\Support\Carbon::parse($kpis['next_service'])->format('d M Y') : '—';
@endphp

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
            <div class="h-full bg-indigo-600" style="width: {{ (int) $kpis['profile_pct'] }}%;"></div>
        </div>
        <div class="text-right text-xs text-gray-600 mt-1">{{ (int) $kpis['profile_pct'] }}%</div>
    </div>
</div>
