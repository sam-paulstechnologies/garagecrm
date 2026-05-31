{{-- resources/views/admin/bookings/index-partials/_hero.blade.php --}}

<div class="sf-booking-panel rounded-2xl border p-5 shadow-sm">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="sf-kicker">Booking Command Center</div>

            <h1 class="sf-booking-title mt-3 text-3xl font-extrabold tracking-tight">
                Bookings
            </h1>

            <p class="sf-booking-muted mt-2 max-w-3xl text-sm font-medium">
                Default view shows open bookings. Converted jobs and lost bookings are available through filters.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(Route::has('admin.bookings.archived'))
                <a href="{{ route('admin.bookings.archived') }}" class="sf-btn-secondary">
                    Archived
                </a>
            @endif

            @if(Route::has('admin.bookings.create'))
                <a href="{{ route('admin.bookings.create') }}" class="sf-btn-primary">
                    + New Booking
                </a>
            @endif
        </div>
    </div>
</div>
