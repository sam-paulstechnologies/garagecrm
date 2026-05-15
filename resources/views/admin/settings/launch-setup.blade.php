@extends('layouts.app')

@section('content')

@php
    /*
    |--------------------------------------------------------------------------
    | WhatsApp readiness
    |--------------------------------------------------------------------------
    | WABA ID is optional for Launch Setup because the current working Meta
    | sending/receiving flow uses phone number ID + access token + active flag.
    |--------------------------------------------------------------------------
    */
    $whatsappReady =
        !empty($company->meta_phone_number_id)
        && !empty($company->meta_access_token)
        && (bool) ($company->is_whatsapp_active ?? false);

    $managerName = data_get($company, 'manager_name');
    $managerPhone = data_get($company, 'manager_phone');
    $managerEmail = data_get($company, 'manager_email');

    $fieldClass = 'w-full rounded-xl border border-white/10 bg-slate-950/70 px-4 py-3 text-sm font-semibold text-white placeholder:text-slate-600 outline-none transition focus:border-orange-400/50 focus:ring-2 focus:ring-orange-500/10 disabled:cursor-not-allowed disabled:bg-slate-950/40 disabled:text-slate-500';
    $labelClass = 'mb-1.5 block text-xs font-extrabold uppercase tracking-wide text-slate-400';
    $cardClass = 'rounded-3xl border border-white/10 bg-slate-900/80 p-6 shadow-xl shadow-black/20';
    $sectionTitleClass = 'text-lg font-extrabold text-white';
    $sectionSubClass = 'mt-1 text-sm font-medium text-slate-500';
@endphp

<div class="sf-page mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-orange-300">
                Garage Launch
            </div>

            <h1 class="mt-4 text-3xl font-black tracking-tight text-white md:text-4xl">
                Launch Setup
            </h1>

            <p class="mt-2 max-w-2xl text-sm font-medium leading-6 text-slate-400">
                Complete garage details, manager handoff, working hours, booking rules, service areas, and WhatsApp readiness before going live.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(Route::has('admin.settings.index'))
                <a href="{{ route('admin.settings.index') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-slate-900 px-4 py-2 text-sm font-extrabold text-slate-200 transition hover:border-orange-400/30 hover:text-white">
                    All Settings
                </a>
            @endif

            @if(Route::has('admin.whatsapp.settings.edit'))
                <a href="{{ route('admin.whatsapp.settings.edit') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-green-400/20 bg-green-500/10 px-4 py-2 text-sm font-extrabold text-green-300 transition hover:border-green-400/40">
                    WhatsApp Settings
                </a>
            @endif
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="rounded-2xl border border-green-400/20 bg-green-500/10 px-4 py-3 text-sm font-bold text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="rounded-2xl border border-yellow-400/20 bg-yellow-500/10 px-4 py-3 text-sm font-bold text-yellow-300">
            {{ session('warning') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-sm text-red-300">
            <div class="font-extrabold text-red-200">Please fix the following:</div>
            <ul class="mt-2 list-disc space-y-1 pl-5 font-semibold">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Top Readiness Strip --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-xl shadow-black/20 lg:col-span-2">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-extrabold text-white">Launch Readiness</h2>
                    <p class="mt-1 text-sm font-medium text-slate-400">
                        Complete the checklist to prepare this garage for launch.
                    </p>
                </div>

                <div class="text-4xl font-black text-blue-300">
                    {{ $completion }}%
                </div>
            </div>

            <div class="mt-5 h-3 overflow-hidden rounded-full bg-slate-950/70 ring-1 ring-white/10">
                <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-orange-500"
                     style="width: {{ $completion }}%">
                </div>
            </div>
        </div>

        <div class="rounded-3xl border {{ $whatsappReady ? 'border-green-400/20 bg-green-500/10' : 'border-red-400/20 bg-red-500/10' }} p-5 shadow-xl shadow-black/20">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl {{ $whatsappReady ? 'bg-green-500/15 text-green-300 ring-green-400/20' : 'bg-red-500/15 text-red-300 ring-red-400/20' }} text-lg font-black ring-1">
                    {{ $whatsappReady ? '✓' : '!' }}
                </span>

                <div>
                    <h2 class="text-lg font-extrabold text-white">
                        {{ $whatsappReady ? 'WhatsApp Connected' : 'WhatsApp Not Ready' }}
                    </h2>
                    <p class="mt-1 text-xs font-semibold text-slate-400">
                        {{ $whatsappReady ? 'Can send and receive messages.' : 'Complete WhatsApp setup before launch.' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- LEFT: FORM --}}
        <div class="lg:col-span-2">

            <form method="POST"
                  action="{{ route('admin.settings.launch-setup.update') }}"
                  class="space-y-6">

                @csrf
                @method('PUT')

                {{-- Business Details --}}
                <div class="{{ $cardClass }}">
                    <div class="mb-5">
                        <h2 class="{{ $sectionTitleClass }}">
                            Business Details
                        </h2>
                        <p class="{{ $sectionSubClass }}">
                            Public garage identity, contact details, and location.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                        <div>
                            <label class="{{ $labelClass }}">
                                Garage Name
                            </label>
                            <input type="text"
                                   value="{{ $company->name }}"
                                   disabled
                                   class="{{ $fieldClass }}">
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">
                                Legal Name
                            </label>
                            <input type="text"
                                   name="legal_name"
                                   value="{{ old('legal_name', $company->legal_name) }}"
                                   class="{{ $fieldClass }}">
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">
                                Business Phone
                            </label>
                            <input type="text"
                                   name="business_phone"
                                   value="{{ old('business_phone', $company->business_phone) }}"
                                   class="{{ $fieldClass }}">
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">
                                Business Email
                            </label>
                            <input type="email"
                                   name="business_email"
                                   value="{{ old('business_email', $company->business_email) }}"
                                   class="{{ $fieldClass }}">
                        </div>

                        <div class="md:col-span-2">
                            <label class="{{ $labelClass }}">
                                Garage Address
                            </label>
                            <textarea name="address"
                                      rows="3"
                                      class="{{ $fieldClass }}">{{ old('address', $company->address) }}</textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label class="{{ $labelClass }}">
                                Google Maps Location Pin / Link
                            </label>
                            <input type="text"
                                   name="location_pin"
                                   value="{{ old('location_pin', $company->location_pin) }}"
                                   placeholder="Paste Google Maps link here"
                                   class="{{ $fieldClass }}">

                            @if(!empty($company->location_pin))
                                <a href="{{ $company->location_pin }}"
                                   target="_blank"
                                   class="mt-3 inline-flex text-sm font-bold text-blue-300 hover:text-blue-200">
                                    Open saved location →
                                </a>
                            @endif
                        </div>

                    </div>
                </div>

                {{-- Manager Details --}}
                <div class="{{ $cardClass }}">
                    <div class="mb-5">
                        <h2 class="{{ $sectionTitleClass }}">
                            Manager / Handoff Details
                        </h2>
                        <p class="{{ $sectionSubClass }}">
                            Used for lead escalation, booking handoff, and internal contact.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                        <div>
                            <label class="{{ $labelClass }}">
                                Manager Name
                            </label>
                            <input type="text"
                                   name="manager_name"
                                   value="{{ old('manager_name', $managerName) }}"
                                   placeholder="Example: Ahmed"
                                   class="{{ $fieldClass }}">
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">
                                Manager Phone / WhatsApp
                            </label>
                            <input type="text"
                                   name="manager_phone"
                                   value="{{ old('manager_phone', $managerPhone) }}"
                                   placeholder="Example: 9715XXXXXXXX"
                                   class="{{ $fieldClass }}">
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">
                                Manager Email
                            </label>
                            <input type="email"
                                   name="manager_email"
                                   value="{{ old('manager_email', $managerEmail) }}"
                                   placeholder="manager@example.com"
                                   class="{{ $fieldClass }}">
                        </div>

                        <div class="flex items-center rounded-xl border border-white/10 bg-slate-950/50 px-4 py-3 md:mt-6">
                            <input type="checkbox"
                                   name="send_manager_password_reset"
                                   value="1"
                                   class="h-4 w-4 rounded border-white/20 bg-slate-950 text-orange-500 focus:ring-orange-500/20">

                            <label class="ml-3 text-sm font-bold text-slate-300">
                                Send password reset link to manager
                            </label>
                        </div>

                    </div>

                    <p class="mt-4 text-xs font-medium text-slate-500">
                        Password reset will work only if this manager email already exists as a user in the system.
                    </p>
                </div>

                {{-- Working Hours --}}
                <div class="{{ $cardClass }}">
                    <div class="mb-5">
                        <h2 class="{{ $sectionTitleClass }}">
                            Working Hours
                        </h2>
                        <p class="{{ $sectionSubClass }}">
                            Helps customers and managers understand garage availability.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                        <div>
                            <label class="{{ $labelClass }}">
                                Opening Time
                            </label>
                            <input type="text"
                                   name="working_hours[open_time]"
                                   value="{{ old('working_hours.open_time', $workingHours['open_time'] ?? '') }}"
                                   placeholder="Example: 8:00 AM"
                                   class="{{ $fieldClass }}">
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">
                                Closing Time
                            </label>
                            <input type="text"
                                   name="working_hours[close_time]"
                                   value="{{ old('working_hours.close_time', $workingHours['close_time'] ?? '') }}"
                                   placeholder="Example: 8:00 PM"
                                   class="{{ $fieldClass }}">
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">
                                Weekly Off
                            </label>
                            <input type="text"
                                   name="working_hours[weekly_off]"
                                   value="{{ old('working_hours.weekly_off', $workingHours['weekly_off'] ?? '') }}"
                                   placeholder="Example: Sunday"
                                   class="{{ $fieldClass }}">
                        </div>

                        <div class="flex items-center rounded-xl border border-white/10 bg-slate-950/50 px-4 py-3 md:mt-6">
                            <input type="checkbox"
                                   name="working_hours[emergency_available]"
                                   value="1"
                                   {{ old('working_hours.emergency_available', $workingHours['emergency_available'] ?? false) ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-white/20 bg-slate-950 text-orange-500 focus:ring-orange-500/20">

                            <label class="ml-3 text-sm font-bold text-slate-300">
                                Emergency support available
                            </label>
                        </div>

                    </div>
                </div>

                {{-- Booking Rules --}}
                <div class="{{ $cardClass }}">
                    <div class="mb-5">
                        <h2 class="{{ $sectionTitleClass }}">
                            Booking Rules
                        </h2>
                        <p class="{{ $sectionSubClass }}">
                            Default slot capacity and pickup/drop-off options.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                        <div>
                            <label class="{{ $labelClass }}">
                                Max Bookings Per Slot
                            </label>
                            <input type="number"
                                   name="booking_rules[max_bookings_per_slot]"
                                   value="{{ old('booking_rules.max_bookings_per_slot', $bookingRules['max_bookings_per_slot'] ?? '') }}"
                                   min="1"
                                   class="{{ $fieldClass }}">
                        </div>

                        <div>
                            <label class="{{ $labelClass }}">
                                Default Slot Duration
                            </label>
                            <input type="text"
                                   name="booking_rules[default_slot_duration]"
                                   value="{{ old('booking_rules.default_slot_duration', $bookingRules['default_slot_duration'] ?? '') }}"
                                   placeholder="Example: 2 hours"
                                   class="{{ $fieldClass }}">
                        </div>

                        <div class="flex items-center rounded-xl border border-white/10 bg-slate-950/50 px-4 py-3">
                            <input type="checkbox"
                                   name="booking_rules[pickup_available]"
                                   value="1"
                                   {{ old('booking_rules.pickup_available', $bookingRules['pickup_available'] ?? false) ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-white/20 bg-slate-950 text-orange-500 focus:ring-orange-500/20">

                            <label class="ml-3 text-sm font-bold text-slate-300">
                                Pickup available
                            </label>
                        </div>

                        <div class="flex items-center rounded-xl border border-white/10 bg-slate-950/50 px-4 py-3">
                            <input type="checkbox"
                                   name="booking_rules[dropoff_available]"
                                   value="1"
                                   {{ old('booking_rules.dropoff_available', $bookingRules['dropoff_available'] ?? false) ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-white/20 bg-slate-950 text-orange-500 focus:ring-orange-500/20">

                            <label class="ml-3 text-sm font-bold text-slate-300">
                                Drop-off available
                            </label>
                        </div>

                    </div>
                </div>

                {{-- Service Areas --}}
                <div class="{{ $cardClass }}">
                    <div class="mb-5">
                        <h2 class="{{ $sectionTitleClass }}">
                            Service Areas
                        </h2>
                        <p class="{{ $sectionSubClass }}">
                            Mention one area per line for pickup/drop-off and service coverage.
                        </p>
                    </div>

                    <label class="{{ $labelClass }}">
                        Areas Served
                    </label>

                    <textarea name="service_areas"
                              rows="5"
                              placeholder="One area per line. Example: JVC, Al Quoz, Business Bay"
                              class="{{ $fieldClass }}">{{ old('service_areas', implode("\n", $serviceAreas ?? [])) }}</textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-orange-500 px-6 py-3 text-sm font-extrabold text-white shadow-lg shadow-orange-500/20 transition hover:bg-orange-600">
                        Save Launch Setup
                    </button>
                </div>

            </form>

        </div>

        {{-- RIGHT: READINESS --}}
        <div class="space-y-6 lg:col-span-1">

            <div class="{{ $cardClass }}">
                <h2 class="{{ $sectionTitleClass }}">
                    Launch Readiness
                </h2>

                <div class="mt-4 text-5xl font-black text-blue-300">
                    {{ $completion }}%
                </div>

                <div class="mt-5 h-3 overflow-hidden rounded-full bg-slate-950/70 ring-1 ring-white/10">
                    <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-orange-500"
                         style="width: {{ $completion }}%">
                    </div>
                </div>

                <p class="mt-4 text-sm font-medium leading-6 text-slate-400">
                    Complete all details to move this garage closer to launch.
                </p>
            </div>

            <div class="{{ $cardClass }}">
                <h2 class="{{ $sectionTitleClass }}">
                    WhatsApp Status
                </h2>

                @if($whatsappReady)
                    <div class="mt-4 flex items-center gap-2 text-sm font-extrabold text-green-300">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-green-500/10 text-xs ring-1 ring-green-400/20">
                            ✓
                        </span>
                        WhatsApp Connected
                    </div>

                    <p class="mt-3 text-sm font-medium leading-6 text-slate-400">
                        This garage can send and receive WhatsApp messages.
                    </p>
                @else
                    <div class="mt-4 flex items-center gap-2 text-sm font-extrabold text-red-300">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-red-500/10 text-xs ring-1 ring-red-400/20">
                            !
                        </span>
                        WhatsApp Not Ready
                    </div>

                    <p class="mt-3 text-sm font-medium leading-6 text-slate-400">
                        Complete WhatsApp setup before launch.
                    </p>
                @endif

                <ul class="mt-4 space-y-2 text-xs font-semibold text-slate-500">
                    <li>Phone Number ID: <span class="text-slate-300">{{ !empty($company->meta_phone_number_id) ? 'Added' : 'Missing' }}</span></li>
                    <li>WABA ID: <span class="text-slate-300">{{ !empty($company->meta_waba_id) ? 'Added' : 'Optional / not required' }}</span></li>
                    <li>Access Token: <span class="text-slate-300">{{ !empty($company->meta_access_token) ? 'Added' : 'Missing' }}</span></li>
                    <li>Status: <span class="text-slate-300">{{ (bool) ($company->is_whatsapp_active ?? false) ? 'Active' : 'Inactive' }}</span></li>
                </ul>

                @if(!$whatsappReady && Route::has('admin.whatsapp.settings.edit'))
                    <a href="{{ route('admin.whatsapp.settings.edit') }}"
                       class="mt-5 inline-flex w-full items-center justify-center rounded-xl bg-green-600 px-4 py-3 text-sm font-extrabold text-white transition hover:bg-green-700">
                        Open WhatsApp Settings
                    </a>
                @endif
            </div>

            <div class="{{ $cardClass }}">
                <h2 class="{{ $sectionTitleClass }}">
                    Checklist
                </h2>

                <div class="mt-5 space-y-3">
                    @foreach($checklist as $item)
                        <div class="flex items-start gap-3 rounded-xl border border-white/10 bg-slate-950/40 p-3">
                            <div class="mt-0.5">
                                @if($item['done'])
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-green-500/10 text-xs font-black text-green-300 ring-1 ring-green-400/20">
                                        ✓
                                    </span>
                                @else
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-700/50 text-xs font-black text-slate-400 ring-1 ring-white/10">
                                        —
                                    </span>
                                @endif
                            </div>

                            <div class="text-sm font-bold {{ $item['done'] ? 'text-slate-200' : 'text-slate-500' }}">
                                {{ $item['label'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

    </div>

</div>

@endsection