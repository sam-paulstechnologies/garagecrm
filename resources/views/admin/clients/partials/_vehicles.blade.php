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

<div class="space-y-5">

    {{-- Section Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="sf-section-title">
                Vehicles
            </h3>

            <p class="sf-section-subtitle">
                Vehicles linked to this client profile and garage service history.
            </p>
        </div>

        @if (\Illuminate\Support\Facades\Route::has('admin.vehicles.create'))
            <a href="{{ route('admin.vehicles.create', ['client_id' => $client->id]) }}"
               class="sf-btn-primary">
                + Add Vehicle
            </a>
        @endif
    </div>

    {{-- Vehicles from Opportunities --}}
    @if(!$hasVehicles && $opportunityVehicles->isNotEmpty())
        <div class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-xl shadow-black/20">
            <div class="text-sm font-extrabold text-blue-300">
                Vehicles from Opportunities
            </div>

            <p class="mt-1 text-xs font-medium text-blue-100/70">
                These vehicles were mentioned in opportunities but have not been added as client vehicles yet.
            </p>

            <ul class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach($opportunityVehicles as $label)
                    <li class="rounded-2xl border border-blue-400/20 bg-slate-950/50 px-4 py-3 text-sm font-bold text-white">
                        {{ $label }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Vehicles --}}
    @if($hasVehicles)
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach ($vehicles as $v)
                @php
                    $makeName = optional($v->make)->name
                        ?? optional($v->vehicleMake)->name
                        ?? '';

                    $modelName = optional($v->model)->name
                        ?? optional($v->vehicleModel)->name
                        ?? '';

                    $vehicleTitle = trim(($v->year ?? '') . ' ' . $makeName . ' ' . $modelName);

                    if ($vehicleTitle === '') {
                        $vehicleTitle = 'Vehicle #' . $v->id;
                    }

                    $registrationExpiry = $v->registration_expiry_date
                        ? \Illuminate\Support\Carbon::parse($v->registration_expiry_date)->format('Y-m-d')
                        : '—';

                    $insuranceExpiry = $v->insurance_expiry_date
                        ? \Illuminate\Support\Carbon::parse($v->insurance_expiry_date)->format('Y-m-d')
                        : '—';

                    $brandInitials = $makeName
                        ? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $makeName), 0, 2))
                        : 'VH';
                @endphp

                <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5 shadow-xl shadow-black/20 transition hover:border-orange-400/30 hover:bg-slate-900">

                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-orange-700 text-sm font-extrabold text-white shadow-lg shadow-orange-950/40">
                                    {{ $brandInitials }}
                                </div>

                                <div class="min-w-0">
                                    <div class="truncate text-lg font-extrabold text-white">
                                        {{ $vehicleTitle }}
                                    </div>

                                    <div class="mt-1 text-sm font-bold text-slate-300">
                                        Plate: {{ $v->plate_number ?? '—' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if(\Illuminate\Support\Facades\Route::has('admin.vehicles.edit'))
                            <a href="{{ route('admin.vehicles.edit', $v->id) }}" class="sf-link shrink-0">
                                Edit
                            </a>
                        @endif
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                                VIN
                            </div>

                            <div class="mt-1 break-all text-sm font-bold text-slate-200">
                                {{ $v->vin ?? '—' }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                                Registration Expiry
                            </div>

                            <div class="mt-1 text-sm font-bold text-slate-200">
                                {{ $registrationExpiry }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/5 p-3 sm:col-span-2">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                                Insurance Expiry
                            </div>

                            <div class="mt-1 text-sm font-bold text-slate-200">
                                {{ $insuranceExpiry }}
                            </div>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>
    @else
        <div class="sf-empty">
            No vehicles added yet.
        </div>
    @endif

</div>