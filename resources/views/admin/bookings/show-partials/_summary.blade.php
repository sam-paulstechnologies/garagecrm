<div class="sf-booking-panel rounded-2xl border shadow-sm">
    <div class="border-b border-white/10 p-5">
        <h2 class="sf-section-title">
            Booking Summary
        </h2>
    </div>

    <div class="p-5">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="sf-booking-soft-panel rounded-2xl border p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide sf-booking-faint">Booking ID</div>
                <div class="mt-1 font-extrabold sf-booking-value">#{{ $booking->id }}</div>
            </div>

            <div class="sf-booking-soft-panel rounded-2xl border p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide sf-booking-faint">Booking Date</div>
                <div class="mt-1 font-extrabold sf-booking-value">
                    {{ $bookingDate ? $bookingDate->format('d M Y') : '-' }}
                </div>
            </div>

            <div class="sf-booking-soft-panel rounded-2xl border p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide sf-booking-faint">Slot</div>
                <div class="mt-2">
                    <span class="{{ $slotBadge }}">
                        {{ ucfirst(str_replace('_', ' ', $booking->slot ?? '-')) }}
                    </span>
                </div>
            </div>

            <div class="sf-booking-soft-panel rounded-2xl border p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide sf-booking-faint">Expected Duration</div>
                <div class="mt-1 font-extrabold sf-booking-value">
                    {{ $booking->expected_duration ?? '-' }} {{ $booking->expected_duration ? 'day(s)' : '' }}
                </div>
            </div>

            <div class="sf-booking-soft-panel rounded-2xl border p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide sf-booking-faint">Expected Close Date</div>
                <div class="mt-1 font-extrabold sf-booking-value">
                    {{ $expectedCloseDate ? $expectedCloseDate->format('d M Y') : '-' }}
                </div>
            </div>

            <div class="sf-booking-soft-panel rounded-2xl border p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide sf-booking-faint">Assigned To</div>
                <div class="mt-1 font-extrabold sf-booking-value">
                    {{ $booking->assignedUser?->name ?? $booking->assignee?->name ?? 'Unassigned' }}
                </div>
            </div>

            @if($status === 'reschedule_required' || filled($booking->reschedule_reason))
                <div class="sf-booking-soft-panel rounded-2xl border p-4 md:col-span-2">
                    <div class="text-xs font-extrabold uppercase tracking-wide sf-booking-faint">Reschedule Reason</div>
                    <div class="mt-1 font-extrabold sf-booking-value">
                        {{ $booking->reschedule_reason ?: 'Reason not set' }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
