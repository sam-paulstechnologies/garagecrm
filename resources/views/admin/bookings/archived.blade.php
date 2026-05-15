@extends('layouts.app')

@section('title', 'Archived Bookings')

@section('content')
@php
    $statusBadge = function ($status) {
        return match (strtolower((string) $status)) {
            'pending' => 'sf-badge-yellow',
            'scheduled', 'confirmed', 'approved' => 'sf-badge-green',
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
@endphp

<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Booking Archive
            </div>

            <h1 class="sf-page-title mt-3">
                Archived Bookings
            </h1>

            <p class="sf-page-subtitle">
                Review archived bookings and restore them back to the active booking list when needed.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.bookings.index') }}" class="sf-btn-secondary">
                ← Back to Active Bookings
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if (session('success'))
        <div class="sf-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="sf-alert-warning">
            {{ session('warning') }}
        </div>
    @endif

    @if (session('error'))
        <div class="sf-alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="sf-alert-danger">
            <div class="mb-2 font-extrabold">
                Please fix the following:
            </div>

            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Archived Bookings
            </div>

            <div class="sf-stat-value text-orange-300">
                {{ method_exists($bookings, 'total') ? $bookings->total() : $bookings->count() }}
            </div>

            <div class="sf-stat-note">
                Removed from active booking queue
            </div>
        </div>

        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Available Action
            </div>

            <div class="mt-3 text-lg font-extrabold text-white">
                Restore Booking
            </div>

            <div class="sf-stat-note">
                Bring booking back to active list
            </div>
        </div>

        <div class="sf-stat-card">
            <div class="sf-stat-label">
                Archive Purpose
            </div>

            <div class="mt-3 text-lg font-extrabold text-white">
                Clean Operations
            </div>

            <div class="sf-stat-note">
                Keep old booking records safely stored
            </div>
        </div>
    </div>

    {{-- Archived Bookings --}}
    @if($bookings->isEmpty())
        <div class="sf-empty">
            <div>No archived bookings found.</div>

            <div class="mt-4">
                <a href="{{ route('admin.bookings.index') }}" class="sf-btn-primary">
                    Go to Active Bookings
                </a>
            </div>
        </div>
    @else
        <div class="sf-table-wrap">
            <div class="sf-table-scroll">
                <table class="sf-table">
                    <thead>
                        <tr>
                            <th class="w-[18%]">Client</th>
                            <th class="w-[18%]">Vehicle</th>
                            <th class="w-[12%]">Date</th>
                            <th class="w-[10%]">Slot</th>
                            <th class="w-[12%]">Priority</th>
                            <th class="w-[14%]">Assigned</th>
                            <th class="w-[10%]">Status</th>
                            <th class="w-[6%] text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($bookings as $booking)
                            @php
                                $vehicle = optional($booking->vehicleData ?? $booking->vehicle ?? null);

                                $makeName = optional($vehicle->make)->name
                                    ?? optional($vehicle->vehicleMake)->name;

                                $modelName = optional($vehicle->model)->name
                                    ?? optional($vehicle->vehicleModel)->name;

                                $vehicleLabel = trim(($makeName ?? '') . ' ' . ($modelName ?? ''));

                                $dateValue = $booking->booking_date
                                    ?? optional($booking->scheduled_at)->format('Y-m-d')
                                    ?? ($booking->date ?? null);

                                $assigned = optional($booking->assignedUser)->name
                                    ?? optional($booking->assignee)->name;
                            @endphp

                            <tr>
                                {{-- Client --}}
                                <td>
                                    <div class="font-extrabold text-white">
                                        {{ optional($booking->client)->name ?? '—' }}
                                    </div>

                                    <div class="mt-1 text-xs font-medium text-slate-500">
                                        Booking ID: #{{ $booking->id }}
                                    </div>
                                </td>

                                {{-- Vehicle --}}
                                <td>
                                    <div class="font-bold text-slate-200">
                                        {{ $vehicleLabel !== '' ? $vehicleLabel : '—' }}
                                    </div>

                                    @if(!empty($vehicle->plate_number))
                                        <div class="mt-1 text-xs font-medium text-slate-500">
                                            {{ $vehicle->plate_number }}
                                        </div>
                                    @endif
                                </td>

                                {{-- Date --}}
                                <td>
                                    <div class="font-bold text-slate-200">
                                        {{ $dateValue ? \Illuminate\Support\Carbon::parse($dateValue)->format('d M Y') : '—' }}
                                    </div>
                                </td>

                                {{-- Slot --}}
                                <td>
                                    <span class="{{ $slotBadge($booking->slot ?? '') }}">
                                        {{ $booking->slot ? ucfirst(str_replace('_', ' ', $booking->slot)) : '—' }}
                                    </span>
                                </td>

                                {{-- Priority --}}
                                <td>
                                    <span class="{{ $priorityBadge($booking->priority ?? '') }}">
                                        {{ $booking->priority ? ucfirst($booking->priority) : '—' }}
                                    </span>
                                </td>

                                {{-- Assigned --}}
                                <td>
                                    <div class="font-bold text-slate-200">
                                        {{ $assigned ?? '—' }}
                                    </div>
                                </td>

                                {{-- Status --}}
                                <td>
                                    <span class="sf-badge-red">
                                        Archived
                                    </span>
                                </td>

                                {{-- Actions --}}
                                <td class="text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @if(Route::has('admin.bookings.show'))
                                            <a href="{{ route('admin.bookings.show', $booking) }}" class="sf-link">
                                                View
                                            </a>
                                        @endif

                                        <form action="{{ route('admin.bookings.restore', $booking) }}"
                                              method="POST"
                                              class="inline"
                                              onsubmit="return confirm('Restore this booking?');">
                                            @csrf
                                            @method('PUT')

                                            <button type="submit" class="sf-link">
                                                Restore
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
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
    @endif

</div>
@endsection