<div class="sf-page-header">
    <div>
        <div class="sf-kicker">
            Garage Schedule
        </div>

        <h1 class="sf-page-title mt-3">
            Garage Calendar
        </h1>

        <p class="sf-page-subtitle">
            View bookings, jobs, and scheduled garage activity in calendar format.
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        @if(\Illuminate\Support\Facades\Route::has('admin.bookings.index'))
            <a href="{{ route('admin.bookings.index') }}" class="sf-btn-secondary">
                Bookings
            </a>
        @endif

        @if(\Illuminate\Support\Facades\Route::has('admin.jobs.index'))
            <a href="{{ route('admin.jobs.index') }}" class="sf-btn-secondary">
                Jobs
            </a>
        @endif

        @if(\Illuminate\Support\Facades\Route::has('admin.bookings.create'))
            <a href="{{ route('admin.bookings.create') }}" class="sf-btn-primary">
                + New Booking
            </a>
        @endif
    </div>
</div>
