{{-- resources/views/admin/clients/partials/documents.blade.php --}}

@php
    $documents = $client->documents instanceof \Illuminate\Support\Collection
        ? $client->documents
        : collect();

    $docTypeBadge = function ($type) {
        $type = strtolower((string) $type);

        return match ($type) {
            'invoice' => 'sf-badge-orange',
            'job_card' => 'sf-badge-blue',
            'insurance' => 'sf-badge-green',
            'mulkia' => 'sf-badge-yellow',
            default => 'sf-badge-slate',
        };
    };
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="sf-section-title">
                Documents
            </h2>

            <p class="sf-section-subtitle">
                Upload and manage invoices, job cards, insurance, mulkia, and other client documents.
            </p>
        </div>

        <span class="sf-badge-slate">
            {{ $documents->count() }} document(s)
        </span>
    </div>

    {{-- Upload --}}
    <form method="POST"
          action="{{ route('admin.clients.documents.store', $client) }}"
          enctype="multipart/form-data"
          class="rounded-3xl border border-white/10 bg-slate-950/60 p-4">
        @csrf

        <div class="space-y-4">
            <div>
                <label class="sf-label">
                    Upload Document
                </label>

                <input type="file"
                       name="document"
                       required
                       class="block w-full rounded-xl border border-white/10 bg-slate-950/70 px-3 py-2 text-sm text-slate-200 file:mr-4 file:rounded-lg file:border-0 file:bg-orange-500 file:px-4 file:py-2 file:text-sm file:font-extrabold file:text-white hover:file:bg-orange-600 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400">

                @error('document')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto]">
                <div>
                    <label class="sf-label">
                        Document Type
                    </label>

                    <select name="type" class="sf-select">
                        <option value="invoice">Invoice</option>
                        <option value="job_card">Job Card</option>
                        <option value="insurance">Insurance</option>
                        <option value="mulkia">Mulkia</option>
                        <option value="other">Other</option>
                    </select>

                    @error('type')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="flex items-end">
                    <button type="submit" class="sf-btn-primary w-full sm:w-auto">
                        Upload
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- List --}}
    @if($documents->isEmpty())
        <div class="sf-empty">
            No documents uploaded yet.
        </div>
    @else
        <ul class="space-y-3">
            @foreach($documents as $doc)
                <li class="rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 transition hover:border-orange-400/30 hover:bg-slate-900">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                        <div class="min-w-0">
                            <div class="truncate font-extrabold text-white">
                                {{ $doc->document_name ?? 'Document' }}
                            </div>

                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="{{ $docTypeBadge($doc->type ?? 'document') }}">
                                    {{ ucfirst(str_replace('_', ' ', $doc->type ?? 'document')) }}
                                </span>

                                @if(!empty($doc->created_at))
                                    <span class="text-xs font-medium text-slate-500">
                                        Uploaded {{ $doc->created_at->format('d M Y') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if(!empty($doc->document_path))
                            <a href="{{ asset('storage/'.$doc->document_path) }}"
                               target="_blank"
                               class="sf-link shrink-0">
                                View
                            </a>
                        @else
                            <span class="text-xs font-bold text-slate-600">
                                No file
                            </span>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

</div>