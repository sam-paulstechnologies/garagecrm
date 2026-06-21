<div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
    <div class="sf-card sf-crm-edit-card">
        <div class="sf-card-header">
            <h2 class="sf-section-title">
                Invoice Information
            </h2>

            <p class="sf-section-subtitle">
                Keep this invoice clean so revenue can be used for campaign ROI.
            </p>
        </div>

        <div class="sf-card-body">
            <form method="POST"
                  action="{{ route('admin.invoices.update', $invoice) }}"
                  class="space-y-6">

                @csrf
                @method('PUT')

                <div class="sf-crm-section">
                    <div class="sf-crm-section-head">
                        <h3>Client / Job Link</h3>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="sf-label">
                        Client <span class="text-red-300">*</span>
                    </label>

                    <select id="client_id"
                            name="client_id"
                            class="sf-select"
                            required>
                        <option value="">Select Client</option>

                        @foreach($clients as $client)
                            <option value="{{ $client->id }}"
                                    {{ (int) old('client_id', $invoice->client_id) === (int) $client->id ? 'selected' : '' }}>
                                {{ $client->name }}
                                @if($client->phone)
                                    - {{ $client->phone }}
                                @endif
                            </option>
                        @endforeach
                    </select>

                    @error('client_id')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="sf-label">
                        Linked Job
                    </label>

                    <select id="job_id"
                            name="job_id"
                            class="sf-select">
                        <option value="">No linked job</option>

                        @foreach($jobs as $job)
                            <option value="{{ $job->id }}"
                                    {{ (int) old('job_id', $invoice->job_id) === (int) $job->id ? 'selected' : '' }}>
                                {{ $job->job_code ?? 'Job #' . $job->id }}
                                - {{ ucwords(str_replace('_', ' ', $job->status ?? '')) }}
                            </option>
                        @endforeach
                    </select>

                    <p class="sf-help">
                        Link job to make invoice usable for campaign ROI attribution.
                    </p>

                    @error('job_id')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>
                    </div>
                </div>

                <div class="sf-crm-section">
                    <div class="sf-crm-section-head">
                        <h3>Invoice Identity</h3>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                <div>
                    <label class="sf-label">
                        Invoice Number <span class="text-red-300">*</span>
                    </label>

                    <input type="text"
                           name="number"
                           value="{{ old('number', $invoice->number ?? $invoice->invoice_number) }}"
                           class="sf-input"
                           placeholder="Example: INV-1001"
                           required>

                    @error('number')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="sf-label">
                        Invoice Amount <span class="text-red-300">*</span>
                    </label>

                    <input type="number"
                           name="amount"
                           value="{{ old('amount', $invoice->amount) }}"
                           min="1"
                           step="0.01"
                           class="sf-input"
                           placeholder="Example: 7000"
                           required>

                    @error('amount')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>
                    </div>
                </div>

                <div class="sf-crm-section">
                    <div class="sf-crm-section-head">
                        <h3>Payment Details</h3>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label class="sf-label">
                        Status <span class="text-red-300">*</span>
                    </label>

                    <select name="status"
                            class="sf-select"
                            required>
                        <option value="pending" {{ old('status', $invoice->status) === 'pending' ? 'selected' : '' }}>
                            Pending
                        </option>

                        <option value="paid" {{ old('status', $invoice->status) === 'paid' ? 'selected' : '' }}>
                            Paid
                        </option>

                        <option value="overdue" {{ old('status', $invoice->status) === 'overdue' ? 'selected' : '' }}>
                            Overdue
                        </option>
                    </select>

                    @error('status')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="sf-label">
                        Currency
                    </label>

                    <input type="text"
                           name="currency"
                           value="{{ old('currency', $invoice->currency ?? 'AED') }}"
                           class="sf-input"
                           maxlength="10"
                           placeholder="AED">

                    @error('currency')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="sf-label">
                        Invoice Date
                    </label>

                    <input type="date"
                           name="invoice_date"
                           value="{{ old('invoice_date', $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') : '') }}"
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
                           value="{{ old('due_date', $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('Y-m-d') : '') }}"
                           class="sf-input">

                    @error('due_date')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>
                    </div>
                </div>

                @include('admin.invoices.edit-partials._roi_preview')
                @include('admin.invoices.edit-partials._legacy_file')

                <div class="sf-crm-action-bar flex flex-wrap items-center justify-end gap-3 border-t border-white/10 pt-4">
                    <a href="{{ route('admin.invoices.show', $invoice) }}" class="sf-btn-secondary">
                        Cancel
                    </a>

                    <button type="submit" class="sf-btn-primary">
                        Update Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>

    <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
        <div class="sf-card sf-invoice-edit-side-card">
            <div class="sf-card-header">
                <h2 class="sf-section-title">Invoice Snapshot</h2>
            </div>

            <div class="divide-y divide-white/10 text-sm">
                @foreach([
                    'Invoice' => $invoiceNumber,
                    'Client' => $invoice->client?->name ?? 'No client linked',
                    'Status' => ucwords($statusValue),
                    'Source' => $sourceLabel,
                    'Amount' => $currency . ' ' . number_format($amount, 2),
                    'Linked Job' => $invoice->job?->job_code ?? 'Not linked',
                ] as $label => $value)
                    <div class="px-5 py-3">
                        <div class="sf-invoice-field-label">{{ $label }}</div>
                        <div class="sf-invoice-field-value">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="sf-invoice-note rounded-2xl border p-5 shadow-sm">
            <div class="sf-invoice-note-title font-extrabold">Payment / ROI Guidance</div>
            <p class="sf-invoice-note-text mt-2 text-sm font-semibold leading-6">
                Paid invoices with amount and linked job are revenue-ready for ROI reporting. Download behavior remains unchanged for uploaded files.
            </p>
        </div>
    </aside>
</div>
