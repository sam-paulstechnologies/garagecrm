{{-- resources/views/admin/leads/index-partials/_hero.blade.php --}}

<div class="sf-leads-panel rounded-2xl border p-5 shadow-sm">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="sf-kicker">Lead Command Center</div>

            <h1 class="sf-leads-title mt-3 text-3xl font-extrabold tracking-tight">
                {{ $pageTitle ?? 'Leads' }}
            </h1>

            <p class="sf-leads-muted mt-2 max-w-3xl text-sm font-medium">
                {{ $pageSubtitle ?? 'Manage leads, qualification flow, follow-ups, WhatsApp status, and lead buckets.' }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(\Illuminate\Support\Facades\Route::has('admin.leads.import.options'))
                <a href="{{ route('admin.leads.import.options') }}" class="sf-btn-secondary">
                    Import
                </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('admin.leads.create'))
                <a href="{{ route('admin.leads.create') }}" class="sf-btn-primary">
                    + Add Lead
                </a>
            @endif
        </div>
    </div>
</div>
