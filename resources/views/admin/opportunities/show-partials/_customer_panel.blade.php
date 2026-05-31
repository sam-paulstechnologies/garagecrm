<div class="sf-opportunity-panel rounded-2xl border shadow-sm">
    <div class="border-b border-white/10 p-5">
        <h2 class="sf-section-title">Customer</h2>
    </div>

    <div class="space-y-4 p-5 text-sm">
        <div>
            <div class="text-xs font-bold uppercase tracking-wide sf-opportunity-muted">Client</div>
            <div class="mt-1 font-extrabold sf-opportunity-value">{{ $opportunity->client?->name ?? 'N/A' }}</div>
        </div>

        <div>
            <div class="text-xs font-bold uppercase tracking-wide sf-opportunity-muted">Phone</div>
            <div class="mt-1 font-bold sf-opportunity-value">{{ $opportunity->client?->phone ?? $opportunity->client?->whatsapp ?? '-' }}</div>
        </div>

        <div>
            <div class="text-xs font-bold uppercase tracking-wide sf-opportunity-muted">Email</div>
            <div class="mt-1 break-words font-bold sf-opportunity-value">{{ $opportunity->client?->email ?? '-' }}</div>
        </div>

        @if($opportunity->client_id && Route::has('admin.clients.show'))
            <a href="{{ route('admin.clients.show', $opportunity->client_id) }}" class="sf-btn-secondary w-full">Open Client Profile</a>
        @endif
    </div>
</div>
