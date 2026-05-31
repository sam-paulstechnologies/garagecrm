<div class="sf-opportunity-panel rounded-2xl border shadow-sm">
    <div class="flex flex-col gap-3 border-b border-white/10 p-5 md:flex-row md:items-start md:justify-between">
        <div>
            <h2 class="sf-section-title">Opportunity Buckets</h2>
            <p class="sf-section-subtitle">Quick filters for active pipeline stages, priority, and missing data.</p>
        </div>

        @if($bucket || $stage || $priority || $q)
            <a href="{{ $clearUrl }}" class="sf-link shrink-0">Clear filters</a>
        @endif
    </div>

    <div class="p-5">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
            @foreach($bucketCards as $card)
                <a href="{{ route('admin.opportunities.index', ['bucket' => $card['key']]) }}"
                   class="rounded-2xl border p-4 shadow-sm transition {{ $bucketCardClass($card['key']) }}">
                    <div class="text-2xl font-extrabold sf-opportunity-value">{{ $card['count'] }}</div>
                    <div class="mt-3 text-sm font-extrabold sf-opportunity-value">{{ $card['title'] }}</div>
                    <div class="mt-1 text-xs font-medium sf-opportunity-muted">{{ $card['note'] }}</div>
                </a>
            @endforeach
        </div>
    </div>
</div>
