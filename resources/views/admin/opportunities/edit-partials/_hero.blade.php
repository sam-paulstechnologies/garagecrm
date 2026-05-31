<div class="sf-page-header">
    <div>
        <div class="sf-kicker">Sales Pipeline</div>
        <h1 class="sf-page-title mt-3">Edit Opportunity</h1>
        <p class="sf-page-subtitle">Update opportunity details, stage, vehicle, services, appointment planning, and booking confirmation.</p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.opportunities.show', $opportunity->id) }}" class="sf-btn-secondary">View Opportunity</a>
        <a href="{{ route('admin.opportunities.index') }}" class="sf-btn-secondary">Back to Opportunities</a>
    </div>
</div>
