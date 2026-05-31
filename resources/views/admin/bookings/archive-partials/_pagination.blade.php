@if(!$bookings->isEmpty() && method_exists($bookings, 'links') && method_exists($bookings, 'hasPages') && $bookings->hasPages())
    <div class="sf-booking-panel rounded-2xl border p-4 shadow-sm">
        {{ $bookings->links() }}
    </div>
@endif
