{{-- resources/views/admin/clients/import-partials/_recent_batches.blade.php --}}

@php
    $recentImportBatches = collect($recentImportBatches ?? []);
@endphp

<section class="sf-client-import-panel overflow-hidden rounded-2xl border shadow-sm">
    <div class="flex flex-col gap-3 border-b border-slate-800 p-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <h2 class="sf-client-import-title text-base font-extrabold tracking-tight">
                Recent Import Previews
            </h2>

            <p class="sf-client-import-muted mt-1 text-xs font-semibold leading-5">
                Saved previews are safe to revisit. They do not create CRM records until rows are applied.
            </p>
        </div>

        @if(\Illuminate\Support\Facades\Route::has('admin.clients.import.batches.index'))
            <a href="{{ route('admin.clients.import.batches.index') }}" class="sf-btn-secondary">
                View All
            </a>
        @endif
    </div>

    <div class="overflow-x-auto">
        <table class="sf-client-import-table min-w-full divide-y divide-slate-800 text-sm">
            <thead>
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Batch</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Uploaded</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Rows</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Validation</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Status</th>
                    <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-wide">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-800">
                @forelse($recentImportBatches as $batch)
                    <tr class="transition hover:bg-slate-800/30">
                        <td class="px-5 py-4 align-top">
                            <div class="sf-client-import-title max-w-[360px] break-words text-sm font-extrabold">
                                #{{ $batch->id }} {{ $batch->original_filename }}
                            </div>

                            <div class="sf-client-import-muted mt-1 text-xs font-semibold">
                                Uploaded by {{ $batch->uploadedBy?->name ?? 'Unknown user' }}
                            </div>
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 align-top text-sm font-semibold">
                            {{ $batch->created_at?->format('d M Y H:i') }}
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 align-top text-sm font-black">
                            {{ $batch->total_rows }}
                        </td>

                        <td class="px-5 py-4 align-top">
                            <div class="flex flex-wrap gap-2 text-xs font-black">
                                <span class="rounded-full bg-emerald-500/10 px-2 py-1 text-emerald-200 ring-1 ring-emerald-400/20">
                                    Importable {{ (int) $batch->valid_rows + (int) $batch->warning_rows }}
                                </span>
                                <span class="rounded-full bg-amber-500/10 px-2 py-1 text-amber-200 ring-1 ring-amber-400/20">
                                    Warnings {{ $batch->warning_rows }}
                                </span>
                                <span class="rounded-full bg-rose-500/10 px-2 py-1 text-rose-200 ring-1 ring-rose-400/20">
                                    Blocked {{ $batch->invalid_rows }}
                                </span>
                            </div>
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 align-top">
                            <span class="inline-flex rounded-full bg-slate-500/10 px-2.5 py-1 text-xs font-black text-slate-300 ring-1 ring-slate-400/20">
                                {{ \Illuminate\Support\Str::headline($batch->status) }}
                            </span>
                        </td>

                        <td class="whitespace-nowrap px-5 py-4 text-right align-top">
                            <a href="{{ route('admin.clients.import.batches.show', $batch) }}" class="sf-client-import-link">
                                View Preview
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10">
                            <div class="sf-client-import-soft-panel rounded-2xl border p-8 text-center">
                                <div class="sf-client-import-title text-base font-extrabold">
                                    No saved client import previews yet.
                                </div>

                                <p class="sf-client-import-muted mt-2 text-sm font-semibold">
                                    Upload a CSV or Excel file to create the first preview.
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
