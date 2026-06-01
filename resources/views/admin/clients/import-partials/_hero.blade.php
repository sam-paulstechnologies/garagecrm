{{-- resources/views/admin/clients/import-partials/_hero.blade.php --}}

<div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-sm">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">
                Import Clients
            </h1>

            <p class="mt-2 max-w-3xl text-sm text-slate-400">
                Upload client records using CSV or Excel. Required fields are name, phone, and email.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if (file_exists(public_path('samples/sample_client_import.csv')))
                <a
                    href="{{ asset('samples/sample_client_import.csv') }}"
                    download
                    class="inline-flex h-10 items-center justify-center rounded-xl border border-blue-400/20 bg-blue-500/10 px-4 text-sm font-bold text-blue-300 transition hover:bg-blue-500/15"
                >
                    Download CSV
                </a>
            @endif

            @if (file_exists(public_path('samples/client_import_sample.xlsx')))
                <a
                    href="{{ asset('samples/client_import_sample.xlsx') }}"
                    download
                    class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-700 bg-slate-800 px-4 text-sm font-bold text-slate-200 transition hover:bg-slate-700"
                >
                    Download Excel
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.clients.index'))
                <a
                    href="{{ route('admin.clients.index') }}"
                    class="inline-flex h-10 items-center justify-center rounded-xl bg-orange-500 px-4 text-sm font-extrabold text-white shadow-lg shadow-orange-950/30 transition hover:bg-orange-600"
                >
                    ← Back to Clients
                </a>
            @endif
        </div>
    </div>
</div>