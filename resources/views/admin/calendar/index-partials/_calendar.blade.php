<div class="sf-card">
    <div class="sf-card-header flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="sf-section-title">
                Schedule
            </h2>

            <p class="sf-section-subtitle">
                Click an event to open the linked booking, job, or related record if available.
            </p>
        </div>

        @include('admin.calendar.index-partials._legend')
    </div>

    <div class="sf-card-body">
        <div
            id="calendar"
            data-events="{{ route('admin.calendar.events') }}"
            class="garage-calendar rounded-3xl border border-white/10 bg-slate-950/60 p-3"
        ></div>
    </div>
</div>
