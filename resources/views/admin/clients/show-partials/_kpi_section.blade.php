{{-- resources/views/admin/clients/show-partials/_kpi_section.blade.php --}}

@php
    $profilePct = (int) ($kpis['profile_pct'] ?? 0);
    $missingItems = collect($kpis['missing_items'] ?? []);

    $lastService = $kpis['last_service'] ?? null;
    $nextService = $kpis['next_service'] ?? null;

    $lastServiceDisplay = $lastService
        ? \Illuminate\Support\Carbon::parse($lastService)->format('d M Y')
        : '—';

    $nextServiceDisplay = $nextService
        ? \Illuminate\Support\Carbon::parse($nextService)->format('d M Y')
        : '—';

    $primaryVehicle = $client->vehicles?->first();

    $clientEditRoute = \Illuminate\Support\Facades\Route::has('admin.clients.edit')
        ? route('admin.clients.edit', $client->id)
        : null;

    $vehicleEditRoute = ($primaryVehicle && \Illuminate\Support\Facades\Route::has('admin.vehicles.edit'))
        ? route('admin.vehicles.edit', $primaryVehicle->id)
        : null;

    $vehicleCreateRoute = \Illuminate\Support\Facades\Route::has('admin.vehicles.create')
        ? route('admin.vehicles.create', ['client_id' => $client->id])
        : null;

    $missingActionUrl = function ($item) use ($clientEditRoute, $vehicleEditRoute, $vehicleCreateRoute) {
        $key = strtolower((string) $item);

        if (str_contains($key, 'address') || str_contains($key, 'location')) {
            return $clientEditRoute ? $clientEditRoute . '#client-address' : null;
        }

        if (str_contains($key, 'phone') || str_contains($key, 'whatsapp')) {
            return $clientEditRoute ? $clientEditRoute . '#client-contact' : null;
        }

        if (str_contains($key, 'email')) {
            return $clientEditRoute ? $clientEditRoute . '#client-contact' : null;
        }

        if (str_contains($key, 'vehicle')) {
            return $vehicleCreateRoute ?: $vehicleEditRoute;
        }

        if (str_contains($key, 'plate')) {
            return $vehicleEditRoute ? $vehicleEditRoute . '#vehicle-plate' : $vehicleCreateRoute;
        }

        if (str_contains($key, 'vin')) {
            return $vehicleEditRoute ? $vehicleEditRoute . '#vehicle-vin' : $vehicleCreateRoute;
        }

        if (str_contains($key, 'mulkia') || str_contains($key, 'registration')) {
            return $vehicleEditRoute ? $vehicleEditRoute . '#vehicle-registration' : $vehicleCreateRoute;
        }

        if (str_contains($key, 'insurance')) {
            return $vehicleEditRoute ? $vehicleEditRoute . '#vehicle-insurance' : $vehicleCreateRoute;
        }

        if (str_contains($key, 'mileage')) {
            return $vehicleEditRoute ? $vehicleEditRoute . '#vehicle-mileage' : $vehicleCreateRoute;
        }

        return $clientEditRoute;
    };

    $kpiCards = [
        [
            'label' => 'Number of Cars',
            'value' => $kpis['cars'] ?? 0,
            'icon' => 'cars',
            'tone' => 'blue',
        ],
        [
            'label' => 'Lifetime Value',
            'value' => 'AED ' . number_format((float) ($kpis['ltv'] ?? 0), 2),
            'icon' => 'value',
            'tone' => 'orange',
        ],
        [
            'label' => 'Avg Spend / Visit',
            'value' => 'AED ' . number_format((float) ($kpis['avg_spend'] ?? 0), 2),
            'icon' => 'spend',
            'tone' => 'green',
        ],
        [
            'label' => 'Last Service',
            'value' => $lastServiceDisplay,
            'icon' => 'service',
            'tone' => 'slate',
        ],
        [
            'label' => 'Upcoming Service',
            'value' => $nextServiceDisplay,
            'icon' => 'upcoming',
            'tone' => 'yellow',
        ],
    ];
@endphp

<style>
    .sf-kpi-shell {
        border-color: rgba(30, 41, 59, 1);
        background: rgba(15, 23, 42, 0.70);
        color: #ffffff;
    }

    .sf-kpi-heading {
        color: #ffffff;
    }

    .sf-kpi-subtext {
        color: #cbd5e1;
    }

    .sf-kpi-profile-badge {
        border-color: rgba(250, 204, 21, 0.35);
        background: rgba(234, 179, 8, 0.18);
        color: #fef08a;
    }

    .sf-kpi-card {
        border-color: rgba(255, 255, 255, 0.08);
        background: rgba(2, 6, 23, 0.38);
    }

    .sf-kpi-label {
        color: #cbd5e1;
    }

    .sf-kpi-value {
        color: #ffffff;
    }

    .sf-kpi-blue {
        background: rgba(59, 130, 246, 0.10);
        border-color: rgba(96, 165, 250, 0.24);
    }

    .sf-kpi-blue .sf-kpi-label {
        color: #93c5fd;
    }

    .sf-kpi-orange {
        background: rgba(249, 115, 22, 0.10);
        border-color: rgba(251, 146, 60, 0.24);
    }

    .sf-kpi-orange .sf-kpi-label {
        color: #fdba74;
    }

    .sf-kpi-green {
        background: rgba(34, 197, 94, 0.10);
        border-color: rgba(74, 222, 128, 0.24);
    }

    .sf-kpi-green .sf-kpi-label {
        color: #86efac;
    }

    .sf-kpi-yellow {
        background: rgba(234, 179, 8, 0.10);
        border-color: rgba(250, 204, 21, 0.24);
    }

    .sf-kpi-yellow .sf-kpi-label {
        color: #fde047;
    }

    .sf-kpi-purple {
        background: rgba(139, 92, 246, 0.12);
        border-color: rgba(167, 139, 250, 0.28);
    }

    .sf-kpi-purple .sf-kpi-label {
        color: #c4b5fd;
    }

    .sf-kpi-slate {
        background: rgba(15, 23, 42, 0.44);
        border-color: rgba(148, 163, 184, 0.18);
    }

    .sf-kpi-slate .sf-kpi-label {
        color: #94a3b8;
    }

    .sf-kpi-icon {
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(255, 255, 255, 0.10);
    }

    .sf-kpi-icon svg {
        stroke-width: 2.2;
    }

    .sf-kpi-blue .sf-kpi-icon {
        background: rgba(59, 130, 246, 0.14);
        border-color: rgba(96, 165, 250, 0.30);
        color: #93c5fd;
    }

    .sf-kpi-orange .sf-kpi-icon {
        background: rgba(249, 115, 22, 0.14);
        border-color: rgba(251, 146, 60, 0.32);
        color: #fdba74;
    }

    .sf-kpi-green .sf-kpi-icon {
        background: rgba(16, 185, 129, 0.14);
        border-color: rgba(52, 211, 153, 0.30);
        color: #6ee7b7;
    }

    .sf-kpi-yellow .sf-kpi-icon {
        background: rgba(234, 179, 8, 0.14);
        border-color: rgba(250, 204, 21, 0.30);
        color: #fde68a;
    }

    .sf-kpi-purple .sf-kpi-icon {
        background: rgba(168, 85, 247, 0.14);
        border-color: rgba(192, 132, 252, 0.30);
        color: #d8b4fe;
    }

    .sf-kpi-slate .sf-kpi-icon {
        background: rgba(148, 163, 184, 0.14);
        border-color: rgba(203, 213, 225, 0.24);
        color: #cbd5e1;
    }

    .sf-kpi-progress-track {
        background: rgba(255, 255, 255, 0.10);
    }

    .sf-kpi-progress-fill {
        background: linear-gradient(90deg, #f97316, #facc15);
    }

    .sf-kpi-missing-pill {
        border-color: rgba(248, 113, 113, 0.22);
        background: rgba(239, 68, 68, 0.10);
        color: #fca5a5;
        transition: 0.18s ease;
    }

    .sf-kpi-missing-pill:hover {
        border-color: rgba(248, 113, 113, 0.50);
        background: rgba(239, 68, 68, 0.18);
        color: #fecaca;
        transform: translateY(-1px);
    }

    .sf-kpi-complete-pill {
        border-color: rgba(74, 222, 128, 0.24);
        background: rgba(34, 197, 94, 0.10);
        color: #86efac;
    }

    .sf-kpi-more-pill {
        border-color: rgba(148, 163, 184, 0.22);
        background: rgba(148, 163, 184, 0.10);
        color: #cbd5e1;
    }

    html[data-theme="light"] .sf-kpi-shell {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-kpi-heading {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-kpi-subtext {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-kpi-profile-badge {
        border-color: #eab308 !important;
        background: #fef9c3 !important;
        color: #713f12 !important;
    }

    html[data-theme="light"] .sf-kpi-card {
        background: #ffffff !important;
        border-color: #d9e1ec !important;
    }

    html[data-theme="light"] .sf-kpi-label {
        color: #64748b !important;
    }

    html[data-theme="light"] .sf-kpi-value {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-kpi-blue {
        background: #eff6ff !important;
        border-color: #bfdbfe !important;
    }

    html[data-theme="light"] .sf-kpi-blue .sf-kpi-label {
        color: #2563eb !important;
    }

    html[data-theme="light"] .sf-kpi-orange {
        background: #fff7ed !important;
        border-color: #fed7aa !important;
    }

    html[data-theme="light"] .sf-kpi-orange .sf-kpi-label {
        color: #ea580c !important;
    }

    html[data-theme="light"] .sf-kpi-green {
        background: #ecfdf5 !important;
        border-color: #bbf7d0 !important;
    }

    html[data-theme="light"] .sf-kpi-green .sf-kpi-label {
        color: #047857 !important;
    }

    html[data-theme="light"] .sf-kpi-yellow {
        background: #fefce8 !important;
        border-color: #fef08a !important;
    }

    html[data-theme="light"] .sf-kpi-yellow .sf-kpi-label {
        color: #a16207 !important;
    }

    html[data-theme="light"] .sf-kpi-purple {
        background: #faf5ff !important;
        border-color: #e9d5ff !important;
    }

    html[data-theme="light"] .sf-kpi-purple .sf-kpi-label {
        color: #7e22ce !important;
    }

    html[data-theme="light"] .sf-kpi-slate {
        background: #f8fafc !important;
        border-color: #d9e1ec !important;
    }

    html[data-theme="light"] .sf-kpi-slate .sf-kpi-label {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-kpi-icon {
        background: rgba(255, 255, 255, 0.75) !important;
        border-color: rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-kpi-blue .sf-kpi-icon {
        background: #dbeafe !important;
        border-color: #bfdbfe !important;
        color: #2563eb !important;
    }

    html[data-theme="light"] .sf-kpi-orange .sf-kpi-icon {
        background: #ffedd5 !important;
        border-color: #fed7aa !important;
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-kpi-green .sf-kpi-icon {
        background: #d1fae5 !important;
        border-color: #a7f3d0 !important;
        color: #047857 !important;
    }

    html[data-theme="light"] .sf-kpi-yellow .sf-kpi-icon {
        background: #fef9c3 !important;
        border-color: #fde68a !important;
        color: #a16207 !important;
    }

    html[data-theme="light"] .sf-kpi-purple .sf-kpi-icon {
        background: #f3e8ff !important;
        border-color: #e9d5ff !important;
        color: #7e22ce !important;
    }

    html[data-theme="light"] .sf-kpi-slate .sf-kpi-icon {
        background: #f1f5f9 !important;
        border-color: #e2e8f0 !important;
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-kpi-progress-track {
        background: #e2e8f0 !important;
    }

    html[data-theme="light"] .sf-kpi-missing-pill {
        border-color: #fecaca !important;
        background: #fef2f2 !important;
        color: #b91c1c !important;
    }

    html[data-theme="light"] .sf-kpi-missing-pill:hover {
        border-color: #f87171 !important;
        background: #fee2e2 !important;
        color: #7f1d1d !important;
    }

    html[data-theme="light"] .sf-kpi-complete-pill {
        border-color: #bbf7d0 !important;
        background: #ecfdf5 !important;
        color: #047857 !important;
    }

    html[data-theme="light"] .sf-kpi-more-pill {
        border-color: #cbd5e1 !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
    }
</style>

<section id="client-kpis" class="sf-kpi-shell rounded-2xl border p-4 shadow-sm sm:p-5">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="sf-kpi-heading text-lg font-extrabold tracking-tight">
                Client KPIs
            </h2>

            <p class="sf-kpi-subtext mt-1 text-xs font-semibold sm:text-sm">
                Snapshot of vehicles, spend, service dates, and profile health.
            </p>
        </div>

        <span class="sf-kpi-profile-badge inline-flex w-fit rounded-full border px-3 py-1 text-xs font-black sm:text-sm">
            Profile {{ $profilePct }}%
        </span>
    </div>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
        @foreach($kpiCards as $card)
            <div class="sf-kpi-card sf-kpi-{{ $card['tone'] }} min-h-[112px] rounded-xl border p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="sf-kpi-label text-xs font-black uppercase tracking-wide">
                            {{ $card['label'] }}
                        </p>

                        <p class="sf-kpi-value mt-3 break-words text-2xl font-black tracking-tight">
                            {{ $card['value'] }}
                        </p>
                    </div>

                    <div class="sf-kpi-icon flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border">
                        @switch($card['icon'])
                            @case('cars')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M5 17h14" />
                                    <path d="M6 17l1.4-5.6A2 2 0 0 1 9.34 10h5.32a2 2 0 0 1 1.94 1.4L18 17" />
                                    <path d="M7 17v2" />
                                    <path d="M17 17v2" />
                                    <circle cx="8" cy="17" r="1.5" />
                                    <circle cx="16" cy="17" r="1.5" />
                                    <path d="M9 7h6" />
                                </svg>
                                @break

                            @case('value')
                                <svg class="h-5 w-5" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-label="UAE Dirham" role="img">
                                    <path d="M8 7.5h8.3c5 0 8.7 3.6 8.7 8.5s-3.7 8.5-8.7 8.5H8" />
                                    <path d="M12 7.5v17" />
                                    <path d="M5 12h21" />
                                    <path d="M5 20h21" />
                                </svg>
                                @break

                            @case('spend')
                                <svg class="h-5 w-5" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-label="UAE Dirham" role="img">
                                    <path d="M8 7.5h8.3c5 0 8.7 3.6 8.7 8.5s-3.7 8.5-8.7 8.5H8" />
                                    <path d="M12 7.5v17" />
                                    <path d="M5 12h21" />
                                    <path d="M5 20h21" />
                                </svg>
                                @break

                            @case('service')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M14.7 6.3a4 4 0 0 0-5 5L4 17v3h3l5.7-5.7a4 4 0 0 0 5-5l-2.4 2.4-3-3 2.4-2.4Z" />
                                </svg>
                                @break

                            @case('upcoming')
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M7 3v3" />
                                    <path d="M17 3v3" />
                                    <rect x="4" y="5" width="16" height="15" rx="2" />
                                    <path d="M4 10h16" />
                                    <path d="M9 15h4" />
                                    <path d="M13 15l-2 2" />
                                </svg>
                                @break
                        @endswitch
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Profile Completion --}}
        <div class="sf-kpi-card sf-kpi-purple rounded-xl border p-4 md:col-span-2 xl:col-span-1">
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2">
                    <div class="sf-kpi-icon flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 11l2 2 4-4" />
                            <path d="M5 4h14v16H5z" />
                            <path d="M8 17h8" />
                        </svg>
                    </div>

                    <p class="sf-kpi-label min-w-0 text-xs font-black uppercase tracking-wide">
                        Profile Completion
                    </p>
                </div>

                <p class="sf-kpi-value text-sm font-black">
                    {{ $profilePct }}%
                </p>
            </div>

            <div class="sf-kpi-progress-track mt-4 h-2.5 overflow-hidden rounded-full">
                <div
                    class="sf-kpi-progress-fill h-full rounded-full"
                    style="width: {{ min(100, max(0, $profilePct)) }}%;"
                ></div>
            </div>

            <div class="mt-4">
                <p class="sf-kpi-label text-xs font-black uppercase tracking-wide">
                    Missing
                </p>

                <div class="mt-2.5 flex flex-wrap gap-2">
                    @forelse($missingItems->take(5) as $missingItem)
                        @php
                            $url = $missingActionUrl($missingItem);
                        @endphp

                        @if($url)
                            <a
                                href="{{ $url }}"
                                class="sf-kpi-missing-pill inline-flex rounded-full border px-3 py-1 text-xs font-bold"
                                title="Click to update {{ $missingItem }}"
                            >
                                {{ $missingItem }}
                            </a>
                        @else
                            <span class="sf-kpi-missing-pill inline-flex rounded-full border px-3 py-1 text-xs font-bold">
                                {{ $missingItem }}
                            </span>
                        @endif
                    @empty
                        <span class="sf-kpi-complete-pill inline-flex rounded-full border px-3 py-1 text-xs font-bold">
                            Complete profile
                        </span>
                    @endforelse

                    @if($missingItems->count() > 5)
                        @php
                            $moreUrl = $vehicleEditRoute ?: $vehicleCreateRoute ?: $clientEditRoute;
                        @endphp

                        @if($moreUrl)
                            <a
                                href="{{ $moreUrl }}"
                                class="sf-kpi-more-pill inline-flex rounded-full border px-3 py-1 text-xs font-bold"
                                title="Click to update remaining missing profile data"
                            >
                                +{{ $missingItems->count() - 5 }} more
                            </a>
                        @else
                            <span class="sf-kpi-more-pill inline-flex rounded-full border px-3 py-1 text-xs font-bold">
                                +{{ $missingItems->count() - 5 }} more
                            </span>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
