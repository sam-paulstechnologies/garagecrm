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
        border-color: rgba(251, 191, 36, 0.22);
        background: rgba(245, 158, 11, 0.08);
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
        background: rgba(234, 179, 8, 0.10);
        color: #fef08a;
    }

    .sf-vehicles-empty {
        border-color: rgba(148, 163, 184, 0.24);
        background: rgba(2, 6, 23, 0.36);
        color: #cbd5e1;
    }

    .sf-vehicle-icon {
        border-color: rgba(251, 146, 60, 0.34);
        background: linear-gradient(135deg, rgba(249, 115, 22, 0.95), rgba(194, 65, 12, 0.95));
        color: #ffffff;
        box-shadow: 0 12px 24px rgba(154, 52, 18, 0.26);
    }

    .sf-vehicle-icon svg,
    .sf-vehicle-missing-icon svg,
    .sf-vehicles-empty-icon svg {
        stroke-width: 2.2;
    }

    .sf-vehicle-missing-icon {
        border-color: rgba(250, 204, 21, 0.34);
        background: rgba(234, 179, 8, 0.16);
        color: #fde68a;
    }

    .sf-vehicles-empty-icon {
        border-color: rgba(251, 146, 60, 0.28);
        background: rgba(249, 115, 22, 0.14);
        color: #fdba74;
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
        border-color: #fde68a !important;
        background: #fffbeb !important;
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

    html[data-theme="light"] .sf-vehicle-icon {
        border-color: #fed7aa !important;
        background: linear-gradient(135deg, #fb923c, #ea580c) !important;
        color: #ffffff !important;
        box-shadow: 0 12px 24px rgba(234, 88, 12, 0.22) !important;
    }

    html[data-theme="light"] .sf-vehicle-missing-icon {
        border-color: #facc15 !important;
        background: #fef9c3 !important;
        color: #a16207 !important;
    }

    html[data-theme="light"] .sf-vehicles-empty-icon {
        border-color: #fed7aa !important;
        background: #ffedd5 !important;
        color: #c2410c !important;
    }
</style>

<section id="vehicles" class="sf-vehicles-shell rounded-2xl border p-4 shadow-sm sm:p-5">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="sf-vehicles-title text-lg font-extrabold tracking-tight">
                Vehicles
            </h2>

            <p class="sf-vehicles-muted mt-1 text-xs font-semibold sm:text-sm">
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

    <div class="space-y-3">
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

                $makeName = is_string($makeName) ? trim($makeName) : null;
                $modelName = is_string($modelName) ? trim($modelName) : null;
                $vehicleName = trim(($makeName ?? '') . ' ' . ($modelName ?? ''));

                if ($vehicleName === '') {
                    $vehicleName = 'Vehicle details missing';
                }

                $brandSlug = $makeName
                    ? strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($makeName)))
                    : null;
                $brandSlug = $brandSlug ? trim($brandSlug, '-') : null;
                $brandLogoPath = $brandSlug
                    ? public_path("images/car-brands/{$brandSlug}.png")
                    : null;
                $brandLogoUrl = ($brandLogoPath && file_exists($brandLogoPath))
                    ? asset("images/car-brands/{$brandSlug}.png")
                    : null;

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

            <div class="sf-vehicle-card rounded-xl border p-4">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex min-w-0 items-center gap-4">
                        <div
                            class="{{ $brandLogoUrl ? 'flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-white/10 bg-white shadow-md ring-1 ring-slate-200/80' : 'sf-vehicle-icon flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl border' }}"
                            title="{{ $vehicleName }}"
                        >
                            @if($brandLogoUrl)
                                <img
                                    src="{{ $brandLogoUrl }}"
                                    alt="{{ $makeName ?? 'Vehicle' }} logo"
                                    class="h-8 w-8 object-contain"
                                >
                            @else
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 17h14" />
                                    <path d="M6 17l1.4-5.6A2 2 0 0 1 9.34 10h5.32a2 2 0 0 1 1.94 1.4L18 17" />
                                    <path d="M7 17v2" />
                                    <path d="M17 17v2" />
                                    <circle cx="8" cy="17" r="1.5" />
                                    <circle cx="16" cy="17" r="1.5" />
                                </svg>
                                <span class="mt-0.5 text-[9px] font-black leading-none tracking-wide">
                                    {{ $vehicleInitials }}
                                </span>
                            @endif
                        </div>

                        <div class="min-w-0">
                            <h3 class="sf-vehicles-title truncate text-lg font-extrabold">
                                {{ $vehicleName }}
                            </h3>

                            @if($makeName || $modelName)
                                <p class="sf-vehicles-muted mt-1 text-sm font-semibold">
                                    {{ $makeName && $modelName ? 'Make and model' : 'Partial vehicle details' }}
                                </p>
                            @else
                                <p class="sf-vehicles-muted mt-1 text-sm font-semibold">
                                    Add make and model from Edit Vehicle.
                                </p>
                            @endif
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
                    <div class="sf-vehicle-box min-w-0 rounded-xl border p-3">
                        <p class="sf-vehicle-label text-xs font-black uppercase tracking-wide">
                            Plate Number
                        </p>
                        <p class="sf-vehicle-value mt-2 break-words text-base font-black">
                            {{ $plateNumber ?: '—' }}
                        </p>
                    </div>

                    <div class="sf-vehicle-box min-w-0 rounded-xl border p-3">
                        <p class="sf-vehicle-label text-xs font-black uppercase tracking-wide">
                            VIN
                        </p>
                        <p class="sf-vehicle-value mt-2 break-all text-base font-black">
                            {{ $vin ?: '—' }}
                        </p>
                    </div>

                    <div class="sf-vehicle-box min-w-0 rounded-xl border p-3">
                        <p class="sf-vehicle-label text-xs font-black uppercase tracking-wide">
                            Current Mileage
                        </p>
                        <p class="sf-vehicle-value mt-2 break-words text-base font-black">
                            {{ $currentMileage ? number_format((float) $currentMileage) : '—' }}
                        </p>
                    </div>

                    <div class="sf-vehicle-box min-w-0 rounded-xl border p-3">
                        <p class="sf-vehicle-label text-xs font-black uppercase tracking-wide">
                            Color
                        </p>
                        <p class="sf-vehicle-value mt-2 break-words text-base font-black">
                            {{ $color ?: '—' }}
                        </p>
                    </div>

                    <div class="{{ !$registrationExpiry ? 'sf-vehicle-box-danger' : 'sf-vehicle-box' }} min-w-0 rounded-xl border p-3">
                        <p class="sf-vehicle-label text-xs font-black uppercase tracking-wide">
                            Mulkia / Registration
                        </p>
                        <p class="sf-vehicle-value mt-2 break-words text-base font-black">
                            {{ $formatDate($registrationExpiry) }}
                        </p>
                    </div>

                    <div class="{{ !$insuranceExpiry ? 'sf-vehicle-box-danger' : 'sf-vehicle-box' }} min-w-0 rounded-xl border p-3">
                        <p class="sf-vehicle-label text-xs font-black uppercase tracking-wide">
                            Insurance Expiry
                        </p>
                        <p class="sf-vehicle-value mt-2 break-words text-base font-black">
                            {{ $formatDate($insuranceExpiry) }}
                        </p>
                    </div>

                    <div class="sf-vehicle-box min-w-0 rounded-xl border p-3">
                        <p class="sf-vehicle-label text-xs font-black uppercase tracking-wide">
                            Last Inspection
                        </p>
                        <p class="sf-vehicle-value mt-2 break-words text-base font-black">
                            {{ $formatDate($lastInspection) }}
                        </p>
                    </div>

                    <div class="sf-vehicle-box min-w-0 rounded-xl border p-3">
                        <p class="sf-vehicle-label text-xs font-black uppercase tracking-wide">
                            Inspection Expiry
                        </p>
                        <p class="sf-vehicle-value mt-2 break-words text-base font-black">
                            {{ $formatDate($inspectionExpiry) }}
                        </p>
                    </div>
                </div>

                @if($missingItems->isNotEmpty())
                    <div class="sf-vehicle-box-warning mt-3 rounded-xl border p-3">
                        <h4 class="sf-vehicle-warning-title inline-flex items-center gap-2 text-sm font-extrabold">
                            <span class="sf-vehicle-missing-icon flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 9v4" />
                                    <path d="M12 17h.01" />
                                    <path d="M10.3 4.3 2.6 18a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0Z" />
                                </svg>
                            </span>
                            Missing vehicle profile data
                        </h4>

                        <div class="mt-2.5 flex flex-wrap gap-1.5">
                            @foreach($missingItems as $item)
                                <span class="sf-vehicle-warning-pill inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-bold leading-5">
                                    {{ $item }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="sf-vehicles-empty rounded-2xl border border-dashed p-8 text-center">
                <div class="sf-vehicles-empty-icon mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl border">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 17h9" />
                        <path d="M6 17l1.4-5.6A2 2 0 0 1 9.34 10h4.32a2 2 0 0 1 1.94 1.4L17 17" />
                        <path d="M7 17v2" />
                        <circle cx="8" cy="17" r="1.5" />
                        <path d="M18 12v6" />
                        <path d="M15 15h6" />
                    </svg>
                </div>

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
