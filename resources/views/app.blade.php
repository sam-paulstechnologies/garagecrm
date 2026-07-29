<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>SayaraForce</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

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

            --sf-page-bg: var(--sf-bg);
            --sf-page-text: var(--sf-text);
            --sf-toggle-bg: var(--sf-hover);
            --sf-toggle-border: var(--sf-border-strong);
            --sf-toggle-text: var(--sf-text-strong);
        }

        html[data-theme="light"] {
            color-scheme: light;

            --sf-page-bg: var(--sf-bg);
            --sf-page-text: var(--sf-text);
            --sf-toggle-bg: var(--sf-surface);
            --sf-toggle-border: var(--sf-border-strong);
            --sf-toggle-text: var(--sf-text-strong);
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
            font-weight: 600;
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
            background: var(--sf-bg);
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        #app-loader.hidden {
            opacity: 0;
            visibility: hidden;
        }

        .sf-app-loader-logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
            animation: sf-loader-pulse 1.1s ease-in-out infinite alternate;
        }

        @keyframes sf-loader-pulse {
            from { opacity: 0.72; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
        }

        .sf-public-nav {
            border-bottom: 1px solid rgba(255, 255, 255, 0.10);
            background: rgba(7, 17, 42, 0.92);
            backdrop-filter: blur(18px);
        }

        .sf-public-nav a {
            color: #cbd5e1;
        }

        .sf-public-nav a:hover {
            color: #ffffff;
        }

        .sf-public-nav .sf-public-cta {
            background: #ff6a00;
            color: #ffffff;
            box-shadow: 0 14px 30px rgba(255, 106, 0, 0.24);
        }

        .sf-public-nav .sf-public-cta:hover {
            background: #e85f00;
            color: #ffffff;
        }
    </style>
</head>

<body class="antialiased sf-theme-body">
    <div id="app-loader">
        <img
            src="{{ asset('images/brand/sayaraforce-app-icon.png') }}"
            alt=""
            width="168"
            height="164"
            class="sf-app-loader-logo"
        >
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
                @php
                    $isInboxShellRoute = request()->routeIs('admin.inbox.*')
                        || request()->routeIs('manager.inbox.*')
                        || request()->routeIs('manager.escalations');

                    $useAdminFullWidthShell = (
                        request()->routeIs('admin.*')
                        || request()->routeIs('super-admin.*')
                        || $isInboxShellRoute
                    ) && strtolower(trim((string) auth()->user()->role)) !== 'media_team';
                @endphp

                @include('layouts.navigation')
            @endif
        @else
            @unless(request()->routeIs([
                'login',
                'register',
                'password.*',
                'verification.*',
            ]))
            <nav class="sf-public-nav sticky top-0 z-40">
                <div class="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <a href="{{ route('public.home') }}" class="flex shrink-0 items-center rounded-2xl focus:outline-none focus:ring-2 focus:ring-orange-400">
                        <img
                            src="{{ asset('images/brand/sayaraforce-logo-horizontal.png') }}"
                            alt="SayaraForce"
                            width="1153"
                            height="326"
                            class="h-9 w-auto object-contain"
                        >
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
            @endunless
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
