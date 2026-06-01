{{-- resources/views/admin/clients/show-partials/sections/_service_history_section.blade.php --}}

@php
    $serviceHistoryItems = collect($serviceHistory ?? $client->jobs ?? []);

    $lastCompletedJob = $serviceHistoryItems->first();

    $lastServiceDate = $kpis['last_service'] ?? $lastCompletedJob?->end_time ?? $lastCompletedJob?->completed_at ?? null;
    $nextServiceDate = $kpis['next_service'] ?? null;
    $nextServiceStatus = $kpis['next_service_status'] ?? 'not_available';

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

    $lastServiceDisplay = $formatDate($lastServiceDate);
    $nextServiceDisplay = $formatDate($nextServiceDate);

    $statusLabel = match ($nextServiceStatus) {
        'overdue' => 'Overdue',
        'due_soon' => 'Due soon',
        'scheduled' => 'Scheduled',
        default => 'No service history',
    };
@endphp

<style>
    .sf-service-shell {
        border-color: rgba(30, 41, 59, 1);
        background: rgba(15, 23, 42, 0.70);
        color: #ffffff;
    }

    .sf-service-title {
        color: #ffffff;
    }

    .sf-service-muted {
        color: #cbd5e1;
    }

    .sf-service-card {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(2, 6, 23, 0.38);
    }

    .sf-service-card-blue {
        border-color: rgba(96, 165, 250, 0.24);
        background: rgba(59, 130, 246, 0.10);
    }

    .sf-service-card-orange {
        border-color: rgba(251, 146, 60, 0.24);
        background: rgba(249, 115, 22, 0.10);
    }

    .sf-service-card-slate {
        border-color: rgba(148, 163, 184, 0.18);
        background: rgba(2, 6, 23, 0.34);
    }

    .sf-service-label {
        color: #cbd5e1;
    }

    .sf-service-value {
        color: #ffffff;
    }

    .sf-service-blue-label {
        color: #93c5fd;
    }

    .sf-service-orange-label {
        color: #fdba74;
    }

    .sf-service-empty {
        border-color: rgba(148, 163, 184, 0.16);
        background: rgba(2, 6, 23, 0.35);
        color: #94a3b8;
    }

    .sf-service-badge {
        border-color: rgba(148, 163, 184, 0.20);
        background: rgba(148, 163, 184, 0.10);
        color: #cbd5e1;
    }

    html[data-theme="light"] .sf-service-shell {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-service-title {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-service-muted {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-service-card {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-service-card-blue {
        border-color: #bfdbfe !important;
        background: #eff6ff !important;
    }

    html[data-theme="light"] .sf-service-card-orange {
        border-color: #fed7aa !important;
        background: #fff7ed !important;
    }

    html[data-theme="light"] .sf-service-card-slate {
        border-color: #d9e1ec !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-service-label {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-service-value {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-service-blue-label {
        color: #2563eb !important;
    }

    html[data-theme="light"] .sf-service-orange-label {
        color: #ea580c !important;
    }

    html[data-theme="light"] .sf-service-empty {
        border-color: #d9e1ec !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-service-badge {
        border-color: #cbd5e1 !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
    }
</style>

<section id="service-history" class="sf-service-shell rounded-2xl border p-5 shadow-sm">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="sf-service-title text-lg font-extrabold tracking-tight">
                Service History
            </h2>

            <p class="sf-service-muted mt-1 text-sm font-medium">
                Completed jobs are used to calculate next service reminders.
            </p>
        </div>

        <span class="sf-service-badge inline-flex w-fit rounded-full border px-4 py-1.5 text-sm font-black">
            {{ $serviceHistoryItems->count() > 0 ? $serviceHistoryItems->count() . ' service(s)' : 'No service history' }}
        </span>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="sf-service-card-blue rounded-2xl border p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="sf-service-blue-label text-xs font-black uppercase tracking-wide">
                        Last Service
                    </p>

                    <p class="sf-service-value mt-4 text-2xl font-black">
                        {{ $lastServiceDisplay }}
                    </p>

                    <p class="sf-service-muted mt-3 text-sm font-medium">
                        Most recent completed job
                    </p>
                </div>

                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-blue-400/20 bg-blue-500/10 text-xl">
                    🛠️
                </div>
            </div>
        </div>

        <div class="sf-service-card-orange rounded-2xl border p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="sf-service-orange-label text-xs font-black uppercase tracking-wide">
                        Next Service
                    </p>

                    <p class="sf-service-value mt-4 text-2xl font-black">
                        {{ $nextServiceDisplay }}
                    </p>

                    <p class="sf-service-muted mt-3 text-sm font-medium">
                        Estimated reminder date
                    </p>
                </div>

                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-orange-400/20 bg-orange-500/10 text-xl">
                    🗓️
                </div>
            </div>
        </div>

        <div class="sf-service-card-slate rounded-2xl border p-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="sf-service-label text-xs font-black uppercase tracking-wide">
                        Service Reminder Status
                    </p>

                    <p class="sf-service-value mt-4 text-lg font-black">
                        {{ $statusLabel }}
                    </p>

                    <p class="sf-service-muted mt-3 text-sm font-medium">
                        Based on completed job history.
                    </p>
                </div>

                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-slate-500/20 bg-slate-500/10 text-xl">
                    🔧
                </div>
            </div>
        </div>
    </div>

    <div class="mt-5">
        @if($serviceHistoryItems->isNotEmpty())
            <div class="space-y-3">
                @foreach($serviceHistoryItems->take(5) as $job)
                    <div class="sf-service-card rounded-2xl border p-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="sf-service-value text-sm font-black">
                                    {{ $job->title ?? $job->job_number ?? $job->description ?? 'Completed Job' }}
                                </p>

                                <p class="sf-service-muted mt-1 text-xs font-medium">
                                    {{ $formatDate($job->end_time ?? $job->completed_at ?? $job->created_at ?? null) }}
                                </p>
                            </div>

                            <span class="inline-flex w-fit rounded-full border border-emerald-400/20 bg-emerald-500/10 px-3 py-1 text-xs font-black text-emerald-300">
                                {{ $job->status ?? 'completed' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="sf-service-empty rounded-2xl border border-dashed p-8 text-center text-sm font-semibold">
                No completed services found for this client.
            </div>
        @endif
    </div>
</section>