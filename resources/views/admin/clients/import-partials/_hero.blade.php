{{-- resources/views/admin/clients/import-partials/_hero.blade.php --}}

<div class="sf-client-import-panel rounded-2xl border p-5 shadow-sm">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-black uppercase tracking-wide text-orange-700 dark:text-orange-300">
                Client Data
            </p>

            <h1 class="sf-client-import-title mt-1 text-3xl font-extrabold tracking-tight">
                Client Import
            </h1>

            <p class="sf-client-import-muted mt-2 max-w-3xl text-sm font-medium leading-6">
                Upload customer contacts, vehicles, and service history from CSV or Excel. Every upload creates a review preview first.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 lg:justify-end">
            @if (\Illuminate\Support\Facades\Route::has('admin.clients.import.sample'))
                <a
                    href="{{ route('admin.clients.import.sample') }}"
                    download
                    class="sf-btn-secondary gap-2"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 3v12m0 0 4-4m-4 4-4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M5 19h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    Sample Sheet
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.clients.import.batches.index'))
                <a
                    href="{{ route('admin.clients.import.batches.index') }}"
                    class="sf-btn-secondary"
                >
                    Recent Previews
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.clients.index'))
                <a
                    href="{{ route('admin.clients.index') }}"
                    class="sf-btn-primary"
                >
                    Back to Clients
                </a>
            @endif
        </div>
    </div>
</div>
