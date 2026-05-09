@extends('layouts.app')

@section('content')
@php
    $q = $q ?? request('q', '');
    $status = $status ?? request('status', '');
    $bucket = $bucket ?? request('bucket', '');

    $today = now()->toDateString();

    $bookingCounts = array_merge([
        'open' => 0,
        'today' => 0,
        'upcoming' => 0,
        'pending' => 0,
        'scheduled' => 0,
        'converted_to_job' => 0,
        'lost' => 0,

        // backward compatibility
        'confirmed' => 0,
        'completed' => 0,
    ], $bookingCounts ?? []);

    $bucketCounts = array_merge([
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
            'converted_to_job' => 'Converted To Job',
            'lost' => 'Lost Booking',
            default => ucwords(str_replace('_', ' ', (string) $status)),
        };
    };

    $statusBadge = function ($status) {
        return match (strtolower((string) $status)) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'scheduled' => 'bg-green-100 text-green-800',
            'converted_to_job' => 'bg-blue-100 text-blue-800',
            'lost' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-700',
        };
    };

    $priorityBadge = function ($priority) {
        return match (strtolower((string) $priority)) {
            'urgent' => 'bg-red-100 text-red-800',
            'high' => 'bg-orange-100 text-orange-800',
            'medium' => 'bg-yellow-100 text-yellow-800',
            'low' => 'bg-gray-100 text-gray-700',
            default => 'bg-gray-100 text-gray-700',
        };
    };

    $slotBadge = function ($slot) {
        return match (strtolower((string) $slot)) {
            'morning' => 'bg-blue-100 text-blue-800',
            'afternoon' => 'bg-purple-100 text-purple-800',
            'evening' => 'bg-indigo-100 text-indigo-800',
            'full_day' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-700',
        };
    };

    $tileClass = function ($key, $type = 'status') use ($status, $bucket) {
        $active = $type === 'status'
            ? $status === $key
            : $bucket === $key;

        return $active
            ? 'ring-2 ring-blue-400 border-blue-200'
            : '';
    };

    $nextAction = function ($booking) {
        return match (strtolower((string) $booking->status)) {
            'pending' => 'Confirm booking',
            'scheduled' => 'Receive vehicle',
            'converted_to_job' => 'Review job',
            'lost' => 'No action',
            default => 'Review',
        };
    };
@endphp

<div class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-6 py-6 space-y-6">

        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">
                    Bookings
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Default view shows open bookings only. Converted jobs and lost bookings are available through tiles.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.bookings.create') }}"
                   class="inline-flex items-center px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700">
                    + New Booking
                </a>
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-100 text-green-800 px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="rounded-lg bg-yellow-50 border border-yellow-100 text-yellow-800 px-4 py-3 text-sm">
                {{ session('warning') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-lg bg-red-50 border border-red-100 text-red-800 px-4 py-3 text-sm">
                {{ session('error') }}
            </div>
        @endif

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-4">

            <a href="{{ route('admin.bookings.index') }}"
               class="rounded-xl border border-blue-100 bg-blue-50 p-5 block hover:bg-blue-100 transition {{ (!$status && !$bucket) ? 'ring-2 ring-blue-400 border-blue-200' : '' }}">
                <div class="text-sm font-medium text-blue-700">Open Bookings</div>
                <div class="text-3xl font-bold text-blue-900 mt-2">{{ $openCount }}</div>
                <div class="text-xs text-blue-700 mt-1">Pending + scheduled</div>
            </a>

            <a href="{{ route('admin.bookings.index', ['bucket' => 'today']) }}"
               class="rounded-xl border border-indigo-100 bg-indigo-50 p-5 block hover:bg-indigo-100 transition {{ $tileClass('today', 'bucket') }}">
                <div class="text-sm font-medium text-indigo-700">Today’s Bookings</div>
                <div class="text-3xl font-bold text-indigo-900 mt-2">{{ $bookingCounts['today'] ?? 0 }}</div>
                <div class="text-xs text-indigo-700 mt-1">Scheduled today</div>
            </a>

            <a href="{{ route('admin.bookings.index', ['status' => 'pending']) }}"
               class="rounded-xl border border-yellow-100 bg-yellow-50 p-5 block hover:bg-yellow-100 transition {{ $tileClass('pending') }}">
                <div class="text-sm font-medium text-yellow-700">Pending</div>
                <div class="text-3xl font-bold text-yellow-900 mt-2">{{ $bookingCounts['pending'] ?? 0 }}</div>
                <div class="text-xs text-yellow-700 mt-1">Needs action</div>
            </a>

            <a href="{{ route('admin.bookings.index', ['status' => 'scheduled']) }}"
               class="rounded-xl border border-green-100 bg-green-50 p-5 block hover:bg-green-100 transition {{ $tileClass('scheduled') }}">
                <div class="text-sm font-medium text-green-700">Scheduled</div>
                <div class="text-3xl font-bold text-green-900 mt-2">{{ $bookingCounts['scheduled'] ?? 0 }}</div>
                <div class="text-xs text-green-700 mt-1">Waiting for vehicle</div>
            </a>

            <a href="{{ route('admin.bookings.index', ['status' => 'converted_to_job']) }}"
               class="rounded-xl border border-blue-100 bg-white p-5 block hover:bg-blue-50 transition {{ $tileClass('converted_to_job') }}">
                <div class="text-sm font-medium text-blue-700">Converted To Job</div>
                <div class="text-3xl font-bold text-blue-900 mt-2">{{ $bookingCounts['converted_to_job'] ?? 0 }}</div>
                <div class="text-xs text-blue-700 mt-1">Moved to jobs</div>
            </a>

            <a href="{{ route('admin.bookings.index', ['status' => 'lost']) }}"
               class="rounded-xl border border-red-100 bg-red-50 p-5 block hover:bg-red-100 transition {{ $tileClass('lost') }}">
                <div class="text-sm font-medium text-red-700">Lost Bookings</div>
                <div class="text-3xl font-bold text-red-900 mt-2">{{ $bookingCounts['lost'] ?? 0 }}</div>
                <div class="text-xs text-red-700 mt-1">Cancelled / rejected</div>
            </a>
        </div>

        {{-- Booking Buckets --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">
                        Booking Buckets
                    </h2>
                    <p class="text-sm text-gray-500">
                        Quick filters for open bookings, slots, overdue bookings, and missing data.
                    </p>
                </div>

                @if($bucket || $status || $q)
                    <a href="{{ $clearUrl }}"
                       class="text-sm text-blue-600 hover:underline shrink-0">
                        Clear filters
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-3">

                <a href="{{ route('admin.bookings.index', ['bucket' => 'morning']) }}"
                   class="rounded-xl border border-gray-100 bg-white hover:bg-gray-50 p-4 block transition {{ $tileClass('morning', 'bucket') }}">
                    <div class="flex items-center justify-between">
                        <div class="text-xl">🌅</div>
                        <div class="text-xl font-bold">{{ $bucketCounts['morning'] ?? 0 }}</div>
                    </div>
                    <div class="text-sm font-semibold mt-2">Morning</div>
                    <div class="text-xs text-gray-500">Morning slot</div>
                </a>

                <a href="{{ route('admin.bookings.index', ['bucket' => 'afternoon']) }}"
                   class="rounded-xl border border-gray-100 bg-white hover:bg-gray-50 p-4 block transition {{ $tileClass('afternoon', 'bucket') }}">
                    <div class="flex items-center justify-between">
                        <div class="text-xl">☀️</div>
                        <div class="text-xl font-bold">{{ $bucketCounts['afternoon'] ?? 0 }}</div>
                    </div>
                    <div class="text-sm font-semibold mt-2">Afternoon</div>
                    <div class="text-xs text-gray-500">Afternoon slot</div>
                </a>

                <a href="{{ route('admin.bookings.index', ['bucket' => 'evening']) }}"
                   class="rounded-xl border border-gray-100 bg-white hover:bg-gray-50 p-4 block transition {{ $tileClass('evening', 'bucket') }}">
                    <div class="flex items-center justify-between">
                        <div class="text-xl">🌙</div>
                        <div class="text-xl font-bold">{{ $bucketCounts['evening'] ?? 0 }}</div>
                    </div>
                    <div class="text-sm font-semibold mt-2">Evening</div>
                    <div class="text-xs text-gray-500">Evening slot</div>
                </a>

                <a href="{{ route('admin.bookings.index', ['bucket' => 'overdue']) }}"
                   class="rounded-xl border border-gray-100 bg-white hover:bg-gray-50 p-4 block transition {{ $tileClass('overdue', 'bucket') }}">
                    <div class="flex items-center justify-between">
                        <div class="text-xl">🚨</div>
                        <div class="text-xl font-bold">{{ $bucketCounts['overdue'] ?? 0 }}</div>
                    </div>
                    <div class="text-sm font-semibold mt-2">Overdue</div>
                    <div class="text-xs text-gray-500">Past pending</div>
                </a>

                <a href="{{ route('admin.bookings.index', ['bucket' => 'no_vehicle']) }}"
                   class="rounded-xl border border-gray-100 bg-white hover:bg-gray-50 p-4 block transition {{ $tileClass('no_vehicle', 'bucket') }}">
                    <div class="flex items-center justify-between">
                        <div class="text-xl">🚗</div>
                        <div class="text-xl font-bold">{{ $bucketCounts['no_vehicle'] ?? 0 }}</div>
                    </div>
                    <div class="text-sm font-semibold mt-2">No Vehicle</div>
                    <div class="text-xs text-gray-500">Missing vehicle</div>
                </a>

                <a href="{{ route('admin.bookings.index', ['bucket' => 'high_priority']) }}"
                   class="rounded-xl border border-gray-100 bg-white hover:bg-gray-50 p-4 block transition {{ $tileClass('high_priority', 'bucket') }}">
                    <div class="flex items-center justify-between">
                        <div class="text-xl">🔥</div>
                        <div class="text-xl font-bold">{{ $bucketCounts['high_priority'] ?? 0 }}</div>
                    </div>
                    <div class="text-sm font-semibold mt-2">High Priority</div>
                    <div class="text-xs text-gray-500">High / urgent</div>
                </a>
            </div>
        </div>

        {{-- Search --}}
        <form method="GET" action="{{ route('admin.bookings.index') }}"
              class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">

            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                <div class="md:col-span-9">
                    <label class="block text-xs font-medium text-gray-500 mb-1">
                        Search
                    </label>

                    <input type="text"
                           name="q"
                           value="{{ $q }}"
                           placeholder="Search client, vehicle, service, slot, status..."
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div class="md:col-span-3">
                    <button type="submit"
                            class="w-full px-4 py-2 rounded-lg bg-gray-900 text-white text-sm hover:bg-gray-800">
                        Search
                    </button>
                </div>

                @if($status)
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif

                @if($bucket)
                    <input type="hidden" name="bucket" value="{{ $bucket }}">
                @endif
            </div>
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Booking</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Client</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Vehicle</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Date</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Slot</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Priority</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Next Action</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    @forelse($bookings as $booking)
                        @php
                            $bookingDateLabel = optional($booking->booking_date)->format('d M Y') ?? '—';

                            $vehicleLabel = trim((string) ($booking->vehicle_label ?? ''));

                            $statusText = strtolower((string) ($booking->status ?? ''));
                        @endphp

                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.bookings.show', $booking->id) }}"
                                   class="font-medium text-blue-600 hover:underline">
                                    {{ $booking->name ?? 'Booking #'.$booking->id }}
                                </a>

                                @if($booking->service_type)
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ $booking->service_type }}
                                    </div>
                                @endif

                                @if($statusText === 'lost' && $booking->lost_reason)
                                    <div class="text-xs text-red-500 mt-1">
                                        Reason: {{ ucwords(str_replace('_', ' ', $booking->lost_reason)) }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">
                                    {{ $booking->client?->name ?? '—' }}
                                </div>

                                @if($booking->client?->phone)
                                    <div class="text-xs text-gray-400">
                                        {{ $booking->client->phone }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-gray-700">
                                {{ $vehicleLabel !== '' ? $vehicleLabel : '—' }}
                            </td>

                            <td class="px-4 py-3 text-gray-700">
                                {{ $bookingDateLabel }}
                            </td>

                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $slotBadge($booking->slot) }}">
                                    {{ ucfirst(str_replace('_', ' ', $booking->slot ?? '—')) }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $priorityBadge($booking->priority) }}">
                                    {{ ucfirst($booking->priority ?? '—') }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusBadge($booking->status) }}">
                                    {{ $statusLabel($booking->status ?? '—') }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <span class="text-xs text-gray-700">
                                    {{ $nextAction($booking) }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.bookings.show', $booking->id) }}"
                                   class="text-blue-600 hover:underline text-sm">
                                    View
                                </a>

                                <span class="text-gray-300 mx-1">|</span>

                                <a href="{{ route('admin.bookings.edit', $booking->id) }}"
                                   class="text-gray-700 hover:underline text-sm">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 text-center text-gray-400">
                                No bookings match the selected filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if(method_exists($bookings, 'links'))
            <div>
                {{ $bookings->links() }}
            </div>
        @endif

    </div>
</div>
@endsection