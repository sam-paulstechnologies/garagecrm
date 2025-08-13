{{-- resources/views/admin/clients/partials/details.blade.php --}}
<div class="flex justify-between items-center mb-4">
    <h2 class="text-2xl font-semibold text-gray-800">Client Details</h2>
    <a href="{{ route('admin.clients.edit', $client->id) }}"
       class="text-sm text-blue-600 hover:underline">✏️ Edit</a>
</div>

<div x-data="{ showAll: false }" class="text-gray-700 space-y-2">
    {{-- Basic Fields --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <p><strong>Name:</strong> {{ $client->name }}</p>
        <p><strong>Email:</strong> {{ $client->email }}</p>
        <p><strong>Phone:</strong> {{ $client->phone }}</p>
        <p><strong>Location:</strong> {{ $client->location ?? 'N/A' }}</p>
        <p><strong>Source:</strong> {{ $client->source ?? 'N/A' }}</p>
    </div>

    {{-- Expanded View --}}
    <div x-show="showAll" class="mt-4 space-y-4">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <p><strong>Last Service:</strong> {{ $client->last_service ?? 'N/A' }}</p>
            <p><strong>Created At:</strong> {{ $client->created_at->format('d M Y H:i') }}</p>
            <p><strong>Updated At:</strong> {{ $client->updated_at->format('d M Y H:i') }}</p>
            <p><strong>Created By:</strong> {{ optional($client->creator)->name ?? 'N/A' }}</p>
        </div>

        {{-- Show Client Files --}}
        <div>
            <h3 class="font-bold text-gray-800 mb-2">Client Files</h3>
            @if ($client->files->count())
                <ul class="list-disc pl-6">
                    @foreach ($client->files as $file)
                        <li>
                            <a href="{{ asset($file->file_path) }}" class="text-blue-600 hover:underline" target="_blank">
                                {{ $file->file_name }} ({{ $file->file_type }})
                            </a>
                            <span class="text-sm text-gray-500 ml-1">
                                — Uploaded: {{ \Carbon\Carbon::parse($file->uploaded_at)->format('d M Y') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-gray-500">No files uploaded.</p>
            @endif
        </div>
    </div>

    {{-- Toggle Button --}}
    <button x-on:click="showAll = !showAll" type="button" class="mt-2 text-blue-600 underline hover:text-blue-800">
        <span x-show="!showAll">+ View More</span>
        <span x-show="showAll">– Show Less</span>
    </button>
</div>
