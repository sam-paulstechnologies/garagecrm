<div id="invoice-activity-timeline" class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            Activity Timeline
        </h2>
    </div>

    <div class="sf-card-body space-y-3">
        @forelse($activityItems as $item)
            <div class="sf-invoice-activity-card">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                    <div class="sf-invoice-activity-title">
                        {{ $item['title'] }}
                    </div>

                    <div class="sf-invoice-activity-meta">
                        {{ $item['meta'] }}
                    </div>
                </div>

                <div class="mt-2 sf-invoice-activity-detail">
                    {{ \Illuminate\Support\Str::limit((string) $item['detail'], 180) }}
                </div>
            </div>
        @empty
            <div class="sf-invoice-soft-panel rounded-2xl border p-5 text-sm font-semibold sf-invoice-muted">
                No activity recorded yet.
            </div>
        @endforelse
    </div>
</div>
