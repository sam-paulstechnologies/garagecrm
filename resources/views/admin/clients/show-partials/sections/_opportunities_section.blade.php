{{-- resources/views/admin/clients/show-partials/sections/_opportunities_section.blade.php --}}

@php
    $opportunities = collect($client->opportunities ?? []);

    $opportunityIndexRoute = \Illuminate\Support\Facades\Route::has('admin.opportunities.index')
        ? route('admin.opportunities.index', ['client_id' => $client->id])
        : null;

    $opportunityCreateRoute = \Illuminate\Support\Facades\Route::has('admin.opportunities.create')
        ? route('admin.opportunities.create', ['client_id' => $client->id])
        : null;

    $opportunityShowRoute = function ($opportunity) {
        return \Illuminate\Support\Facades\Route::has('admin.opportunities.show')
            ? route('admin.opportunities.show', $opportunity->id)
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
    .sf-opps-shell {
        border-color: rgba(30, 41, 59, 1);
        background: rgba(15, 23, 42, 0.70);
        color: #ffffff;
    }

    .sf-opps-title {
        color: #ffffff;
    }

    .sf-opps-muted {
        color: #cbd5e1;
    }

    .sf-opps-card {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(2, 6, 23, 0.38);
    }

    .sf-opps-value {
        color: #ffffff;
    }

    .sf-opps-empty {
        border-color: rgba(148, 163, 184, 0.16);
        background: rgba(2, 6, 23, 0.35);
        color: #94a3b8;
    }

    .sf-opps-secondary-btn {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(255, 255, 255, 0.08);
        color: #ffffff;
    }

    html[data-theme="light"] .sf-opps-shell {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-opps-title,
    html[data-theme="light"] .sf-opps-value {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-opps-muted {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-opps-card {
        border-color: #d9e1ec !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-opps-empty {
        border-color: #d9e1ec !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-opps-secondary-btn {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }
</style>

<section id="opportunities" class="sf-opps-shell rounded-2xl border p-5 shadow-sm">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="sf-opps-title text-lg font-extrabold tracking-tight">
                Opportunities
            </h2>

            <p class="sf-opps-muted mt-1 text-sm font-medium">
                Sales opportunities and possible bookings linked to this client.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if($opportunityIndexRoute)
                <a
                    href="{{ $opportunityIndexRoute }}"
                    class="sf-opps-secondary-btn inline-flex h-10 items-center justify-center rounded-xl border px-4 text-sm font-bold transition"
                >
                    View All
                </a>
            @endif

            @if($opportunityCreateRoute)
                <a
                    href="{{ $opportunityCreateRoute }}"
                    class="inline-flex h-10 items-center justify-center rounded-xl bg-orange-500 px-5 text-sm font-extrabold text-white shadow-lg shadow-orange-950/20 transition hover:bg-orange-600"
                >
                    + Add Opportunity
                </a>
            @endif
        </div>
    </div>

    @if($opportunities->isNotEmpty())
        <div class="space-y-3">
            @foreach($opportunities->take(5) as $opportunity)
                @php
                    $showRoute = $opportunityShowRoute($opportunity);
                @endphp

                <div class="sf-opps-card rounded-2xl border p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="sf-opps-value text-sm font-black">
                                {{ $opportunity->title ?? $opportunity->name ?? 'Opportunity #' . $opportunity->id }}
                            </p>

                            <p class="sf-opps-muted mt-1 text-xs font-medium">
                                {{ $opportunity->service_type ?? $opportunity->looking_for ?? 'No service type' }}
                                · {{ $formatDate($opportunity->created_at ?? null) }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="inline-flex rounded-full border border-orange-400/20 bg-orange-500/10 px-3 py-1 text-xs font-black text-orange-700 dark:text-orange-200">
                                {{ $opportunity->stage ?? 'new' }}
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
        <div class="sf-opps-empty rounded-2xl border border-dashed p-8 text-center text-sm font-semibold">
            No opportunities yet.
        </div>
    @endif
</section>
