{{-- resources/views/admin/opportunities/index-partials/_hero.blade.php --}}

<div class="sf-opportunity-panel rounded-2xl border p-5 shadow-sm">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="sf-opportunity-title text-3xl font-extrabold tracking-tight">
                {{ $pageTitle ?? 'Open Opportunities' }}
            </h1>

            <p class="sf-opportunity-muted mt-2 max-w-3xl text-sm font-medium">
                {{ $pageSubtitle ?? 'Active pipeline opportunities that still need follow-up, confirmation, or conversion.' }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(Route::has('admin.opportunities.create'))
                <a href="{{ route('admin.opportunities.create') }}" class="sf-btn-primary">
                    + Create Opportunity
                </a>
            @endif
        </div>
    </div>
</div>
