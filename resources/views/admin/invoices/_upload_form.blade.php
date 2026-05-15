{{-- resources/views/admin/invoices/partials/form.blade.php --}}

<form method="POST"
      action="{{ $action }}"
      enctype="multipart/form-data"
      class="space-y-6">
    @csrf

    {{-- Upload --}}
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
                       class="block w-full rounded-xl border border-white/10 bg-slate-950 px-3 py-2 text-sm text-slate-300 shadow-sm file:mr-4 file:rounded-lg file:border-0 file:bg-orange-500 file:px-3 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-orange-600">

                <p class="sf-help">
                    pdf, jpg, jpeg, png, webp • max 5MB
                </p>

                @error('invoice_file')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    {{-- Metadata --}}
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

                {{-- Invoice Number --}}
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

                {{-- Invoice Date --}}
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

                {{-- Due Date --}}
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

                {{-- Currency --}}
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

                {{-- Amount --}}
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

    {{-- Job Primary Option --}}
    @isset($job)
        <div class="rounded-3xl border border-green-400/20 bg-green-500/10 p-5 shadow-xl shadow-black/20">
            <label class="flex items-start gap-3">
                <input type="checkbox"
                       name="is_primary"
                       value="1"
                       @checked(old('is_primary'))
                       class="mt-1 rounded border-white/10 bg-slate-950 text-green-500 shadow-sm focus:ring-green-400">

                <span>
                    <span class="block text-sm font-extrabold text-green-300">
                        Set as Primary
                    </span>

                    <span class="mt-1 block text-xs font-medium leading-5 text-green-100/80">
                        Mark this invoice as the main invoice for this job.
                    </span>
                </span>
            </label>

            @error('is_primary')
                <div class="sf-error">{{ $message }}</div>
            @enderror
        </div>
    @endisset

    {{-- Client Job Attachment --}}
    @isset($client)
        <div class="sf-card">
            <div class="sf-card-header">
                <h2 class="sf-section-title">
                    Job Attachment
                </h2>

                <p class="sf-section-subtitle">
                    Optionally attach this invoice to a job and mark it as primary.
                </p>
            </div>

            <div class="sf-card-body">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                    <div>
                        <label class="sf-label">
                            Attach to Job ID
                        </label>

                        <input type="number"
                               name="job_id"
                               placeholder="Attach to Job ID (optional)"
                               value="{{ old('job_id', $jobId ?? '') }}"
                               class="sf-input">

                        @error('job_id')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="rounded-2xl border border-green-400/20 bg-green-500/10 p-4">
                        <label class="flex items-start gap-3">
                            <input type="checkbox"
                                   name="is_primary"
                                   value="1"
                                   @checked(old('is_primary'))
                                   class="mt-1 rounded border-white/10 bg-slate-950 text-green-500 shadow-sm focus:ring-green-400">

                            <span>
                                <span class="block text-sm font-extrabold text-green-300">
                                    Set as Primary
                                </span>

                                <span class="mt-1 block text-xs font-medium leading-5 text-green-100/80">
                                    Applies if a Job ID is selected.
                                </span>
                            </span>
                        </label>
                    </div>

                </div>
            </div>
        </div>
    @endisset

    {{-- Actions --}}
    <div class="flex flex-wrap items-center gap-3">
        <button type="submit" class="sf-btn-primary">
            Upload
        </button>
    </div>
</form>