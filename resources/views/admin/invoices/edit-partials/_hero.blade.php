<div class="sf-page-header">
    <div>
        <div class="flex flex-wrap items-center gap-2">
            <div class="sf-kicker">
                Revenue Tracking
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

        <h1 class="sf-page-title mt-3">
            Edit Invoice {{ $invoiceNumber }}
        </h1>

        <p class="sf-page-subtitle">
            Update invoice number, amount, status, client and linked job for ROI reporting.
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.invoices.show', $invoice) }}" class="sf-btn-secondary">
            Back to Invoice
        </a>

        <a href="{{ route('admin.invoices.index') }}" class="sf-btn-secondary">
            All Invoices
        </a>
    </div>
</div>
