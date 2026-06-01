@extends('layouts.app')

@section('content')
@php
    use Illuminate\Support\Facades\Route;

    $hasMetaConnect = Route::has('admin.meta.connect');
    $hasMetaRefresh = Route::has('admin.meta.refresh');
    $hasMetaDisconnect = Route::has('admin.meta.disconnect');

    $connectedMeta = $connectedMeta
        ?? \App\Models\MetaPage::where('company_id', $company->id)->first();

    $forms = $forms
        ?? ($connectedMeta ? (json_decode($connectedMeta->forms_json ?? '[]', true) ?: []) : []);

    $defaultFormId = old('meta.form_id', $settings['meta.form_id'] ?? '');

    $cardClass = 'rounded-3xl border border-white/10 bg-slate-900/80 shadow-xl shadow-black/20 overflow-hidden';
    $cardHeaderClass = 'border-b border-white/10 px-6 py-4 bg-slate-950/35';
    $cardBodyClass = 'px-6 py-6';
    $labelClass = 'block text-xs font-extrabold uppercase tracking-wide text-slate-400 mb-1.5';
    $inputClass = 'block w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm font-semibold text-white placeholder:text-slate-600 outline-none transition focus:border-orange-400/50 focus:ring-2 focus:ring-orange-500/10';
    $badgeClass = 'rounded-full px-2.5 py-0.5 text-xs font-extrabold';
@endphp

@include('admin.settings.index-partials._styles')

<div class="sf-integration-settings-page mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-orange-300">
                Admin Settings
            </div>

            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">
                Settings
            </h1>

            <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-400">
                Manage tenant profile, Meta lead forms, WhatsApp/Twilio fallback, garage handoff, and system defaults.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button
                type="submit"
                form="settingsForm"
                formaction="{{ route('admin.settings.test-meta') }}"
                formmethod="POST"
                class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                Test Meta
            </button>

            <button
                type="submit"
                form="settingsForm"
                formaction="{{ route('admin.settings.test-twilio') }}"
                formmethod="POST"
                class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                Test Twilio
            </button>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="mb-5 rounded-2xl border border-green-400/20 bg-green-500/10 px-4 py-3 text-sm font-bold text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="mb-5 rounded-2xl border border-yellow-400/20 bg-yellow-500/10 px-4 py-3 text-sm font-bold text-yellow-300">
            {{ session('warning') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-5 rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-sm font-bold text-red-300">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-sm text-red-300">
            <div class="font-extrabold text-red-200">Please fix the following:</div>
            <ul class="mt-2 list-disc space-y-1 pl-5 font-semibold">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="settingsForm" action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6">
        @csrf

        {{-- Company --}}
        <section class="{{ $cardClass }}">
            <div class="{{ $cardHeaderClass }}">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-extrabold text-white">
                            Company / Tenant Profile
                        </h2>
                        <p class="mt-1 text-sm font-medium text-slate-500">
                            Basic garage identity used across the CRM.
                        </p>
                    </div>

                    <span class="{{ $badgeClass }} bg-slate-700/60 text-slate-300 ring-1 ring-white/10">
                        Tenant
                    </span>
                </div>
            </div>

            <div class="{{ $cardBodyClass }}">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}">
                            Company Name <span class="text-red-400">*</span>
                        </label>

                        <input
                            name="company[name]"
                            value="{{ old('company.name', $company->name ?? '') }}"
                            class="{{ $inputClass }}"
                            required>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">
                            Email
                        </label>

                        <input
                            type="email"
                            name="company[email]"
                            value="{{ old('company.email', $company->email ?? '') }}"
                            class="{{ $inputClass }}">
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">
                            Phone
                        </label>

                        <input
                            name="company[phone]"
                            value="{{ old('company.phone', $company->phone ?? '') }}"
                            class="{{ $inputClass }}">
                    </div>

                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">
                            Address
                        </label>

                        <textarea
                            name="company[address]"
                            rows="3"
                            class="{{ $inputClass }}">{{ old('company.address', $company->address ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </section>

        {{-- Meta --}}
        <section class="{{ $cardClass }}">
            <div class="{{ $cardHeaderClass }}">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-lg font-extrabold text-white">
                            Meta / Lead Forms
                        </h2>
                        <p class="mt-1 text-sm font-medium text-slate-500">
                            Facebook page, lead form, and optional manual overrides.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        @if($connectedMeta)
                            <span class="{{ $badgeClass }} bg-green-500/10 text-green-300 ring-1 ring-green-400/20">
                                Connected
                            </span>

                            @if($hasMetaConnect)
                                <a href="{{ route('admin.meta.connect') }}"
                                   class="inline-flex items-center rounded-xl bg-slate-800 px-3 py-2 text-xs font-extrabold text-slate-200 transition hover:bg-slate-700">
                                    Change Page
                                </a>
                            @endif

                            @if($hasMetaRefresh)
                                <button type="submit"
                                        form="metaRefreshForm"
                                        class="inline-flex items-center rounded-xl border border-white/10 px-3 py-2 text-xs font-extrabold text-slate-300 transition hover:border-orange-400/30 hover:text-white">
                                    Refresh Forms
                                </button>
                            @endif

                            @if($hasMetaDisconnect)
                                <button type="submit"
                                        form="metaDisconnectForm"
                                        onclick="return confirm('Disconnect this Meta page?')"
                                        class="inline-flex items-center rounded-xl border border-red-400/20 px-3 py-2 text-xs font-extrabold text-red-300 transition hover:bg-red-500/10">
                                    Disconnect
                                </button>
                            @endif
                        @else
                            @if($hasMetaConnect)
                                <a href="{{ route('admin.meta.connect') }}"
                                   class="inline-flex items-center rounded-xl bg-orange-500 px-4 py-2 text-xs font-extrabold text-white transition hover:bg-orange-600">
                                    Connect Facebook Page
                                </a>
                            @else
                                <span class="{{ $badgeClass }} bg-slate-700/60 text-slate-400 ring-1 ring-white/10">
                                    Meta route unavailable
                                </span>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            <div class="{{ $cardBodyClass }} space-y-5">
                @if($connectedMeta)
                    <div class="rounded-2xl border border-white/10 bg-slate-950/55 px-4 py-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <div class="text-sm font-extrabold text-white">
                                    {{ $connectedMeta->page_name }}
                                </div>
                                <div class="mt-1 text-xs font-semibold text-slate-500">
                                    Page ID: {{ $connectedMeta->page_id }}
                                </div>
                            </div>

                            <div class="text-xs font-semibold text-slate-500">
                                Updated: {{ optional($connectedMeta->updated_at)->format('Y-m-d H:i') ?? '—' }}
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">
                            Default Lead Form
                        </label>

                        <select name="meta[form_id]" class="{{ $inputClass }}">
                            <option value="">— Select a form —</option>

                            @foreach($forms as $f)
                                <option value="{{ $f['id'] ?? '' }}" @selected(($f['id'] ?? '') === $defaultFormId)>
                                    {{ ($f['name'] ?? 'Untitled') . ' (' . ($f['id'] ?? '–') . ')' }}
                                </option>
                            @endforeach
                        </select>

                        <p class="mt-2 text-xs font-medium text-slate-500">
                            Used by default for imports/webhooks; can be changed anytime.
                        </p>
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}">
                            App ID
                        </label>

                        <input
                            name="meta[app_id]"
                            value="{{ old('meta.app_id', $settings['meta.app_id'] ?? '') }}"
                            class="{{ $inputClass }}"
                            placeholder="Optional">
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">
                            Page ID
                        </label>

                        <input
                            name="meta[page_id]"
                            value="{{ old('meta.page_id', $settings['meta.page_id'] ?? ($connectedMeta->page_id ?? '')) }}"
                            class="{{ $inputClass }}"
                            placeholder="Optional">
                    </div>

                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">
                            Access Token
                        </label>

                        <div class="relative">
                            <input
                                type="password"
                                id="meta_access_token"
                                name="meta[access_token]"
                                value="{{ old('meta.access_token', $settings['meta.access_token'] ?? ($connectedMeta->page_access_token ?? '')) }}"
                                class="{{ $inputClass }} pr-24"
                                placeholder="EAAB..."
                                autocomplete="off">

                            <button type="button"
                                    data-toggle-visibility="#meta_access_token"
                                    class="absolute right-2 top-2 inline-flex rounded-lg border border-white/10 bg-slate-900 px-3 py-1.5 text-xs font-extrabold text-slate-300 transition hover:text-white">
                                <span class="show">Show</span>
                                <span class="hide hidden">Hide</span>
                            </button>
                        </div>

                        <p class="mt-2 text-xs font-medium text-slate-500">
                            Not required if you’ve connected a Page above.
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">
                            Additional Form IDs
                        </label>

                        <input
                            name="meta[form_ids]"
                            value="{{ old('meta.form_ids', $settings['meta.form_ids'] ?? '') }}"
                            class="{{ $inputClass }}"
                            placeholder='["123","456"] or 123,456'>

                        <p class="mt-2 text-xs font-medium text-slate-500">
                            Optional: import from multiple forms; input is normalized.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Twilio / WhatsApp --}}
        <section class="{{ $cardClass }}">
            <div class="{{ $cardHeaderClass }}">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-extrabold text-white">
                            Twilio / WhatsApp
                        </h2>
                        <p class="mt-1 text-sm font-medium text-slate-500">
                            Legacy or fallback WhatsApp messaging settings.
                        </p>
                    </div>

                    <span class="{{ $badgeClass }} bg-green-500/10 text-green-300 ring-1 ring-green-400/20">
                        Messaging
                    </span>
                </div>
            </div>

            <div class="{{ $cardBodyClass }}">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}">
                            Account SID
                        </label>

                        <input
                            name="twilio[account_sid]"
                            value="{{ old('twilio.account_sid', $settings['twilio.account_sid'] ?? '') }}"
                            class="{{ $inputClass }}"
                            placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">
                            Auth Token
                        </label>

                        <div class="relative">
                            <input
                                type="password"
                                id="twilio_auth_token"
                                name="twilio[auth_token]"
                                value="{{ old('twilio.auth_token', $settings['twilio.auth_token'] ?? '') }}"
                                class="{{ $inputClass }} pr-24"
                                autocomplete="off">

                            <button type="button"
                                    data-toggle-visibility="#twilio_auth_token"
                                    class="absolute right-2 top-2 inline-flex rounded-lg border border-white/10 bg-slate-900 px-3 py-1.5 text-xs font-extrabold text-slate-300 transition hover:text-white">
                                <span class="show">Show</span>
                                <span class="hide hidden">Hide</span>
                            </button>
                        </div>

                        <p class="mt-2 text-xs font-medium text-slate-500">
                            Stored encrypted at rest.
                        </p>
                    </div>

                    <div class="md:col-span-2 md:max-w-md">
                        <label class="{{ $labelClass }}">
                            WhatsApp From
                        </label>

                        <input
                            name="twilio[whatsapp_from]"
                            value="{{ old('twilio.whatsapp_from', $settings['twilio.whatsapp_from'] ?? '') }}"
                            class="{{ $inputClass }}"
                            placeholder="whatsapp:+14155238886">
                    </div>
                </div>

                @if(!empty($webhookUrl))
                    <div class="mt-5 rounded-2xl border border-white/10 bg-slate-950/55 px-4 py-4">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                            Twilio WhatsApp Webhook URL
                        </div>

                        <div class="mt-2 break-all text-sm font-bold text-slate-200">
                            {{ $webhookUrl }}
                        </div>
                    </div>
                @endif
            </div>
        </section>

        {{-- Garage / WhatsApp Handoff --}}
        <section class="{{ $cardClass }}">
            <div class="{{ $cardHeaderClass }}">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-extrabold text-white">
                            Garage / WhatsApp Handoff
                        </h2>
                        <p class="mt-1 text-sm font-medium text-slate-500">
                            Manager handoff, review link, and location link.
                        </p>
                    </div>

                    <span class="{{ $badgeClass }} bg-blue-500/10 text-blue-300 ring-1 ring-blue-400/20">
                        Operations
                    </span>
                </div>
            </div>

            <div class="{{ $cardBodyClass }}">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}">
                            Manager WhatsApp
                        </label>

                        <input
                            name="manager_whatsapp"
                            value="{{ old('manager_whatsapp', $managerWhatsapp ?? '') }}"
                            class="{{ $inputClass }}"
                            placeholder="9715XXXXXXXX">
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">
                            Google Review Link
                        </label>

                        <input
                            name="google_review_link"
                            value="{{ old('google_review_link', $googleReviewLink ?? '') }}"
                            class="{{ $inputClass }}"
                            placeholder="https://g.page/...">
                    </div>

                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">
                            Garage Location Link
                        </label>

                        <input
                            name="garage_location_link"
                            value="{{ old('garage_location_link', $garageLocationLink ?? '') }}"
                            class="{{ $inputClass }}"
                            placeholder="https://maps.app.goo.gl/...">
                    </div>
                </div>
            </div>
        </section>

        {{-- System --}}
        <section class="{{ $cardClass }}">
            <div class="{{ $cardHeaderClass }}">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-extrabold text-white">
                            System
                        </h2>
                        <p class="mt-1 text-sm font-medium text-slate-500">
                            Default timezone, country code, and notification email.
                        </p>
                    </div>

                    <span class="{{ $badgeClass }} bg-slate-700/60 text-slate-300 ring-1 ring-white/10">
                        General
                    </span>
                </div>
            </div>

            <div class="{{ $cardBodyClass }}">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}">
                            Timezone
                        </label>

                        <input
                            name="system[timezone]"
                            value="{{ old('system.timezone', $settings['system.timezone'] ?? 'Asia/Dubai') }}"
                            class="{{ $inputClass }}">
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">
                            Default Country Code
                        </label>

                        <input
                            name="system[default_country_code]"
                            value="{{ old('system.default_country_code', $settings['system.default_country_code'] ?? '+971') }}"
                            class="{{ $inputClass }}">
                    </div>

                    <div class="md:col-span-2 md:max-w-lg">
                        <label class="{{ $labelClass }}">
                            Notification Email
                        </label>

                        <input
                            type="email"
                            name="system[notification_email]"
                            value="{{ old('system.notification_email', $settings['system.notification_email'] ?? '') }}"
                            class="{{ $inputClass }}"
                            placeholder="ops@yourgarage.com">
                    </div>
                </div>
            </div>
        </section>

        {{-- Save --}}
        <div class="flex justify-end">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-6 py-3 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
                Save All
            </button>
        </div>
    </form>

    {{-- External Meta Forms --}}
    @if($hasMetaRefresh)
        <form id="metaRefreshForm" action="{{ route('admin.meta.refresh') }}" method="POST" class="hidden">
            @csrf
        </form>
    @endif

    @if($hasMetaDisconnect)
        <form id="metaDisconnectForm" action="{{ route('admin.meta.disconnect') }}" method="POST" class="hidden">
            @csrf
        </form>
    @endif

    {{-- Tips --}}
    <div class="mt-8 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-3xl border border-white/10 bg-slate-900/80 shadow-xl shadow-black/20">
            <div class="border-b border-white/10 px-6 py-4">
                <h3 class="text-lg font-extrabold text-white">
                    Tips
                </h3>
            </div>

            <div class="px-6 py-5 text-sm font-medium leading-7 text-slate-400">
                <ul class="list-disc space-y-1 pl-5">
                    <li>Connect Facebook Page to fetch Pages and Lead Forms.</li>
                    <li>Select a default form for imports and webhooks.</li>
                    <li>Manual Meta fields act as fallback overrides.</li>
                    <li>Twilio settings are legacy/fallback if testing Twilio WhatsApp.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('[data-toggle-visibility]').forEach(btn => {
        btn.addEventListener('click', () => {
            const sel = btn.getAttribute('data-toggle-visibility');
            const input = document.querySelector(sel);

            if (!input) return;

            const show = btn.querySelector('.show');
            const hide = btn.querySelector('.hide');
            const toType = input.type === 'password' ? 'text' : 'password';

            input.type = toType;

            show.classList.toggle('hidden', toType === 'text');
            hide.classList.toggle('hidden', toType === 'password');
        });
    });
</script>
@endsection
