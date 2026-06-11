{{-- resources/views/admin/clients/import-batches.blade.php --}}

@extends('layouts.app')

@section('title', 'Client Import Previews')

@section('content')
    <div class="sf-page mx-auto max-w-7xl space-y-5 px-4 py-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-wide text-orange-700 dark:text-orange-300">
                        Preview Batches
                    </p>

                    <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-slate-950 dark:text-white">
                        Client Import Previews
                    </h1>

                    <p class="mt-2 max-w-3xl text-sm font-semibold leading-6 text-slate-600 dark:text-slate-300">
                        Saved parsed import previews. These records have not created clients, vehicles, messages, or retention actions.
                    </p>
                </div>

                <a
                    href="{{ route('admin.clients.import.form') }}"
                    class="inline-flex h-10 items-center justify-center rounded-xl bg-orange-500 px-4 text-sm font-extrabold text-white shadow-lg shadow-orange-950/30 transition hover:bg-orange-600"
                >
                    Upload New File
                </a>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-950/70">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-300">File</th>
                            <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-300">Uploaded</th>
                            <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-300">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-300">Rows</th>
                            <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-300">Validation</th>
                            <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-300">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($batches as $batch)
                            <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-950/40">
                                <td class="px-5 py-4">
                                    <div class="max-w-[340px] break-words text-sm font-extrabold text-slate-950 dark:text-white">
                                        {{ $batch->original_filename }}
                                    </div>

                                    <div class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                        Uploaded by {{ $batch->uploadedBy?->name ?? 'Unknown user' }}
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                    {{ $batch->created_at?->format('d M Y H:i') }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-black text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20">
                                        {{ \Illuminate\Support\Str::headline($batch->status) }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-sm font-bold text-slate-700 dark:text-slate-200">
                                    {{ $batch->total_rows }}
                                </td>

                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-2 text-xs font-black">
                                        <span class="rounded-full bg-emerald-50 px-2 py-1 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20">Valid {{ $batch->valid_rows }}</span>
                                        <span class="rounded-full bg-amber-50 px-2 py-1 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20">Warnings {{ $batch->warning_rows }}</span>
                                        <span class="rounded-full bg-rose-50 px-2 py-1 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20">Invalid {{ $batch->invalid_rows }}</span>
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <a
                                        href="{{ route('admin.clients.import.batches.show', $batch) }}"
                                        class="inline-flex h-9 items-center justify-center rounded-xl border border-orange-200 bg-orange-50 px-3 text-xs font-extrabold text-orange-700 transition hover:bg-orange-100 hover:text-orange-800 dark:border-orange-400/20 dark:bg-orange-500/10 dark:text-orange-200 dark:hover:bg-orange-500/15"
                                    >
                                        View Preview
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-sm font-bold text-slate-500 dark:text-slate-300">
                                    No saved client import previews yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($batches->hasPages())
                <div class="border-t border-slate-200 p-4 dark:border-slate-800">
                    {{ $batches->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
