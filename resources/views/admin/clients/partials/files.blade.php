<h3 class="text-xl font-semibold text-gray-800 mb-2">Files</h3>
@forelse ($client->files ?? [] as $file)
    <p class="text-gray-700">
        • <a href="{{ asset($file->file_path) }}" target="_blank" class="text-blue-600 underline">
            {{ $file->file_name }}
        </a>
    </p>
@empty
    <p class="text-gray-500">No files uploaded.</p>
@endforelse
