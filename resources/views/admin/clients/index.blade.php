@extends('layouts.app')

@section('title', 'Clients')

@section('content')
<div class="sf-page space-y-6">

    {{-- Import Success Message --}}
    @if(session('import_success'))
        <div class="sf-alert-success relative">
            <strong class="font-extrabold">Upload Successful!</strong>

            <span class="block sm:inline">
                {{ session('imported') }} out of {{ session('total') }} clients imported.
                @if(session('skipped') > 0)
                    {{ session('skipped') }} skipped due to duplicates or errors.
                @endif
            </span>

            <span onclick="this.parentElement.remove()"
                  class="absolute bottom-0 right-0 top-0 cursor-pointer px-4 py-3 text-green-200 hover:text-white">
                &times;
            </span>
        </div>
    @endif

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Client Management
            </div>

            <h1 class="sf-page-title mt-3">
                Clients
            </h1>

            <p class="sf-page-subtitle">
                Manage client profiles, contact details, vehicles, bookings, service history, and CRM activity.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.clients.archived') }}" class="sf-btn-secondary">
                View Archived
            </a>

            <a href="{{ route('admin.clients.import.form') }}" class="sf-btn-soft-blue">
                Import Clients
            </a>

            <a href="{{ route('admin.clients.create') }}" class="sf-btn-primary">
                + Add Client
            </a>
        </div>
    </div>

    {{-- Search --}}
    <div class="sf-card">
        <div class="sf-card-body">
            <label for="client-search" class="sf-label">
                Search Clients
            </label>

            <input type="text"
                   id="client-search"
                   placeholder="Search clients, phone, email, vehicle make/model..."
                   class="sf-input">

            <p class="sf-help">
                Search works instantly across visible client cards.
            </p>
        </div>
    </div>

    {{-- Client Cards --}}
    <div id="client-cards" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($clients as $client)
            @php
                $vehicles = $client->vehicles ?? collect();
                $firstVehicle = $vehicles->first();

                $makeName = $firstVehicle?->make?->name
                    ?? $firstVehicle?->vehicleMake?->name
                    ?? null;

                $modelName = $firstVehicle?->model?->name
                    ?? $firstVehicle?->vehicleModel?->name
                    ?? null;

                $vehicleCount = $vehicles->count();

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
            @endphp

            <div class="client-card sf-card sf-card-hover relative min-h-[180px] overflow-hidden">

                {{-- Background Glow --}}
                <div class="pointer-events-none absolute -right-16 -top-16 h-32 w-32 rounded-full bg-orange-500/10 blur-2xl"></div>

                {{-- Small Car Brand Logo --}}
                @if($firstVehicle)
                    <div class="absolute right-5 top-5 text-right">
                        <div class="inline-flex h-14 w-14 items-center justify-center overflow-hidden rounded-2xl border border-white/10 bg-slate-950/70 shadow-lg shadow-black/20">
                            @if($brandLogoUrl)
                                <img src="{{ $brandLogoUrl }}"
                                     alt="{{ $makeName }} logo"
                                     class="h-10 w-10 object-contain">
                            @else
                                <div class="text-xs font-extrabold text-slate-300">
                                    {{ $brandInitials }}
                                </div>
                            @endif
                        </div>

                        @if($vehicleCount > 1)
                            <div class="mt-1">
                                <span class="sf-badge-blue">
                                    +{{ $vehicleCount - 1 }}
                                </span>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="sf-card-body">
                    <div class="pr-24">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-orange-700 text-sm font-extrabold text-white shadow-lg shadow-orange-950/40">
                                {{ strtoupper(substr($client->name ?? 'C', 0, 1)) }}
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
                        <a href="{{ route('admin.clients.show', $client->id) }}" class="sf-btn-soft-orange">
                            View
                        </a>

                        <a href="{{ route('admin.clients.edit', $client->id) }}" class="sf-btn-secondary">
                            Edit
                        </a>

                        <a href="{{ route('admin.clients.bookings', $client->id) }}" class="sf-btn-soft-blue">
                            Bookings
                        </a>

                        <form action="{{ route('admin.clients.archive', $client->id) }}"
                              method="POST"
                              onsubmit="return confirm('Archive this client?')"
                              class="inline-block">
                            @csrf

                            <button type="submit" class="sf-btn-secondary">
                                Archive
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="sm:col-span-2 lg:col-span-3">
                <div class="sf-empty">
                    No clients found.
                </div>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if(method_exists($clients, 'links'))
        <div class="text-slate-300">
            {{ $clients->links() }}
        </div>
    @endif

</div>

{{-- Live Search Script --}}
<script>
    document.getElementById('client-search')?.addEventListener('input', function () {
        const query = this.value.toLowerCase();

        document.querySelectorAll('.client-card').forEach(function (card) {
            const name = card.querySelector('.client-name')?.textContent.toLowerCase() || '';
            const email = card.querySelector('.client-email')?.textContent.toLowerCase() || '';
            const phone = card.querySelector('.client-phone')?.textContent.toLowerCase() || '';
            const vehicle = card.querySelector('.client-vehicle')?.textContent.toLowerCase() || '';

            card.style.display = (
                name.includes(query) ||
                email.includes(query) ||
                phone.includes(query) ||
                vehicle.includes(query)
            ) ? '' : 'none';
        });
    });
</script>
@endsection