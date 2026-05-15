@extends('layouts.app')

@section('title', $leadSource->config['form_name'] ?? $leadSource->name ?? 'Website Form')

@section('content')
@php
    $cardClass = 'rounded-3xl border border-white/10 bg-slate-900/80 shadow-xl shadow-black/20 overflow-hidden';
    $cardHeaderClass = 'border-b border-white/10 px-6 py-4 bg-slate-950/35';
    $cardBodyClass = 'px-6 py-6';
    $labelClass = 'block text-xs font-extrabold uppercase tracking-wide text-slate-400 mb-1.5';
    $inputClass = 'block w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm font-semibold text-white placeholder:text-slate-600 outline-none';
    $textareaClass = $inputClass . ' min-h-[160px] font-mono text-xs leading-6';
@endphp

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-orange-300">
                Website Form
            </div>

            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">
                {{ $leadSource->config['form_name'] ?? $leadSource->name }}
            </h1>

            <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-slate-400">
                Copy the API endpoint or embed snippet to capture website leads directly into SayaraForce.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.lead-sources.website.index') }}"
               class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                Back to Forms
            </a>

            <a href="{{ route('admin.lead-sources.index') }}"
               class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                Lead Sources
            </a>
        </div>
    </div>

    {{-- Info --}}
    <div class="mb-6 rounded-3xl border border-blue-400/20 bg-blue-500/10 px-6 py-5">
        <p class="text-sm font-extrabold text-blue-200">
            Website embed setup
        </p>

        <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
            The API endpoint is public and accepts both form-data and JSON. Use the embed snippet to place this form on your website.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- LEFT --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- API Endpoint --}}
            <section class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-extrabold text-white">
                                API Endpoint
                            </h2>

                            <p class="mt-1 text-sm font-medium text-slate-500">
                                Public endpoint for this website form.
                            </p>
                        </div>

                        <span class="rounded-full bg-blue-500/10 px-2.5 py-0.5 text-xs font-extrabold text-blue-300 ring-1 ring-blue-400/20">
                            Public
                        </span>
                    </div>
                </div>

                <div class="{{ $cardBodyClass }}">
                    <label class="{{ $labelClass }}">
                        Endpoint URL
                    </label>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <input id="formUrlInput"
                               class="{{ $inputClass }}"
                               readonly
                               value="{{ $formUrl }}">

                        <button type="button"
                                onclick="copyToClipboard('formUrlInput', 'API endpoint copied')"
                                class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
                            Copy
                        </button>
                    </div>

                    <p class="mt-2 text-xs font-medium text-slate-500">
                        Public • No CSRF • Accepts form-data and JSON
                    </p>
                </div>
            </section>

            {{-- Embed Snippet --}}
            <section class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-extrabold text-white">
                                Embed Snippet
                            </h2>

                            <p class="mt-1 text-sm font-medium text-slate-500">
                                Paste this snippet into your website page.
                            </p>
                        </div>

                        <span class="rounded-full bg-green-500/10 px-2.5 py-0.5 text-xs font-extrabold text-green-300 ring-1 ring-green-400/20">
                            HTML
                        </span>
                    </div>
                </div>

                <div class="{{ $cardBodyClass }}">
                    <label class="{{ $labelClass }}">
                        Embed Code
                    </label>

                    <textarea id="embedSnippetInput"
                              class="{{ $textareaClass }}"
                              rows="7"
                              readonly>{{ $embed }}</textarea>

                    <div class="mt-4 flex justify-end">
                        <button type="button"
                                onclick="copyToClipboard('embedSnippetInput', 'Embed code copied')"
                                class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
                            Copy Embed Code
                        </button>
                    </div>
                </div>
            </section>

            {{-- Live Preview --}}
            <section class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <div>
                        <h2 class="text-lg font-extrabold text-white">
                            Live Preview
                        </h2>

                        <p class="mt-1 text-sm font-medium text-slate-500">
                            Preview of the form as embedded on a website.
                        </p>
                    </div>
                </div>

                <div class="{{ $cardBodyClass }}">
                    <div class="overflow-hidden rounded-2xl border border-white/10 bg-white p-4 text-slate-900">
                        {!! $embed !!}
                    </div>
                </div>
            </section>

        </div>

        {{-- RIGHT --}}
        <aside class="space-y-6">

            {{-- How this works --}}
            <section class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <h2 class="text-lg font-extrabold text-white">
                        How this works
                    </h2>
                </div>

                <div class="{{ $cardBodyClass }}">
                    <ul class="list-disc list-inside space-y-3 text-sm font-medium leading-6 text-slate-400">
                        <li>Customer submits the embedded form.</li>
                        <li>Lead is created in SayaraForce.</li>
                        <li>Phone/email can be used for deduplication.</li>
                        <li>Source is tagged as website.</li>
                        <li>Manager can continue follow-up from CRM or WhatsApp.</li>
                    </ul>
                </div>
            </section>

            {{-- Recommended Use --}}
            <section class="rounded-3xl border border-orange-400/20 bg-orange-500/10 p-6 shadow-xl shadow-black/20">
                <h2 class="text-lg font-extrabold text-orange-200">
                    Recommended Use
                </h2>

                <p class="mt-3 text-sm font-medium leading-6 text-orange-100/80">
                    Use separate forms for different landing pages or service campaigns, so source-level reporting stays clean.
                </p>
            </section>

            {{-- Quick Checks --}}
            <section class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <h2 class="text-lg font-extrabold text-white">
                        Quick Checks
                    </h2>
                </div>

                <div class="{{ $cardBodyClass }}">
                    <div class="space-y-3 text-sm font-semibold text-slate-400">
                        <div class="flex items-center justify-between gap-3">
                            <span>Endpoint available</span>
                            <span class="rounded-full bg-green-500/10 px-2.5 py-1 text-xs font-extrabold text-green-300 ring-1 ring-green-400/20">
                                Ready
                            </span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span>Embed snippet</span>
                            <span class="rounded-full bg-green-500/10 px-2.5 py-1 text-xs font-extrabold text-green-300 ring-1 ring-green-400/20">
                                Ready
                            </span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span>CSRF required</span>
                            <span class="rounded-full bg-blue-500/10 px-2.5 py-1 text-xs font-extrabold text-blue-300 ring-1 ring-blue-400/20">
                                No
                            </span>
                        </div>
                    </div>
                </div>
            </section>

        </aside>
    </div>
</div>

<script>
    function copyToClipboard(id, message) {
        const el = document.getElementById(id);

        if (!el) {
            return;
        }

        el.select();
        el.setSelectionRange(0, 99999);

        navigator.clipboard.writeText(el.value).then(function () {
            alert(message || 'Copied to clipboard');
        }).catch(function () {
            document.execCommand('copy');
            alert(message || 'Copied to clipboard');
        });
    }
</script>
@endsection