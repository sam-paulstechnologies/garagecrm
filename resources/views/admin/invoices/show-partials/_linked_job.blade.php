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
            <div class="sf-invoice-field-grid">
                @foreach([
                    'Job Code' => $invoice->job->job_code ?? 'Job #' . $invoice->job->id,
                    'Job Status' => ucwords(str_replace('_', ' ', $invoice->job->status ?? '-')),
                    'Job Description' => $invoice->job->description ?: 'Not set',
                ] as $label => $value)
                    <div class="sf-invoice-field-card {{ $label === 'Job Description' ? 'md:col-span-2' : '' }}">
                        <div class="sf-invoice-field-label">{{ $label }}</div>
                        <div class="sf-invoice-field-value">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
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
