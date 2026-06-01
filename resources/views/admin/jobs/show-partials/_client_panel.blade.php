<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            Client
        </h2>
    </div>

    <div class="sf-card-body space-y-4 text-sm">
        <div>
            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                Name
            </div>

            <div class="mt-1 font-extrabold text-white">
                {{ $job->client?->name ?? '-' }}
            </div>
        </div>

        <div>
            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                Phone
            </div>

            <div class="mt-1 font-bold text-slate-200">
                {{ $job->client?->phone ?: $job->client?->phone_norm ?: '-' }}
            </div>
        </div>

        <div>
            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                Email
            </div>

            <div class="mt-1 break-words font-bold text-slate-200">
                {{ $job->client?->email ?: '-' }}
            </div>
        </div>
    </div>
</div>
