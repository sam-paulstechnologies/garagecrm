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
@endphp

<div class="max-w-6xl mx-auto px-4 py-6">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            Launch Setup
        </h1>

        <p class="text-sm text-gray-600 mt-1">
            Complete these details so your garage is ready for launch.
        </p>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-100 text-green-800 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="mb-4 bg-yellow-100 text-yellow-800 px-4 py-3 rounded">
            {{ session('warning') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 bg-red-100 text-red-800 px-4 py-3 rounded">
            <strong>Please fix the following:</strong>
            <ul class="list-disc list-inside mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- LEFT: FORM --}}
        <div class="lg:col-span-2">

            <form method="POST"
                  action="{{ route('admin.settings.launch-setup.update') }}"
                  class="space-y-6">

                @csrf
                @method('PUT')

                {{-- Business Details --}}
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">
                        Business Details
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Garage Name
                            </label>
                            <input type="text"
                                   value="{{ $company->name }}"
                                   disabled
                                   class="w-full border rounded px-3 py-2 bg-gray-100 text-gray-600">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Legal Name
                            </label>
                            <input type="text"
                                   name="legal_name"
                                   value="{{ old('legal_name', $company->legal_name) }}"
                                   class="w-full border rounded px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Business Phone
                            </label>
                            <input type="text"
                                   name="business_phone"
                                   value="{{ old('business_phone', $company->business_phone) }}"
                                   class="w-full border rounded px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Business Email
                            </label>
                            <input type="email"
                                   name="business_email"
                                   value="{{ old('business_email', $company->business_email) }}"
                                   class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Garage Address
                            </label>
                            <textarea name="address"
                                      rows="3"
                                      class="w-full border rounded px-3 py-2">{{ old('address', $company->address) }}</textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Google Maps Location Pin / Link
                            </label>
                            <input type="text"
                                   name="location_pin"
                                   value="{{ old('location_pin', $company->location_pin) }}"
                                   placeholder="Paste Google Maps link here"
                                   class="w-full border rounded px-3 py-2">

                            @if(!empty($company->location_pin))
                                <a href="{{ $company->location_pin }}"
                                   target="_blank"
                                   class="text-sm text-blue-600 hover:underline inline-block mt-2">
                                    Open saved location
                                </a>
                            @endif
                        </div>

                    </div>
                </div>

                {{-- Manager Details --}}
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">
                        Manager / Handoff Details
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Manager Name
                            </label>
                            <input type="text"
                                   name="manager_name"
                                   value="{{ old('manager_name', $managerName) }}"
                                   placeholder="Example: Ahmed"
                                   class="w-full border rounded px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Manager Phone / WhatsApp
                            </label>
                            <input type="text"
                                   name="manager_phone"
                                   value="{{ old('manager_phone', $managerPhone) }}"
                                   placeholder="Example: 9715XXXXXXXX"
                                   class="w-full border rounded px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Manager Email
                            </label>
                            <input type="email"
                                   name="manager_email"
                                   value="{{ old('manager_email', $managerEmail) }}"
                                   placeholder="manager@example.com"
                                   class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="flex items-center mt-6">
                            <input type="checkbox"
                                   name="send_manager_password_reset"
                                   value="1"
                                   class="mr-2">

                            <label class="text-sm text-gray-700">
                                Send password reset link to manager
                            </label>
                        </div>

                    </div>

                    <p class="text-xs text-gray-500 mt-3">
                        Password reset will work only if this manager email already exists as a user in the system.
                    </p>
                </div>

                {{-- Working Hours --}}
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">
                        Working Hours
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Opening Time
                            </label>
                            <input type="text"
                                   name="working_hours[open_time]"
                                   value="{{ old('working_hours.open_time', $workingHours['open_time'] ?? '') }}"
                                   placeholder="Example: 8:00 AM"
                                   class="w-full border rounded px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Closing Time
                            </label>
                            <input type="text"
                                   name="working_hours[close_time]"
                                   value="{{ old('working_hours.close_time', $workingHours['close_time'] ?? '') }}"
                                   placeholder="Example: 8:00 PM"
                                   class="w-full border rounded px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Weekly Off
                            </label>
                            <input type="text"
                                   name="working_hours[weekly_off]"
                                   value="{{ old('working_hours.weekly_off', $workingHours['weekly_off'] ?? '') }}"
                                   placeholder="Example: Sunday"
                                   class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="flex items-center mt-6">
                            <input type="checkbox"
                                   name="working_hours[emergency_available]"
                                   value="1"
                                   {{ old('working_hours.emergency_available', $workingHours['emergency_available'] ?? false) ? 'checked' : '' }}
                                   class="mr-2">

                            <label class="text-sm text-gray-700">
                                Emergency support available
                            </label>
                        </div>

                    </div>
                </div>

                {{-- Booking Rules --}}
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">
                        Booking Rules
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Max Bookings Per Slot
                            </label>
                            <input type="number"
                                   name="booking_rules[max_bookings_per_slot]"
                                   value="{{ old('booking_rules.max_bookings_per_slot', $bookingRules['max_bookings_per_slot'] ?? '') }}"
                                   min="1"
                                   class="w-full border rounded px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Default Slot Duration
                            </label>
                            <input type="text"
                                   name="booking_rules[default_slot_duration]"
                                   value="{{ old('booking_rules.default_slot_duration', $bookingRules['default_slot_duration'] ?? '') }}"
                                   placeholder="Example: 2 hours"
                                   class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox"
                                   name="booking_rules[pickup_available]"
                                   value="1"
                                   {{ old('booking_rules.pickup_available', $bookingRules['pickup_available'] ?? false) ? 'checked' : '' }}
                                   class="mr-2">

                            <label class="text-sm text-gray-700">
                                Pickup available
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox"
                                   name="booking_rules[dropoff_available]"
                                   value="1"
                                   {{ old('booking_rules.dropoff_available', $bookingRules['dropoff_available'] ?? false) ? 'checked' : '' }}
                                   class="mr-2">

                            <label class="text-sm text-gray-700">
                                Drop-off available
                            </label>
                        </div>

                    </div>
                </div>

                {{-- Service Areas --}}
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">
                        Service Areas
                    </h2>

                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Areas Served
                    </label>

                    <textarea name="service_areas"
                              rows="5"
                              placeholder="One area per line. Example: JVC, Al Quoz, Business Bay"
                              class="w-full border rounded px-3 py-2">{{ old('service_areas', implode("\n", $serviceAreas ?? [])) }}</textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                        Save Launch Setup
                    </button>
                </div>

            </form>

        </div>

        {{-- RIGHT: READINESS --}}
        <div class="lg:col-span-1">

            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">
                    Launch Readiness
                </h2>

                <div class="text-4xl font-bold text-blue-600 mb-3">
                    {{ $completion }}%
                </div>

                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="bg-blue-600 h-3 rounded-full"
                         style="width: {{ $completion }}%">
                    </div>
                </div>

                <p class="text-sm text-gray-600 mt-3">
                    Complete all details to move this garage closer to launch.
                </p>
            </div>

            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">
                    WhatsApp Status
                </h2>

                @if($whatsappReady)
                    <div class="flex items-center gap-2 text-green-700 font-medium">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-green-100 text-green-700 text-xs">
                            ✓
                        </span>
                        WhatsApp Connected
                    </div>

                    <p class="text-sm text-gray-600 mt-3">
                        This garage can send and receive WhatsApp messages.
                    </p>

                    <ul class="list-disc list-inside text-xs text-gray-500 mt-3">
                        <li>Phone Number ID: {{ !empty($company->meta_phone_number_id) ? 'Added' : 'Missing' }}</li>
                        <li>WABA ID: {{ !empty($company->meta_waba_id) ? 'Added' : 'Optional / not required' }}</li>
                        <li>Access Token: {{ !empty($company->meta_access_token) ? 'Added' : 'Missing' }}</li>
                        <li>Status: {{ (bool) ($company->is_whatsapp_active ?? false) ? 'Active' : 'Inactive' }}</li>
                    </ul>
                @else
                    <div class="flex items-center gap-2 text-red-700 font-medium">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-100 text-red-700 text-xs">
                            !
                        </span>
                        WhatsApp Not Ready
                    </div>

                    <div class="text-sm text-gray-600 mt-3 space-y-1">
                        <p>Complete WhatsApp setup before launch.</p>

                        <ul class="list-disc list-inside text-xs text-gray-500">
                            <li>Phone Number ID: {{ !empty($company->meta_phone_number_id) ? 'Added' : 'Missing' }}</li>
                            <li>WABA ID: {{ !empty($company->meta_waba_id) ? 'Added' : 'Optional / not required' }}</li>
                            <li>Access Token: {{ !empty($company->meta_access_token) ? 'Added' : 'Missing' }}</li>
                            <li>Status: {{ (bool) ($company->is_whatsapp_active ?? false) ? 'Active' : 'Inactive' }}</li>
                        </ul>
                    </div>

                    @if(Route::has('admin.whatsapp.settings.edit'))
                        <a href="{{ route('admin.whatsapp.settings.edit') }}"
                           class="inline-block mt-4 bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">
                            Open WhatsApp Settings
                        </a>
                    @endif
                @endif
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    Checklist
                </h2>

                <div class="space-y-3">
                    @foreach($checklist as $item)
                        <div class="flex items-start gap-3">
                            <div class="mt-1">
                                @if($item['done'])
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-green-100 text-green-700 text-xs">
                                        ✓
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-100 text-gray-500 text-xs">
                                        —
                                    </span>
                                @endif
                            </div>

                            <div class="text-sm {{ $item['done'] ? 'text-gray-800' : 'text-gray-500' }}">
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