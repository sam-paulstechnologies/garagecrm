<div class="sf-page-header sf-calendar-hero">
    <div>
        <h1 class="sf-page-title">
            Booking Confirmation Calendar
        </h1>

        <p class="sf-page-subtitle">
            Review bookings that need manager confirmation, confirmed bookings, and bookings that must be rescheduled.
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        @if(\Illuminate\Support\Facades\Route::has('admin.bookings.index'))
            <a href="{{ route('admin.bookings.index') }}" class="sf-btn-secondary">
                Bookings
            </a>
        @endif

        @if(\Illuminate\Support\Facades\Route::has('admin.bookings.create'))
            <a href="{{ route('admin.bookings.create') }}" class="sf-btn-primary">
                + New Booking
            </a>
        @endif
    </div>
</div>
