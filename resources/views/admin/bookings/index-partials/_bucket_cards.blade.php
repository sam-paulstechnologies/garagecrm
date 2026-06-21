{{-- resources/views/admin/bookings/index-partials/_bucket_cards.blade.php --}}

@php
    $q = $q ?? request('q', '');
    $status = $status ?? request('status', '');
    $bucket = $bucket ?? request('bucket', '');

    $clearUrl = route('admin.bookings.index');

    $bucketCounts = array_merge([
        'morning' => 0,
        'afternoon' => 0,
        'evening' => 0,
        'pending' => 0,
        'reschedule_required' => 0,
        'overdue' => 0,
        'no_vehicle' => 0,
        'high_priority' => 0,
    ], $bucketCounts ?? []);

    $tileClass = function ($key) use ($bucket) {
        return $bucket === $key
            ? 'border-orange-400/40 bg-orange-500/10 ring-1 ring-orange-400/25'
            : 'sf-booking-soft-panel hover:border-orange-400/35';
    };

    $bucketCards = [
        [
            'key' => 'morning',
            'title' => 'Morning',
            'count' => $bucketCounts['morning'] ?? 0,
            'note' => 'Morning slot',
        ],
        [
            'key' => 'afternoon',
            'title' => 'Afternoon',
            'count' => $bucketCounts['afternoon'] ?? 0,
            'note' => 'Afternoon slot',
        ],
        [
            'key' => 'evening',
            'title' => 'Evening',
            'count' => $bucketCounts['evening'] ?? 0,
            'note' => 'Evening slot',
        ],
        [
            'key' => 'pending',
            'title' => 'Manager Confirmation',
            'count' => $bucketCounts['pending'] ?? 0,
            'note' => 'Needs action',
        ],
        [
            'key' => 'reschedule_required',
            'title' => 'Rescheduling Required',
            'count' => $bucketCounts['reschedule_required'] ?? 0,
            'note' => 'Needs new slot',
        ],
        [
            'key' => 'overdue',
            'title' => 'Overdue',
            'count' => $bucketCounts['overdue'] ?? 0,
            'note' => 'Past due',
        ],
        [
            'key' => 'no_vehicle',
            'title' => 'No Vehicle',
            'count' => $bucketCounts['no_vehicle'] ?? 0,
            'note' => 'Missing vehicle',
        ],
        [
            'key' => 'high_priority',
            'title' => 'High Priority',
            'count' => $bucketCounts['high_priority'] ?? 0,
            'note' => 'High priority',
        ],
    ];

    $bookingFilters = collect(request()->only([
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

    $bucketTotal = collect($bucketCards)->sum('count');

    $selectedBucketTitle = null;

    if ($bucket) {
        $matchedBucket = collect($bucketCards)->firstWhere('key', $bucket);
        $selectedBucketTitle = $matchedBucket['title'] ?? ucwords(str_replace('_', ' ', $bucket));
    }

    $bucketSummary = [
        'Buckets: ' . $bucketTotal,
        $selectedBucketTitle ? 'Selected: ' . $selectedBucketTitle : 'No bucket selected',
    ];
@endphp

<div id="sfBookingBuckets" class="sf-booking-panel rounded-2xl border p-4 shadow-sm">
    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="sf-booking-title text-base font-extrabold tracking-tight">
                    Booking Buckets
                </h2>

                @if(! blank($bucket))
                    <span class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-black text-orange-300">
                        Active
                    </span>
                @endif

                <div class="flex min-w-0 flex-wrap items-center gap-2">
                    @foreach($bucketSummary as $summaryItem)
                        <span class="sf-booking-filter-pill inline-flex rounded-full border px-3 py-1 text-xs font-bold">
                            {{ $summaryItem }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2">
            @if(! blank($bucket))
                <a
                    href="{{ route('admin.bookings.index', $bookingFilters) }}"
                    class="sf-btn-secondary"
                >
                    Clear Bucket
                </a>
            @endif

            <button
                type="button"
                id="sfBookingBucketsToggle"
                class="sf-btn-secondary inline-flex h-10 w-fit shrink-0 items-center justify-center rounded-xl px-4 text-sm font-bold transition"
                aria-expanded="false"
            >
                Show Buckets
            </button>
        </div>
    </div>

    <div id="sfBookingBucketsBody" class="mt-5 hidden">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
            @foreach($bucketCards as $card)
                <a
                    href="{{ route('admin.bookings.index', array_merge($bookingFilters, ['bucket' => $card['key'], 'q' => $q])) }}"
                    class="rounded-2xl border p-4 shadow-sm transition {{ $tileClass($card['key']) }}"
                >
                    <div class="flex items-center justify-between gap-2">
                        <div class="sf-booking-muted text-xs font-black uppercase tracking-wide">
                            Bucket
                        </div>

                        <div class="sf-booking-value text-2xl font-extrabold">
                            {{ $card['count'] }}
                        </div>
                    </div>

                    <div class="sf-booking-title mt-3 text-sm font-extrabold">
                        {{ $card['title'] }}
                    </div>

                    <div class="sf-booking-muted mt-1 text-xs font-medium">
                        {{ $card['note'] }}
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var body = document.getElementById('sfBookingBucketsBody');
        var toggle = document.getElementById('sfBookingBucketsToggle');

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
