@extends('layouts.app')

@section('title', 'Bookings')

@section('content')
@php
    $q = $q ?? request('q', '');
    $status = $status ?? request('status', '');
    $bucket = $bucket ?? request('bucket', '');

    $bookingCounts = array_merge([
        'open' => 0,
        'today' => 0,
        'upcoming' => 0,
        'pending' => 0,
        'scheduled' => 0,
        'converted_to_job' => 0,
        'lost' => 0,
        'confirmed' => 0,
        'completed' => 0,
    ], $bookingCounts ?? []);

    $bucketCounts = array_merge([
        'today' => 0,
        'morning' => 0,
        'afternoon' => 0,
        'evening' => 0,
        'pending' => 0,
        'overdue' => 0,
        'no_vehicle' => 0,
        'high_priority' => 0,
    ], $bucketCounts ?? []);

    $openCount = ($bookingCounts['pending'] ?? 0) + ($bookingCounts['scheduled'] ?? 0);

    $clearUrl = route('admin.bookings.index');

    $statusLabel = function ($status) {
        return match (strtolower((string) $status)) {
            'pending' => 'Pending',
            'scheduled' => 'Scheduled',
            'confirmed' => 'Confirmed',
            'converted_to_job' => 'Converted To Job',
            'completed' => 'Completed',
            'lost' => 'Lost Booking',
            'cancelled', 'canceled' => 'Cancelled',
            default => ucwords(str_replace('_', ' ', (string) $status)),
        };
    };

    $statusBadge = function ($status) {
        return match (strtolower((string) $status)) {
            'pending' => 'sf-badge-yellow',
            'scheduled', 'confirmed' => 'sf-badge-green',
            'converted_to_job', 'completed' => 'sf-badge-blue',
            'lost', 'cancelled', 'canceled', 'rejected' => 'sf-badge-red',
            default => 'sf-badge-slate',
        };
    };

    $priorityBadge = function ($priority) {
        return match (strtolower((string) $priority)) {
            'urgent' => 'sf-badge-red',
            'high' => 'sf-badge-orange',
            'medium' => 'sf-badge-yellow',
            'low' => 'sf-badge-slate',
            default => 'sf-badge-slate',
        };
    };

    $slotBadge = function ($slot) {
        return match (strtolower((string) $slot)) {
            'morning' => 'sf-badge-blue',
            'afternoon' => 'sf-badge-orange',
            'evening' => 'sf-badge-slate',
            'full_day' => 'sf-badge-green',
            default => 'sf-badge-slate',
        };
    };

    $tileClass = function ($key, $type = 'status') use ($status, $bucket) {
        $active = $type === 'status'
            ? $status === $key
            : $bucket === $key;

        return $active
            ? 'border-orange-400/40 bg-orange-500/10 ring-1 ring-orange-400/30'
            : 'border-white/10 bg-slate-950/60 hover:border-orange-400/30 hover:bg-slate-900';
    };

    $nextAction = function ($booking) {
        return match (strtolower((string) $booking->status)) {
            'pending' => 'Confirm booking',
            'scheduled', 'confirmed' => 'Receive vehicle',
            'converted_to_job', 'completed' => 'Review job',
            'lost', 'cancelled', 'canceled', 'rejected' => 'No action',
            default => 'Review',
        };
    };

    $bookingDate = function ($booking) {
        return $booking->booking_date
            ?? $booking->scheduled_at
            ?? $booking->date
            ?? $booking->preferred_date
            ?? null;
    };

    $vehicleLabel = function ($booking) {
        $vehicle = $booking->vehicle ?? null;

        if ($vehicle) {
            $label = trim(
                ($vehicle->year ? $vehicle->year . ' ' : '') .
                ($vehicle->make?->name ?? $vehicle->vehicleMake?->name ?? '') . ' ' .
                ($vehicle->model?->name ?? $vehicle->vehicleModel?->name ?? '') . ' ' .
                ($vehicle->plate_number ? '(' . $vehicle->plate_number . ')' : '')
            );

            if ($label !== '') {
                return $label;
            }
        }

        return trim(
            ($booking->vehicleMake?->name ?? $booking->other_make ?? '') . ' ' .
            ($booking->vehicleModel?->name ?? $booking->other_model ?? '')
        );
    };

    $bucketCards = [
        ['key' => 'morning', 'title' => 'Morning', 'count' => $bucketCounts['morning'] ?? 0, 'note' => 'Morning slot', 'emoji' => '🌅'],
        ['key' => 'afternoon', 'title' => 'Afternoon', 'count' => $bucketCounts['afternoon'] ?? 0, 'note' => 'Afternoon slot', 'emoji' => '☀️'],
        ['key' => 'evening', 'title' => 'Evening', 'count' => $bucketCounts['evening'] ?? 0, 'note' => 'Evening slot', 'emoji' => '🌙'],
        ['key' => 'pending', 'title' => 'Pending', 'count' => $bucketCounts['pending'] ?? 0, 'note' => 'Needs action', 'emoji' => '⏳'],
        ['key' => 'overdue', 'title' => 'Overdue', 'count' => $bucketCounts['overdue'] ?? 0, 'note' => 'Past due', 'emoji' => '🚨'],
        ['key' => 'no_vehicle', 'title' => 'No Vehicle', 'count' => $bucketCounts['no_vehicle'] ?? 0, 'note' => 'Missing vehicle', 'emoji' => '🚗'],
        ['key' => 'high_priority', 'title' => 'High Priority', 'count' => $bucketCounts['high_priority'] ?? 0, 'note' => 'High / urgent', 'emoji' => '🔥'],
    ];
@endphp

<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Booking Command Center
            </div>

            <h1 class="sf-page-title mt-3">
                Bookings
            </h1>

            <p class="sf-page-subtitle">
                Default view shows open bookings. Converted jobs and lost bookings are available through filters.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(Route::has('admin.bookings.create'))
                <a href="{{ route('admin.bookings.create') }}" class="sf-btn-primary">
                    + New Booking
                </a>
            @endif
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="sf-alert-success">{{ session('success') }}</div>
    @endif

    @if(session('warning'))
        <div class="sf-alert-warning">{{ session('warning') }}</div>
    @endif

    @if(session('error'))
        <div class="sf-alert-danger">{{ session('error') }}</div>
    @endif

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-6">

        <a href="{{ route('admin.bookings.index') }}"
           class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:border-blue-400/40 hover:bg-blue-500/20 {{ (!$status && !$bucket) ? 'ring-1 ring-blue-400/30' : '' }}">
            <div class="text-sm font-bold text-blue-300">Open Bookings</div>
            <div class="mt-2 text-3xl font-extrabold text-white">{{ $openCount }}</div>
            <div class="mt-1 text-xs font-medium text-blue-100/70">Pending + scheduled</div>
        </a>

        <a href="{{ route('admin.bookings.index', ['bucket' => 'today']) }}"
           class="rounded-3xl border border-indigo-400/20 bg-indigo-500/10 p-5 shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:border-indigo-400/40 hover:bg-indigo-500/20 {{ $tileClass('today', 'bucket') }}">
            <div class="text-sm font-bold text-indigo-300">Today’s Bookings</div>
            <div class="mt-2 text-3xl font-extrabold text-white">{{ $bookingCounts['today'] ?? 0 }}</div>
            <div class="mt-1 text-xs font-medium text-indigo-100/70">Scheduled today</div>
        </a>

        <a href="{{ route('admin.bookings.index', ['status' => 'pending']) }}"
           class="rounded-3xl border border-yellow-400/20 bg-yellow-500/10 p-5 shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:border-yellow-400/40 hover:bg-yellow-500/20 {{ $tileClass('pending') }}">
            <div class="text-sm font-bold text-yellow-300">Pending</div>
            <div class="mt-2 text-3xl font-extrabold text-white">{{ $bookingCounts['pending'] ?? 0 }}</div>
            <div class="mt-1 text-xs font-medium text-yellow-100/70">Needs action</div>
        </a>

        <a href="{{ route('admin.bookings.index', ['status' => 'scheduled']) }}"
           class="rounded-3xl border border-green-400/20 bg-green-500/10 p-5 shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:border-green-400/40 hover:bg-green-500/20 {{ $tileClass('scheduled') }}">
            <div class="text-sm font-bold text-green-300">Scheduled</div>
            <div class="mt-2 text-3xl font-extrabold text-white">{{ $bookingCounts['scheduled'] ?? 0 }}</div>
            <div class="mt-1 text-xs font-medium text-green-100/70">Waiting for vehicle</div>
        </a>

        <a href="{{ route('admin.bookings.index', ['status' => 'converted_to_job']) }}"
           class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:border-blue-400/40 hover:bg-blue-500/20 {{ $tileClass('converted_to_job') }}">
            <div class="text-sm font-bold text-blue-300">Converted To Job</div>
            <div class="mt-2 text-3xl font-extrabold text-white">{{ $bookingCounts['converted_to_job'] ?? 0 }}</div>
            <div class="mt-1 text-xs font-medium text-blue-100/70">Moved to jobs</div>
        </a>

        <a href="{{ route('admin.bookings.index', ['status' => 'lost']) }}"
           class="rounded-3xl border border-red-400/20 bg-red-500/10 p-5 shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:border-red-400/40 hover:bg-red-500/20 {{ $tileClass('lost') }}">
            <div class="text-sm font-bold text-red-300">Lost Bookings</div>
            <div class="mt-2 text-3xl font-extrabold text-white">{{ $bookingCounts['lost'] ?? 0 }}</div>
            <div class="mt-1 text-xs font-medium text-red-100/70">Cancelled / rejected</div>
        </a>
    </div>

    {{-- Booking Buckets --}}
    <div class="sf-card">
        <div class="sf-card-header flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="sf-section-title">
                    Booking Buckets
                </h2>

                <p class="sf-section-subtitle">
                    Quick filters for slots, overdue bookings, priority, and missing vehicle data.
                </p>
            </div>

            @if($bucket || $status || $q)
                <a href="{{ $clearUrl }}" class="sf-link shrink-0">
                    Clear filters
                </a>
            @endif
        </div>

        <div class="sf-card-body">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
                @foreach($bucketCards as $card)
                    <a href="{{ route('admin.bookings.index', ['bucket' => $card['key']]) }}"
                       class="rounded-2xl border p-4 shadow-xl shadow-black/10 transition {{ $tileClass($card['key'], 'bucket') }}">
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-xl">{{ $card['emoji'] }}</div>
                            <div class="text-2xl font-extrabold text-white">{{ $card['count'] }}</div>
                        </div>

                        <div class="mt-3 text-sm font-extrabold text-white">
                            {{ $card['title'] }}
                        </div>

                        <div class="mt-1 text-xs font-medium text-slate-500">
                            {{ $card['note'] }}
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.bookings.index') }}" class="sf-card">
        <div class="sf-card-body">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <div class="md:col-span-2">
                    <label class="sf-label">
                        Search
                    </label>

                    <input type="text"
                           name="q"
                           value="{{ $q }}"
                           placeholder="Search client, phone, vehicle, booking ID..."
                           class="sf-input">
                </div>

                <div>
                    <label class="sf-label">
                        Status
                    </label>

                    <select name="status" class="sf-select">
                        <option value="">All statuses</option>

                        @foreach(['pending', 'scheduled', 'confirmed', 'converted_to_job', 'completed', 'lost'] as $statusOption)
                            <option value="{{ $statusOption }}" @selected($status === $statusOption)>
                                {{ $statusLabel($statusOption) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    @if($bucket)
                        <input type="hidden" name="bucket" value="{{ $bucket }}">
                    @endif

                    <button type="submit" class="sf-btn-primary w-full">
                        Filter
                    </button>

                    @if($bucket || $status || $q)
                        <a href="{{ $clearUrl }}" class="sf-btn-secondary">
                            Reset
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="sf-table-wrap">
        <div class="sf-table-scroll">
            <table class="sf-table">
                <thead>
                    <tr>
                        <th class="w-[22%]">Booking</th>
                        <th class="w-[20%]">Client / Vehicle</th>
                        <th class="w-[14%]">Date / Slot</th>
                        <th class="w-[12%]">Status</th>
                        <th class="w-[12%]">Priority</th>
                        <th class="w-[14%]">Next Action</th>
                        <th class="w-[6%] text-right">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($bookings as $booking)
                        @php
                            $date = $bookingDate($booking);
                            $dateFormatted = $date
                                ? \Illuminate\Support\Carbon::parse($date)->format('d M Y')
                                : '—';

                            $vehicle = $vehicleLabel($booking);
                        @endphp

                        <tr>
                            <td>
                                <div class="font-extrabold text-white">
                                    {{ $booking->name ?? 'Booking #' . $booking->id }}
                                </div>

                                <div class="mt-1 text-xs font-medium text-slate-500">
                                    Booking ID: #{{ $booking->id }}
                                    @if(!empty($booking->source))
                                        · {{ $booking->source }}
                                    @endif
                                </div>
                            </td>

                            <td>
                                <div class="font-bold text-slate-200">
                                    {{ $booking->client?->name ?? $booking->client_name ?? 'No client' }}
                                </div>

                                <div class="mt-1 text-xs font-medium text-slate-500">
                                    🚗 {{ $vehicle !== '' ? $vehicle : 'No vehicle' }}
                                </div>
                            </td>

                            <td>
                                <div class="font-bold text-slate-200">
                                    {{ $dateFormatted }}
                                </div>

                                <div class="mt-1">
                                    <span class="{{ $slotBadge($booking->slot ?? '') }}">
                                        {{ ucfirst(str_replace('_', ' ', $booking->slot ?? 'No slot')) }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                <span class="{{ $statusBadge($booking->status ?? 'pending') }}">
                                    {{ $statusLabel($booking->status ?? 'pending') }}
                                </span>
                            </td>

                            <td>
                                <span class="{{ $priorityBadge($booking->priority ?? 'medium') }}">
                                    {{ ucfirst($booking->priority ?? 'Medium') }}
                                </span>
                            </td>

                            <td>
                                <div class="font-bold text-orange-300">
                                    {{ $nextAction($booking) }}
                                </div>

                                @if(!empty($booking->assignedUser?->name))
                                    <div class="mt-1 text-xs text-slate-500">
                                        Assigned: {{ $booking->assignedUser->name }}
                                    </div>
                                @endif
                            </td>

                            <td class="text-right">
                                @if(Route::has('admin.bookings.show'))
                                    <a href="{{ route('admin.bookings.show', $booking) }}" class="sf-link">
                                        View
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="sf-empty">
                                    No bookings found.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if(method_exists($bookings, 'links'))
        <div class="text-slate-300">
            {{ $bookings->links() }}
        </div>
    @endif

</div>
@endsection