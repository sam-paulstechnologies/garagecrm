@php
/**
 * ------------------------------------------------------------
 * Vehicles – Defensive Partial
 * ------------------------------------------------------------
 * Rules:
 * - $client->vehicles is ALWAYS a Collection
 * - Fallback to opportunities only if no vehicles exist
 */

$vehicles = $client->vehicles instanceof \Illuminate\Support\Collection
    ? $client->vehicles
    : collect();

$hasVehicles = $vehicles->isNotEmpty();

/**
 * Fallback: derive unique vehicles from opportunities
 */
$opportunityVehicles = collect();

if (!$hasVehicles && $client->opportunities instanceof \Illuminate\Support\Collection) {
    $opportunityVehicles = $client->opportunities
        ->map(fn ($o) => trim(
            ($o->vehicleMake?->name ?? $o->other_make ?? '') . ' ' .
            ($o->vehicleModel?->name ?? $o->other_model ?? '')
        ))
        ->filter()
        ->unique()
        ->values();
}
@endphp

<div class="flex items-center justify-between mb-3">
    <h2 class="text-lg font-semibold">Vehicles</h2>

    @if(Route::has('admin.vehicles.create'))
        <a href="{{ route('admin.vehicles.create', ['client_id' => $client->id]) }}"
           class="inline-flex items-center px-3 py-2 text-sm rounded bg-indigo-600 text-white hover:bg-indigo-700">
            + Add Vehicle
        </a>
    @endif
</div>

{{-- 🔁 Opportunity fallback --}}
@if(!$hasVehicles && $opportunityVehicles->isNotEmpty())
    <div class="border rounded p-3 mb-4 bg-gray-50">
        <div class="text-xs text-gray-500 font-medium mb-1">
            Vehicles from Opportunities
        </div>

        <ul class="text-sm text-gray-800 space-y-1">
            @foreach($opportunityVehicles as $label)
                <li>{{ $label }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- 🚗 Actual vehicles --}}
@if($hasVehicles)
    <div class="space-y-3">
        @foreach ($vehicles as $v)
            <div class="border rounded p-3">
                <div class="font-medium">
                    {{ $v->year ?? '' }}
                    {{ optional($v->make)->name }}
                    {{ optional($v->model)->name }}
                </div>

                <div class="text-sm text-gray-700">
                    Plate: {{ $v->plate_number ?? '—' }}
                </div>

                <div class="mt-2 flex gap-3 text-xs">
                    @if(Route::has('admin.vehicles.edit'))
                        <a href="{{ route('admin.vehicles.edit', $v->id) }}"
                           class="text-blue-600 underline">
                            Edit
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@else
    <p class="text-sm text-gray-500">No vehicles added yet.</p>
@endif
