<div class="sf-booking-panel rounded-2xl border shadow-sm">
    <div class="border-b border-white/10 p-5">
        <h2 class="sf-section-title">
            System Details
        </h2>
    </div>

    <div class="p-5">
        <div class="sf-booking-system-grid">
            <div class="sf-booking-system-card">
                <div class="sf-booking-system-label">Created At</div>
                <div class="sf-booking-system-value">
                    {{ $booking->created_at?->format('d M Y, h:i A') ?? '-' }}
                </div>
            </div>

            <div class="sf-booking-system-card">
                <div class="sf-booking-system-label">Last Updated</div>
                <div class="sf-booking-system-value">
                    {{ $booking->updated_at?->format('d M Y, h:i A') ?? '-' }}
                </div>
            </div>
        </div>
    </div>
</div>
