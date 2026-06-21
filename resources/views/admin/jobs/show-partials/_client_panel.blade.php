<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            Contact
        </h2>
    </div>

    <div class="sf-card-body space-y-3 text-sm">
        <div class="sf-job-contact-name">
            {{ $job->client?->name ?? 'No client linked' }}
        </div>

        <div class="sf-job-contact-row">
            <span class="sf-job-contact-label">Call</span>
            @if($contactTelUrl && $contactPhoneDisplay)
                <a href="{{ $contactTelUrl }}" class="sf-job-contact-value">{{ $contactPhoneDisplay }}</a>
            @else
                <span class="sf-job-contact-empty">Phone not set</span>
            @endif
        </div>

        <div class="sf-job-contact-row">
            <span class="sf-job-contact-label">WhatsApp</span>
            @if($jobWhatsappInboxUrl !== '#')
                <a href="{{ $jobWhatsappInboxUrl }}" class="sf-job-wa-chip" title="Open WhatsApp Inbox" aria-label="Open WhatsApp Inbox">
                    WhatsApp Inbox
                </a>
            @else
                <span class="sf-job-contact-empty">Not available</span>
            @endif
        </div>

        <div class="sf-job-contact-row">
            <span class="sf-job-contact-label">Email</span>
            @if($contactMailtoUrl)
                <a href="{{ $contactMailtoUrl }}" class="sf-job-contact-value break-all">{{ $contactEmail }}</a>
            @else
                <span class="sf-job-contact-empty">Email not set</span>
            @endif
        </div>
    </div>
</div>
