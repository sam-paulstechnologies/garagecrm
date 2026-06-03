{{-- resources/views/admin/bookings/index-partials/_table.blade.php --}}

@php
    $statusLabel = function ($status) {
        return match (strtolower((string) $status)) {
            'pending' => 'Pending',
            'scheduled' => 'Scheduled',
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
@endphp

<div class="sf-booking-panel overflow-hidden rounded-2xl border shadow-sm">
    <div class="overflow-x-auto">
        <table class="sf-booking-table min-w-full divide-y divide-slate-800 text-sm">
            <thead>
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Booking</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Client / Vehicle</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Date / Slot</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Priority</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Next Action</th>
                    <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-wide">Action</th>
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
                    @endphp

                    <tr class="transition hover:bg-slate-800/30">
                        <td class="px-5 py-4 align-top">
                            <div class="sf-booking-title font-extrabold">
                                {{ $booking->name ?? 'Booking #' . $booking->id }}
                            </div>

                            <div class="sf-booking-muted mt-1 text-xs font-medium">
                                Booking ID: #{{ $booking->id }}
                                @if(!empty($booking->source))
                                    · {{ $booking->source }}
                                @endif
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
                            @if(Route::has('admin.bookings.show'))
                                <a href="{{ route('admin.bookings.show', $booking) }}" class="sf-link">
                                    View
                                </a>
                            @endif
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