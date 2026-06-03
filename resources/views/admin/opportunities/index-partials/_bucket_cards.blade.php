{{-- resources/views/admin/opportunities/index-partials/_bucket_cards.blade.php --}}

@php
    $q = $q ?? request('q', '');
    $bucket = $bucket ?? request('bucket', '');

    $opportunityFilters = collect(request()->only([
        'q',
        'stage',
        'priority',
        'date_range',
        'lead_source',
        'assigned_user',
        'service_type',
        'customer_type',
        'from_date',
        'to_date',
    ]))
        ->filter(fn ($value) => filled($value) && $value !== 'all')
        ->all();

    $bucketTotal = collect($bucketCards ?? [])->sum('count');

    $selectedBucketTitle = null;

    if ($bucket) {
        $matchedBucket = collect($bucketCards ?? [])->firstWhere('key', $bucket);
        $selectedBucketTitle = $matchedBucket['title'] ?? ucwords(str_replace('_', ' ', $bucket));
    }

    $bucketSummary = [
        'Buckets: ' . $bucketTotal,
        $selectedBucketTitle ? 'Selected: ' . $selectedBucketTitle : 'No bucket selected',
    ];
@endphp

<div id="sfOpportunityBuckets" class="sf-opportunity-panel rounded-2xl border p-4 shadow-sm">
    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="sf-opportunity-title text-base font-extrabold tracking-tight">
                    Opportunity Buckets
                </h2>

                @if(! blank($bucket))
                    <span class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-black text-orange-300">
                        Active
                    </span>
                @endif

                <div class="flex min-w-0 flex-wrap items-center gap-2">
                    @foreach($bucketSummary as $summaryItem)
                        <span class="sf-opportunity-filter-pill inline-flex rounded-full border px-3 py-1 text-xs font-bold">
                            {{ $summaryItem }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2">
            @if(! blank($bucket))
                <a href="{{ route('admin.opportunities.index', $opportunityFilters) }}" class="sf-btn-secondary">
                    Clear Bucket
                </a>
            @endif

            <button
                type="button"
                id="sfOpportunityBucketsToggle"
                class="sf-btn-secondary inline-flex h-10 w-fit shrink-0 items-center justify-center rounded-xl px-4 text-sm font-bold transition"
                aria-expanded="false"
            >
                Show Buckets
            </button>
        </div>
    </div>

    <div id="sfOpportunityBucketsBody" class="mt-5 hidden">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            @foreach($bucketCards as $bucketCard)
                <a
                    href="{{ route('admin.opportunities.index', array_merge($opportunityFilters, ['bucket' => $bucketCard['key'], 'q' => $q])) }}"
                    class="block rounded-2xl border p-4 transition {{ $bucketCardClass($bucketCard['key']) }}"
                >
                    <div class="sf-opportunity-value text-2xl font-extrabold">
                        {{ $bucketCard['count'] }}
                    </div>

                    <div class="sf-opportunity-title mt-3 text-sm font-extrabold leading-tight">
                        {{ $bucketCard['title'] }}
                    </div>

                    <div class="sf-opportunity-muted mt-1 text-xs font-medium">
                        {{ $bucketCard['note'] }}
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var body = document.getElementById('sfOpportunityBucketsBody');
        var toggle = document.getElementById('sfOpportunityBucketsToggle');

        if (!body || !toggle) {
            return;
        }

        var collapsed = true;

        function applyState() {
            if (collapsed) {
                body.classList.add('hidden');
                toggle.textContent = 'Show Buckets';
                toggle.setAttribute('aria-expanded', 'false');
            } else {
                body.classList.remove('hidden');
                toggle.textContent = 'Hide Buckets';
                toggle.setAttribute('aria-expanded', 'true');
            }
        }

        toggle.addEventListener('click', function () {
            collapsed = !collapsed;
            applyState();
        });

        applyState();
    });
</script>