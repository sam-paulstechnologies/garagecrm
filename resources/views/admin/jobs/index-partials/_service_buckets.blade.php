{{-- resources/views/admin/jobs/index-partials/_service_buckets.blade.php --}}

@php
    $q = $q ?? request('q', '');
    $status = $status ?? request('status', '');
    $bucket = $bucket ?? request('bucket', '');

    $jobFilters = collect(request()->only([
        'q',
        'status',
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

    $bucketCards = [
        [
            'key' => 'general',
            'label' => 'General',
            'count' => $bucketCounts['General Service'] ?? 0,
            'note' => 'Service reminder',
            'class' => 'sf-job-bucket-slate',
        ],
        [
            'key' => 'oil',
            'label' => 'Oil',
            'count' => $bucketCounts['Oil Service'] ?? 0,
            'note' => 'Oil follow-up',
            'class' => 'sf-job-bucket-orange',
        ],
        [
            'key' => 'battery',
            'label' => 'Battery',
            'count' => $bucketCounts['Battery Service'] ?? 0,
            'note' => 'Battery check',
            'class' => 'sf-job-bucket-blue',
        ],
        [
            'key' => 'tyres',
            'label' => 'Tyres',
            'count' => $bucketCounts['Tyre Service'] ?? 0,
            'note' => 'Tyre reminder',
            'class' => 'sf-job-bucket-slate',
        ],
        [
            'key' => 'ac',
            'label' => 'AC',
            'count' => $bucketCounts['AC Service'] ?? 0,
            'note' => 'AC follow-up',
            'class' => 'sf-job-bucket-blue',
        ],
        [
            'key' => 'brakes',
            'label' => 'Brakes',
            'count' => $bucketCounts['Brake Service'] ?? 0,
            'note' => 'Safety check',
            'class' => 'sf-job-bucket-red',
        ],
        [
            'key' => 'wash',
            'label' => 'Wash',
            'count' => $bucketCounts['Car Wash / Detailing'] ?? 0,
            'note' => 'Promo ready',
            'class' => 'sf-job-bucket-green',
        ],
    ];

    $bucketTotal = collect($bucketCards)->sum('count');

    $selectedBucketTitle = null;

    if ($bucket) {
        $matchedBucket = collect($bucketCards)->firstWhere('key', $bucket);
        $selectedBucketTitle = $matchedBucket['label'] ?? ucwords(str_replace('_', ' ', $bucket));
    }

    $bucketSummary = [
        'Buckets: ' . $bucketTotal,
        $selectedBucketTitle ? 'Selected: ' . $selectedBucketTitle : 'No bucket selected',
    ];
@endphp

<div id="sfJobBuckets" class="sf-jobs-panel rounded-2xl border p-4 shadow-sm">
    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="sf-job-title text-base font-extrabold tracking-tight">
                    Cars in Service by Service Bucket
                </h2>

                @if(! blank($bucket))
                    <span class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-black text-orange-300">
                        Active
                    </span>
                @endif

                <div class="flex min-w-0 flex-wrap items-center gap-2">
                    @foreach($bucketSummary as $summaryItem)
                        <span class="sf-job-filter-pill inline-flex rounded-full border px-3 py-1 text-xs font-bold">
                            {{ $summaryItem }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2">
            @if(! blank($bucket))
                <a href="{{ route('admin.jobs.index', $jobFilters) }}" class="sf-btn-secondary">
                    Clear Bucket
                </a>
            @endif

            <button
                type="button"
                id="sfJobBucketsToggle"
                class="sf-btn-secondary inline-flex h-10 w-fit shrink-0 items-center justify-center rounded-xl px-4 text-sm font-bold transition"
                aria-expanded="false"
            >
                Show Buckets
            </button>
        </div>
    </div>

    <div id="sfJobBucketsBody" class="mt-5 hidden">
        <p class="sf-job-muted mb-4 text-sm font-medium">
            These buckets show what kind of future WhatsApp follow-up can be prepared once the job is closed.
        </p>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
            @foreach($bucketCards as $card)
                <a
                    href="{{ route('admin.jobs.index', array_merge($jobFilters, ['bucket' => $card['key'], 'q' => $q])) }}"
                    class="sf-job-bucket-card {{ $card['class'] }} {{ $bucket === $card['key'] ? 'sf-job-bucket-active' : '' }} rounded-2xl border p-4 transition"
                >
                    <div class="text-xs font-extrabold uppercase tracking-wide">
                        {{ $card['label'] }}
                    </div>

                    <div class="mt-2 text-2xl font-extrabold">
                        {{ $card['count'] }}
                    </div>

                    <div class="mt-1 text-xs font-medium">
                        {{ $card['note'] }}
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var body = document.getElementById('sfJobBucketsBody');
        var toggle = document.getElementById('sfJobBucketsToggle');

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