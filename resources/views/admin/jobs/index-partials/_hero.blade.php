{{-- resources/views/admin/jobs/index-partials/_hero.blade.php --}}

<div class="sf-jobs-panel rounded-2xl border p-5 shadow-sm">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="sf-job-title text-3xl font-extrabold tracking-tight">
                Open Jobs
            </h1>

            <p class="sf-job-muted mt-2 max-w-3xl text-sm font-medium">
                Track active service jobs, customer updates, service buckets, and closure readiness.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.jobs.completed') }}" class="sf-btn-secondary">
                Completed Jobs
            </a>

            <a href="{{ route('admin.jobs.create') }}" class="sf-btn-primary">
                + Create Job
            </a>
        </div>
    </div>
</div>