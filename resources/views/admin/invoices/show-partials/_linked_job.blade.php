<div class="sf-card">
    <div class="sf-card-header flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="sf-section-title">
                Linked Job
            </h2>
        </div>

        @if($invoice->job)
            <a href="{{ route('admin.jobs.show', $invoice->job) }}" class="sf-link">
                View Job
            </a>
        @endif
    </div>

    <div class="sf-card-body">
        @if($invoice->job)
            <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                        Job Code
                    </dt>

                    <dd class="mt-1 font-extrabold text-white">
                        {{ $invoice->job->job_code ?? 'Job #' . $invoice->job->id }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                        Job Status
                    </dt>

                    <dd class="mt-1 font-bold text-slate-200">
                        {{ ucwords(str_replace('_', ' ', $invoice->job->status ?? '-')) }}
                    </dd>
                </div>

                <div class="sm:col-span-2">
                    <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                        Job Description
                    </dt>

                    <dd class="mt-1 font-bold leading-6 text-slate-200">
                        {{ $invoice->job->description ?: '-' }}
                    </dd>
                </div>
            </dl>
        @else
            <div class="rounded-2xl border border-yellow-400/20 bg-yellow-500/10 p-5">
                <div class="font-extrabold text-yellow-300">
                    No job linked
                </div>

                <p class="mt-2 text-sm font-medium leading-6 text-yellow-100/80">
                    Link this invoice to a job so it can be used properly for campaign ROI attribution.
                </p>

                <a href="{{ route('admin.invoices.edit', $invoice) }}" class="sf-btn-primary mt-4">
                    Link Job
                </a>
            </div>
        @endif
    </div>
</div>
