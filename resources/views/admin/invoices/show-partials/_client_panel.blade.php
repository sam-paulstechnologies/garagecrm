<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            Contact
        </h2>
    </div>

    <div class="sf-card-body space-y-3 text-sm">
        <div class="sf-invoice-contact-name">
            {{ $invoice->client?->name ?? 'No client linked' }}
        </div>

        <div class="sf-invoice-contact-row">
            <span class="sf-invoice-contact-label">Call</span>
            @if($contactTelUrl && $contactPhoneDisplay)
                <a href="{{ $contactTelUrl }}" class="sf-invoice-contact-value">{{ $contactPhoneDisplay }}</a>
            @else
                <span class="sf-invoice-contact-empty">Phone not set</span>
            @endif
        </div>

        <div class="sf-invoice-contact-row">
            <span class="sf-invoice-contact-label">WhatsApp</span>
            @if($invoiceWhatsappInboxUrl !== '#')
                <a href="{{ $invoiceWhatsappInboxUrl }}" class="sf-invoice-wa-chip" title="Open WhatsApp Inbox" aria-label="Open WhatsApp Inbox">
                    WhatsApp Inbox
                </a>
            @else
                <span class="sf-invoice-contact-empty">Not available</span>
            @endif
        </div>

        <div class="sf-invoice-contact-row">
            <span class="sf-invoice-contact-label">Email</span>
            @if($contactMailtoUrl)
                <a href="{{ $contactMailtoUrl }}" class="sf-invoice-contact-value break-all">{{ $contactEmail }}</a>
            @else
                <span class="sf-invoice-contact-empty">Email not set</span>
            @endif
        </div>
    </div>
</div>
