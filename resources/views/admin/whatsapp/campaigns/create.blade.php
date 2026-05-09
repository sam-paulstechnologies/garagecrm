@extends('layouts.app')

@section('title', 'New WhatsApp Campaign')

@section('content')
<div class="max-w-3xl mx-auto p-6">
    <div class="mb-5">
        <h1 class="text-2xl font-semibold text-gray-900">New WhatsApp Campaign</h1>
        <p class="text-sm text-gray-500 mt-1">
            Create a WhatsApp broadcast using an approved template and manual audience numbers.
        </p>
    </div>

    <form method="POST" action="{{ route('admin.whatsapp.campaigns.store') }}" class="space-y-5 bg-white border rounded shadow-sm p-6">
        @csrf

        @if ($errors->any())
            <div class="p-3 rounded bg-red-50 text-red-800">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div>
            <label class="block text-sm font-medium mb-1">Campaign Name</label>
            <input
                name="name"
                value="{{ old('name') }}"
                required
                maxlength="120"
                class="w-full border rounded px-3 py-2"
                placeholder="Example: May AC Service Offer"
            >
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">WhatsApp Template</label>
            <select name="message_template_id" required class="w-full border rounded px-3 py-2">
                <option value="">— Select template —</option>

                @foreach($templates as $template)
                    <option value="{{ $template->id }}" @selected(old('message_template_id') == $template->id)>
                        {{ $template->name }}
                        @if($template->provider_template)
                            — {{ $template->provider_template }}
                        @endif
                        ({{ strtoupper($template->language ?? 'en') }})
                    </option>
                @endforeach
            </select>

            @if($templates->isEmpty())
                <p class="text-sm text-red-600 mt-2">
                    No active WhatsApp templates found. Please create/activate a template first.
                </p>
            @endif
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Description</label>
            <textarea
                name="description"
                rows="3"
                class="w-full border rounded px-3 py-2"
                placeholder="Internal notes about this campaign"
            >{{ old('description') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Audience Numbers</label>
            <textarea
                name="audience"
                rows="8"
                class="w-full border rounded px-3 py-2 font-mono text-sm"
                placeholder="One number per line, or comma-separated. Example:
971501234567
0501234567"
            >{{ old('audience') }}</textarea>

            <p class="text-xs text-gray-500 mt-1">
                UAE numbers starting with 05 will be normalized to 9715...
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Schedule At</label>
            <input
                type="datetime-local"
                name="scheduled_at"
                value="{{ old('scheduled_at') }}"
                class="w-full border rounded px-3 py-2"
            >
            <p class="text-xs text-gray-500 mt-1">
                Leave empty to save as draft. Add date/time to schedule.
            </p>
        </div>

        <div class="pt-2 flex gap-2">
            <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded">
                Save Campaign
            </button>

            <a href="{{ route('admin.whatsapp.campaigns.index') }}"
               class="px-4 py-2 border rounded text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection