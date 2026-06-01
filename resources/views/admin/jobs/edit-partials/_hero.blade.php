<div class="sf-page-header">
    <div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="sf-kicker">
                Job Management
            </div>

            <span class="{{ $statusBadge }}">
                {{ ucwords(str_replace('_', ' ', $status)) }}
            </span>

            <span class="{{ $serviceBadge }}">
                {{ $serviceBucket }}
            </span>
        </div>

        <h1 class="sf-page-title mt-3">
            Edit {{ $job->job_code ?? 'Job' }}
        </h1>

        <p class="sf-page-subtitle">
            Update the job. To mark it completed, invoice number and amount are required.
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.jobs.show', $job->id) }}" class="sf-btn-secondary">
            Back to Job
        </a>

        <a href="{{ route('admin.jobs.index') }}" class="sf-btn-secondary">
            Open Jobs
        </a>
    </div>
</div>
