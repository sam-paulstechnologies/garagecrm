@extends('layouts.app')

@section('title', 'Connect WhatsApp')

@section('content')
@php
    use App\Services\WhatsApp\MetaEmbeddedSignupService;

    $businessAppMode = MetaEmbeddedSignupService::MODE_BUSINESS_APP;
    $cloudApiMode = MetaEmbeddedSignupService::MODE_CLOUD_API;
    $isBusinessAppMode = $connectionMode === $businessAppMode;
    $isConnected = (bool) ($status['is_connected'] ?? false);
    $modeLabel = match ($status['connection_mode'] ?? 'manual') {
        $businessAppMode => 'Existing WhatsApp Business app',
        $cloudApiMode => 'Dedicated Cloud API number',
        default => 'Not connected',
    };
    $callbackUrl = route('admin.whatsapp.connect.callback');
    $statusUrl = route('admin.whatsapp.connect.status');
    $canLaunch = (bool) ($signupConfiguration['is_configured'] ?? false);
@endphp

<div class="sf-page">
    @if(session('success'))
        <div class="sf-alert-success mb-5">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="sf-alert-danger mb-5">
            {{ session('error') }}
        </div>
    @endif

    <div id="sfwaBrowserAlert" role="alert" class="sf-alert-danger mb-5 hidden"></div>
    <div id="sfwaBrowserStatus" aria-live="polite" class="sf-alert-info mb-5 hidden"></div>

    <header class="mb-7 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="sf-badge-orange uppercase tracking-[0.12em]">
                SayaraForce WhatsApp connection
            </div>
            <h1 class="sf-page-title mt-4">
                Connect this garage's WhatsApp
            </h1>
            <p class="sf-page-subtitle">
                Choose the path that matches the number today. Existing Business app numbers must use Meta's Business App onboarding flow; dedicated API numbers use the standard Cloud API flow.
            </p>
        </div>

        <a href="{{ route('admin.whatsapp.settings.edit') }}" class="sf-btn-secondary min-h-11">
            WhatsApp settings
        </a>
    </header>

    @unless($canLaunch)
        <div class="sf-alert-warning mb-6 leading-6">
            <strong>This onboarding path is not configured.</strong>
            @if($isBusinessAppMode)
                An explicit Meta v4 Business App onboarding configuration is required. SayaraForce will not fall back to the standard migration flow.
            @else
                Configure the dedicated Cloud API Embedded Signup configuration before connecting a number.
            @endif
        </div>
    @endunless

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(320px,.75fr)]">
        <main class="space-y-6">
            <section class="sf-card overflow-hidden">
                <div class="sf-card-header py-5">
                    <h2 class="sf-section-title text-xl">Choose one onboarding path</h2>
                    <p class="sf-section-subtitle text-sm leading-6">The two flows are intentionally separate and use separate server configuration.</p>
                </div>

                <div class="grid gap-4 p-6 md:grid-cols-2">
                    <article class="sf-mini-card p-5 {{ $isBusinessAppMode ? 'border-[color:var(--sf-orange)] bg-[color:var(--sf-selected)]' : '' }}">
                        <div class="text-xs font-semibold uppercase tracking-[0.12em] text-[color:var(--sf-orange)]">Existing number</div>
                        <h3 class="mt-3 font-heading text-xl font-semibold text-[color:var(--sf-text-strong)]">Connect an existing WhatsApp Business app number</h3>
                        <p class="mt-2 text-sm leading-6 text-[color:var(--sf-muted)]">Keep using the WhatsApp Business mobile app while connecting the same number to SayaraForce through Meta's Business App onboarding flow.</p>
                        <ul class="mt-4 space-y-2 text-sm text-[color:var(--sf-muted)]">
                            <li>Mobile app remains available after successful coexistence onboarding.</li>
                            <li>New customer messages can enter the SayaraForce inbox.</li>
                            <li>Mobile-app replies are captured as manual outbound echoes.</li>
                        </ul>

                        @if($isBusinessAppMode)
                            <label class="mt-5 flex cursor-pointer items-start gap-3 rounded-xl border border-[color:var(--sf-border)] bg-[color:var(--sf-surface-soft)] p-3 text-sm leading-5 text-[color:var(--sf-text)]">
                                <input id="sfwaBusinessAppAcknowledgement" type="checkbox" class="mt-0.5 rounded border-[color:var(--sf-border-strong)] bg-[color:var(--sf-input-bg)] text-[color:var(--sf-orange)] focus:ring-[color:var(--sf-focus-ring)]">
                                <span>Only continue if Meta confirms that your WhatsApp Business app will remain active. Cancel the setup if Meta asks to remove, transfer or migrate the number away from the app.</span>
                            </label>
                            <button id="sfwaLaunchButton" type="button" @disabled(!$canLaunch) class="sf-btn-primary mt-4 min-h-11 w-full px-5 py-2.5 disabled:cursor-not-allowed disabled:opacity-45">
                                Connect existing Business app number
                            </button>
                        @else
                            <a href="{{ route('admin.whatsapp.connect', ['mode' => $businessAppMode]) }}" class="sf-btn-secondary mt-4 min-h-11 w-full px-5 py-2.5">
                                Select existing-number path
                            </a>
                        @endif
                    </article>

                    <article class="sf-mini-card p-5 {{ !$isBusinessAppMode ? 'border-[color:var(--sf-orange)] bg-[color:var(--sf-selected)]' : '' }}">
                        <div class="text-xs font-semibold uppercase tracking-[0.12em] text-[color:var(--sf-muted-strong)]">Dedicated API number</div>
                        <h3 class="mt-3 font-heading text-xl font-semibold text-[color:var(--sf-text-strong)]">Connect a new or dedicated Cloud API number</h3>
                        <p class="mt-2 text-sm leading-6 text-[color:var(--sf-muted)]">Use standard Embedded Signup when SayaraForce will operate a number that is not being retained in the WhatsApp Business mobile app.</p>
                        <ul class="mt-4 space-y-2 text-sm text-[color:var(--sf-muted)]">
                            <li>Standard Cloud API onboarding remains supported.</li>
                            <li>SayaraForce validates the returned WABA and number server-side.</li>
                            <li>The app subscribes to the selected WABA after validation.</li>
                        </ul>

                        @if(!$isBusinessAppMode)
                            <button id="sfwaLaunchButton" type="button" @disabled(!$canLaunch) class="sf-btn-primary mt-4 min-h-11 w-full px-5 py-2.5 disabled:cursor-not-allowed disabled:opacity-45">
                                Connect dedicated API number
                            </button>
                        @else
                            <a href="{{ route('admin.whatsapp.connect', ['mode' => $cloudApiMode]) }}" class="sf-btn-secondary mt-4 min-h-11 w-full px-5 py-2.5">
                                Select dedicated-number path
                            </a>
                        @endif
                    </article>
                </div>
            </section>

            <section class="sf-card p-6">
                <h2 class="sf-section-title text-xl">Before using an existing number</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach([
                        'Back up chats in the WhatsApp Business app.',
                        'Confirm the number is on WhatsApp Business, not personal WhatsApp.',
                        'Keep the phone available to scan Meta\'s QR code.',
                        'Cancel immediately if the flow says the app number will be removed or migrated.',
                    ] as $item)
                        <div class="sf-mini-card rounded-xl p-4 text-sm leading-6">{{ $item }}</div>
                    @endforeach
                </div>
            </section>

            @if($isConnected)
                <section class="sf-card p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="sf-section-title text-xl">Diagnostics and synchronization</h2>
                            <p class="sf-section-subtitle text-sm leading-6">These actions use the securely stored company credential. Tokens are never displayed.</p>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap gap-3">
                        <form method="POST" action="{{ route('admin.whatsapp.connect.diagnostics') }}">
                            @csrf
                            <button class="sf-btn-primary min-h-11">Run diagnostics</button>
                        </form>

                        @if(($status['business_app_onboarding'] ?? false))
                            <form method="POST" action="{{ route('admin.whatsapp.connect.sync.contacts') }}">
                                @csrf
                                <button class="sf-btn-secondary min-h-11">Retry contact sync</button>
                            </form>
                            <form method="POST" action="{{ route('admin.whatsapp.connect.sync.history') }}" onsubmit="return confirm('Request chat-history synchronization only if the business approved sharing history during onboarding. Continue?');">
                                @csrf
                                <button class="sf-btn-secondary min-h-11">Request approved history sync</button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('admin.whatsapp.connect.disconnect') }}" onsubmit="return confirm('Disable this WhatsApp connection in SayaraForce? This does not remove the number from Meta or the mobile app.');">
                            @csrf
                            <button class="sf-btn-danger min-h-11">Disable locally</button>
                        </form>
                    </div>
                </section>
            @endif
        </main>

        <aside class="space-y-6">
            <section class="sf-card p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="sf-section-title">Connection status</h2>
                    <span class="{{ $isConnected ? 'sf-badge-green' : 'sf-badge-yellow' }}">
                        {{ $isConnected ? 'Connected' : 'Not connected' }}
                    </span>
                </div>

                <dl class="mt-5 space-y-4 text-sm">
                    @foreach([
                        'Mode' => $modeLabel,
                        'WABA' => $status['waba_id'] ?? 'Not available',
                        'Phone' => $status['masked_display_phone_number'] ?? 'Not available',
                        'Phone number ID' => $status['masked_phone_number_id'] ?? 'Not available',
                        'App-level callback URL verification' => $status['callback_verification_status'] ?? 'Not checked',
                        'WABA app subscription' => $status['waba_subscription_status'] ?? 'Not checked',
                        'Last inbound webhook' => $status['last_inbound_at'] ?? 'Not received',
                        'Last mobile-app echo' => $status['last_mobile_app_echo_at'] ?? 'Not received',
                        'Last API outbound' => $status['last_api_outbound_at'] ?? 'Not sent',
                        'Contact sync' => $status['contact_sync_status'] ?? 'Not requested',
                        'History sync' => $status['history_sync_status'] ?? 'Not requested',
                    ] as $label => $value)
                        <div class="sf-mini-card rounded-xl p-3">
                            <dt class="text-xs font-semibold uppercase tracking-[0.1em] text-[color:var(--sf-muted)]">{{ $label }}</dt>
                            <dd class="mt-1 break-words font-medium text-[color:var(--sf-text-strong)]">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="sf-card p-6">
                <h2 class="sf-section-title">Recent connection activity</h2>
                <div class="mt-4 space-y-3">
                    @forelse($recentAudits as $audit)
                        <div class="sf-mini-card rounded-xl p-3">
                            <div class="text-sm font-semibold text-[color:var(--sf-text-strong)]">{{ str($audit->event)->replace('_', ' ')->title() }}</div>
                            <div class="mt-1 text-xs text-[color:var(--sf-muted)]">{{ $audit->status }} · {{ optional($audit->occurred_at)->toDayDateTimeString() }}</div>
                        </div>
                    @empty
                        <p class="text-sm leading-6 text-[color:var(--sf-muted)]">No connection activity recorded yet.</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</div>

<script>
    const sfwaConfig = @json($signupConfiguration);
    const sfwaState = @json($state);
    const sfwaCallbackUrl = @json($callbackUrl);
    const sfwaStatusUrl = @json($statusUrl);
    const sfwaCsrf = @json(csrf_token());
    const sfwaExpectedBusinessAppEvent = 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING';
    const sfwaLaunchButton = document.getElementById('sfwaLaunchButton');
    const sfwaAlert = document.getElementById('sfwaBrowserAlert');
    const sfwaStatus = document.getElementById('sfwaBrowserStatus');
    const sfwaAcknowledgement = document.getElementById('sfwaBusinessAppAcknowledgement');
    let sfwaAuthorizationCode = null;
    let sfwaSession = null;
    let sfwaSubmitted = false;
    let sfwaCompletionTimer = null;

    function sfwaShowStatus(message) {
        sfwaAlert?.classList.add('hidden');
        if (sfwaStatus) {
            sfwaStatus.textContent = message;
            sfwaStatus.classList.remove('hidden');
        }
    }

    function sfwaShowError(message) {
        sfwaStatus?.classList.add('hidden');
        if (sfwaAlert) {
            sfwaAlert.textContent = message;
            sfwaAlert.classList.remove('hidden');
        }
    }

    function sfwaIsTrustedOrigin(origin) {
        try {
            const url = new URL(origin);
            return url.protocol === 'https:' && (url.hostname === 'facebook.com' || url.hostname.endsWith('.facebook.com'));
        } catch (_) {
            return false;
        }
    }

    function sfwaExpectedEvent(eventName) {
        if (sfwaConfig.connection_mode === @json($businessAppMode)) {
            return eventName === sfwaExpectedBusinessAppEvent;
        }

        return eventName === 'FINISH' || eventName === 'FINISH_ONLY_WABA';
    }

    async function sfwaTryComplete() {
        if (sfwaSubmitted || !sfwaAuthorizationCode || !sfwaSession) {
            return;
        }

        sfwaSubmitted = true;
        window.clearTimeout(sfwaCompletionTimer);
        sfwaShowStatus('Validating the Meta account and subscribing webhooks…');

        try {
            const response = await fetch(sfwaCallbackUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': sfwaCsrf,
                },
                body: JSON.stringify({
                    code: sfwaAuthorizationCode,
                    state: sfwaState,
                    session_event: sfwaSession.event,
                    business_id: sfwaSession.data.business_id || null,
                    waba_id: sfwaSession.data.waba_id || null,
                    phone_number_id: sfwaSession.data.phone_number_id || null,
                }),
            });

            const json = await response.json().catch(() => ({}));
            if (!response.ok || !json.ok) {
                throw new Error(json.message || 'WhatsApp connection could not be completed. Restart the flow.');
            }

            sfwaShowStatus(json.warnings?.length ? json.warnings.join(' ') : json.message);
            window.setTimeout(() => window.location.reload(), 900);
        } catch (error) {
            sfwaSubmitted = false;
            sfwaAuthorizationCode = null;
            sfwaSession = null;
            sfwaShowError(error.message || 'WhatsApp connection could not be completed. Restart the flow.');
        }
    }

    window.addEventListener('message', function (event) {
        if (!sfwaIsTrustedOrigin(event.origin)) {
            return;
        }

        let message = event.data;
        try {
            if (typeof message === 'string') {
                message = JSON.parse(message);
            }
        } catch (_) {
            return;
        }

        if (!message || message.type !== 'WA_EMBEDDED_SIGNUP') {
            return;
        }

        const eventName = String(message.event || '');
        const data = message.data && typeof message.data === 'object' ? message.data : {};

        if (eventName === 'CANCEL') {
            window.clearTimeout(sfwaCompletionTimer);
            sfwaAuthorizationCode = null;
            sfwaSession = null;
            sfwaShowError('Meta onboarding was cancelled. No WhatsApp connection was saved.');
            return;
        }

        if (eventName === 'ERROR') {
            window.clearTimeout(sfwaCompletionTimer);
            sfwaShowError('Meta reported an onboarding error. No WhatsApp connection was saved; restart the flow.');
            return;
        }

        if (!sfwaExpectedEvent(eventName)) {
            if (sfwaConfig.connection_mode === @json($businessAppMode) && (eventName === 'FINISH' || eventName === 'FINISH_ONLY_WABA')) {
                window.clearTimeout(sfwaCompletionTimer);
                sfwaAuthorizationCode = null;
                sfwaSession = null;
                sfwaShowError('Meta returned the standard API flow instead of Business App onboarding. Nothing was connected. Cancel if Meta asks to migrate the mobile-app number.');
            }
            return;
        }

        sfwaSession = { event: eventName, data };
        sfwaShowStatus('Meta onboarding completed. Waiting for secure server validation…');
        sfwaTryComplete();
    });

    window.fbAsyncInit = function () {
        FB.init({
            appId: sfwaConfig.app_id,
            autoLogAppEvents: false,
            xfbml: true,
            version: sfwaConfig.graph_version,
        });
    };

    (function (document, tagName, id) {
        if (document.getElementById(id)) return;
        const script = document.createElement(tagName);
        script.id = id;
        script.async = true;
        script.defer = true;
        script.crossOrigin = 'anonymous';
        script.src = 'https://connect.facebook.net/en_US/sdk.js';
        const firstScript = document.getElementsByTagName(tagName)[0];
        firstScript.parentNode.insertBefore(script, firstScript);
    }(document, 'script', 'facebook-jssdk'));

    sfwaLaunchButton?.addEventListener('click', function () {
        if (!sfwaConfig.is_configured) {
            sfwaShowError('This onboarding path is not configured.');
            return;
        }

        if (sfwaConfig.connection_mode === @json($businessAppMode) && !sfwaAcknowledgement?.checked) {
            sfwaShowError('Confirm the backup and migration warning before opening Meta onboarding.');
            return;
        }

        if (!window.FB) {
            sfwaShowError('The Meta signup library has not loaded. Check browser privacy controls and try again.');
            return;
        }

        sfwaAuthorizationCode = null;
        sfwaSession = null;
        sfwaSubmitted = false;
        sfwaShowStatus('Opening Meta Embedded Signup…');

        sfwaCompletionTimer = window.setTimeout(function () {
            if (!sfwaSubmitted) {
                sfwaAuthorizationCode = null;
                sfwaSession = null;
                sfwaShowError('Meta did not return complete session information. Nothing was connected; restart the flow.');
            }
        }, 120000);

        FB.login(function (response) {
            if (!response?.authResponse?.code) {
                window.clearTimeout(sfwaCompletionTimer);
                sfwaShowError('Meta did not grant an authorization code. Nothing was connected.');
                return;
            }

            sfwaAuthorizationCode = response.authResponse.code;
            sfwaTryComplete();
        }, {
            config_id: sfwaConfig.config_id,
            response_type: 'code',
            override_default_response_type: true,
            extras: sfwaConfig.extras,
        });
    });
</script>
@endsection
