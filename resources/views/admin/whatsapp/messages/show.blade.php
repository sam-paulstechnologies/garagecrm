@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6">
    <h1 class="text-2xl font-semibold mb-4">Message #{{ $message->id }}</h1>

    <div class="bg-white rounded shadow divide-y">
        <div class="p-4 grid grid-cols-2 gap-4">
            <div><strong>Provider:</strong> {{ strtoupper($message->provider) }}</div>
            <div><strong>Status:</strong> {{ $message->status }}</div>
            <div><strong>Direction:</strong> {{ $message->direction }}</div>
            <div><strong>Template:</strong> {{ $message->template ?? '—' }}</div>
            <div><strong>To:</strong> {{ $message->to_number }}</div>
            <div><strong>From:</strong> {{ $message->from_number }}</div>
            <div class="col-span-2"><strong>Created:</strong> {{ $message->created_at }}</div>
        </div>

        <div class="p-4">
            <h2 class="font-semibold mb-2">Payload</h2>
            <pre class="text-xs bg-gray-50 p-3 rounded overflow-x-auto">{{ json_encode($payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
        </div>

        @if($message->error_message)
        <div class="p-4">
            <h2 class="font-semibold mb-2 text-red-700">Error</h2>
            <div class="text-sm text-red-600">{{ $message->error_message }}</div>
        </div>
        @endif

        <div class="p-4">
            <form method="POST" action="{{ route('admin.whatsapp.messages.retry', $message) }}">
                @csrf
                <button class="bg-gray-900 text-white rounded px-4 py-2">Retry Send</button>
            </form>
        </div>
    </div>
</div>
@endsection
