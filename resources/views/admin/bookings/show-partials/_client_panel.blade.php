<div class="sf-booking-panel rounded-2xl border shadow-sm">
    <div class="border-b border-white/10 p-5">
        <h2 class="sf-section-title">
            Client
        </h2>
    </div>

    <div class="space-y-4 p-5 text-sm">
        <div>
            <div class="text-xs font-bold uppercase tracking-wide sf-booking-faint">Name</div>
            <div class="mt-1 font-extrabold sf-booking-value">
                {{ $booking->client?->name ?? 'N/A' }}
            </div>
        </div>

        <div>
            <div class="text-xs font-bold uppercase tracking-wide sf-booking-faint">Phone</div>
            <div class="mt-1 font-bold sf-booking-muted">
                {{ $booking->client?->phone ?? $booking->client?->whatsapp ?? '-' }}
            </div>
        </div>

        <div>
            <div class="text-xs font-bold uppercase tracking-wide sf-booking-faint">Email</div>
            <div class="mt-1 break-words font-bold sf-booking-muted">
                {{ $booking->client?->email ?? '-' }}
            </div>
        </div>

        @if($booking->client_id && Route::has('admin.clients.show'))
            <a href="{{ route('admin.clients.show', $booking->client_id) }}" class="sf-btn-secondary w-full">
                Open Client Profile
            </a>
        @endif
    </div>
</div>
