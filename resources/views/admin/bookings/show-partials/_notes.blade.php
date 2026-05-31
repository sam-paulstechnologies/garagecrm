<div class="sf-booking-panel rounded-2xl border shadow-sm">
    <div class="border-b border-white/10 p-5">
        <h2 class="sf-section-title">
            Notes
        </h2>
    </div>

    <div class="p-5">
        @if(!empty($booking->notes))
            <div class="whitespace-pre-line text-sm font-medium leading-7 sf-booking-muted">
                {{ $booking->notes }}
            </div>
        @else
            <div class="sf-booking-soft-panel rounded-2xl border p-5 text-sm font-semibold sf-booking-muted">
                No notes added.
            </div>
        @endif
    </div>
</div>
