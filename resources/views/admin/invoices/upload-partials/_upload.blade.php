<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            Invoice Upload
        </h2>

        <p class="sf-section-subtitle">
            Upload the invoice file and add optional invoice metadata.
        </p>
    </div>

    <div class="sf-card-body space-y-5">
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

            @error('invoice_file')
                <div class="sf-error">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>
