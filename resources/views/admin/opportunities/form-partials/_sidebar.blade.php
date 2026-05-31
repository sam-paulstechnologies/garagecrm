<div class="space-y-6">
    <div class="sf-card">
        <div class="sf-card-header">
            <h2 class="sf-section-title">Stage Rules</h2>
        </div>

        <div class="sf-card-body">
            <ul class="space-y-3 text-sm text-slate-300">
                <li class="flex gap-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">1</span>
                    <span><strong class="text-white">Appointment Planned</strong> is only tentative planning.</span>
                </li>

                <li class="flex gap-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">2</span>
                    <span><strong class="text-white">Booking Confirmed</strong> means customer agreed and booking details must be captured.</span>
                </li>

                <li class="flex gap-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">3</span>
                    <span><strong class="text-white">Closed Lost</strong> should include a close reason.</span>
                </li>
            </ul>
        </div>
    </div>

    @if($isEdit && $opp)
        <div class="sf-card">
            <div class="sf-card-header">
                <h2 class="sf-section-title">Current Snapshot</h2>
            </div>

            <div class="sf-card-body space-y-4 text-sm">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Opportunity</div>
                    <div class="mt-1 font-extrabold text-white">{{ $opp->title ?? 'Untitled Opportunity' }}</div>
                </div>

                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Client</div>
                    <div class="mt-1 font-bold text-slate-200">{{ $opp->client?->name ?? '-' }}</div>
                </div>

                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Stage</div>
                    <div class="mt-1"><span class="sf-badge-orange">{{ $stageOptions[$opp->stage] ?? ucwords(str_replace('_', ' ', $opp->stage ?? 'new')) }}</span></div>
                </div>

                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Created</div>
                    <div class="mt-1 font-bold text-slate-200">{{ $opp->created_at?->format('d M Y, h:i A') ?? '-' }}</div>
                </div>
            </div>
        </div>
    @endif

    <div class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-xl shadow-black/20">
        <h3 class="font-extrabold text-blue-300">Vehicle Tip</h3>
        <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">Use an existing vehicle when possible. Manual vehicle capture should be used only if the vehicle does not exist yet.</p>
    </div>

    <div class="rounded-3xl border border-orange-400/20 bg-orange-500/10 p-5 shadow-xl shadow-black/20">
        <h3 class="font-extrabold text-orange-300">WhatsApp Flow</h3>
        <p class="mt-2 text-sm font-medium leading-6 text-orange-100/80">Stage changes may trigger follow-up logic depending on your WhatsApp event mapping and automation setup.</p>
    </div>
</div>
