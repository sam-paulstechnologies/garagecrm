<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            Related Records
        </h2>
    </div>

    <div class="sf-card-body space-y-3">
        @if($booking && Route::has('admin.bookings.show'))
            <a href="{{ route('admin.bookings.show', $booking) }}" class="sf-job-related-card">
                <span>
                    <span class="sf-job-related-type">Booking</span>
                    <span class="sf-job-related-title">#{{ $booking->id }}</span>
                    <span class="sf-job-related-meta">
                        {{ $booking->booking_date?->format('d M Y') ?? $booking->created_at?->format('d M Y') ?? '-' }}
                        @if($booking->status)
                            &middot; {{ $booking->status_label ?? ucfirst(str_replace('_', ' ', $booking->status)) }}
                        @endif
                    </span>
                </span>
                <span class="sf-job-related-action">View</span>
            </a>
        @endif

        @if($invoice && Route::has('admin.invoices.show'))
            <a href="{{ route('admin.invoices.show', $invoice) }}" class="sf-job-related-card">
                <span>
                    <span class="sf-job-related-type">Invoice</span>
                    <span class="sf-job-related-title">{{ $invoiceNumber ?: '#' . $invoice->id }}</span>
                    <span class="sf-job-related-meta">
                        {{ $invoice->invoice_date?->format('d M Y') ?? $invoice->created_at?->format('d M Y') ?? '-' }}
                        @if($invoice->status)
                            &middot; {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                        @endif
                    </span>
                </span>
                <span class="sf-job-related-action">View</span>
            </a>
        @endif

        @if($job->client && Route::has('admin.clients.show'))
            <a href="{{ route('admin.clients.show', $job->client) }}" class="sf-job-related-card">
                <span>
                    <span class="sf-job-related-type">Client</span>
                    <span class="sf-job-related-title">{{ $job->client->name }}</span>
                    <span class="sf-job-related-meta">{{ $contactPhoneDisplay ?: 'No phone' }}</span>
                </span>
                <span class="sf-job-related-action">View</span>
            </a>
        @endif

        @if($vehicle)
            <div class="sf-job-related-card">
                <span>
                    <span class="sf-job-related-type">Vehicle</span>
                    <span class="sf-job-related-title">{{ $vehicleLabel ?: 'Vehicle linked' }}</span>
                    <span class="sf-job-related-meta">{{ $vehicle->plate_number ?? 'No plate number' }}</span>
                </span>
            </div>
        @endif

        @if(! $booking && ! $invoice && ! $job->client && ! $vehicle)
            <div class="sf-job-soft-panel rounded-2xl border p-5 text-sm font-semibold sf-job-muted">
                No related booking, invoice, client, or vehicle linked.
            </div>
        @endif
    </div>
</div>
