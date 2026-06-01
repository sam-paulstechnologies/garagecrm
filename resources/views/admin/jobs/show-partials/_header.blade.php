<div class="sf-hero-panel">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="sf-kicker">
                    Job Profile
                </div>

                <span class="{{ $statusBadge }}">
                    {{ ucwords(str_replace('_', ' ', $status)) }}
                </span>

                <span class="{{ $serviceBadge }}">
                    {{ $serviceBucket }}
                </span>
            </div>

            <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-white">
                {{ $job->job_code ?? 'Job' }}
            </h1>

            <p class="mt-2 text-sm font-medium text-slate-400">
                Job created for
                <span class="font-extrabold text-white">
                    {{ $job->client?->name ?? 'N/A' }}
                </span>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.jobs.edit', $job->id) }}" class="sf-btn-primary">
                Edit Job
            </a>

            <a href="{{ route('admin.jobs.index') }}" class="sf-btn-secondary">
                Back to Jobs
            </a>
        </div>
    </div>
</div>
