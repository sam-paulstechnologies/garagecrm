<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            ROI Readiness
        </h2>
    </div>

    <div class="sf-card-body space-y-4 text-sm">
        <div class="sf-invoice-field-grid">
            <div class="sf-invoice-field-card">
                <div class="sf-invoice-field-label">Invoice Amount</div>
                <div class="sf-invoice-field-value">{{ $currency }} {{ number_format($amount, 2) }}</div>
            </div>

            <div class="sf-invoice-field-card">
                <div class="sf-invoice-field-label">Paid Amount</div>
                <div class="sf-invoice-field-value">{{ $currency }} {{ number_format($paidAmount, 2) }}</div>
            </div>

            <div class="sf-invoice-field-card">
                <div class="sf-invoice-field-label">Outstanding</div>
                <div class="sf-invoice-field-value">{{ $currency }} {{ number_format($outstandingAmount, 2) }}</div>
            </div>

            <div class="sf-invoice-field-card">
                <div class="sf-invoice-field-label">Payment Status</div>
                <div class="sf-invoice-field-value">{{ $statusLabels[$statusValue] ?? ucwords($statusValue) }}</div>
            </div>
        </div>

        <div class="sf-divider"></div>

        @if($roiReady)
            <div class="rounded-2xl border border-green-400/20 bg-green-500/10 p-4">
                <div class="font-extrabold text-green-300">
                    Ready for ROI
                </div>

                <p class="mt-2 text-xs font-medium leading-5 text-green-100/80">
                    This invoice can be included in campaign revenue reporting.
                </p>
            </div>
        @else
            <div class="rounded-2xl border border-yellow-400/20 bg-yellow-500/10 p-4">
                <div class="font-extrabold text-yellow-300">
                    ROI pending
                </div>

                <p class="mt-2 text-xs font-medium leading-5 text-yellow-100/80">
                    Make sure the invoice is paid, has amount, and is linked to a job.
                </p>
            </div>
        @endif
    </div>
</div>
