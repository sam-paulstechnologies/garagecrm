{{-- resources/views/admin/bookings/index-partials/_stats.blade.php --}}

@php
    $q = $q ?? request('q', '');
    $status = $status ?? request('status', '');
    $bucket = $bucket ?? request('bucket', '');

    $bookingCounts = array_merge([
        'today' => 0,
        'pending' => 0,
        'scheduled' => 0,
        'converted_to_job' => 0,
        'lost' => 0,
    ], $bookingCounts ?? []);

    $openCount = ($bookingCounts['pending'] ?? 0) + ($bookingCounts['scheduled'] ?? 0);

    $activeClass = function ($active) {
        return $active ? 'ring-1 ring-orange-400/30 border-orange-400/40' : '';
    };
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-6">
    <a href="{{ route('admin.bookings.index') }}"
       class="rounded-2xl border border-blue-400/25 bg-blue-500/10 p-5 shadow-sm transition hover:border-blue-400/45 {{ $activeClass(!$status && !$bucket) }}">
        <div class="sf-booking-accent-title text-sm font-bold">Open Bookings</div>
        <div class="sf-booking-accent-value mt-2 text-3xl font-extrabold">{{ $openCount }}</div>
        <div class="sf-booking-accent-muted mt-1 text-xs font-medium">Pending + scheduled</div>
    </a>

    <a href="{{ route('admin.bookings.index', ['bucket' => 'today']) }}"
       class="rounded-2xl border border-indigo-400/25 bg-indigo-500/10 p-5 shadow-sm transition hover:border-indigo-400/45 {{ $activeClass($bucket === 'today') }}">
        <div class="sf-booking-accent-title text-sm font-bold">Today's Bookings</div>
        <div class="sf-booking-accent-value mt-2 text-3xl font-extrabold">{{ $bookingCounts['today'] ?? 0 }}</div>
        <div class="sf-booking-accent-muted mt-1 text-xs font-medium">Scheduled today</div>
    </a>

    <a href="{{ route('admin.bookings.index', ['status' => 'pending']) }}"
       class="rounded-2xl border border-yellow-400/25 bg-yellow-500/10 p-5 shadow-sm transition hover:border-yellow-400/45 {{ $activeClass($status === 'pending') }}">
        <div class="sf-booking-accent-title text-sm font-bold">Pending</div>
        <div class="sf-booking-accent-value mt-2 text-3xl font-extrabold">{{ $bookingCounts['pending'] ?? 0 }}</div>
        <div class="sf-booking-accent-muted mt-1 text-xs font-medium">Needs action</div>
    </a>

    <a href="{{ route('admin.bookings.index', ['status' => 'scheduled']) }}"
       class="rounded-2xl border border-green-400/25 bg-green-500/10 p-5 shadow-sm transition hover:border-green-400/45 {{ $activeClass($status === 'scheduled') }}">
        <div class="sf-booking-accent-title text-sm font-bold">Scheduled</div>
        <div class="sf-booking-accent-value mt-2 text-3xl font-extrabold">{{ $bookingCounts['scheduled'] ?? 0 }}</div>
        <div class="sf-booking-accent-muted mt-1 text-xs font-medium">Waiting for vehicle</div>
    </a>

    <a href="{{ route('admin.bookings.index', ['status' => 'converted_to_job']) }}"
       class="rounded-2xl border border-blue-400/25 bg-blue-500/10 p-5 shadow-sm transition hover:border-blue-400/45 {{ $activeClass($status === 'converted_to_job') }}">
        <div class="sf-booking-accent-title text-sm font-bold">Converted To Job</div>
        <div class="sf-booking-accent-value mt-2 text-3xl font-extrabold">{{ $bookingCounts['converted_to_job'] ?? 0 }}</div>
        <div class="sf-booking-accent-muted mt-1 text-xs font-medium">Moved to jobs</div>
    </a>

    <a href="{{ route('admin.bookings.index', ['status' => 'lost']) }}"
       class="rounded-2xl border border-red-400/25 bg-red-500/10 p-5 shadow-sm transition hover:border-red-400/45 {{ $activeClass($status === 'lost') }}">
        <div class="sf-booking-accent-title text-sm font-bold">Lost Bookings</div>
        <div class="sf-booking-accent-value mt-2 text-3xl font-extrabold">{{ $bookingCounts['lost'] ?? 0 }}</div>
        <div class="sf-booking-accent-muted mt-1 text-xs font-medium">Cancelled / rejected</div>
    </a>
</div>
