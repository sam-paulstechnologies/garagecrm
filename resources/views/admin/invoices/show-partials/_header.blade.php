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

            <p class="mt-2 text-sm font-medium text-slate-400">
                Lightweight invoice record used for revenue and campaign ROI reporting.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.invoices.edit', $invoice) }}" class="sf-btn-primary">
                Edit Invoice
            </a>

            <a href="{{ route('admin.invoices.index') }}" class="sf-btn-secondary">
                Back to Invoices
            </a>
        </div>
    </div>
</div>
