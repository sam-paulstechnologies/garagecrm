<div class="sf-card-header flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
        <h2 class="sf-section-title">
            Invoices
        </h2>

        <p class="sf-section-subtitle">
            Upload and manage invoices linked to this job.
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.invoices.index') }}?job_id={{ $job->id }}" class="sf-btn-secondary">
            View All
        </a>

        <button id="{{ $openBtnId }}" type="button" class="sf-btn-primary">
            + Upload Invoice
        </button>
    </div>
</div>
