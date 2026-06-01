@if($invoice->file_path)
    <div class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-xl shadow-black/20">
        <div class="font-extrabold text-blue-300">
            Existing uploaded file
        </div>

        <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
            This invoice has an old uploaded file. File upload is no longer required for the SayaraForce ROI flow.
        </p>

        <a href="{{ route('admin.invoices.download', $invoice) }}" class="sf-btn-primary mt-4">
            Download Existing File
        </a>
    </div>
@endif
