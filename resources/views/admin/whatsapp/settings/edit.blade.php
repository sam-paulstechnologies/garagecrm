@extends('layouts.app')

@section('title', 'WhatsApp Settings')

@section('content')
<div class="px-6 py-6 max-w-5xl mx-auto space-y-6">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">WhatsApp Settings</h1>
        <p class="text-sm text-gray-500 mt-1">
            Configure notification numbers and customer-facing links
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- LEFT: FORM --}}
        <div class="md:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border">

                <div class="p-5 border-b">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Notification & Sharing Details
                    </h2>
                </div>

                <form method="POST"
                      action="{{ route('admin.whatsapp.settings.save') }}"
                      class="p-5 space-y-5">
                    @csrf

                    {{-- Manager WhatsApp --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Manager WhatsApp Number
                        </label>

                        <input type="text"
                               name="whatsapp_manager_number"
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="+9715XXXXXXXX"
                               value="{{ old('whatsapp_manager_number', $settings['whatsapp_manager_number'] ?? '') }}">

                        <p class="text-xs text-gray-500 mt-1">
                            Alerts, escalations, and internal follow-ups will be sent to this number.
                        </p>
                    </div>

                    {{-- Google Review --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Google Review Link
                        </label>

                        <input type="url"
                               name="google_review_link"
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="https://g.page/your-garage"
                               value="{{ old('google_review_link', $settings['google_review_link'] ?? '') }}">

                        <p class="text-xs text-gray-500 mt-1">
                            Automatically shared with customers after job completion.
                        </p>
                    </div>

                    {{-- Garage Location --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Garage Location (Google Maps)
                        </label>

                        <input type="url"
                               name="garage_location_link"
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="https://maps.google.com/?q=..."
                               value="{{ old('garage_location_link', $settings['garage_location_link'] ?? '') }}">

                        <p class="text-xs text-gray-500 mt-1">
                            Used in WhatsApp replies for directions and location sharing.
                        </p>
                    </div>

                    {{-- Actions --}}
                    <div class="pt-4 border-t flex justify-end">
                        <button type="submit"
                                class="px-6 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700">
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- RIGHT: INFO --}}
        <div>
            <div class="bg-white rounded-xl shadow-sm border p-5 space-y-4">
                <h3 class="font-semibold text-gray-900">
                    How this is used
                </h3>

                <ul class="text-sm text-gray-600 space-y-2 list-disc list-inside">
                    <li>Manager number receives internal WhatsApp alerts</li>
                    <li>Review link is shared post service completion</li>
                    <li>Location is sent when customers ask for directions</li>
                    <li>Garage WhatsApp number is managed by Super Admin</li>
                </ul>

                <div class="pt-3 border-t text-xs text-gray-500">
                    These settings apply immediately across WhatsApp automation.
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
