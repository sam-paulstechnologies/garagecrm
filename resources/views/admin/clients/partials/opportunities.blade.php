<h3 class="text-lg font-semibold text-gray-800 mb-2 flex items-center justify-between">
    <span>Opportunities</span>

    <span class="flex items-center gap-3">
        @if (\Illuminate\Support\Facades\Route::has('admin.opportunities.index'))
            <a href="{{ route('admin.opportunities.index', ['client_id' => $client->id]) }}"
               class="text-sm text-indigo-600 hover:underline">View all</a>
        @endif
        @if (\Illuminate\Support\Facades\Route::has('admin.opportunities.create'))
            <a href="{{ route('admin.opportunities.create', ['client_id' => $client->id]) }}"
               class="text-xs px-2 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700">+ Add Opportunity</a>
        @endif
    </span>
</h3>

@php
    // Latest 3 opps with make/model snapshot
    $opps = method_exists($client, 'opportunities')
        ? $client->opportunities()->with('vehicleMake:id,name','vehicleModel:id,name')->latest()->take(3)->get()
        : collect();
@endphp

@forelse ($opps as $opp)
    <div class="py-2 text-sm">
        @if (\Illuminate\Support\Facades\Route::has('admin.opportunities.show'))
            <a href="{{ route('admin.opportunities.show', $opp->id) }}" class="hover:underline">
                • {{ $opp->title ?? 'Untitled Opportunity' }}
            </a>
        @else
            • {{ $opp->title ?? 'Untitled Opportunity' }}
        @endif

        @php
            $stage = ucfirst(str_replace('_',' ', $opp->stage ?? 'new'));
            $mk = optional($opp->vehicleMake)->name;
            $md = optional($opp->vehicleModel)->name;
            $veh = trim(($mk ? $mk : '') . ' ' . ($md ? $md : ''));
        @endphp
        <span class="text-gray-500">
            ({{ $stage }}@if($veh) — {{ $veh }} @endif)
        </span>
    </div>
@empty
    <p class="text-sm text-gray-500">No opportunities yet.</p>
@endforelse
