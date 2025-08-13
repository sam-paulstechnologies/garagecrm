<h3 class="text-xl font-semibold text-gray-800 mb-2">Notes</h3>
@forelse ($client->notes ?? [] as $note)
    <div class="mb-2 p-3 border rounded bg-gray-50">
        <p class="text-gray-700">{{ $note->content }}</p>
        <p class="text-sm text-gray-400">{{ $note->created_at->format('d M Y H:i') }}</p>
    </div>
@empty
    <p class="text-gray-500">No notes available.</p>
@endforelse
