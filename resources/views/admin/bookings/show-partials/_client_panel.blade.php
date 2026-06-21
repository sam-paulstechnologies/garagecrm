<div class="sf-booking-panel rounded-2xl border shadow-sm">
    <div class="border-b border-white/10 p-5">
        <h2 class="sf-section-title">
            Contact
        </h2>
    </div>

    <div class="space-y-3 p-5 text-sm">
        <div class="sf-booking-contact-name">
            {{ $booking->client?->name ?? 'No client linked' }}
        </div>

        <div class="sf-booking-contact-row">
            <span class="sf-booking-contact-label">Call</span>
            @if($contactTelUrl && $contactPhoneDisplay)
                <a href="{{ $contactTelUrl }}" class="sf-booking-contact-value">{{ $contactPhoneDisplay }}</a>
            @else
                <span class="sf-booking-contact-empty">Phone not set</span>
            @endif
        </div>

        <div class="sf-booking-contact-row">
            <span class="sf-booking-contact-label">WhatsApp</span>
            @if($bookingWhatsappInboxUrl !== '#')
                <a href="{{ $bookingWhatsappInboxUrl }}" class="sf-booking-wa-chip" title="Open WhatsApp Inbox" aria-label="Open WhatsApp Inbox">
                    WhatsApp Inbox
                </a>
            @else
                <span class="sf-booking-contact-empty">Not available</span>
            @endif
        </div>

        <div class="sf-booking-contact-row">
            <span class="sf-booking-contact-label">Email</span>
            @if($contactMailtoUrl)
                <a href="{{ $contactMailtoUrl }}" class="sf-booking-contact-value break-all">{{ $contactEmail }}</a>
            @else
                <span class="sf-booking-contact-empty">Email not set</span>
            @endif
        </div>

        @if($booking->client_id && Route::has('admin.clients.show'))
            <a href="{{ route('admin.clients.show', $booking->client_id) }}" class="sf-btn-secondary w-full">
                Open Client Profile
            </a>
        @endif
    </div>
</div>
