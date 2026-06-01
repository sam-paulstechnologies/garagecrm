{{-- resources/views/admin/clients/show-partials/sections/_communications_section.blade.php --}}

@php
    $communications = collect($client->communicationLogs ?? $client->communications ?? []);

    $communicationCreateRoute = \Illuminate\Support\Facades\Route::has('admin.communication-logs.create')
        ? route('admin.communication-logs.create', ['client_id' => $client->id])
        : null;

    $communicationIndexRoute = \Illuminate\Support\Facades\Route::has('admin.communication-logs.index')
        ? route('admin.communication-logs.index', ['client_id' => $client->id])
        : null;

    $formatDate = function ($value) {
        if (!$value) {
            return '—';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d M Y, h:i A');
        } catch (\Throwable $e) {
            return $value;
        }
    };
@endphp

<style>
    .sf-comms-shell {
        border-color: rgba(30, 41, 59, 1);
        background: rgba(15, 23, 42, 0.70);
        color: #ffffff;
    }

    .sf-comms-title {
        color: #ffffff;
    }

    .sf-comms-muted {
        color: #cbd5e1;
    }

    .sf-comms-card {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(2, 6, 23, 0.38);
    }

    .sf-comms-value {
        color: #ffffff;
    }

    .sf-comms-empty {
        border-color: rgba(148, 163, 184, 0.16);
        background: rgba(2, 6, 23, 0.35);
        color: #94a3b8;
    }

    .sf-comms-secondary-btn {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(255, 255, 255, 0.08);
        color: #ffffff;
    }

    .sf-comms-secondary-btn:hover {
        background: rgba(255, 255, 255, 0.14);
    }

    html[data-theme="light"] .sf-comms-shell {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-comms-title,
    html[data-theme="light"] .sf-comms-value {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-comms-muted {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-comms-card {
        border-color: #d9e1ec !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-comms-empty {
        border-color: #d9e1ec !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-comms-secondary-btn {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-comms-secondary-btn:hover {
        background: #f8fafc !important;
    }
</style>

<section id="communications" class="sf-comms-shell rounded-2xl border p-5 shadow-sm">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="sf-comms-title text-lg font-extrabold tracking-tight">
                Communications
            </h2>

            <p class="sf-comms-muted mt-1 text-sm font-medium">
                Recent calls, emails, WhatsApp updates, and follow-up notes for this client.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if($communicationCreateRoute)
                <a
                    href="{{ $communicationCreateRoute }}"
                    class="inline-flex h-10 items-center justify-center rounded-xl bg-orange-500 px-5 text-sm font-extrabold text-white shadow-lg shadow-orange-950/20 transition hover:bg-orange-600"
                >
                    Add Communication
                </a>
            @endif

            @if($communicationIndexRoute)
                <a
                    href="{{ $communicationIndexRoute }}"
                    class="sf-comms-secondary-btn inline-flex h-10 items-center justify-center rounded-xl border px-4 text-sm font-bold transition"
                >
                    Open Log
                </a>
            @endif
        </div>
    </div>

    @if($communications->isNotEmpty())
        <div class="space-y-3">
            @foreach($communications->take(5) as $communication)
                <div class="sf-comms-card rounded-2xl border p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="sf-comms-value text-sm font-black">
                                {{ $communication->communication_type ?? $communication->type ?? 'Communication' }}
                            </p>

                            <p class="sf-comms-muted mt-1 text-xs font-medium">
                                {{ $formatDate($communication->communication_date ?? $communication->created_at ?? null) }}
                            </p>

                            @if(!empty($communication->content))
                                <p class="sf-comms-muted mt-3 text-sm font-medium leading-6">
                                    {{ \Illuminate\Support\Str::limit($communication->content, 160) }}
                                </p>
                            @endif
                        </div>

                        @if(!empty($communication->follow_up_required))
                            <span class="inline-flex w-fit rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-black text-orange-300">
                                Follow-up
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="sf-comms-empty rounded-2xl border border-dashed p-8 text-center text-sm font-semibold">
            No communications yet.
        </div>
    @endif
</section>