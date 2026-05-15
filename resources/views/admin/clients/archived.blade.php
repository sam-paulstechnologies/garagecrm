@extends('layouts.app')

@section('title', 'Archived Clients')

@section('content')
<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Client Archive
            </div>

            <h1 class="sf-page-title mt-3">
                Archived Clients
            </h1>

            <p class="sf-page-subtitle">
                Review archived client profiles and restore them when needed.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.clients.index') }}" class="sf-btn-secondary">
                ← Back to Clients
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="sf-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="sf-alert-warning">
            {{ session('warning') }}
        </div>
    @endif

    @if(session('error'))
        <div class="sf-alert-danger">
            {{ session('error') }}
        </div>
    @endif

    {{-- Summary --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Archived Clients
            </div>

            <div class="sf-stat-value text-orange-300">
                {{ method_exists($clients, 'total') ? $clients->total() : $clients->count() }}
            </div>

            <div class="sf-stat-note">
                Hidden from active client list
            </div>
        </div>

        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Available Action
            </div>

            <div class="mt-3 text-lg font-extrabold text-white">
                Restore Client
            </div>

            <div class="sf-stat-note">
                Move client back to active list
            </div>
        </div>

        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Archive Purpose
            </div>

            <div class="mt-3 text-lg font-extrabold text-white">
                Clean Workspace
            </div>

            <div class="sf-stat-note">
                Keep inactive records safely stored
            </div>
        </div>
    </div>

    {{-- Archived Clients --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($clients as $client)
            <div class="sf-card sf-card-hover overflow-hidden">
                <div class="sf-card-body">

                    <div class="flex items-start gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-orange-700 text-sm font-extrabold text-white shadow-lg shadow-orange-950/40">
                            {{ strtoupper(substr($client->name ?? 'C', 0, 1)) }}
                        </div>

                        <div class="min-w-0">
                            <h2 class="truncate text-xl font-extrabold text-white">
                                {{ $client->name ?? 'Unnamed Client' }}
                            </h2>

                            <p class="mt-1 truncate text-sm font-medium text-slate-500">
                                {{ $client->email ?? 'No email' }}
                            </p>

                            <p class="mt-1 text-sm font-bold text-slate-300">
                                {{ $client->phone ?? $client->whatsapp ?? 'No phone' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        <form action="{{ route('admin.clients.restore', $client->id) }}"
                              method="POST"
                              onsubmit="return confirm('Restore this client?')">
                            @csrf

                            <button type="submit" class="sf-btn-primary">
                                Restore
                            </button>
                        </form>

                        @if(Route::has('admin.clients.show'))
                            <a href="{{ route('admin.clients.show', $client->id) }}" class="sf-btn-secondary">
                                View
                            </a>
                        @endif
                    </div>

                </div>
            </div>
        @empty
            <div class="sm:col-span-2 lg:col-span-3">
                <div class="sf-empty">
                    No archived clients found.
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
@endsection