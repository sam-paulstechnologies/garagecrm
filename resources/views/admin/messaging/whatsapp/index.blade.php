@extends('layouts.app')

@section('title', 'Connect WhatsApp')

@section('content')
@php
    $status = $connection?->status?->value ?? 'not_connected';
    $connected = $status === 'connected';
    $needsAction = in_array($status, ['requires_action', 'failed'], true);
    $savedNumber = $numberClaim?->phone_e164 ?: $phone?->display_phone_number;
    $numberAdded = filled($savedNumber);
    $selectedMode = $numberClaim?->connection_mode ?: $connection?->connection_mode;
    $selectedConfiguration = $selectedMode ? ($configurations[$selectedMode] ?? []) : [];
    $metaConfigured = (bool) ($selectedConfiguration['is_configured'] ?? false);
    $metaVerified = in_array($numberClaim?->status, ['meta_verified', 'ready'], true)
        || (filled($phone?->phone_number_id) && filled($connection?->waba_id));
    $webhookReady = ($checks->get('app_subscription')?->status === 'passed')
        && ($checks->get('webhook_fields')?->status === 'passed');
    $ready = $connected && $webhookReady;
    $editableClaim = $numberClaim?->isUnverified() ?? false;
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
    @if($errors->any())
        <div class="sf-alert-danger mb-5" role="alert">
            <ul class="list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif
    <div id="messagingWhatsAppAlert" class="sf-alert-danger mb-5 hidden" role="alert"></div>
    <div id="messagingWhatsAppStatus" class="sf-alert-info mb-5 hidden" aria-live="polite"></div>

    <header class="mb-7 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="sf-badge-orange uppercase tracking-[0.12em]">Secure WhatsApp setup</div>
            <h1 class="sf-page-title mt-4">Connect your garage WhatsApp</h1>
            <p class="sf-page-subtitle max-w-3xl">
                Add the number your garage owns first. Meta verification is a separate step and SayaraForce never marks a number connected until Meta confirms it.
            </p>
        </div>
        @if($connected && Route::has('admin.inbox.index'))
            <a href="{{ route('admin.inbox.index') }}" class="sf-btn-primary min-h-11">Open Inbox</a>
        @endif
    </header>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,.75fr)]">
        <main class="space-y-6">
            @unless($numberAdded)
                <section class="sf-card overflow-hidden">
                    <div class="sf-card-header py-5">
                        <div class="text-xs font-semibold uppercase tracking-[0.12em] text-[color:var(--sf-orange)]">Step 1</div>
                        <h2 class="sf-section-title mt-2 text-xl">Add your WhatsApp number</h2>
                        <p class="sf-section-subtitle text-sm leading-6">This saves an onboarding number only. It does not connect, subscribe or verify anything with Meta.</p>
                    </div>
                    <form method="POST" action="{{ route('admin.messaging.whatsapp.number.store') }}" class="space-y-6 p-6">
                        @csrf
                        <div class="grid gap-4 md:grid-cols-[180px_1fr]">
                            <label class="block">
                                <span class="text-sm font-semibold text-[color:var(--sf-text-strong)]">Country code</span>
                                <select name="country_code" class="sf-input mt-2 w-full" required>
                                    @foreach(['+971' => 'UAE +971', '+966' => 'Saudi Arabia +966', '+974' => 'Qatar +974', '+973' => 'Bahrain +973', '+965' => 'Kuwait +965', '+968' => 'Oman +968', '+20' => 'Egypt +20', '+91' => 'India +91', '+92' => 'Pakistan +92', '+44' => 'United Kingdom +44', '+1' => 'US / Canada +1'] as $code => $label)
                                        <option value="{{ $code }}" @selected(old('country_code', '+971') === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block">
                                <span class="text-sm font-semibold text-[color:var(--sf-text-strong)]">WhatsApp Business number</span>
                                <input name="phone_number" value="{{ old('phone_number') }}" class="sf-input mt-2 w-full" inputmode="tel" autocomplete="tel" placeholder="50 123 4567" required>
                                <span class="mt-2 block text-xs text-[color:var(--sf-muted)]">You may also enter a full international number beginning with +.</span>
                            </label>
                        </div>
                        <label class="block">
                            <span class="text-sm font-semibold text-[color:var(--sf-text-strong)]">Display label (optional)</span>
                            <input name="label" value="{{ old('label') }}" class="sf-input mt-2 w-full" maxlength="80" placeholder="Main service desk">
                        </label>

                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.12em] text-[color:var(--sf-orange)]">Step 2</div>
                            <h3 class="mt-2 font-heading text-lg font-semibold text-[color:var(--sf-text-strong)]">Choose how you use this number</h3>
                            <div class="mt-4 grid gap-4 md:grid-cols-2">
                                <label class="sf-mini-card cursor-pointer p-5">
                                    <input type="radio" name="connection_mode" value="business_app_onboarding" @checked(old('connection_mode', 'business_app_onboarding') === 'business_app_onboarding') required>
                                    <strong class="ml-2 text-[color:var(--sf-text-strong)]">Keep using WhatsApp Business App</strong>
                                    <span class="mt-2 block text-sm leading-6 text-[color:var(--sf-muted)]">Use Meta's coexistence flow when the eligible number should remain in the mobile Business app.</span>
                                </label>
                                <label class="sf-mini-card cursor-pointer p-5">
                                    <input type="radio" name="connection_mode" value="cloud_api" @checked(old('connection_mode') === 'cloud_api') required>
                                    <strong class="ml-2 text-[color:var(--sf-text-strong)]">Dedicated WhatsApp number</strong>
                                    <span class="mt-2 block text-sm leading-6 text-[color:var(--sf-muted)]">Use standard Cloud API onboarding for a number dedicated to SayaraForce.</span>
                                </label>
                            </div>
                        </div>
                        <button class="sf-btn-primary min-h-11">Add WhatsApp number</button>
                    </form>
                </section>
            @else
                <section class="sf-card p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="{{ $connected ? 'sf-badge-green' : 'sf-badge-yellow' }}">{{ $connected ? 'Connected' : 'Pending Meta verification' }}</div>
                            <h2 class="sf-section-title mt-3 text-2xl">{{ $numberClaim?->label ?: 'Garage WhatsApp number' }}</h2>
                            <p class="mt-2 font-mono text-lg text-[color:var(--sf-text-strong)]">{{ $savedNumber }}</p>
                            <p class="sf-section-subtitle mt-2 text-sm">{{ $selectedMode === 'business_app_onboarding' ? 'Existing WhatsApp Business App / coexistence' : 'Dedicated WhatsApp number' }}</p>
                        </div>
                        @if($editableClaim)
                            <form method="POST" action="{{ route('admin.messaging.whatsapp.number.destroy', $numberClaim) }}" onsubmit="return confirm('Remove this unverified number? No Meta asset will be changed.');">
                                @csrf
                                @method('DELETE')
                                <button class="sf-btn-danger min-h-11">Remove unverified number</button>
                            </form>
                        @endif
                    </div>

                    @if($editableClaim)
                        <details class="sf-mini-card mt-5 p-4">
                            <summary class="cursor-pointer font-semibold text-[color:var(--sf-text-strong)]">Edit pending number</summary>
                            <form method="POST" action="{{ route('admin.messaging.whatsapp.number.update', $numberClaim) }}" class="mt-4 grid gap-4 md:grid-cols-2">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="country_code" value="">
                                <label class="block">
                                    <span class="text-sm font-semibold">International number</span>
                                    <input name="phone_number" value="{{ old('phone_number', $numberClaim->phone_e164) }}" class="sf-input mt-2 w-full" required>
                                </label>
                                <label class="block">
                                    <span class="text-sm font-semibold">Display label</span>
                                    <input name="label" value="{{ old('label', $numberClaim->label) }}" class="sf-input mt-2 w-full" maxlength="80">
                                </label>
                                <label class="block md:col-span-2">
                                    <span class="text-sm font-semibold">Connection mode</span>
                                    <select name="connection_mode" class="sf-input mt-2 w-full">
                                        <option value="business_app_onboarding" @selected($numberClaim->connection_mode === 'business_app_onboarding')>Existing WhatsApp Business App / coexistence</option>
                                        <option value="cloud_api" @selected($numberClaim->connection_mode === 'cloud_api')>Dedicated WhatsApp number</option>
                                    </select>
                                </label>
                                <button class="sf-btn-secondary min-h-11 md:w-max">Save pending number</button>
                            </form>
                        </details>
                    @endif
                </section>
            @endunless

            @if($numberAdded && ! $connected)
                <section class="sf-card p-6">
                    <div class="text-xs font-semibold uppercase tracking-[0.12em] text-[color:var(--sf-orange)]">Step 3</div>
                    <h2 class="sf-section-title mt-2 text-xl">Connect securely with Meta</h2>
                    @if($metaConfigured)
                        <p class="sf-section-subtitle mt-2 text-sm leading-6">Meta will verify that your garage controls the saved number. A different number returned by Meta will be rejected.</p>
                        <label class="sf-mini-card mt-4 flex cursor-pointer items-start gap-3 p-4 text-sm leading-6 text-[color:var(--sf-text)]">
                            <input id="messagingConsent" type="checkbox" class="mt-1 rounded border-[color:var(--sf-border-strong)] bg-[color:var(--sf-input-bg)] text-[color:var(--sf-orange)] focus:ring-[color:var(--sf-focus-ring)]">
                            <span>I understand that SayaraForce will receive and store business messages and authorised garage employees may access conversations. AI analysis and automated follow-ups remain separate.</span>
                        </label>
                        <button type="button" class="sf-btn-primary mt-4 min-h-11" data-connect-mode="{{ $selectedMode }}">Connect with Meta</button>
                        <p class="mt-3 text-sm text-[color:var(--sf-muted)]">SayaraForce will never ask for your Facebook password.</p>
                    @else
                        <div class="sf-alert-warning mt-4">
                            <strong>WhatsApp number added.</strong><br>
                            Meta connection is not configured for this staging environment yet.
                        </div>
                        <button type="button" class="sf-btn-secondary mt-4 min-h-11" disabled>Connect with Meta</button>
                    @endif
                </section>
            @elseif($connected)
                <section class="sf-card p-6">
                    <div class="sf-badge-green">Ready</div>
                    <h2 class="sf-section-title mt-3 text-2xl">WhatsApp is connected</h2>
                    <p class="sf-section-subtitle mt-2 text-sm">Provider verification and required connection checks passed for {{ $company->name }}.</p>
                </section>
            @endif

            @if($connection)
                <section class="sf-card p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="sf-section-title text-xl">Connection checks</h2>
                            <p class="sf-section-subtitle mt-1 text-sm">Checks appear only after Meta returns provider-controlled account details.</p>
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
                            <p class="text-sm text-[color:var(--sf-muted)]">No provider checks have run.</p>
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
                    <p class="sf-section-subtitle mt-2 text-sm">This preserves CRM history and never deletes external Meta assets.</p>
                    <form method="POST" action="{{ route('admin.messaging.whatsapp.disconnect') }}" class="mt-4" onsubmit="return confirm('Disconnect this number from SayaraForce? Historical CRM records will remain.');">
                        @csrf
                        <label class="mb-3 flex items-start gap-3 text-sm"><input type="checkbox" name="confirm_disconnect" value="1" required><span>I understand future outbound sending will stop until the connection is restored.</span></label>
                        <button class="sf-btn-danger min-h-11">Disconnect locally</button>
                    </form>
                </section>
            @endif
        </main>

        <aside class="space-y-6">
            <section class="sf-card p-6">
                <h2 class="sf-section-title">WhatsApp setup status</h2>
                <ol class="mt-5 space-y-3 text-sm">
                    @foreach([
                        ['Number added', $numberAdded],
                        ['Meta verification', $metaVerified],
                        ['Connection', $connected],
                        ['Webhook', $webhookReady],
                        ['Ready', $ready],
                    ] as [$label, $complete])
                        <li class="sf-mini-card flex items-center justify-between gap-3 p-3">
                            <span class="font-semibold text-[color:var(--sf-text-strong)]">{{ $loop->iteration }}. {{ $label }}</span>
                            <span class="{{ $complete ? 'sf-badge-green' : 'sf-badge-yellow' }}">{{ $complete ? 'Complete' : 'Pending' }}</span>
                        </li>
                    @endforeach
                </ol>
            </section>
            <section class="sf-card p-6">
                <h2 class="sf-section-title">Security boundary</h2>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-[color:var(--sf-muted)]">
                    <li>A saved number is not a connected number.</li>
                    <li>WABA and provider phone IDs can only come from Meta.</li>
                    <li>Pending numbers never receive inbound webhook routing.</li>
                    <li>Plan number allowances are enforced on the server.</li>
                </ul>
            </section>
        </aside>
    </div>
</div>

@if($metaConfigured && $numberAdded && ! $connected)
<script>
(() => {
    const configurations = @json($configurations);
    const numberClaimId = @json($numberClaim?->id);
    const startUrl = @json(route('admin.messaging.whatsapp.start'));
    const completeUrl = @json(route('admin.messaging.whatsapp.complete'));
    const csrf = @json(csrf_token());
    const consent = document.getElementById('messagingConsent');
    const alertBox = document.getElementById('messagingWhatsAppAlert');
    const statusBox = document.getElementById('messagingWhatsAppStatus');
    let active = null, authCode = null, sessionInfo = null, submitted = false;
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
        show(statusBox, 'Verifying the saved number and connection with Meta...');
        try {
            const response = await fetch(completeUrl, {
                method: 'POST', credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
                body: JSON.stringify({
                    code: authCode, state: active.state, nonce: active.nonce,
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
            show(alertBox, error.message || 'WhatsApp could not be connected.');
        }
    }
    window.addEventListener('message', (event) => {
        if (!active || !trustedOrigin(event.origin)) return;
        let message = event.data;
        try { if (typeof message === 'string') message = JSON.parse(message); } catch (_) { return; }
        if (!message || message.type !== 'WA_EMBEDDED_SIGNUP') return;
        if (message.event === 'CANCEL') { active = authCode = sessionInfo = null; show(alertBox, 'Meta onboarding was cancelled. Nothing was connected.'); return; }
        if (message.event === 'ERROR') { show(alertBox, 'Meta reported an onboarding error. Nothing was connected.'); return; }
        if (!eventMatches(active.mode, String(message.event || ''))) return;
        sessionInfo = {event: message.event, data: (message.data && typeof message.data === 'object') ? message.data : {}};
        completeIfReady();
    });
    window.fbAsyncInit = () => {
        const first = Object.values(configurations).find((item) => item?.is_configured && item?.app_id);
        if (first) FB.init({appId: first.app_id, autoLogAppEvents: false, xfbml: false, version: first.graph_version});
    };
    const script = document.createElement('script');
    script.id = 'facebook-jssdk'; script.async = true; script.defer = true;
    script.crossOrigin = 'anonymous'; script.src = 'https://connect.facebook.net/en_US/sdk.js';
    document.head.appendChild(script);
    document.querySelectorAll('[data-connect-mode]').forEach((button) => button.addEventListener('click', async () => {
        const mode = button.dataset.connectMode;
        if (!consent?.checked) { show(alertBox, 'Accept the messaging consent before opening Meta.'); consent?.focus(); return; }
        if (!window.FB) { show(alertBox, 'Meta signup could not load. Try again after the secure SDK loads.'); return; }
        button.disabled = true;
        try {
            const response = await fetch(startUrl, {
                method: 'POST', credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf},
                body: JSON.stringify({number_claim_id: numberClaimId, connection_mode: mode, consent_accepted: true}),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.ok) throw new Error(data.message || 'The secure connection session could not start.');
            active = {mode, state: data.state, nonce: data.nonce}; authCode = sessionInfo = null; submitted = false;
            FB.login((result) => {
                if (!result?.authResponse?.code) { show(alertBox, 'Meta did not grant temporary authorization. Nothing was connected.'); return; }
                authCode = result.authResponse.code; completeIfReady();
            }, {config_id: data.configuration.config_id, response_type: 'code', override_default_response_type: true, extras: data.configuration.extras});
        } catch (error) { show(alertBox, error.message || 'The WhatsApp connection could not start.'); }
        finally { button.disabled = false; }
    }));
})();
</script>
@endif
@endsection
