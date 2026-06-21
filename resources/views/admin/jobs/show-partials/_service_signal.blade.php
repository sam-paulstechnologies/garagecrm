<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            Service Signal
        </h2>

        <p class="sf-section-subtitle">
            This job is currently detected under the following service bucket.
        </p>
    </div>

    <div class="sf-card-body">
        <div class="sf-job-field-grid">
            <div class="sf-job-field-card">
                <div class="sf-job-field-label">Service Bucket</div>
                <div class="mt-2">
                    <span class="{{ $serviceBadge }}" title="Detected from job description, work summary, issues found, and parts used.">
                        {{ $serviceBucket }}
                    </span>
                </div>
            </div>

            <div class="sf-job-field-card">
                <div class="sf-job-field-label">Explanation</div>
                <div class="sf-job-field-value">
                    Used for the correct WhatsApp follow-up once the job is closed.
                </div>
            </div>
        </div>
    </div>
</div>
