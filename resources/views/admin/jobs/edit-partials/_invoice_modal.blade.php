<div id="invoiceModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 px-4 backdrop-blur-sm">

    <div class="sf-jobs-panel w-full max-w-md rounded-3xl border p-6 shadow-2xl shadow-black/50">
        <h3 class="text-lg font-extrabold text-white">
            Close Job with Invoice
        </h3>

        <p class="mt-2 text-sm font-medium leading-6 text-slate-400">
            Please enter invoice number and invoice amount to mark this job as completed.
        </p>

        <div class="mt-5 space-y-4">
            <div>
                <label class="sf-label">
                    Invoice Number <span class="text-red-300">*</span>
                </label>

                <input type="text"
                       id="modal_invoice_number"
                       value="{{ old('invoice_number', $invoiceNumber) }}"
                       class="sf-input"
                       placeholder="Example: INV-1001">
            </div>

            <div>
                <label class="sf-label">
                    Invoice Amount <span class="text-red-300">*</span>
                </label>

                <input type="number"
                       id="modal_invoice_amount"
                       value="{{ old('invoice_amount', $invoiceAmount) }}"
                       class="sf-input"
                       min="0"
                       step="0.01"
                       placeholder="Example: 850">
            </div>

            <p id="invoiceModalError" class="hidden text-sm font-bold text-red-400">
                Invoice number and amount are required to close the job.
            </p>
        </div>

        <div class="mt-6 flex items-center justify-end gap-3">
            <button type="button"
                    id="cancelInvoiceModal"
                    class="sf-btn-secondary">
                Cancel
            </button>

            <button type="button"
                    id="confirmInvoiceModal"
                    class="sf-btn-primary">
                Close Job
            </button>
        </div>
    </div>
</div>
