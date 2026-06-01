<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            Closure & ROI
        </h2>
    </div>

    <div class="sf-card-body space-y-4 text-sm">
        <div>
            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                Invoice Number
            </div>

            <div class="mt-1 font-extrabold text-white">
                {{ $invoiceNumber ?: 'Not captured yet' }}
            </div>
        </div>

        <div>
            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                Invoice Amount
            </div>

            <div class="mt-1 font-extrabold text-white">
                {{ $invoiceAmount ? 'AED ' . number_format((float) $invoiceAmount, 2) : 'Not captured yet' }}
            </div>
        </div>

        <div>
            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                ROI Status
            </div>

            @if($status === 'completed')
                <span class="sf-badge-green mt-2">
                    {{ $roiStatus }}
                </span>
            @else
                <span class="sf-badge-orange mt-2">
                    {{ $roiStatus }}
                </span>
            @endif
        </div>

        <div class="sf-divider"></div>

        @if($status === 'completed')
            <div class="rounded-2xl border border-green-400/20 bg-green-500/10 p-4">
                <div class="font-extrabold text-green-300">
                    Job closed
                </div>

                <p class="mt-2 text-xs font-medium leading-5 text-green-100/80">
                    This invoice value can now be used for Meta / WhatsApp campaign ROI reporting.
                </p>
            </div>
        @else
            <div class="rounded-2xl border border-orange-400/20 bg-orange-500/10 p-4">
                <div class="font-extrabold text-orange-300">
                    Invoice required before closing
                </div>

                <p class="mt-2 text-xs font-medium leading-5 text-orange-100/80">
                    Only invoice number and amount are needed. No itemized bill or job card upload required.
                </p>
            </div>

            <a href="{{ route('admin.jobs.edit', $job->id) }}" class="sf-btn-primary w-full">
                Add Invoice / Close Job
            </a>
        @endif
    </div>
</div>
