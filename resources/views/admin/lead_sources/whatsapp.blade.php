@extends('layouts.app')

@section('content')
<div class="px-6 py-6 max-w-6xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">WhatsApp Lead Intake</h1>
            <p class="text-sm text-gray-500 mt-1">
                WhatsApp numbers, webhooks and review links
            </p>
        </div>

        <a href="{{ route('admin.lead-sources.index') }}"
           class="px-4 py-2 rounded border bg-white">
            Back
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <div class="md:col-span-2 bg-white border rounded-lg p-5 space-y-4">
            <div>
                <label class="text-sm font-medium">Garage WhatsApp (Twilio)</label>
                <input class="w-full border rounded bg-gray-100" readonly value="{{ $waFrom }}">
            </div>

            <div>
                <label class="text-sm font-medium">Manager WhatsApp</label>
                <input class="w-full border rounded bg-gray-100" readonly value="{{ $managerWhatsapp }}">
            </div>

            <div>
                <label class="text-sm font-medium">Google Review Link</label>
                <input class="w-full border rounded bg-gray-100" readonly value="{{ $googleReviewLink }}">
            </div>

            <a href="{{ route('admin.whatsapp.settings.edit') }}"
               class="inline-block px-4 py-2 bg-blue-600 text-white rounded">
                Manage WhatsApp Settings
            </a>
        </div>

        <div class="bg-white border rounded-lg p-5">
            <h3 class="font-semibold mb-2">Webhook URL</h3>
            <div class="text-xs bg-gray-100 p-2 rounded break-all">
                {{ $webhookUrl }}
            </div>
        </div>

    </div>
</div>
@endsection
