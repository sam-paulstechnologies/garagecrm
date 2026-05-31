<div class="sf-booking-panel rounded-2xl border shadow-sm">
    <div class="border-b border-white/10 p-5">
        <h2 class="sf-section-title">
            Related Records
        </h2>
    </div>

    <div class="space-y-3 p-5">
        @if(!empty($booking->opportunity_id) && Route::has('admin.opportunities.show'))
            <a href="{{ route('admin.opportunities.show', $booking->opportunity_id) }}" class="sf-btn-secondary w-full">
                View Opportunity
            </a>
        @endif

        @if(!empty($booking->job_id) && Route::has('admin.jobs.show'))
            <a href="{{ route('admin.jobs.show', $booking->job_id) }}" class="sf-btn-secondary w-full">
                View Job
            </a>
        @endif

        @if(empty($booking->opportunity_id) && empty($booking->job_id))
            <div class="sf-booking-soft-panel rounded-2xl border p-5 text-sm font-semibold sf-booking-muted">
                No related opportunity or job linked.
            </div>
        @endif
    </div>
</div>
