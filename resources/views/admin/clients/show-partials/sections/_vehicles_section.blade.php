{{-- resources/views/admin/clients/show-partials/sections/_vehicles_section.blade.php --}}

@php
    $vehicles = collect($client->vehicles ?? []);

    $vehicleCreateRoute = \Illuminate\Support\Facades\Route::has('admin.vehicles.create')
        ? route('admin.vehicles.create', ['client_id' => $client->id])
        : null;

    $vehicleEditRoute = function ($vehicle) {
        return \Illuminate\Support\Facades\Route::has('admin.vehicles.edit')
            ? route('admin.vehicles.edit', $vehicle->id)
            : null;
    };

    $formatDate = function ($value) {
        if (!$value) {
            return '—';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d M Y');
        } catch (\Throwable $e) {
            return $value;
        }
    };
@endphp

<style>
    .sf-vehicles-shell {
        border-color: rgba(30, 41, 59, 1);
        background: rgba(15, 23, 42, 0.70);
        color: #ffffff;
    }

    .sf-vehicles-title {
        color: #ffffff;
    }

    .sf-vehicles-muted {
        color: #cbd5e1;
    }

    .sf-vehicle-card {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(2, 6, 23, 0.36);
        color: #ffffff;
    }

    .sf-vehicle-box {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(15, 23, 42, 0.55);
    }

    .sf-vehicle-box-danger {
        border-color: rgba(248, 113, 113, 0.24);
        background: rgba(239, 68, 68, 0.10);
    }

    .sf-vehicle-box-warning {
        border-color: rgba(250, 204, 21, 0.24);
        background: rgba(234, 179, 8, 0.10);
    }

    .sf-vehicle-label {
        color: #94a3b8;
    }

    .sf-vehicle-value {
        color: #ffffff;
    }

    .sf-vehicle-warning-title {
        color: #fef08a;
    }

    .sf-vehicle-warning-pill {
        border-color: rgba(250, 204, 21, 0.24);
        background: rgba(234, 179, 8, 0.12);
        color: #fef08a;
    }

    .sf-vehicles-empty {
        border-color: rgba(148, 163, 184, 0.24);
        background: rgba(2, 6, 23, 0.36);
        color: #cbd5e1;
    }

    html[data-theme="light"] .sf-vehicles-shell {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-vehicles-title {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-vehicles-muted {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-vehicle-card {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-vehicle-box {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-vehicle-box-danger {
        border-color: #fecaca !important;
        background: #fef2f2 !important;
    }

    html[data-theme="light"] .sf-vehicle-box-warning {
        border-color: #fef08a !important;
        background: #fefce8 !important;
    }

    html[data-theme="light"] .sf-vehicle-label {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-vehicle-value {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-vehicle-warning-title {
        color: #a16207 !important;
    }

    html[data-theme="light"] .sf-vehicle-warning-pill {
        border-color: #facc15 !important;
        background: #fef9c3 !important;
        color: #713f12 !important;
    }

    html[data-theme="light"] .sf-vehicles-empty {
        border-color: #d9e1ec !important;
        background: #f8fafc !important;
        color: #475569 !important;
    }
</style>

<section id="vehicles" class="sf-vehicles-shell rounded-2xl border p-5 shadow-sm">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="sf-vehicles-title text-lg font-extrabold tracking-tight">
                Vehicles
            </h2>

            <p class="sf-vehicles-muted mt-1 text-sm font-medium">
                Vehicle details help complete the customer profile and power retention reminders.
            </p>
        </div>

        @if($vehicleCreateRoute)
            <a
                href="{{ $vehicleCreateRoute }}"
                class="inline-flex h-10 w-fit items-center justify-center rounded-xl bg-orange-500 px-5 text-sm font-extrabold text-white shadow-lg shadow-orange-950/20 transition hover:bg-orange-600"
            >
                + Add Vehicle
            </a>
        @endif
    </div>

    <div class="space-y-4">
        @forelse($vehicles as $vehicle)
            @php
                $makeName = $vehicle->make->name
                    ?? $vehicle->vehicleMake->name
                    ?? $vehicle->make
                    ?? $vehicle->other_make
                    ?? null;

                $modelName = $vehicle->model->name
                    ?? $vehicle->vehicleModel->name
                    ?? $vehicle->model
                    ?? $vehicle->other_model
                    ?? null;

                $vehicleName = trim(($makeName ?? '') . ' ' . ($modelName ?? ''));

                if ($vehicleName === '') {
                    $vehicleName = $vehicle->name ?? 'Vehicle';
                }

                $vehicleInitials = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $makeName ?: $vehicleName), 0, 2)) ?: 'VH';

                $plateNumber = $vehicle->plate_number ?? $vehicle->plate ?? null;
                $vin = $vehicle->vin ?? null;
                $currentMileage = $vehicle->current_mileage ?? $vehicle->mileage ?? null;
                $color = $vehicle->color ?? null;
                $registrationExpiry = $vehicle->registration_expiry_date ?? $vehicle->mulkia_expiry_date ?? null;
                $insuranceExpiry = $vehicle->insurance_expiry_date ?? null;
                $lastInspection = $vehicle->last_inspection_date ?? null;
                $inspectionExpiry = $vehicle->inspection_expiry_date ?? null;

                $missingItems = collect();

                if (!$plateNumber) {
                    $missingItems->push('Plate number');
                }

                if (!$vin) {
                    $missingItems->push('VIN');
                }

                if (!$registrationExpiry) {
                    $missingItems->push('Mulkia expiry');
                }

                if (!$insuranceExpiry) {
                    $missingItems->push('Insurance expiry');
                }

                if (!$currentMileage) {
                    $missingItems->push('Current mileage');
                }

                $editRoute = $vehicleEditRoute($vehicle);
            @endphp

            <div class="sf-vehicle-card rounded-2xl border p-5">
                <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-orange-700 text-lg font-black text-white shadow-lg shadow-orange-950/25">
                            {{ $vehicleInitials }}
                        </div>

                        <div class="min-w-0">
                            <h3 class="sf-vehicles-title truncate text-xl font-extrabold">
                                {{ $vehicleName }}
                            </h3>

                            <p class="sf-vehicles-muted mt-1 text-sm font-semibold">
                                Vehicle ID: #{{ $vehicle->id }}
                            </p>
                        </div>
                    </div>

                    @if($editRoute)
                        <a
                            href="{{ $editRoute }}"
                            class="inline-flex h-10 w-fit items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 text-sm font-bold text-slate-200 transition hover:bg-slate-700"
                        >
                            Edit Vehicle
                        </a>
                    @endif
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="sf-vehicle-box rounded-2xl border p-4">
                        <p class="sf-vehicle-label text-xs font-black uppercase tracking-wide">
                            Plate Number
                        </p>
                        <p class="sf-vehicle-value mt-3 text-lg font-black">
                            {{ $plateNumber ?: '—' }}
                        </p>
                    </div>

                    <div class="sf-vehicle-box rounded-2xl border p-4">
                        <p class="sf-vehicle-label text-xs font-black uppercase tracking-wide">
                            VIN
                        </p>
                        <p class="sf-vehicle-value mt-3 truncate text-lg font-black">
                            {{ $vin ?: '—' }}
                        </p>
                    </div>

                    <div class="sf-vehicle-box rounded-2xl border p-4">
                        <p class="sf-vehicle-label text-xs font-black uppercase tracking-wide">
                            Current Mileage
                        </p>
                        <p class="sf-vehicle-value mt-3 text-lg font-black">
                            {{ $currentMileage ? number_format((float) $currentMileage) : '—' }}
                        </p>
                    </div>

                    <div class="sf-vehicle-box rounded-2xl border p-4">
                        <p class="sf-vehicle-label text-xs font-black uppercase tracking-wide">
                            Color
                        </p>
                        <p class="sf-vehicle-value mt-3 text-lg font-black">
                            {{ $color ?: '—' }}
                        </p>
                    </div>

                    <div class="sf-vehicle-box-danger rounded-2xl border p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="sf-vehicle-label text-xs font-black uppercase tracking-wide">
                                    Mulkia / Registration
                                </p>
                                <p class="sf-vehicle-value mt-3 text-lg font-black">
                                    {{ $formatDate($registrationExpiry) }}
                                </p>
                            </div>

                            @if(!$registrationExpiry)
                                <span class="rounded-full bg-red-500/10 px-3 py-1 text-xs font-black text-red-300 ring-1 ring-red-400/20">
                                    Missing
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="sf-vehicle-box-danger rounded-2xl border p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="sf-vehicle-label text-xs font-black uppercase tracking-wide">
                                    Insurance Expiry
                                </p>
                                <p class="sf-vehicle-value mt-3 text-lg font-black">
                                    {{ $formatDate($insuranceExpiry) }}
                                </p>
                            </div>

                            @if(!$insuranceExpiry)
                                <span class="rounded-full bg-red-500/10 px-3 py-1 text-xs font-black text-red-300 ring-1 ring-red-400/20">
                                    Missing
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="sf-vehicle-box rounded-2xl border p-4">
                        <p class="sf-vehicle-label text-xs font-black uppercase tracking-wide">
                            Last Inspection
                        </p>
                        <p class="sf-vehicle-value mt-3 text-lg font-black">
                            {{ $formatDate($lastInspection) }}
                        </p>
                    </div>

                    <div class="sf-vehicle-box rounded-2xl border p-4">
                        <p class="sf-vehicle-label text-xs font-black uppercase tracking-wide">
                            Inspection Expiry
                        </p>
                        <p class="sf-vehicle-value mt-3 text-lg font-black">
                            {{ $formatDate($inspectionExpiry) }}
                        </p>
                    </div>
                </div>

                @if($missingItems->isNotEmpty())
                    <div class="sf-vehicle-box-warning mt-4 rounded-2xl border p-4">
                        <h4 class="sf-vehicle-warning-title text-base font-extrabold">
                            Missing vehicle profile data
                        </h4>

                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($missingItems as $item)
                                <span class="sf-vehicle-warning-pill rounded-full border px-3 py-1 text-xs font-bold">
                                    {{ $item }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="sf-vehicles-empty rounded-2xl border border-dashed p-8 text-center">
                <p class="text-sm font-semibold">
                    No vehicles added yet.
                </p>

                @if($vehicleCreateRoute)
                    <a
                        href="{{ $vehicleCreateRoute }}"
                        class="mt-4 inline-flex h-10 items-center justify-center rounded-xl bg-orange-500 px-4 text-sm font-extrabold text-white transition hover:bg-orange-600"
                    >
                        + Add First Vehicle
                    </a>
                @endif
            </div>
        @endforelse
    </div>
</section>
