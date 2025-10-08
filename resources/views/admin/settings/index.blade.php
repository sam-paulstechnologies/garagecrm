@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6">

  {{-- Header + quick test buttons (submit the main form) --}}
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-slate-900">Settings</h1>
    <div class="flex gap-2">
      <button
        type="submit"
        form="settingsForm"
        formaction="{{ route('admin.settings.test.meta.inline') }}"
        formmethod="POST"
        class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
        data-inline-test
      >Test Meta</button>

      <button
        type="submit"
        form="settingsForm"
        formaction="{{ route('admin.settings.test.twilio.inline') }}"
        formmethod="POST"
        class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
        data-inline-test
      >Test Twilio</button>
    </div>
  </div>

  {{-- Alerts --}}
  @if(session('success'))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
      {{ session('success') }}
    </div>
  @endif
  @if(session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
      {{ session('error') }}
    </div>
  @endif
  @if ($errors->any())
    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900">
      <ul class="list-disc pl-5 space-y-1">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- MAIN FORM --}}
  <form id="settingsForm" action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6">
    @csrf
    <input type="hidden" name="_method" id="methodField" value="PUT">

    {{-- Company --}}
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-200 px-5 py-3">
        <div class="flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-800">Company (Tenant profile)</h2>
          <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">Tenant</span>
        </div>
      </div>
      <div class="px-5 py-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Company Name <span class="text-red-500">*</span></label>
            <input name="company[name]" value="{{ old('company.name', $company->name ?? '') }}"
                   class="block w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input type="email" name="company[email]" value="{{ old('company.email', $company->email ?? '') }}"
                   class="block w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
            <input name="company[phone]" value="{{ old('company.phone', $company->phone ?? '') }}"
                   class="block w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
            <textarea name="company[address]" rows="2"
                      class="block w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">{{ old('company.address', $company->address ?? '') }}</textarea>
          </div>
        </div>
      </div>
    </section>

    {{-- Meta (Lead Forms) --}}
    @php
      /** Use connected page if present (safe to query here so the blade is standalone) */
      $connectedMeta = $connectedMeta
        ?? \App\Models\MetaPage::where('company_id', $company->id)->first();
      $forms = $forms
        ?? ($connectedMeta ? (json_decode($connectedMeta->forms_json ?? '[]', true) ?: []) : []);
      $defaultFormId = old('meta.form_id', $settings['meta.form_id'] ?? '');
    @endphp

    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-200 px-5 py-3">
        <div class="flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-800">Meta (Lead Forms)</h2>

          @if($connectedMeta)
            <div class="flex items-center gap-2">
              <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Connected</span>

              <a href="{{ route('admin.meta.connect') }}"
                 class="inline-flex items-center rounded-md bg-slate-700 hover:bg-slate-800 text-white px-3 py-1.5 text-xs">
                 Change Page
              </a>

              <form action="{{ route('admin.meta.refresh') }}" method="POST" class="inline">
                @csrf
                <button class="inline-flex items-center rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                  Refresh Forms
                </button>
              </form>

              <form action="{{ route('admin.meta.disconnect') }}" method="POST" class="inline">
                @csrf
                <button class="inline-flex items-center rounded-md border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">
                  Disconnect
                </button>
              </form>
            </div>
          @else
            <a href="{{ route('admin.meta.connect') }}"
               class="inline-flex items-center rounded-md bg-slate-700 hover:bg-slate-800 text-white px-3 py-1.5 text-xs">
               Connect Facebook Page
            </a>
          @endif
        </div>
      </div>

      <div class="px-5 py-5 space-y-5">
        {{-- Connected page summary + Default form chooser --}}
        @if($connectedMeta)
          <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div>
                <div class="font-medium">{{ $connectedMeta->page_name }}</div>
                <div class="text-xs text-slate-500">Page ID: {{ $connectedMeta->page_id }}</div>
              </div>
              <div class="text-xs text-slate-500">
                Updated: {{ optional($connectedMeta->updated_at)->format('Y-m-d H:i') ?? '—' }}
              </div>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Default Lead Form</label>
            <select name="meta[form_id]"
                    class="block w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
              <option value="">— Select a form —</option>
              @foreach($forms as $f)
                <option value="{{ $f['id'] ?? '' }}" @selected(($f['id'] ?? '') === $defaultFormId)>
                  {{ ($f['name'] ?? 'Untitled').' ('.($f['id'] ?? '–').')' }}
                </option>
              @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-500">Used by default for imports/webhooks; can be changed anytime.</p>
          </div>
        @endif

        {{-- Manual overrides (optional fallback) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">App ID (optional)</label>
            <input name="meta[app_id]" value="{{ old('meta.app_id', $settings['meta.app_id'] ?? '') }}"
                   class="block w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Page ID (optional)</label>
            <input name="meta[page_id]" value="{{ old('meta.page_id', $settings['meta.page_id'] ?? ($connectedMeta->page_id ?? '')) }}"
                   class="block w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 mb-1">Access Token</label>
            <div class="relative">
              <input type="password" id="meta_access_token" name="meta[access_token]"
                     value="{{ old('meta.access_token', $settings['meta.access_token'] ?? ($connectedMeta->page_access_token ?? '')) }}"
                     class="block w-full rounded-lg border-slate-300 pr-24 focus:border-blue-500 focus:ring-blue-500"
                     placeholder="EAAB..." autocomplete="off">
              <button type="button" data-toggle-visibility="#meta_access_token"
                      class="absolute right-1 top-1.5 inline-flex items-center rounded-md border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                <span class="show">Show</span><span class="hide hidden">Hide</span>
              </button>
            </div>
            <p class="mt-1 text-xs text-slate-500">Not required if you’ve connected a Page above.</p>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 mb-1">Additional Form IDs (CSV or JSON)</label>
            <input name="meta[form_ids]" value="{{ old('meta.form_ids', $settings['meta.form_ids'] ?? '') }}"
                   class="block w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                   placeholder='["123","456"] or 123,456'>
            <p class="mt-1 text-xs text-slate-500">Optional: import from multiple forms; we normalize your input.</p>
          </div>
        </div>
      </div>
    </section>

    {{-- Twilio / WhatsApp --}}
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-200 px-5 py-3">
        <div class="flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-800">Twilio / WhatsApp</h2>
          <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Messaging</span>
        </div>
      </div>
      <div class="px-5 py-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Account SID</label>
            <input name="twilio[account_sid]" value="{{ old('twilio.account_sid', $settings['twilio.account_sid'] ?? '') }}"
                   class="block w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                   placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Auth Token</label>
            <div class="relative">
              <input type="password" id="twilio_auth_token" name="twilio[auth_token]"
                     value="{{ old('twilio.auth_token', $settings['twilio.auth_token'] ?? '') }}"
                     class="block w-full rounded-lg border-slate-300 pr-24 focus:border-blue-500 focus:ring-blue-500" autocomplete="off">
              <button type="button" data-toggle-visibility="#twilio_auth_token"
                      class="absolute right-1 top-1.5 inline-flex items-center rounded-md border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                <span class="show">Show</span><span class="hide hidden">Hide</span>
              </button>
            </div>
            <p class="mt-1 text-xs text-slate-500">Stored encrypted at rest.</p>
          </div>
          <div class="md:col-span-2 md:max-w-md">
            <label class="block text-sm font-medium text-slate-700 mb-1">WhatsApp From</label>
            <input name="twilio[whatsapp_from]" value="{{ old('twilio.whatsapp_from', $settings['twilio.whatsapp_from'] ?? '') }}"
                   class="block w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                   placeholder="whatsapp:+14155238886">
          </div>
        </div>
      </div>
    </section>

    {{-- System --}}
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-200 px-5 py-3">
        <div class="flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-800">System</h2>
          <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">General</span>
        </div>
      </div>
      <div class="px-5 py-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Timezone</label>
            <input name="system[timezone]" value="{{ old('system.timezone', $settings['system.timezone'] ?? 'Asia/Dubai') }}"
                   class="block w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Default Country Code</label>
            <input name="system[default_country_code]" value="{{ old('system.default_country_code', $settings['system.default_country_code'] ?? '+971') }}"
                   class="block w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
          </div>
          <div class="md:col-span-2 md:max-w-lg">
            <label class="block text-sm font-medium text-slate-700 mb-1">Notification Email</label>
            <input type="email" name="system[notification_email]" value="{{ old('system.notification_email', $settings['system.notification_email'] ?? '') }}"
                   class="block w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                   placeholder="ops@yourgarage.com">
          </div>
        </div>
      </div>
    </section>

    {{-- Save --}}
    <div class="flex justify-end">
      <button type="submit" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-white shadow hover:bg-blue-700">
        Save All
      </button>
    </div>
  </form>

  {{-- Tips --}}
  <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="px-5 py-3 border-b border-slate-200">
        <h3 class="text-sm font-semibold text-slate-800">Tips</h3>
      </div>
      <div class="px-5 py-4 text-sm text-slate-600">
        <ul class="list-disc pl-5 space-y-1">
          <li>Click <em>Connect Facebook Page</em> to fetch your Pages and Lead Forms.</li>
          <li>Select a default form; you can still import from others when needed.</li>
          <li>Manual fields act as overrides if you don’t want to connect via OAuth.</li>
        </ul>
      </div>
    </div>
  </div>
</div>

{{-- Small JS helpers --}}
<script>
  // Toggle secret visibility
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

  // Make inline test submits POST (disable the hidden _method=PUT temporarily)
  const methodField = document.getElementById('methodField');
  document.querySelectorAll('button[data-inline-test]').forEach(btn => {
    btn.addEventListener('click', () => {
      if (methodField) methodField.disabled = true;
      setTimeout(() => { if (methodField) methodField.disabled = false; }, 0);
    });
  });
</script>
@endsection
