<div class="sf-card sf-job-detail-section">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            Job Details
        </h2>

        <p class="sf-section-subtitle">
            Only service information required for customer visibility and future follow-up.
        </p>
    </div>

    <div class="sf-card-body">
        <div class="sf-job-field-grid">
            @foreach([
                'Service / Job Description' => $job->description ?: 'Not set',
                'Work Summary' => $job->work_summary ?: 'Not set',
                'Issues Found' => $job->issues_found ?: 'Not set',
                'Parts Used' => $job->parts_used ?: 'Not set',
                'Start Time' => $job->start_time?->format('d M Y, h:i A') ?? 'Not set',
                'Assigned Owner' => $job->assignedUser?->name ?? 'Unassigned',
            ] as $label => $value)
                <div class="sf-job-field-card {{ in_array($label, ['Service / Job Description', 'Work Summary'], true) ? 'md:col-span-2' : '' }}">
                    <div class="sf-job-field-label">{{ $label }}</div>
                    <div class="sf-job-field-value whitespace-pre-line">{{ $value }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
