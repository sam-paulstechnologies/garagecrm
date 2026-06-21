<div class="sf-hero-panel sf-booking-hero">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="sf-kicker">
                    Booking Profile
                </div>

                <span class="{{ $statusBadge }}">
                    {{ $bookingStatusLabels[$status] ?? ucfirst(str_replace('_', ' ', $booking->status ?? 'Pending')) }}
                </span>

                <span class="{{ $priorityBadge }}">
                    {{ ucfirst($booking->priority ?? 'Medium') }}
                </span>
            </div>

            <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-white sf-booking-title">
                {{ $booking->name ?? 'Booking #' . $booking->id }}
            </h1>

            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-sm font-medium sf-booking-muted">
                <span>{{ $booking->client?->name ?? 'No client' }}</span>
                @if($contactTelUrl && $contactPhoneDisplay)
                    <a href="{{ $contactTelUrl }}" class="sf-booking-hero-chip" title="Click to call this customer.">
                        {{ $contactPhoneDisplay }}
                    </a>
                @elseif($contactPhoneDisplay)
                    <span>{{ $contactPhoneDisplay }}</span>
                @endif
                <span>{{ $vehicleLabel !== '' ? $vehicleLabel : 'No vehicle' }}</span>
                <span>{{ $bookingDate ? $bookingDate->format('d M Y') : 'No date' }}</span>
                <span>{{ ucfirst(str_replace('_', ' ', $booking->slot ?? 'No slot')) }}</span>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="#booking-activity-timeline" class="sf-btn-secondary">
                View All Activity
            </a>

            @if(Route::has('admin.bookings.edit'))
                <a href="{{ route('admin.bookings.edit', $booking->id) }}" class="sf-btn-primary">
                    Edit Booking
                </a>
            @endif

            @if($booking->client_id && Route::has('admin.clients.show'))
                <a href="{{ route('admin.clients.show', $booking->client_id) }}" class="sf-btn-secondary">
                    View Client
                </a>
            @endif

            @if(!empty($booking->opportunity_id) && Route::has('admin.opportunities.show'))
                <a href="{{ route('admin.opportunities.show', $booking->opportunity_id) }}" class="sf-btn-secondary">
                    View Opportunity
                </a>
            @endif
        </div>
    </div>
</div>
