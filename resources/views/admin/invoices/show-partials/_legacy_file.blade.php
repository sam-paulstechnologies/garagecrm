<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            File / Download
        </h2>

        <p class="sf-section-subtitle">
            Generated and uploaded invoices keep the existing download behavior.
        </p>
    </div>

    <div class="sf-card-body">
        <div class="sf-invoice-field-grid">
            <div class="sf-invoice-field-card">
                <div class="sf-invoice-field-label">File Name</div>
                <div class="sf-invoice-field-value">{{ $invoice->file_path ? basename((string) $invoice->file_path) : 'No file uploaded' }}</div>
            </div>

            <div class="sf-invoice-field-card">
                <div class="sf-invoice-field-label">Source</div>
                <div class="sf-invoice-field-value">{{ $sourceLabel }}</div>
            </div>
        </div>

        @if($hasDownload)
            <a href="{{ route('admin.invoices.download', $invoice) }}" class="sf-btn-primary mt-4">
                Download File
            </a>
        @endif
    </div>
</div>
