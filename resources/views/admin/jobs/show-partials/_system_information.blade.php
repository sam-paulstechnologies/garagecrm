<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            System Information
        </h2>
    </div>

    <div class="sf-card-body">
        <div class="sf-job-field-grid">
            @foreach([
                'Created At' => $job->created_at?->format('d M Y, h:i A') ?? 'Not set',
                'Last Updated' => $job->updated_at?->format('d M Y, h:i A') ?? 'Not set',
                'Archived' => $job->is_archived ? 'Yes' : 'No',
                'Job ID' => '#' . $job->id,
            ] as $label => $value)
                <div class="sf-job-field-card">
                    <div class="sf-job-field-label">{{ $label }}</div>
                    <div class="sf-job-field-value">{{ $value }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
