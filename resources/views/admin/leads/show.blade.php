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
        'new' => 'bg-blue-100 text-blue-800',
        'attempting_contact' => 'bg-yellow-100 text-yellow-800',
        'contact_on_hold' => 'bg-orange-100 text-orange-800',
        'qualified' => 'bg-green-100 text-green-800',
        'converted' => 'bg-emerald-100 text-emerald-800',
        'disqualified', 'lost' => 'bg-red-100 text-red-800',
        default => 'bg-gray-100 text-gray-800',
    };

    $score = (int) ($leadScore ?? $lead->score ?? 0);

    $scoreBadgeClass = $score >= 75
        ? 'bg-green-100 text-green-800'
        : ($score >= 45 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-700');
@endphp

<div class="max-w-6xl mx-auto px-6 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-bold text-gray-800">Lead Details</h1>

                @if((bool) ($lead->is_hot ?? false))
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                        🔥 Hot Lead
                    </span>
                @endif

                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $scoreBadgeClass }}">
                    Score: {{ $score }}/100
                </span>
            </div>

            <p class="text-sm text-gray-500 mt-1">
                View lead profile, source attribution, communications, and message history.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.leads.index') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded shadow text-sm">
                ← Back
            </a>

            <a href="{{ route('admin.leads.edit', $lead) }}"
               class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded shadow text-sm">
                ✏️ Edit Lead
            </a>

            <form method="POST" action="{{ route('admin.leads.toggleHot', $lead) }}">
                @csrf
                @method('PATCH')
                <button type="submit"
                        class="bg-rose-500 hover:bg-rose-600 text-white px-4 py-2 rounded shadow text-sm">
                    {{ $lead->is_hot ? '🔥 Unmark Hot' : '⭐ Mark Hot' }}
                </button>
            </form>
        </div>
    </div>

    {{-- Quick Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-sm text-gray-500">Status</div>
            <div class="mt-2">
                <span class="inline-flex px-2 py-1 text-xs rounded-full font-medium {{ $statusBadgeClass }}">
                    {{ $lead->status_label ?? ucfirst(str_replace('_', ' ', (string) $lead->status)) }}
                </span>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-sm text-gray-500">Lead Score</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">{{ $score }}/100</div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-sm text-gray-500">Source</div>
            <div class="text-lg font-semibold text-gray-900 mt-1">{{ $sourceLabel }}</div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="text-sm text-gray-500">Hot Lead</div>
            <div class="text-lg font-semibold mt-1 {{ $lead->is_hot ? 'text-red-700' : 'text-gray-900' }}">
                {{ $lead->is_hot ? '🔥 Yes' : 'No' }}
            </div>
        </div>
    </div>

    {{-- Basic Details --}}
    <div class="bg-white shadow-sm border border-gray-100 rounded-xl divide-y divide-gray-200">
        <div class="px-5 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-800">Basic Details</h2>
        </div>

        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5 text-sm text-gray-700">
            <div>
                <div class="text-gray-500">Name</div>
                <div class="font-medium text-gray-900">{{ $lead->name ?? '—' }}</div>
            </div>

            <div>
                <div class="text-gray-500">Status</div>
                <div>
                    <span class="inline-flex px-2 py-1 text-xs rounded-full {{ $statusBadgeClass }}">
                        {{ $lead->status_label ?? ucfirst(str_replace('_', ' ', (string) $lead->status)) }}
                    </span>

                    @if($lead->status === 'converted')
                        <span class="ml-2 px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">
                            Converted
                        </span>
                    @elseif($lead->status === 'attempting_contact')
                        <span class="ml-2 px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">
                            In Progress
                        </span>
                    @endif
                </div>
            </div>

            <div>
                <div class="text-gray-500">Email</div>
                <div class="font-medium text-gray-900">{{ $lead->email ?? '—' }}</div>
            </div>

            <div>
                <div class="text-gray-500">Phone</div>
                <div class="font-medium text-gray-900">{{ $lead->phone ?? $lead->phone_norm ?? '—' }}</div>
            </div>

            <div>
                <div class="text-gray-500">Assigned To</div>
                <div class="font-medium text-gray-900">{{ $lead->assignee?->name ?? 'Unassigned' }}</div>
            </div>

            <div>
                <div class="text-gray-500">Preferred Channel</div>
                <div class="font-medium text-gray-900">{{ $lead->preferred_channel ?? '—' }}</div>
            </div>

            <div>
                <div class="text-gray-500">Vehicle</div>
                <div class="font-medium text-gray-900">{{ $lead->vehicle_label ?? '—' }}</div>
            </div>

            <div>
                <div class="text-gray-500">Lead Score</div>
                <div>
                    <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $scoreBadgeClass }}">
                        {{ $score }}/100
                    </span>
                </div>
            </div>

            <div>
                <div class="text-gray-500">Hot Lead</div>
                <div class="font-medium {{ $lead->is_hot ? 'text-red-700' : 'text-gray-900' }}">
                    {{ $lead->is_hot ? '🔥 Yes' : 'No' }}
                </div>
            </div>

            <div>
                <div class="text-gray-500">Last Contacted</div>
                <div class="font-medium text-gray-900">
                    {{ $lead->last_contacted_at?->format('d M Y, h:i A') ?? '—' }}
                </div>
            </div>

            <div class="md:col-span-2">
                <div class="text-gray-500">Lead Score Reason</div>
                <div class="font-medium text-gray-900">
                    {{ $lead->lead_score_reason ?? 'Calculated from phone, source, vehicle, hot flag, status, and WhatsApp activity.' }}
                </div>
            </div>

            <div class="md:col-span-2">
                <div class="text-gray-500">Notes</div>
                <div class="font-medium text-gray-900 whitespace-pre-wrap">{{ $lead->notes ?? '—' }}</div>
            </div>

            <div>
                <div class="text-gray-500">Created</div>
                <div class="font-medium text-gray-900">
                    {{ $lead->created_at?->format('d M Y, h:i A') ?? '—' }}
                </div>
            </div>

            <div>
                <div class="text-gray-500">Updated</div>
                <div class="font-medium text-gray-900">
                    {{ $lead->updated_at?->format('d M Y, h:i A') ?? '—' }}
                </div>
            </div>
        </div>
    </div>

    {{-- Source & Attribution --}}
    <div class="bg-white shadow-sm border border-gray-100 rounded-xl">
        <div class="px-5 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-800">Source & Attribution</h2>
            <p class="text-sm text-gray-500 mt-1">
                Useful for verifying Meta Lead Ads, website forms, WhatsApp, and campaign attribution.
            </p>
        </div>

        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5 text-sm text-gray-700">
            <div>
                <div class="text-gray-500">Displayed Source</div>
                <div class="font-medium text-gray-900">{{ $sourceLabel }}</div>
            </div>

            <div>
                <div class="text-gray-500">Lead Source Record</div>
                <div class="font-medium text-gray-900">
                    @if($lead->leadSource)
                        #{{ $lead->leadSource->id }} — {{ $lead->leadSource->name }}
                    @else
                        —
                    @endif
                </div>
            </div>

            <div>
                <div class="text-gray-500">Lead Source Type</div>
                <div class="font-medium text-gray-900">{{ $leadSourceType ? ucfirst($leadSourceType) : '—' }}</div>
            </div>

            <div>
                <div class="text-gray-500">Lead Source Status</div>
                <div class="font-medium text-gray-900">{{ $leadSourceStatus ? ucfirst($leadSourceStatus) : '—' }}</div>
            </div>

            <div>
                <div class="text-gray-500">External Source</div>
                <div class="font-medium text-gray-900">{{ $lead->external_source ?? '—' }}</div>
            </div>

            <div>
                <div class="text-gray-500">External Lead ID</div>
                <div class="font-mono text-xs text-gray-900 break-all">{{ $leadgenId ?? '—' }}</div>
            </div>

            <div>
                <div class="text-gray-500">External Form ID</div>
                <div class="font-mono text-xs text-gray-900 break-all">{{ $formId ?? '—' }}</div>
            </div>

            <div>
                <div class="text-gray-500">Received At</div>
                <div class="font-medium text-gray-900">
                    {{ $lead->external_received_at?->format('d M Y, h:i A') ?? '—' }}
                </div>
            </div>

            <div>
                <div class="text-gray-500">Meta Page</div>
                <div class="font-medium text-gray-900">{{ $pageName ?? '—' }}</div>
                @if($pageId)
                    <div class="font-mono text-xs text-gray-400 mt-1">{{ $pageId }}</div>
                @endif
            </div>

            <div>
                <div class="text-gray-500">Meta Form</div>
                <div class="font-medium text-gray-900">{{ $formName ?? '—' }}</div>
                @if($formId)
                    <div class="font-mono text-xs text-gray-400 mt-1">{{ $formId }}</div>
                @endif
            </div>

            @if(!empty($webhook))
                <div class="md:col-span-2">
                    <details class="border rounded bg-gray-50">
                        <summary class="cursor-pointer px-4 py-2 font-medium text-gray-700">
                            View Webhook Metadata
                        </summary>
                        <pre class="text-xs p-4 overflow-x-auto whitespace-pre-wrap">{{ json_encode($webhook, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                </div>
            @endif
        </div>
    </div>

    {{-- Communications --}}
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h2 class="text-lg font-semibold mb-3">Communications</h2>

        @if($communications->count())
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Date</th>
                            <th class="px-3 py-2 text-left">Type</th>
                            <th class="px-3 py-2 text-left">Content</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($communications as $c)
                            <tr>
                                <td class="px-3 py-2">
                                    {{ $c->communication_date ? \Carbon\Carbon::parse($c->communication_date)->format('d M Y, h:i A') : '—' }}
                                </td>
                                <td class="px-3 py-2">{{ $c->communication_type ?? '—' }}</td>
                                <td class="px-3 py-2">{{ \Illuminate\Support\Str::limit($c->content ?? '', 120) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $communications->links() }}
            </div>
        @else
            <p class="text-sm text-gray-500">No communications yet.</p>
        @endif
    </div>

    {{-- Message Logs --}}
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h2 class="text-lg font-semibold mb-3">Message Logs</h2>

        @if($messageLogs->count())
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left">Date</th>
                            <th class="px-3 py-2 text-left">Channel</th>
                            <th class="px-3 py-2 text-left">Direction</th>
                            <th class="px-3 py-2 text-left">Status</th>
                            <th class="px-3 py-2 text-left">AI</th>
                            <th class="px-3 py-2 text-left">Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($messageLogs as $log)
                            <tr>
                                <td class="px-3 py-2">
                                    {{ $log->created_at?->format('d M Y, h:i A') ?? '—' }}
                                </td>
                                <td class="px-3 py-2">{{ $log->channel ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $log->direction ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $log->provider_status ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    @if((bool) ($log->is_ai ?? false))
                                        <span class="px-2 py-0.5 rounded-full text-xs bg-indigo-100 text-indigo-700">
                                            AI
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">{{ \Illuminate\Support\Str::limit($log->message ?? $log->body ?? '', 120) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $messageLogs->links() }}
            </div>
        @else
            <p class="text-sm text-gray-500">No message logs yet.</p>
        @endif
    </div>

</div>
@endsection