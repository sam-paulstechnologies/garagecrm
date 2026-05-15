{{-- resources/views/admin/clients/partials/files.blade.php --}}

@php
    // Latest 3 client files
    $files = method_exists($client, 'files')
        ? $client->files()->latest()->take(3)->get()
        : collect();
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="sf-section-title">
                Files
            </h3>

            <p class="sf-section-subtitle">
                Latest uploaded files linked to this client.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if (\Illuminate\Support\Facades\Route::has('admin.clients.files.index'))
                <a href="{{ route('admin.clients.files.index', $client->id) }}" class="sf-btn-secondary">
                    View All
                </a>
            @endif

            @if (\Illuminate\Support\Facades\Route::has('admin.clients.files.create'))
                <a href="{{ route('admin.clients.files.create', $client->id) }}" class="sf-btn-primary">
                    + Add File
                </a>
            @endif
        </div>
    </div>

    {{-- File List --}}
    @forelse ($files as $file)
        @php
            $label = $file->name
                ?? $file->original_name
                ?? $file->filename
                ?? $file->file_name
                ?? ('File #' . $file->id);

            $fileType = $file->type
                ?? $file->file_type
                ?? $file->mime_type
                ?? null;
        @endphp

        <div class="rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 transition hover:border-orange-400/30 hover:bg-slate-900">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-blue-500/10 text-blue-300 ring-1 ring-blue-400/20">
                            📄
                        </div>

                        <div class="min-w-0">
                            <div class="truncate font-extrabold text-white">
                                {{ $label }}
                            </div>

                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs font-medium text-slate-500">
                                <span>
                                    {{ optional($file->created_at)->format('M j, Y H:i') ?? 'No date' }}
                                </span>

                                @if($fileType)
                                    <span class="sf-badge-slate">
                                        {{ ucfirst(str_replace('_', ' ', $fileType)) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    $fileUrl = null;

                    if (!empty($file->file_path)) {
                        $fileUrl = asset($file->file_path);
                    } elseif (!empty($file->path)) {
                        $fileUrl = asset($file->path);
                    } elseif (!empty($file->document_path)) {
                        $fileUrl = asset('storage/' . $file->document_path);
                    }
                @endphp

                @if($fileUrl)
                    <a href="{{ $fileUrl }}" target="_blank" class="sf-link shrink-0">
                        View
                    </a>
                @endif

            </div>
        </div>
    @empty
        <div class="sf-empty">
            No files uploaded.
        </div>
    @endforelse

</div>