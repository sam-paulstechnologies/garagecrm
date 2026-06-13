<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'Garage CRM') }}</title>

    {{-- Prevent theme flash before page loads --}}
    <script>
        (function () {
            try {
                var savedTheme = localStorage.getItem('sayaraforce_theme') || 'dark';
                document.documentElement.setAttribute('data-theme', savedTheme);
                document.documentElement.classList.toggle('dark', savedTheme === 'dark');
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    {{-- Scripts --}}
    @routes
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
    @inertiaHead

    <style>
        [x-cloak] {
            display: none !important;
        }

        :root {
            color-scheme: dark;

            --sf-page-bg: #050914;
            --sf-page-text: #f8fafc;
            --sf-border: rgba(255, 255, 255, 0.10);
            --sf-toggle-bg: rgba(255, 255, 255, 0.10);
            --sf-toggle-border: rgba(255, 255, 255, 0.16);
            --sf-toggle-text: #ffffff;
        }

        html[data-theme="light"] {
            color-scheme: light;

            --sf-page-bg: #f4f7fb;
            --sf-page-text: #0f172a;
            --sf-border: rgba(15, 23, 42, 0.10);
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

        .sf-theme-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 999px;
            border: 1px solid var(--sf-toggle-border);
            background: var(--sf-toggle-bg);
            padding: 0.6rem 0.9rem;
            color: var(--sf-toggle-text);
            font-size: 0.8rem;
            font-weight: 800;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.18);
            transition: all 0.2s ease;
        }

        .sf-theme-toggle:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.18);
        }

        #app-loader {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        #app-loader.hidden {
            opacity: 0;
            visibility: hidden;
        }

        .car {
            position: relative;
            width: 120px;
            height: 60px;
        }

        .car-body {
            position: absolute;
            top: 14px;
            width: 120px;
            height: 34px;
            border-radius: 6px;
            background: #2563eb;
        }

        .car-top {
            position: absolute;
            top: 0;
            left: 30px;
            width: 60px;
            height: 20px;
            border-radius: 6px 6px 0 0;
            background: #2563eb;
        }

        .wheel {
            position: absolute;
            bottom: -4px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #111827;
            animation: spin 0.8s linear infinite;
        }

        .wheel.left { left: 20px; }
        .wheel.right { right: 20px; }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .sf-public-nav {
            border-bottom: 1px solid rgba(255, 255, 255, 0.10);
            background: rgba(5, 9, 20, 0.88);
            backdrop-filter: blur(18px);
        }

        .sf-public-nav a {
            color: #cbd5e1;
        }

        .sf-public-nav a:hover {
            color: #ffffff;
        }

        .sf-public-nav .sf-public-cta {
            background: #f97316;
            color: #ffffff;
            box-shadow: 0 14px 30px rgba(249, 115, 22, 0.24);
        }

        .sf-public-nav .sf-public-cta:hover {
            background: #ea580c;
            color: #ffffff;
        }
    </style>
</head>

<body class="font-sans antialiased sf-theme-body">
    <div id="app-loader">
        <div class="car">
            <div class="car-top"></div>
            <div class="car-body"></div>
            <div class="wheel left"></div>
            <div class="wheel right"></div>
        </div>
    </div>

    <div class="min-h-screen relative overflow-x-hidden sf-app-shell">
        {{-- Theme Toggle --}}
        @auth
        <div class="fixed right-4 top-4 z-50">
            <button type="button" id="sfThemeToggle" class="sf-theme-toggle">
                <span id="sfThemeIcon">🌙</span>
                <span id="sfThemeLabel">Dark</span>
            </button>
        </div>
        @endauth

        {{-- Navigation --}}
        @auth
            @if(View::exists('layouts.navigation') && (request()->routeIs('admin.*') || request()->routeIs('manager.*')))
                @include('layouts.navigation')
            @endif
        @else
            <nav class="sf-public-nav sticky top-0 z-40">
                <div class="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <a href="{{ route('public.home') }}" class="flex shrink-0 items-center gap-3 rounded-2xl focus:outline-none focus:ring-2 focus:ring-orange-400">
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-orange-500 text-sm font-extrabold text-white shadow-lg shadow-orange-950/30">
                            SF
                        </span>

                        <span class="hidden text-sm font-extrabold tracking-tight text-white sm:block">
                            SayaraForce
                        </span>
                    </a>

                    <div class="hidden items-center gap-5 text-sm font-bold lg:flex">
                        <a href="{{ route('public.home') }}#problem">Problem</a>
                        <a href="{{ route('public.home') }}#solution">Solution</a>
                        <a href="{{ route('public.home') }}#retention">Retention</a>
                        <a href="{{ route('public.home') }}#pricing">Pricing</a>
                        <a href="{{ route('public.home') }}#audit">Audit</a>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('login') }}" class="hidden rounded-xl px-3 py-2 text-sm font-extrabold text-white sm:inline-flex">
                            Login
                        </a>

                        <a href="{{ route('public.home') }}#audit" class="sf-public-cta inline-flex h-10 items-center rounded-xl px-4 text-sm font-extrabold transition">
                            Get Free Audit
                        </a>
                    </div>
                </div>
            </nav>
        @endauth

        @inertia
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toggle = document.getElementById('sfThemeToggle');
            var icon = document.getElementById('sfThemeIcon');
            var label = document.getElementById('sfThemeLabel');

            function applyTheme(theme) {
                document.documentElement.setAttribute('data-theme', theme);
                document.documentElement.classList.toggle('dark', theme === 'dark');

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
</body>
</html>
