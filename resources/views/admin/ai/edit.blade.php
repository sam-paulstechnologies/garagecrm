@extends('layouts.app')

@section('content')
@php
    $cardClass = 'rounded-3xl border border-white/10 bg-slate-900/80 shadow-xl shadow-black/20 overflow-hidden';
    $cardHeaderClass = 'border-b border-white/10 px-6 py-4 bg-slate-950/35';
    $cardBodyClass = 'px-6 py-6';
    $labelClass = 'block text-xs font-extrabold uppercase tracking-wide text-slate-400 mb-1.5';
    $inputClass = 'block w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm font-semibold text-white placeholder:text-slate-600 outline-none transition focus:border-orange-400/50 focus:ring-2 focus:ring-orange-500/10';
    $textareaClass = $inputClass . ' min-h-[110px]';
@endphp

@include('admin.ai.edit-partials._styles')

<div class="sf-ai-control-page mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-orange-300">
                AI Control
            </div>

            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">
                AI Control Center
            </h1>

            <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-400">
                Control when AI replies, what it can handle, when it should hand off, and the safety rules for customer conversations.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(\Illuminate\Support\Facades\Route::has('admin.settings.index'))
                <a href="{{ route('admin.settings.index') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                    Integration Settings
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.whatsapp.settings.edit'))
                <a href="{{ route('admin.whatsapp.settings.edit') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                    WhatsApp Settings
                </a>
            @endif
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="mb-5 rounded-2xl border border-green-400/20 bg-green-500/10 px-4 py-3 text-sm font-bold text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-5 rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-sm text-red-300">
            <div class="font-extrabold text-red-200">Please fix the following:</div>
            <ul class="mt-2 list-disc space-y-1 pl-5 font-semibold">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.ai.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- AI Availability --}}
        <section class="{{ $cardClass }}">
            <div class="{{ $cardHeaderClass }}">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-extrabold text-white">
                            AI Availability
                        </h2>
                        <p class="mt-1 text-sm font-medium text-slate-500">
                            Decide whether AI is active and whether it can send the first reply.
                        </p>
                    </div>

                    <span class="rounded-full bg-blue-500/10 px-2.5 py-0.5 text-xs font-extrabold text-blue-300 ring-1 ring-blue-400/20">
                        Policy
                    </span>
                </div>
            </div>

            <div class="{{ $cardBodyClass }}">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

                    <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                        <input type="hidden" name="enabled" value="0">

                        <label class="flex items-start gap-3">
                            <input type="checkbox"
                                   name="enabled"
                                   value="1"
                                   {{ old('enabled', $initial['enabled'] ?? false) ? 'checked' : '' }}
                                   class="mt-1 h-4 w-4 rounded border-white/20 bg-slate-950 text-orange-500 focus:ring-orange-500/20">

                            <span>
                                <span class="block text-sm font-extrabold text-white">
                                    Enable AI
                                </span>
                                <span class="mt-1 block text-xs font-medium leading-5 text-slate-500">
                                    Allows AI to draft or respond based on your workflow.
                                </span>
                            </span>
                        </label>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                        <input type="hidden" name="first_reply" value="0">

                        <label class="flex items-start gap-3">
                            <input type="checkbox"
                                   name="first_reply"
                                   value="1"
                                   {{ old('first_reply', $initial['first_reply'] ?? false) ? 'checked' : '' }}
                                   class="mt-1 h-4 w-4 rounded border-white/20 bg-slate-950 text-orange-500 focus:ring-orange-500/20">

                            <span>
                                <span class="block text-sm font-extrabold text-white">
                                    AI First Reply
                                </span>
                                <span class="mt-1 block text-xs font-medium leading-5 text-slate-500">
                                    AI may send the first response when rules allow it.
                                </span>
                            </span>
                        </label>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">
                            Confidence Threshold
                        </label>

                        <input type="number"
                               step="0.01"
                               min="0"
                               max="1"
                               name="confidence_threshold"
                               value="{{ old('confidence_threshold', $initial['confidence_threshold'] ?? 0.6) }}"
                               class="{{ $inputClass }}">

                        <p class="mt-2 text-xs font-medium text-slate-500">
                            0.60 means AI should be at least 60% confident before handling.
                        </p>
                    </div>

                </div>
            </div>
        </section>

        {{-- Intent Rules --}}
        <section class="{{ $cardClass }}">
            <div class="{{ $cardHeaderClass }}">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-extrabold text-white">
                            Intent Rules
                        </h2>
                        <p class="mt-1 text-sm font-medium text-slate-500">
                            Define what AI can handle, what should be handed off, and what is forbidden.
                        </p>
                    </div>

                    <span class="rounded-full bg-purple-500/10 px-2.5 py-0.5 text-xs font-extrabold text-purple-300 ring-1 ring-purple-400/20">
                        Routing
                    </span>
                </div>
            </div>

            <div class="{{ $cardBodyClass }}">
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="{{ $labelClass }}">
                            AI Can Handle
                        </label>

                        <input type="text"
                               name="intent_handle"
                               value="{{ old('intent_handle', $initial['intent_handle'] ?? '') }}"
                               class="{{ $inputClass }}"
                               placeholder="greeting,price,service_info">

                        <p class="mt-2 text-xs font-medium text-slate-500">
                            CSV list of intents AI can answer.
                        </p>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">
                            Handoff Intents
                        </label>

                        <input type="text"
                               name="intent_handoff"
                               value="{{ old('intent_handoff', $initial['intent_handoff'] ?? '') }}"
                               class="{{ $inputClass }}"
                               placeholder="booking_change,complex_quote">

                        <p class="mt-2 text-xs font-medium text-slate-500">
                            CSV list of intents that should go to manager/team.
                        </p>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">
                            Forbidden Intents
                        </label>

                        <input type="text"
                               name="intent_forbidden"
                               value="{{ old('intent_forbidden', $initial['intent_forbidden'] ?? '') }}"
                               class="{{ $inputClass }}"
                               placeholder="payments,personal_data">

                        <p class="mt-2 text-xs font-medium text-slate-500">
                            CSV list of areas AI should never handle.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Safety --}}
        <section class="{{ $cardClass }}">
            <div class="{{ $cardHeaderClass }}">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-extrabold text-white">
                            Safety & Policy Reply
                        </h2>
                        <p class="mt-1 text-sm font-medium text-slate-500">
                            Define restricted topics and the default safe reply.
                        </p>
                    </div>

                    <span class="rounded-full bg-red-500/10 px-2.5 py-0.5 text-xs font-extrabold text-red-300 ring-1 ring-red-400/20">
                        Safety
                    </span>
                </div>
            </div>

            <div class="{{ $cardBodyClass }} space-y-4">
                <div>
                    <label class="{{ $labelClass }}">
                        Forbidden Topics
                    </label>

                    <input type="text"
                           name="forbidden_topics"
                           value="{{ old('forbidden_topics', $initial['forbidden_topics'] ?? '') }}"
                           class="{{ $inputClass }}"
                           placeholder="Card details,PIN,OTP">

                    <p class="mt-2 text-xs font-medium text-slate-500">
                        CSV list of topics AI must avoid.
                    </p>
                </div>

                <div>
                    <label class="{{ $labelClass }}">
                        Policy Reply
                    </label>

                    <textarea name="policy_reply"
                              class="{{ $textareaClass }}"
                              placeholder="I can’t help with that. I’ll connect you to our manager.">{{ old('policy_reply', $initial['policy_reply'] ?? '') }}</textarea>

                    <p class="mt-2 text-xs font-medium text-slate-500">
                        This is sent when AI refuses or hands off because of policy.
                    </p>
                </div>
            </div>
        </section>

        {{-- Business Profile --}}
        <section class="{{ $cardClass }}">
            <div class="{{ $cardHeaderClass }}">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-extrabold text-white">
                            Business Context
                        </h2>
                        <p class="mt-1 text-sm font-medium text-slate-500">
                            Information AI can use while drafting replies.
                        </p>
                    </div>

                    <span class="rounded-full bg-green-500/10 px-2.5 py-0.5 text-xs font-extrabold text-green-300 ring-1 ring-green-400/20">
                        Context
                    </span>
                </div>
            </div>

            <div class="{{ $cardBodyClass }}">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}">
                            Manager Phone
                        </label>

                        <input type="text"
                               name="manager_phone"
                               value="{{ old('manager_phone', $initial['manager_phone'] ?? '') }}"
                               class="{{ $inputClass }}"
                               placeholder="9715XXXXXXXX">
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">
                            Work Hours
                        </label>

                        <input type="text"
                               name="work_hours"
                               value="{{ old('work_hours', $initial['work_hours'] ?? '') }}"
                               class="{{ $inputClass }}"
                               placeholder="Mon–Sat 09:00–18:00">
                    </div>

                    <div class="md:col-span-2">
                        <label class="{{ $labelClass }}">
                            Holidays JSON
                        </label>

                        <input type="text"
                               name="holidays"
                               value="{{ old('holidays', $initial['holidays'] ?? '[]') }}"
                               class="{{ $inputClass }}"
                               placeholder='["2025-12-25","2026-01-01"]'>

                        <p class="mt-2 text-xs font-medium text-slate-500">
                            Must be a JSON array.
                        </p>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">
                            Business Location
                        </label>

                        <input type="text"
                               name="location"
                               value="{{ old('location', $initial['location'] ?? '') }}"
                               class="{{ $inputClass }}"
                               placeholder="Al Quoz, Dubai">
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">
                            Location Coordinates
                        </label>

                        <input type="text"
                               name="location_coords"
                               value="{{ old('location_coords', $initial['location_coords'] ?? '') }}"
                               class="{{ $inputClass }}"
                               placeholder="25.12345,55.12345">
                    </div>
                </div>
            </div>
        </section>

        {{-- Escalation Rules --}}
        <section class="{{ $cardClass }}">
            <div class="{{ $cardHeaderClass }}">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-extrabold text-white">
                            Escalation Rules
                        </h2>
                        <p class="mt-1 text-sm font-medium text-slate-500">
                            Decide when AI should move the conversation to human mode.
                        </p>
                    </div>

                    <span class="rounded-full bg-yellow-500/10 px-2.5 py-0.5 text-xs font-extrabold text-yellow-300 ring-1 ring-yellow-400/20">
                        Handoff
                    </span>
                </div>
            </div>

            <div class="{{ $cardBodyClass }}">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

                    <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                        <input type="hidden" name="esc_low_confidence" value="0">

                        <label class="flex items-start gap-3">
                            <input type="checkbox"
                                   name="esc_low_confidence"
                                   value="1"
                                   {{ old('esc_low_confidence', $initial['esc_low_confidence'] ?? true) ? 'checked' : '' }}
                                   class="mt-1 h-4 w-4 rounded border-white/20 bg-slate-950 text-orange-500 focus:ring-orange-500/20">

                            <span>
                                <span class="block text-sm font-extrabold text-white">
                                    Low Confidence
                                </span>
                                <span class="mt-1 block text-xs font-medium leading-5 text-slate-500">
                                    Escalate if confidence is below threshold.
                                </span>
                            </span>
                        </label>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/55 p-4">
                        <input type="hidden" name="esc_sentiment" value="0">

                        <label class="flex items-start gap-3">
                            <input type="checkbox"
                                   name="esc_sentiment"
                                   value="1"
                                   {{ old('esc_sentiment', $initial['esc_sentiment'] ?? true) ? 'checked' : '' }}
                                   class="mt-1 h-4 w-4 rounded border-white/20 bg-slate-950 text-orange-500 focus:ring-orange-500/20">

                            <span>
                                <span class="block text-sm font-extrabold text-white">
                                    Negative Sentiment
                                </span>
                                <span class="mt-1 block text-xs font-medium leading-5 text-slate-500">
                                    Escalate angry or complaint-style messages.
                                </span>
                            </span>
                        </label>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}">
                            Timeout Minutes
                        </label>

                        <input type="number"
                               name="esc_timeout_minutes"
                               min="5"
                               max="10080"
                               value="{{ old('esc_timeout_minutes', $initial['esc_timeout_minutes'] ?? 120) }}"
                               class="{{ $inputClass }}">

                        <p class="mt-2 text-xs font-medium text-slate-500">
                            Conversation timeout before escalation.
                        </p>
                    </div>

                </div>
            </div>
        </section>

        {{-- Save --}}
        <div class="flex justify-end">
            <button type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-6 py-3 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
                Save AI Settings
            </button>
        </div>
    </form>
</div>
@endsection
