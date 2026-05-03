@php
$vehicles = $client->vehicles instanceof \Illuminate\Support\Collection ? $client->vehicles : collect();
$hasVehicles = $vehicles->isNotEmpty();

$opportunityVehicles = collect();

if (!$hasVehicles) {
    $opportunityVehicles = ($client->opportunities instanceof \Illuminate\Support\Collection ? $client->opportunities : collect())
        ->map(fn ($o) => trim(
            ($o->vehicleMake?->name ?? $o->other_make ?? '') . ' ' .
            ($o->vehicleModel?->name ?? $o->other_model ?? '')
        ))
        ->filter()
        ->unique()
        ->values();
}
@endphp

<h3 class="text-lg font-semibold text-gray-800 mb-2 flex items-center justify-between">
    <span>Vehicles</span>

    @if (\Illuminate\Support\Facades\Route::has('admin.vehicles.create'))
        <a href="{{ route('admin.vehicles.create', ['client_id' => $client->id]) }}"
           class="inline-flex items-center px-3 py-2 text-sm font-medium rounded bg-indigo-600 text-white hover:bg-indigo-700">
            + Add Vehicle
        </a>
    @endif
</h3>

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

                <div class="text-xs text-gray-500 mt-2">
                    VIN: {{ $v->vin ?? '—' }}
                    • Reg Exp: {{ optional($v->registration_expiry_date)->format('Y-m-d') ?? '—' }}
                    • Ins Exp: {{ optional($v->insurance_expiry_date)->format('Y-m-d') ?? '—' }}
                </div>

                @if(\Illuminate\Support\Facades\Route::has('admin.vehicles.edit'))
                    <div class="mt-2">
                        <a href="{{ route('admin.vehicles.edit', $v->id) }}" class="text-sm text-blue-600 underline">
                            Edit Vehicle
                        </a>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@else
    <p class="text-sm text-gray-500">No vehicles added yet.</p>
@endif
