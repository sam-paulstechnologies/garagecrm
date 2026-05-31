@if($booking->pickup_required)
    <div class="sf-booking-panel rounded-2xl border shadow-sm">
        <div class="border-b border-white/10 p-5">
            <h2 class="sf-section-title">
                Pickup Details
            </h2>
        </div>

        <div class="p-5">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="sf-booking-soft-panel rounded-2xl border p-4">
                    <div class="text-xs font-extrabold uppercase tracking-wide sf-booking-faint">Pickup Address</div>
                    <div class="mt-1 font-bold sf-booking-muted">
                        {{ $booking->pickup_address ?? '-' }}
                    </div>
                </div>

                <div class="sf-booking-soft-panel rounded-2xl border p-4">
                    <div class="text-xs font-extrabold uppercase tracking-wide sf-booking-faint">Pickup Contact Number</div>
                    <div class="mt-1 font-bold sf-booking-muted">
                        {{ $booking->pickup_contact_number ?? '-' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
