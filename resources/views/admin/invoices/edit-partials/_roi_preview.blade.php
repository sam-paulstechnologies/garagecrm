<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            ROI Readiness Preview
        </h2>

        <p class="sf-section-subtitle">
            ROI is ready only when invoice is paid, amount is captured, and invoice is linked to a job.
        </p>
    </div>

    <div class="sf-card-body">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Amount
                </div>

                <div class="mt-2 font-extrabold {{ $hasRevenue ? 'text-green-300' : 'text-red-300' }}">
                    {{ $hasRevenue ? 'Available' : 'Missing' }}
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Paid Status
                </div>

                <div class="mt-2 font-extrabold {{ $statusValue === 'paid' ? 'text-green-300' : 'text-yellow-300' }}">
                    {{ $statusValue === 'paid' ? 'Paid' : 'Not Paid' }}
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    Linked Job
                </div>

                <div class="mt-2 font-extrabold {{ $hasJob ? 'text-green-300' : 'text-red-300' }}">
                    {{ $hasJob ? 'Linked' : 'Missing' }}
                </div>
            </div>
        </div>
    </div>
</div>
