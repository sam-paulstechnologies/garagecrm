{{-- resources/views/admin/clients/show-partials/sections/_details_section.blade.php --}}

@php
    $editRoute = \Illuminate\Support\Facades\Route::has('admin.clients.edit')
        ? route('admin.clients.edit', $client->id)
        : null;

    $details = [
        ['label' => 'Name', 'value' => $client->name ?? 'N/A'],
        ['label' => 'Email', 'value' => $client->email ?? 'N/A'],
        ['label' => 'Phone', 'value' => $client->phone ?? 'N/A'],
        ['label' => 'Location', 'value' => $client->city ?? $client->country ?? 'N/A'],
        ['label' => 'Source', 'value' => $client->source ?? 'N/A'],
    ];

    $extraDetails = [
        ['label' => 'WhatsApp', 'value' => $client->whatsapp ?? 'N/A'],
        ['label' => 'Preferred Channel', 'value' => $client->preferred_channel ?? 'N/A'],
        ['label' => 'Status', 'value' => $client->status ?? 'N/A'],
        ['label' => 'Country', 'value' => $client->country ?? 'N/A'],
        ['label' => 'City', 'value' => $client->city ?? 'N/A'],
        ['label' => 'Address', 'value' => $client->address ?? 'N/A'],
    ];
@endphp

<style>
    .sf-details-shell {
        border-color: rgba(30, 41, 59, 1);
        background: rgba(15, 23, 42, 0.70);
        color: #ffffff;
    }

    .sf-details-title {
        color: #ffffff;
    }

    .sf-details-muted {
        color: #cbd5e1;
    }

    .sf-details-box {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(2, 6, 23, 0.38);
    }

    .sf-details-label {
        color: #94a3b8;
    }

    .sf-details-value {
        color: #ffffff;
    }

    .sf-details-view-more {
        color: #fb923c;
    }

    .sf-details-view-more:hover {
        color: #fdba74;
    }

    html[data-theme="light"] .sf-details-shell {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-details-title {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-details-muted {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-details-box {
        border-color: #d9e1ec !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-details-label {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-details-value {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-details-view-more {
        color: #ea580c !important;
    }

    html[data-theme="light"] .sf-details-view-more:hover {
        color: #c2410c !important;
    }
</style>

<section id="details" class="sf-details-shell rounded-2xl border p-5 shadow-sm" x-data="{ expanded: false }">
    <div class="mb-5 flex items-start justify-between gap-4">
        <div>
            <h2 class="sf-details-title text-lg font-extrabold tracking-tight">
                Client Details
            </h2>

            <p class="sf-details-muted mt-1 text-sm font-medium">
                Basic profile, contact, source, and account information.
            </p>
        </div>

        @if($editRoute)
            <a
                href="{{ $editRoute }}"
                class="inline-flex h-12 w-20 shrink-0 flex-col items-center justify-center rounded-2xl border border-slate-700 bg-slate-800 text-sm font-extrabold text-white transition hover:bg-slate-700"
            >
                <span>✏️</span>
                <span>Edit</span>
            </a>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        @foreach($details as $detail)
            <div class="sf-details-box rounded-2xl border p-4">
                <p class="sf-details-label text-xs font-black uppercase tracking-wide">
                    {{ $detail['label'] }}
                </p>

                <p class="sf-details-value mt-3 break-words text-lg font-black">
                    {{ $detail['value'] }}
                </p>
            </div>
        @endforeach
    </div>

    <div x-show="expanded" x-transition class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2" style="display: none;">
        @foreach($extraDetails as $detail)
            <div class="sf-details-box rounded-2xl border p-4">
                <p class="sf-details-label text-xs font-black uppercase tracking-wide">
                    {{ $detail['label'] }}
                </p>

                <p class="sf-details-value mt-3 break-words text-base font-extrabold">
                    {{ $detail['value'] }}
                </p>
            </div>
        @endforeach
    </div>

    <button
        type="button"
        class="sf-details-view-more mt-5 text-sm font-black"
        @click="expanded = !expanded"
        x-text="expanded ? '− View Less' : '+ View More'"
    ></button>
</section>