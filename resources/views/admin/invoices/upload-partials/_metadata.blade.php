<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            Invoice Metadata
        </h2>

        <p class="sf-section-subtitle">
            These fields are optional, but amount and invoice number help with revenue tracking.
        </p>
    </div>

    <div class="sf-card-body">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="sf-label">
                    Invoice #
                </label>

                <input type="text"
                       name="number"
                       value="{{ old('number') }}"
                       placeholder="Invoice #"
                       class="sf-input">

                @error('number')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="sf-label">
                    Invoice Date
                </label>

                <input type="date"
                       name="invoice_date"
                       value="{{ old('invoice_date') }}"
                       class="sf-input">

                @error('invoice_date')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="sf-label">
                    Due Date
                </label>

                <input type="date"
                       name="due_date"
                       value="{{ old('due_date') }}"
                       class="sf-input">

                @error('due_date')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="sf-label">
                    Currency
                </label>

                <input type="text"
                       name="currency"
                       value="{{ old('currency') }}"
                       placeholder="Currency (AED)"
                       class="sf-input">

                @error('currency')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="sf-label">
                    Amount
                </label>

                <input type="number"
                       step="0.01"
                       name="amount"
                       value="{{ old('amount') }}"
                       placeholder="Amount"
                       class="sf-input">

                @error('amount')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>
