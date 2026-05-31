<div class="sf-page-header">
    <div>
        <div class="sf-kicker">Sales Pipeline</div>
        <h1 class="sf-page-title mt-3">Opportunities</h1>
        <p class="sf-page-subtitle">Track opportunities from lead qualification to appointment, booking, job, and invoice.</p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        @if(Route::has('admin.opportunities.create'))
            <a href="{{ route('admin.opportunities.create') }}" class="sf-btn-primary">Create Opportunity</a>
        @endif
    </div>
</div>
