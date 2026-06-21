<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            System Information
        </h2>
    </div>

    <div class="sf-card-body">
        <div class="sf-invoice-field-grid">
            @foreach([
                'Created At' => $invoice->created_at?->format('d M Y, h:i A') ?? 'Not set',
                'Last Updated' => $invoice->updated_at?->format('d M Y, h:i A') ?? 'Not set',
                'Deleted' => method_exists($invoice, 'trashed') && $invoice->trashed() ? 'Yes' : 'No',
                'Invoice ID' => '#' . $invoice->id,
            ] as $label => $value)
                <div class="sf-invoice-field-card">
                    <div class="sf-invoice-field-label">{{ $label }}</div>
                    <div class="sf-invoice-field-value">{{ $value }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
