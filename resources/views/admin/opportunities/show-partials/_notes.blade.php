<div class="sf-opportunity-panel rounded-2xl border shadow-sm">
    <div class="border-b border-white/10 p-5">
        <h2 class="sf-section-title">Notes</h2>
    </div>

    <div class="p-5">
        @if($opportunity->notes)
            <div class="whitespace-pre-line text-sm font-medium leading-7 sf-opportunity-muted">{{ $opportunity->notes }}</div>
        @else
            <div class="sf-empty">No notes added.</div>
        @endif
    </div>
</div>
