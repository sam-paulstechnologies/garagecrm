<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            Job Details
        </h2>

        <p class="sf-section-subtitle">
            Only service information required for customer visibility and future follow-up.
        </p>
    </div>

    <div class="sf-card-body">
        <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
            <div class="sm:col-span-2">
                <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Service / Job Description
                </dt>

                <dd class="mt-1 font-bold leading-6 text-slate-200">
                    {{ $job->description ?: '-' }}
                </dd>
            </div>

            <div class="sm:col-span-2">
                <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Work Summary
                </dt>

                <dd class="mt-1 font-bold leading-6 text-slate-200">
                    {{ $job->work_summary ?: '-' }}
                </dd>
            </div>

            <div>
                <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Issues Found
                </dt>

                <dd class="mt-1 font-bold leading-6 text-slate-200">
                    {{ $job->issues_found ?: '-' }}
                </dd>
            </div>

            <div>
                <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Parts Used
                </dt>

                <dd class="mt-1 font-bold leading-6 text-slate-200">
                    {{ $job->parts_used ?: '-' }}
                </dd>
            </div>
        </dl>
    </div>
</div>
