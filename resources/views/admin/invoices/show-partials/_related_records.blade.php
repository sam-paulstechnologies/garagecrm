<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            Related Records
        </h2>
    </div>

    <div class="sf-card-body space-y-3">
        @if($job && Route::has('admin.jobs.show'))
            <a href="{{ route('admin.jobs.show', $job) }}" class="sf-invoice-related-card">
                <span>
                    <span class="sf-invoice-related-type">Job</span>
                    <span class="sf-invoice-related-title">{{ $job->job_code ?? 'Job #' . $job->id }}</span>
                    <span class="sf-invoice-related-meta">{{ ucwords(str_replace('_', ' ', $job->status ?? '-')) }}</span>
                </span>
                <span class="sf-invoice-related-action">View</span>
            </a>
        @endif

        @if($booking && Route::has('admin.bookings.show'))
            <a href="{{ route('admin.bookings.show', $booking) }}" class="sf-invoice-related-card">
                <span>
                    <span class="sf-invoice-related-type">Booking</span>
                    <span class="sf-invoice-related-title">#{{ $booking->id }}</span>
                    <span class="sf-invoice-related-meta">
                        {{ $booking->booking_date?->format('d M Y') ?? $booking->created_at?->format('d M Y') ?? '-' }}
                    </span>
                </span>
                <span class="sf-invoice-related-action">View</span>
            </a>
        @endif

        @if($invoice->client && Route::has('admin.clients.show'))
            <a href="{{ route('admin.clients.show', $invoice->client) }}" class="sf-invoice-related-card">
                <span>
                    <span class="sf-invoice-related-type">Client</span>
                    <span class="sf-invoice-related-title">{{ $invoice->client->name }}</span>
                    <span class="sf-invoice-related-meta">{{ $contactPhoneDisplay ?: 'No phone' }}</span>
                </span>
                <span class="sf-invoice-related-action">View</span>
            </a>
        @endif

        @if($vehicle)
            <div class="sf-invoice-related-card">
                <span>
                    <span class="sf-invoice-related-type">Vehicle</span>
                    <span class="sf-invoice-related-title">{{ $vehicleLabel ?: 'Vehicle linked' }}</span>
                    <span class="sf-invoice-related-meta">{{ $vehicle->plate_number ?? 'No plate number' }}</span>
                </span>
            </div>
        @endif

        @if(! $job && ! $booking && ! $invoice->client && ! $vehicle)
            <div class="sf-invoice-soft-panel rounded-2xl border p-5 text-sm font-semibold sf-invoice-muted">
                No related job, booking, client, or vehicle linked.
            </div>
        @endif
    </div>
</div>
