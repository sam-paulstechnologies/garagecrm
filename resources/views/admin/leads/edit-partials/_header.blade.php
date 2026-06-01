{{-- resources/views/admin/leads/edit-partials/_header.blade.php --}}

<div class="sf-leads-edit-panel rounded-2xl border p-5 shadow-sm">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="sf-kicker">Lead Management</div>

            <h1 class="sf-leads-edit-title mt-3 text-3xl font-extrabold tracking-tight">
                Edit Lead
            </h1>

            <p class="sf-leads-edit-muted mt-2 max-w-3xl text-sm font-medium">
                Update customer details, source, assignment, vehicle information, and lead status.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.leads.show', $lead) }}" class="sf-btn-secondary">
                View Lead
            </a>

            <a href="{{ route('admin.leads.index') }}" class="sf-btn-secondary">
                Back to Leads
            </a>
        </div>
    </div>
</div>
