<div class="sf-card">
    <div class="sf-card-header flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="sf-section-title">
                Booking Calendar
            </h2>

            <p class="sf-section-subtitle">
                Click a booking to open its detail page. Calendar actions are read-only.
            </p>
        </div>

        @include('admin.calendar.index-partials._legend')
    </div>

    <div class="sf-card-body">
        <div
            id="calendar"
            data-events="{{ route('admin.calendar.events') }}"
            data-initial-assigned-user="{{ $calendarFilters['assigned_user'] ?? 'all' }}"
            data-initial-status="{{ $calendarFilters['status'] ?? 'all' }}"
            data-initial-slot="{{ $calendarFilters['slot'] ?? 'all' }}"
            class="garage-calendar rounded-3xl border border-white/10 bg-slate-950/60 p-3"
        ></div>
    </div>
</div>
