{{-- resources/views/admin/clients/import-partials/_side_notes.blade.php --}}

<aside
    class="sf-client-import-panel rounded-2xl border p-4 shadow-sm"
    data-client-import-collapsible
    data-storage-key="sayara.clientImport.rulesCollapsed"
    data-default-collapsed="true"
>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between xl:flex-col xl:items-start">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="sf-client-import-title text-base font-extrabold tracking-tight">
                    Import Rules
                </h2>

                <span class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-black text-orange-300">
                    Preview first
                </span>
            </div>

            <div class="mt-2 flex flex-wrap gap-2">
                <span class="sf-client-import-filter-pill inline-flex rounded-full border px-3 py-1 text-xs font-bold">Required fields</span>
                <span class="sf-client-import-filter-pill inline-flex rounded-full border px-3 py-1 text-xs font-bold">Duplicate detection</span>
                <span class="sf-client-import-filter-pill inline-flex rounded-full border px-3 py-1 text-xs font-bold">Review before apply</span>
            </div>
        </div>

        <button
            type="button"
            class="sf-btn-secondary"
            data-client-import-collapsible-toggle
            aria-expanded="false"
        >
            Show rules
        </button>
    </div>

    <div class="mt-5 hidden space-y-3" data-client-import-collapsible-body>
        <div class="sf-client-import-soft-panel rounded-2xl border p-4">
            <h3 class="sf-client-import-title text-sm font-extrabold">
                Required fields
            </h3>

            <div class="mt-3 flex flex-wrap gap-2">
                <span class="inline-flex rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-black text-orange-300 ring-1 ring-orange-400/20">name</span>
                <span class="inline-flex rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-black text-orange-300 ring-1 ring-orange-400/20">phone or whatsapp</span>
            </div>
        </div>

        <div class="sf-client-import-soft-panel rounded-2xl border p-4">
            <h3 class="sf-client-import-title text-sm font-extrabold">
                Optional service history
            </h3>

            <p class="sf-client-import-muted mt-2 text-sm font-semibold leading-6">
                Add vehicle, last service, mileage, invoice, insurance, and mulkia dates to prepare follow-up opportunities.
            </p>
        </div>

        <div class="sf-client-import-soft-panel rounded-2xl border p-4">
            <h3 class="sf-client-import-title text-sm font-extrabold">
                Duplicate detection
            </h3>

            <p class="sf-client-import-muted mt-2 text-sm font-semibold leading-6">
                Existing clients are matched inside the current company using phone, WhatsApp, or email.
            </p>
        </div>

        <div class="sf-client-import-soft-panel rounded-2xl border p-4">
            <h3 class="sf-client-import-title text-sm font-extrabold">
                Review before apply
            </h3>

            <p class="sf-client-import-muted mt-2 text-sm font-semibold leading-6">
                Upload creates a saved preview. Managers can review, approve, skip, or reject rows before any CRM records are created.
            </p>
        </div>
    </div>
</aside>
