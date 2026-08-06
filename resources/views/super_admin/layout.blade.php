@extends('layouts.app')

@section('content')
    <div class="sf-page mx-auto w-full max-w-[1680px] px-4 sm:px-6 lg:px-8">
        <style>
            .sa-card {
                border: 1px solid var(--sf-border);
                background: var(--sf-surface);
                color: var(--sf-text);
                box-shadow: var(--sf-shadow-soft);
            }
            .sa-soft {
                border: 1px solid var(--sf-border);
                background: var(--sf-surface-soft);
            }
            .sa-label { color: var(--sf-muted); }
            .sa-muted { color: var(--sf-muted-strong); }
            .sa-input {
                border: 1px solid var(--sf-border-strong);
                background: var(--sf-input-bg);
                color: var(--sf-input-text);
            }
            .sa-input:focus {
                border-color: var(--sf-orange);
                outline: 2px solid var(--sf-focus-ring);
            }
            .sa-table th {
                color: #94a3b8;
                font-size: 0.72rem;
                font-weight: 600;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }
            .sa-table td {
                border-top: 1px solid rgba(148, 163, 184, 0.14);
                color: var(--sf-text);
            }
            html[data-theme="light"] .sa-card {
                background: #ffffff;
                color: var(--sf-text);
                border-color: var(--sf-border);
                box-shadow: var(--sf-shadow-soft);
            }
            html[data-theme="light"] .sa-soft {
                background: var(--sf-surface-soft);
                border-color: var(--sf-border);
            }
            html[data-theme="light"] .sa-label { color: var(--sf-muted); }
            html[data-theme="light"] .sa-muted { color: var(--sf-muted-strong); }
            html[data-theme="light"] .sa-input {
                background: #ffffff;
                color: var(--sf-input-text);
                border-color: var(--sf-border-strong);
            }
            html[data-theme="light"] .sa-table td {
                border-color: var(--sf-border);
                color: var(--sf-text);
            }
        </style>

        <div class="mb-5 flex flex-col gap-3 rounded-3xl border border-orange-400/20 bg-orange-500/10 px-5 py-4 text-sm font-bold text-orange-200 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="block text-xs uppercase tracking-wide text-orange-300">Platform Owner</span>
                <span class="text-white">Paul's Technologies Super Admin Control Center</span>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('super-admin.dashboard') }}" class="rounded-2xl px-4 py-2 text-xs font-extrabold {{ request()->routeIs('super-admin.dashboard') ? 'bg-orange-500 text-white' : 'bg-white/10 text-white hover:bg-white/15' }}">Dashboard</a>
                <a href="{{ route('super-admin.garages.index') }}" class="rounded-2xl px-4 py-2 text-xs font-extrabold {{ request()->routeIs('super-admin.garages.*') ? 'bg-orange-500 text-white' : 'bg-white/10 text-white hover:bg-white/15' }}">Garages</a>
                <a href="{{ route('super-admin.logs.messages') }}" class="rounded-2xl px-4 py-2 text-xs font-extrabold {{ request()->routeIs('super-admin.logs.messages') ? 'bg-orange-500 text-white' : 'bg-white/10 text-white hover:bg-white/15' }}">Messages</a>
                <a href="{{ route('super-admin.logs.leads') }}" class="rounded-2xl px-4 py-2 text-xs font-extrabold {{ request()->routeIs('super-admin.logs.leads') ? 'bg-orange-500 text-white' : 'bg-white/10 text-white hover:bg-white/15' }}">Leads</a>
                <a href="{{ route('super-admin.messaging-connections.index') }}" class="rounded-2xl px-4 py-2 text-xs font-extrabold {{ request()->routeIs('super-admin.messaging-connections.*') ? 'bg-orange-500 text-white' : 'bg-white/10 text-white hover:bg-white/15' }}">Messaging</a>
                <a href="{{ route('super-admin.operations.view', 'journey-flow') }}" class="rounded-2xl px-4 py-2 text-xs font-extrabold {{ request()->routeIs('super-admin.operations.*') ? 'bg-orange-500 text-white' : 'bg-white/10 text-white hover:bg-white/15' }}">Operations</a>
                <a href="{{ route('super-admin.system.health') }}" class="rounded-2xl px-4 py-2 text-xs font-extrabold {{ request()->routeIs('super-admin.system.*') ? 'bg-orange-500 text-white' : 'bg-white/10 text-white hover:bg-white/15' }}">Health</a>
                <a href="{{ route('super-admin.audit.index') }}" class="rounded-2xl px-4 py-2 text-xs font-extrabold {{ request()->routeIs('super-admin.audit.*') ? 'bg-orange-500 text-white' : 'bg-white/10 text-white hover:bg-white/15' }}">Audit</a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-5 rounded-2xl border border-emerald-400/30 bg-emerald-500/10 px-5 py-3 text-sm font-bold text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-5 rounded-2xl border border-red-400/30 bg-red-500/10 px-5 py-3 text-sm font-bold text-red-200">
                {{ $errors->first() }}
            </div>
        @endif

        @yield('super_admin_content')
    </div>
@endsection
