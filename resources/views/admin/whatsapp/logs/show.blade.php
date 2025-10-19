@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto p-6">
    <a href="{{ url()->previous() }}" class="text-sm text-gray-600 hover:underline">&larr; Back</a>

    <div class="mt-3 bg-white shadow rounded-lg p-5 space-y-4">
        <h1 class="text-xl font-semibold">Log #{{ $log->id }}</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-500">Created:</span> {{ $log->created_at }}</div>
            <div><span class="text-gray-500">Updated:</span> {{ $log->updated_at }}</div>
            <div><span class="text-gray-500">Company:</span> {{ $log->company_id }}</div>
            <div><span class="text-gray-500">Lead:</span> {{ $log->lead_id ?? '—' }}</div>
            <div><span class="text-gray-500">Direction:</span>
                <span class="inline-block px-2 py-0.5 rounded text-xs
                    {{ $log->direction === 'out' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                    {{ $log->direction }}
                </span>
            </div>
            <div><span class="text-gray-500">Channel:</span> {{ $log->channel }}</div>
            <div><span class="text-gray-500">To:</span> {{ $log->to_number }}</div>
            <div><span class="text-gray-500">From:</span> {{ $log->from_number }}</div>
            <div><span class="text-gray-500">Template:</span> {{ $log->template ?? '—' }}</div>
            <div><span class="text-gray-500">Provider ID:</span> {{ $log->provider_message_id ?? '—' }}</div>
            <div><span class="text-gray-500">Provider Status:</span> {{ $log->provider_status ?? '—' }}</div>
        </div>

        <div>
            <h2 class="font-semibold mb-1">Body</h2>
            <div class="border rounded p-3 text-sm whitespace-pre-wrap bg-gray-50">{{ $log->body ?? '—' }}</div>
        </div>

        <div>
            <h2 class="font-semibold mb-1">Meta (raw)</h2>
            <pre class="border rounded p-3 bg-gray-50 text-xs overflow-x-auto">{{ json_encode($log->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>
</div>
@endsection
