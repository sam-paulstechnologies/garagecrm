{{-- resources/views/admin/clients/index-partials/_client_card.blade.php --}}

@php
    $vehicles = collect($client->vehicles ?? []);
    $firstVehicle = $vehicles->first();

    $vehicleCards = $vehicles->take(3)->values();

    $vehicleLogoData = $vehicleCards->map(function ($vehicle) {
        $makeName = $vehicle?->make?->name
            ?? $vehicle?->vehicleMake?->name
            ?? null;

        $modelName = $vehicle?->model?->name
            ?? $vehicle?->vehicleModel?->name
            ?? null;

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

        $brandInitials = $makeName
            ? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $makeName), 0, 2))
            : 'VH';

        return [
            'make' => $makeName,
            'model' => $modelName,
            'logo' => $brandLogoUrl,
            'initials' => $brandInitials,
            'label' => trim(($makeName ?? '') . ' ' . ($modelName ?? '')),
        ];
    });

    $firstLogo = $vehicleLogoData->first();

    $makeName = $firstLogo['make'] ?? null;
    $modelName = $firstLogo['model'] ?? null;

    $vehicleCount = $vehicles->count();

    $clientInitial = strtoupper(substr($client->name ?? 'C', 0, 1));

    $source = $client->source ?? null;
    $createdAt = $client->created_at ?? null;
    $isNewCustomer = $createdAt && $createdAt->gte(now()->subDays(30));
    $isVip = (bool) ($client->is_vip ?? false);
@endphp

@once
    <style>
        .sf-client-card {
            box-shadow: 0 12px 30px rgba(2, 6, 23, 0.18);
        }

        .sf-client-card:hover {
            box-shadow: 0 18px 42px rgba(2, 6, 23, 0.24);
        }

        .sf-client-badge,
        .sf-client-action,
        .sf-client-missing-vehicle {
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        .sf-client-action-view {
            color: #fed7aa;
            background: rgba(249, 115, 22, 0.14);
            border-color: rgba(251, 146, 60, 0.28);
        }

        .sf-client-action-view:hover {
            background: rgba(249, 115, 22, 0.22);
            color: #ffedd5;
        }

        .sf-client-action-edit,
        .sf-client-action-archive {
            color: #e2e8f0;
            background: rgba(30, 41, 59, 0.84);
            border-color: rgba(100, 116, 139, 0.55);
        }

        .sf-client-action-edit:hover,
        .sf-client-action-archive:hover {
            color: #ffffff;
            background: rgba(51, 65, 85, 0.92);
        }

        .sf-client-action-bookings {
            color: #bfdbfe;
            background: rgba(59, 130, 246, 0.14);
            border-color: rgba(96, 165, 250, 0.28);
        }

        .sf-client-action-bookings:hover {
            background: rgba(59, 130, 246, 0.22);
            color: #dbeafe;
        }

        .sf-client-badge-vip {
            color: #fde68a;
            background: rgba(234, 179, 8, 0.14);
            border-color: rgba(250, 204, 21, 0.30);
        }

        .sf-client-badge-new {
            color: #a7f3d0;
            background: rgba(16, 185, 129, 0.14);
            border-color: rgba(52, 211, 153, 0.28);
        }

        .sf-client-badge-returning {
            color: #bfdbfe;
            background: rgba(59, 130, 246, 0.14);
            border-color: rgba(96, 165, 250, 0.28);
        }

        .sf-client-badge-source {
            color: #e2e8f0;
            background: rgba(30, 41, 59, 0.84);
            border-color: rgba(100, 116, 139, 0.48);
        }

        .sf-client-missing-vehicle {
            color: #fde68a;
            background: rgba(245, 158, 11, 0.14);
            border-color: rgba(251, 191, 36, 0.36);
        }

        html[data-theme="light"] .sf-client-card {
            background: #ffffff !important;
            border-color: #d9e2ef !important;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08);
        }

        html[data-theme="light"] .sf-client-card:hover {
            border-color: rgba(249, 115, 22, 0.36) !important;
            box-shadow: 0 18px 44px rgba(15, 23, 42, 0.12);
        }

        html[data-theme="light"] .sf-client-muted {
            color: #475569 !important;
        }

        html[data-theme="light"] .sf-client-action-view {
            color: #9a3412 !important;
            background: #fff7ed !important;
            border-color: #fdba74 !important;
        }

        html[data-theme="light"] .sf-client-action-edit {
            color: #334155 !important;
            background: #f8fafc !important;
            border-color: #cbd5e1 !important;
        }

        html[data-theme="light"] .sf-client-action-bookings {
            color: #1d4ed8 !important;
            background: #eff6ff !important;
            border-color: #93c5fd !important;
        }

        html[data-theme="light"] .sf-client-action-archive {
            color: #9f1239 !important;
            background: #fff1f2 !important;
            border-color: #fecdd3 !important;
        }

        html[data-theme="light"] .sf-client-badge-vip {
            color: #92400e !important;
            background: #fef3c7 !important;
            border-color: #f59e0b !important;
        }

        html[data-theme="light"] .sf-client-badge-new {
            color: #047857 !important;
            background: #ecfdf5 !important;
            border-color: #6ee7b7 !important;
        }

        html[data-theme="light"] .sf-client-badge-returning {
            color: #1d4ed8 !important;
            background: #eff6ff !important;
            border-color: #93c5fd !important;
        }

        html[data-theme="light"] .sf-client-badge-source {
            color: #334155 !important;
            background: #f8fafc !important;
            border-color: #cbd5e1 !important;
        }

        html[data-theme="light"] .sf-client-missing-vehicle {
            color: #92400e !important;
            background: #fffbeb !important;
            border-color: #f59e0b !important;
        }
    </style>
@endonce

<div class="client-card sf-client-card relative min-h-[150px] overflow-hidden rounded-xl border border-slate-800 bg-slate-900/70 shadow-sm transition hover:-translate-y-0.5 hover:border-orange-400/30">

    {{-- Background Glow --}}
    <div class="pointer-events-none absolute -right-14 -top-14 h-28 w-28 rounded-full bg-orange-500/10 blur-2xl"></div>

    {{-- Vehicle Brand Stack --}}
    @if($vehicleCount > 0)
        <div class="absolute right-4 top-4 flex flex-col items-end">
            <div class="flex items-center justify-end -space-x-2">
                @foreach($vehicleLogoData as $vehicleLogo)
                    <div
                        class="inline-flex h-12 w-12 items-center justify-center overflow-hidden rounded-xl border border-white/10 bg-white shadow-md ring-1 ring-slate-200/80"
                        title="{{ $vehicleLogo['label'] ?: 'Vehicle' }}"
                    >
                        @if($vehicleLogo['logo'])
                            <img
                                src="{{ $vehicleLogo['logo'] }}"
                                alt="{{ $vehicleLogo['make'] ?? 'Vehicle' }} logo"
                                class="h-8 w-8 object-contain"
                            >
                        @else
                            <div class="text-[11px] font-extrabold text-slate-700">
                                {{ $vehicleLogo['initials'] }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            @if($vehicleCount > 3)
                <div class="mt-1.5">
                    <span class="inline-flex rounded-full bg-blue-500/10 px-2 py-0.5 text-[10px] font-black text-blue-300 ring-1 ring-blue-400/20">
                        +{{ $vehicleCount - 3 }}
                    </span>
                </div>
            @elseif($vehicleCount > 1)
                <div class="mt-1.5">
                    <span class="inline-flex rounded-full bg-blue-500/10 px-2 py-0.5 text-[10px] font-black text-blue-300 ring-1 ring-blue-400/20">
                        {{ $vehicleCount }} vehicles
                    </span>
                </div>
            @endif
        </div>
    @else
        <div
            class="sf-client-missing-vehicle absolute right-4 top-4 inline-flex h-12 w-12 items-center justify-center rounded-xl border text-amber-200"
            title="Vehicle missing"
            aria-label="Vehicle missing"
        >
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 8v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                <path d="M12 17h.01" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
            </svg>
        </div>
    @endif

    <div class="p-4">
        <div class="pr-24">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-orange-700 text-xs font-extrabold text-white shadow-md shadow-orange-950/30">
                    {{ $clientInitial }}
                </div>

                <div class="min-w-0">
                    <h2 class="client-name truncate text-base font-extrabold text-white">
                        {{ $client->name ?? 'Unnamed Client' }}
                    </h2>

                    <p class="client-email sf-client-muted mt-0.5 truncate text-xs font-semibold text-slate-400">
                        {{ $client->email ?? 'No email' }}
                    </p>
                </div>
            </div>

            <div class="mt-2.5 flex flex-wrap gap-1">
                @if($isVip)
                    <span class="sf-client-badge sf-client-badge-vip inline-flex rounded-full border px-1.5 py-0.5 text-[10px] font-black uppercase tracking-wide">
                        VIP
                    </span>
                @endif

                @if($isNewCustomer)
                    <span class="sf-client-badge sf-client-badge-new inline-flex rounded-full border px-1.5 py-0.5 text-[10px] font-black uppercase tracking-wide">
                        New
                    </span>
                @else
                    <span class="sf-client-badge sf-client-badge-returning inline-flex rounded-full border px-1.5 py-0.5 text-[10px] font-black uppercase tracking-wide">
                        Returning
                    </span>
                @endif

                @if($source)
                    <span class="sf-client-badge sf-client-badge-source inline-flex rounded-full border px-1.5 py-0.5 text-[10px] font-black uppercase tracking-wide">
                        {{ str($source)->replace('_', ' ')->title() }}
                    </span>
                @endif
            </div>

            <p class="client-phone mt-2.5 text-xs font-bold text-slate-300">
                {{ $client->phone ?? $client->whatsapp ?? 'No phone' }}
            </p>

            <p class="client-vehicle sf-client-muted mt-1 truncate text-xs font-semibold text-slate-400">
                @if($makeName || $modelName)
                    {{ trim(($makeName ?? '') . ' ' . ($modelName ?? '')) }}
                @else
                    <span class="sf-client-missing-vehicle inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-black uppercase tracking-wide">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 8v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            <path d="M12 17h.01" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
                        </svg>
                        Vehicle Missing
                    </span>
                @endif
            </p>
        </div>

        <div class="mt-3 flex flex-wrap gap-1.5">
            <a
                href="{{ route('admin.clients.show', $client->id) }}"
                class="sf-client-action sf-client-action-view inline-flex h-7 items-center justify-center rounded-md border px-2.5 text-[11px] font-extrabold transition"
            >
                View
            </a>

            <a
                href="{{ route('admin.clients.edit', $client->id) }}"
                class="sf-client-action sf-client-action-edit inline-flex h-7 items-center justify-center rounded-md border px-2.5 text-[11px] font-extrabold transition"
            >
                Edit
            </a>

            <a
                href="{{ route('admin.clients.bookings', $client->id) }}"
                class="sf-client-action sf-client-action-bookings inline-flex h-7 items-center justify-center rounded-md border px-2.5 text-[11px] font-extrabold transition"
            >
                Bookings
            </a>

            <form
                action="{{ route('admin.clients.archive', $client->id) }}"
                method="POST"
                onsubmit="return confirm('Archive this client?')"
                class="inline-block"
            >
                @csrf

                <button
                    type="submit"
                    class="sf-client-action sf-client-action-archive inline-flex h-7 items-center justify-center rounded-md border px-2.5 text-[11px] font-extrabold transition"
                >
                    Archive
                </button>
            </form>
        </div>
    </div>
</div>
