{{-- resources/views/admin/bookings/index-partials/_table.blade.php --}}

@php
    $statusLabel = function ($status) {
        return match (strtolower((string) $status)) {
            'pending' => 'Manager Confirmation',
            'scheduled' => 'Booking Confirmed',
            'reschedule_required' => 'Rescheduling Required',
            'confirmed' => 'Confirmed',
            'vehicle_received' => 'Vehicle Received',
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
            'reschedule_required' => 'sf-badge-red',
            'vehicle_received' => 'sf-badge-orange',
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

    $nextAction = function ($booking) {
        return match (strtolower((string) $booking->status)) {
            'pending' => 'Confirm booking',
            'scheduled', 'confirmed' => 'Receive vehicle',
            'reschedule_required' => 'Reschedule booking',
            'vehicle_received' => 'Create job',
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

    $phoneForBooking = function ($booking) {
        return $booking->client?->phone
            ?? $booking->client?->whatsapp
            ?? $booking->lead?->phone
            ?? $booking->lead?->phone_norm
            ?? $booking->opportunity?->client?->phone
            ?? $booking->opportunity?->lead?->phone
            ?? null;
    };

    $phoneService = app(\App\Services\PhoneNumberService::class);
@endphp

<div class="sf-booking-panel overflow-hidden rounded-2xl border shadow-sm">
    <div class="overflow-x-auto">
        <table class="sf-booking-table min-w-full table-fixed divide-y divide-slate-800 text-sm">
            <thead>
                <tr>
                    <th class="w-[22%] px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Booking</th>
                    <th class="w-[17%] px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Client / Vehicle</th>
                    <th class="w-[13%] px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Date / Slot</th>
                    <th class="w-[12%] px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Status</th>
                    <th class="w-[9%] px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Priority</th>
                    <th class="w-[12%] px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Next Action</th>
                    <th class="w-[15%] px-5 py-3 text-right text-xs font-black uppercase tracking-wide">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-800">
                @forelse($bookings as $booking)
                    @php
                        $date = $bookingDate($booking);
                        $dateFormatted = $date
                            ? \Illuminate\Support\Carbon::parse($date)->format('d M Y')
                            : '-';

                        $vehicle = $vehicleLabel($booking);
                        $phone = $phoneForBooking($booking);
                        $phoneDisplay = $phone ? $phoneService->formatForDisplay($phone) : null;
                        $phoneTelUrl = $phone ? $phoneService->buildTelUrl($phone) : null;
                    @endphp

                    <tr class="transition hover:bg-slate-800/30">
                        <td class="px-5 py-4 align-top" data-label="Booking">
                            <div class="min-w-0">
                            @if(Route::has('admin.bookings.show'))
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="sf-booking-name-link">
                                    {{ $booking->name ?? 'Booking #' . $booking->id }}
                                </a>
                            @else
                                <div class="sf-booking-title font-extrabold">
                                    {{ $booking->name ?? 'Booking #' . $booking->id }}
                                </div>
                            @endif

                                <div class="mt-1 text-sm font-bold sf-booking-value">
                                    @if($phoneDisplay && $phoneTelUrl)
                                        <a href="{{ $phoneTelUrl }}" class="sf-link break-all">
                                            {{ $phoneDisplay }}
                                        </a>
                                    @else
                                        <span class="sf-booking-muted">No phone</span>
                                    @endif
                                </div>

                                <div class="sf-booking-muted mt-1 text-xs font-medium">
                                    Booking ID: #{{ $booking->id }}
                                    @if(!empty($booking->source))
                                        &middot; {{ $booking->source }}
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td class="px-5 py-4 align-top">
                            <div class="sf-booking-value font-bold">
                                {{ $booking->client?->name ?? $booking->client_name ?? 'No client' }}
                            </div>

                            <div class="sf-booking-muted mt-1 text-xs font-medium">
                                {{ $vehicle !== '' ? $vehicle : 'No vehicle' }}
                            </div>
                        </td>

                        <td class="px-5 py-4 align-top">
                            <div class="sf-booking-value font-bold">
                                {{ $dateFormatted }}
                            </div>

                            <div class="mt-1">
                                <span class="{{ $slotBadge($booking->slot ?? '') }}">
                                    {{ ucfirst(str_replace('_', ' ', $booking->slot ?? 'No slot')) }}
                                </span>
                            </div>
                        </td>

                        <td class="px-5 py-4 align-top">
                            <span class="{{ $statusBadge($booking->status ?? 'pending') }}">
                                {{ $statusLabel($booking->status ?? 'pending') }}
                            </span>
                        </td>

                        <td class="px-5 py-4 align-top">
                            <span class="{{ $priorityBadge($booking->priority ?? 'medium') }}">
                                {{ ucfirst($booking->priority ?? 'Medium') }}
                            </span>
                        </td>

                        <td class="px-5 py-4 align-top">
                            <div class="font-bold text-orange-300">
                                {{ $nextAction($booking) }}
                            </div>

                            @if(!empty($booking->assignedUser?->name))
                                <div class="sf-booking-muted mt-1 text-xs">
                                    Assigned: {{ $booking->assignedUser->name }}
                                </div>
                            @endif
                        </td>

                        <td class="px-5 py-4 text-right align-top">
                            <div class="sf-bookings-action-group">
                                @if(Route::has('admin.bookings.show'))
                                    <a href="{{ route('admin.bookings.show', $booking) }}" class="sf-bookings-action-pill sf-bookings-action-view">
                                        View
                                    </a>
                                @endif

                                @if(Route::has('admin.bookings.edit'))
                                    <a href="{{ route('admin.bookings.edit', $booking) }}" class="sf-bookings-action-pill sf-bookings-action-edit">
                                        Edit
                                    </a>
                                @endif

                                @if(Route::has('admin.bookings.archive') && empty($booking->is_archived))
                                    <form method="POST" action="{{ route('admin.bookings.archive', $booking) }}" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="sf-bookings-action-pill sf-bookings-action-archive">
                                            Archive
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-10">
                            <div class="sf-booking-soft-panel rounded-2xl border p-8 text-center">
                                <div class="sf-booking-title font-extrabold">
                                    No bookings found.
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
