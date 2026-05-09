@php
/**
 * ------------------------------------------------------------
 * Vehicles – Defensive Partial
 * ------------------------------------------------------------
 * Rules:
 * - $client->vehicles is ALWAYS treated as a Collection
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

$formatDate = function ($date) {
    return $date
        ? \Illuminate\Support\Carbon::parse($date)->format('d M Y')
        : '—';
};

$statusBadge = function ($date) {
    if (!$date) {
        return [
            'label' => 'Missing',
            'class' => 'bg-red-50 text-red-700 border-red-100',
        ];
    }

    $parsedDate = \Illuminate\Support\Carbon::parse($date);

    if ($parsedDate->isPast()) {
        return [
            'label' => 'Expired',
            'class' => 'bg-red-50 text-red-700 border-red-100',
        ];
    }

    if ($parsedDate->diffInDays(now()) <= 30) {
        return [
            'label' => 'Due Soon',
            'class' => 'bg-yellow-50 text-yellow-700 border-yellow-100',
        ];
    }

    return [
        'label' => 'Valid',
        'class' => 'bg-green-50 text-green-700 border-green-100',
    ];
};
@endphp

<div class="flex items-center justify-between mb-4">
    <div>
        <h2 class="text-lg font-semibold">Vehicles</h2>
        <p class="text-xs text-gray-500 mt-1">
            Vehicle details help complete the customer profile and power retention reminders.
        </p>
    </div>

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
    <div class="space-y-4">
        @foreach ($vehicles as $v)
            @php
                $registrationStatus = $statusBadge($v->registration_expiry_date ?? null);
                $insuranceStatus = $statusBadge($v->insurance_expiry_date ?? null);

                $vehicleTitle = trim(
                    ($v->year ?? '') . ' ' .
                    (optional($v->make)->name ?? '') . ' ' .
                    (optional($v->model)->name ?? '')
                );
            @endphp

            <div class="border rounded-lg p-4 bg-white">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                    <div>
                        <div class="font-semibold text-gray-900">
                            {{ $vehicleTitle !== '' ? $vehicleTitle : 'Vehicle' }}
                        </div>

                        <div class="text-xs text-gray-500 mt-1">
                            Vehicle ID: #{{ $v->id }}
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @if(Route::has('admin.vehicles.edit'))
                            <a href="{{ route('admin.vehicles.edit', $v->id) }}"
                               class="inline-flex items-center px-3 py-1.5 rounded bg-blue-50 text-blue-700 text-xs font-medium hover:bg-blue-100">
                                Edit Vehicle
                            </a>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-4">
                    <div class="bg-gray-50 border rounded p-3">
                        <div class="text-xs text-gray-500 mb-1">Plate Number</div>
                        <div class="text-sm font-medium text-gray-900">
                            {{ $v->plate_number ?? '—' }}
                        </div>
                    </div>

                    <div class="bg-gray-50 border rounded p-3">
                        <div class="text-xs text-gray-500 mb-1">VIN</div>
                        <div class="text-sm font-medium text-gray-900 break-all">
                            {{ $v->vin ?? '—' }}
                        </div>
                    </div>

                    <div class="bg-gray-50 border rounded p-3">
                        <div class="text-xs text-gray-500 mb-1">Current Mileage</div>
                        <div class="text-sm font-medium text-gray-900">
                            {{ $v->current_mileage ? number_format((int) $v->current_mileage) . ' km' : '—' }}
                        </div>
                    </div>

                    <div class="bg-gray-50 border rounded p-3">
                        <div class="text-xs text-gray-500 mb-1">Color</div>
                        <div class="text-sm font-medium text-gray-900">
                            {{ $v->color ?? '—' }}
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-3">
                    <div class="bg-gray-50 border rounded p-3">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <div class="text-xs text-gray-500 mb-1">Mulkia / Registration Expiry</div>
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $formatDate($v->registration_expiry_date ?? null) }}
                                </div>
                            </div>

                            <span class="px-2 py-0.5 rounded-full border text-[11px] {{ $registrationStatus['class'] }}">
                                {{ $registrationStatus['label'] }}
                            </span>
                        </div>
                    </div>

                    <div class="bg-gray-50 border rounded p-3">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <div class="text-xs text-gray-500 mb-1">Insurance Expiry</div>
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $formatDate($v->insurance_expiry_date ?? null) }}
                                </div>
                            </div>

                            <span class="px-2 py-0.5 rounded-full border text-[11px] {{ $insuranceStatus['class'] }}">
                                {{ $insuranceStatus['label'] }}
                            </span>
                        </div>
                    </div>

                    <div class="bg-gray-50 border rounded p-3">
                        <div class="text-xs text-gray-500 mb-1">Last Inspection</div>
                        <div class="text-sm font-medium text-gray-900">
                            {{ $formatDate($v->last_inspection_date ?? null) }}
                        </div>
                    </div>

                    <div class="bg-gray-50 border rounded p-3">
                        <div class="text-xs text-gray-500 mb-1">Inspection Expiry</div>
                        <div class="text-sm font-medium text-gray-900">
                            {{ $formatDate($v->inspection_expiry_date ?? null) }}
                        </div>
                    </div>
                </div>

                @if(
                    empty($v->plate_number) ||
                    empty($v->vin) ||
                    empty($v->registration_expiry_date) ||
                    empty($v->insurance_expiry_date) ||
                    empty($v->current_mileage)
                )
                    <div class="mt-3 bg-yellow-50 border border-yellow-100 text-yellow-800 rounded p-3 text-xs">
                        <div class="font-semibold mb-1">Missing vehicle profile data:</div>

                        <div class="flex flex-wrap gap-1">
                            @if(empty($v->plate_number))
                                <span class="px-2 py-0.5 rounded-full bg-white border border-yellow-100">Plate number</span>
                            @endif

                            @if(empty($v->vin))
                                <span class="px-2 py-0.5 rounded-full bg-white border border-yellow-100">VIN</span>
                            @endif

                            @if(empty($v->registration_expiry_date))
                                <span class="px-2 py-0.5 rounded-full bg-white border border-yellow-100">Mulkia expiry</span>
                            @endif

                            @if(empty($v->insurance_expiry_date))
                                <span class="px-2 py-0.5 rounded-full bg-white border border-yellow-100">Insurance expiry</span>
                            @endif

                            @if(empty($v->current_mileage))
                                <span class="px-2 py-0.5 rounded-full bg-white border border-yellow-100">Current mileage</span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@else
    <div class="border border-dashed rounded-lg p-6 text-center">
        <p class="text-sm text-gray-500">No vehicles added yet.</p>

        @if(Route::has('admin.vehicles.create'))
            <a href="{{ route('admin.vehicles.create', ['client_id' => $client->id]) }}"
               class="inline-flex items-center mt-3 px-3 py-2 text-sm rounded bg-indigo-600 text-white hover:bg-indigo-700">
                + Add First Vehicle
            </a>
        @endif
    </div>
@endif