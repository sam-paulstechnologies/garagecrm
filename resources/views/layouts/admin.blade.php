<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Administration') — SayaraForce</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <script>
        (function () {
            try {
                document.documentElement.setAttribute('data-theme', localStorage.getItem('sayaraforce_theme') || 'dark');
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>

<body class="sf-legacy-shell min-h-screen antialiased">
    <header class="sticky top-0 z-40 border-b border-[var(--sf-border)] bg-[var(--sf-bg)]/95 backdrop-blur">
        <div class="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center rounded-xl focus:outline-none">
                <img
                    src="{{ asset('images/brand/sayaraforce-logo-horizontal.png') }}"
                    alt="SayaraForce"
                    width="1153"
                    height="326"
                    class="h-9 w-auto object-contain"
                >
            </a>

            <nav class="flex items-center gap-3 text-sm" aria-label="Administration">
                @if(\Illuminate\Support\Facades\Route::has('admin.documents.index'))
                    <a
                        href="{{ route('admin.documents.index') }}"
                        class="rounded-lg px-3 py-2 font-semibold text-[var(--sf-muted-strong)] transition hover:bg-[var(--sf-hover)] hover:text-[var(--sf-text-strong)]"
                    >
                        Documents
                    </a>
                @endif

                @auth
                    <span class="hidden text-[var(--sf-muted)] sm:inline">{{ auth()->user()->name }}</span>
                @endauth
            </nav>
        </div>
    </header>

    <main class="py-6">
        @yield('content')
    </main>

    @auth
        @include('admin.partials.whatsapp-popup')
    @endauth
</body>
</html>
