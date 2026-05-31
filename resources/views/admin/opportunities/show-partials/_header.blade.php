<div class="sf-hero-panel">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="sf-kicker">Opportunity Profile</div>
                <span class="{{ $stageBadge($opportunity->stage) }}">{{ $stageLabel($opportunity->stage) }}</span>
                <span class="{{ $priorityBadge($opportunity->priority ?? 'medium') }}">{{ ucfirst($opportunity->priority ?? 'Medium') }}</span>
            </div>

            <h1 class="mt-3 text-3xl font-extrabold tracking-tight sf-opportunity-value">{{ $opportunity->title ?? 'Untitled Opportunity' }}</h1>

            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-sm font-medium sf-opportunity-muted">
                <span>{{ $opportunity->client?->name ?? 'No client' }}</span>
                <span>{{ $vehicleLabel !== '' ? $vehicleLabel : 'No vehicle' }}</span>
                <span>AED {{ number_format((float) $value, 2) }}</span>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @if(Route::has('admin.opportunities.edit'))
                <a href="{{ route('admin.opportunities.edit', $opportunity) }}" class="sf-btn-primary">Edit Opportunity</a>
            @endif

            @if($opportunity->client_id && Route::has('admin.clients.show'))
                <a href="{{ route('admin.clients.show', $opportunity->client_id) }}" class="sf-btn-secondary">View Client</a>
            @endif
        </div>
    </div>
</div>
