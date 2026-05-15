@extends('layouts.app')

@section('title', 'WhatsApp Lead Intake')

@section('content')
@php
    $cardClass = 'rounded-3xl border border-white/10 bg-slate-900/80 shadow-xl shadow-black/20 overflow-hidden';
    $cardHeaderClass = 'border-b border-white/10 px-6 py-4 bg-slate-950/35';
    $cardBodyClass = 'px-6 py-6';
    $labelClass = 'block text-xs font-extrabold uppercase tracking-wide text-slate-400 mb-1.5';
    $inputClass = 'block w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm font-semibold text-white placeholder:text-slate-600 outline-none';
@endphp

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-orange-300">
                WhatsApp Lead Source
            </div>

            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">
                WhatsApp Lead Intake
            </h1>

            <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-slate-400">
                Review WhatsApp numbers, webhook URL, manager handoff, review link, and lead intake configuration.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.lead-sources.index') }}"
               class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                Back to Lead Sources
            </a>

            @if(\Illuminate\Support\Facades\Route::has('admin.whatsapp.settings.edit'))
                <a href="{{ route('admin.whatsapp.settings.edit') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
                    Manage WhatsApp Settings
                </a>
            @endif
        </div>
    </div>

    {{-- Info --}}
    <div class="mb-6 rounded-3xl border border-green-400/20 bg-green-500/10 px-6 py-5">
        <p class="text-sm font-extrabold text-green-200">
            WhatsApp-first lead capture
        </p>

        <p class="mt-2 text-sm font-medium leading-6 text-green-100/80">
            Customer messages coming from WhatsApp can become leads, open conversations, trigger manager handoff, and continue into booking and service journeys.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- LEFT --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- WhatsApp Details --}}
            <section class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-extrabold text-white">
                                WhatsApp Configuration
                            </h2>

                            <p class="mt-1 text-sm font-medium text-slate-500">
                                Current numbers and links used by the WhatsApp journey.
                            </p>
                        </div>

                        <span class="rounded-full bg-green-500/10 px-2.5 py-0.5 text-xs font-extrabold text-green-300 ring-1 ring-green-400/20">
                            Intake
                        </span>
                    </div>
                </div>

                <div class="{{ $cardBodyClass }} space-y-5">

                    {{-- Garage WhatsApp --}}
                    <div>
                        <label class="{{ $labelClass }}">
                            Garage WhatsApp
                        </label>

                        <input class="{{ $inputClass }}"
                               readonly
                               value="{{ $waFrom }}">

                        <p class="mt-2 text-xs font-medium text-slate-500">
                            This is the configured outgoing WhatsApp number.
                        </p>
                    </div>

                    {{-- Manager WhatsApp --}}
                    <div>
                        <label class="{{ $labelClass }}">
                            Manager WhatsApp
                        </label>

                        <input class="{{ $inputClass }}"
                               readonly
                               value="{{ $managerWhatsapp }}">

                        <p class="mt-2 text-xs font-medium text-slate-500">
                            Manager receives handoff and escalation alerts.
                        </p>
                    </div>

                    {{-- Google Review Link --}}
                    <div>
                        <label class="{{ $labelClass }}">
                            Google Review Link
                        </label>

                        <input class="{{ $inputClass }}"
                               readonly
                               value="{{ $googleReviewLink }}">

                        <p class="mt-2 text-xs font-medium text-slate-500">
                            Used after positive feedback to request reviews.
                        </p>
                    </div>

                    @if(\Illuminate\Support\Facades\Route::has('admin.whatsapp.settings.edit'))
                        <div class="border-t border-white/10 pt-5">
                            <a href="{{ route('admin.whatsapp.settings.edit') }}"
                               class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
                                Manage WhatsApp Settings
                            </a>
                        </div>
                    @endif

                </div>
            </section>

            {{-- Journey Flow --}}
            <section class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <h2 class="text-lg font-extrabold text-white">
                        WhatsApp Intake Journey
                    </h2>

                    <p class="mt-1 text-sm font-medium text-slate-500">
                        Expected path for inbound WhatsApp enquiries.
                    </p>
                </div>

                <div class="{{ $cardBodyClass }}">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                            <p class="text-sm font-extrabold text-white">1. Customer sends message</p>
                            <p class="mt-2 text-xs font-medium leading-5 text-slate-500">
                                Inbound WhatsApp message is received through webhook.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                            <p class="text-sm font-extrabold text-white">2. Lead / client resolved</p>
                            <p class="mt-2 text-xs font-medium leading-5 text-slate-500">
                                Phone number is used for dedupe and source tagging.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                            <p class="text-sm font-extrabold text-white">3. Conversation starts</p>
                            <p class="mt-2 text-xs font-medium leading-5 text-slate-500">
                                Admin can review messages in the inbox.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                            <p class="text-sm font-extrabold text-white">4. Booking journey</p>
                            <p class="mt-2 text-xs font-medium leading-5 text-slate-500">
                                Service enquiry can continue into booking, job, invoice, and feedback.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

        </div>

        {{-- RIGHT --}}
        <aside class="space-y-6">

            {{-- Webhook URL --}}
            <section class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-extrabold text-white">
                            Webhook URL
                        </h2>

                        <span class="rounded-full bg-blue-500/10 px-2.5 py-0.5 text-xs font-extrabold text-blue-300 ring-1 ring-blue-400/20">
                            Callback
                        </span>
                    </div>
                </div>

                <div class="{{ $cardBodyClass }}">
                    <label class="{{ $labelClass }}">
                        Endpoint
                    </label>

                    <textarea id="webhookUrlInput"
                              readonly
                              class="block min-h-[120px] w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-xs font-semibold leading-6 text-white outline-none">{{ $webhookUrl }}</textarea>

                    <button type="button"
                            onclick="copyWebhookUrl()"
                            class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-orange-500 px-4 py-3 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
                        Copy Webhook URL
                    </button>
                </div>
            </section>

            {{-- How this is used --}}
            <section class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <h2 class="text-lg font-extrabold text-white">
                        How this is used
                    </h2>
                </div>

                <div class="{{ $cardBodyClass }}">
                    <ul class="list-disc list-inside space-y-3 text-sm font-medium leading-6 text-slate-400">
                        <li>Receives inbound WhatsApp messages.</li>
                        <li>Creates or matches leads using phone number.</li>
                        <li>Routes messages to the inbox.</li>
                        <li>Supports manager handoff and follow-up journeys.</li>
                        <li>Feeds WhatsApp conversation history.</li>
                    </ul>
                </div>
            </section>

            {{-- Testing --}}
            <section class="rounded-3xl border border-orange-400/20 bg-orange-500/10 p-6 shadow-xl shadow-black/20">
                <h2 class="text-lg font-extrabold text-orange-200">
                    Testing Tip
                </h2>

                <p class="mt-3 text-sm font-medium leading-6 text-orange-100/80">
                    After webhook setup, send “hi” from a test phone number, check logs, then confirm if a conversation and lead are created.
                </p>
            </section>

        </aside>
    </div>
</div>

<script>
    function copyWebhookUrl() {
        const el = document.getElementById('webhookUrlInput');

        if (!el) {
            return;
        }

        el.select();
        el.setSelectionRange(0, 99999);

        navigator.clipboard.writeText(el.value).then(function () {
            alert('Webhook URL copied');
        }).catch(function () {
            document.execCommand('copy');
            alert('Webhook URL copied');
        });
    }
</script>
@endsection