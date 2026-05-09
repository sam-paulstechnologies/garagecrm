@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4">

    {{-- ✅ Import Success Message --}}
    @if(session('import_success'))
        <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded relative mb-4">
            <strong class="font-bold">Upload Successful!</strong>
            <span class="block sm:inline">
                {{ session('imported') }} out of {{ session('total') }} clients imported.
                @if(session('skipped') > 0)
                    {{ session('skipped') }} skipped due to duplicates or errors.
                @endif
            </span>
            <span onclick="this.parentElement.remove()" class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer">
                &times;
            </span>
        </div>
    @endif

    {{-- 📋 Header --}}
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Clients</h1>

        <div class="flex gap-2">
            <a href="{{ route('admin.clients.archived') }}"
               class="bg-gray-200 hover:bg-gray-300 text-black font-bold py-2 px-4 rounded">
                View Archived
            </a>

            <a href="{{ route('admin.clients.import.form') }}"
               class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                Import Clients
            </a>

            <a href="{{ route('admin.clients.create') }}"
               class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add Client
            </a>
        </div>
    </div>

    {{-- 🔍 Search Bar --}}
    <div class="mb-6">
        <input type="text"
               id="client-search"
               placeholder="Search clients, phone, email, vehicle make/model..."
               class="w-full px-4 py-2 border rounded shadow-sm focus:outline-none focus:ring focus:border-blue-300">
    </div>

    {{-- 🧾 Client Cards --}}
    <div id="client-cards" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($clients as $client)
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

            <div class="bg-white shadow rounded-lg p-4 transition hover:shadow-lg client-card relative overflow-hidden min-h-[150px]">

                {{-- Small Car Brand Logo --}}
                @if($firstVehicle)
                    <div class="absolute top-4 right-4 text-right">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-white border border-gray-100 shadow-sm overflow-hidden">
                            @if($brandLogoUrl)
                                <img src="{{ $brandLogoUrl }}"
                                     alt="{{ $makeName }} logo"
                                     class="w-10 h-10 object-contain">
                            @else
                                <div class="text-xs font-bold text-gray-500">
                                    {{ $brandInitials }}
                                </div>
                            @endif
                        </div>

                        @if($vehicleCount > 1)
                            <div class="mt-1">
                                <span class="inline-flex px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-800 text-[10px] font-semibold">
                                    +{{ $vehicleCount - 1 }}
                                </span>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="pr-24">
                    <h2 class="text-xl font-semibold mb-1 client-name">
                        {{ $client->name }}
                    </h2>

                    <p class="text-sm text-gray-600 client-email">
                        {{ $client->email ?? 'No email' }}
                    </p>

                    <p class="text-sm text-gray-600 client-phone">
                        {{ $client->phone ?? $client->whatsapp ?? 'No phone' }}
                    </p>

                    <p class="hidden client-vehicle">
                        {{ $makeName }} {{ $modelName }}
                    </p>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('admin.clients.show', $client->id) }}"
                       class="text-blue-600 hover:underline text-sm">
                        View
                    </a>

                    <a href="{{ route('admin.clients.edit', $client->id) }}"
                       class="text-yellow-600 hover:underline text-sm">
                        Edit
                    </a>

                    <form action="{{ route('admin.clients.archive', $client->id) }}"
                          method="POST"
                          onsubmit="return confirm('Archive this client?')"
                          class="inline-block">
                        @csrf
                        <button type="submit"
                                class="text-gray-600 hover:underline text-sm">
                            Archive
                        </button>
                    </form>

                    <a href="{{ route('admin.clients.bookings', $client->id) }}"
                       class="text-green-600 hover:underline text-sm">
                        Bookings
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    @if(method_exists($clients, 'links'))
        <div class="mt-6">
            {{ $clients->links() }}
        </div>
    @endif
</div>

{{-- 🔍 Live Search Script --}}
<script>
    document.getElementById('client-search').addEventListener('input', function () {
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