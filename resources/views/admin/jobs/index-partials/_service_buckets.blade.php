<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            Cars in Service by Service Bucket
        </h2>

        <p class="sf-section-subtitle">
            These buckets show what kind of future WhatsApp follow-up can be prepared once the job is closed.
        </p>
    </div>

    <div class="sf-card-body">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">General</div>
                <div class="mt-2 text-2xl font-extrabold text-white">{{ $bucketCounts['General Service'] ?? 0 }}</div>
                <div class="mt-1 text-xs font-medium text-slate-500">Service reminder</div>
            </div>

            <div class="rounded-2xl border border-orange-400/20 bg-orange-500/10 p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide text-orange-300">Oil</div>
                <div class="mt-2 text-2xl font-extrabold text-white">{{ $bucketCounts['Oil Service'] ?? 0 }}</div>
                <div class="mt-1 text-xs font-medium text-orange-100/70">Oil follow-up</div>
            </div>

            <div class="rounded-2xl border border-blue-400/20 bg-blue-500/10 p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide text-blue-300">Battery</div>
                <div class="mt-2 text-2xl font-extrabold text-white">{{ $bucketCounts['Battery Service'] ?? 0 }}</div>
                <div class="mt-1 text-xs font-medium text-blue-100/70">Battery check</div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide text-slate-400">Tyres</div>
                <div class="mt-2 text-2xl font-extrabold text-white">{{ $bucketCounts['Tyre Service'] ?? 0 }}</div>
                <div class="mt-1 text-xs font-medium text-slate-500">Tyre reminder</div>
            </div>

            <div class="rounded-2xl border border-blue-400/20 bg-blue-500/10 p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide text-blue-300">AC</div>
                <div class="mt-2 text-2xl font-extrabold text-white">{{ $bucketCounts['AC Service'] ?? 0 }}</div>
                <div class="mt-1 text-xs font-medium text-blue-100/70">AC follow-up</div>
            </div>

            <div class="rounded-2xl border border-red-400/20 bg-red-500/10 p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide text-red-300">Brakes</div>
                <div class="mt-2 text-2xl font-extrabold text-white">{{ $bucketCounts['Brake Service'] ?? 0 }}</div>
                <div class="mt-1 text-xs font-medium text-red-100/70">Safety check</div>
            </div>

            <div class="rounded-2xl border border-green-400/20 bg-green-500/10 p-4">
                <div class="text-xs font-extrabold uppercase tracking-wide text-green-300">Wash</div>
                <div class="mt-2 text-2xl font-extrabold text-white">{{ $bucketCounts['Car Wash / Detailing'] ?? 0 }}</div>
                <div class="mt-1 text-xs font-medium text-green-100/70">Promo ready</div>
            </div>
        </div>
    </div>
</div>
