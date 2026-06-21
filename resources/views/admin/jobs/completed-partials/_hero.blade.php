<div class="sf-jobs-panel rounded-2xl border p-5 shadow-sm">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="sf-job-title text-3xl font-extrabold tracking-tight">
                Completed Jobs
            </h1>

            <p class="sf-job-muted mt-2 max-w-3xl text-sm font-medium">
                Closed jobs with invoice value captured for ROI reporting.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.jobs.index') }}" class="sf-btn-secondary">
                Open Jobs
            </a>
        </div>
    </div>
</div>
