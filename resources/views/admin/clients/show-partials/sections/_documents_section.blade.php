{{-- resources/views/admin/clients/show-partials/sections/_documents_section.blade.php --}}

@php
    $documents = collect($client->files ?? $client->documents ?? []);
    $documentCount = $documents->count();

    $uploadRoute = \Illuminate\Support\Facades\Route::has('admin.client-files.store')
        ? route('admin.client-files.store', $client->id)
        : (
            \Illuminate\Support\Facades\Route::has('admin.clients.files.store')
                ? route('admin.clients.files.store', $client->id)
                : '#'
        );

    $canUpload = $uploadRoute !== '#';

    $documentTypes = [
        'Invoice',
        'Job Card',
        'Insurance',
        'Mulkia',
        'Other',
    ];

    $formatDate = function ($value) {
        if (!$value) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d M Y');
        } catch (\Throwable $e) {
            return $value;
        }
    };
@endphp

<style>
    .sf-doc-shell {
        border-color: rgba(30, 41, 59, 1);
        background: rgba(15, 23, 42, 0.70);
        color: #ffffff;
    }

    .sf-doc-title {
        color: #ffffff;
    }

    .sf-doc-muted {
        color: #cbd5e1;
    }

    .sf-doc-count {
        border-color: rgba(148, 163, 184, 0.20);
        background: rgba(148, 163, 184, 0.10);
        color: #cbd5e1;
    }

    .sf-doc-form {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(2, 6, 23, 0.35);
    }

    .sf-doc-label {
        color: #ffffff;
    }

    .sf-doc-input,
    .sf-doc-select {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(2, 6, 23, 0.55);
        color: #ffffff;
    }

    .sf-doc-empty {
        border-color: rgba(148, 163, 184, 0.16);
        background: rgba(2, 6, 23, 0.35);
        color: #94a3b8;
    }

    .sf-doc-item {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(2, 6, 23, 0.35);
    }

    html[data-theme="light"] .sf-doc-shell {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-doc-title {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-doc-muted {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-doc-count {
        border-color: #cbd5e1 !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-doc-form {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-doc-label {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-doc-input,
    html[data-theme="light"] .sf-doc-select {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-doc-empty {
        border-color: #d9e1ec !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-doc-item {
        border-color: #d9e1ec !important;
        background: #f8fafc !important;
    }
</style>

<section id="documents" class="sf-doc-shell rounded-2xl border p-5 shadow-sm">
    <div class="mb-5 flex items-start justify-between gap-4">
        <div>
            <h2 class="sf-doc-title text-lg font-extrabold tracking-tight">
                Documents
            </h2>

            <p class="sf-doc-muted mt-1 text-sm font-medium leading-6">
                Upload and manage invoices, job cards, insurance, mulkia, and other client documents.
            </p>
        </div>

        <span class="sf-doc-count inline-flex shrink-0 rounded-full border px-4 py-2 text-center text-sm font-black">
            {{ $documentCount }} document(s)
        </span>
    </div>

    <div class="sf-doc-form rounded-2xl border p-5">
        <h3 class="sf-doc-title text-base font-extrabold">
            Upload Document
        </h3>

        <form
            action="{{ $uploadRoute }}"
            method="POST"
            enctype="multipart/form-data"
            class="mt-4 space-y-4"
        >
            @csrf

            <div>
                <label for="document_file" class="sf-doc-label mb-2 block text-xs font-black uppercase tracking-wide">
                    Upload Document
                </label>

                <input
                    type="file"
                    id="document_file"
                    name="file"
                    {{ $canUpload ? '' : 'disabled' }}
                    class="sf-doc-input block w-full rounded-xl border px-3 py-2 text-sm font-semibold file:mr-4 file:rounded-lg file:border-0 file:bg-orange-500 file:px-4 file:py-2 file:text-sm file:font-black file:text-white hover:file:bg-orange-600"
                >
            </div>

            <div>
                <label for="document_type" class="sf-doc-label mb-2 block text-xs font-black uppercase tracking-wide">
                    Document Type
                </label>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <select
                        id="document_type"
                        name="file_type"
                        {{ $canUpload ? '' : 'disabled' }}
                        class="sf-doc-select h-11 flex-1 rounded-xl border px-3 text-sm font-semibold outline-none"
                    >
                        @foreach($documentTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>

                    <button
                        type="submit"
                        {{ $canUpload ? '' : 'disabled' }}
                        class="inline-flex h-11 items-center justify-center rounded-xl bg-orange-500 px-5 text-sm font-extrabold text-white shadow-lg shadow-orange-950/20 transition hover:bg-orange-600 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Upload
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="mt-5">
        @if($documents->isNotEmpty())
            <div class="space-y-3">
                @foreach($documents->take(5) as $document)
                    <div class="sf-doc-item rounded-2xl border p-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="sf-doc-title text-sm font-black">
                                    {{ $document->file_name ?? $document->name ?? 'Document' }}
                                </p>

                                <p class="sf-doc-muted mt-1 text-xs font-medium">
                                    {{ $document->file_type ?? $document->type ?? 'File' }}
                                    @if($formatDate($document->created_at ?? null))
                                        · {{ $formatDate($document->created_at) }}
                                    @endif
                                </p>
                            </div>

                            @if(!empty($document->file_path))
                                <a
                                    href="{{ asset('storage/' . $document->file_path) }}"
                                    target="_blank"
                                    class="inline-flex w-fit rounded-xl border border-blue-400/20 bg-blue-500/10 px-3 py-2 text-xs font-black text-blue-300 transition hover:bg-blue-500/15"
                                >
                                    View
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="sf-doc-empty rounded-2xl border border-dashed p-8 text-center text-sm font-semibold">
                No documents uploaded yet.
            </div>
        @endif
    </div>
</section>