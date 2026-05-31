<div class="sf-opportunity-panel rounded-2xl border shadow-sm">
    <div class="border-b border-white/10 p-5">
        <h2 class="sf-section-title">System Details</h2>
    </div>

    <div class="space-y-4 p-5 text-sm">
        <div>
            <div class="text-xs font-bold uppercase tracking-wide sf-opportunity-muted">Created At</div>
            <div class="mt-1 font-bold sf-opportunity-value">{{ $opportunity->created_at?->format('d M Y, h:i A') ?? '-' }}</div>
        </div>

        <div>
            <div class="text-xs font-bold uppercase tracking-wide sf-opportunity-muted">Last Updated</div>
            <div class="mt-1 font-bold sf-opportunity-value">{{ $opportunity->updated_at?->format('d M Y, h:i A') ?? '-' }}</div>
        </div>

        <div>
            <div class="text-xs font-bold uppercase tracking-wide sf-opportunity-muted">Company ID</div>
            <div class="mt-1 font-bold sf-opportunity-value">{{ $opportunity->company_id }}</div>
        </div>
    </div>
</div>
