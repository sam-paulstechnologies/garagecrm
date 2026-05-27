@extends('layouts.app')

@section('title', 'SF-WA Connect')

@section('content')

@php
    use Illuminate\Support\Facades\Route;

    $isConnected = (bool) ($status['is_connected'] ?? false);
    $isActive = (bool) ($status['is_active'] ?? false);

    $currentConnectionMode = $status['connection_mode'] ?? 'manual';
    $selectedConnectionMode = $connectionMode ?? request('mode', 'coexistence');

    if (! in_array($selectedConnectionMode, ['coexistence', 'cloud_api', 'manual'], true)) {
        $selectedConnectionMode = 'coexistence';
    }

    $connectionModeLabel = match ($currentConnectionMode) {
        'coexistence' => 'Coexistence',
        'cloud_api' => 'Cloud API',
        default => 'Manual / Not connected',
    };

    $callbackUrl = Route::has('admin.whatsapp.connect.callback')
        ? route('admin.whatsapp.connect.callback')
        : url('/admin/whatsapp/embedded-signup/callback');

    $statusUrl = Route::has('admin.whatsapp.connect.status')
        ? route('admin.whatsapp.connect.status')
        : url('/admin/whatsapp/connect/status');

    $disconnectUrl = Route::has('admin.whatsapp.connect.disconnect')
        ? route('admin.whatsapp.connect.disconnect')
        : url('/admin/whatsapp/disconnect');

    $settingsUrl = Route::has('admin.whatsapp.settings.edit')
        ? route('admin.whatsapp.settings.edit')
        : url('/admin/whatsapp/settings');

    $connectBaseUrl = Route::has('admin.whatsapp.connect')
        ? route('admin.whatsapp.connect')
        : url('/admin/whatsapp/connect');

    $embeddedSignupConfigId = config('services.meta.whatsapp_embedded_signup_config_id')
        ?: config('services.whatsapp.embedded_signup_config_id')
        ?: env('META_WHATSAPP_EMBEDDED_SIGNUP_CONFIG_ID');

    $metaAppIdValue = $metaAppId ?? config('services.meta.app_id') ?? env('META_APP_ID');
    $graphVersionValue = $graphVersion ?? 'v21.0';

    $panelClass = 'rounded-3xl border border-white/10 bg-slate-900/80 shadow-xl shadow-black/20 overflow-hidden';
    $panelHeaderClass = 'border-b border-white/10 px-6 py-4 bg-slate-950/35';
    $panelBodyClass = 'px-6 py-6';
@endphp

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Alerts --}}
    @if(session('success'))
        <div class="mb-5 rounded-2xl border border-green-400/20 bg-green-500/10 px-4 py-3 text-sm font-bold text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-5 rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-sm font-bold text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <div id="sfwaBrowserAlert" class="mb-5 hidden rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-sm font-bold text-red-300"></div>

    @if(blank($metaAppIdValue) || blank($embeddedSignupConfigId))
        <div class="mb-5 rounded-2xl border border-yellow-400/20 bg-yellow-500/10 px-4 py-3 text-sm font-bold text-yellow-200">
            Meta App ID or Embedded Signup Config ID is missing.
            Please set <span class="text-yellow-100">META_APP_ID</span> and
            <span class="text-yellow-100">META_WHATSAPP_EMBEDDED_SIGNUP_CONFIG_ID</span>
            before testing SF-WA Connect.
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-orange-300">
                SF-WA Connect · Garage WhatsApp Onboarding
            </div>

            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">
                Connect this garage’s WhatsApp number
            </h1>

            <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-slate-400">
                Each garage can connect its own WhatsApp Business number. With coexistence, the garage can continue using
                the WhatsApp Business app while SayaraForce captures leads, logs conversations, sends approved templates,
                and routes replies into the CRM.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ $settingsUrl }}"
               class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                Back to WhatsApp Settings
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- LEFT --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- Main Connection Card --}}
            <div class="{{ $panelClass }}">
                <div class="{{ $panelHeaderClass }}">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-extrabold text-white">
                                Connection
                            </h2>

                            <p class="mt-1 text-sm font-medium text-slate-500">
                                Choose how this garage should connect WhatsApp to SayaraForce.
                            </p>
                        </div>

                        @if($isConnected)
                            <span class="inline-flex rounded-full border border-green-400/20 bg-green-500/10 px-3 py-1 text-xs font-extrabold text-green-300">
                                Connected · {{ $connectionModeLabel }}
                            </span>
                        @else
                            <span class="inline-flex rounded-full border border-yellow-400/20 bg-yellow-500/10 px-3 py-1 text-xs font-extrabold text-yellow-300">
                                Not Connected
                            </span>
                        @endif
                    </div>
                </div>

                <div class="{{ $panelBodyClass }}">
                    <div class="rounded-3xl border border-orange-400/20 bg-gradient-to-br from-slate-950 via-slate-950 to-orange-950/30 p-6 shadow-xl shadow-black/20">
                        <div class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-orange-300">
                            24/7 Lead Desk
                        </div>

                        <h2 class="mt-4 text-2xl font-black tracking-tight text-white md:text-3xl">
                            Keep WhatsApp simple. Let SayaraForce do the follow-up work.
                        </h2>

                        <p class="mt-3 max-w-2xl text-sm font-semibold leading-7 text-slate-300">
                            Connect WhatsApp so SayaraForce can capture enquiries, send approved templates,
                            receive inbound replies, and route conversations to the correct garage inbox.
                        </p>

                        <div class="mt-6 grid grid-cols-1 gap-3 md:grid-cols-3">
                            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                                <div class="text-lg font-black text-green-300">✓</div>
                                <div class="mt-2 text-sm font-extrabold text-white">Garage Number</div>
                                <p class="mt-1 text-xs font-medium leading-5 text-slate-500">
                                    Messages are sent from the garage’s own WhatsApp number.
                                </p>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                                <div class="text-lg font-black text-blue-300">✓</div>
                                <div class="mt-2 text-sm font-extrabold text-white">Auto Routing</div>
                                <p class="mt-1 text-xs font-medium leading-5 text-slate-500">
                                    Inbound messages map using Meta phone number ID.
                                </p>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                                <div class="text-lg font-black text-orange-300">✓</div>
                                <div class="mt-2 text-sm font-extrabold text-white">Usage Billing</div>
                                <p class="mt-1 text-xs font-medium leading-5 text-slate-500">
                                    Meta charges SayaraForce. SayaraForce bills the garage.
                                </p>
                            </div>
                        </div>

                        {{-- Connection Mode Cards --}}
                        <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2">

                            {{-- Coexistence --}}
                            <div class="rounded-3xl border {{ $selectedConnectionMode === 'coexistence' ? 'border-green-400/40 bg-green-500/10' : 'border-white/10 bg-slate-950/60' }} p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="inline-flex rounded-full border border-green-400/20 bg-green-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-green-300">
                                            Recommended
                                        </div>

                                        <h3 class="mt-4 text-lg font-black text-white">
                                            Connect existing WhatsApp Business App
                                        </h3>

                                        <p class="mt-2 text-sm font-medium leading-6 text-slate-400">
                                            The garage keeps using the WhatsApp Business mobile app. SayaraForce connects in parallel
                                            to capture leads, log conversations, send templates, and manage follow-ups.
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                                    <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                        Best for
                                    </div>
                                    <p class="mt-2 text-sm font-bold leading-6 text-slate-300">
                                        Small garages who already use WhatsApp Business daily and do not want to lose app access.
                                    </p>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="sfwaConnectModeButton inline-flex items-center justify-center rounded-xl bg-green-500 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-green-500/20 transition hover:bg-green-600 disabled:cursor-not-allowed disabled:opacity-50"
                                        data-mode="coexistence"
                                        @if(blank($metaAppIdValue) || blank($embeddedSignupConfigId)) disabled @endif
                                    >
                                        Connect with Coexistence
                                    </button>

                                    @if($selectedConnectionMode !== 'coexistence')
                                        <a href="{{ $connectBaseUrl }}?mode=coexistence"
                                           class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-green-400/30 hover:text-white">
                                            Select
                                        </a>
                                    @endif
                                </div>
                            </div>

                            {{-- Cloud API --}}
                            <div class="rounded-3xl border {{ $selectedConnectionMode === 'cloud_api' ? 'border-blue-400/40 bg-blue-500/10' : 'border-white/10 bg-slate-950/60' }} p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="inline-flex rounded-full border border-blue-400/20 bg-blue-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-blue-300">
                                            Advanced
                                        </div>

                                        <h3 class="mt-4 text-lg font-black text-white">
                                            Use WhatsApp Cloud API only
                                        </h3>

                                        <p class="mt-2 text-sm font-medium leading-6 text-slate-400">
                                            Use SayaraForce as the main WhatsApp automation and inbox system. This is better when
                                            the garage wants the CRM to control most WhatsApp communication.
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                                    <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                        Best for
                                    </div>
                                    <p class="mt-2 text-sm font-bold leading-6 text-slate-300">
                                        Larger garages, teams, or branches that want a CRM-first WhatsApp operating model.
                                    </p>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="sfwaConnectModeButton inline-flex items-center justify-center rounded-xl bg-blue-500 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-blue-500/20 transition hover:bg-blue-600 disabled:cursor-not-allowed disabled:opacity-50"
                                        data-mode="cloud_api"
                                        @if(blank($metaAppIdValue) || blank($embeddedSignupConfigId)) disabled @endif
                                    >
                                        Connect Cloud API
                                    </button>

                                    @if($selectedConnectionMode !== 'cloud_api')
                                        <a href="{{ $connectBaseUrl }}?mode=cloud_api"
                                           class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-blue-400/30 hover:text-white">
                                            Select
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <button
                                type="button"
                                id="sfwaRefreshStatusButton"
                                class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-5 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white"
                            >
                                Refresh Status
                            </button>

                            @if($isConnected)
                                <form method="POST"
                                      action="{{ $disconnectUrl }}"
                                      onsubmit="return confirm('Disconnect WhatsApp for this garage? Messages will stop using this connected number.');">
                                    @csrf

                                    <button type="submit"
                                            class="inline-flex items-center justify-center rounded-xl bg-red-600 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-red-500/20 transition hover:bg-red-700">
                                        Disconnect WhatsApp
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="mt-5 rounded-2xl border border-white/10 bg-slate-950 p-4">
                        <div class="mb-2 text-xs font-extrabold uppercase tracking-wide text-slate-500">
                            Connect Log
                        </div>

                        <pre id="sfwaLog" class="min-h-[110px] whitespace-pre-wrap break-words text-xs font-bold leading-6 text-slate-300">Ready.</pre>
                    </div>
                </div>
            </div>

            {{-- Steps --}}
            <div class="{{ $panelClass }}">
                <div class="{{ $panelHeaderClass }}">
                    <h2 class="text-lg font-extrabold text-white">
                        What happens after connection?
                    </h2>

                    <p class="mt-1 text-sm font-medium text-slate-500">
                        SF-WA Connect saves the garage’s Meta WhatsApp details and routes messages by company.
                    </p>
                </div>

                <div class="{{ $panelBodyClass }}">
                    <div class="space-y-4">
                        <div class="flex gap-4 rounded-2xl border border-white/10 bg-slate-950/50 p-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-blue-500/10 text-sm font-black text-blue-300">1</span>
                            <div>
                                <h3 class="text-sm font-extrabold text-white">Garage completes Meta Embedded Signup</h3>
                                <p class="mt-1 text-sm font-medium leading-6 text-slate-500">
                                    The owner/admin logs into Facebook, selects or creates their business,
                                    connects a WhatsApp Business Account, and verifies a number.
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-4 rounded-2xl border border-white/10 bg-slate-950/50 p-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-green-500/10 text-sm font-black text-green-300">2</span>
                            <div>
                                <h3 class="text-sm font-extrabold text-white">Coexistence keeps the app usable</h3>
                                <p class="mt-1 text-sm font-medium leading-6 text-slate-500">
                                    If coexistence is selected, the garage can keep replying from WhatsApp Business app.
                                    SayaraForce logs those app replies as manual outbound messages.
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-4 rounded-2xl border border-white/10 bg-slate-950/50 p-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-orange-500/10 text-sm font-black text-orange-300">3</span>
                            <div>
                                <h3 class="text-sm font-extrabold text-white">SayaraForce saves the connected number</h3>
                                <p class="mt-1 text-sm font-medium leading-6 text-slate-500">
                                    WABA ID, phone number ID, encrypted access token, verify token, mode, and active flag are saved against the company.
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-4 rounded-2xl border border-white/10 bg-slate-950/50 p-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-sm font-black text-purple-300">4</span>
                            <div>
                                <h3 class="text-sm font-extrabold text-white">Messages route by company</h3>
                                <p class="mt-1 text-sm font-medium leading-6 text-slate-500">
                                    Outbound messages use that company’s Meta credentials. Inbound webhooks map back using the phone number ID.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT --}}
        <aside class="space-y-6">

            {{-- Current Status --}}
            <div class="{{ $panelClass }}">
                <div class="{{ $panelHeaderClass }}">
                    <h3 class="text-lg font-extrabold text-white">
                        Current Status
                    </h3>

                    <p class="mt-1 text-sm font-medium text-slate-500">
                        Saved against this company record.
                    </p>
                </div>

                <div class="{{ $panelBodyClass }}">
                    <div class="space-y-3">
                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Company</div>
                            <div class="mt-2 break-words text-sm font-black text-white">
                                {{ $company->name ?? ('Company #'.$company->id) }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Active</div>
                            <div class="mt-2 text-sm font-black {{ $isActive ? 'text-green-300' : 'text-yellow-300' }}">
                                {{ $isActive ? 'Yes' : 'No' }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Connection Mode</div>
                            <div class="mt-2 text-sm font-black {{ $currentConnectionMode === 'coexistence' ? 'text-green-300' : ($currentConnectionMode === 'cloud_api' ? 'text-blue-300' : 'text-yellow-300') }}">
                                {{ $connectionModeLabel }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Coexistence</div>
                            <div class="mt-2 text-sm font-black {{ ($status['coexistence_enabled'] ?? false) ? 'text-green-300' : 'text-slate-300' }}">
                                {{ ($status['coexistence_enabled'] ?? false) ? 'Enabled' : 'Not enabled' }}
                            </div>

                            <div class="mt-1 text-xs font-bold text-slate-500">
                                Status: {{ $status['coexistence_status'] ?? 'N/A' }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Business ID</div>
                            <div class="mt-2 break-all text-sm font-bold text-slate-300">
                                {{ $status['business_id'] ?? 'Not available' }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Display Phone</div>
                            <div class="mt-2 break-all text-sm font-bold text-slate-300">
                                {{ $status['display_phone_number'] ?? 'Not available' }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">WABA ID</div>
                            <div class="mt-2 break-all text-sm font-bold text-slate-300">
                                {{ $status['waba_id'] ?? 'Not connected' }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Phone Number ID</div>
                            <div class="mt-2 break-all text-sm font-bold text-slate-300">
                                {{ $status['phone_number_id'] ?? 'Not connected' }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Verify Token</div>
                            <div class="mt-2 break-all text-sm font-bold text-slate-300">
                                {{ $status['verify_token'] ?? 'Not generated yet' }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Connected At</div>
                            <div class="mt-2 break-all text-sm font-bold text-slate-300">
                                {{ $status['connected_at'] ?? 'Not available' }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Last App Reply Synced</div>
                            <div class="mt-2 break-all text-sm font-bold text-slate-300">
                                {{ $status['last_echo_at'] ?? 'Not synced yet' }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Token Expiry</div>
                            <div class="mt-2 break-all text-sm font-bold text-slate-300">
                                {{ $status['token_expires_at'] ?? 'Not available' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Coexistence Note --}}
            <div class="rounded-3xl border border-green-400/20 bg-green-500/10 p-6">
                <h3 class="text-lg font-extrabold text-green-100">
                    Coexistence Advantage
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-green-100/80">
                    Recommended for first 10 garages. They can keep using WhatsApp Business app while SayaraForce captures,
                    tracks, and automates around the same number.
                </p>
            </div>

            {{-- Webhook Note --}}
            <div class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-6">
                <h3 class="text-lg font-extrabold text-blue-100">
                    Webhook Routing
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
                    Webhook URL remains the shared SayaraForce Meta webhook. The system resolves the correct garage using Meta
                    <span class="font-black text-blue-100">phone_number_id</span>. App replies in coexistence are stored as
                    <span class="font-black text-blue-100">whatsapp_business_app</span> messages.
                </p>
            </div>

            {{-- Config Note --}}
            <div class="rounded-3xl border border-yellow-400/20 bg-yellow-500/10 p-6">
                <h3 class="text-lg font-extrabold text-yellow-100">
                    Required Meta Config
                </h3>

                <div class="mt-3 space-y-2 text-xs font-bold leading-6 text-yellow-100/80">
                    <div>META_APP_ID</div>
                    <div>META_APP_SECRET</div>
                    <div>META_WHATSAPP_EMBEDDED_SIGNUP_CONFIG_ID</div>
                    <div>META_APP_SECRET for webhook signature</div>
                </div>
            </div>

        </aside>
    </div>
</div>

<script>
    window.fbAsyncInit = function () {
        FB.init({
            appId: @json($metaAppIdValue),
            autoLogAppEvents: true,
            xfbml: true,
            version: @json($graphVersionValue),
        });

        sfwaLog('Facebook SDK loaded.');
    };

    (function (d, s, id) {
        if (d.getElementById(id)) {
            return;
        }

        const js = d.createElement(s);
        js.id = id;
        js.src = 'https://connect.facebook.net/en_US/sdk.js';

        const fjs = d.getElementsByTagName(s)[0];
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));

    const sfwaState = @json($state);
    const sfwaCallbackUrl = @json($callbackUrl);
    const sfwaStatusUrl = @json($statusUrl);
    const sfwaConfigId = @json($embeddedSignupConfigId);
    const sfwaCsrf = @json(csrf_token());
    let sfwaSelectedConnectionMode = @json($selectedConnectionMode);

    let sfwaSignupPayload = {
        business_id: null,
        waba_id: null,
        phone_number_id: null,
        display_phone_number: null,
    };

    const sfwaConnectModeButtons = document.querySelectorAll('.sfwaConnectModeButton');
    const sfwaRefreshStatusButton = document.getElementById('sfwaRefreshStatusButton');
    const sfwaLogBox = document.getElementById('sfwaLog');
    const sfwaBrowserAlert = document.getElementById('sfwaBrowserAlert');

    function sfwaLog(message, data = null) {
        if (! sfwaLogBox) {
            return;
        }

        const timestamp = new Date().toLocaleTimeString();
        let line = `[${timestamp}] ${message}`;

        if (data) {
            line += "\n" + JSON.stringify(data, null, 2);
        }

        sfwaLogBox.textContent = `${line}\n\n${sfwaLogBox.textContent || ''}`;
    }

    function sfwaShowError(message) {
        if (sfwaBrowserAlert) {
            sfwaBrowserAlert.classList.remove('hidden');
            sfwaBrowserAlert.textContent = message;
        }

        sfwaLog(message);
    }

    function sfwaModeLabel(mode) {
        if (mode === 'coexistence') {
            return 'Coexistence';
        }

        if (mode === 'cloud_api') {
            return 'Cloud API';
        }

        return 'WhatsApp';
    }

    window.addEventListener('message', function (event) {
        if (! event.origin.includes('facebook.com')) {
            return;
        }

        let data = event.data;

        try {
            if (typeof data === 'string') {
                data = JSON.parse(data);
            }
        } catch (error) {
            return;
        }

        const eventName = data.event || data.type || null;
        const eventData = data.data || data.payload || data;

        if (! eventName) {
            return;
        }

        if (
            eventName === 'WA_EMBEDDED_SIGNUP' ||
            eventName === 'whatsapp_embedded_signup' ||
            String(eventName).includes('WHATSAPP')
        ) {
            sfwaSignupPayload.business_id =
                eventData.business_id ||
                eventData.businessID ||
                eventData.business?.id ||
                sfwaSignupPayload.business_id;

            sfwaSignupPayload.waba_id =
                eventData.waba_id ||
                eventData.wabaID ||
                eventData.whatsapp_business_account_id ||
                eventData.whatsappBusinessAccountId ||
                eventData.waba?.id ||
                sfwaSignupPayload.waba_id;

            sfwaSignupPayload.phone_number_id =
                eventData.phone_number_id ||
                eventData.phoneNumberId ||
                eventData.phone_number?.id ||
                eventData.phone?.id ||
                sfwaSignupPayload.phone_number_id;

            sfwaSignupPayload.display_phone_number =
                eventData.display_phone_number ||
                eventData.displayPhoneNumber ||
                eventData.phone_number?.display_phone_number ||
                sfwaSignupPayload.display_phone_number;

            sfwaLog('Received Embedded Signup message from Meta.', {
                connection_mode: sfwaSelectedConnectionMode,
                ...sfwaSignupPayload,
            });
        }
    });

    async function sfwaPostCallback(payload) {
        sfwaLog('Saving WhatsApp connection...', {
            connection_mode: payload.connection_mode,
        });

        const response = await fetch(sfwaCallbackUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': sfwaCsrf,
            },
            body: JSON.stringify(payload),
        });

        const json = await response.json();

        if (! response.ok || ! json.ok) {
            throw new Error(json.message || 'Unable to save WhatsApp connection.');
        }

        sfwaLog('WhatsApp connected successfully.', json.status || json);

        window.location.reload();
    }

    function sfwaStartEmbeddedSignup(connectionMode = 'coexistence') {
        sfwaSelectedConnectionMode = connectionMode;

        if (! window.FB) {
            sfwaShowError('Facebook SDK is not loaded yet. Wait a few seconds and try again.');
            return;
        }

        if (! sfwaConfigId) {
            sfwaShowError('Missing META_WHATSAPP_EMBEDDED_SIGNUP_CONFIG_ID.');
            return;
        }

        sfwaLog(`Opening Meta Embedded Signup for ${sfwaModeLabel(connectionMode)} mode...`);

        FB.login(function (response) {
            sfwaLog('Meta login response received.', response);

            if (! response.authResponse || ! response.authResponse.code) {
                sfwaShowError('Meta Embedded Signup did not return an authorization code.');
                return;
            }

            sfwaPostCallback({
                code: response.authResponse.code,
                state: sfwaState,
                connection_mode: connectionMode,
                business_id: sfwaSignupPayload.business_id,
                waba_id: sfwaSignupPayload.waba_id,
                phone_number_id: sfwaSignupPayload.phone_number_id,
                display_phone_number: sfwaSignupPayload.display_phone_number,
            }).catch(function (error) {
                sfwaShowError(error.message);
            });
        }, {
            config_id: sfwaConfigId,
            response_type: 'code',
            override_default_response_type: true,
            extras: {
                setup: {},
                featureType: 'whatsapp_embedded_signup',
                sessionInfoVersion: '3',
            },
        });
    }

    async function sfwaRefreshStatus() {
        sfwaLog('Refreshing connection status...');

        try {
            const response = await fetch(sfwaStatusUrl, {
                headers: {
                    'Accept': 'application/json',
                },
            });

            const json = await response.json();

            sfwaLog('Current connection status.', json.status || json);
        } catch (error) {
            sfwaShowError(error.message);
        }
    }

    if (sfwaConnectModeButtons && sfwaConnectModeButtons.length) {
        sfwaConnectModeButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const mode = button.dataset.mode || 'coexistence';
                sfwaStartEmbeddedSignup(mode);
            });
        });
    }

    if (sfwaRefreshStatusButton) {
        sfwaRefreshStatusButton.addEventListener('click', sfwaRefreshStatus);
    }
</script>

@endsection