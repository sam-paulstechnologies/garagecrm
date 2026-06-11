{{-- resources/views/admin/clients/show-partials/sections/_leads_section.blade.php --}}

@php
    $leads = collect($client->leads ?? []);

    $leadIndexRoute = \Illuminate\Support\Facades\Route::has('admin.leads.index')
        ? route('admin.leads.index', ['client_id' => $client->id])
        : null;

    $leadCreateRoute = \Illuminate\Support\Facades\Route::has('admin.leads.create')
        ? route('admin.leads.create', ['client_id' => $client->id])
        : null;

    $leadShowRoute = function ($lead) {
        return \Illuminate\Support\Facades\Route::has('admin.leads.show')
            ? route('admin.leads.show', $lead->id)
            : null;
    };

    $formatDate = function ($value) {
        if (!$value) {
            return '—';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d M Y');
        } catch (\Throwable $e) {
            return $value;
        }
    };
@endphp

<style>
    .sf-leads-shell {
        border-color: rgba(30, 41, 59, 1);
        background: rgba(15, 23, 42, 0.70);
        color: #ffffff;
    }

    .sf-leads-title {
        color: #ffffff;
    }

    .sf-leads-muted {
        color: #cbd5e1;
    }

    .sf-leads-card {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(2, 6, 23, 0.38);
    }

    .sf-leads-label {
        color: #94a3b8;
    }

    .sf-leads-value {
        color: #ffffff;
    }

    .sf-leads-empty {
        border-color: rgba(148, 163, 184, 0.16);
        background: rgba(2, 6, 23, 0.35);
        color: #94a3b8;
    }

    .sf-leads-secondary-btn {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(255, 255, 255, 0.08);
        color: #ffffff;
    }

    .sf-leads-secondary-btn:hover {
        background: rgba(255, 255, 255, 0.14);
    }

    html[data-theme="light"] .sf-leads-shell {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-leads-title {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-leads-muted {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-leads-card {
        border-color: #d9e1ec !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-leads-label {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-leads-value {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-leads-empty {
        border-color: #d9e1ec !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-leads-secondary-btn {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-leads-secondary-btn:hover {
        background: #f8fafc !important;
    }
</style>

<section id="leads" class="sf-leads-shell rounded-2xl border p-5 shadow-sm">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="sf-leads-title text-lg font-extrabold tracking-tight">
                Leads
            </h2>

            <p class="sf-leads-muted mt-1 text-sm font-medium">
                Latest lead records linked to this client.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if($leadIndexRoute)
                <a
                    href="{{ $leadIndexRoute }}"
                    class="sf-leads-secondary-btn inline-flex h-10 items-center justify-center rounded-xl border px-4 text-sm font-bold transition"
                >
                    View All
                </a>
            @endif

            @if($leadCreateRoute)
                <a
                    href="{{ $leadCreateRoute }}"
                    class="inline-flex h-10 items-center justify-center rounded-xl bg-orange-500 px-5 text-sm font-extrabold text-white shadow-lg shadow-orange-950/20 transition hover:bg-orange-600"
                >
                    + Add Lead
                </a>
            @endif
        </div>
    </div>

    @if($leads->isNotEmpty())
        <div class="space-y-3">
            @foreach($leads->take(5) as $lead)
                @php
                    $showRoute = $leadShowRoute($lead);
                @endphp

                <div class="sf-leads-card rounded-2xl border p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="sf-leads-value text-sm font-black">
                                {{ $lead->name ?? $lead->title ?? 'Lead #' . $lead->id }}
                            </p>

                            <p class="sf-leads-muted mt-1 text-xs font-medium">
                                {{ $lead->source ?? $lead->lead_source ?? 'No source' }}
                                · {{ $formatDate($lead->created_at ?? null) }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="inline-flex rounded-full border border-blue-400/20 bg-blue-500/10 px-3 py-1 text-xs font-black text-blue-700 dark:text-blue-200">
                                {{ $lead->status ?? 'new' }}
                            </span>

                            @if($showRoute)
                                <a href="{{ $showRoute }}" class="text-xs font-black text-orange-700 hover:text-orange-800 dark:text-orange-200 dark:hover:text-orange-100">
                                    View
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="sf-leads-empty rounded-2xl border border-dashed p-8 text-center text-sm font-semibold">
            No leads yet.
        </div>
    @endif
</section>
