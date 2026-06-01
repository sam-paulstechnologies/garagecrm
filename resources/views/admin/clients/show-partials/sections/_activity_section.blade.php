{{-- resources/views/admin/clients/show-partials/sections/_activity_section.blade.php --}}

@php
    $activities = collect();

    foreach (collect($client->notes ?? []) as $note) {
        $activities->push([
            'type' => 'Note',
            'title' => 'Note added',
            'description' => $note->content ?? $note->note ?? null,
            'date' => $note->created_at ?? null,
            'tone' => 'blue',
        ]);
    }

    foreach (collect($client->files ?? $client->documents ?? []) as $document) {
        $activities->push([
            'type' => 'Document',
            'title' => 'Document uploaded',
            'description' => $document->file_name ?? $document->name ?? 'Client document',
            'date' => $document->created_at ?? null,
            'tone' => 'orange',
        ]);
    }

    foreach (collect($client->leads ?? []) as $lead) {
        $activities->push([
            'type' => 'Lead',
            'title' => 'Lead created',
            'description' => $lead->name ?? $lead->title ?? 'Lead record',
            'date' => $lead->created_at ?? null,
            'tone' => 'green',
        ]);
    }

    foreach (collect($client->opportunities ?? []) as $opportunity) {
        $activities->push([
            'type' => 'Opportunity',
            'title' => 'Opportunity created',
            'description' => $opportunity->title ?? $opportunity->name ?? 'Opportunity record',
            'date' => $opportunity->created_at ?? null,
            'tone' => 'purple',
        ]);
    }

    $activities = $activities
        ->sortByDesc(fn ($item) => $item['date'])
        ->values();

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
    .sf-activity-shell {
        border-color: rgba(30, 41, 59, 1);
        background: rgba(15, 23, 42, 0.70);
        color: #ffffff;
    }

    .sf-activity-title {
        color: #ffffff;
    }

    .sf-activity-muted {
        color: #cbd5e1;
    }

    .sf-activity-count {
        border-color: rgba(148, 163, 184, 0.20);
        background: rgba(148, 163, 184, 0.10);
        color: #cbd5e1;
    }

    .sf-activity-card {
        border-color: rgba(255, 255, 255, 0.10);
        background: rgba(2, 6, 23, 0.38);
    }

    .sf-activity-value {
        color: #ffffff;
    }

    .sf-activity-empty {
        border-color: rgba(148, 163, 184, 0.16);
        background: rgba(2, 6, 23, 0.35);
        color: #94a3b8;
    }

    .sf-activity-dot-blue {
        background: #3b82f6;
    }

    .sf-activity-dot-orange {
        background: #f97316;
    }

    .sf-activity-dot-green {
        background: #10b981;
    }

    .sf-activity-dot-purple {
        background: #8b5cf6;
    }

    html[data-theme="light"] .sf-activity-shell {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-activity-title,
    html[data-theme="light"] .sf-activity-value {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-activity-muted {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-activity-count {
        border-color: #cbd5e1 !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-activity-card {
        border-color: #d9e1ec !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-activity-empty {
        border-color: #d9e1ec !important;
        background: #f1f5f9 !important;
        color: #475569 !important;
    }
</style>

<section id="activity" class="sf-activity-shell rounded-2xl border p-5 shadow-sm">
    <div class="mb-5 flex items-start justify-between gap-4">
        <div>
            <h2 class="sf-activity-title text-lg font-extrabold tracking-tight">
                Recent Activity
            </h2>

            <p class="sf-activity-muted mt-1 text-sm font-medium leading-6">
                Latest client-related updates from notes, documents, leads, and opportunities.
            </p>
        </div>

        <span class="sf-activity-count inline-flex shrink-0 rounded-full border px-4 py-2 text-center text-sm font-black">
            {{ $activities->count() }} item(s)
        </span>
    </div>

    @if($activities->isNotEmpty())
        <div class="space-y-3">
            @foreach($activities->take(6) as $activity)
                <div class="sf-activity-card rounded-2xl border p-4">
                    <div class="flex gap-3">
                        <span class="sf-activity-dot-{{ $activity['tone'] }} mt-1 h-3 w-3 shrink-0 rounded-full"></span>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="sf-activity-value text-sm font-black">
                                    {{ $activity['title'] }}
                                </p>

                                <span class="inline-flex rounded-full border border-slate-500/20 bg-slate-500/10 px-2 py-0.5 text-[11px] font-black text-slate-300">
                                    {{ $activity['type'] }}
                                </span>
                            </div>

                            @if(!empty($activity['description']))
                                <p class="sf-activity-muted mt-1 text-xs font-medium leading-5">
                                    {{ \Illuminate\Support\Str::limit($activity['description'], 120) }}
                                </p>
                            @endif

                            <p class="sf-activity-muted mt-2 text-[11px] font-semibold">
                                {{ $formatDate($activity['date']) }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="sf-activity-empty rounded-2xl border border-dashed p-8 text-center text-sm font-semibold">
            No recent activity.
        </div>
    @endif
</section>