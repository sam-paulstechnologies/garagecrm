<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'SayaraForce'))</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    {{-- Vite --}}
    @viteReactRefresh
    @vite(['resources/js/app.jsx'])

    @stack('styles')
</head>

<body class="font-sans antialiased bg-[#050914] text-slate-100">

    <div class="min-h-screen bg-[#050914] relative overflow-x-hidden">

        {{-- Background Glow --}}
        <div class="pointer-events-none fixed inset-0 -z-10">
            <div class="absolute left-1/2 top-0 h-[420px] w-[720px] -translate-x-1/2 rounded-full bg-orange-500/10 blur-3xl"></div>
            <div class="absolute right-[-160px] top-24 h-[360px] w-[360px] rounded-full bg-blue-600/10 blur-3xl"></div>
            <div class="absolute bottom-[-220px] left-[-120px] h-[420px] w-[420px] rounded-full bg-orange-600/10 blur-3xl"></div>
        </div>

        {{-- Navigation --}}
        @if(View::exists('layouts.navigation'))
            @include('layouts.navigation')
        @endif

        {{-- Optional Header --}}
        @hasSection('header')
            <header class="border-b border-white/10 bg-slate-950/80 backdrop-blur">
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

    @stack('scripts')

</body>
</html>