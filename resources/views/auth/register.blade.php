<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create your garage — SayaraForce</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="min-h-screen bg-[#07112A] text-white antialiased">
<main class="mx-auto flex min-h-screen max-w-6xl items-center px-5 py-10 sm:px-8">
    <div class="grid w-full overflow-hidden rounded-[2rem] border border-white/10 bg-[#0D1B3D]/95 shadow-2xl shadow-black/30 lg:grid-cols-[0.8fr_1.2fr]">
        <section class="border-b border-white/10 bg-[radial-gradient(circle_at_top_left,rgba(255,106,0,0.22),transparent_44%)] p-7 lg:border-b-0 lg:border-r lg:p-10">
            <a href="{{ route('public.home') }}" class="inline-flex" aria-label="SayaraForce home">
                <img src="{{ asset('images/brand/sayaraforce-logo-horizontal.png') }}" alt="SayaraForce" class="h-11 w-auto">
            </a>
            <p class="mt-10 inline-flex rounded-full border border-[#FF6A00]/35 bg-[#FF6A00]/10 px-4 py-2 text-sm font-semibold text-[#FF9A52]">
                Garage self-service onboarding
            </p>
            <h1 class="mt-5 text-4xl leading-tight tracking-tight">Create your SayaraForce workspace.</h1>
            <p class="mt-4 text-sm leading-7 text-[#C5D0E2]">
                Register your garage, create its administrator, then continue to the WhatsApp connection screen. No provider connection or message is created during registration.
            </p>
            <ul class="mt-8 space-y-3 text-sm text-[#D2DAEA]">
                <li>One isolated workspace for your garage</li>
                <li>You become the first garage administrator</li>
                <li>WhatsApp setup remains a separate, consented step</li>
            </ul>
        </section>

        <section class="p-7 sm:p-9 lg:p-10">
            <div class="mb-7">
                <h2 class="text-2xl font-semibold">Garage and administrator details</h2>
                <p class="mt-2 text-sm text-[#AEBBD0]">All fields are required. Use synthetic details in staging.</p>
            </div>

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-400/25 bg-red-500/10 px-4 py-3 text-sm text-red-200" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="grid gap-5 sm:grid-cols-2">
                @csrf

                <div class="sm:col-span-2">
                    <label for="garage_name" class="mb-2 block text-sm font-semibold text-[#D2DAEA]">Garage or business name</label>
                    <input id="garage_name" name="garage_name" value="{{ old('garage_name', $quickScan?->garage_name) }}" required autofocus autocomplete="organization" maxlength="191" class="w-full rounded-xl border border-white/15 bg-[#09142E] px-4 py-3 text-white outline-none placeholder:text-[#75849D]" placeholder="Example Test Garage">
                </div>

                <div>
                    <label for="name" class="mb-2 block text-sm font-semibold text-[#D2DAEA]">Administrator name</label>
                    <input id="name" name="name" value="{{ old('name', $quickScan?->garage_contact_name) }}" required autocomplete="name" maxlength="255" class="w-full rounded-xl border border-white/15 bg-[#09142E] px-4 py-3 text-white outline-none placeholder:text-[#75849D]" placeholder="Garage administrator">
                </div>

                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-[#D2DAEA]">Administrator email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $quickScan?->garage_email) }}" required autocomplete="email" maxlength="255" class="w-full rounded-xl border border-white/15 bg-[#09142E] px-4 py-3 text-white outline-none placeholder:text-[#75849D]" placeholder="admin@example.test">
                </div>

                <div>
                    <label for="phone" class="mb-2 block text-sm font-semibold text-[#D2DAEA]">Business phone</label>
                    <input id="phone" name="phone" value="{{ old('phone', $quickScan?->garage_phone) }}" required autocomplete="tel" maxlength="30" class="w-full rounded-xl border border-white/15 bg-[#09142E] px-4 py-3 text-white outline-none placeholder:text-[#75849D]" placeholder="+971 50 000 0000">
                </div>

                <div>
                    <label for="address" class="mb-2 block text-sm font-semibold text-[#D2DAEA]">Garage address</label>
                    <input id="address" name="address" value="{{ old('address') }}" required autocomplete="street-address" maxlength="255" class="w-full rounded-xl border border-white/15 bg-[#09142E] px-4 py-3 text-white outline-none placeholder:text-[#75849D]" placeholder="Dubai, UAE">
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-[#D2DAEA]">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password" class="w-full rounded-xl border border-white/15 bg-[#09142E] px-4 py-3 text-white outline-none">
                </div>

                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-[#D2DAEA]">Confirm password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="w-full rounded-xl border border-white/15 bg-[#09142E] px-4 py-3 text-white outline-none">
                </div>

                <label class="sm:col-span-2 flex items-start gap-3 text-sm leading-6 text-[#AEBBD0]">
                    <input type="checkbox" name="terms" value="1" required class="mt-1 h-4 w-4 rounded border-white/20 bg-[#09142E] text-[#FF6A00] focus:ring-[#FF6A00]">
                    <span>I confirm these are authorised garage details and accept the <a href="{{ route('terms') }}" class="font-semibold text-[#FF9A52]">terms</a>.</span>
                </label>

                @if($quickScanToken)
                    <input type="hidden" name="quick_scan_token" value="{{ $quickScanToken }}">
                    <div class="sm:col-span-2 rounded-2xl border border-emerald-400/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                        Your Quick Scan is ready for normal onboarding. History stays quarantined and no Client is created until you review and choose Track.
                    </div>
                @endif

                <button type="submit" class="sm:col-span-2 rounded-xl bg-[#FF6A00] px-6 py-3.5 text-base font-semibold text-white shadow-xl shadow-[#FF6A00]/20 transition hover:bg-[#E85F00]">
                    Create garage workspace
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-[#AEBBD0]">
                Already registered?
                <a href="{{ route('login') }}" class="font-semibold text-[#FF9A52]">Sign in</a>
            </p>
        </section>
    </div>
</main>
</body>
</html>
