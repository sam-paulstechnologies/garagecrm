@extends('layouts.app')

@section('title', 'Connect WhatsApp')

@section('content')
@php
    $status = $connection?->status?->value ?? 'not_connected';
    $connected = $status === 'connected';
    $needsAction = in_array($status, ['requires_action', 'failed'], true);
    $maskedPhone = $phone?->display_phone_number
        ? preg_replace('/.(?=.{4})/', '•', $phone->display_phone_number)
        : null;
@endphp

<div class="sf-page" data-self-service-whatsapp>
    @if(session('success'))
        <div class="sf-alert-success mb-5">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="sf-alert-warning mb-5">{{ session('warning') }}</div>
    @endif
    @if(session('error'))
        <div class="sf-alert-danger mb-5">{{ session('error') }}</div>
    @endif
    <div id="messagingWhatsAppAlert" class="sf-alert-danger mb-5 hidden" role="alert"></div>
    <div id="messagingWhatsAppStatus" class="sf-alert-info mb-5 hidden" aria-live="polite"></div>

    <header class="mb-7 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="sf-badge-orange uppercase tracking-[0.12em]">Secure Meta connection</div>
            <h1 class="sf-page-title mt-4">Connect your garage WhatsApp</h1>
            <p class="sf-page-subtitle max-w-3xl">
                Use your existing WhatsApp Business number or connect a new number. The setup is completed securely through Meta—there are no technical credentials to copy.
            </p>
        </div>
        @if($connected && Route::has('admin.inbox.index'))
            <a href="{{ route('admin.inbox.index') }}" class="sf-btn-primary min-h-11">Open Inbox</a>
        @endif
    </header>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,.75fr)]">
        <main class="space-y-6">
            @unless($connected)
                <section class="sf-card overflow-hidden">
                    <div class="sf-card-header py-5">
                        <h2 class="sf-section-title text-xl">Choose how to connect</h2>
                        <p class="sf-section-subtitle text-sm leading-6">Choose the option that matches how your number is used today.</p>
                    </div>

                    <div class="grid gap-4 p-6 md:grid-cols-2">
                        <article class="sf-mini-card p-5">
                            <div class="text-xs font-semibold uppercase tracking-[0.12em] text-[color:var(--sf-orange)]">Existing number</div>
                            <h3 class="mt-3 font-heading text-xl font-semibold text-[color:var(--sf-text-strong)]">Keep using your WhatsApp Business app</h3>
                            <p class="mt-2 text-sm leading-6 text-[color:var(--sf-muted)]">Connect the same eligible number to SayaraForce while retaining the mobile app through Meta's Business App onboarding.</p>
                            <ul class="mt-4 space-y-2 text-sm text-[color:var(--sf-muted)]">
                                <li>Back up your WhatsApp Business chats first.</li>
                                <li>Keep the phone available for Meta verification.</li>
                                <li>Cancel if Meta asks to remove or migrate the number away from the app.</li>
                            </ul>
                            <button type="button"
                                    class="sf-btn-primary mt-5 min-h-11 w-full"
                                    data-connect-mode="business_app_onboarding"
                                    @disabled(!($configurations['business_app_onboarding']['is_configured'] ?? false))>
                                Connect existing Business app number
                            </button>
                            @unless($configurations['business_app_onboarding']['is_configured'] ?? false)
                                <p class="mt-3 text-xs text-[color:var(--sf-muted)]">This option is not configured yet. SayaraForce will not substitute the dedicated-number flow.</p>
                            @endunless
                        </article>

                        <article class="sf-mini-card p-5">
                            <div class="text-xs font-semibold uppercase tracking-[0.12em] text-[color:var(--sf-muted-strong)]">New or dedicated number</div>
                            <h3 class="mt-3 font-heading text-xl font-semibold text-[color:var(--sf-text-strong)]">Connect a dedicated WhatsApp number</h3>
                            <p class="mt-2 text-sm leading-6 text-[color:var(--sf-muted)]">Use standard Meta Embedded Signup for a new number or a number dedicated to SayaraForce.</p>
                            <ul class="mt-4 space-y-2 text-sm text-[color:var(--sf-muted)]">
                                <li>Select or create the business in Meta.</li>
                                <li>Verify the number inside Meta's secure flow.</li>
                                <li>Return here to see connection checks and open the inbox.</li>
                            </ul>
                            <button type="button"
                                    class="sf-btn-secondary mt-5 min-h-11 w-full"
                                    data-connect-mode="cloud_api"
                                    @disabled(!($configurations['cloud_api']['is_configured'] ?? false))>
                                Connect dedicated number
                            </button>
                        </article>
                    </div>
                </section>

                <section class="sf-card p-6">
                    <h2 class="sf-section-title text-xl">Messaging consent</h2>
                    <p class="sf-section-subtitle mt-2 text-sm leading-6">Review and accept this before opening Meta.</p>
                    <label class="sf-mini-card mt-4 flex cursor-pointer items-start gap-3 p-4 text-sm leading-6 text-[color:var(--sf-text)]">
                        <input id="messagingConsent" type="checkbox" class="mt-1 rounded border-[color:var(--sf-border-strong)] bg-[color:var(--sf-input-bg)] text-[color:var(--sf-orange)] focus:ring-[color:var(--sf-focus-ring)]">
                        <span>
                            I understand that SayaraForce will receive and store business messages; authorised garage employees may access conversations; and messages may create or update CRM records. AI analysis and automated follow-ups remain separate. This garage remains responsible for lawful communication and customer opt-outs.
                        </span>
                    </label>
                    <p class="mt-3 text-sm text-[color:var(--sf-muted)]">SayaraForce will never ask for your Facebook password.</p>
                </section>
            @else
                <section class="sf-card p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="sf-badge-green">Connected</div>
                            <h2 class="sf-section-title mt-3 text-2xl">WhatsApp is ready</h2>
                            <p class="sf-section-subtitle mt-2 text-sm">Messages for this number are mapped to {{ $company->name }}.</p>
                        </div>
                        @if(Route::has('admin.inbox.index'))
                            <a href="{{ route('admin.inbox.index') }}" class="sf-btn-primary min-h-11">Open Inbox</a>
                        @endif
                    </div>
                </section>
            @endunless

            @if($connection)
                <section class="sf-card p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="sf-section-title text-xl">Connection checks</h2>
                            <p class="sf-section-subtitle mt-1 text-sm">Each check is stored separately so a failed step can be retried safely.</p>
                        </div>
                        <form method="POST" action="{{ route('admin.messaging.whatsapp.health') }}">
                            @csrf
                            <button class="sf-btn-secondary min-h-11">Refresh checks</button>
                        </form>
                    </div>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        @forelse($checks as $check)
                            <div class="sf-mini-card p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <strong class="text-sm text-[color:var(--sf-text-strong)]">{{ str($check->check_key)->replace('_', ' ')->title() }}</strong>
                                    <span class="{{ $check->status === 'passed' ? 'sf-badge-green' : ($check->status === 'failed' ? 'sf-badge-red' : 'sf-badge-yellow') }}">{{ ucfirst($check->status) }}</span>
                                </div>
                                <p class="mt-2 text-sm leading-6 text-[color:var(--sf-muted)]">{{ $check->summary }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-[color:var(--sf-muted)]">Checks appear after Meta returns the account details.</p>
                        @endforelse
                    </div>

                    @if($needsAction)
                        <form method="POST" action="{{ route('admin.messaging.whatsapp.retry') }}" class="mt-5">
                            @csrf
                            <button class="sf-btn-primary min-h-11">Retry saved connection</button>
                        </form>
                    @endif
                </section>

                <section class="sf-card p-6">
                    <h2 class="sf-section-title text-xl">Disconnect from SayaraForce</h2>
                    <p class="sf-section-subtitle mt-2 text-sm leading-6">This stops future sending in SayaraForce and preserves CRM history. It does not delete the number, account or business from Meta.</p>
                    <form method="POST" action="{{ route('admin.messaging.whatsapp.disconnect') }}" class="mt-4" onsubmit="return confirm('Disconnect this number from SayaraForce? Historical CRM records will remain.');">
                        @csrf
                        <label class="mb-3 flex items-start gap-3 text-sm text-[color:var(--sf-text)]">
                            <input type="checkbox" name="confirm_disconnect" value="1" required class="mt-1 rounded border-[color:var(--sf-border-strong)] text-[color:var(--sf-orange)] focus:ring-[color:var(--sf-focus-ring)]">
                            <span>I understand future outbound WhatsApp sending will stop until the connection is restored.</span>
                        </label>
                        <button class="sf-btn-danger min-h-11">Disconnect locally</button>
                    </form>
                </section>
            @endif
        </main>

        <aside class="space-y-6">
            <section class="sf-card p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="sf-section-title">Connection status</h2>
                    <span class="{{ $connected ? 'sf-badge-green' : ($needsAction ? 'sf-badge-red' : 'sf-badge-yellow') }}">{{ str($status)->replace('_', ' ')->title() }}</span>
                </div>
                <dl class="mt-5 space-y-3 text-sm">
                    @foreach([
                        'Garage' => $company->name,
                        'Connected number' => $maskedPhone ?: 'Not connected',
                        'Connection type' => $connection?->connection_mode === 'business_app_onboarding' ? 'Existing WhatsApp Business app' : ($connection ? 'Dedicated number' : 'Not selected'),
                        'Number registration' => $phone?->registration_status ?: 'Not checked',
                        'Coexistence' => $phone?->coexistence_status ?: 'Not applicable',
                        'Last health check' => optional($connection?->last_verified_at)->toDayDateTimeString() ?: 'Not run',
                        'Last received message' => optional($company->whatsapp_last_inbound_at)->toDayDateTimeString() ?: 'Not received',
                    ] as $label => $value)
                        <div class="sf-mini-card p-3">
                            <dt class="text-xs font-semibold uppercase tracking-[0.08em] text-[color:var(--sf-muted)]">{{ $label }}</dt>
                            <dd class="mt-1 break-words font-medium text-[color:var(--sf-text-strong)]">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="sf-card p-6">
                <h2 class="sf-section-title">You will need</h2>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-[color:var(--sf-muted)]">
                    <li>Facebook access to your business.</li>
                    <li>Authority to connect the WhatsApp account.</li>
                    <li>Access to receive an OTP.</li>
                    <li>Your two-step-verification PIN when Meta requests it.</li>
                </ul>
            </section>
        </aside>
    </div>
</div>

<script>
(() => {
    const configurations = @json($configurations);
    const startUrl = @json(route('admin.messaging.whatsapp.start'));
    const completeUrl = @json(route('admin.messaging.whatsapp.complete'));
    const csrf = @json(csrf_token());
    const consent = document.getElementById('messagingConsent');
    const alertBox = document.getElementById('messagingWhatsAppAlert');
    const statusBox = document.getElementById('messagingWhatsAppStatus');
    let active = null;
    let authCode = null;
    let sessionInfo = null;
    let submitted = false;

    const show = (node, message) => {
        if (!node) return;
        node.textContent = message;
        node.classList.remove('hidden');
        (node === alertBox ? statusBox : alertBox)?.classList.add('hidden');
    };
    const trustedOrigin = (origin) => {
        try {
            const url = new URL(origin);
            return url.protocol === 'https:' && (url.hostname === 'facebook.com' || url.hostname.endsWith('.facebook.com'));
        } catch (_) { return false; }
    };
    const eventMatches = (mode, eventName) => mode === 'business_app_onboarding'
        ? eventName === 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING'
        : eventName === 'FINISH' || eventName === 'FINISH_ONLY_WABA';

    async function completeIfReady() {
        if (!active || !authCode || !sessionInfo || submitted) return;
        submitted = true;
        show(statusBox, 'Verifying the shared account, number and message delivery…');
        try {
            const response = await fetch(completeUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
                body: JSON.stringify({
                    code: authCode,
                    state: active.state,
                    nonce: active.nonce,
                    session_event: sessionInfo.event,
                    business_id: sessionInfo.data.business_id || null,
                    waba_id: sessionInfo.data.waba_id || null,
                    phone_number_id: sessionInfo.data.phone_number_id || null,
                }),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.ok) throw new Error(data.message || 'WhatsApp could not be connected.');
            show(statusBox, data.message);
            window.setTimeout(() => window.location.reload(), 900);
        } catch (error) {
            submitted = false;
            show(alertBox, error.message || 'WhatsApp could not be connected. Retry safely from this page.');
        }
    }

    window.addEventListener('message', (event) => {
        if (!active || !trustedOrigin(event.origin)) return;
        let message = event.data;
        try { if (typeof message === 'string') message = JSON.parse(message); } catch (_) { return; }
        if (!message || message.type !== 'WA_EMBEDDED_SIGNUP') return;
        if (message.event === 'CANCEL') {
            active = authCode = sessionInfo = null;
            show(alertBox, 'Meta onboarding was cancelled. No WhatsApp connection was saved.');
            return;
        }
        if (message.event === 'ERROR') {
            show(alertBox, 'Meta reported an onboarding error. No connection was activated.');
            return;
        }
        if (!eventMatches(active.mode, String(message.event || ''))) {
            if (String(message.event || '').startsWith('FINISH')) {
                show(alertBox, 'Meta returned a different connection flow. Nothing was activated.');
            }
            return;
        }
        sessionInfo = {event: message.event, data: (message.data && typeof message.data === 'object') ? message.data : {}};
        completeIfReady();
    });

    window.fbAsyncInit = () => {
        const first = Object.values(configurations).find((item) => item?.app_id);
        if (!first) return;
        FB.init({appId: first.app_id, autoLogAppEvents: false, xfbml: false, version: first.graph_version});
    };
    if (!document.getElementById('facebook-jssdk')) {
        const script = document.createElement('script');
        script.id = 'facebook-jssdk';
        script.async = true;
        script.defer = true;
        script.crossOrigin = 'anonymous';
        script.src = 'https://connect.facebook.net/en_US/sdk.js';
        document.head.appendChild(script);
    }

    document.querySelectorAll('[data-connect-mode]').forEach((button) => button.addEventListener('click', async () => {
        const mode = button.dataset.connectMode;
        if (!consent?.checked) {
            show(alertBox, 'Accept the messaging consent before opening Meta.');
            consent?.focus();
            return;
        }
        if (!window.FB) {
            show(alertBox, 'Meta signup could not load. Check browser privacy controls and try again.');
            return;
        }
        button.disabled = true;
        try {
            const response = await fetch(startUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
                body: JSON.stringify({connection_mode: mode, consent_accepted: true}),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.ok) throw new Error(data.message || 'The secure connection session could not start.');
            active = {mode, state: data.state, nonce: data.nonce};
            authCode = sessionInfo = null;
            submitted = false;
            const config = data.configuration;
            show(statusBox, 'Opening Meta secure signup…');
            FB.login((result) => {
                if (!result?.authResponse?.code) {
                    show(alertBox, 'Meta did not grant temporary authorization. No connection was saved.');
                    return;
                }
                authCode = result.authResponse.code;
                completeIfReady();
            }, {
                config_id: config.config_id,
                response_type: 'code',
                override_default_response_type: true,
                extras: config.extras,
            });
        } catch (error) {
            show(alertBox, error.message || 'The WhatsApp connection could not start.');
        } finally {
            button.disabled = false;
        }
    }));
})();
</script>
@endsection
