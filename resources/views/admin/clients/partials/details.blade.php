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

<div x-data="{ showAll: false }" class="space-y-5">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="sf-section-title">
                Client Details
            </h2>

            <p class="sf-section-subtitle">
                Basic profile, contact, source, and account information.
            </p>
        </div>

        <a href="{{ route('admin.clients.edit', $client->id) }}" class="sf-btn-secondary">
            ✏️ Edit
        </a>
    </div>

    {{-- Basic Fields --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">

        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                Name
            </div>
            <div class="mt-1 font-extrabold text-white">
                {{ $client->name ?? '—' }}
            </div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                Email
            </div>
            <div class="mt-1 break-words font-bold text-slate-200">
                {{ $client->email ?? '—' }}
            </div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                Phone
            </div>
            <div class="mt-1 font-bold text-slate-200">
                {{ $client->phone ?? $client->whatsapp ?? '—' }}
            </div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                Location
            </div>
            <div class="mt-1 font-bold text-slate-200">
                {{ $client->location ?? $client->city ?? $client->country ?? 'N/A' }}
            </div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4 sm:col-span-2">
            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                Source
            </div>
            <div class="mt-1 font-bold text-slate-200">
                {{ $client->source ?? 'N/A' }}
            </div>
        </div>

    </div>

    {{-- Expanded View --}}
    <div x-cloak x-show="showAll" x-transition class="space-y-5">

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">

            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Last Service
                </div>
                <div class="mt-1 font-bold text-slate-200">
                    {{ $client->last_service ?? 'N/A' }}
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Created At
                </div>
                <div class="mt-1 font-bold text-slate-200">
                    {{ optional($client->created_at)->format('d M Y H:i') ?? '—' }}
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Updated At
                </div>
                <div class="mt-1 font-bold text-slate-200">
                    {{ optional($client->updated_at)->format('d M Y H:i') ?? '—' }}
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Created By
                </div>
                <div class="mt-1 font-bold text-slate-200">
                    {{ $client->creator?->name ?? 'N/A' }}
                </div>
            </div>

        </div>

        {{-- Client Files --}}
        <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="font-extrabold text-white">
                        Client Files
                    </h3>

                    <p class="mt-1 text-xs font-medium text-slate-500">
                        Files attached directly to this client profile.
                    </p>
                </div>

                <span class="sf-badge-slate">
                    {{ $fileCount }} file(s)
                </span>
            </div>

            @if ($fileCount > 0)
                <ul class="mt-4 space-y-2">
                    @foreach ($files as $file)
                        <li class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <a href="{{ asset($file->file_path) }}"
                                       class="truncate font-bold text-orange-300 hover:text-orange-200 hover:underline"
                                       target="_blank">
                                        {{ $file->file_name ?? 'File' }}
                                    </a>

                                    <div class="mt-1 text-xs font-medium text-slate-500">
                                        @if(!empty($file->file_type))
                                            {{ ucfirst(str_replace('_', ' ', $file->file_type)) }}
                                            ·
                                        @endif

                                        Uploaded:
                                        {{ optional($file->uploaded_at)->format('d M Y') ?? '—' }}
                                    </div>
                                </div>

                                <a href="{{ asset($file->file_path) }}"
                                   target="_blank"
                                   class="sf-link shrink-0">
                                    View
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="mt-4 sf-empty">
                    No files uploaded.
                </div>
            @endif
        </div>
    </div>

    {{-- Toggle --}}
    <button
        x-on:click="showAll = !showAll"
        type="button"
        class="sf-link">
        <span x-show="!showAll">+ View More</span>
        <span x-show="showAll">– Show Less</span>
    </button>

</div>