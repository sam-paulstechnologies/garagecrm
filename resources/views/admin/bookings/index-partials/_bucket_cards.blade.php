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
        ['key' => 'morning', 'title' => 'Morning', 'count' => $bucketCounts['morning'] ?? 0, 'note' => 'Morning slot'],
        ['key' => 'afternoon', 'title' => 'Afternoon', 'count' => $bucketCounts['afternoon'] ?? 0, 'note' => 'Afternoon slot'],
        ['key' => 'evening', 'title' => 'Evening', 'count' => $bucketCounts['evening'] ?? 0, 'note' => 'Evening slot'],
        ['key' => 'pending', 'title' => 'Pending', 'count' => $bucketCounts['pending'] ?? 0, 'note' => 'Needs action'],
        ['key' => 'overdue', 'title' => 'Overdue', 'count' => $bucketCounts['overdue'] ?? 0, 'note' => 'Past due'],
        ['key' => 'no_vehicle', 'title' => 'No Vehicle', 'count' => $bucketCounts['no_vehicle'] ?? 0, 'note' => 'Missing vehicle'],
        ['key' => 'high_priority', 'title' => 'High Priority', 'count' => $bucketCounts['high_priority'] ?? 0, 'note' => 'High / urgent'],
    ];
@endphp

<div class="sf-booking-panel rounded-2xl border p-5 shadow-sm">
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
            <h2 class="sf-booking-title text-lg font-extrabold tracking-tight">Booking Buckets</h2>
            <p class="sf-booking-muted mt-1 text-sm">
                Quick filters for slots, overdue bookings, priority, and missing vehicle data.
            </p>
        </div>

        @if($bucket || $status || $q)
            <a href="{{ $clearUrl }}" class="sf-link shrink-0">Clear filters</a>
        @endif
    </div>

    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
        @foreach($bucketCards as $card)
            <a href="{{ route('admin.bookings.index', ['bucket' => $card['key']]) }}"
               class="rounded-2xl border p-4 shadow-sm transition {{ $tileClass($card['key']) }}">
                <div class="flex items-center justify-between gap-2">
                    <div class="sf-booking-muted text-xs font-black uppercase tracking-wide">Bucket</div>
                    <div class="sf-booking-value text-2xl font-extrabold">{{ $card['count'] }}</div>
                </div>

                <div class="sf-booking-title mt-3 text-sm font-extrabold">{{ $card['title'] }}</div>
                <div class="sf-booking-muted mt-1 text-xs font-medium">{{ $card['note'] }}</div>
            </a>
        @endforeach
    </div>
</div>
