<!-- resources/views/layouts/guest.blade.php -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'SayaraForce')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.jsx']) {{-- ✅ updated --}}
</head>
<body class="sf-auth-shell min-h-screen antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center bg-[radial-gradient(circle_at_top_left,rgba(255,106,0,0.14),transparent_34%),linear-gradient(145deg,#07112A,#0D1B3D)] px-5 py-8">
        <div>
            <a href="{{ route('public.home') }}" aria-label="SayaraForce home">
                <x-application-logo class="sf-brand-logo--horizontal" />
            </a>
        </div>

        <div class="mt-7 w-full overflow-hidden rounded-3xl border border-white/10 bg-white px-6 py-6 text-[#0D1B3D] shadow-2xl sm:max-w-md sm:px-8">
            {{ $slot }}
        </div>

        <p class="mt-6 text-center text-xs text-[#AEBBD0]">
            &copy; {{ date('Y') }} SayaraForce. Growth Engine for UAE Garages.
        </p>
    </div>
</body>
</html>
