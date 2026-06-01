<div class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-xl shadow-black/20">
    <div class="flex items-start gap-4">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-500/20 text-xs font-extrabold text-blue-200 ring-1 ring-blue-400/20">
            WA
        </div>

        <div>
            <div class="font-extrabold text-blue-300">
                Customer update suggestion
            </div>

            <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
                {{ $customerUpdateNow }}
            </p>

            @if($status !== 'completed')
                <p class="mt-2 text-xs font-medium leading-5 text-blue-100/70">
                    Once the job is completed with invoice number and amount, feedback can be triggered and invoice value can be used for campaign ROI.
                </p>
            @endif
        </div>
    </div>
</div>
