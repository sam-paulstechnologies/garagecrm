<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Manager Dashboard') — SayaraForce</title>

    {{-- App Assets --}}
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])

    {{-- Bootstrap required for current manager blades --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        :root {
            --sf-bg: #050914;
            --sf-surface: #ffffff;
            --sf-surface-soft: #f8fafc;
            --sf-border: rgba(148, 163, 184, 0.22);
            --sf-border-light: #e5e7eb;
            --sf-text: #0f172a;
            --sf-muted: #64748b;
            --sf-dark-muted: #94a3b8;
            --sf-primary: #2563eb;
            --sf-primary-dark: #1d4ed8;
            --sf-orange: #ea580c;
            --sf-orange-dark: #c2410c;
            --sf-danger: #dc2626;
            --sf-danger-dark: #b91c1c;
            --sf-success: #16a34a;
            --sf-warning: #f59e0b;
            --sf-header: #060b16;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            margin: 0;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.12), transparent 34%),
                radial-gradient(circle at top right, rgba(234, 88, 12, 0.11), transparent 28%),
                var(--sf-bg);
            color: var(--sf-text);
            font-family: Figtree, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 14px;
        }

        a {
            text-decoration: none;
        }

        .manager-app-shell {
            min-height: 100vh;
            background: transparent;
        }

        /*
        |--------------------------------------------------------------------------
        | Top Bar
        |--------------------------------------------------------------------------
        */
        .manager-header {
            position: sticky;
            top: 0;
            z-index: 1020;
            background: rgba(6, 11, 22, 0.96);
            border-bottom: 1px solid rgba(148, 163, 184, 0.16);
            backdrop-filter: blur(14px);
        }

        .manager-header-inner {
            width: min(1320px, calc(100% - 32px));
            margin: 0 auto;
            min-height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }

        .manager-header-left {
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .manager-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #ffffff;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
            line-height: 1;
        }

        .manager-brand:hover {
            color: #ffffff;
        }

        .manager-logo {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 14px;
            font-weight: 900;
            letter-spacing: 0.02em;
            background: linear-gradient(135deg, var(--sf-orange), #f97316);
            box-shadow: 0 10px 24px rgba(234, 88, 12, 0.25);
            flex: 0 0 auto;
        }

        .manager-brand-text {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .manager-brand-name {
            font-size: 15px;
            font-weight: 900;
            letter-spacing: -0.02em;
            color: #ffffff;
        }

        .manager-brand-badge {
            width: max-content;
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #fbbf24;
            background: rgba(234, 88, 12, 0.15);
            border: 1px solid rgba(251, 191, 36, 0.22);
        }

        .manager-nav-wrap {
            min-width: 0;
            flex: 1;
        }

        .manager-nav {
            display: flex;
            align-items: center;
            gap: 4px;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
            padding: 0 2px;
        }

        .manager-nav::-webkit-scrollbar {
            display: none;
        }

        .manager-nav a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0 13px;
            border-radius: 12px;
            color: #a7b0c0;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
            transition: all 0.15s ease;
        }

        .manager-nav a:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.07);
        }

        .manager-nav a.active {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.12);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.04);
        }

        .manager-nav a.manager-safe-link {
            color: #cbd5e1;
        }

        .manager-nav a.manager-safe-link.active {
            color: #fdba74;
            background: rgba(249, 115, 22, 0.12);
            box-shadow: inset 0 0 0 1px rgba(249, 115, 22, 0.18);
        }

        .manager-user-area {
            display: flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
            flex: 0 0 auto;
        }

        .manager-user-pill {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            border: 0;
            border-radius: 16px;
            padding: 8px 12px;
            color: #ffffff;
            background: linear-gradient(135deg, var(--sf-orange), #f97316);
            box-shadow: 0 10px 24px rgba(234, 88, 12, 0.24);
            font-weight: 900;
            font-size: 13px;
        }

        .manager-user-initial {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            font-size: 11px;
            font-weight: 900;
            color: #ffffff;
        }

        .manager-user-chevron {
            font-size: 12px;
            opacity: 0.9;
        }

        .manager-dropdown-menu {
            border: 0;
            border-radius: 14px;
            padding: 8px;
            min-width: 190px;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.22);
        }

        .manager-dropdown-header {
            padding: 8px 10px 10px;
            border-bottom: 1px solid #eef2f7;
            margin-bottom: 6px;
        }

        .manager-dropdown-name {
            font-weight: 900;
            font-size: 13px;
            color: #0f172a;
            margin: 0;
        }

        .manager-dropdown-role {
            font-size: 11px;
            color: #64748b;
            margin: 2px 0 0;
        }

        .manager-dropdown-item {
            width: 100%;
            border: 0;
            background: transparent;
            color: #334155;
            text-align: left;
            font-size: 13px;
            font-weight: 700;
            border-radius: 10px;
            padding: 9px 10px;
        }

        .manager-dropdown-item:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .manager-dropdown-item.danger {
            color: #dc2626;
        }

        .manager-dropdown-item.danger:hover {
            background: #fef2f2;
            color: #b91c1c;
        }

        /*
        |--------------------------------------------------------------------------
        | Main Content
        |--------------------------------------------------------------------------
        */
        main.manager-main {
            padding: 44px 0 64px;
        }

        .manager-content-shell {
            width: min(1320px, calc(100% - 32px));
            margin: 0 auto;
        }

        @hasSection('header')
        .manager-page-header {
            width: min(1320px, calc(100% - 32px));
            margin: 0 auto 20px;
            padding: 22px 24px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid var(--sf-border);
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.12);
        }
        @endif

        /*
        |--------------------------------------------------------------------------
        | Bootstrap / Legacy Blade Harmonising
        |--------------------------------------------------------------------------
        */
        .card {
            border: 1px solid var(--sf-border-light);
            border-radius: 18px;
            box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08);
        }

        .card-header {
            background: #ffffff;
            border-bottom: 1px solid #eef2f7;
            border-radius: 18px 18px 0 0 !important;
            font-weight: 900;
        }

        .btn {
            border-radius: 10px;
            font-weight: 800;
            font-size: 13px;
            padding: 8px 13px;
        }

        .btn-primary {
            background: var(--sf-primary);
            border-color: var(--sf-primary);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background: var(--sf-primary-dark);
            border-color: var(--sf-primary-dark);
        }

        .btn-danger {
            background: var(--sf-danger);
            border-color: var(--sf-danger);
        }

        .btn-danger:hover,
        .btn-danger:focus {
            background: var(--sf-danger-dark);
            border-color: var(--sf-danger-dark);
        }

        .btn-warning {
            color: #111827;
            background: #f59e0b;
            border-color: #f59e0b;
        }

        .btn-success {
            background: var(--sf-success);
            border-color: var(--sf-success);
        }

        .btn-outline-primary {
            color: var(--sf-primary);
            border-color: rgba(37, 99, 235, 0.35);
        }

        .btn-outline-primary:hover {
            background: var(--sf-primary);
            border-color: var(--sf-primary);
            color: #ffffff;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            border-color: #dbe2ea;
            font-size: 14px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: rgba(37, 99, 235, 0.65);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.12);
        }

        .table {
            color: #0f172a;
            vertical-align: middle;
        }

        .table thead th {
            font-size: 12px;
            color: #475569;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
        }

        .table tbody td {
            font-size: 13px;
            color: #334155;
        }

        .table-hover tbody tr:hover {
            background: #f8fafc;
        }

        .badge {
            border-radius: 999px;
            font-weight: 900;
            padding: 6px 9px;
        }

        .alert {
            border: 0;
            border-radius: 14px;
            font-weight: 700;
        }

        /*
        |--------------------------------------------------------------------------
        | Reusable Manager UI Helpers
        |--------------------------------------------------------------------------
        */
        .sf-page-title {
            color: #ffffff;
            font-weight: 950;
            letter-spacing: -0.04em;
            margin: 0;
        }

        .sf-page-subtitle {
            color: var(--sf-dark-muted);
            margin: 6px 0 0;
            font-weight: 600;
        }

        .sf-panel {
            background: #ffffff;
            border: 1px solid var(--sf-border-light);
            border-radius: 18px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
        }

        .sf-panel-header {
            padding: 18px 22px;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .sf-panel-title {
            margin: 0;
            color: #0f172a;
            font-size: 16px;
            font-weight: 950;
            letter-spacing: -0.02em;
        }

        .sf-panel-subtitle {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .sf-panel-body {
            padding: 20px 22px;
        }

        .sf-stat-card {
            min-height: 122px;
            background: #ffffff;
            border: 1px solid var(--sf-border-light);
            border-radius: 16px;
            padding: 20px 22px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
        }

        .sf-stat-label {
            margin: 0 0 12px;
            color: #64748b;
            font-weight: 800;
            font-size: 13px;
        }

        .sf-stat-value {
            margin: 0;
            color: #0f172a;
            font-size: 28px;
            line-height: 1;
            font-weight: 950;
            letter-spacing: -0.04em;
        }

        .sf-stat-help {
            margin: 12px 0 0;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 700;
        }

        .sf-action-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 38px;
            border-radius: 10px;
            padding: 0 14px;
            font-weight: 900;
            font-size: 13px;
            text-decoration: none;
            border: 0;
        }

        .sf-action-button.primary {
            color: #ffffff;
            background: var(--sf-primary);
        }

        .sf-action-button.primary:hover {
            color: #ffffff;
            background: var(--sf-primary-dark);
        }

        .sf-action-button.danger {
            color: #ffffff;
            background: var(--sf-danger);
        }

        .sf-action-button.danger:hover {
            color: #ffffff;
            background: var(--sf-danger-dark);
        }

        .sf-action-button.orange {
            color: #ffffff;
            background: var(--sf-orange);
        }

        .sf-action-button.orange:hover {
            color: #ffffff;
            background: var(--sf-orange-dark);
        }

        .sf-action-button.light {
            color: #0f172a;
            background: #ffffff;
            border: 1px solid #e5e7eb;
        }

        .sf-action-button.light:hover {
            color: #0f172a;
            background: #f8fafc;
        }

        /*
        |--------------------------------------------------------------------------
        | Responsive
        |--------------------------------------------------------------------------
        */
        @media (max-width: 1180px) {
            .manager-header-inner {
                width: min(100% - 24px, 1320px);
            }

            .manager-content-shell {
                width: min(100% - 24px, 1320px);
            }

            .manager-nav a {
                padding: 0 11px;
                font-size: 13px;
            }
        }

        @media (max-width: 900px) {
            .manager-header {
                position: relative;
            }

            .manager-header-inner {
                min-height: auto;
                padding: 12px 0;
                align-items: flex-start;
                flex-direction: column;
            }

            .manager-header-left {
                width: 100%;
                align-items: flex-start;
                flex-direction: column;
                gap: 12px;
            }

            .manager-nav-wrap {
                width: 100%;
            }

            .manager-nav {
                width: 100%;
                padding-bottom: 2px;
            }

            .manager-user-area {
                position: absolute;
                top: 12px;
                right: 0;
            }

            .manager-brand-name,
            .manager-brand-badge {
                display: none;
            }

            main.manager-main {
                padding-top: 28px;
            }
        }

        @media (max-width: 576px) {
            .manager-logo {
                width: 38px;
                height: 38px;
                font-size: 13px;
            }

            .manager-nav a {
                min-height: 36px;
                padding: 0 10px;
                border-radius: 10px;
            }

            .manager-user-pill {
                padding: 8px 10px;
            }

            .manager-user-chevron {
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
            'safe' => false,
        ],
        [
            'label' => 'Clients',
            'route' => 'manager.clients.index',
            'active' => 'manager.clients.*',
            'safe' => false,
        ],
        [
            'label' => 'Leads',
            'route' => 'manager.leads.index',
            'active' => 'manager.leads.*',
            'safe' => false,
        ],
        [
            'label' => 'Opportunities',
            'route' => 'manager.opportunities.index',
            'active' => 'manager.opportunities.*',
            'safe' => false,
        ],
        [
            'label' => 'Bookings',
            'route' => 'manager.bookings.index',
            'active' => 'manager.bookings.*',
            'safe' => false,
        ],
        [
            'label' => 'Jobs',
            'route' => 'manager.jobs.index',
            'active' => 'manager.jobs.*',
            'safe' => false,
        ],
        [
            'label' => 'Invoices',
            'route' => 'manager.invoices.index',
            'active' => 'manager.invoices.*',
            'safe' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Calendar
        |--------------------------------------------------------------------------
        | This will only show if manager.calendar.index exists.
        |--------------------------------------------------------------------------
        */
        [
            'label' => 'Calendar',
            'route' => 'manager.calendar.index',
            'active' => 'manager.calendar.*',
            'safe' => false,
        ],

        [
            'label' => 'Inbox',
            'route' => 'manager.inbox.index',
            'active' => ['manager.inbox.*', 'manager.escalations', 'manager.conversation', 'manager.conversation.*'],
            'safe' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Manager-safe Growth + Settings
        |--------------------------------------------------------------------------
        | These are separate from Admin Growth/Admin Settings.
        |--------------------------------------------------------------------------
        */
        [
            'label' => 'Growth',
            'route' => 'manager.growth.index',
            'active' => 'manager.growth.*',
            'safe' => true,
        ],
        [
            'label' => 'Settings',
            'route' => 'manager.settings.index',
            'active' => 'manager.settings.*',
            'safe' => true,
        ],

        [
            'label' => 'Team',
            'route' => 'manager.team.index',
            'active' => 'manager.team.*',
            'safe' => false,
        ],
    ];

    $authUser = auth()->user();
    $userName = $authUser?->name ?? 'Manager';
    $userInitial = strtoupper(mb_substr($userName, 0, 1));

    $isRouteActive = function ($activePattern) {
        if (is_array($activePattern)) {
            foreach ($activePattern as $pattern) {
                if (request()->routeIs($pattern)) {
                    return true;
                }
            }

            return false;
        }

        return request()->routeIs($activePattern);
    };
@endphp

<div class="manager-app-shell">

    {{-- ========================================= --}}
    {{-- Manager Top Bar --}}
    {{-- ========================================= --}}
    <header class="manager-header">
        <div class="manager-header-inner">

            <div class="manager-header-left">
                <a href="{{ Route::has('manager.dashboard') ? route('manager.dashboard') : url('/') }}"
                   class="manager-brand">
                    <span class="manager-logo">SF</span>

                    <span class="manager-brand-text">
                        <span class="manager-brand-name">SayaraForce</span>
                        <span class="manager-brand-badge">Growth Plan</span>
                    </span>
                </a>

                <div class="manager-nav-wrap">
                    <nav class="manager-nav">
                        @foreach($managerNavItems as $item)
                            @if(Route::has($item['route']))
                                <a href="{{ route($item['route']) }}"
                                   class="{{ $isRouteActive($item['active']) ? 'active' : '' }} {{ ($item['safe'] ?? false) ? 'manager-safe-link' : '' }}">
                                    {{ $item['label'] }}
                                </a>
                            @endif
                        @endforeach
                    </nav>
                </div>
            </div>

            <div class="manager-user-area">
                @auth
                    <div class="dropdown">
                        <button
                            class="manager-user-pill dropdown-toggle"
                            type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            <span class="manager-user-initial">{{ $userInitial }}</span>
                        </button>

                        <div class="dropdown-menu dropdown-menu-end manager-dropdown-menu">
                            <div class="manager-dropdown-header">
                                <p class="manager-dropdown-name">{{ $userName }}</p>
                                <p class="manager-dropdown-role">Manager</p>
                            </div>

                            @if(Route::has('logout'))
                                <form method="POST" action="{{ route('logout') }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="manager-dropdown-item danger">
                                        Logout
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endauth
            </div>

        </div>
    </header>


    {{-- ========================================= --}}
    {{-- Optional Page Header --}}
    {{-- ========================================= --}}
    @hasSection('header')
        <section class="manager-page-header">
            @yield('header')
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