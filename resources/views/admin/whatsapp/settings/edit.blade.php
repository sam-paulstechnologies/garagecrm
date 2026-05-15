@extends('layouts.app')

@section('title', 'WhatsApp Settings')

@section('content')
@php
    $cardClass = 'rounded-3xl border border-white/10 bg-slate-900/80 shadow-xl shadow-black/20 overflow-hidden';
    $cardHeaderClass = 'border-b border-white/10 px-6 py-4 bg-slate-950/35';
    $cardBodyClass = 'px-6 py-6';
    $labelClass = 'block text-xs font-extrabold uppercase tracking-wide text-slate-400 mb-1.5';
    $inputClass = 'block w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm font-semibold text-white placeholder:text-slate-600 outline-none transition focus:border-orange-400/50 focus:ring-2 focus:ring-orange-500/10';
    $selectClass = $inputClass;
@endphp

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-orange-300">
                WhatsApp Control
            </div>

            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">
                WhatsApp Settings
            </h1>

            <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-400">
                Configure manager alerts, review links, WhatsApp automation controls, UAT reset, and journey mappings.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(\Illuminate\Support\Facades\Route::has('admin.whatsapp.templates.index'))
                <a href="{{ route('admin.whatsapp.templates.index') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                    Templates
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.whatsapp.mappings.index'))
                <a href="{{ route('admin.whatsapp.mappings.index') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                    Template Mappings
                </a>
            @endif
        </div>
    </div>

    {{-- Success --}}
    @if(session('success'))
        <div class="mb-5 rounded-2xl border border-green-400/20 bg-green-500/10 px-4 py-3 text-sm font-bold text-green-300">
            {{ session('success') }}
        </div>
    @endif

    {{-- UAT Reset Summary --}}
    @if(session('uat_reset_summary'))
        @php
            $summary = session('uat_reset_summary');
            $deleted = $summary['deleted'] ?? [];
        @endphp

        <div class="mb-5 rounded-2xl border border-yellow-400/20 bg-yellow-500/10 px-4 py-4 text-sm text-yellow-200">
            <p class="mb-3 font-extrabold text-yellow-100">
                UAT reset completed for +{{ $summary['phone'] ?? '' }}
            </p>

            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <div class="rounded-xl border border-white/10 bg-slate-950/40 p-3">Clients: <strong>{{ $deleted['clients'] ?? 0 }}</strong></div>
                <div class="rounded-xl border border-white/10 bg-slate-950/40 p-3">Leads: <strong>{{ $deleted['leads'] ?? 0 }}</strong></div>
                <div class="rounded-xl border border-white/10 bg-slate-950/40 p-3">Opportunities: <strong>{{ $deleted['opportunities'] ?? 0 }}</strong></div>
                <div class="rounded-xl border border-white/10 bg-slate-950/40 p-3">Bookings: <strong>{{ $deleted['bookings'] ?? 0 }}</strong></div>
                <div class="rounded-xl border border-white/10 bg-slate-950/40 p-3">Jobs: <strong>{{ $deleted['jobs'] ?? 0 }}</strong></div>
                <div class="rounded-xl border border-white/10 bg-slate-950/40 p-3">Invoices: <strong>{{ $deleted['invoices'] ?? 0 }}</strong></div>
                <div class="rounded-xl border border-white/10 bg-slate-950/40 p-3">Messages: <strong>{{ $deleted['message_logs'] ?? 0 }}</strong></div>
                <div class="rounded-xl border border-white/10 bg-slate-950/40 p-3">Conversations: <strong>{{ $deleted['conversations'] ?? 0 }}</strong></div>
            </div>
        </div>
    @endif

    {{-- Errors --}}
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

    {{-- Info --}}
    <div class="mb-6 rounded-3xl border border-blue-400/20 bg-blue-500/10 px-6 py-5">
        <p class="text-sm font-extrabold text-blue-200">
            WhatsApp-first journey settings
        </p>

        <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
            These settings support manager escalation, Google review requests, and customer WhatsApp journeys after booking, job completion, and feedback.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- LEFT --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- SETTINGS FORM --}}
            <form method="POST"
                  action="{{ route('admin.whatsapp.settings.update') }}"
                  class="{{ $cardClass }}">

                @csrf
                @method('PUT')

                <div class="{{ $cardHeaderClass }}">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-extrabold text-white">
                                WhatsApp Automation
                            </h2>

                            <p class="mt-1 text-sm font-medium text-slate-500">
                                Control provider, manager escalation, reviews, and location messages.
                            </p>
                        </div>

                        <span class="rounded-full bg-green-500/10 px-2.5 py-0.5 text-xs font-extrabold text-green-300 ring-1 ring-green-400/20">
                            Messaging
                        </span>
                    </div>
                </div>

                <div class="{{ $cardBodyClass }} space-y-5">

                    {{-- WhatsApp Active --}}
                    <div>
                        <label class="{{ $labelClass }}">
                            WhatsApp Automation Status
                        </label>

                        <select name="whatsapp_active" class="{{ $selectClass }}">
                            <option value="1" {{ old('whatsapp_active', $settings['whatsapp_active'] ?? '1') == '1' ? 'selected' : '' }}>
                                Active
                            </option>

                            <option value="0" {{ old('whatsapp_active', $settings['whatsapp_active'] ?? '1') == '0' ? 'selected' : '' }}>
                                Inactive
                            </option>
                        </select>

                        <p class="mt-2 text-xs font-medium text-slate-500">
                            Turn this off if the garage wants to pause automated WhatsApp messages.
                        </p>
                    </div>

                    {{-- Provider --}}
                    <div>
                        <label class="{{ $labelClass }}">
                            WhatsApp Provider
                        </label>

                        <select name="whatsapp_provider" class="{{ $selectClass }}">
                            <option value="meta" {{ old('whatsapp_provider', $settings['whatsapp_provider'] ?? 'meta') === 'meta' ? 'selected' : '' }}>
                                Meta Cloud API
                            </option>

                            <option value="twilio" {{ old('whatsapp_provider', $settings['whatsapp_provider'] ?? '') === 'twilio' ? 'selected' : '' }}>
                                Twilio
                            </option>
                        </select>

                        <p class="mt-2 text-xs font-medium text-slate-500">
                            Provider routing should match your configured WhatsApp service.
                        </p>
                    </div>

                    {{-- Manager WhatsApp --}}
                    <div>
                        <label class="{{ $labelClass }}">
                            Manager WhatsApp Number
                        </label>

                        <input type="text"
                               name="whatsapp_manager_number"
                               class="{{ $inputClass }}"
                               placeholder="+9715XXXXXXXX"
                               value="{{ old('whatsapp_manager_number', $settings['whatsapp_manager_number'] ?? $settings['whatsapp.manager_number'] ?? '') }}">

                        <p class="mt-2 text-xs font-medium text-slate-500">
                            Manager receives alerts when customer WhatsApp is missing, invalid, failed, or feedback is negative.
                        </p>
                    </div>

                    {{-- Google Review --}}
                    <div>
                        <label class="{{ $labelClass }}">
                            Google Review Link
                        </label>

                        <input type="url"
                               name="google_review_link"
                               class="{{ $inputClass }}"
                               placeholder="https://g.page/your-garage"
                               value="{{ old('google_review_link', $settings['google_review_link'] ?? '') }}">

                        <p class="mt-2 text-xs font-medium text-slate-500">
                            Sent only after positive customer feedback.
                        </p>
                    </div>

                    {{-- Garage Location --}}
                    <div>
                        <label class="{{ $labelClass }}">
                            Garage Location Link
                        </label>

                        <input type="url"
                               name="garage_location_link"
                               class="{{ $inputClass }}"
                               placeholder="https://maps.google.com/?q=..."
                               value="{{ old('garage_location_link', $settings['garage_location_link'] ?? '') }}">

                        <p class="mt-2 text-xs font-medium text-slate-500">
                            Used when sending directions or garage location to customers.
                        </p>
                    </div>

                    {{-- Positive Feedback Threshold --}}
                    <div>
                        <label class="{{ $labelClass }}">
                            Positive Feedback Threshold
                        </label>

                        <select name="positive_feedback_threshold" class="{{ $selectClass }}">
                            @foreach([3, 4, 5] as $rating)
                                <option value="{{ $rating }}"
                                    {{ (int) old('positive_feedback_threshold', $settings['positive_feedback_threshold'] ?? 4) === $rating ? 'selected' : '' }}>
                                    {{ $rating }} stars and above
                                </option>
                            @endforeach
                        </select>

                        <p class="mt-2 text-xs font-medium text-slate-500">
                            Feedback at or above this rating will trigger Google review request. Lower rating will escalate to manager.
                        </p>
                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-wrap justify-end gap-3 border-t border-white/10 pt-5">
                        <a href="{{ url()->previous() }}"
                           class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-5 py-2.5 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                            Cancel
                        </a>

                        <button type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-6 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
                            Save Settings
                        </button>
                    </div>
                </div>
            </form>

            {{-- UAT RESET TOOL --}}
            <div class="rounded-3xl border border-red-400/20 bg-slate-900/80 shadow-xl shadow-black/20 overflow-hidden">

                <div class="border-b border-red-400/10 bg-red-500/5 px-6 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-extrabold text-white">
                                UAT WhatsApp Reset Tool
                            </h2>

                            <p class="mt-1 text-sm font-medium text-slate-500">
                                Use only for testing. It deletes test records linked to a phone number.
                            </p>
                        </div>

                        <span class="rounded-full bg-red-500/10 px-2.5 py-0.5 text-xs font-extrabold text-red-300 ring-1 ring-red-400/20">
                            Danger
                        </span>
                    </div>
                </div>

                <div class="{{ $cardBodyClass }} space-y-4">
                    <div class="rounded-2xl border border-yellow-400/20 bg-yellow-500/10 p-4 text-sm font-medium leading-6 text-yellow-200">
                        This will delete matching client, lead, opportunity, booking, job, invoice, messages, and conversation records for your company only.
                    </div>

                    <form method="POST"
                          action="{{ route('admin.whatsapp.settings.uat-reset') }}"
                          class="space-y-4"
                          onsubmit="return confirm('Are you sure? This will permanently delete UAT records for this phone number.');">

                        @csrf

                        <div>
                            <label class="{{ $labelClass }}">
                                Test Phone Number
                            </label>

                            <input type="text"
                                   name="uat_phone"
                                   class="{{ $inputClass }}"
                                   placeholder="+971586934377"
                                   value="{{ old('uat_phone') }}">

                            <p class="mt-2 text-xs font-medium text-slate-500">
                                Example: +971586934377 or 971586934377. UAE local format like 0586934377 is also accepted.
                            </p>
                        </div>

                        <label class="flex items-start gap-3 rounded-2xl border border-white/10 bg-slate-950/55 p-4 text-sm font-semibold text-slate-300">
                            <input type="checkbox"
                                   name="confirm_uat_reset"
                                   value="1"
                                   class="mt-1 h-4 w-4 rounded border-white/20 bg-slate-950 text-red-500 focus:ring-red-500/20">

                            <span>
                                I understand this will permanently delete test data for the entered phone number.
                            </span>
                        </label>

                        <div class="flex justify-end">
                            <button type="submit"
                                    class="inline-flex items-center justify-center rounded-xl bg-red-600 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-red-500/20 transition hover:bg-red-700">
                                Delete Test Records
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- RIGHT --}}
        <aside class="space-y-6">

            <div class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <h3 class="text-lg font-extrabold text-white">
                        How this is used
                    </h3>
                </div>

                <div class="{{ $cardBodyClass }}">
                    <ul class="list-disc list-inside space-y-3 text-sm font-medium leading-6 text-slate-400">
                        <li>Manager number receives internal escalation alerts.</li>
                        <li>Google review link is sent only after positive feedback.</li>
                        <li>Negative feedback is escalated to manager.</li>
                        <li>Location link can be shared with customers.</li>
                        <li>Template mappings control journey messages.</li>
                    </ul>
                </div>
            </div>

            <div class="{{ $cardClass }}">
                <div class="{{ $cardHeaderClass }}">
                    <h3 class="text-lg font-extrabold text-white">
                        Journey Events
                    </h3>
                </div>

                <div class="{{ $cardBodyClass }}">
                    <div class="space-y-3 text-sm font-semibold text-slate-400">
                        <div class="flex justify-between gap-3">
                            <span>Lead acknowledgement</span>
                            <span class="text-slate-600">Template</span>
                        </div>

                        <div class="flex justify-between gap-3">
                            <span>Booking confirmed</span>
                            <span class="text-slate-600">Template</span>
                        </div>

                        <div class="flex justify-between gap-3">
                            <span>Job started</span>
                            <span class="text-slate-600">Template</span>
                        </div>

                        <div class="flex justify-between gap-3">
                            <span>Feedback survey</span>
                            <span class="text-slate-600">Template</span>
                        </div>

                        <div class="flex justify-between gap-3">
                            <span>Google review request</span>
                            <span class="text-slate-600">Template</span>
                        </div>

                        <div class="flex justify-between gap-3">
                            <span>Manager escalation</span>
                            <span class="text-slate-600">Template</span>
                        </div>
                    </div>

                    @if(\Illuminate\Support\Facades\Route::has('admin.whatsapp.mappings.index'))
                        <a href="{{ route('admin.whatsapp.mappings.index') }}"
                           class="mt-5 inline-flex w-full justify-center rounded-xl bg-orange-500 px-4 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
                            Manage Mappings
                        </a>
                    @endif
                </div>
            </div>

            <div class="rounded-3xl border border-white/10 bg-slate-950 shadow-xl shadow-black/20 overflow-hidden">
                <div class="border-b border-white/10 px-6 py-4">
                    <h3 class="text-lg font-extrabold text-white">
                        Testing Order
                    </h3>
                </div>

                <div class="px-6 py-6">
                    <ol class="list-decimal list-inside space-y-3 text-sm font-medium leading-6 text-slate-400">
                        <li>Reset the test phone number.</li>
                        <li>Start the queue worker.</li>
                        <li>Send “hi” to Sayara WhatsApp.</li>
                        <li>Complete booking journey.</li>
                        <li>Convert booking to job.</li>
                        <li>Complete job and test feedback.</li>
                    </ol>
                </div>
            </div>

        </aside>
    </div>
</div>
@endsection