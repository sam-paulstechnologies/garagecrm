<div id="booking-activity-timeline" class="sf-booking-panel rounded-2xl border shadow-sm">
    <div class="border-b border-white/10 p-5">
        <h2 class="sf-section-title">
            Activity Timeline
        </h2>
    </div>

    <div class="space-y-3 p-5">
        @forelse($activityItems as $item)
            <div class="sf-booking-activity-card">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                    <div class="sf-booking-activity-title">
                        {{ $item['title'] }}
                    </div>

                    <div class="sf-booking-activity-meta">
                        {{ $item['meta'] }}
                    </div>
                </div>

                <div class="mt-2 sf-booking-activity-detail">
                    {{ \Illuminate\Support\Str::limit((string) $item['detail'], 180) }}
                </div>
            </div>
        @empty
            <div class="sf-booking-soft-panel rounded-2xl border p-5 text-sm font-semibold sf-booking-muted">
                No activity recorded yet.
            </div>
        @endforelse
    </div>
</div>
