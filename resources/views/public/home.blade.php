<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SayaraForce — Lead Recovery System for Garages</title>

    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>

<body class="bg-slate-950 text-white antialiased">

    {{-- Header --}}
    <header class="sticky top-0 z-50 border-b border-white/10 bg-slate-950/90 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
            <a href="{{ route('public.home') }}" class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-orange-500 font-black text-white">
                    SF
                </div>
                <div>
                    <div class="text-lg font-bold tracking-tight">SayaraForce</div>
                    <div class="text-xs text-slate-400">Garage Growth CRM</div>
                </div>
            </a>

            <nav class="hidden items-center gap-8 text-sm text-slate-300 md:flex">
                <a href="#problem" class="hover:text-white">Problem</a>
                <a href="#solution" class="hover:text-white">Solution</a>
                <a href="#pricing" class="hover:text-white">Pricing</a>
                <a href="#audit" class="hover:text-white">Audit</a>
            </nav>

            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}"
                   class="hidden rounded-xl px-4 py-2 text-sm font-semibold text-slate-300 hover:bg-white/10 hover:text-white sm:inline-flex">
                    Login
                </a>

                <a href="#audit"
                   class="rounded-xl bg-orange-500 px-4 py-2 text-sm font-bold text-white shadow-lg shadow-orange-500/20 hover:bg-orange-600">
                    Get Free Audit
                </a>
            </div>
        </div>
    </header>

    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(249,115,22,0.25),transparent_35%),radial-gradient(circle_at_bottom_left,rgba(59,130,246,0.16),transparent_35%)]"></div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-6 py-20 lg:grid-cols-2 lg:py-28">
            <div>
                <div class="mb-6 inline-flex rounded-full border border-orange-400/30 bg-orange-500/10 px-4 py-2 text-sm font-semibold text-orange-300">
                    Founders offer for selected UAE garages
                </div>

                <h1 class="max-w-4xl text-4xl font-black leading-tight tracking-tight md:text-6xl">
                    Stop losing garage leads from
                    <span class="text-orange-400">WhatsApp, Meta, calls & website forms.</span>
                </h1>

                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">
                    SayaraForce helps garages capture every enquiry, follow up faster, convert more bookings,
                    and bring old customers back with WhatsApp-first lead recovery.
                </p>

                <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                    <a href="#audit"
                       class="inline-flex items-center justify-center rounded-2xl bg-orange-500 px-6 py-4 text-base font-bold text-white shadow-xl shadow-orange-500/20 hover:bg-orange-600">
                        Request Free 7-Day Lead Recovery Audit
                    </a>

                    <a href="#pricing"
                       class="inline-flex items-center justify-center rounded-2xl border border-white/15 px-6 py-4 text-base font-bold text-white hover:bg-white/10">
                        View Founders Pricing
                    </a>
                </div>

                <div class="mt-8 grid max-w-xl grid-cols-3 gap-4 text-sm text-slate-400">
                    <div>
                        <div class="text-2xl font-black text-white">1</div>
                        Lead inbox
                    </div>
                    <div>
                        <div class="text-2xl font-black text-white">24/7</div>
                        Follow-up tracking
                    </div>
                    <div>
                        <div class="text-2xl font-black text-white">UAE</div>
                        Garage focused
                    </div>
                </div>
            </div>

            <div class="rounded-[2rem] border border-white/10 bg-white/10 p-4 shadow-2xl backdrop-blur">
                <div class="rounded-[1.5rem] bg-slate-900 p-6">
                    <div class="mb-5 flex items-center justify-between">
                        <div>
                            <div class="text-sm text-slate-400">Today’s Lead Recovery</div>
                            <div class="text-2xl font-black">Garage Dashboard</div>
                        </div>
                        <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-bold text-emerald-300">
                            Live
                        </span>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl bg-slate-800 p-5">
                            <div class="text-sm text-slate-400">New Leads</div>
                            <div class="mt-2 text-3xl font-black">18</div>
                            <div class="mt-2 text-xs text-emerald-300">+6 from WhatsApp</div>
                        </div>

                        <div class="rounded-2xl bg-slate-800 p-5">
                            <div class="text-sm text-slate-400">Pending Follow-ups</div>
                            <div class="mt-2 text-3xl font-black">7</div>
                            <div class="mt-2 text-xs text-orange-300">Action required</div>
                        </div>

                        <div class="rounded-2xl bg-slate-800 p-5">
                            <div class="text-sm text-slate-400">Bookings</div>
                            <div class="mt-2 text-3xl font-black">5</div>
                            <div class="mt-2 text-xs text-blue-300">Confirmed today</div>
                        </div>

                        <div class="rounded-2xl bg-slate-800 p-5">
                            <div class="text-sm text-slate-400">Recovered Jobs</div>
                            <div class="mt-2 text-3xl font-black">3</div>
                            <div class="mt-2 text-xs text-emerald-300">From old leads</div>
                        </div>
                    </div>

                    <div class="mt-5 rounded-2xl border border-orange-400/20 bg-orange-500/10 p-5">
                        <div class="text-sm font-bold text-orange-300">Next best action</div>
                        <p class="mt-2 text-sm text-slate-300">
                            4 customers asked for price but were not followed up. Send WhatsApp follow-up now.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Problem --}}
    <section id="problem" class="border-t border-white/10 bg-slate-900/70 py-20">
        <div class="mx-auto max-w-7xl px-6">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-widest text-orange-400">The problem</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight md:text-5xl">
                    Garages do not lose customers because of bad service.
                    They lose them because follow-up is scattered.
                </h2>
            </div>

            <div class="mt-12 grid gap-6 md:grid-cols-3">
                <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                    <div class="text-xl font-bold">WhatsApp chaos</div>
                    <p class="mt-3 text-slate-400">
                        Customer enquiries sit inside personal phones with no proper lead tracking.
                    </p>
                </div>

                <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                    <div class="text-xl font-bold">Missed follow-ups</div>
                    <p class="mt-3 text-slate-400">
                        Staff forget to follow up with customers who asked for prices, appointments, or service reminders.
                    </p>
                </div>

                <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                    <div class="text-xl font-bold">No clear pipeline</div>
                    <p class="mt-3 text-slate-400">
                        Owners cannot see how many leads came in, who followed up, and which jobs were won or lost.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Solution --}}
    <section id="solution" class="py-20">
        <div class="mx-auto max-w-7xl px-6">
            <div class="grid gap-12 lg:grid-cols-2">
                <div>
                    <p class="text-sm font-bold uppercase tracking-widest text-orange-400">The solution</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight md:text-5xl">
                        SayaraForce is not just a CRM. It is a lead recovery and follow-up system built for garages.
                    </h2>
                    <p class="mt-6 text-lg leading-8 text-slate-300">
                        Capture leads from WhatsApp, website forms, Meta campaigns and manual enquiries.
                        Assign follow-ups, confirm bookings, track jobs, and bring customers back with campaigns.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @php
                        $features = [
                            ['title' => 'Lead Flow Management', 'desc' => 'Capture, assign and track every garage enquiry.'],
                            ['title' => 'WhatsApp Follow-ups', 'desc' => 'Keep conversations and follow-ups organized.'],
                            ['title' => 'Booking Pipeline', 'desc' => 'Move enquiries into confirmed service bookings.'],
                            ['title' => 'Job Tracking', 'desc' => 'Track work progress from booking to job completion.'],
                            ['title' => 'Retention Campaigns', 'desc' => 'Bring old customers back for service reminders.'],
                            ['title' => 'Owner Dashboard', 'desc' => 'See leads, jobs, revenue and staff activity.'],
                        ];
                    @endphp

                    @foreach($features as $feature)
                        <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                            <div class="font-bold text-white">{{ $feature['title'] }}</div>
                            <p class="mt-2 text-sm leading-6 text-slate-400">{{ $feature['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ROI --}}
    <section class="border-y border-white/10 bg-orange-500 py-14 text-slate-950">
        <div class="mx-auto max-w-7xl px-6">
            <div class="grid items-center gap-8 md:grid-cols-3">
                <div class="md:col-span-2">
                    <h2 class="text-3xl font-black tracking-tight md:text-4xl">
                        If your garage misses even 2–3 jobs a month, SayaraForce can pay for itself.
                    </h2>
                    <p class="mt-4 max-w-3xl text-lg font-medium text-slate-900/80">
                        The launch offer is designed to help early garages recover missed leads before spending more on ads.
                    </p>
                </div>

                <div class="rounded-3xl bg-white p-6 shadow-xl">
                    <div class="text-sm font-bold uppercase tracking-widest text-slate-500">Launch focus</div>
                    <div class="mt-2 text-2xl font-black">Lead Recovery First</div>
                    <p class="mt-2 text-sm text-slate-600">
                        We help you identify missed enquiries and improve follow-up before scaling campaigns.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="py-20">
        <div class="mx-auto max-w-7xl px-6">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-bold uppercase tracking-widest text-orange-400">Founders pricing</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight md:text-5xl">
                    Launch pricing for selected early garages.
                </h2>
                <p class="mt-5 text-lg text-slate-300">
                    Founders pricing is available for the first 10 selected garages only.
                    Includes guided setup, onboarding support and early access to new features.
                </p>
            </div>

            <div class="mt-12 grid gap-6 lg:grid-cols-3">
                {{-- Starter --}}
                <div class="rounded-[2rem] border border-white/10 bg-white/5 p-7">
                    <div class="text-xl font-black">Starter</div>
                    <p class="mt-2 min-h-12 text-sm text-slate-400">
                        For small garages starting with lead tracking and WhatsApp follow-up.
                    </p>

                    <div class="mt-8">
                        <div class="text-sm font-semibold text-slate-500 line-through">AED 1,999/month</div>
                        <div class="mt-1 text-4xl font-black text-white">AED 999</div>
                        <div class="text-sm text-slate-400">per month</div>
                    </div>

                    <ul class="mt-8 space-y-3 text-sm text-slate-300">
                        <li>✓ Lead capture</li>
                        <li>✓ Client management</li>
                        <li>✓ WhatsApp follow-up tracking</li>
                        <li>✓ Basic booking pipeline</li>
                        <li>✓ Guided setup</li>
                    </ul>

                    <a href="#audit"
                       class="mt-8 inline-flex w-full justify-center rounded-2xl border border-white/15 px-5 py-3 font-bold hover:bg-white/10">
                        Claim Founders Offer
                    </a>
                </div>

                {{-- Growth --}}
                <div class="relative rounded-[2rem] border border-orange-400 bg-white p-7 text-slate-950 shadow-2xl shadow-orange-500/20">
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 rounded-full bg-orange-500 px-4 py-2 text-xs font-black uppercase tracking-widest text-white">
                        Recommended
                    </div>

                    <div class="text-xl font-black">Growth</div>
                    <p class="mt-2 min-h-12 text-sm text-slate-600">
                        For garages handling WhatsApp, Meta, website leads and follow-up campaigns.
                    </p>

                    <div class="mt-8">
                        <div class="text-sm font-semibold text-slate-400 line-through">AED 2,999/month</div>
                        <div class="mt-1 text-4xl font-black">AED 1,499</div>
                        <div class="text-sm text-slate-500">per month</div>
                    </div>

                    <ul class="mt-8 space-y-3 text-sm text-slate-700">
                        <li>✓ Everything in Starter</li>
                        <li>✓ Meta / website lead handling</li>
                        <li>✓ Opportunity pipeline</li>
                        <li>✓ Retention segments</li>
                        <li>✓ Campaign tracking</li>
                        <li>✓ Manager dashboard</li>
                    </ul>

                    <a href="#audit"
                       class="mt-8 inline-flex w-full justify-center rounded-2xl bg-orange-500 px-5 py-3 font-bold text-white hover:bg-orange-600">
                        Claim Founders Offer
                    </a>
                </div>

                {{-- Pro --}}
                <div class="rounded-[2rem] border border-white/10 bg-white/5 p-7">
                    <div class="text-xl font-black">Pro</div>
                    <p class="mt-2 min-h-12 text-sm text-slate-400">
                        For garages that want full lead recovery, reports, team workflows and campaigns.
                    </p>

                    <div class="mt-8">
                        <div class="text-sm font-semibold text-slate-500 line-through">AED 3,999/month</div>
                        <div class="mt-1 text-4xl font-black text-white">AED 1,999</div>
                        <div class="text-sm text-slate-400">per month</div>
                    </div>

                    <ul class="mt-8 space-y-3 text-sm text-slate-300">
                        <li>✓ Everything in Growth</li>
                        <li>✓ Advanced dashboard</li>
                        <li>✓ Jobs and invoice tracking</li>
                        <li>✓ Team roles and permissions</li>
                        <li>✓ Advanced campaign reports</li>
                        <li>✓ Priority onboarding</li>
                    </ul>

                    <a href="#audit"
                       class="mt-8 inline-flex w-full justify-center rounded-2xl border border-white/15 px-5 py-3 font-bold hover:bg-white/10">
                        Claim Founders Offer
                    </a>
                </div>
            </div>

            <p class="mt-8 text-center text-sm text-slate-400">
                Not sure which plan fits? Start with a free 7-day lead recovery audit.
            </p>
        </div>
    </section>

    {{-- Audit CTA --}}
    <section id="audit" class="border-t border-white/10 bg-slate-900 py-20">
        <div class="mx-auto max-w-5xl px-6">
            <div class="rounded-[2rem] border border-white/10 bg-white/5 p-8 md:p-12">
                <div class="grid gap-10 lg:grid-cols-2">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-widest text-orange-400">Free audit</p>
                        <h2 class="mt-3 text-3xl font-black tracking-tight md:text-5xl">
                            Get a free 7-day lead recovery audit.
                        </h2>
                        <p class="mt-5 text-lg leading-8 text-slate-300">
                            We will review how your garage handles WhatsApp, website, Meta and manual enquiries,
                            then show where leads are being missed.
                        </p>

                        <div class="mt-8 space-y-3 text-sm text-slate-300">
                            <div>✓ No long contract required</div>
                            <div>✓ Setup guidance included</div>
                            <div>✓ Built specifically for garage lead follow-up</div>
                        </div>
                    </div>

                    <form method="POST" action="#" class="space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-300">Garage Name</label>
                            <input type="text"
                                   placeholder="Example: City Auto Garage"
                                   class="w-full rounded-2xl border border-white/10 bg-slate-950 px-4 py-3 text-white outline-none ring-orange-500 placeholder:text-slate-600 focus:ring-2">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-300">Your Name</label>
                            <input type="text"
                                   placeholder="Owner / Manager name"
                                   class="w-full rounded-2xl border border-white/10 bg-slate-950 px-4 py-3 text-white outline-none ring-orange-500 placeholder:text-slate-600 focus:ring-2">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-300">WhatsApp Number</label>
                            <input type="text"
                                   placeholder="+971 5X XXX XXXX"
                                   class="w-full rounded-2xl border border-white/10 bg-slate-950 px-4 py-3 text-white outline-none ring-orange-500 placeholder:text-slate-600 focus:ring-2">
                        </div>

                        <button type="button"
                                class="w-full rounded-2xl bg-orange-500 px-6 py-4 text-base font-black text-white shadow-xl shadow-orange-500/20 hover:bg-orange-600">
                            Request Free Audit
                        </button>

                        <p class="text-xs leading-5 text-slate-500">
                            Form connection can be enabled after UAT. For now, use this section as the conversion block.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-white/10 py-8">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 text-sm text-slate-500 md:flex-row">
            <div>© {{ date('Y') }} SayaraForce. Built for UAE garages.</div>
            <div class="flex gap-5">
                <a href="{{ route('login') }}" class="hover:text-white">Login</a>
                <a href="#pricing" class="hover:text-white">Pricing</a>
                <a href="#audit" class="hover:text-white">Audit</a>
            </div>
        </div>
    </footer>

</body>
</html>