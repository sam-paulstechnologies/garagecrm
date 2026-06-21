<div class="sf-page-header">
    <div>
        <h1 class="sf-page-title">
            Edit Booking #{{ $booking->id }}
        </h1>

        <p class="sf-page-subtitle">
            Update customer appointment details, vehicle context, slot, priority, and booking status.
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        @if(Route::has('admin.bookings.show'))
            <a href="{{ route('admin.bookings.show', $booking) }}" class="sf-btn-secondary">
                View Booking
            </a>
        @endif

        <a href="{{ route('admin.bookings.index') }}" class="sf-btn-secondary">
            Back to Bookings
        </a>
    </div>
</div>
