<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} — SayaraForce</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    @vite('resources/css/app.css')
</head>
<body class="sf-auth-shell min-h-screen bg-[#07112A] text-white antialiased">
    <main class="flex min-h-screen items-center justify-center px-5 py-10">
        <section class="w-full max-w-lg rounded-[2rem] border border-white/10 bg-[#0D1B3D] p-7 text-center shadow-2xl sm:p-10">
            <a href="{{ route('public.home') }}" class="inline-flex" aria-label="SayaraForce home">
                <img
                    src="{{ asset('images/brand/sayaraforce-logo-horizontal.png') }}"
                    alt="SayaraForce"
                    width="1153"
                    height="326"
                    class="h-11 w-auto object-contain"
                >
            </a>

            <p class="mt-8 text-sm font-semibold uppercase tracking-[0.18em] text-[#FF8A38]">{{ $code }}</p>
            <h1 class="mt-3 text-3xl leading-tight text-white sm:text-4xl">{{ $title }}</h1>
            <p class="mx-auto mt-4 max-w-md text-sm leading-6 text-[#AEBBD0] sm:text-base">{{ $message }}</p>

            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('public.home') }}" class="mt-7 inline-flex min-h-11 items-center justify-center rounded-xl bg-[#FF6A00] px-5 text-sm font-semibold text-white transition hover:bg-[#E85F00]">
                Go back
            </a>
        </section>
    </main>
</body>
</html>
