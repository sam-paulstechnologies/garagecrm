@extends('layouts.app')

@section('title', 'AI Suggestions')

@section('content')
<div class="max-w-7xl mx-auto p-6">

    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">Pending AI Suggestions</h1>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 rounded bg-green-50 text-green-800">{{ session('success') }}</div>
    @endif

    @if($suggestions->isEmpty())
        <div class="border rounded p-4 text-gray-600">
            No pending suggestions.
        </div>
    @else
        @foreach($suggestions as $s)
            <div class="border rounded p-4 mb-4 bg-white">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-sm text-gray-600">
                        Conversation: {{ $s->conversation_id ?? '—' }} | To: {{ $s->to_number ?? '—' }}
                    </div>

                    <div class="text-xs">
                        <span class="px-2 py-1 rounded bg-gray-100 text-gray-700">
                            Confidence: {{ $s->confidence !== null ? number_format((float)$s->confidence, 2) : '—' }}
                        </span>
                    </div>
                </div>

                <div class="text-gray-900 mb-3">
                    {{ $s->suggestion_text }}
                </div>

                <div class="flex gap-4">
                    <form method="POST" action="{{ route('admin.ai.suggestions.approve', $s->id) }}">
                        @csrf
                        <button class="text-green-600 hover:underline text-sm">Approve & Send</button>
                    </form>

                    <form method="POST" action="{{ route('admin.ai.suggestions.reject', $s->id) }}">
                        @csrf
                        <button class="text-red-600 hover:underline text-sm">Reject</button>
                    </form>
                </div>
            </div>
        @endforeach

        <div class="mt-4">
            {{ $suggestions->links() }}
        </div>
    @endif

</div>
@endsection
