<div id="{{ $modalId }}"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 px-4 backdrop-blur-sm">

    <div class="sf-jobs-panel w-full max-w-2xl rounded-3xl border shadow-2xl shadow-black/50">
        <div class="flex items-center justify-between border-b border-white/10 px-6 py-4">
            <div>
                <h4 class="text-lg font-extrabold text-white">
                    Upload Invoice
                </h4>

                <p class="mt-1 text-xs font-medium text-slate-500">
                    Upload invoice file and optional invoice metadata.
                </p>
            </div>

            <button type="button"
                    id="{{ $closeBtnId }}"
                    class="flex h-9 w-9 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-slate-300 transition hover:bg-red-500/10 hover:text-red-300">
                x
            </button>
        </div>

        <form method="POST"
              action="{{ route('admin.jobs.invoices.upload', $job) }}"
              enctype="multipart/form-data"
              class="space-y-5 p-6">
            @csrf

            <div>
                <label class="sf-label">
                    Invoice file <span class="text-red-300">*</span>
                </label>

                <input type="file"
                       name="invoice_file"
                       required
                       class="sf-file-input block w-full rounded-xl border px-3 py-2 text-sm shadow-sm file:mr-4 file:rounded-lg file:border-0 file:bg-orange-500 file:px-3 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-orange-600">

                <p class="sf-help">
                    pdf, jpg, jpeg, png, webp - max 5MB
                </p>
            </div>

            <details id="{{ $advId }}" class="rounded-2xl border border-white/10 bg-slate-950/60">
                <summary class="cursor-pointer px-4 py-3 text-sm font-bold text-slate-300">
                    Advanced optional metadata
                </summary>

                <div class="grid grid-cols-1 gap-3 border-t border-white/10 p-4 md:grid-cols-3">
                    <input type="text"
                           name="number"
                           placeholder="Invoice #"
                           class="sf-input">

                    <input type="date"
                           name="invoice_date"
                           class="sf-input">

                    <input type="date"
                           name="due_date"
                           class="sf-input">

                    <input type="text"
                           name="currency"
                           placeholder="Currency (AED)"
                           class="sf-input">

                    <input type="number"
                           step="0.01"
                           name="amount"
                           placeholder="Amount"
                           class="sf-input">
                </div>
            </details>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" id="{{ $closeBtnId }}-2" class="sf-btn-secondary">
                    Cancel
                </button>

                <button class="sf-btn-primary">
                    Upload
                </button>
            </div>
        </form>
    </div>
</div>
