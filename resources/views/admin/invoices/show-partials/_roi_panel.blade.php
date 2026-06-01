<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            ROI Readiness
        </h2>
    </div>

    <div class="sf-card-body space-y-4 text-sm">
        <div class="flex items-center justify-between gap-4">
            <span class="font-medium text-slate-400">
                Invoice amount
            </span>

            @if($hasRevenue)
                <span class="font-extrabold text-green-300">
                    Available
                </span>
            @else
                <span class="font-extrabold text-red-300">
                    Missing
                </span>
            @endif
        </div>

        <div class="flex items-center justify-between gap-4">
            <span class="font-medium text-slate-400">
                Paid status
            </span>

            @if($statusValue === 'paid')
                <span class="font-extrabold text-green-300">
                    Paid
                </span>
            @else
                <span class="font-extrabold text-yellow-300">
                    Not paid
                </span>
            @endif
        </div>

        <div class="flex items-center justify-between gap-4">
            <span class="font-medium text-slate-400">
                Linked job
            </span>

            @if($hasJob)
                <span class="font-extrabold text-green-300">
                    Linked
                </span>
            @else
                <span class="font-extrabold text-red-300">
                    Missing
                </span>
            @endif
        </div>

        <div class="sf-divider"></div>

        @if($roiReady)
            <div class="rounded-2xl border border-green-400/20 bg-green-500/10 p-4">
                <div class="font-extrabold text-green-300">
                    Ready for ROI
                </div>

                <p class="mt-2 text-xs font-medium leading-5 text-green-100/80">
                    This invoice can be included in campaign revenue reporting.
                </p>
            </div>
        @else
            <div class="rounded-2xl border border-yellow-400/20 bg-yellow-500/10 p-4">
                <div class="font-extrabold text-yellow-300">
                    ROI pending
                </div>

                <p class="mt-2 text-xs font-medium leading-5 text-yellow-100/80">
                    Make sure the invoice is paid, has amount, and is linked to a job.
                </p>
            </div>
        @endif
    </div>
</div>
