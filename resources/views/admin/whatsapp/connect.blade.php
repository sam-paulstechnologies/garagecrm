@extends('layouts.app')

@section('title', 'SF-WA Connect')

@section('content')

@php
    use Illuminate\Support\Facades\Route;

    $isConnected = (bool) ($status['is_connected'] ?? false);
    $isActive = (bool) ($status['is_active'] ?? false);

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
                Each garage connects its own WhatsApp Business number. Messages go from that garage’s number,
                Meta charges SayaraForce, and SayaraForce can bill the garage based on usage.
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
                                Start Meta Embedded Signup and save the connected number to this company.
                            </p>
                        </div>

                        @if($isConnected)
                            <span class="inline-flex rounded-full border border-green-400/20 bg-green-500/10 px-3 py-1 text-xs font-extrabold text-green-300">
                                Connected
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
                            Your garage is open for enquiries 24/7.
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

                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <button
                                type="button"
                                id="sfwaConnectButton"
                                class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600 disabled:cursor-not-allowed disabled:opacity-50"
                                @if(blank($metaAppIdValue) || blank($embeddedSignupConfigId)) disabled @endif
                            >
                                Connect WhatsApp
                            </button>

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
                        SF-WA Connect v1 uses existing company-level WhatsApp fields.
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
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-orange-500/10 text-sm font-black text-orange-300">2</span>
                            <div>
                                <h3 class="text-sm font-extrabold text-white">SayaraForce saves the connected number</h3>
                                <p class="mt-1 text-sm font-medium leading-6 text-slate-500">
                                    WABA ID, phone number ID, encrypted access token, verify token, and active flag are saved against the company.
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-4 rounded-2xl border border-white/10 bg-slate-950/50 p-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-green-500/10 text-sm font-black text-green-300">3</span>
                            <div>
                                <h3 class="text-sm font-extrabold text-white">Messages route by company</h3>
                                <p class="mt-1 text-sm font-medium leading-6 text-slate-500">
                                    Outbound messages use that company’s Meta credentials. Inbound webhooks map back using the phone number ID.
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-4 rounded-2xl border border-white/10 bg-slate-950/50 p-4">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-sm font-black text-purple-300">4</span>
                            <div>
                                <h3 class="text-sm font-extrabold text-white">Usage can be billed</h3>
                                <p class="mt-1 text-sm font-medium leading-6 text-slate-500">
                                    Meta charges SayaraForce. SayaraForce can track usage and charge garages from the platform.
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
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Token Expiry</div>
                            <div class="mt-2 break-all text-sm font-bold text-slate-300">
                                {{ $status['token_expires_at'] ?? 'Not available' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Webhook Note --}}
            <div class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-6">
                <h3 class="text-lg font-extrabold text-blue-100">
                    Webhook Routing
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
                    Webhook URL remains the shared SayaraForce Meta webhook. The system resolves the correct garage using Meta
                    <span class="font-black text-blue-100">phone_number_id</span>.
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

    let sfwaSignupPayload = {
        business_id: null,
        waba_id: null,
        phone_number_id: null,
        display_phone_number: null,
    };

    const sfwaConnectButton = document.getElementById('sfwaConnectButton');
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

            sfwaLog('Received Embedded Signup message from Meta.', sfwaSignupPayload);
        }
    });

    async function sfwaPostCallback(payload) {
        sfwaLog('Saving WhatsApp connection...');

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

    function sfwaStartEmbeddedSignup() {
        if (! window.FB) {
            sfwaShowError('Facebook SDK is not loaded yet. Wait a few seconds and try again.');
            return;
        }

        if (! sfwaConfigId) {
            sfwaShowError('Missing META_WHATSAPP_EMBEDDED_SIGNUP_CONFIG_ID.');
            return;
        }

        sfwaLog('Opening Meta Embedded Signup...');

        FB.login(function (response) {
            sfwaLog('Meta login response received.', response);

            if (! response.authResponse || ! response.authResponse.code) {
                sfwaShowError('Meta Embedded Signup did not return an authorization code.');
                return;
            }

            sfwaPostCallback({
                code: response.authResponse.code,
                state: sfwaState,
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

    if (sfwaConnectButton) {
        sfwaConnectButton.addEventListener('click', sfwaStartEmbeddedSignup);
    }

    if (sfwaRefreshStatusButton) {
        sfwaRefreshStatusButton.addEventListener('click', sfwaRefreshStatus);
    }
</script>

@endsection