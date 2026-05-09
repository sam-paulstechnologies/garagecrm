@extends('layouts.app')

@section('title', 'WhatsApp Settings')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                WhatsApp Settings
            </h1>

            <p class="text-sm text-gray-500 mt-1">
                Configure manager alerts, review links, and WhatsApp automation controls.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(\Illuminate\Support\Facades\Route::has('admin.whatsapp.templates.index'))
                <a href="{{ route('admin.whatsapp.templates.index') }}"
                   class="inline-flex items-center justify-center px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                    Templates
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.whatsapp.mappings.index'))
                <a href="{{ route('admin.whatsapp.mappings.index') }}"
                   class="inline-flex items-center justify-center px-4 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                    Template Mappings
                </a>
            @endif
        </div>

    </div>

    {{-- Success --}}
    @if(session('success'))
        <div class="rounded-xl bg-green-50 border border-green-100 p-4 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Errors --}}
    @if($errors->any())
        <div class="rounded-xl bg-red-50 border border-red-100 p-4 text-red-800 text-sm">
            <p class="font-semibold mb-2">Please fix the following:</p>
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Info --}}
    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
        <p class="text-sm font-semibold text-blue-900">
            WhatsApp-first journey settings
        </p>
        <p class="text-sm text-blue-800 mt-1">
            These settings support manager escalation, Google review requests, and customer WhatsApp journeys after booking, job completion, and feedback.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- LEFT: FORM --}}
        <div class="lg:col-span-2">

            <form method="POST"
                  action="{{ route('admin.whatsapp.settings.update') }}"
                  class="bg-white rounded-xl border shadow-sm p-5 space-y-5">

                @csrf
                @method('PUT')

                {{-- WhatsApp Active --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        WhatsApp Automation Status
                    </label>

                    <select name="whatsapp_active"
                            class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="1" {{ old('whatsapp_active', $settings['whatsapp_active'] ?? '1') == '1' ? 'selected' : '' }}>
                            Active
                        </option>

                        <option value="0" {{ old('whatsapp_active', $settings['whatsapp_active'] ?? '1') == '0' ? 'selected' : '' }}>
                            Inactive
                        </option>
                    </select>

                    <p class="text-xs text-gray-500 mt-1">
                        Turn this off if the garage wants to pause automated WhatsApp messages.
                    </p>
                </div>

                {{-- Provider --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        WhatsApp Provider
                    </label>

                    <select name="whatsapp_provider"
                            class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="meta" {{ old('whatsapp_provider', $settings['whatsapp_provider'] ?? 'meta') === 'meta' ? 'selected' : '' }}>
                            Meta Cloud API
                        </option>

                        <option value="twilio" {{ old('whatsapp_provider', $settings['whatsapp_provider'] ?? '') === 'twilio' ? 'selected' : '' }}>
                            Twilio
                        </option>
                    </select>

                    <p class="text-xs text-gray-500 mt-1">
                        Provider routing should match your configured WhatsApp service.
                    </p>
                </div>

                {{-- Manager WhatsApp --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Manager WhatsApp Number
                    </label>

                    <input type="text"
                           name="whatsapp_manager_number"
                           class="w-full border rounded-lg px-3 py-2 text-sm"
                           placeholder="+9715XXXXXXXX"
                           value="{{ old('whatsapp_manager_number', $settings['whatsapp_manager_number'] ?? $settings['whatsapp.manager_number'] ?? '') }}">

                    <p class="text-xs text-gray-500 mt-1">
                        Manager receives alerts when customer WhatsApp is missing, invalid, failed, or feedback is negative.
                    </p>
                </div>

                {{-- Google Review --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Google Review Link
                    </label>

                    <input type="url"
                           name="google_review_link"
                           class="w-full border rounded-lg px-3 py-2 text-sm"
                           placeholder="https://g.page/your-garage"
                           value="{{ old('google_review_link', $settings['google_review_link'] ?? '') }}">

                    <p class="text-xs text-gray-500 mt-1">
                        Sent only after positive customer feedback.
                    </p>
                </div>

                {{-- Garage Location --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Garage Location Link
                    </label>

                    <input type="url"
                           name="garage_location_link"
                           class="w-full border rounded-lg px-3 py-2 text-sm"
                           placeholder="https://maps.google.com/?q=..."
                           value="{{ old('garage_location_link', $settings['garage_location_link'] ?? '') }}">

                    <p class="text-xs text-gray-500 mt-1">
                        Used when sending directions or garage location to customers.
                    </p>
                </div>

                {{-- Positive Feedback Threshold --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Positive Feedback Threshold
                    </label>

                    <select name="positive_feedback_threshold"
                            class="w-full border rounded-lg px-3 py-2 text-sm">
                        @foreach([3, 4, 5] as $rating)
                            <option value="{{ $rating }}"
                                {{ (int) old('positive_feedback_threshold', $settings['positive_feedback_threshold'] ?? 4) === $rating ? 'selected' : '' }}>
                                {{ $rating }} stars and above
                            </option>
                        @endforeach
                    </select>

                    <p class="text-xs text-gray-500 mt-1">
                        Feedback at or above this rating will trigger Google review request. Lower rating will escalate to manager.
                    </p>
                </div>

                {{-- Actions --}}
                <div class="pt-4 border-t flex flex-wrap justify-end gap-3">

                    <a href="{{ url()->previous() }}"
                       class="px-5 py-2 border rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>

                    <button type="submit"
                            class="px-6 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                        Save Settings
                    </button>

                </div>

            </form>

        </div>

        {{-- RIGHT: INFO --}}
        <aside class="space-y-6">

            <div class="bg-white rounded-xl border shadow-sm p-5 space-y-4">

                <h3 class="font-semibold text-gray-900">
                    How this is used
                </h3>

                <ul class="text-sm text-gray-600 space-y-2 list-disc list-inside">
                    <li>Manager number receives internal escalation alerts.</li>
                    <li>Google review link is sent only after positive feedback.</li>
                    <li>Negative feedback is escalated to manager.</li>
                    <li>Location link can be shared with customers.</li>
                    <li>Template mappings control which message is used for each journey step.</li>
                </ul>

            </div>

            <div class="bg-white rounded-xl border shadow-sm p-5">

                <h3 class="font-semibold text-gray-900">
                    Journey Events
                </h3>

                <div class="mt-3 space-y-2 text-sm text-gray-600">
                    <div class="flex justify-between">
                        <span>Lead acknowledgement</span>
                        <span class="text-gray-400">Template</span>
                    </div>

                    <div class="flex justify-between">
                        <span>Booking confirmed</span>
                        <span class="text-gray-400">Template</span>
                    </div>

                    <div class="flex justify-between">
                        <span>Job started</span>
                        <span class="text-gray-400">Template</span>
                    </div>

                    <div class="flex justify-between">
                        <span>Feedback survey</span>
                        <span class="text-gray-400">Template</span>
                    </div>

                    <div class="flex justify-between">
                        <span>Google review request</span>
                        <span class="text-gray-400">Template</span>
                    </div>

                    <div class="flex justify-between">
                        <span>Manager escalation</span>
                        <span class="text-gray-400">Template</span>
                    </div>
                </div>

                @if(\Illuminate\Support\Facades\Route::has('admin.whatsapp.mappings.index'))
                    <a href="{{ route('admin.whatsapp.mappings.index') }}"
                       class="mt-4 inline-flex w-full justify-center px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg text-sm font-medium">
                        Manage Mappings
                    </a>
                @endif

            </div>

        </aside>

    </div>

</div>
@endsection