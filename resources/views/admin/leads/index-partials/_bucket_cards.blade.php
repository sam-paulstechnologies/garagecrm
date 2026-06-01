{{-- resources/views/admin/leads/index-partials/_bucket_cards.blade.php --}}

@php
    $pageMode = $pageMode ?? 'open';
    $bucket = $bucket ?? '';
    $q = $q ?? request('q');
    $leadFilters = collect($leadFilters ?? [])->filter(fn ($value) => filled($value) && $value !== 'all')->all();

    $bucketCardClass = function ($active) {
        return $active
            ? 'border-orange-400/40 bg-orange-500/10 ring-1 ring-orange-400/25'
            : 'sf-leads-soft-panel hover:border-orange-400/35';
    };

    $bucketCards = [
        ['key' => 'service', 'title' => 'Service Requests', 'count' => $bucketCounts['service'] ?? 0, 'note' => 'Service enquiries'],
        ['key' => 'quote_followup', 'title' => 'Quote Follow-up', 'count' => $bucketCounts['quote_followup'] ?? 0, 'note' => 'Quote + follow-up'],
        ['key' => 'complaints', 'title' => 'Complaints', 'count' => $bucketCounts['complaints'] ?? 0, 'note' => 'Needs attention'],
        ['key' => 'hot', 'title' => 'Hot Leads', 'count' => $bucketCounts['hot'] ?? 0, 'note' => 'High intent'],
        ['key' => 'high_priority', 'title' => 'High Priority', 'count' => $bucketCounts['high_priority'] ?? 0, 'note' => 'High / urgent'],
        ['key' => 'followup_due', 'title' => 'Follow-up Due', 'count' => $bucketCounts['followup_due'] ?? 0, 'note' => 'Due today/overdue'],
        ['key' => 'service_due', 'title' => 'Service Due', 'count' => $bucketCounts['service_due'] ?? 0, 'note' => 'Retention bucket'],
        ['key' => 'fleet_corporate', 'title' => 'Fleet / Corporate', 'count' => $bucketCounts['fleet_corporate'] ?? 0, 'note' => 'B2B leads'],
    ];
@endphp

@if($pageMode === 'open')
    <div class="sf-leads-panel rounded-2xl border p-5 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="sf-leads-title text-lg font-extrabold tracking-tight">Lead Buckets</h2>
                <p class="sf-leads-muted mt-1 text-sm">Quick filters for categorization, retention, and follow-up.</p>
            </div>

            @if(! blank($bucket))
                <a href="{{ route('admin.leads.index', $leadFilters) }}" class="sf-link">
                    Clear bucket filter
                </a>
            @endif
        </div>

        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
            @foreach($bucketCards as $bucketCard)
                <a href="{{ route('admin.leads.index', array_merge($leadFilters, ['bucket' => $bucketCard['key'], 'q' => $q])) }}"
                   class="block rounded-2xl border p-4 transition {{ $bucketCardClass($bucket === $bucketCard['key']) }}">
                    <div class="flex items-center justify-between gap-2">
                        <div class="sf-leads-muted text-xs font-black uppercase tracking-wide">Bucket</div>
                        <div class="sf-leads-value text-2xl font-extrabold">{{ $bucketCard['count'] }}</div>
                    </div>

                    <div class="sf-leads-title mt-3 text-sm font-extrabold leading-tight">
                        {{ $bucketCard['title'] }}
                    </div>

                    <div class="sf-leads-muted mt-1 text-xs font-medium">
                        {{ $bucketCard['note'] }}
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endif
