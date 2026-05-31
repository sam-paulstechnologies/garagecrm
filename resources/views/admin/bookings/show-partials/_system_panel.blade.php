<div class="sf-booking-panel rounded-2xl border shadow-sm">
    <div class="border-b border-white/10 p-5">
        <h2 class="sf-section-title">
            System Details
        </h2>
    </div>

    <div class="space-y-4 p-5 text-sm">
        <div>
            <div class="text-xs font-bold uppercase tracking-wide sf-booking-faint">Created At</div>
            <div class="mt-1 font-bold sf-booking-muted">
                {{ $booking->created_at?->format('d M Y, h:i A') ?? '-' }}
            </div>
        </div>

        <div>
            <div class="text-xs font-bold uppercase tracking-wide sf-booking-faint">Last Updated</div>
            <div class="mt-1 font-bold sf-booking-muted">
                {{ $booking->updated_at?->format('d M Y, h:i A') ?? '-' }}
            </div>
        </div>

        <div>
            <div class="text-xs font-bold uppercase tracking-wide sf-booking-faint">Company ID</div>
            <div class="mt-1 font-bold sf-booking-muted">
                {{ $booking->company_id ?? '-' }}
            </div>
        </div>
    </div>
</div>
