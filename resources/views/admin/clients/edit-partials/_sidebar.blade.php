{{-- resources/views/admin/clients/edit-partials/_sidebar.blade.php --}}

@php
    $showRoute = \Illuminate\Support\Facades\Route::has('admin.clients.show')
        ? route('admin.clients.show', $client->id)
        : null;
@endphp

{{-- Snapshot --}}
<div class="sf-edit-panel rounded-2xl border shadow-sm">
    <div class="border-b border-white/10 p-5">
        <h2 class="sf-edit-title text-lg font-extrabold tracking-tight">
            Client Snapshot
        </h2>

        <p class="sf-edit-muted mt-1 text-sm font-medium">
            Current profile context.
        </p>
    </div>

    <div class="space-y-4 p-5">
        <div class="sf-edit-side-box rounded-2xl border p-4">
            <div class="sf-edit-muted text-xs font-black uppercase tracking-wide">
                Name
            </div>

            <div class="sf-edit-value mt-2 font-extrabold">
                {{ $client->name ?? 'Unnamed Client' }}
            </div>
        </div>

        <div class="sf-edit-side-box rounded-2xl border p-4">
            <div class="sf-edit-muted text-xs font-black uppercase tracking-wide">
                Contact
            </div>

            <div class="sf-edit-value mt-2 font-bold">
                {{ $client->phone ?? $client->whatsapp ?? $client->email ?? 'No contact available' }}
            </div>
        </div>

        <div class="sf-edit-side-box rounded-2xl border p-4">
            <div class="sf-edit-muted text-xs font-black uppercase tracking-wide">
                Source
            </div>

            <div class="sf-edit-value mt-2 font-bold">
                {{ $client->source ?? 'N/A' }}
            </div>
        </div>

        <div class="sf-edit-side-box rounded-2xl border p-4">
            <div class="sf-edit-muted text-xs font-black uppercase tracking-wide">
                Status
            </div>

            <div class="mt-2">
                <span class="inline-flex rounded-full border border-blue-400/20 bg-blue-500/10 px-3 py-1 text-xs font-black text-blue-300">
                    {{ ucfirst(str_replace('_', ' ', $client->status ?? 'active')) }}
                </span>
            </div>
        </div>

        @if($client->is_vip ?? false)
            <div>
                <span class="sf-edit-vip-badge inline-flex rounded-full border px-3 py-1 text-xs font-black">
                    VIP Client
                </span>
            </div>
        @endif

        <div class="sf-edit-side-box rounded-2xl border p-4">
            <div class="sf-edit-muted text-xs font-black uppercase tracking-wide">
                Created
            </div>

            <div class="sf-edit-value mt-2 font-bold">
                {{ $client->created_at?->format('d M Y, h:i A') ?? '—' }}
            </div>
        </div>
    </div>
</div>

{{-- Guidelines --}}
<div class="sf-edit-panel rounded-2xl border shadow-sm">
    <div class="border-b border-white/10 p-5">
        <h2 class="sf-edit-title text-lg font-extrabold tracking-tight">
            Edit Guidelines
        </h2>
    </div>

    <div class="p-5">
        <ul class="space-y-4 text-sm">
            <li class="flex gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                    1
                </span>
                <span class="sf-edit-muted font-medium">
                    Keep phone and WhatsApp numbers with country code where possible.
                </span>
            </li>

            <li class="flex gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                    2
                </span>
                <span class="sf-edit-muted font-medium">
                    Use source consistently for reporting and segmentation.
                </span>
            </li>

            <li class="flex gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">
                    3
                </span>
                <span class="sf-edit-muted font-medium">
                    VIP flag should be used only for high-priority customers.
                </span>
            </li>
        </ul>
    </div>
</div>

{{-- Next Step --}}
<div class="sf-edit-next-card rounded-2xl border p-5 shadow-sm">
    <h3 class="sf-edit-next-title font-extrabold">
        After Updating
    </h3>

    <p class="sf-edit-next-text mt-2 text-sm font-medium leading-6">
        Review vehicles, bookings, invoices, and documents from the client profile page.
    </p>

    @if($showRoute)
        <a
            href="{{ $showRoute }}"
            class="mt-4 inline-flex h-10 items-center justify-center rounded-xl bg-orange-500 px-4 text-sm font-extrabold text-white shadow-lg shadow-orange-950/20 transition hover:bg-orange-600"
        >
            Open Profile
        </a>
    @endif
</div>