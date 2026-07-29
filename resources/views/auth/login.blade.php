<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — SayaraForce</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>

<body class="sf-auth-shell min-h-screen bg-[#07112A] text-white antialiased">
<main class="grid min-h-screen lg:grid-cols-[1.08fr_0.92fr]">
    <section class="relative hidden overflow-hidden border-r border-white/10 bg-[#0D1B3D] lg:flex lg:min-h-screen lg:flex-col">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,106,0,0.2),transparent_33%),radial-gradient(circle_at_bottom_right,rgba(41,69,121,0.46),transparent_38%)]"></div>
        <div class="relative flex h-full flex-col justify-between p-10 xl:p-12">
            <a href="{{ route('public.home') }}" class="inline-flex w-fit items-center" aria-label="SayaraForce home">
                <img
                    src="{{ asset('images/brand/sayaraforce-logo-horizontal.png') }}"
                    alt="SayaraForce"
                    width="1153"
                    height="326"
                    class="h-12 w-auto object-contain"
                >
            </a>

            <div class="max-w-2xl py-12">
                <p class="mb-5 inline-flex rounded-full border border-[#FF6A00]/35 bg-[#FF6A00]/10 px-4 py-2 text-sm font-semibold text-[#FF9A52]">
                    Growth Engine for UAE Garages
                </p>

                <h1 class="max-w-2xl text-4xl leading-[1.08] tracking-tight text-white xl:text-5xl">
                    Manage leads, bookings, jobs and WhatsApp follow-ups in one place.
                </h1>

                <p class="mt-6 max-w-xl text-base leading-7 text-[#D2DAEA] xl:text-lg xl:leading-8">
                    A clear operational workspace for capturing enquiries, coordinating follow-up and keeping garage teams aligned.
                </p>
            </div>

            <div class="grid grid-cols-3 gap-3 xl:gap-4">
                @foreach ([
                    ['Lead capture', 'Enquiries organised'],
                    ['Follow-up', 'WhatsApp-enabled'],
                    ['Workshop flow', 'Bookings to jobs'],
                ] as [$label, $description])
                    <div class="rounded-2xl border border-white/10 bg-white/[0.055] p-4 xl:p-5">
                        <p class="text-sm font-semibold text-white xl:text-base">{{ $label }}</p>
                        <p class="mt-1 text-xs leading-5 text-[#AEBBD0]">{{ $description }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="relative flex min-h-screen items-center justify-center overflow-hidden px-5 py-8 sm:px-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,106,0,0.12),transparent_32%)] lg:hidden"></div>
        <div class="relative w-full max-w-md">
            <div class="mb-8 flex justify-center lg:hidden">
                <a href="{{ route('public.home') }}" aria-label="SayaraForce home">
                    <img
                        src="{{ asset('images/brand/sayaraforce-logo-horizontal.png') }}"
                        alt="SayaraForce"
                        width="1153"
                        height="326"
                        class="h-11 w-auto max-w-[240px] object-contain"
                    >
                </a>
            </div>

            <div class="rounded-[1.75rem] border border-white/10 bg-[#0D1B3D]/90 p-6 shadow-2xl shadow-black/25 backdrop-blur sm:p-8">
                <div class="mb-7">
                    <h2 class="text-3xl leading-tight tracking-tight text-white">Welcome back</h2>
                    <p class="mt-2 text-sm leading-6 text-[#AEBBD0]">Sign in to your SayaraForce workspace.</p>
                </div>

                @if (session('status'))
                    <div class="mb-5 rounded-2xl border border-emerald-400/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 rounded-2xl border border-red-400/25 bg-red-500/10 px-4 py-3 text-sm text-red-200" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold text-[#D2DAEA]">Email address</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="you@company.com"
                            class="w-full rounded-xl border border-white/15 bg-[#09142E] px-4 py-3 text-white outline-none placeholder:text-[#75849D]"
                        >
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between gap-4">
                            <label for="password" class="block text-sm font-semibold text-[#D2DAEA]">Password</label>

                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="rounded text-sm font-semibold text-[#FF8A38] transition hover:text-[#FFB079]">
                                    Forgot password?
                                </a>
                            @endif
                        </div>

                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            class="w-full rounded-xl border border-white/15 bg-[#09142E] px-4 py-3 text-white outline-none placeholder:text-[#75849D]"
                        >
                    </div>

                    <label for="remember_me" class="flex w-fit items-center gap-2 text-sm text-[#AEBBD0]">
                        <input
                            id="remember_me"
                            type="checkbox"
                            name="remember"
                            class="h-4 w-4 rounded border-white/20 bg-[#09142E] text-[#FF6A00] focus:ring-[#FF6A00]"
                        >
                        <span>Remember me</span>
                    </label>

                    <button
                        type="submit"
                        class="w-full rounded-xl bg-[#FF6A00] px-6 py-3.5 text-base font-semibold text-white shadow-xl shadow-[#FF6A00]/20 transition hover:bg-[#E85F00]"
                    >
                        Log in
                    </button>
                </form>

                <div class="mt-6 border-t border-white/10 pt-6 text-center text-sm leading-6 text-[#AEBBD0]">
                    New to SayaraForce?
                    <a href="{{ route('public.home') }}#audit" class="font-semibold text-[#FF8A38] transition hover:text-[#FFB079]">
                        Book a free audit
                    </a>
                </div>
            </div>

            <p class="mt-6 text-center text-xs text-[#75849D]">
                &copy; {{ date('Y') }} SayaraForce. Built for UAE garages.
            </p>
        </div>
    </section>
</main>
</body>
</html>
