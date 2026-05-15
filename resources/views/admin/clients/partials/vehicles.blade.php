{{-- resources/views/admin/clients/partials/vehicles.blade.php --}}

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
            'class' => 'sf-badge-red',
            'card' => 'border-red-400/20 bg-red-500/10',
            'text' => 'text-red-300',
        ];
    }

    $parsedDate = \Illuminate\Support\Carbon::parse($date);

    if ($parsedDate->isPast()) {
        return [
            'label' => 'Expired',
            'class' => 'sf-badge-red',
            'card' => 'border-red-400/20 bg-red-500/10',
            'text' => 'text-red-300',
        ];
    }

    if ($parsedDate->diffInDays(now()) <= 30) {
        return [
            'label' => 'Due Soon',
            'class' => 'sf-badge-yellow',
            'card' => 'border-yellow-400/20 bg-yellow-500/10',
            'text' => 'text-yellow-300',
        ];
    }

    return [
        'label' => 'Valid',
        'class' => 'sf-badge-green',
        'card' => 'border-green-400/20 bg-green-500/10',
        'text' => 'text-green-300',
    ];
};
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="sf-section-title">
                Vehicles
            </h2>

            <p class="sf-section-subtitle">
                Vehicle details help complete the customer profile and power retention reminders.
            </p>
        </div>

        @if(Route::has('admin.vehicles.create'))
            <a href="{{ route('admin.vehicles.create', ['client_id' => $client->id]) }}" class="sf-btn-primary">
                + Add Vehicle
            </a>
        @endif
    </div>

    {{-- Opportunity fallback --}}
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

    {{-- Actual vehicles --}}
    @if($hasVehicles)
        <div class="space-y-4">
            @foreach ($vehicles as $v)
                @php
                    $registrationStatus = $statusBadge($v->registration_expiry_date ?? null);
                    $insuranceStatus = $statusBadge($v->insurance_expiry_date ?? null);

                    $makeName = optional($v->make)->name
                        ?? optional($v->vehicleMake)->name
                        ?? '';

                    $modelName = optional($v->model)->name
                        ?? optional($v->vehicleModel)->name
                        ?? '';

                    $vehicleTitle = trim(
                        ($v->year ?? '') . ' ' .
                        $makeName . ' ' .
                        $modelName
                    );

                    $vehicleTitle = $vehicleTitle !== '' ? $vehicleTitle : 'Vehicle #' . $v->id;

                    $brandInitials = $makeName
                        ? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $makeName), 0, 2))
                        : 'VH';

                    $missingItems = [];

                    if (empty($v->plate_number)) {
                        $missingItems[] = 'Plate number';
                    }

                    if (empty($v->vin)) {
                        $missingItems[] = 'VIN';
                    }

                    if (empty($v->registration_expiry_date)) {
                        $missingItems[] = 'Mulkia expiry';
                    }

                    if (empty($v->insurance_expiry_date)) {
                        $missingItems[] = 'Insurance expiry';
                    }

                    if (empty($v->current_mileage)) {
                        $missingItems[] = 'Current mileage';
                    }
                @endphp

                <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5 shadow-xl shadow-black/20 transition hover:border-orange-400/30 hover:bg-slate-900">

                    {{-- Vehicle Header --}}
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-orange-700 text-sm font-extrabold text-white shadow-lg shadow-orange-950/40">
                                {{ $brandInitials }}
                            </div>

                            <div class="min-w-0">
                                <div class="truncate text-lg font-extrabold text-white">
                                    {{ $vehicleTitle }}
                                </div>

                                <div class="mt-1 text-xs font-medium text-slate-500">
                                    Vehicle ID: #{{ $v->id }}
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @if(Route::has('admin.vehicles.edit'))
                                <a href="{{ route('admin.vehicles.edit', $v->id) }}" class="sf-btn-secondary">
                                    Edit Vehicle
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Core Vehicle Data --}}
                    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Plate Number
                            </div>

                            <div class="mt-1 text-sm font-bold text-slate-200">
                                {{ $v->plate_number ?? '—' }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                VIN
                            </div>

                            <div class="mt-1 break-all text-sm font-bold text-slate-200">
                                {{ $v->vin ?? '—' }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Current Mileage
                            </div>

                            <div class="mt-1 text-sm font-bold text-slate-200">
                                {{ $v->current_mileage ? number_format((int) $v->current_mileage) . ' km' : '—' }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Color
                            </div>

                            <div class="mt-1 text-sm font-bold text-slate-200">
                                {{ $v->color ?? '—' }}
                            </div>
                        </div>
                    </div>

                    {{-- Expiry / Inspection Data --}}
                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-2xl border {{ $registrationStatus['card'] }} p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="text-xs font-extrabold uppercase tracking-wide {{ $registrationStatus['text'] }}">
                                        Mulkia / Registration
                                    </div>

                                    <div class="mt-1 text-sm font-bold text-white">
                                        {{ $formatDate($v->registration_expiry_date ?? null) }}
                                    </div>
                                </div>

                                <span class="{{ $registrationStatus['class'] }}">
                                    {{ $registrationStatus['label'] }}
                                </span>
                            </div>
                        </div>

                        <div class="rounded-2xl border {{ $insuranceStatus['card'] }} p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="text-xs font-extrabold uppercase tracking-wide {{ $insuranceStatus['text'] }}">
                                        Insurance Expiry
                                    </div>

                                    <div class="mt-1 text-sm font-bold text-white">
                                        {{ $formatDate($v->insurance_expiry_date ?? null) }}
                                    </div>
                                </div>

                                <span class="{{ $insuranceStatus['class'] }}">
                                    {{ $insuranceStatus['label'] }}
                                </span>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Last Inspection
                            </div>

                            <div class="mt-1 text-sm font-bold text-slate-200">
                                {{ $formatDate($v->last_inspection_date ?? null) }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/5 p-3">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                Inspection Expiry
                            </div>

                            <div class="mt-1 text-sm font-bold text-slate-200">
                                {{ $formatDate($v->inspection_expiry_date ?? null) }}
                            </div>
                        </div>
                    </div>

                    {{-- Missing Profile Data --}}
                    @if(count($missingItems))
                        <div class="mt-4 rounded-2xl border border-yellow-400/20 bg-yellow-500/10 p-4">
                            <div class="font-extrabold text-yellow-300">
                                Missing vehicle profile data
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach($missingItems as $item)
                                    <span class="inline-flex rounded-full bg-yellow-500/10 px-2.5 py-1 text-xs font-bold text-yellow-200 ring-1 ring-yellow-400/20">
                                        {{ $item }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>
            @endforeach
        </div>
    @else
        <div class="sf-empty">
            <div>No vehicles added yet.</div>

            @if(Route::has('admin.vehicles.create'))
                <div class="mt-4">
                    <a href="{{ route('admin.vehicles.create', ['client_id' => $client->id]) }}" class="sf-btn-primary">
                        + Add First Vehicle
                    </a>
                </div>
            @endif
        </div>
    @endif

</div>