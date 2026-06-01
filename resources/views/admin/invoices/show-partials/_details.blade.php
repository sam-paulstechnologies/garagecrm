<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            Invoice Details
        </h2>
    </div>

    <div class="sf-card-body">
        <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Invoice Number
                </dt>

                <dd class="mt-1 font-extrabold text-white">
                    {{ $invoiceNumber }}
                </dd>
            </div>

            <div>
                <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Amount
                </dt>

                <dd class="mt-1 font-extrabold text-orange-300">
                    {{ $currency }} {{ number_format($amount, 2) }}
                </dd>
            </div>

            <div>
                <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Status
                </dt>

                <dd class="mt-2">
                    <span class="{{ $statusBadge }}">
                        {{ ucwords($statusValue) }}
                    </span>
                </dd>
            </div>

            <div>
                <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Source
                </dt>

                <dd class="mt-1 font-bold text-slate-200">
                    {{ $sourceLabel }}
                </dd>
            </div>

            <div>
                <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Invoice Date
                </dt>

                <dd class="mt-1 font-bold text-slate-200">
                    {{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d') : '-' }}
                </dd>
            </div>

            <div>
                <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Due Date
                </dt>

                <dd class="mt-1 font-bold text-slate-200">
                    {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('Y-m-d') : '-' }}
                </dd>
            </div>

            <div>
                <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Created
                </dt>

                <dd class="mt-1 font-bold text-slate-200">
                    {{ $invoice->created_at?->format('Y-m-d H:i') ?? '-' }}
                </dd>
            </div>

            <div>
                <dt class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Last Updated
                </dt>

                <dd class="mt-1 font-bold text-slate-200">
                    {{ $invoice->updated_at?->format('Y-m-d H:i') ?? '-' }}
                </dd>
            </div>
        </dl>
    </div>
</div>
