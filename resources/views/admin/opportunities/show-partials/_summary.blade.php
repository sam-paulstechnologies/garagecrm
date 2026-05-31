<div class="sf-opportunity-panel rounded-2xl border shadow-sm">
    <div class="border-b border-white/10 p-5">
        <h2 class="sf-section-title">Opportunity Summary</h2>
        <p class="sf-section-subtitle">Core commercial and operational details.</p>
    </div>

    <div class="p-5">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="sf-opportunity-soft-panel rounded-2xl border p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide sf-opportunity-muted">Opportunity ID</div>
                <div class="mt-1 font-extrabold sf-opportunity-value">#{{ $opportunity->id }}</div>
            </div>

            <div class="sf-opportunity-soft-panel rounded-2xl border p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide sf-opportunity-muted">Source</div>
                <div class="mt-1 font-extrabold sf-opportunity-value">{{ $opportunity->source ?? '-' }}</div>
            </div>

            <div class="rounded-2xl border border-orange-400/20 bg-orange-500/10 p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide text-orange-300">Estimated Value</div>
                <div class="mt-1 font-extrabold sf-opportunity-value">AED {{ number_format((float) $value, 2) }}</div>
            </div>

            <div class="rounded-2xl border border-blue-400/20 bg-blue-500/10 p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide text-blue-300">Appointment / Expected Close Date</div>
                <div class="mt-1 font-extrabold sf-opportunity-value">{{ optional($opportunity->expected_close_date)->format('d M Y') ?? '-' }}</div>
            </div>

            <div class="sf-opportunity-soft-panel rounded-2xl border p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide sf-opportunity-muted">Assigned To</div>
                <div class="mt-1 font-extrabold sf-opportunity-value">{{ $opportunity->assignee?->name ?? 'Unassigned' }}</div>
            </div>

            <div class="sf-opportunity-soft-panel rounded-2xl border p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide sf-opportunity-muted">Converted?</div>
                <div class="mt-1"><span class="{{ $opportunity->is_converted ? 'sf-badge-green' : 'sf-badge-slate' }}">{{ $opportunity->is_converted ? 'Yes' : 'No' }}</span></div>
            </div>
        </div>
    </div>
</div>
