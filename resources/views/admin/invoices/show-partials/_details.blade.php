<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            Invoice Details
        </h2>
    </div>

    <div class="sf-card-body">
        <div class="sf-invoice-field-grid">
            @foreach([
                'Invoice Number' => $invoiceNumber,
                'Amount' => $currency . ' ' . number_format($amount, 2),
                'Status' => $statusLabel,
                'Source' => $sourceLabel,
                'Invoice Date' => $invoice->invoice_date?->format('d M Y') ?? 'Not set',
                'Due Date' => $invoice->due_date?->format('d M Y') ?? 'Not set',
            ] as $label => $value)
                <div class="sf-invoice-field-card">
                    <div class="sf-invoice-field-label">{{ $label }}</div>
                    <div class="sf-invoice-field-value">{{ $value }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
