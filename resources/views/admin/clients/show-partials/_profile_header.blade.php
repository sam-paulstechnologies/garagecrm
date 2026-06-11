{{-- resources/views/admin/clients/show-partials/_profile_header.blade.php --}}

@php
    $clientInitial = strtoupper(substr($client->name ?? 'C', 0, 1));

    $editRoute = \Illuminate\Support\Facades\Route::has('admin.clients.edit')
        ? route('admin.clients.edit', $client->id)
        : null;

    $vehicleCreateRoute = \Illuminate\Support\Facades\Route::has('admin.vehicles.create')
        ? route('admin.vehicles.create', ['client_id' => $client->id])
        : null;

    $bookingCreateRoute = \Illuminate\Support\Facades\Route::has('admin.bookings.create')
        ? route('admin.bookings.create', ['client_id' => $client->id])
        : null;

    $phoneDisplay = $client->phone ?? $client->phone_norm ?? $client->whatsapp ?? null;
    $phoneNorm = $client->phone_norm
        ?? \App\Models\Client\Client::normalizePhone($phoneDisplay);
    $phoneHref = $phoneNorm ? 'tel:+' . ltrim($phoneNorm, '+') : null;

    $whatsappDisplay = $client->whatsapp ?? null;
    $whatsappNorm = \App\Models\Client\Client::normalizePhone($whatsappDisplay);
    $whatsappUrl = ($whatsappNorm && \Illuminate\Support\Facades\Route::has('admin.inbox.index'))
        ? route('admin.inbox.index', ['search' => ltrim($whatsappNorm, '+')])
        : null;
    $whatsappIsVerified = isset($client->whatsapp_verified) && (bool) $client->whatsapp_verified;

    $source = $client->source ?? null;
    $createdAt = $client->created_at ?? null;
    $isNewCustomer = $createdAt && $createdAt->gte(now()->subDays(30));
    $isVip = (bool) ($client->is_vip ?? false);
@endphp

<style>
    .sf-profile-header-card {
        border-color: rgba(30, 41, 59, 1);
        background: linear-gradient(135deg, #0f172a 0%, #111827 68%, rgba(124, 45, 18, 0.70) 100%);
        color: #ffffff;
    }

    .sf-profile-header-kicker {
        border-color: rgba(251, 146, 60, 0.20);
        background: rgba(249, 115, 22, 0.10);
        color: #fdba74;
    }

    .sf-profile-header-name {
        color: #ffffff;
    }

    .sf-profile-header-meta {
        color: #cbd5e1;
    }

    .sf-profile-contact-link {
        color: #dbeafe;
        text-decoration: none;
    }

    .sf-profile-contact-link:hover {
        color: #93c5fd;
    }

    .sf-profile-whatsapp-pill {
        border-color: rgba(74, 222, 128, 0.24);
        background: rgba(34, 197, 94, 0.12);
        color: #bbf7d0;
    }

    .sf-profile-whatsapp-pill:hover {
        background: rgba(34, 197, 94, 0.20);
        color: #dcfce7;
    }

    .sf-profile-badge {
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
    }

    .sf-profile-badge-vip {
        color: #fde68a;
        background: rgba(234, 179, 8, 0.14);
        border-color: rgba(250, 204, 21, 0.30);
    }

    .sf-profile-badge-new {
        color: #a7f3d0;
        background: rgba(16, 185, 129, 0.14);
        border-color: rgba(52, 211, 153, 0.28);
    }

    .sf-profile-badge-returning {
        color: #bfdbfe;
        background: rgba(59, 130, 246, 0.14);
        border-color: rgba(96, 165, 250, 0.28);
    }

    .sf-profile-badge-source {
        color: #e2e8f0;
        background: rgba(30, 41, 59, 0.84);
        border-color: rgba(100, 116, 139, 0.48);
    }

    .sf-profile-action-secondary {
        border-color: rgba(255, 255, 255, 0.14);
        background: rgba(255, 255, 255, 0.10);
        color: #ffffff;
    }

    .sf-profile-action-secondary:hover {
        background: rgba(255, 255, 255, 0.16);
    }

    .sf-profile-action-blue {
        border-color: rgba(147, 197, 253, 0.24);
        background: rgba(59, 130, 246, 0.16);
        color: #dbeafe;
    }

    .sf-profile-action-blue:hover {
        background: rgba(59, 130, 246, 0.24);
    }

    html[data-theme="light"] .sf-profile-header-card {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-profile-header-kicker {
        border-color: #fed7aa !important;
        background: #fff7ed !important;
        color: #c2410c !important;
    }

    html[data-theme="light"] .sf-profile-header-name {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-profile-header-meta {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-profile-contact-link {
        color: #1d4ed8 !important;
    }

    html[data-theme="light"] .sf-profile-contact-link:hover {
        color: #1e40af !important;
    }

    html[data-theme="light"] .sf-profile-whatsapp-pill {
        border-color: #86efac !important;
        background: #ecfdf5 !important;
        color: #047857 !important;
    }

    html[data-theme="light"] .sf-profile-whatsapp-pill:hover {
        background: #d1fae5 !important;
        color: #065f46 !important;
    }

    html[data-theme="light"] .sf-profile-badge-vip {
        color: #92400e !important;
        background: #fef3c7 !important;
        border-color: #f59e0b !important;
    }

    html[data-theme="light"] .sf-profile-badge-new {
        color: #047857 !important;
        background: #ecfdf5 !important;
        border-color: #6ee7b7 !important;
    }

    html[data-theme="light"] .sf-profile-badge-returning {
        color: #1d4ed8 !important;
        background: #eff6ff !important;
        border-color: #93c5fd !important;
    }

    html[data-theme="light"] .sf-profile-badge-source {
        color: #334155 !important;
        background: #f8fafc !important;
        border-color: #cbd5e1 !important;
    }

    html[data-theme="light"] .sf-profile-action-secondary {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06) !important;
    }

    html[data-theme="light"] .sf-profile-action-secondary:hover {
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-profile-action-blue {
        border-color: #bfdbfe !important;
        background: #eff6ff !important;
        color: #1d4ed8 !important;
    }

    html[data-theme="light"] .sf-profile-action-blue:hover {
        background: #dbeafe !important;
    }
</style>

<div class="sf-profile-header-card overflow-hidden rounded-2xl border p-4 shadow-sm sm:p-5">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">

        {{-- Client Identity --}}
        <div class="flex min-w-0 items-start gap-4 sm:items-center">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-3xl bg-gradient-to-br from-orange-500 to-orange-700 text-xl font-extrabold text-white shadow-lg shadow-orange-950/30">
                {{ $clientInitial }}
            </div>

            <div class="min-w-0">
                <div class="sf-profile-header-kicker inline-flex rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-wide">
                    Client Profile
                </div>

                <div class="mt-2 flex flex-wrap gap-1.5">
                    @if($isVip)
                        <span class="sf-profile-badge sf-profile-badge-vip inline-flex rounded-full border px-2 py-0.5 text-[10px] font-black uppercase tracking-wide">
                            VIP
                        </span>
                    @endif

                    @if($isNewCustomer)
                        <span class="sf-profile-badge sf-profile-badge-new inline-flex rounded-full border px-2 py-0.5 text-[10px] font-black uppercase tracking-wide">
                            New
                        </span>
                    @else
                        <span class="sf-profile-badge sf-profile-badge-returning inline-flex rounded-full border px-2 py-0.5 text-[10px] font-black uppercase tracking-wide">
                            Returning
                        </span>
                    @endif

                    @if($source)
                        <span class="sf-profile-badge sf-profile-badge-source inline-flex rounded-full border px-2 py-0.5 text-[10px] font-black uppercase tracking-wide">
                            {{ str($source)->replace('_', ' ')->title() }}
                        </span>
                    @endif
                </div>

                <h1 class="sf-profile-header-name mt-2 truncate text-2xl font-extrabold tracking-tight sm:text-3xl">
                    {{ $client->name ?? 'Unnamed Client' }}
                </h1>

                <div class="sf-profile-header-meta mt-2 flex max-w-full flex-wrap items-center gap-x-4 gap-y-2 text-sm font-semibold">
                    <span class="inline-flex min-w-0 items-center gap-1.5">
                        <span>📞</span>
                        @if($phoneHref)
                            <a href="{{ $phoneHref }}" class="sf-profile-contact-link min-w-0 break-all font-extrabold">
                                {{ $phoneDisplay }}
                            </a>
                        @else
                            <span>No phone</span>
                        @endif
                    </span>

                    @if($whatsappUrl)
                        <a
                            href="{{ $whatsappUrl }}"
                            title="Open WhatsApp conversation in Inbox"
                            class="sf-profile-whatsapp-pill inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-black"
                        >
                            <span>WA</span>
                            <span>{{ $whatsappIsVerified ? 'Verified' : 'Available' }}</span>
                        </a>
                    @endif

                    <span class="inline-flex min-w-0 items-center gap-1.5">
                        <span>✉️</span>
                        <span class="min-w-0 break-all">{{ $client->email ?? 'No email' }}</span>
                    </span>

                    <span class="inline-flex min-w-0 items-center gap-1.5">
                        <span>📍</span>
                        <span class="min-w-0 break-words">{{ $client->city ?? $client->country ?? 'No location' }}</span>
                    </span>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex shrink-0 flex-wrap items-center gap-2 xl:justify-end">
            @if($editRoute)
                <a
                    href="{{ $editRoute }}"
                    class="inline-flex h-10 items-center justify-center rounded-xl bg-orange-500 px-4 text-sm font-extrabold text-white shadow-lg shadow-orange-950/20 transition hover:bg-orange-600"
                >
                    Edit Client
                </a>

                <a
                    href="{{ $editRoute }}#client-contact"
                    class="sf-profile-action-secondary inline-flex h-10 items-center justify-center rounded-xl border px-4 text-sm font-bold transition"
                >
                    Quick Edit
                </a>
            @endif

            @if($vehicleCreateRoute)
                <a
                    href="{{ $vehicleCreateRoute }}"
                    class="sf-profile-action-secondary inline-flex h-10 items-center justify-center rounded-xl border px-4 text-sm font-bold transition"
                >
                    + Add Vehicle
                </a>
            @endif

            @if($bookingCreateRoute)
                <a
                    href="{{ $bookingCreateRoute }}"
                    class="sf-profile-action-blue inline-flex h-10 items-center justify-center rounded-xl border px-4 text-sm font-bold transition"
                >
                    + Booking
                </a>
            @endif
        </div>

    </div>
</div>
