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

    $phoneService = app(\App\Services\PhoneNumberService::class);
    $contactPhone = $booking->client?->phone
        ?? $booking->client?->whatsapp
        ?? $booking->lead?->phone
        ?? $booking->lead?->phone_norm
        ?? $booking->opportunity?->client?->phone
        ?? $booking->opportunity?->lead?->phone
        ?? null;
    $contactPhoneDisplay = $contactPhone ? $phoneService->formatForDisplay($contactPhone) : null;
    $contactTelUrl = $contactPhone ? $phoneService->buildTelUrl($contactPhone) : null;
    $whatsappLookup = $contactPhone ? $phoneService->buildWhatsappLookupKey($contactPhone) : null;
    $bookingWhatsappInboxUrl = \Illuminate\Support\Facades\Route::has('admin.inbox.index')
        ? route('admin.inbox.index', $whatsappLookup ? ['search' => $whatsappLookup] : [])
        : '#';
    $whatsappFloatingUrl = $bookingWhatsappInboxUrl;
    $contactEmail = trim((string) ($booking->client?->email ?? $booking->lead?->email ?? $booking->opportunity?->lead?->email ?? ''));
    $contactMailtoUrl = $contactEmail !== '' ? 'mailto:' . $contactEmail : null;
    $job = $booking->job ?? null;
    $invoice = $job?->primaryInvoice ?? $job?->invoice ?? $job?->invoices?->first();

    $bookingStatusLabels = [
        'pending' => 'Pending',
        'scheduled' => 'Scheduled',
        'converted_to_job' => 'Converted To Job',
        'lost' => 'Lost Booking',
    ];
    $bookingStatusHelp = [
        'pending' => 'Booking needs manager review or confirmation.',
        'scheduled' => 'Booking date and slot are confirmed.',
        'converted_to_job' => 'Booking has moved into the Job module.',
        'lost' => 'Booking did not happen and requires a lost reason.',
    ];
    $bookingLostReasons = [
        'cancelled_by_customer' => 'Cancelled by customer',
        'rejected_by_garage' => 'Rejected by garage',
        'no_show' => 'No show',
        'slot_unavailable' => 'Slot unavailable',
        'duplicate' => 'Duplicate',
        'wrong_booking' => 'Wrong booking',
        'price_issue' => 'Price issue',
        'customer_postponed' => 'Customer postponed',
        'other' => 'Other',
    ];

    $activityItems = collect([
        [
            'title' => 'Booking created',
            'meta' => $booking->created_at?->format('d M Y, h:i A') ?? '-',
            'detail' => 'Booking record was created.',
        ],
    ]);

    if ($booking->updated_at && (!$booking->created_at || $booking->updated_at->ne($booking->created_at))) {
        $activityItems->push([
            'title' => 'Booking updated',
            'meta' => $booking->updated_at->format('d M Y, h:i A'),
            'detail' => 'Current status: ' . ($bookingStatusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status))),
        ]);
    }

    if ($booking->opportunity) {
        $activityItems->push([
            'title' => 'Opportunity linked',
            'meta' => $booking->opportunity->created_at?->format('d M Y, h:i A') ?? '-',
            'detail' => $booking->opportunity->title ?? 'Opportunity #' . $booking->opportunity->id,
        ]);
    }

    if ($job) {
        $activityItems->push([
            'title' => 'Job linked',
            'meta' => $job->created_at?->format('d M Y, h:i A') ?? '-',
            'detail' => $job->job_code ?? 'Job #' . $job->id,
        ]);
    }

    if ($invoice) {
        $activityItems->push([
            'title' => 'Invoice linked',
            'meta' => $invoice->created_at?->format('d M Y, h:i A') ?? '-',
            'detail' => $invoice->number ?? 'Invoice #' . $invoice->id,
        ]);
    }

    foreach (($communications ?? collect())->take(5) as $communication) {
        $activityItems->push([
            'title' => 'Communication recorded',
            'meta' => $communication->created_at?->format('d M Y, h:i A') ?? '-',
            'detail' => $communication->subject ?? $communication->message ?? $communication->body ?? 'Customer communication linked to this booking.',
        ]);
    }
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
                @include('admin.bookings.show-partials._activity_panel')
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
