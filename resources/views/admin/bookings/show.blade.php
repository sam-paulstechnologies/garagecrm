@extends('layouts.app')

@section('title', 'Booking Details')

@section('content')
@php
    $status = strtolower((string) ($booking->status ?? 'pending'));
    $priority = strtolower((string) ($booking->priority ?? 'medium'));

    $bookingDateRaw = $booking->booking_date
        ?? $booking->scheduled_at
        ?? $booking->date
        ?? $booking->preferred_date
        ?? null;

    $bookingDate = $bookingDateRaw
        ? \Illuminate\Support\Carbon::parse($bookingDateRaw)
        : null;

    $expectedCloseDate = !empty($booking->expected_close_date)
        ? \Illuminate\Support\Carbon::parse($booking->expected_close_date)
        : null;

    $vehicleLabel = '';

    if (!empty($booking->vehicle)) {
        $vehicleLabel = trim(
            ($booking->vehicle->year ? $booking->vehicle->year . ' ' : '') .
            ($booking->vehicle->make?->name ?? $booking->vehicle->vehicleMake?->name ?? '') . ' ' .
            ($booking->vehicle->model?->name ?? $booking->vehicle->vehicleModel?->name ?? '') . ' ' .
            ($booking->vehicle->plate_number ? '(' . $booking->vehicle->plate_number . ')' : '')
        );
    }

    if ($vehicleLabel === '') {
        $vehicleLabel = trim(
            ($booking->vehicleMake?->name ?? $booking->other_make ?? '') . ' ' .
            ($booking->vehicleModel?->name ?? $booking->other_model ?? '')
        );
    }

    $servicesRaw = $booking->service_type
        ?? $booking->services
        ?? $booking->notes_services
        ?? '';

    $services = is_array($servicesRaw)
        ? collect($servicesRaw)
        : collect(explode(',', (string) $servicesRaw));

    $services = $services
        ->map(fn ($service) => trim((string) $service))
        ->filter()
        ->values();

    $statusBadge = match ($status) {
        'pending' => 'sf-badge-yellow',
        'scheduled', 'confirmed', 'approved' => 'sf-badge-green',
        'converted_to_job', 'completed' => 'sf-badge-blue',
        'lost', 'cancelled', 'canceled', 'rejected' => 'sf-badge-red',
        default => 'sf-badge-slate',
    };

    $priorityBadge = match ($priority) {
        'urgent' => 'sf-badge-red',
        'high' => 'sf-badge-orange',
        'medium' => 'sf-badge-yellow',
        'low' => 'sf-badge-slate',
        default => 'sf-badge-slate',
    };

    $slotBadge = match (strtolower((string) ($booking->slot ?? ''))) {
        'morning' => 'sf-badge-blue',
        'afternoon' => 'sf-badge-orange',
        'evening' => 'sf-badge-slate',
        'full_day' => 'sf-badge-green',
        default => 'sf-badge-slate',
    };

    $nextAction = match ($status) {
        'pending' => 'Confirm booking',
        'scheduled', 'confirmed', 'approved' => 'Receive vehicle',
        'converted_to_job', 'completed' => 'Review job',
        'lost', 'cancelled', 'canceled', 'rejected' => 'No action',
        default => 'Review',
    };

    $stepClass = function ($active, $done = false) {
        if ($active) {
            return 'sf-booking-step-active';
        }

        if ($done) {
            return 'sf-booking-step-done';
        }

        return 'sf-booking-step-idle';
    };
@endphp

    @include('admin.bookings.show-partials._styles')

    <div class="sf-page sf-booking-show-page mx-auto max-w-7xl px-4 py-6 space-y-6">
        @include('admin.bookings.show-partials._back_link')
        @include('admin.bookings.show-partials._header')
        @include('admin.bookings.show-partials._status_strip')

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div class="space-y-6 xl:col-span-2">
                @include('admin.bookings.show-partials._summary')
                @include('admin.bookings.show-partials._service_panel')
                @include('admin.bookings.show-partials._pickup_panel')
                @include('admin.bookings.show-partials._notes')
            </div>

            <div class="space-y-6">
                @include('admin.bookings.show-partials._client_panel')
                @include('admin.bookings.show-partials._vehicle_panel')
                @include('admin.bookings.show-partials._related_panel')
                @include('admin.bookings.show-partials._system_panel')
            </div>
        </div>
    </div>
@endsection
