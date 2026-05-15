@extends('layouts.app')

@section('title', 'Client Bookings')

@section('content')
<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Client Bookings
            </div>

            <h1 class="sf-page-title mt-3">
                Bookings — {{ $client->name }}
            </h1>

            <p class="sf-page-subtitle">
                View booking activity linked to this client profile.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.clients.show', $client->id) }}" class="sf-btn-secondary">
                ← Back to Client
            </a>

            @if(Route::has('admin.bookings.create'))
                <a href="{{ route('admin.bookings.create', ['client_id' => $client->id]) }}" class="sf-btn-primary">
                    + Add Booking
                </a>
            @endif
        </div>
    </div>

    {{-- Client Summary --}}
    <div class="sf-card">
        <div class="sf-card-body">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                        Client
                    </div>

                    <div class="mt-1 font-extrabold text-white">
                        {{ $client->name ?? 'Unnamed Client' }}
                    </div>
                </div>

                <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                        Phone
                    </div>

                    <div class="mt-1 font-bold text-slate-200">
                        {{ $client->phone ?? $client->whatsapp ?? '—' }}
                    </div>
                </div>

                <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                        Client ID
                    </div>

                    <div class="mt-1 font-bold text-slate-200">
                        #{{ $client->id }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bookings --}}
    <div class="sf-card">
        <div class="sf-card-body">
            @include('admin.clients.partials.bookings')
        </div>
    </div>

</div>
@endsection