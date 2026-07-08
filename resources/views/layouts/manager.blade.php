<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Manager Dashboard') - SayaraForce</title>

    {{-- Prevent theme flash before page loads. Uses the same key as the Admin layout. --}}
    <script>
        (function () {
            try {
                var savedTheme = localStorage.getItem('sayaraforce_theme') || 'dark';

                if (savedTheme !== 'light' && savedTheme !== 'dark') {
                    savedTheme = 'dark';
                }

                document.documentElement.setAttribute('data-theme', savedTheme);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>

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
            color-scheme: dark;

            --sf-bg: #050914;
            --sf-bg-soft: #0f172a;
            --sf-surface: #111827;
            --sf-surface-soft: #172033;
            --sf-surface-strong: #1e293b;
            --sf-border: rgba(148, 163, 184, 0.20);
            --sf-border-light: rgba(148, 163, 184, 0.24);
            --sf-text: #f8fafc;
            --sf-text-strong: #ffffff;
            --sf-muted: #94a3b8;
            --sf-muted-strong: #cbd5e1;
            --sf-primary: #2563eb;
            --sf-primary-dark: #1d4ed8;
            --sf-orange: #ea580c;
            --sf-orange-dark: #c2410c;
            --sf-danger: #dc2626;
            --sf-danger-dark: #b91c1c;
            --sf-success: #16a34a;
            --sf-warning: #f59e0b;
            --sf-header: rgba(6, 11, 22, 0.94);
            --sf-shadow: 0 18px 50px rgba(0, 0, 0, 0.26);
            --sf-soft-shadow: 0 16px 40px rgba(0, 0, 0, 0.20);
            --sf-input-bg: #0f172a;
            --sf-input-text: #f8fafc;
            --sf-row-hover: rgba(148, 163, 184, 0.10);
            --sf-orange-soft: rgba(249, 115, 22, 0.14);
            --sf-theme-toggle-bg: rgba(255, 255, 255, 0.08);
            --sf-theme-toggle-border: rgba(255, 255, 255, 0.14);
        }

        html[data-theme="light"] {
            color-scheme: light;

            --sf-bg: #f4f7fb;
            --sf-bg-soft: #eef3f9;
            --sf-surface: #ffffff;
            --sf-surface-soft: #f8fafc;
            --sf-surface-strong: #ffffff;
            --sf-border: rgba(15, 23, 42, 0.10);
            --sf-border-light: #d9e1ec;
            --sf-text: #0f172a;
            --sf-text-strong: #020617;
            --sf-muted: #64748b;
            --sf-muted-strong: #475569;
            --sf-header: rgba(255, 255, 255, 0.94);
            --sf-shadow: 0 18px 50px rgba(15, 23, 42, 0.12);
            --sf-soft-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
            --sf-input-bg: #ffffff;
            --sf-input-text: #0f172a;
            --sf-row-hover: #f8fafc;
            --sf-orange-soft: rgba(249, 115, 22, 0.10);
            --sf-theme-toggle-bg: #ffffff;
            --sf-theme-toggle-border: #d9e1ec;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        body.manager-theme-body {
            margin: 0;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.12), transparent 34%),
                radial-gradient(circle at top right, rgba(234, 88, 12, 0.11), transparent 28%),
                var(--sf-bg);
            color: var(--sf-text);
            font-family: Figtree, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 14px;
        }

        html[data-theme="light"] body.manager-theme-body {
            background: var(--sf-bg);
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
            background: var(--sf-header);
            border-bottom: 1px solid var(--sf-border);
            backdrop-filter: blur(14px);
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.10);
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

        .manager-mobile-toggle {
            display: none;
            width: 44px;
            height: 44px;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            border: 1px solid var(--sf-border-light);
            color: var(--sf-text);
            background: var(--sf-surface);
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.12);
        }

        .manager-mobile-toggle span,
        .manager-mobile-toggle span::before,
        .manager-mobile-toggle span::after {
            display: block;
            width: 18px;
            height: 2px;
            border-radius: 999px;
            background: currentColor;
            content: "";
        }

        .manager-mobile-toggle span {
            position: relative;
        }

        .manager-mobile-toggle span::before,
        .manager-mobile-toggle span::after {
            position: absolute;
            left: 0;
        }

        .manager-mobile-toggle span::before {
            top: -6px;
        }

        .manager-mobile-toggle span::after {
            top: 6px;
        }

        .manager-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--sf-text-strong);
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
            line-height: 1;
        }

        .manager-brand:hover {
            color: var(--sf-text-strong);
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
            color: var(--sf-text-strong);
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
            color: var(--sf-muted-strong);
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
            transition: all 0.15s ease;
        }

        .manager-nav a:hover {
            color: var(--sf-text-strong);
            background: rgba(148, 163, 184, 0.12);
        }

        .manager-nav a.active {
            color: var(--sf-text-strong);
            background: var(--sf-orange-soft);
            box-shadow: inset 0 0 0 1px rgba(249, 115, 22, 0.24);
        }

        .manager-nav a.manager-safe-link {
            color: var(--sf-muted-strong);
        }

        .manager-nav a.manager-safe-link.active {
            color: var(--sf-text-strong);
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
            border: 1px solid var(--sf-border-light);
            border-radius: 18px;
            padding: 10px;
            min-width: 260px;
            background: var(--sf-surface);
            box-shadow: var(--sf-shadow);
        }

        .manager-dropdown-header {
            padding: 8px 10px 12px;
            border-bottom: 1px solid var(--sf-border-light);
            margin-bottom: 6px;
        }

        .manager-dropdown-name {
            font-weight: 900;
            font-size: 13px;
            color: var(--sf-text-strong);
            margin: 0;
        }

        .manager-dropdown-role {
            font-size: 11px;
            color: var(--sf-muted);
            margin: 2px 0 0;
        }

        .manager-dropdown-item {
            width: 100%;
            border: 0;
            background: transparent;
            color: var(--sf-muted-strong);
            text-align: left;
            font-size: 13px;
            font-weight: 700;
            border-radius: 10px;
            padding: 9px 10px;
        }

        .manager-dropdown-item:hover {
            background: var(--sf-row-hover);
            color: var(--sf-text-strong);
        }

        .manager-dropdown-item.danger {
            color: #dc2626;
        }

        .manager-dropdown-item.danger:hover {
            background: rgba(220, 38, 38, 0.10);
            color: #ef4444;
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
            background: var(--sf-surface);
            border: 1px solid var(--sf-border);
            box-shadow: var(--sf-shadow);
        }
        @endif

        /*
        |--------------------------------------------------------------------------
        | Bootstrap / Legacy Blade Harmonising
        |--------------------------------------------------------------------------
        */
        .card {
            color: var(--sf-text);
            background: var(--sf-surface);
            border: 1px solid var(--sf-border-light);
            border-radius: 18px;
            box-shadow: var(--sf-soft-shadow);
        }

        .card-header {
            color: var(--sf-text-strong);
            background: var(--sf-surface);
            border-bottom: 1px solid var(--sf-border-light);
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
            color: var(--sf-input-text);
            background-color: var(--sf-input-bg);
            border-color: var(--sf-border-light);
            font-size: 14px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: rgba(37, 99, 235, 0.65);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.12);
        }

        .table {
            color: var(--sf-text);
            vertical-align: middle;
        }

        .table thead th {
            font-size: 12px;
            color: var(--sf-muted-strong);
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: var(--sf-surface-soft);
            border-bottom: 1px solid var(--sf-border-light);
        }

        .table tbody td {
            font-size: 13px;
            color: var(--sf-muted-strong);
        }

        .table-hover tbody tr:hover {
            background: var(--sf-row-hover);
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
            color: var(--sf-text-strong);
            font-weight: 950;
            letter-spacing: -0.04em;
            margin: 0;
        }

        .sf-page-subtitle {
            color: var(--sf-muted);
            margin: 6px 0 0;
            font-weight: 600;
        }

        .sf-panel {
            color: var(--sf-text);
            background: var(--sf-surface);
            border: 1px solid var(--sf-border-light);
            border-radius: 18px;
            box-shadow: var(--sf-soft-shadow);
        }

        .sf-panel-header {
            padding: 18px 22px;
            border-bottom: 1px solid var(--sf-border-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .sf-panel-title {
            margin: 0;
            color: var(--sf-text-strong);
            font-size: 16px;
            font-weight: 950;
            letter-spacing: -0.02em;
        }

        .sf-panel-subtitle {
            margin: 4px 0 0;
            color: var(--sf-muted);
            font-size: 12px;
            font-weight: 700;
        }

        .sf-panel-body {
            padding: 20px 22px;
        }

        .sf-stat-card {
            min-height: 122px;
            color: var(--sf-text);
            background: var(--sf-surface);
            border: 1px solid var(--sf-border-light);
            border-radius: 16px;
            padding: 20px 22px;
            box-shadow: var(--sf-soft-shadow);
            transition: border-color 0.16s ease, transform 0.16s ease, box-shadow 0.16s ease;
        }

        a .sf-stat-card:hover {
            border-color: rgba(249, 115, 22, 0.38);
            transform: translateY(-1px);
        }

        .sf-stat-label {
            margin: 0 0 12px;
            color: var(--sf-muted);
            font-weight: 800;
            font-size: 13px;
        }

        .sf-stat-value {
            margin: 0;
            color: var(--sf-text-strong);
            font-size: 28px;
            line-height: 1;
            font-weight: 950;
            letter-spacing: -0.04em;
        }

        .sf-stat-help {
            margin: 12px 0 0;
            color: var(--sf-muted);
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
            color: var(--sf-text-strong);
            background: var(--sf-surface);
            border: 1px solid var(--sf-border-light);
        }

        .sf-action-button.light:hover {
            color: var(--sf-text-strong);
            background: var(--sf-surface-soft);
        }

        .manager-theme-toggle {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border: 0;
            border-radius: 12px;
            padding: 10px;
            background: transparent;
            color: var(--sf-muted-strong);
            text-align: left;
            font-size: 13px;
            font-weight: 800;
        }

        .manager-theme-toggle:hover,
        .manager-theme-toggle:focus {
            background: var(--sf-row-hover);
            color: var(--sf-text-strong);
        }

        .manager-theme-label {
            display: block;
            color: var(--sf-text-strong);
            font-weight: 900;
        }

        .manager-theme-state {
            display: block;
            margin-top: 2px;
            color: var(--sf-muted);
            font-size: 11px;
            font-weight: 700;
        }

        .manager-theme-switch {
            position: relative;
            width: 44px;
            height: 24px;
            flex: 0 0 auto;
            border-radius: 999px;
            border: 1px solid var(--sf-theme-toggle-border);
            background: var(--sf-theme-toggle-bg);
            transition: background 0.18s ease, border-color 0.18s ease;
        }

        .manager-theme-switch::after {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 16px;
            height: 16px;
            border-radius: 999px;
            background: #f8fafc;
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.26);
            content: "";
            transition: transform 0.18s ease, background 0.18s ease;
        }

        html[data-theme="light"] .manager-theme-switch {
            background: #f97316;
            border-color: rgba(249, 115, 22, 0.40);
        }

        html[data-theme="light"] .manager-theme-switch::after {
            transform: translateX(20px);
            background: #fff7ed;
        }

        .manager-mobile-menu {
            border-top: 1px solid var(--sf-border-light);
            background: var(--sf-header);
        }

        .manager-mobile-menu-inner {
            width: min(1320px, calc(100% - 32px));
            margin: 0 auto;
            padding: 14px 0 18px;
        }

        .manager-mobile-nav {
            display: grid;
            gap: 8px;
        }

        .manager-mobile-nav a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-radius: 14px;
            padding: 12px 14px;
            color: var(--sf-muted-strong);
            background: var(--sf-surface);
            border: 1px solid var(--sf-border-light);
            font-weight: 900;
        }

        .manager-mobile-nav a.active {
            color: var(--sf-text-strong);
            background: var(--sf-orange-soft);
            border-color: rgba(249, 115, 22, 0.30);
        }

        .manager-mobile-utility {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--sf-border-light);
        }

        body.manager-theme-body .text-dark {
            color: var(--sf-text-strong) !important;
        }

        body.manager-theme-body .text-muted {
            color: var(--sf-muted) !important;
        }

        body.manager-theme-body .bg-light {
            background-color: var(--sf-surface-soft) !important;
            color: var(--sf-text-strong) !important;
        }

        body.manager-theme-body .border,
        body.manager-theme-body .border-bottom,
        body.manager-theme-body .border-top {
            border-color: var(--sf-border-light) !important;
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

            .manager-mobile-toggle {
                display: inline-flex;
            }

            .manager-nav-wrap {
                display: none;
            }

            .manager-header-left {
                flex: 1;
                justify-content: space-between;
            }
        }

        @media (max-width: 900px) {
            .manager-header-inner {
                min-height: 60px;
                padding: 8px 0;
            }

            .manager-header-left {
                width: 100%;
                align-items: center;
                flex-direction: row;
                gap: 10px;
            }

            .manager-user-area {
                margin-left: auto;
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

    <style>
        /*
        |--------------------------------------------------------------------------
        | Manager Theme Guardrails
        |--------------------------------------------------------------------------
        | Several legacy manager blades still carry page-local light-only rules.
        | Keep their structure intact, but force shared surfaces back onto the
        | manager theme tokens after all page styles have loaded.
        |--------------------------------------------------------------------------
        */
        body.manager-theme-body .sf-page-title {
            color: var(--sf-text-strong) !important;
        }

        body.manager-theme-body .sf-page-subtitle {
            color: var(--sf-muted) !important;
        }

        body.manager-theme-body .sf-panel,
        body.manager-theme-body .card,
        body.manager-theme-body .list-group-item,
        body.manager-theme-body .booking-stat-card,
        body.manager-theme-body .job-stat-card,
        body.manager-theme-body .invoice-stat-card,
        body.manager-theme-body .manager-stat-card,
        body.manager-theme-body .manager-bucket-card {
            background: var(--sf-surface) !important;
            border-color: var(--sf-border-light) !important;
            color: var(--sf-text) !important;
        }

        body.manager-theme-body .sf-panel-header,
        body.manager-theme-body .sf-panel-body,
        body.manager-theme-body .card-header,
        body.manager-theme-body .card-body,
        body.manager-theme-body .card-footer,
        body.manager-theme-body .manager-pagination,
        body.manager-theme-body .sf-empty,
        body.manager-theme-body .modal-body,
        body.manager-theme-body .modal-footer,
        body.manager-theme-body .schedule-modal-body,
        body.manager-theme-body .schedule-modal-footer,
        body.manager-theme-body .schedule-summary {
            background: var(--sf-surface) !important;
            border-color: var(--sf-border-light) !important;
            color: var(--sf-text) !important;
        }

        body.manager-theme-body .modal-content {
            background: var(--sf-surface) !important;
            border: 1px solid var(--sf-border-light) !important;
            color: var(--sf-text) !important;
        }

        body.manager-theme-body .sf-panel-title,
        body.manager-theme-body .sf-empty h3,
        body.manager-theme-body .card h1,
        body.manager-theme-body .card h2,
        body.manager-theme-body .card h3,
        body.manager-theme-body .card h4,
        body.manager-theme-body .card h5,
        body.manager-theme-body .card h6,
        body.manager-theme-body .fw-bold,
        body.manager-theme-body .fw-semibold,
        body.manager-theme-body .fw-black,
        body.manager-theme-body .text-dark,
        body.manager-theme-body .lead-primary,
        body.manager-theme-body .lead-value,
        body.manager-theme-body .booking-stat-value,
        body.manager-theme-body .job-stat-value,
        body.manager-theme-body .invoice-stat-value,
        body.manager-theme-body .summary-value {
            color: var(--sf-text-strong) !important;
        }

        body.manager-theme-body .sf-panel-subtitle,
        body.manager-theme-body .sf-empty p,
        body.manager-theme-body .text-muted,
        body.manager-theme-body .small,
        body.manager-theme-body .booking-stat-label,
        body.manager-theme-body .booking-stat-note,
        body.manager-theme-body .job-stat-label,
        body.manager-theme-body .job-stat-note,
        body.manager-theme-body .invoice-stat-label,
        body.manager-theme-body .invoice-stat-note,
        body.manager-theme-body .summary-label {
            color: var(--sf-muted) !important;
        }

        body.manager-theme-body .bg-white,
        body.manager-theme-body .bg-light,
        body.manager-theme-body .table-light,
        body.manager-theme-body .table-light th {
            background-color: var(--sf-surface-soft) !important;
            color: var(--sf-text-strong) !important;
        }

        body.manager-theme-body .table,
        body.manager-theme-body .manager-bookings-table,
        body.manager-theme-body .manager-jobs-table,
        body.manager-theme-body .manager-invoices-table,
        body.manager-theme-body .manager-opportunities-table {
            color: var(--sf-text) !important;
        }

        body.manager-theme-body .table thead th,
        body.manager-theme-body .manager-bookings-table thead th,
        body.manager-theme-body .manager-jobs-table thead th,
        body.manager-theme-body .manager-invoices-table thead th,
        body.manager-theme-body .manager-opportunities-table thead th {
            background: var(--sf-surface-soft) !important;
            border-color: var(--sf-border-light) !important;
            color: var(--sf-muted-strong) !important;
        }

        body.manager-theme-body .table tbody td,
        body.manager-theme-body .manager-bookings-table tbody td,
        body.manager-theme-body .manager-jobs-table tbody td,
        body.manager-theme-body .manager-invoices-table tbody td,
        body.manager-theme-body .manager-opportunities-table tbody td {
            background: var(--sf-surface) !important;
            border-color: var(--sf-border-light) !important;
            color: var(--sf-text) !important;
        }

        body.manager-theme-body .table tbody tr:hover td,
        body.manager-theme-body .manager-bookings-table tbody tr:hover td,
        body.manager-theme-body .manager-jobs-table tbody tr:hover td,
        body.manager-theme-body .manager-invoices-table tbody tr:hover td,
        body.manager-theme-body .manager-opportunities-table tbody tr:hover td {
            background: var(--sf-row-hover) !important;
        }

        body.manager-theme-body .form-label {
            color: var(--sf-muted-strong) !important;
        }

        body.manager-theme-body .form-control,
        body.manager-theme-body .form-select,
        body.manager-theme-body textarea,
        body.manager-theme-body input,
        body.manager-theme-body select {
            background-color: var(--sf-input-bg) !important;
            border-color: var(--sf-border-light) !important;
            color: var(--sf-input-text) !important;
        }

        body.manager-theme-body .form-control::placeholder,
        body.manager-theme-body textarea::placeholder,
        body.manager-theme-body input::placeholder {
            color: var(--sf-muted) !important;
        }

        body.manager-theme-body .manager-count-pill {
            background: var(--sf-surface-soft) !important;
            border-color: var(--sf-border-light) !important;
            color: var(--sf-text-strong) !important;
        }

        html[data-theme="dark"] body.manager-theme-body .booking-stat-card.warning,
        html[data-theme="dark"] body.manager-theme-body .job-stat-card.warning,
        html[data-theme="dark"] body.manager-theme-body .invoice-stat-card.warning {
            background: rgba(245, 158, 11, 0.12) !important;
        }

        html[data-theme="dark"] body.manager-theme-body .booking-stat-card.primary,
        html[data-theme="dark"] body.manager-theme-body .job-stat-card.primary,
        html[data-theme="dark"] body.manager-theme-body .invoice-stat-card.primary {
            background: rgba(37, 99, 235, 0.14) !important;
        }

        html[data-theme="dark"] body.manager-theme-body .booking-stat-card.success,
        html[data-theme="dark"] body.manager-theme-body .job-stat-card.success,
        html[data-theme="dark"] body.manager-theme-body .invoice-stat-card.success {
            background: rgba(22, 163, 74, 0.12) !important;
        }

        html[data-theme="dark"] body.manager-theme-body .booking-stat-card.danger,
        html[data-theme="dark"] body.manager-theme-body .job-stat-card.danger,
        html[data-theme="dark"] body.manager-theme-body .invoice-stat-card.danger {
            background: rgba(220, 38, 38, 0.12) !important;
        }
    </style>
</head>

<body class="manager-theme-body">

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
        [
            'label' => 'Clients',
            'route' => 'manager.clients.index',
            'active' => 'manager.clients.*',
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
            'label' => 'Reports',
            'route' => 'manager.growth.index',
            'active' => 'manager.growth.*',
            'safe' => false,
        ],

        [
            'label' => 'Inbox',
            'route' => 'manager.inbox.index',
            'active' => ['manager.inbox.*', 'manager.escalations', 'manager.conversation', 'manager.conversation.*'],
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

                <button
                    class="manager-mobile-toggle"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#managerMobileNav"
                    aria-controls="managerMobileNav"
                    aria-expanded="false"
                    aria-label="Open manager navigation"
                >
                    <span aria-hidden="true"></span>
                </button>

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

                            <button
                                type="button"
                                class="manager-theme-toggle"
                                data-sf-theme-toggle
                                aria-pressed="false"
                                aria-label="Toggle manager theme"
                            >
                                <span>
                                    <span class="manager-theme-label">Theme</span>
                                    <span class="manager-theme-state" data-sf-theme-label>Dark mode</span>
                                </span>
                                <span class="manager-theme-switch" aria-hidden="true"></span>
                            </button>

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

        <div class="collapse manager-mobile-menu" id="managerMobileNav">
            <div class="manager-mobile-menu-inner">
                <nav class="manager-mobile-nav" aria-label="Manager mobile navigation">
                    @foreach($managerNavItems as $item)
                        @if(Route::has($item['route']))
                            <a href="{{ route($item['route']) }}"
                               class="{{ $isRouteActive($item['active']) ? 'active' : '' }}">
                                <span>{{ $item['label'] }}</span>
                                @if($isRouteActive($item['active']))
                                    <span aria-hidden="true">Active</span>
                                @endif
                            </a>
                        @endif
                    @endforeach
                </nav>

                <div class="manager-mobile-utility">
                    <button
                        type="button"
                        class="manager-theme-toggle"
                        data-sf-theme-toggle
                        aria-pressed="false"
                        aria-label="Toggle manager theme"
                    >
                        <span>
                            <span class="manager-theme-label">Theme</span>
                            <span class="manager-theme-state" data-sf-theme-label>Dark mode</span>
                        </span>
                        <span class="manager-theme-switch" aria-hidden="true"></span>
                    </button>
                </div>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toggles = document.querySelectorAll('[data-sf-theme-toggle]');
        var labels = document.querySelectorAll('[data-sf-theme-label]');

        function applyTheme(theme) {
            if (theme !== 'light' && theme !== 'dark') {
                theme = 'dark';
            }

            document.documentElement.setAttribute('data-theme', theme);

            labels.forEach(function (label) {
                label.textContent = theme === 'light' ? 'Light mode' : 'Dark mode';
            });

            toggles.forEach(function (toggle) {
                toggle.setAttribute('aria-pressed', theme === 'light' ? 'true' : 'false');
            });

            try {
                localStorage.setItem('sayaraforce_theme', theme);
            } catch (e) {}
        }

        applyTheme(document.documentElement.getAttribute('data-theme') || 'dark');

        toggles.forEach(function (toggle) {
            toggle.addEventListener('click', function () {
                var activeTheme = document.documentElement.getAttribute('data-theme') || 'dark';
                applyTheme(activeTheme === 'dark' ? 'light' : 'dark');
            });
        });
    });
</script>

@stack('scripts')

</body>
</html>
