@extends('layouts.app')

@section('title', $leadSource->name ?? 'Lead Source')

@section('content')
@php
    $cardClass = 'rounded-3xl border border-white/10 bg-slate-900/80 shadow-xl shadow-black/20 overflow-hidden';
    $cardHeaderClass = 'border-b border-white/10 px-6 py-4 bg-slate-950/35';
    $cardBodyClass = 'px-6 py-6';
    $labelClass = 'block text-xs font-extrabold uppercase tracking-wide text-slate-400 mb-1.5';
    $inputClass = 'block w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm font-semibold text-white placeholder:text-slate-600 outline-none';
    $textareaClass = $inputClass . ' min-h-[130px]';
@endphp

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-orange-300">
                Lead Source
            </div>

            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">
                {{ $leadSource->name }}
            </h1>

            <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-slate-400">
                Hosted form URL, embed snippet, and live preview for this lead source.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(\Illuminate\Support\Facades\Route::has('admin.lead-sources.index'))
                <a href="{{ route('admin.lead-sources.index') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                    Back to Lead Sources
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- LEFT --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- Hosted URL --}}
            <section class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-extrabold text-white">
                                Hosted Form URL
                            </h2>

                            <p class="mt-1 text-sm font-medium text-slate-500">
                                Share this link directly with customers or use it in campaigns.
                            </p>
                        </div>

                        <span class="rounded-full bg-blue-500/10 px-2.5 py-0.5 text-xs font-extrabold text-blue-300 ring-1 ring-blue-400/20">
                            Link
                        </span>
                    </div>
                </div>

                <div class="{{ $cardBodyClass }}">
                    <label class="{{ $labelClass }}">
                        URL
                    </label>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <input id="formUrlInput"
                               class="{{ $inputClass }}"
                               readonly
                               value="{{ $formUrl }}">

                        <button type="button"
                                onclick="copyToClipboard('formUrlInput')"
                                class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
                            Copy
                        </button>
                    </div>
                </div>
            </section>

            {{-- Embed --}}
            <section class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-extrabold text-white">
                                Embed Snippet
                            </h2>

                            <p class="mt-1 text-sm font-medium text-slate-500">
                                Add this snippet to your website to display the form.
                            </p>
                        </div>

                        <span class="rounded-full bg-green-500/10 px-2.5 py-0.5 text-xs font-extrabold text-green-300 ring-1 ring-green-400/20">
                            Embed
                        </span>
                    </div>
                </div>

                <div class="{{ $cardBodyClass }}">
                    <label class="{{ $labelClass }}">
                        Snippet
                    </label>

                    <textarea id="embedSnippetInput"
                              class="{{ $textareaClass }}"
                              readonly>{{ $embed }}</textarea>

                    <div class="mt-4 flex justify-end">
                        <button type="button"
                                onclick="copyToClipboard('embedSnippetInput')"
                                class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
                            Copy Embed Code
                        </button>
                    </div>
                </div>
            </section>
        </div>

        {{-- RIGHT --}}
        <aside class="space-y-6">

            {{-- Info --}}
            <section class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <h2 class="text-lg font-extrabold text-white">
                        How this works
                    </h2>
                </div>

                <div class="{{ $cardBodyClass }}">
                    <ul class="list-disc list-inside space-y-3 text-sm font-medium leading-6 text-slate-400">
                        <li>Customer submits the hosted form.</li>
                        <li>Lead is captured into SayaraForce.</li>
                        <li>Duplicate phone numbers can be mapped to existing clients.</li>
                        <li>Lead source helps track channel performance.</li>
                    </ul>
                </div>
            </section>

            {{-- Preview --}}
            <section class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <h2 class="text-lg font-extrabold text-white">
                        Preview
                    </h2>

                    <p class="mt-1 text-sm font-medium text-slate-500">
                        Live embedded form preview.
                    </p>
                </div>

                <div class="{{ $cardBodyClass }}">
                    <div class="overflow-hidden rounded-2xl border border-white/10 bg-white p-4 text-slate-900">
                        {!! $embed !!}
                    </div>
                </div>
            </section>

        </aside>
    </div>
</div>

<script>
    function copyToClipboard(id) {
        const el = document.getElementById(id);

        if (!el) {
            return;
        }

        el.select();
        el.setSelectionRange(0, 99999);

        navigator.clipboard.writeText(el.value).then(function () {
            alert('Copied to clipboard');
        }).catch(function () {
            document.execCommand('copy');
            alert('Copied to clipboard');
        });
    }
</script>
@endsection