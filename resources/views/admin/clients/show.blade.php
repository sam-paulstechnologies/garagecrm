@extends('layouts.app')

@section('title', $client->name ?? 'Client Profile')

@section('content')
<div class="sf-page space-y-6">

    {{-- Back Link --}}
    <div>
        <a href="{{ route('admin.clients.index') }}" class="sf-link">
            ← Back to Clients
        </a>
    </div>

    {{-- Profile Header --}}
    <div class="sf-hero-panel">
        <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">

            {{-- Client Identity --}}
            <div class="flex min-w-0 items-center gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-3xl bg-gradient-to-br from-orange-500 to-orange-700 text-xl font-extrabold text-white shadow-lg shadow-orange-950/40">
                    {{ strtoupper(substr($client->name ?? 'C', 0, 1)) }}
                </div>

                <div class="min-w-0">
                    <div class="sf-kicker">
                        Client Profile
                    </div>

                    <h1 class="mt-2 truncate text-3xl font-extrabold tracking-tight text-white">
                        {{ $client->name ?? 'Unnamed Client' }}
                    </h1>

                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm font-medium text-slate-400">
                        <span class="whitespace-nowrap">
                            📞 {{ $client->phone ?? $client->whatsapp ?? 'No phone' }}
                        </span>

                        <span class="whitespace-nowrap">
                            ✉️ {{ $client->email ?? 'No email' }}
                        </span>

                        <span class="whitespace-nowrap">
                            📍 {{ $client->city ?? $client->country ?? 'No location' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex shrink-0 flex-wrap gap-2 md:justify-end">
                <a href="{{ route('admin.clients.edit', $client->id) }}" class="sf-btn-primary">
                    Edit Client
                </a>

                @if(Route::has('admin.vehicles.create'))
                    <a href="{{ route('admin.vehicles.create', ['client_id' => $client->id]) }}" class="sf-btn-secondary">
                        + Add Vehicle
                    </a>
                @endif

                @if(Route::has('admin.bookings.create'))
                    <a href="{{ route('admin.bookings.create', ['client_id' => $client->id]) }}" class="sf-btn-soft-blue">
                        + Booking
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="sf-card">
        <div class="sf-card-body">
            @include('admin.clients.partials.tabs')
        </div>
    </div>

    {{-- KPI Section --}}
    <div class="sf-card">
        <div class="sf-card-body">
            @include('admin.clients.partials.kpis')
        </div>
    </div>

    {{-- Main Layout --}}
    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">

        {{-- Main Column --}}
        <div class="space-y-6 lg:col-span-8">

            <section class="sf-card">
                <div class="sf-card-body">
                    @include('admin.clients.partials.vehicles')
                </div>
            </section>

            <section class="sf-card">
                <div class="sf-card-body">
                    @include('admin.clients.partials.service_history')
                </div>
            </section>

            <section class="sf-card">
                <div class="sf-card-body">
                    @include('admin.clients.partials.leads')
                </div>
            </section>

            <section class="sf-card">
                <div class="sf-card-body">
                    @include('admin.clients.partials.opportunities')
                </div>
            </section>

            <section class="sf-card">
                <div class="sf-card-body">
                    @include('admin.clients.partials.bookings')
                </div>
            </section>

            <section class="sf-card">
                <div class="sf-card-body">
                    @include('admin.clients.partials.communications')
                </div>
            </section>

        </div>

        {{-- Sidebar --}}
        <aside class="space-y-6 lg:col-span-4">

            <section class="sf-card">
                <div class="sf-card-body">
                    @include('admin.clients.partials.details')
                </div>
            </section>

            <section class="sf-card">
                <div class="sf-card-body">
                    @include('admin.clients.partials.documents')
                </div>
            </section>

            <section class="sf-card">
                <div class="sf-card-body">
                    @include('admin.clients.partials.invoices')
                </div>
            </section>

            <section class="sf-card">
                <div class="sf-card-body">
                    @include('admin.clients.partials.notes')
                </div>
            </section>

            <section class="sf-card">
                <div class="sf-card-body">
                    @include('admin.clients.partials.activity')
                </div>
            </section>

        </aside>
    </div>

</div>
@endsection