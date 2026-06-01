<div class="sf-page-header">
    <div>
        <div class="sf-kicker">
            Job Command Center
        </div>

        <h1 class="sf-page-title mt-3">
            Open Jobs
        </h1>

        <p class="sf-page-subtitle">
            Cars currently in service, grouped by the next useful service signal.
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
