@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Notes — {{ $client->name }}</h1>
        <a href="{{ route('admin.clients.show', $client->id) }}" class="text-indigo-600 hover:underline">← Back to Client</a>
    </div>

    {{-- Quick add --}}
    <div class="bg-white rounded shadow p-4">
        <form action="{{ route('admin.clients.notes.store', $client->id) }}" method="POST">
            @csrf
            <label for="note_content" class="block text-sm text-gray-700 mb-1">Add a note</label>
            <textarea id="note_content" name="content" rows="3"
                      class="w-full border rounded p-3 text-sm focus:outline-none focus:ring focus:border-indigo-300"
                      placeholder="Type a note and press Add…">{{ old('content') }}</textarea>
            <div class="mt-3 flex items-center gap-3">
                <button type="submit"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium rounded bg-indigo-600 text-white hover:bg-indigo-700">
                    Add Note
                </button>
                @error('content')<span class="text-sm text-red-600">{{ $message }}</span>@enderror
            </div>
        </form>
    </div>

    {{-- Notes list --}}
    <div class="bg-white rounded shadow divide-y">
        @forelse ($notes as $note)
            <div class="p-4">
                <div class="text-gray-900 whitespace-pre-line">{{ $note->content }}</div>
                <div class="text-xs text-gray-500 mt-2">
                    {{ optional($note->created_at)->format('M j, Y H:i') }}
                    • by {{ $note->creator?->name ?? 'Unknown' }}
                </div>
            </div>
        @empty
            <div class="p-4 text-sm text-gray-500">No notes yet.</div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div>{{ $notes->links() }}</div>
</div>
@endsection
