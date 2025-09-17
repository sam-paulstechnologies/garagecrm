<h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center justify-between">
    <span>Notes</span>

    @if (\Illuminate\Support\Facades\Route::has('admin.clients.notes.index'))
        <a href="{{ route('admin.clients.notes.index', $client->id) }}"
           class="text-sm text-indigo-600 hover:underline">
            View all
        </a>
    @endif
</h3>

@php
    // only fetch the latest 3; eager-load author
    $notes = method_exists($client, 'notes')
        ? $client->notes()->with('creator:id,name')->latest()->take(3)->get()
        : collect();
@endphp

{{-- Inline add note --}}
<form action="{{ route('admin.clients.notes.store', $client->id) }}" method="POST" class="mb-4">
    @csrf
    <label for="note_content" class="sr-only">Add note</label>
    <textarea
        id="note_content"
        name="content"
        rows="3"
        placeholder="Type a quick note and press Add…"
        class="w-full border rounded p-3 text-sm focus:outline-none focus:ring focus:border-indigo-300"
    >{{ old('content') }}</textarea>
    <div class="mt-2 flex items-center gap-3">
        <button type="submit"
                class="inline-flex items-center px-3 py-2 text-sm font-medium rounded bg-indigo-600 text-white hover:bg-indigo-700">
            Add Note
        </button>
        @error('content')
            <span class="text-sm text-red-600">{{ $message }}</span>
        @enderror
    </div>
</form>

Recent notes (latest 3):
@forelse ($notes as $note)
    <div class="bg-gray-50 border rounded p-3 mb-3">
        <div class="text-gray-900 whitespace-pre-line">{{ $note->content ?? $note->note ?? '' }}</div>
        <div class="text-xs text-gray-500 mt-2">
            {{ optional($note->created_at)->format('M j, Y H:i') }}
            • by {{ $note->creator?->name ?? 'Unknown' }}
        </div>
    </div>
@empty
    <p class="text-sm text-gray-500">No notes available.</p>
@endforelse
