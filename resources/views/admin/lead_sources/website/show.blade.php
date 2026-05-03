@extends('layouts.app')

@section('content')
<div class="px-6 py-6 space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">
                {{ $leadSource->config['form_name'] ?? $leadSource->name }}
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Embed this form on your website to capture leads
            </p>
        </div>

        <a href="{{ route('admin.lead-sources.website.index') }}"
           class="px-4 py-2 rounded border bg-white hover:bg-gray-50">
            Back to Forms
        </a>
    </div>

    <div class="bg-white border rounded-lg p-5">
        <label class="block text-sm font-medium">API Endpoint</label>
        <input class="w-full mt-1 border rounded px-3 py-2 font-mono text-sm bg-gray-50"
               readonly value="{{ $formUrl }}">
        <p class="text-xs text-gray-500 mt-1">
            Public • No CSRF • Accepts form-data & JSON
        </p>
    </div>

    <div class="bg-white border rounded-lg p-5">
        <label class="block text-sm font-medium">Embed Snippet</label>
        <textarea class="w-full mt-1 border rounded px-3 py-2 font-mono text-sm bg-gray-50"
                  rows="6" readonly>{{ $embed }}</textarea>
    </div>

    <div class="bg-white border rounded-lg p-5">
        <h2 class="text-lg font-semibold mb-2">Live Preview</h2>
        <div class="border rounded-lg p-4 bg-gray-50">
            {!! $embed !!}
        </div>
    </div>

</div>
@endsection
