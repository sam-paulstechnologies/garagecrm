@extends('layouts.app')

@section('title', 'Lead Details')

@section('content')
@php
    $payload = is_array($lead->external_payload) ? $lead->external_payload : [];
    $webhook = $payload['_webhook'] ?? [];

    $sourceLabel = $lead->leadSource?->name
        ?? $lead->source
        ?? 'Manual';

    $leadSourceType = $lead->leadSource?->type;
    $leadSourceStatus = $lead->leadSource?->status;

    $pageName = $lead->leadSource?->configValue('page_name')
        ?? data_get($webhook, 'page_name');

    $pageId = $lead->leadSource?->configValue('page_id')
        ?? data_get($webhook, 'page_id');

    $formName = $lead->leadSource?->configValue('form_name')
        ?? data_get($webhook, 'form_name');

    $formId = $lead->external_form_id
        ?? $lead->leadSource?->configValue('form_id')
        ?? data_get($webhook, 'form_id');

    $leadgenId = $lead->external_id
        ?? data_get($webhook, 'leadgen_id');

    $statusBadgeClass = match (strtolower((string) $lead->status)) {
        'new' => 'bg-blue-500/10 text-blue-300 ring-blue-400/20',
        'attempting_contact' => 'bg-yellow-500/10 text-yellow-300 ring-yellow-400/20',
        'contact_on_hold' => 'bg-orange-500/10 text-orange-300 ring-orange-400/20',
        'qualified' => 'bg-green-500/10 text-green-300 ring-green-400/20',
        'converted' => 'bg-emerald-500/10 text-emerald-300 ring-emerald-400/20',
        'disqualified', 'lost' => 'bg-red-500/10 text-red-300 ring-red-400/20',
        default => 'bg-white/5 text-slate-300 ring-white/10',
    };

    $score = (int) ($leadScore ?? $lead->score ?? 0);

    $scoreBadgeClass = $score >= 75
        ? 'bg-green-500/10 text-green-300 ring-green-400/20'
        : ($score >= 45 ? 'bg-yellow-500/10 text-yellow-300 ring-yellow-400/20' : 'bg-white/5 text-slate-300 ring-white/10');

    $badgeBase = 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold ring-1';

    $field = function ($label, $value, $mono = false) {
        $valueClass = $mono
            ? 'font-mono text-xs text-slate-200 break-all'
            : 'font-bold text-white';

        return [
            'label' => $label,
            'value' => $value,
            'valueClass' => $valueClass,
        ];
    };
@endphp

<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="sf-kicker">
                    Lead Profile
                </div>

                @if((bool) ($lead->is_hot ?? false))
                    <span class="{{ $badgeBase }} bg-red-500/10 text-red-300 ring-red-400/20">
                        🔥 Hot Lead
                    </span>
                @endif

                <span class="{{ $badgeBase }} {{ $scoreBadgeClass }}">
                    Score: {{ $score }}/100
                </span>
            </div>

            <h1 class="sf-page-title mt-3">
                Lead Details
            </h1>

            <p class="sf-page-subtitle">
                View lead profile, source attribution, communications, and message history.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.leads.index') }}" class="sf-btn-secondary">
                ← Back
            </a>

            <a href="{{ route('admin.leads.edit', $lead) }}" class="sf-btn-primary">
                ✏️ Edit Lead
            </a>

            <form method="POST" action="{{ route('admin.leads.toggleHot', $lead) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="sf-btn-danger">
                    {{ $lead->is_hot ? '🔥 Unmark Hot' : '⭐ Mark Hot' }}
                </button>
            </form>
        </div>
    </div>

    {{-- Quick Summary --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="sf-stat-card">
            <div class="sf-stat-label">Status</div>
            <div class="mt-3">
                <span class="{{ $badgeBase }} {{ $statusBadgeClass }}">
                    {{ $lead->status_label ?? ucfirst(str_replace('_', ' ', (string) $lead->status)) }}
                </span>
            </div>
        </div>

        <div class="sf-stat-card">
            <div class="sf-stat-label">Lead Score</div>
            <div class="mt-2 text-3xl font-extrabold text-white">{{ $score }}/100</div>
        </div>

        <div class="sf-stat-card">
            <div class="sf-stat-label">Source</div>
            <div class="mt-2 text-lg font-extrabold text-white">{{ $sourceLabel }}</div>
        </div>

        <div class="sf-stat-card">
            <div class="sf-stat-label">Hot Lead</div>
            <div class="mt-2 text-lg font-extrabold {{ $lead->is_hot ? 'text-red-300' : 'text-white' }}">
                {{ $lead->is_hot ? '🔥 Yes' : 'No' }}
            </div>
        </div>
    </div>

    {{-- Basic Details --}}
    <div class="sf-card">
        <div class="sf-card-header">
            <h2 class="sf-section-title">Basic Details</h2>
        </div>

        <div class="sf-card-body grid grid-cols-1 gap-5 text-sm md:grid-cols-2">
            <div>
                <div class="text-slate-500">Name</div>
                <div class="font-bold text-white">{{ $lead->name ?? '—' }}</div>
            </div>

            <div>
                <div class="text-slate-500">Status</div>
                <div class="flex flex-wrap gap-2">
                    <span class="{{ $badgeBase }} {{ $statusBadgeClass }}">
                        {{ $lead->status_label ?? ucfirst(str_replace('_', ' ', (string) $lead->status)) }}
                    </span>

                    @if($lead->status === 'converted')
                        <span class="{{ $badgeBase }} bg-green-500/10 text-green-300 ring-green-400/20">
                            Converted
                        </span>
                    @elseif($lead->status === 'attempting_contact')
                        <span class="{{ $badgeBase }} bg-yellow-500/10 text-yellow-300 ring-yellow-400/20">
                            In Progress
                        </span>
                    @endif
                </div>
            </div>

            <div>
                <div class="text-slate-500">Email</div>
                <div class="font-bold text-white">{{ $lead->email ?? '—' }}</div>
            </div>

            <div>
                <div class="text-slate-500">Phone</div>
                <div class="font-bold text-white">{{ $lead->phone ?? $lead->phone_norm ?? '—' }}</div>
            </div>

            <div>
                <div class="text-slate-500">Assigned To</div>
                <div class="font-bold text-white">{{ $lead->assignee?->name ?? 'Unassigned' }}</div>
            </div>

            <div>
                <div class="text-slate-500">Preferred Channel</div>
                <div class="font-bold text-white">{{ $lead->preferred_channel ?? '—' }}</div>
            </div>

            <div>
                <div class="text-slate-500">Vehicle</div>
                <div class="font-bold text-white">{{ $lead->vehicle_label ?? '—' }}</div>
            </div>

            <div>
                <div class="text-slate-500">Lead Score</div>
                <span class="{{ $badgeBase }} {{ $scoreBadgeClass }}">
                    {{ $score }}/100
                </span>
            </div>

            <div>
                <div class="text-slate-500">Hot Lead</div>
                <div class="font-bold {{ $lead->is_hot ? 'text-red-300' : 'text-white' }}">
                    {{ $lead->is_hot ? '🔥 Yes' : 'No' }}
                </div>
            </div>

            <div>
                <div class="text-slate-500">Last Contacted</div>
                <div class="font-bold text-white">
                    {{ $lead->last_contacted_at?->format('d M Y, h:i A') ?? '—' }}
                </div>
            </div>

            <div class="md:col-span-2">
                <div class="text-slate-500">Lead Score Reason</div>
                <div class="font-bold text-white">
                    {{ $lead->lead_score_reason ?? 'Calculated from phone, source, vehicle, hot flag, status, and WhatsApp activity.' }}
                </div>
            </div>

            <div class="md:col-span-2">
                <div class="text-slate-500">Notes</div>
                <div class="whitespace-pre-wrap font-bold text-white">{{ $lead->notes ?? '—' }}</div>
            </div>

            <div>
                <div class="text-slate-500">Created</div>
                <div class="font-bold text-white">
                    {{ $lead->created_at?->format('d M Y, h:i A') ?? '—' }}
                </div>
            </div>

            <div>
                <div class="text-slate-500">Updated</div>
                <div class="font-bold text-white">
                    {{ $lead->updated_at?->format('d M Y, h:i A') ?? '—' }}
                </div>
            </div>
        </div>
    </div>

    {{-- Source & Attribution --}}
    <div class="sf-card">
        <div class="sf-card-header">
            <h2 class="sf-section-title">Source & Attribution</h2>
            <p class="sf-section-subtitle">
                Useful for verifying Meta Lead Ads, website forms, WhatsApp, and campaign attribution.
            </p>
        </div>

        <div class="sf-card-body grid grid-cols-1 gap-5 text-sm md:grid-cols-2">
            <div>
                <div class="text-slate-500">Displayed Source</div>
                <div class="font-bold text-white">{{ $sourceLabel }}</div>
            </div>

            <div>
                <div class="text-slate-500">Lead Source Record</div>
                <div class="font-bold text-white">
                    @if($lead->leadSource)
                        #{{ $lead->leadSource->id }} — {{ $lead->leadSource->name }}
                    @else
                        —
                    @endif
                </div>
            </div>

            <div>
                <div class="text-slate-500">Lead Source Type</div>
                <div class="font-bold text-white">{{ $leadSourceType ? ucfirst($leadSourceType) : '—' }}</div>
            </div>

            <div>
                <div class="text-slate-500">Lead Source Status</div>
                <div class="font-bold text-white">{{ $leadSourceStatus ? ucfirst($leadSourceStatus) : '—' }}</div>
            </div>

            <div>
                <div class="text-slate-500">External Source</div>
                <div class="font-bold text-white">{{ $lead->external_source ?? '—' }}</div>
            </div>

            <div>
                <div class="text-slate-500">External Lead ID</div>
                <div class="break-all font-mono text-xs text-slate-200">{{ $leadgenId ?? '—' }}</div>
            </div>

            <div>
                <div class="text-slate-500">External Form ID</div>
                <div class="break-all font-mono text-xs text-slate-200">{{ $formId ?? '—' }}</div>
            </div>

            <div>
                <div class="text-slate-500">Received At</div>
                <div class="font-bold text-white">
                    {{ $lead->external_received_at?->format('d M Y, h:i A') ?? '—' }}
                </div>
            </div>

            <div>
                <div class="text-slate-500">Meta Page</div>
                <div class="font-bold text-white">{{ $pageName ?? '—' }}</div>
                @if($pageId)
                    <div class="mt-1 font-mono text-xs text-slate-500">{{ $pageId }}</div>
                @endif
            </div>

            <div>
                <div class="text-slate-500">Meta Form</div>
                <div class="font-bold text-white">{{ $formName ?? '—' }}</div>
                @if($formId)
                    <div class="mt-1 font-mono text-xs text-slate-500">{{ $formId }}</div>
                @endif
            </div>

            @if(!empty($webhook))
                <div class="md:col-span-2">
                    <details class="rounded-2xl border border-white/10 bg-slate-950/60">
                        <summary class="cursor-pointer px-4 py-3 font-bold text-slate-200">
                            View Webhook Metadata
                        </summary>
                        <pre class="overflow-x-auto whitespace-pre-wrap p-4 text-xs text-slate-300">{{ json_encode($webhook, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                </div>
            @endif
        </div>
    </div>

    {{-- Communications --}}
    <div class="sf-card">
        <div class="sf-card-header">
            <h2 class="sf-section-title">Communications</h2>
        </div>

        <div class="sf-card-body">
            @if($communications->count())
                <div class="sf-table-scroll">
                    <table class="sf-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Content</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($communications as $c)
                                <tr>
                                    <td>
                                        {{ $c->communication_date ? \Carbon\Carbon::parse($c->communication_date)->format('d M Y, h:i A') : '—' }}
                                    </td>
                                    <td>{{ $c->communication_type ?? '—' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($c->content ?? '', 120) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 text-slate-300">
                    {{ $communications->links() }}
                </div>
            @else
                <div class="sf-empty">No communications yet.</div>
            @endif
        </div>
    </div>

    {{-- Message Logs --}}
    <div class="sf-card">
        <div class="sf-card-header">
            <h2 class="sf-section-title">Message Logs</h2>
        </div>

        <div class="sf-card-body">
            @if($messageLogs->count())
                <div class="sf-table-scroll">
                    <table class="sf-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Channel</th>
                                <th>Direction</th>
                                <th>Status</th>
                                <th>AI</th>
                                <th>Message</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($messageLogs as $log)
                                <tr>
                                    <td>
                                        {{ $log->created_at?->format('d M Y, h:i A') ?? '—' }}
                                    </td>
                                    <td>{{ $log->channel ?? '—' }}</td>
                                    <td>{{ $log->direction ?? '—' }}</td>
                                    <td>{{ $log->provider_status ?? '—' }}</td>
                                    <td>
                                        @if((bool) ($log->is_ai ?? false))
                                            <span class="{{ $badgeBase }} bg-blue-500/10 text-blue-300 ring-blue-400/20">
                                                AI
                                            </span>
                                        @else
                                            <span class="text-slate-600">—</span>
                                        @endif
                                    </td>
                                    <td>{{ \Illuminate\Support\Str::limit($log->message ?? $log->body ?? '', 120) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 text-slate-300">
                    {{ $messageLogs->links() }}
                </div>
            @else
                <div class="sf-empty">No message logs yet.</div>
            @endif
        </div>
    </div>

</div>
@endsection