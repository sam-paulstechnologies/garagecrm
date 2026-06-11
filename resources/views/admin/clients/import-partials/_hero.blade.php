{{-- resources/views/admin/clients/import-partials/_hero.blade.php --}}

<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-black uppercase tracking-wide text-orange-700 dark:text-orange-300">
                Client Data
            </p>

            <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-slate-950 dark:text-white">
                Import Clients
            </h1>

            <p class="mt-2 max-w-3xl text-sm font-semibold leading-6 text-slate-600 dark:text-slate-300">
                Upload historical garage customer data using CSV or Excel. Required fields are name and phone or WhatsApp.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 lg:justify-end">
            @if (\Illuminate\Support\Facades\Route::has('admin.clients.import.sample'))
                <a
                    href="{{ route('admin.clients.import.sample') }}"
                    download
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 text-sm font-extrabold text-blue-700 transition hover:bg-blue-100 hover:text-blue-800 dark:border-blue-400/30 dark:bg-blue-500/15 dark:text-blue-200 dark:hover:bg-blue-500/25 dark:hover:text-blue-100"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 3v12m0 0 4-4m-4 4-4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M5 19h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    Download Sample Sheet
                </a>
            @endif

            @if (file_exists(public_path('samples/client_import_sample.xlsx')))
                <a
                    href="{{ asset('samples/client_import_sample.xlsx') }}"
                    download
                    class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50 hover:text-slate-950 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                >
                    Download Excel
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.clients.import.batches.index'))
                <a
                    href="{{ route('admin.clients.import.batches.index') }}"
                    class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50 hover:text-slate-950 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                >
                    Recent Previews
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.clients.index'))
                <a
                    href="{{ route('admin.clients.index') }}"
                    class="inline-flex h-10 items-center justify-center rounded-xl bg-orange-500 px-4 text-sm font-extrabold text-white shadow-lg shadow-orange-950/30 transition hover:bg-orange-600"
                >
                    Back to Clients
                </a>
            @endif
        </div>
    </div>
</div>
