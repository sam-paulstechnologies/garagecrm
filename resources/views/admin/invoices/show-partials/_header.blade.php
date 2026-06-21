<div class="sf-hero-panel">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="sf-kicker">
                    Invoice Profile
                </div>

                <span class="{{ $statusBadge }}">
                    {{ ucwords($statusValue) }}
                </span>

                @if($roiReady)
                    <span class="sf-badge-orange">
                        ROI Ready
                    </span>
                @else
                    <span class="sf-badge-slate">
                        ROI Pending
                    </span>
                @endif
            </div>

            <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-white">
                Invoice {{ $invoiceNumber }}
            </h1>

            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-sm font-medium sf-invoice-muted">
                @if($contactTelUrl && $contactPhoneDisplay)
                    <a href="{{ $contactTelUrl }}" class="sf-invoice-hero-chip" title="Click to call this customer.">
                        {{ $contactPhoneDisplay }}
                    </a>
                @elseif($contactPhoneDisplay)
                    <span>{{ $contactPhoneDisplay }}</span>
                @endif

                <span>{{ $invoice->client?->name ?? 'No client' }}</span>
                <span>{{ $job?->job_code ?? 'No job linked' }}</span>
                <span>{{ $currency }} {{ number_format($amount, 2) }}</span>
                <span>{{ $invoice->due_date?->format('d M Y') ?? 'No due date' }}</span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="#invoice-activity-timeline" class="sf-btn-secondary">
                View All Activity
            </a>

            <a href="{{ route('admin.invoices.edit', $invoice) }}" class="sf-btn-primary">
                Edit Invoice
            </a>

            @if($hasDownload)
                <a href="{{ route('admin.invoices.download', $invoice) }}" class="sf-btn-secondary">
                    Download
                </a>
            @endif

            @if($job && Route::has('admin.jobs.show'))
                <a href="{{ route('admin.jobs.show', $job) }}" class="sf-btn-secondary">
                    View Job
                </a>
            @endif

            @if($invoice->client && Route::has('admin.clients.show'))
                <a href="{{ route('admin.clients.show', $invoice->client) }}" class="sf-btn-secondary">
                    View Client
                </a>
            @endif
        </div>
    </div>
</div>
