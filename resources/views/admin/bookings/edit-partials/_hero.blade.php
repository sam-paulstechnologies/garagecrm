<div class="sf-page-header">
    <div>
        <div class="sf-kicker">
            Booking Management
        </div>

        <h1 class="sf-page-title mt-3">
            Edit Booking #{{ $booking->id }}
        </h1>

        <p class="sf-page-subtitle">
            Update booking details, client, opportunity, vehicle, date, slot, priority, and assigned team member.
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
