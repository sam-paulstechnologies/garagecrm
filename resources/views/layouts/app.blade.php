<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'SayaraForce'))</title>

    {{-- Prevent theme flash before page loads --}}
    <script>
        (function () {
            try {
                var savedTheme = localStorage.getItem('sayaraforce_theme') || 'dark';
                document.documentElement.setAttribute('data-theme', savedTheme);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    {{-- Vite --}}
    @viteReactRefresh
    @vite(['resources/js/app.jsx'])

    <style>
        :root {
            color-scheme: dark;

            --sf-page-bg: #050914;
            --sf-page-bg-soft: #0f172a;
            --sf-page-text: #f8fafc;
            --sf-page-muted: #cbd5e1;
            --sf-panel-bg: #ffffff;
            --sf-panel-text: #020617;
            --sf-border: rgba(255, 255, 255, 0.10);
            --sf-header-bg: rgba(15, 23, 42, 0.80);
            --sf-toggle-bg: rgba(255, 255, 255, 0.10);
            --sf-toggle-border: rgba(255, 255, 255, 0.16);
            --sf-toggle-text: #ffffff;
        }

        html[data-theme="light"] {
            color-scheme: light;

            --sf-page-bg: #f4f7fb;
            --sf-page-bg-soft: #ffffff;
            --sf-page-text: #0f172a;
            --sf-page-muted: #475569;
            --sf-panel-bg: #ffffff;
            --sf-panel-text: #020617;
            --sf-border: rgba(15, 23, 42, 0.10);
            --sf-header-bg: rgba(255, 255, 255, 0.92);
            --sf-toggle-bg: #ffffff;
            --sf-toggle-border: #cbd5e1;
            --sf-toggle-text: #0f172a;
        }

        body.sf-theme-body {
            background: var(--sf-page-bg) !important;
            color: var(--sf-page-text) !important;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .sf-app-shell {
            background: var(--sf-page-bg) !important;
            color: var(--sf-page-text) !important;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .sf-theme-header {
            border-color: var(--sf-border) !important;
            background: var(--sf-header-bg) !important;
            color: var(--sf-page-text) !important;
        }

        .sf-theme-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 38px;
            padding: 0 14px;
            border-radius: 999px;
            border: 1px solid var(--sf-toggle-border);
            background: var(--sf-toggle-bg);
            color: var(--sf-toggle-text);
            font-size: 12px;
            font-weight: 900;
            letter-spacing: -0.01em;
            cursor: pointer;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.14);
            transition: all 0.2s ease;
        }

        .sf-theme-toggle:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.18);
        }

        html[data-theme="light"] .sf-dark-glow {
            display: none !important;
        }

        html[data-theme="light"] .sf-page-title,
        html[data-theme="light"] .sf-page-subtitle {
            color: var(--sf-page-text) !important;
        }

        html[data-theme="light"] .text-slate-100,
        html[data-theme="light"] .text-white,
        html[data-theme="light"] .text-gray-100 {
            color: #0f172a !important;
        }

        html[data-theme="light"] .text-slate-200,
        html[data-theme="light"] .text-slate-300,
        html[data-theme="light"] .text-gray-300,
        html[data-theme="light"] .text-gray-400 {
            color: #475569 !important;
        }

        html[data-theme="light"] .bg-\[\#050914\],
        html[data-theme="light"] .bg-slate-950,
        html[data-theme="light"] .bg-slate-950\/80 {
            background-color: #f4f7fb !important;
        }

        html[data-theme="light"] .border-white\/10 {
            border-color: rgba(15, 23, 42, 0.10) !important;
        }

        html[data-theme="light"] .sf-panel {
            background: #ffffff !important;
            color: #020617 !important;
            border-color: #d9e1ec !important;
        }

        html[data-theme="light"] .sf-panel-header {
            background: linear-gradient(180deg, #ffffff, #f8fafc) !important;
            border-color: #e5eaf1 !important;
        }

        html[data-theme="light"] .sf-panel-title,
        html[data-theme="light"] .sf-panel-subtitle {
            color: #020617 !important;
        }
    </style>

    @stack('styles')
</head>

<body class="font-sans antialiased sf-theme-body">

    <div class="min-h-screen relative overflow-x-hidden sf-app-shell">

        {{-- Background Glow --}}
        <div class="pointer-events-none fixed inset-0 -z-10 sf-dark-glow">
            <div class="absolute left-1/2 top-0 h-[420px] w-[720px] -translate-x-1/2 rounded-full bg-orange-500/10 blur-3xl"></div>
            <div class="absolute right-[-160px] top-24 h-[360px] w-[360px] rounded-full bg-blue-600/10 blur-3xl"></div>
            <div class="absolute bottom-[-220px] left-[-120px] h-[420px] w-[420px] rounded-full bg-orange-600/10 blur-3xl"></div>
        </div>

        {{-- Theme Toggle --}}
        <div class="fixed right-4 top-4 z-50">
            <button type="button" id="sfThemeToggle" class="sf-theme-toggle">
                <span id="sfThemeIcon">🌙</span>
                <span id="sfThemeLabel">Dark</span>
            </button>
        </div>

        {{-- Navigation --}}
        @if(View::exists('layouts.navigation'))
            @include('layouts.navigation')
        @endif

        {{-- Optional Header --}}
        @hasSection('header')
            <header class="border-b backdrop-blur sf-theme-header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    @yield('header')
                </div>
            </header>
        @endif

        {{-- Main Content --}}
        <main class="relative py-6">
            @yield('content')
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toggle = document.getElementById('sfThemeToggle');
            var icon = document.getElementById('sfThemeIcon');
            var label = document.getElementById('sfThemeLabel');

            function applyTheme(theme) {
                document.documentElement.setAttribute('data-theme', theme);

                if (icon && label) {
                    if (theme === 'light') {
                        icon.textContent = '☀️';
                        label.textContent = 'Light';
                    } else {
                        icon.textContent = '🌙';
                        label.textContent = 'Dark';
                    }
                }

                try {
                    localStorage.setItem('sayaraforce_theme', theme);
                } catch (e) {}
            }

            var currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
            applyTheme(currentTheme);

            if (toggle) {
                toggle.addEventListener('click', function () {
                    var activeTheme = document.documentElement.getAttribute('data-theme') || 'dark';
                    var nextTheme = activeTheme === 'dark' ? 'light' : 'dark';

                    applyTheme(nextTheme);
                });
            }
        });
    </script>

    @stack('scripts')

</body>
</html>