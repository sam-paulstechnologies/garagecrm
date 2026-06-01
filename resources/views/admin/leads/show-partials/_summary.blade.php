{{-- resources/views/admin/leads/show-partials/_summary.blade.php --}}

<div class="grid grid-cols-1 gap-4 md:grid-cols-4">
    <div class="sf-leads-show-panel rounded-2xl border p-5 shadow-sm">
        <div class="sf-leads-show-muted text-xs font-black uppercase tracking-wide">Status</div>
        <div class="mt-3">
            <span class="{{ $badgeBase }} {{ $statusBadgeClass }}">
                {{ $lead->status_label ?? ucfirst(str_replace('_', ' ', (string) $lead->status)) }}
            </span>
        </div>
    </div>

    <div class="sf-leads-show-panel rounded-2xl border p-5 shadow-sm">
        <div class="sf-leads-show-muted text-xs font-black uppercase tracking-wide">Lead Score</div>
        <div class="sf-leads-show-value mt-2 text-3xl font-extrabold">{{ $score }}/100</div>
    </div>

    <div class="sf-leads-show-panel rounded-2xl border p-5 shadow-sm">
        <div class="sf-leads-show-muted text-xs font-black uppercase tracking-wide">Source</div>
        <div class="sf-leads-show-value mt-2 text-lg font-extrabold">{{ $sourceLabel }}</div>
    </div>

    <div class="sf-leads-show-panel rounded-2xl border p-5 shadow-sm">
        <div class="sf-leads-show-muted text-xs font-black uppercase tracking-wide">Hot Lead</div>
        <div class="mt-2 text-lg font-extrabold {{ $lead->is_hot ? 'text-red-300' : 'sf-leads-show-value' }}">
            {{ $lead->is_hot ? 'Yes' : 'No' }}
        </div>
    </div>
</div>
