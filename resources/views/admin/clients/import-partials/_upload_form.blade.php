{{-- resources/views/admin/clients/import-partials/_upload_form.blade.php --}}

<style>
    .sf-import-card {
        border-color: rgba(30, 41, 59, 1);
        background: rgba(15, 23, 42, 0.70);
        color: #ffffff;
    }

    .sf-import-card-border {
        border-color: rgba(30, 41, 59, 1);
    }

    .sf-import-title {
        color: #ffffff;
    }

    .sf-import-subtitle {
        color: #cbd5e1;
    }

    .sf-import-label {
        color: #94a3b8;
    }

    .sf-import-help {
        color: #cbd5e1;
    }

    .sf-import-input {
        border-color: #334155;
        background: rgba(2, 6, 23, 0.70);
        color: #e2e8f0;
    }

    .sf-import-sample-box {
        border-color: rgba(96, 165, 250, 0.20);
        background: rgba(59, 130, 246, 0.10);
    }

    .sf-import-sample-title {
        color: #93c5fd;
    }

    .sf-import-sample-text {
        color: #dbeafe;
    }

    .sf-import-upload-icon {
        color: #c2410c;
    }

    html[data-theme="light"] .sf-import-card {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-import-card-border {
        border-color: #e2e8f0 !important;
    }

    html[data-theme="light"] .sf-import-title {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-import-subtitle,
    html[data-theme="light"] .sf-import-label,
    html[data-theme="light"] .sf-import-help {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-import-input {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-import-sample-box {
        border-color: #bfdbfe !important;
        background: #eff6ff !important;
    }

    html[data-theme="light"] .sf-import-sample-title {
        color: #1d4ed8 !important;
    }

    html[data-theme="light"] .sf-import-sample-text {
        color: #1e3a8a !important;
    }

    html[data-theme="light"] .sf-import-upload-icon {
        color: #9a3412 !important;
    }
</style>

<div class="lg:col-span-2">
    <form
        action="{{ route('admin.clients.import') }}"
        method="POST"
        enctype="multipart/form-data"
        class="sf-import-card overflow-hidden rounded-2xl border shadow-sm"
    >
        @csrf

        <div class="sf-import-card-border flex items-start justify-between gap-4 border-b p-5">
            <div>
                <h2 class="sf-import-title text-base font-extrabold tracking-tight">
                    Upload Client File
                </h2>

                <p class="sf-import-subtitle mt-1 text-xs font-medium">
                    Accepted formats: .xlsx, .xls, .csv. Use the sample file to avoid failed rows.
                </p>
            </div>

            <div class="sf-import-upload-icon flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-orange-500/10 text-xl ring-1 ring-orange-400/20">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 15V3m0 0 4 4m-4-4L8 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M5 14v3.5A2.5 2.5 0 0 0 7.5 20h9A2.5 2.5 0 0 0 19 17.5V14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </div>
        </div>

        <div class="space-y-5 p-5">

            {{-- Sample Downloads --}}
            <div class="sf-import-sample-box rounded-2xl border p-5">
                <div class="sf-import-sample-title font-extrabold">
                    Sample Files
                </div>

                <p class="sf-import-sample-text mt-1 text-sm font-semibold leading-6">
                    Download the sample sheet, fill your client data, and upload it here.
                </p>

                <div class="mt-4 flex flex-wrap gap-2">
                    @if (\Illuminate\Support\Facades\Route::has('admin.clients.import.sample'))
                        <a
                            href="{{ route('admin.clients.import.sample') }}"
                            download
                            class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-blue-400/30 bg-blue-500/15 px-4 text-sm font-extrabold text-blue-700 transition hover:bg-blue-500/25 hover:text-blue-800 dark:text-blue-200 dark:hover:text-blue-100"
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
                            class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50 hover:text-slate-950 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 3v12m0 0 4-4m-4 4-4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M5 19h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                            Download Sample Excel
                        </a>
                    @endif
                </div>
            </div>

            {{-- File Upload --}}
            <div>
                <label for="file" class="sf-import-label mb-2 block text-xs font-extrabold uppercase tracking-wide">
                    Upload File <span class="text-red-400">*</span>
                </label>

                <input
                    type="file"
                    name="file"
                    id="file"
                    accept=".xlsx,.xls,.csv"
                    required
                    class="sf-import-input block w-full rounded-xl border px-3 py-2 text-sm font-semibold file:mr-4 file:rounded-lg file:border-0 file:bg-orange-500 file:px-4 file:py-2 file:text-sm file:font-extrabold file:text-white hover:file:bg-orange-600 focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-500/20"
                >

                <p class="sf-import-help mt-2 text-xs font-medium">
                    Upload CSV or Excel file with required columns: name and phone or WhatsApp. This step previews retention suggestions only.
                </p>

                @error('file')
                    <div class="mt-2 text-xs font-bold text-red-300">
                        {{ $message }}
                    </div>
                @enderror
            </div>
        </div>

        <div class="sf-import-card-border border-t p-5">
            <div class="flex flex-wrap gap-2">
                <button
                    class="inline-flex h-10 items-center justify-center rounded-xl bg-orange-500 px-4 text-sm font-extrabold text-white shadow-lg shadow-orange-950/30 transition hover:bg-orange-600"
                    type="submit"
                >
                    Preview Retention Opportunities
                </button>

                @if(\Illuminate\Support\Facades\Route::has('admin.clients.index'))
                    <a
                        href="{{ route('admin.clients.index') }}"
                        class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50 hover:text-slate-950 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        Cancel
                    </a>
                @endif
            </div>
        </div>
    </form>
</div>
