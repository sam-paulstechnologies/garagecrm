@if($invoice->file_path)
    <div class="sf-card">
        <div class="sf-card-header">
            <h2 class="sf-section-title">
                Uploaded Invoice File
            </h2>

            <p class="sf-section-subtitle">
                File upload is legacy support only. SayaraForce now uses invoice number and amount for ROI tracking.
            </p>
        </div>

        <div class="sf-card-body">
            <a href="{{ route('admin.invoices.download', $invoice) }}" class="sf-btn-primary">
                Download File
            </a>
        </div>
    </div>
@endif
