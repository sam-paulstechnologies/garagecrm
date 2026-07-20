@extends('layouts.app')

@section('content')
    <div class="sf-page mx-auto w-full max-w-[1680px] px-4 sm:px-6 lg:px-8">
        <style>
            .sa-card {
                border: 1px solid rgba(148, 163, 184, 0.22);
                background: rgba(15, 23, 42, 0.72);
                color: #f8fafc;
                box-shadow: 0 18px 45px rgba(2, 6, 23, 0.18);
            }
            .sa-soft {
                border: 1px solid rgba(148, 163, 184, 0.18);
                background: rgba(15, 23, 42, 0.52);
            }
            .sa-label { color: #94a3b8; }
            .sa-muted { color: #cbd5e1; }
            .sa-input {
                border: 1px solid rgba(148, 163, 184, 0.28);
                background: rgba(2, 6, 23, 0.28);
                color: #f8fafc;
            }
            .sa-input:focus {
                border-color: #fb923c;
                outline: 2px solid rgba(249, 115, 22, 0.22);
            }
            .sa-table th {
                color: #94a3b8;
                font-size: 0.72rem;
                font-weight: 900;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }
            .sa-table td {
                border-top: 1px solid rgba(148, 163, 184, 0.14);
                color: #e2e8f0;
            }
            html[data-theme="light"] .sa-card {
                background: #ffffff;
                color: #0f172a;
                border-color: #d9e1ec;
                box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
            }
            html[data-theme="light"] .sa-soft {
                background: #f8fafc;
                border-color: #d9e1ec;
            }
            html[data-theme="light"] .sa-label { color: #64748b; }
            html[data-theme="light"] .sa-muted { color: #475569; }
            html[data-theme="light"] .sa-input {
                background: #ffffff;
                color: #0f172a;
                border-color: #cbd5e1;
            }
            html[data-theme="light"] .sa-table td {
                border-color: #e5eaf1;
                color: #0f172a;
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
                <a href="{{ route('super-admin.marketing.dashboard') }}" class="rounded-2xl px-4 py-2 text-xs font-extrabold {{ request()->routeIs('super-admin.marketing.*') ? 'bg-orange-500 text-white' : 'bg-white/10 text-white hover:bg-white/15' }}">Marketing</a>
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
