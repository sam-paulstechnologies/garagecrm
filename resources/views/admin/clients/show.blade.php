@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        {{-- Back Link --}}
        <div>
            <a href="{{ route('admin.clients.index') }}"
               class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 hover:underline">
                ← Back to Clients
            </a>
        </div>

        {{-- Profile Header --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5">

                {{-- Client Identity --}}
                <div class="flex items-center gap-4 min-w-0">
                    <div class="w-12 h-12 rounded-full bg-indigo-50 text-indigo-700 flex items-center justify-center text-lg font-bold shrink-0">
                        {{ strtoupper(substr($client->name ?? 'C', 0, 1)) }}
                    </div>

                    <div class="min-w-0">
                        <h1 class="text-2xl font-semibold text-gray-900 truncate">
                            {{ $client->name ?? 'Unnamed Client' }}
                        </h1>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500 mt-1">
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
                <div class="flex flex-wrap md:justify-end gap-2 shrink-0">
                    <a href="{{ route('admin.clients.edit', $client->id) }}"
                       class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                        Edit Client
                    </a>

                    @if(Route::has('admin.vehicles.create'))
                        <a href="{{ route('admin.vehicles.create', ['client_id' => $client->id]) }}"
                           class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-indigo-50 text-indigo-700 text-sm font-medium hover:bg-indigo-100">
                            + Add Vehicle
                        </a>
                    @endif

                    @if(Route::has('admin.bookings.create'))
                        <a href="{{ route('admin.bookings.create', ['client_id' => $client->id]) }}"
                           class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-green-50 text-green-700 text-sm font-medium hover:bg-green-100">
                            + Booking
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-4 py-3">
            @include('admin.clients.partials.tabs')
        </div>

        {{-- KPI Section --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            @include('admin.clients.partials.kpis')
        </div>

        {{-- Main Layout --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            {{-- Main Column --}}
            <div class="lg:col-span-8 space-y-6">

                <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    @include('admin.clients.partials.vehicles')
                </section>

                <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    @include('admin.clients.partials.service_history')
                </section>

                <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    @include('admin.clients.partials.leads')
                </section>

                <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    @include('admin.clients.partials.opportunities')
                </section>

                <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    @include('admin.clients.partials.bookings')
                </section>

                <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    @include('admin.clients.partials.communications')
                </section>

            </div>

            {{-- Sidebar --}}
            <aside class="lg:col-span-4 space-y-6">

                <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    @include('admin.clients.partials.details')
                </section>

                <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    @include('admin.clients.partials.documents')
                </section>

                <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    @include('admin.clients.partials.invoices')
                </section>

                <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    @include('admin.clients.partials.notes')
                </section>

                <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    @include('admin.clients.partials.activity')
                </section>

            </aside>
        </div>
    </div>
</div>
@endsection