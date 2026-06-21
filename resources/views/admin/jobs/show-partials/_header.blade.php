<div class="sf-hero-panel">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="sf-kicker">
                    Job Profile
                </div>

                <span class="{{ $statusBadge }}">
                    {{ ucwords(str_replace('_', ' ', $status)) }}
                </span>

                <span class="{{ $serviceBadge }}">
                    {{ $serviceBucket }}
                </span>
            </div>

            <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-white">
                {{ $job->job_code ?? 'Job' }}
            </h1>

            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-sm font-medium sf-job-muted">
                @if($contactTelUrl && $contactPhoneDisplay)
                    <a href="{{ $contactTelUrl }}" class="sf-job-hero-chip" title="Click to call this customer.">
                        {{ $contactPhoneDisplay }}
                    </a>
                @elseif($contactPhoneDisplay)
                    <span>{{ $contactPhoneDisplay }}</span>
                @endif

                <span>{{ $job->client?->name ?? 'No client' }}</span>
                <span>{{ $vehicleLabel ?: 'No vehicle' }}</span>
                <span>{{ $serviceBucket }}</span>
                <span>{{ $roiStatus }}</span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="#job-activity-timeline" class="sf-btn-secondary">
                View All Activity
            </a>

            <a href="{{ route('admin.jobs.edit', $job) }}" class="sf-btn-primary">
                Edit Job
            </a>

            <a href="{{ route('admin.jobs.index') }}" class="sf-btn-secondary">
                Open Jobs
            </a>

            @if($booking && Route::has('admin.bookings.show'))
                <a href="{{ route('admin.bookings.show', $booking) }}" class="sf-btn-secondary">
                    View Booking
                </a>
            @endif

            @if($invoice && Route::has('admin.invoices.show'))
                <a href="{{ route('admin.invoices.show', $invoice) }}" class="sf-btn-secondary">
                    View Invoice
                </a>
            @endif

            @if(Route::has('admin.jobs.archive') && empty($job->is_archived))
                <form method="POST" action="{{ route('admin.jobs.archive', $job) }}">
                    @csrf
                    <button type="submit" class="sf-btn-danger">
                        Archive
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
