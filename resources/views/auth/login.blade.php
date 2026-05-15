<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SayaraForce</title>

    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>

<body class="min-h-screen bg-slate-950 text-white antialiased">

<div class="grid min-h-screen lg:grid-cols-2">

    {{-- Left Branding Panel --}}
    <div class="relative hidden overflow-hidden border-r border-white/10 bg-slate-900 lg:block">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(249,115,22,0.28),transparent_35%),radial-gradient(circle_at_bottom_right,rgba(59,130,246,0.18),transparent_35%)]"></div>

        <div class="relative flex h-full flex-col justify-between p-12">
            <a href="{{ route('public.home') }}" class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-500 text-lg font-black text-white shadow-lg shadow-orange-500/20">
                    SF
                </div>

                <div>
                    <div class="text-xl font-black tracking-tight text-white">
                        SayaraForce
                    </div>
                    <div class="text-sm text-slate-400">
                        Garage Growth CRM
                    </div>
                </div>
            </a>

            <div>
                <div class="mb-5 inline-flex rounded-full border border-orange-400/30 bg-orange-500/10 px-4 py-2 text-sm font-semibold text-orange-300">
                    Lead recovery system for garages
                </div>

                <h1 class="max-w-xl text-5xl font-black leading-tight tracking-tight text-white">
                    Manage leads, bookings, jobs and WhatsApp follow-ups in one place.
                </h1>

                <p class="mt-6 max-w-lg text-lg leading-8 text-slate-300">
                    Built for garages that want to stop losing enquiries and convert more customers into confirmed jobs.
                </p>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                    <div class="text-2xl font-black text-white">Leads</div>
                    <div class="mt-1 text-sm text-slate-400">Capture</div>
                </div>

                <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                    <div class="text-2xl font-black text-white">WA</div>
                    <div class="mt-1 text-sm text-slate-400">Follow-up</div>
                </div>

                <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                    <div class="text-2xl font-black text-white">Jobs</div>
                    <div class="mt-1 text-sm text-slate-400">Track</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Right Login Panel --}}
    <div class="flex min-h-screen items-center justify-center px-6 py-10">
        <div class="w-full max-w-md">

            {{-- Mobile Logo --}}
            <div class="mb-8 text-center lg:hidden">
                <a href="{{ route('public.home') }}" class="inline-flex items-center justify-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-500 text-lg font-black text-white shadow-lg shadow-orange-500/20">
                        SF
                    </div>

                    <div class="text-left">
                        <div class="text-xl font-black tracking-tight text-white">
                            SayaraForce
                        </div>
                        <div class="text-sm text-slate-400">
                            Garage Growth CRM
                        </div>
                    </div>
                </a>
            </div>

            <div class="rounded-[2rem] border border-white/10 bg-white/5 p-8 shadow-2xl backdrop-blur">
                <div class="mb-8">
                    <h2 class="text-3xl font-black tracking-tight text-white">
                        Welcome back
                    </h2>
                    <p class="mt-2 text-sm text-slate-400">
                        Login to your garage workspace.
                    </p>
                </div>

                @if (session('status'))
                    <div class="mb-5 rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    {{-- Email Address --}}
                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold text-slate-300">
                            Email Address
                        </label>

                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="admin@garage.com"
                            class="w-full rounded-2xl border border-white/10 bg-slate-950 px-4 py-3 text-white outline-none ring-orange-500 placeholder:text-slate-600 focus:ring-2"
                        >
                    </div>

                    {{-- Password --}}
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label for="password" class="block text-sm font-semibold text-slate-300">
                                Password
                            </label>

                            @if (Route::has('password.request'))
                                <a
                                    href="{{ route('password.request') }}"
                                    class="text-sm font-semibold text-orange-400 hover:text-orange-300"
                                >
                                    Forgot?
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
                            class="w-full rounded-2xl border border-white/10 bg-slate-950 px-4 py-3 text-white outline-none ring-orange-500 placeholder:text-slate-600 focus:ring-2"
                        >
                    </div>

                    {{-- Remember Me --}}
                    <div class="flex items-center justify-between">
                        <label for="remember_me" class="flex items-center gap-2 text-sm text-slate-400">
                            <input
                                id="remember_me"
                                type="checkbox"
                                name="remember"
                                class="h-4 w-4 rounded border-white/10 bg-slate-950 text-orange-500 focus:ring-orange-500"
                            >

                            <span>Remember me</span>
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-2xl bg-orange-500 px-6 py-4 text-base font-black text-white shadow-xl shadow-orange-500/20 transition hover:bg-orange-600"
                    >
                        Login
                    </button>
                </form>

                <div class="mt-6 border-t border-white/10 pt-6 text-center text-sm text-slate-400">
                    New to SayaraForce?

                    <a
                        href="{{ route('public.home') }}#audit"
                        class="font-bold text-orange-400 hover:text-orange-300"
                    >
                        Request a free lead recovery audit
                    </a>
                </div>
            </div>

            <div class="mt-6 text-center text-xs text-slate-600">
                © {{ date('Y') }} SayaraForce. Built for UAE garages.
            </div>
        </div>
    </div>
</div>

</body>
</html>