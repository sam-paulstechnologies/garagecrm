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

<div class="sf-profile-header-card overflow-hidden rounded-2xl border p-5 shadow-sm">
    <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">

        {{-- Client Identity --}}
        <div class="flex min-w-0 items-start gap-4 sm:items-center">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-3xl bg-gradient-to-br from-orange-500 to-orange-700 text-xl font-extrabold text-white shadow-lg shadow-orange-950/30">
                {{ $clientInitial }}
            </div>

            <div class="min-w-0">
                <div class="sf-profile-header-kicker inline-flex rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-wide">
                    Client Profile
                </div>

                <h1 class="sf-profile-header-name mt-2 truncate text-3xl font-extrabold tracking-tight">
                    {{ $client->name ?? 'Unnamed Client' }}
                </h1>

                <div class="sf-profile-header-meta mt-2 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm font-semibold">
                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                        <span>📞</span>
                        <span>{{ $client->phone ?? $client->whatsapp ?? 'No phone' }}</span>
                    </span>

                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                        <span>✉️</span>
                        <span>{{ $client->email ?? 'No email' }}</span>
                    </span>

                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                        <span>📍</span>
                        <span>{{ $client->city ?? $client->country ?? 'No location' }}</span>
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