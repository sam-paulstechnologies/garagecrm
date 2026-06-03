{{-- resources/views/admin/leads/index-partials/_bucket_cards.blade.php --}}

@php
    $pageMode = $pageMode ?? 'open';
    $bucket = $bucket ?? '';
    $q = $q ?? request('q');

    $leadFilters = collect($leadFilters ?? [])
        ->filter(fn ($value) => filled($value) && $value !== 'all')
        ->all();

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

    $bucketSummary = [
        'Buckets: ' . collect($bucketCards)->sum('count'),
        $bucket ? 'Selected: ' . collect($bucketCards)->firstWhere('key', $bucket)['title'] ?? $bucket : 'No bucket selected',
    ];
@endphp

@if($pageMode === 'open')
    <div id="sfLeadBuckets" class="sf-leads-panel rounded-2xl border p-4 shadow-sm">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="sf-leads-title text-base font-extrabold tracking-tight">
                        Lead Buckets
                    </h2>

                    @if(! blank($bucket))
                        <span class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-black text-orange-300">
                            Active
                        </span>
                    @endif

                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                        @foreach($bucketSummary as $summaryItem)
                            <span class="sf-leads-filter-pill inline-flex rounded-full border px-3 py-1 text-xs font-bold">
                                {{ $summaryItem }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-2">
                @if(! blank($bucket))
                    <a href="{{ route('admin.leads.index', $leadFilters) }}" class="sf-btn-secondary">
                        Clear Bucket
                    </a>
                @endif

                <button
                    type="button"
                    id="sfLeadBucketsToggle"
                    class="sf-btn-secondary inline-flex h-10 w-fit shrink-0 items-center justify-center rounded-xl px-4 text-sm font-bold transition"
                    aria-expanded="false"
                >
                    Show Buckets
                </button>
            </div>
        </div>

        <div id="sfLeadBucketsBody" class="mt-5 hidden">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
                @foreach($bucketCards as $bucketCard)
                    <a
                        href="{{ route('admin.leads.index', array_merge($leadFilters, ['bucket' => $bucketCard['key'], 'q' => $q])) }}"
                        class="block rounded-2xl border p-4 transition {{ $bucketCardClass($bucket === $bucketCard['key']) }}"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div class="sf-leads-muted text-xs font-black uppercase tracking-wide">
                                Bucket
                            </div>

                            <div class="sf-leads-value text-2xl font-extrabold">
                                {{ $bucketCard['count'] }}
                            </div>
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
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var body = document.getElementById('sfLeadBucketsBody');
            var toggle = document.getElementById('sfLeadBucketsToggle');

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
@endif