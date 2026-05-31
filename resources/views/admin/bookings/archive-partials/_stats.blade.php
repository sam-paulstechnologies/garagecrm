<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <div class="sf-booking-panel rounded-2xl border p-5 shadow-sm">
        <div class="text-xs font-extrabold uppercase tracking-wide sf-booking-faint">
            Archived Bookings
        </div>

        <div class="mt-3 text-3xl font-black text-orange-300">
            {{ method_exists($bookings, 'total') ? $bookings->total() : $bookings->count() }}
        </div>

        <div class="mt-2 text-sm font-semibold sf-booking-muted">
            Removed from active booking queue
        </div>
    </div>

    <div class="sf-booking-panel rounded-2xl border p-5 shadow-sm">
        <div class="text-xs font-extrabold uppercase tracking-wide sf-booking-faint">
            Available Action
        </div>

        <div class="mt-3 text-lg font-extrabold sf-booking-value">
            Restore Booking
        </div>

        <div class="mt-2 text-sm font-semibold sf-booking-muted">
            Bring booking back to active list
        </div>
    </div>

    <div class="sf-booking-panel rounded-2xl border p-5 shadow-sm">
        <div class="text-xs font-extrabold uppercase tracking-wide sf-booking-faint">
            Archive Purpose
        </div>

        <div class="mt-3 text-lg font-extrabold sf-booking-value">
            Clean Operations
        </div>

        <div class="mt-2 text-sm font-semibold sf-booking-muted">
            Keep old booking records safely stored
        </div>
    </div>
</div>
