{{-- resources/views/admin/clients/import-partials/_upload_form.blade.php --}}

<div class="xl:col-span-2">
    <form
        action="{{ route('admin.clients.import') }}"
        method="POST"
        enctype="multipart/form-data"
        class="sf-client-import-panel overflow-hidden rounded-2xl border shadow-sm"
    >
        @csrf

        <div class="flex flex-col gap-4 border-b border-slate-800 p-5 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-black uppercase tracking-wide text-orange-300">
                    Upload
                </p>

                <h2 class="sf-client-import-title mt-1 text-base font-extrabold tracking-tight">
                    Upload Client File
                </h2>

                <p class="sf-client-import-muted mt-1 max-w-2xl text-sm font-medium leading-6">
                    Upload will create a preview first. Nothing is imported until rows are reviewed and applied.
                </p>
            </div>

            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-orange-500/10 text-orange-300 ring-1 ring-orange-400/20">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 15V3m0 0 4 4m-4-4L8 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M5 14v3.5A2.5 2.5 0 0 0 7.5 20h9A2.5 2.5 0 0 0 19 17.5V14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </div>
        </div>

        <div class="space-y-5 p-5">
            <div class="sf-client-import-soft-panel rounded-2xl border p-4">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="sf-client-import-title text-sm font-extrabold">
                            Accepted formats
                        </div>

                        <p class="sf-client-import-muted mt-1 text-xs font-semibold leading-5">
                            CSV, XLS, or XLSX. Required columns are <span class="font-black">name</span> and <span class="font-black">phone or whatsapp</span>.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @if (\Illuminate\Support\Facades\Route::has('admin.clients.import.sample'))
                            <a href="{{ route('admin.clients.import.sample') }}" download class="sf-btn-secondary">
                                Sample Sheet
                            </a>
                        @endif

                        @if (file_exists(public_path('samples/client_import_sample.xlsx')))
                            <a href="{{ asset('samples/client_import_sample.xlsx') }}" download class="sf-btn-secondary">
                                Excel Sample
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div>
                <label for="file" class="sf-client-import-muted mb-2 block text-xs font-extrabold uppercase tracking-wide">
                    Upload File <span class="text-red-400">*</span>
                </label>

                <input
                    type="file"
                    name="file"
                    id="file"
                    accept=".xlsx,.xls,.csv"
                    required
                    class="sf-client-import-input block w-full rounded-xl border px-3 py-2 text-sm font-semibold file:mr-4 file:rounded-lg file:border-0 file:bg-orange-500 file:px-4 file:py-2 file:text-sm file:font-extrabold file:text-white hover:file:bg-orange-600"
                >

                <p class="sf-client-import-muted mt-2 text-xs font-medium">
                    The preview will classify importable rows, warnings, blocked rows, duplicate clients, service history, and retention suggestions.
                </p>

                @error('file')
                    <div class="mt-2 text-xs font-bold text-red-300">
                        {{ $message }}
                    </div>
                @enderror
            </div>
        </div>

        <div class="border-t border-slate-800 p-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="sf-client-import-muted text-xs font-semibold">
                    No clients, vehicles, messages, or retention actions are created during upload.
                </p>

                <div class="flex flex-col gap-2 sm:flex-row">
                    @if(\Illuminate\Support\Facades\Route::has('admin.clients.index'))
                        <a href="{{ route('admin.clients.index') }}" class="sf-btn-secondary">
                            Cancel
                        </a>
                    @endif

                    <button class="sf-btn-primary" type="submit">
                        Upload and Preview
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
