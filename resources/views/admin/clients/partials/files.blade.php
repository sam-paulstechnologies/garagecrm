<h3 class="text-lg font-semibold text-gray-800 mb-2 flex items-center justify-between">
    <span>Files</span>

    <span class="flex items-center gap-3">
        @if (\Illuminate\Support\Facades\Route::has('admin.clients.files.index'))
            <a href="{{ route('admin.clients.files.index', $client->id) }}"
               class="text-sm text-indigo-600 hover:underline">View all</a>
        @endif
        @if (\Illuminate\Support\Facades\Route::has('admin.clients.files.create'))
            <a href="{{ route('admin.clients.files.create', $client->id) }}"
               class="text-xs px-2 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700">+ Add File</a>
        @endif
    </span>
</h3>

@php
    // Latest 3 client files
    $files = method_exists($client, 'files')
        ? $client->files()->latest()->take(3)->get()
        : collect();
@endphp

@forelse ($files as $file)
    @php
        $label = $file->name
            ?? $file->original_name
            ?? (property_exists($file, 'filename') ? $file->filename : null)
            ?? ('File #' . $file->id);
    @endphp

    <div class="py-2 text-sm flex items-center justify-between">
        <div class="truncate">• {{ $label }}</div>
        <div class="text-xs text-gray-500 ml-3 shrink-0">
            {{ optional($file->created_at)->format('M j, Y H:i') }}
        </div>
    </div>
@empty
    <p class="text-sm text-gray-500">No files uploaded.</p>
@endforelse
