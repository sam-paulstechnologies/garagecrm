<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <div class="sf-opportunity-panel rounded-2xl border p-5 shadow-sm">
        <div class="text-xs font-extrabold uppercase tracking-wide sf-opportunity-muted">Archived Opportunities</div>
        <div class="mt-3 text-3xl font-black text-orange-300">{{ method_exists($opportunities, 'total') ? $opportunities->total() : $opportunities->count() }}</div>
        <div class="mt-2 text-sm font-semibold sf-opportunity-muted">Deleted from active pipeline</div>
    </div>

    <div class="sf-opportunity-panel rounded-2xl border p-5 shadow-sm">
        <div class="text-xs font-extrabold uppercase tracking-wide sf-opportunity-muted">Available Action</div>
        <div class="mt-3 text-lg font-extrabold sf-opportunity-value">Restore Opportunity</div>
        <div class="mt-2 text-sm font-semibold sf-opportunity-muted">Move back to active opportunities</div>
    </div>

    <div class="sf-opportunity-panel rounded-2xl border p-5 shadow-sm">
        <div class="text-xs font-extrabold uppercase tracking-wide sf-opportunity-muted">Archive Purpose</div>
        <div class="mt-3 text-lg font-extrabold sf-opportunity-value">Pipeline Cleanup</div>
        <div class="mt-2 text-sm font-semibold sf-opportunity-muted">Keep lost or deleted records safely stored</div>
    </div>
</div>
