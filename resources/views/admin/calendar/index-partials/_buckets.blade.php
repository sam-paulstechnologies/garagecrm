@php
    $counts = array_merge([
        'pending' => 0,
        'scheduled' => 0,
        'reschedule_required' => 0,
    ], $calendarCounts ?? []);

    $bucketCards = [
        [
            'key' => 'pending',
            'filter' => 'pending',
            'title' => 'Manager Confirmation',
            'count' => $counts['pending'] ?? 0,
            'note' => 'Needs confirmation',
            'tone' => 'amber',
        ],
        [
            'key' => 'scheduled',
            'filter' => 'scheduled',
            'title' => 'Booking Confirmed',
            'count' => $counts['scheduled'] ?? 0,
            'note' => 'Confirmed date and slot',
            'tone' => 'green',
        ],
        [
            'key' => 'reschedule_required',
            'filter' => 'reschedule_required',
            'title' => 'Rescheduling Required',
            'count' => $counts['reschedule_required'] ?? 0,
            'note' => 'Needs a new slot',
            'tone' => 'red',
        ],
    ];
@endphp

<div id="sfCalendarBuckets" data-calendar-buckets-body>
    <div class="sf-calendar-bucket-grid">
        @foreach($bucketCards as $card)
            <button
                type="button"
                class="sf-calendar-bucket-card sf-calendar-bucket-{{ $card['tone'] }}"
                data-calendar-bucket="{{ $card['filter'] }}"
            >
                <span class="sf-calendar-bucket-label">{{ $card['title'] }}</span>
                <span class="sf-calendar-bucket-count">{{ $card['count'] }}</span>
                <span class="sf-calendar-bucket-note">{{ $card['note'] }}</span>
            </button>
        @endforeach
    </div>
</div>
