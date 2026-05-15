<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Manager Dashboard') — Garage CRM</title>

    {{-- App Assets --}}
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])

    {{-- Bootstrap required for current manager blades --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background: #f3f4f6;
            color: #111827;
            font-family: Figtree, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .manager-header {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
        }

        .manager-header-inner {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 1rem;
            min-height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .manager-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            color: #111827;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
        }

        .manager-logo {
            width: 32px;
            height: 32px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            background: #ffffff;
            color: #111827;
        }

        .manager-nav {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            font-size: 14px;
            overflow-x: auto;
        }

        .manager-nav a {
            color: #4b5563;
            text-decoration: none;
            padding: 18px 0 14px;
            border-bottom: 2px solid transparent;
            white-space: nowrap;
        }

        .manager-nav a:hover {
            color: #111827;
            border-bottom-color: #d1d5db;
        }

        .manager-nav a.active {
            color: #111827;
            font-weight: 600;
            border-bottom-color: #6366f1;
        }

        .manager-user-area {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            white-space: nowrap;
        }

        .manager-user-name {
            font-size: 14px;
            color: #4b5563;
        }

        .manager-logout-btn {
            border: 0;
            background: #111827;
            color: #ffffff;
            border-radius: 6px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .manager-logout-btn:hover {
            background: #1f2937;
        }

        main.manager-main {
            padding: 24px 0;
        }

        .manager-content-shell {
            max-width: 100%;
            margin: 0 auto;
        }

        .card {
            border-radius: 10px;
        }

        .btn {
            border-radius: 7px;
        }

        .form-control,
        .form-select {
            border-radius: 7px;
        }

        .table thead th {
            font-size: 13px;
            color: #111827;
            font-weight: 700;
        }

        .table tbody td {
            font-size: 13px;
        }

        @media (max-width: 1024px) {
            .manager-header-inner {
                align-items: flex-start;
                flex-direction: column;
                padding-top: 0.75rem;
                padding-bottom: 0.75rem;
            }

            .manager-nav {
                width: 100%;
                padding-bottom: 0.25rem;
            }

            .manager-user-area {
                position: absolute;
                top: 12px;
                right: 16px;
            }

            .manager-user-name {
                display: none;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

@php
    use Illuminate\Support\Facades\Route;

    $managerNavItems = [
        [
            'label' => 'Dashboard',
            'route' => 'manager.dashboard',
            'active' => 'manager.dashboard',
        ],
        [
            'label' => 'Clients',
            'route' => 'manager.clients.index',
            'active' => 'manager.clients.*',
        ],
        [
            'label' => 'Leads',
            'route' => 'manager.leads.index',
            'active' => 'manager.leads.*',
        ],
        [
            'label' => 'Opportunities',
            'route' => 'manager.opportunities.index',
            'active' => 'manager.opportunities.*',
        ],
        [
            'label' => 'Bookings',
            'route' => 'manager.bookings.index',
            'active' => 'manager.bookings.*',
        ],
        [
            'label' => 'Jobs',
            'route' => 'manager.jobs.index',
            'active' => 'manager.jobs.*',
        ],
        [
            'label' => 'Invoices',
            'route' => 'manager.invoices.index',
            'active' => 'manager.invoices.*',
        ],
        [
            'label' => 'Inbox',
            'route' => 'manager.inbox.index',
            'active' => 'manager.inbox.*',
        ],
        [
            'label' => 'Team',
            'route' => 'manager.team.index',
            'active' => 'manager.team.*',
        ],
    ];
@endphp

<div class="min-vh-100">

    {{-- ========================================= --}}
    {{-- Manager Top Bar --}}
    {{-- ========================================= --}}
    <header class="manager-header">
        <div class="manager-header-inner">

            <div class="d-flex align-items-center gap-4 flex-wrap">
                <a href="{{ Route::has('manager.dashboard') ? route('manager.dashboard') : url('/') }}"
                   class="manager-brand">
                    <span class="manager-logo">CRM</span>
                    <span>Garage CRM</span>
                </a>

                <nav class="manager-nav">
                    @foreach($managerNavItems as $item)
                        @if(Route::has($item['route']))
                            <a href="{{ route($item['route']) }}"
                               class="{{ request()->routeIs($item['active']) ? 'active' : '' }}">
                                {{ $item['label'] }}
                            </a>
                        @endif
                    @endforeach
                </nav>
            </div>

            <div class="manager-user-area">
                @auth
                    <span class="manager-user-name">
                        {{ auth()->user()->name }}
                    </span>
                @endauth

                @if(Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="manager-logout-btn">
                            Logout
                        </button>
                    </form>
                @endif
            </div>

        </div>
    </header>


    {{-- ========================================= --}}
    {{-- Optional Page Header --}}
    {{-- ========================================= --}}
    @hasSection('header')
        <section class="bg-white border-bottom">
            <div class="container-fluid py-4">
                @yield('header')
            </div>
        </section>
    @endif


    {{-- ========================================= --}}
    {{-- Main Content --}}
    {{-- ========================================= --}}
    <main class="manager-main">
        <div class="manager-content-shell">
            @yield('content')
        </div>
    </main>

</div>


{{-- WhatsApp Floating Popup --}}
@auth
    @if(View::exists('partials.whatsapp-popup'))
        @include('partials.whatsapp-popup')
    @elseif(View::exists('admin.partials.whatsapp-popup'))
        @include('admin.partials.whatsapp-popup')
    @endif
@endauth

{{-- Bootstrap --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

{{-- Alpine --}}
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

@stack('scripts')

</body>
</html>