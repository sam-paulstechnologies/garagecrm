<h3 class="text-xl font-semibold text-gray-800 mb-2">Activity Log</h3>
@forelse ($client->activities ?? [] as $activity)
    <div class="mb-2 p-3 border rounded bg-gray-50">
        <p class="text-gray-700">{{ $activity->description }}</p>
        <p class="text-sm text-gray-400">{{ $activity->created_at->format('d M Y H:i') }}</p>
    </div>
@empty
    <p class="text-gray-500">No activities found.</p>
@endforelse
