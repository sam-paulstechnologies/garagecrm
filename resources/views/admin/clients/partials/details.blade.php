{{-- resources/views/admin/clients/partials/details.blade.php --}}

@php
/**
 * ------------------------------------------------------------
 * Client Details – Defensive View Contract
 * ------------------------------------------------------------
 * Never assume optional relationships exist.
 */

$files = method_exists($client, 'files')
    ? ($client->files ?? collect())
    : collect();

$fileCount = $files instanceof \Illuminate\Support\Collection
    ? $files->count()
    : 0;
@endphp

<div class="flex justify-between items-center mb-4">
    <h2 class="text-2xl font-semibold text-gray-800">Client Details</h2>
    <a href="{{ route('admin.clients.edit', $client->id) }}"
       class="text-sm text-blue-600 hover:underline">✏️ Edit</a>
</div>

<div x-data="{ showAll: false }" class="text-gray-700 space-y-2">

    {{-- Basic Fields --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <p><strong>Name:</strong> {{ $client->name }}</p>
        <p><strong>Email:</strong> {{ $client->email ?? '—' }}</p>
        <p><strong>Phone:</strong> {{ $client->phone ?? '—' }}</p>
        <p><strong>Location:</strong> {{ $client->location ?? 'N/A' }}</p>
        <p><strong>Source:</strong> {{ $client->source ?? 'N/A' }}</p>
    </div>

    {{-- Expanded View --}}
    <div x-show="showAll" class="mt-4 space-y-4">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <p><strong>Last Service:</strong> {{ $client->last_service ?? 'N/A' }}</p>
            <p><strong>Created At:</strong> {{ optional($client->created_at)->format('d M Y H:i') }}</p>
            <p><strong>Updated At:</strong> {{ optional($client->updated_at)->format('d M Y H:i') }}</p>
            <p><strong>Created By:</strong> {{ $client->creator?->name ?? 'N/A' }}</p>
        </div>

        {{-- Client Files --}}
        <div>
            <h3 class="font-bold text-gray-800 mb-2">Client Files</h3>

            @if ($fileCount > 0)
                <ul class="list-disc pl-6 space-y-1">
                    @foreach ($files as $file)
                        <li>
                            <a href="{{ asset($file->file_path) }}"
                               class="text-blue-600 hover:underline"
                               target="_blank">
                                {{ $file->file_name ?? 'File' }}
                                @if(!empty($file->file_type))
                                    ({{ $file->file_type }})
                                @endif
                            </a>

                            <span class="text-sm text-gray-500 ml-1">
                                — Uploaded:
                                {{ optional($file->uploaded_at)->format('d M Y') ?? '—' }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-gray-500">No files uploaded.</p>
            @endif
        </div>
    </div>

    {{-- Toggle --}}
    <button
        x-on:click="showAll = !showAll"
        type="button"
        class="mt-2 text-blue-600 underline hover:text-blue-800">
        <span x-show="!showAll">+ View More</span>
        <span x-show="showAll">– Show Less</span>
    </button>
</div>
