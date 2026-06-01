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

<div class="client-card relative min-h-[190px] overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/70 shadow-sm transition hover:-translate-y-0.5 hover:border-orange-400/30 hover:shadow-lg">

    {{-- Background Glow --}}
    <div class="pointer-events-none absolute -right-16 -top-16 h-32 w-32 rounded-full bg-orange-500/10 blur-2xl"></div>

    {{-- Vehicle Brand Stack --}}
    @if($vehicleCount > 0)
        <div class="absolute right-5 top-5 flex flex-col items-end">
            <div class="flex items-center justify-end -space-x-3">
                @foreach($vehicleLogoData as $vehicleLogo)
                    <div
                        class="inline-flex h-14 w-14 items-center justify-center overflow-hidden rounded-2xl border border-white/10 bg-white shadow-lg ring-1 ring-slate-200/80"
                        title="{{ $vehicleLogo['label'] ?: 'Vehicle' }}"
                    >
                        @if($vehicleLogo['logo'])
                            <img
                                src="{{ $vehicleLogo['logo'] }}"
                                alt="{{ $vehicleLogo['make'] ?? 'Vehicle' }} logo"
                                class="h-10 w-10 object-contain"
                            >
                        @else
                            <div class="text-xs font-extrabold text-slate-700">
                                {{ $vehicleLogo['initials'] }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            @if($vehicleCount > 3)
                <div class="mt-2">
                    <span class="inline-flex rounded-full bg-blue-500/10 px-2 py-0.5 text-[10px] font-black text-blue-300 ring-1 ring-blue-400/20">
                        +{{ $vehicleCount - 3 }}
                    </span>
                </div>
            @elseif($vehicleCount > 1)
                <div class="mt-2">
                    <span class="inline-flex rounded-full bg-blue-500/10 px-2 py-0.5 text-[10px] font-black text-blue-300 ring-1 ring-blue-400/20">
                        {{ $vehicleCount }} vehicles
                    </span>
                </div>
            @endif
        </div>
    @endif

    <div class="p-5">
        <div class="pr-28">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-orange-700 text-sm font-extrabold text-white shadow-lg shadow-orange-950/40">
                    {{ $clientInitial }}
                </div>

                <div class="min-w-0">
                    <h2 class="client-name truncate text-xl font-extrabold text-white">
                        {{ $client->name ?? 'Unnamed Client' }}
                    </h2>

                    <p class="client-email mt-1 truncate text-sm font-medium text-slate-500">
                        {{ $client->email ?? 'No email' }}
                    </p>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-1.5">
                @if($isVip)
                    <span class="inline-flex rounded-full bg-yellow-500/10 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-yellow-300 ring-1 ring-yellow-400/20">
                        VIP
                    </span>
                @endif

                @if($isNewCustomer)
                    <span class="inline-flex rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-emerald-300 ring-1 ring-emerald-400/20">
                        New
                    </span>
                @else
                    <span class="inline-flex rounded-full bg-blue-500/10 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-blue-300 ring-1 ring-blue-400/20">
                        Returning
                    </span>
                @endif

                @if($source)
                    <span class="inline-flex rounded-full bg-slate-800 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-slate-300 ring-1 ring-slate-700">
                        {{ str($source)->replace('_', ' ')->title() }}
                    </span>
                @endif
            </div>

            <p class="client-phone mt-4 text-sm font-bold text-slate-300">
                {{ $client->phone ?? $client->whatsapp ?? 'No phone' }}
            </p>

            <p class="client-vehicle mt-1 text-sm font-medium text-slate-500">
                @if($makeName || $modelName)
                    {{ trim(($makeName ?? '') . ' ' . ($modelName ?? '')) }}
                @else
                    No vehicle linked
                @endif
            </p>
        </div>

        <div class="mt-5 flex flex-wrap gap-2">
            <a
                href="{{ route('admin.clients.show', $client->id) }}"
                class="inline-flex h-8 items-center justify-center rounded-lg bg-orange-500/10 px-3 text-xs font-bold text-orange-300 transition hover:bg-orange-500/15"
            >
                View
            </a>

            <a
                href="{{ route('admin.clients.edit', $client->id) }}"
                class="inline-flex h-8 items-center justify-center rounded-lg border border-slate-700 bg-slate-800 px-3 text-xs font-bold text-slate-200 transition hover:bg-slate-700"
            >
                Edit
            </a>

            <a
                href="{{ route('admin.clients.bookings', $client->id) }}"
                class="inline-flex h-8 items-center justify-center rounded-lg bg-blue-500/10 px-3 text-xs font-bold text-blue-300 transition hover:bg-blue-500/15"
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
                    class="inline-flex h-8 items-center justify-center rounded-lg border border-slate-700 bg-slate-800 px-3 text-xs font-bold text-slate-200 transition hover:bg-slate-700"
                >
                    Archive
                </button>
            </form>
        </div>
    </div>
</div>