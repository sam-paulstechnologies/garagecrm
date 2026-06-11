{{-- resources/views/admin/clients/show-partials/sections/_details_section.blade.php --}}

@php
    $editRoute = \Illuminate\Support\Facades\Route::has('admin.clients.edit')
        ? route('admin.clients.edit', $client->id)
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

    $details = [
        ['label' => 'Name', 'value' => $client->name ?? 'N/A', 'type' => 'text'],
        ['label' => 'Email', 'value' => $client->email ?? 'N/A', 'type' => 'email'],
        ['label' => 'Phone', 'value' => $phoneDisplay ?? 'N/A', 'type' => 'phone'],
        ['label' => 'Location', 'value' => $client->city ?? $client->country ?? 'N/A', 'type' => 'text'],
        ['label' => 'Source', 'value' => $client->source ?? 'N/A', 'type' => 'text'],
    ];

    $extraDetails = [
        ['label' => 'WhatsApp', 'value' => $client->whatsapp ?? 'N/A', 'type' => 'whatsapp'],
        ['label' => 'Preferred Channel', 'value' => $client->preferred_channel ?? 'N/A', 'type' => 'text'],
        ['label' => 'Status', 'value' => $client->status ?? 'N/A', 'type' => 'text'],
        ['label' => 'Country', 'value' => $client->country ?? 'N/A', 'type' => 'text'],
        ['label' => 'City', 'value' => $client->city ?? 'N/A', 'type' => 'text'],
        ['label' => 'Address', 'value' => $client->address ?? 'N/A', 'type' => 'text'],
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

    .sf-details-value-text {
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .sf-details-value-email {
        overflow-wrap: anywhere;
        word-break: break-all;
    }

    .sf-details-contact-link {
        color: #bfdbfe;
        text-decoration: none;
    }

    .sf-details-contact-link:hover {
        color: #93c5fd;
    }

    .sf-details-whatsapp-pill {
        border-color: rgba(74, 222, 128, 0.24);
        background: rgba(34, 197, 94, 0.12);
        color: #bbf7d0;
    }

    .sf-details-whatsapp-pill:hover {
        background: rgba(34, 197, 94, 0.20);
        color: #dcfce7;
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

    html[data-theme="light"] .sf-details-contact-link {
        color: #1d4ed8 !important;
    }

    html[data-theme="light"] .sf-details-contact-link:hover {
        color: #1e40af !important;
    }

    html[data-theme="light"] .sf-details-whatsapp-pill {
        border-color: #86efac !important;
        background: #ecfdf5 !important;
        color: #047857 !important;
    }

    html[data-theme="light"] .sf-details-whatsapp-pill:hover {
        background: #d1fae5 !important;
        color: #065f46 !important;
    }

    html[data-theme="light"] .sf-details-view-more {
        color: #ea580c !important;
    }

    html[data-theme="light"] .sf-details-view-more:hover {
        color: #c2410c !important;
    }
</style>

<section id="details" class="sf-details-shell rounded-2xl border p-4 shadow-sm" x-data="{ expanded: false }">
    <div class="mb-4 flex items-start justify-between gap-4">
        <div>
            <h2 class="sf-details-title text-lg font-extrabold tracking-tight">
                Client Details
            </h2>

            <p class="sf-details-muted mt-1 text-xs font-semibold sm:text-sm">
                Basic profile, contact, source, and account information.
            </p>
        </div>

        @if($editRoute)
            <a
                href="{{ $editRoute }}"
                class="inline-flex h-8 shrink-0 items-center justify-center gap-1 whitespace-nowrap rounded-lg border border-slate-700 bg-slate-800 px-3 text-xs font-extrabold text-white transition hover:bg-slate-700"
            >
                <span>✏️</span>
                <span>Edit</span>
            </a>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        @foreach($details as $detail)
            <div class="sf-details-box min-w-0 rounded-xl border p-3 {{ $loop->last && count($details) % 2 === 1 ? 'sm:col-span-2' : '' }}">
                <p class="sf-details-label text-xs font-black uppercase tracking-wide">
                    {{ $detail['label'] }}
                </p>

                <div class="sf-details-value mt-2 text-base font-extrabold leading-snug">
                    @if(($detail['type'] ?? 'text') === 'phone' && $phoneHref)
                        <a href="{{ $phoneHref }}" class="sf-details-contact-link sf-details-value-text break-all">
                            {{ $detail['value'] }}
                        </a>
                    @elseif(($detail['type'] ?? 'text') === 'email')
                        <span class="sf-details-value-email">
                            {{ $detail['value'] }}
                        </span>
                    @else
                        <span class="sf-details-value-text">
                            {{ $detail['value'] }}
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div x-show="expanded" x-transition class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2" style="display: none;">
        @foreach($extraDetails as $detail)
            <div class="sf-details-box min-w-0 rounded-xl border p-3 {{ $loop->last && count($extraDetails) % 2 === 1 ? 'sm:col-span-2' : '' }}">
                <p class="sf-details-label text-xs font-black uppercase tracking-wide">
                    {{ $detail['label'] }}
                </p>

                <div class="sf-details-value mt-2 text-sm font-extrabold leading-snug">
                    @if(($detail['type'] ?? 'text') === 'whatsapp' && $whatsappUrl)
                        <div class="flex flex-col gap-2">
                            <a href="{{ $whatsappUrl }}" class="sf-details-contact-link break-all" title="Open WhatsApp conversation in Inbox">
                                {{ $detail['value'] }}
                            </a>
                            <a href="{{ $whatsappUrl }}" class="sf-details-whatsapp-pill inline-flex w-fit items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-black" title="Open WhatsApp conversation in Inbox">
                                <span>WA</span>
                                <span>{{ $whatsappIsVerified ? 'Verified' : 'Available' }}</span>
                            </a>
                        </div>
                    @else
                        <span class="sf-details-value-text">
                            {{ $detail['value'] }}
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <button
        type="button"
        class="sf-details-view-more mt-4 text-sm font-black"
        @click="expanded = !expanded"
        x-text="expanded ? '− View Less' : '+ View More'"
    ></button>
</section>
