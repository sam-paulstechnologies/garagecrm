<div class="sf-opportunity-edit-panel rounded-2xl border p-5 shadow-sm">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="sf-opportunity-edit-title text-3xl font-extrabold tracking-tight">
                Edit Opportunity
            </h1>

            <p class="sf-opportunity-edit-muted mt-2 max-w-3xl text-sm font-medium">
                Update customer details, source, assignment, vehicle information, opportunity stage, and booking details.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.opportunities.show', $opportunity->id) }}" class="sf-btn-secondary">
                View Opportunity
            </a>

            <a href="{{ route('admin.opportunities.index') }}" class="sf-btn-secondary">
                Back to Opportunities
            </a>
        </div>
    </div>
</div>
