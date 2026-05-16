@extends('layouts.app')

@section('title', 'Google Ads Lead Capture')

@section('content')
@php
    $panelClass = 'rounded-3xl border border-white/10 bg-slate-900/80 shadow-xl shadow-black/20';
    $inputClass = 'w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm font-semibold text-white placeholder:text-slate-500 focus:border-orange-400/50 focus:outline-none focus:ring-2 focus:ring-orange-500/20';
    $labelClass = 'mb-2 block text-xs font-extrabold uppercase tracking-wide text-slate-400';
@endphp

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-orange-300">
                Google Lead Source
            </div>

            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">
                Google Ads Lead Capture
            </h1>

            <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-slate-400">
                Create webhook keys for Google Ads Lead Form Assets and route submitted leads into SayaraForce.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.lead-sources.index') }}"
               class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                Back to Lead Sources
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="mb-6 rounded-3xl border border-green-400/20 bg-green-500/10 px-6 py-4 text-sm font-extrabold text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 rounded-3xl border border-red-400/20 bg-red-500/10 px-6 py-4 text-sm font-extrabold text-red-200">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-3xl border border-red-400/20 bg-red-500/10 px-6 py-4 text-sm text-red-100">
            <div class="font-extrabold">Please fix the below:</div>
            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Setup Values --}}
    <div class="{{ $panelClass }} mb-6 overflow-hidden">
        <div class="border-b border-white/10 bg-orange-500/5 px-6 py-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-black text-white">
                        Google Ads setup values
                    </h2>
                    <p class="mt-1 text-sm font-medium text-slate-400">
                        Copy these values into the Google Ads Lead Form Asset webhook section.
                    </p>
                </div>

                <span class="text-3xl">🔎</span>
            </div>
        </div>

        <div class="px-6 py-6">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="{{ $labelClass }}">
                        Webhook URL
                    </label>

                    <input type="text"
                           readonly
                           value="{{ $webhookUrl }}"
                           class="{{ $inputClass }}">

                    <p class="mt-2 text-xs font-semibold text-slate-500">
                        Paste this into Google Ads Lead Form Asset webhook URL.
                    </p>
                </div>

                <div>
                    <label class="{{ $labelClass }}">
                        Webhook Key
                    </label>

                    <input type="text"
                           readonly
                           value="Use the key from the source table below"
                           class="{{ $inputClass }}">

                    <p class="mt-2 text-xs font-semibold text-slate-500">
                        Each garage/source gets its own unique key.
                    </p>
                </div>
            </div>

            <div class="mt-5 rounded-2xl border border-blue-400/20 bg-blue-500/10 px-5 py-4">
                <p class="text-sm font-extrabold text-blue-200">
                    Google Ads path
                </p>

                <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
                    Google Ads → Campaign/Assets → Lead form asset → Export leads from Google Ads → Webhook URL + Key → Send test data.
                </p>
            </div>
        </div>
    </div>

    {{-- Create Source --}}
    <div class="{{ $panelClass }} mb-6 overflow-hidden">
        <div class="border-b border-white/10 px-6 py-4">
            <h2 class="text-lg font-black text-white">
                Create Google Lead Source
            </h2>

            <p class="mt-1 text-sm font-medium text-slate-400">
                Create a webhook key for a garage, campaign, or specific Google lead form.
            </p>
        </div>

        <form method="POST" action="{{ route('admin.lead-sources.google.store') }}" class="px-6 py-6">
            @csrf

            <div class="grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="{{ $labelClass }}">
                        Source Name <span class="text-orange-300">*</span>
                    </label>

                    <input type="text"
                           name="name"
                           value="{{ old('name', 'Google Ads - Lead Form') }}"
                           required
                           class="{{ $inputClass }}">
                </div>

                <div>
                    <label class="{{ $labelClass }}">
                        Google Form ID
                    </label>

                    <input type="text"
                           name="form_id"
                           value="{{ old('form_id') }}"
                           placeholder="Optional"
                           class="{{ $inputClass }}">

                    <p class="mt-2 text-xs font-semibold text-slate-500">
                        Leave blank if this key can accept any Google form.
                    </p>
                </div>

                <div>
                    <label class="{{ $labelClass }}">
                        Campaign ID
                    </label>

                    <input type="text"
                           name="campaign_id"
                           value="{{ old('campaign_id') }}"
                           placeholder="Optional"
                           class="{{ $inputClass }}">
                </div>

                <div>
                    <label class="{{ $labelClass }}">
                        Campaign Name
                    </label>

                    <input type="text"
                           name="campaign_name"
                           value="{{ old('campaign_name') }}"
                           placeholder="Optional"
                           class="{{ $inputClass }}">
                </div>

                <div>
                    <label class="{{ $labelClass }}">
                        Description
                    </label>

                    <input type="text"
                           name="description"
                           value="{{ old('description') }}"
                           placeholder="Example: Car AC Repair Dubai lead form"
                           class="{{ $inputClass }}">
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-orange-600 px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-700">
                    Create Google Source
                </button>

                <p class="text-xs font-semibold text-slate-500">
                    After creation, copy the key into Google Ads and click Send test data.
                </p>
            </div>
        </form>
    </div>

    {{-- Existing Sources --}}
    <div class="{{ $panelClass }} overflow-hidden">
        <div class="border-b border-white/10 px-6 py-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-black text-white">
                        Google Lead Sources
                    </h2>

                    <p class="mt-1 text-sm font-medium text-slate-400">
                        Use the webhook key from here inside Google Ads.
                    </p>
                </div>

                <span class="inline-flex rounded-full bg-orange-500/10 px-3 py-1 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                    {{ $sources->count() }} {{ Str::plural('Source', $sources->count()) }}
                </span>
            </div>
        </div>

        @if($sources->isEmpty())
            <div class="px-6 py-10 text-center">
                <div class="text-4xl">🔎</div>
                <p class="mt-3 text-sm font-extrabold text-white">
                    No Google lead sources created yet.
                </p>
                <p class="mt-1 text-sm font-medium text-slate-500">
                    Create a source above to generate your first Google webhook key.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/10">
                    <thead class="bg-slate-950/60">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">Source</th>
                            <th class="px-6 py-4 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">Webhook Key</th>
                            <th class="px-6 py-4 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">Config</th>
                            <th class="px-6 py-4 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-extrabold uppercase tracking-wide text-slate-500">Last Lead</th>
                            <th class="px-6 py-4 text-right text-xs font-extrabold uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-white/10">
                        @foreach($sources as $source)
                            @php
                                $config = $source->config ?? [];
                                $formId = data_get($config, 'form_id');
                                $campaignName = data_get($config, 'campaign_name');
                                $campaignId = data_get($config, 'campaign_id');
                                $isActive = in_array($source->status, ['active', 'connected'], true);
                            @endphp

                            <tr class="bg-slate-900/40">
                                <td class="px-6 py-5 align-top">
                                    <div class="font-black text-white">
                                        {{ $source->name }}
                                    </div>

                                    <div class="mt-1 text-xs font-semibold text-slate-500">
                                        ID: {{ $source->id }}
                                    </div>
                                </td>

                                <td class="px-6 py-5 align-top">
                                    <input type="text"
                                           readonly
                                           value="{{ $source->form_token }}"
                                           class="w-80 rounded-xl border border-white/10 bg-slate-950/70 px-4 py-2.5 text-xs font-semibold text-slate-200">
                                </td>

                                <td class="px-6 py-5 align-top">
                                    <div class="text-sm font-semibold text-slate-300">
                                        Form: <span class="text-white">{{ $formId ?: 'Any' }}</span>
                                    </div>

                                    <div class="mt-1 text-sm font-semibold text-slate-300">
                                        Campaign:
                                        <span class="text-white">
                                            {{ $campaignName ?: ($campaignId ?: 'Not set') }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-5 align-top">
                                    @if($isActive)
                                        <span class="inline-flex rounded-full bg-green-500/10 px-3 py-1 text-xs font-extrabold text-green-300 ring-1 ring-green-400/20">
                                            {{ ucfirst($source->status) }}
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-500/10 px-3 py-1 text-xs font-extrabold text-slate-300 ring-1 ring-slate-400/20">
                                            {{ ucfirst($source->status) }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-5 align-top text-sm font-semibold text-slate-300">
                                    {{ $source->last_received_at ? $source->last_received_at->format('d M Y, h:i A') : 'No leads yet' }}
                                </td>

                                <td class="px-6 py-5 align-top">
                                    <div class="flex justify-end gap-2">
                                        <form method="POST"
                                              action="{{ route('admin.lead-sources.google.rotate', $source) }}"
                                              onsubmit="return confirm('Rotate webhook key? You must update the new key in Google Ads.');">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit"
                                                    class="rounded-xl border border-orange-400/20 bg-orange-500/10 px-3 py-2 text-xs font-extrabold text-orange-300 transition hover:bg-orange-500/20">
                                                Rotate Key
                                            </button>
                                        </form>

                                        @if($isActive)
                                            <form method="POST" action="{{ route('admin.lead-sources.google.deactivate', $source) }}">
                                                @csrf
                                                @method('PATCH')

                                                <button type="submit"
                                                        class="rounded-xl border border-red-400/20 bg-red-500/10 px-3 py-2 text-xs font-extrabold text-red-300 transition hover:bg-red-500/20">
                                                    Deactivate
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.lead-sources.google.activate', $source) }}">
                                                @csrf
                                                @method('PATCH')

                                                <button type="submit"
                                                        class="rounded-xl border border-green-400/20 bg-green-500/10 px-3 py-2 text-xs font-extrabold text-green-300 transition hover:bg-green-500/20">
                                                    Activate
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Bottom Note --}}
    <div class="mt-8 rounded-3xl border border-orange-400/20 bg-orange-500/10 px-6 py-5">
        <p class="text-sm font-extrabold text-orange-200">
            Google lead capture status
        </p>

        <p class="mt-2 text-sm font-medium leading-6 text-orange-100/80">
            Backend webhook is ready. Once Google Ads sends a test lead, SayaraForce will validate the webhook key, create/update the lead, log the request, and let the existing LeadCreated flow handle follow-up.
        </p>
    </div>
</div>
@endsection