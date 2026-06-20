@extends('layouts.app')

@section('title', 'Lead Upload Preview')

@section('content')
@include('admin.leads.import.partials._styles')

@php
    $summary = $preview['summary'] ?? null;
    $rows = $preview['rows'] ?? [];
    $campaignGroups = $preview['campaign_groups'] ?? [];
    $sendReadyGroups = collect($campaignGroups)->where('mapping_status', 'Ready to Send');
    $sendReadyContacts = $sendReadyGroups->sum('valid_rows');
    $sendReadyGroupCount = $sendReadyGroups->count();
    $campaignTypes = $campaignTypes ?? [
        'New Lead Campaign',
        'Service Offer Campaign',
        'Retention Campaign',
        'Lost Lead Revival Campaign',
        'WhatsApp Campaign',
        'Meta Lead Form Campaign',
        'Website Form Campaign',
        'Walk-in / Manual Entry',
        'Referral Campaign',
        'Fleet Campaign',
    ];

    $statusClasses = [
        'valid' => 'bg-emerald-500/10 text-emerald-200 ring-emerald-400/20',
        'warning' => 'bg-orange-500/10 text-orange-200 ring-orange-400/20',
        'invalid' => 'bg-rose-500/10 text-rose-200 ring-rose-400/20',
    ];

    $readinessClasses = [
        'ready' => 'bg-emerald-500/10 text-emerald-200 ring-emerald-400/20',
        'needs_review' => 'bg-blue-500/10 text-blue-200 ring-blue-400/20',
        'template_pending' => 'bg-orange-500/10 text-orange-200 ring-orange-400/20',
        'missing_template_mapping' => 'bg-rose-500/10 text-rose-200 ring-rose-400/20',
        'missing_phone' => 'bg-rose-500/10 text-rose-200 ring-rose-400/20',
        'opted_out' => 'bg-slate-500/10 text-slate-200 ring-slate-400/20',
        'duplicate_recent_lead' => 'bg-orange-500/10 text-orange-200 ring-orange-400/20',
        'invalid_row' => 'bg-rose-500/10 text-rose-200 ring-rose-400/20',
    ];

    $reviewClasses = [
        'pending_review' => 'bg-blue-500/10 text-blue-200 ring-blue-400/20',
        'approved' => 'bg-emerald-500/10 text-emerald-200 ring-emerald-400/20',
        'rejected' => 'bg-rose-500/10 text-rose-200 ring-rose-400/20',
        'skipped' => 'bg-slate-500/10 text-slate-200 ring-slate-400/20',
        'applied' => 'bg-purple-500/10 text-purple-200 ring-purple-400/20',
    ];

    $mappingBadge = function ($status) {
        return match ((string) $status) {
            'Mapped' => 'sf-badge-green',
            'Preview Only' => 'sf-badge-orange',
            'Inactive' => 'sf-badge-slate',
            'Missing' => 'sf-badge-red',
            default => 'sf-badge-slate',
        };
    };

    $whatsappBadge = function ($status) {
        return match ((string) $status) {
            'Enabled' => 'sf-badge-green',
            'Preview Only' => 'sf-badge-orange',
            'Template Missing' => 'sf-badge-yellow',
            'Disabled' => 'sf-badge-slate',
            default => 'sf-badge-slate',
        };
    };

    $groupStatusBadge = function ($status) {
        return match ((string) $status) {
            'Ready to Send', 'Queued' => 'sf-badge-green',
            'Preview Only', 'Skipped' => 'sf-badge-orange',
            'Template Missing' => 'sf-badge-yellow',
            'Missing Mapping', 'Failed' => 'sf-badge-red',
            'Mapping Inactive' => 'sf-badge-slate',
            default => 'sf-badge-slate',
        };
    };
@endphp

<div class="sf-page sf-import-page w-full px-4 py-6 space-y-6 sm:px-6 lg:px-8">
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">Lead Upload Preview</div>

            <h1 class="sf-page-title mt-3">Instant Response Preview</h1>

            <p class="sf-page-subtitle">
                Preview recent marketing leads, duplicates, and WhatsApp ACK readiness before anything is saved or sent.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.leads.import.preview.batches.index') }}" class="sf-btn-soft-blue">
                Saved Previews
            </a>

            <a href="{{ route('admin.leads.import.sample') }}" class="sf-btn-soft-blue">
                Download Sample CSV
            </a>

            <a href="{{ route('admin.leads.import.upload') }}" class="sf-btn-secondary">
                Direct Import
            </a>
        </div>
    </div>

    @if(session('error'))
        <div class="sf-alert-danger">{{ session('error') }}</div>
    @endif

    @if(session('warning'))
        <div class="sf-alert-warning">{{ session('warning') }}</div>
    @endif

    @if(session('success'))
        <div class="sf-alert-success">{{ session('success') }}</div>
    @endif

    @if(session('info'))
        <div class="sf-alert-info">
            {{ session('info') }}
        </div>
    @endif

    @if(isset($errors) && $errors->any())
        <div class="sf-alert-danger">
            <div class="mb-2 font-extrabold">Please fix the following:</div>
            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="sf-card">
        <div class="sf-card-header flex items-start justify-between gap-4">
            <div>
                <h2 class="sf-section-title">Upload for preview</h2>
                <p class="sf-section-subtitle">
                    CSV, XLS, and XLSX files up to 5 MB. Preview is limited to the first 200 non-empty rows.
                </p>
            </div>
        </div>

        <div class="sf-card-body">
            <form method="POST"
                  action="{{ route('admin.leads.import.preview.process') }}"
                  enctype="multipart/form-data"
                  class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_18rem_auto] lg:items-end">
                @csrf

                <div class="min-w-0 flex-1">
                    <label class="sf-label">Lead file</label>
                    <input type="file"
                           name="lead_file"
                           accept=".csv,.txt,.xls,.xlsx,text/csv"
                           required
                           class="sf-import-field block file:mr-4 file:rounded-lg file:border-0 file:bg-orange-500 file:px-4 file:py-2 file:text-sm file:font-extrabold file:text-white hover:file:bg-orange-600">
                    <p class="sf-help mt-2">
                        Required: customer_name, phone, lead_source, and campaign_type. Backward aliases name/source are still accepted.
                    </p>
                </div>

                <div>
                    <label for="preview_campaign_type" class="sf-label">Default Campaign Type</label>
                    <select id="preview_campaign_type"
                            name="campaign_type"
                            required
                            class="sf-import-select">
                        @foreach($campaignTypes as $campaignType)
                            <option value="{{ $campaignType }}" @selected(old('campaign_type', 'New Lead Campaign') === $campaignType)>
                                {{ $campaignType }}
                            </option>
                        @endforeach
                    </select>
                    <p class="sf-help mt-2">Used only when a CSV row does not contain campaign_type. If campaign_type is present in the file, the row value wins.</p>
                </div>

                <button type="submit" class="sf-btn-primary">
                    Preview Leads
                </button>
            </form>
        </div>
    </div>

    @if($preview)
        <div class="rounded-2xl border border-blue-400/20 bg-blue-500/10 p-4 text-sm font-semibold text-blue-100">
            {{ $preview['notice'] }}
        </div>

        @if(isset($batch) && $batch)
            <div class="rounded-2xl border border-orange-400/20 bg-orange-500/10 p-4 text-sm font-semibold text-orange-100">
                Approval only marks rows for future application. It does not create leads or send messages.
            </div>
        @endif

        @if(! empty($campaignGroups))
            <div class="sf-card">
                <div class="sf-card-header flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="sf-section-title">Campaign Groups Found</h2>
                        <p class="sf-section-subtitle">
                            Review campaign_type groups from the uploaded file and override journey/template mapping for this upload if needed.
                        </p>
                    </div>

                    @if(isset($batch) && $batch)
                        <button type="submit" form="campaign-group-mapping-form" class="sf-btn-primary">
                            Save Preview
                        </button>
                    @endif
                </div>

                @if(isset($batch) && $batch)
                    <form id="campaign-group-mapping-form"
                          method="POST"
                          action="{{ route('admin.leads.import.preview.batches.mappings.save', $batch) }}">
                        @csrf
                    </form>
                @endif

                <div class="sf-table-scroll overflow-x-auto">
                    <table class="sf-table sf-import-table min-w-[1480px]">
                        <thead>
                            <tr>
                                <th>Campaign Type</th>
                                <th>Rows</th>
                                <th>Mapped Journey</th>
                                <th>Journey Key</th>
                                <th>Trigger Key</th>
                                <th>WhatsApp Template</th>
                                <th>Follow-up Template</th>
                                <th>Mode</th>
                                <th>Status</th>
                                @if(isset($batch) && $batch)
                                    <th>Default</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($campaignGroups as $group)
                                @php($groupKey = $group['key'])
                                <tr>
                                    <td>
                                        <div class="font-extrabold text-white">{{ $group['campaign_type'] }}</div>
                                        @if(isset($batch) && $batch)
                                            <input type="hidden"
                                                   name="campaign_groups[{{ $groupKey }}][campaign_type]"
                                                   value="{{ $group['campaign_type'] }}"
                                                   form="campaign-group-mapping-form">
                                        @endif
                                    </td>
                                    <td>
                                        <div class="text-sm font-semibold text-white">{{ $group['total'] }} leads</div>
                                        <div class="mt-1 text-xs text-slate-400">
                                            Valid {{ $group['valid_rows'] }} | Duplicates {{ $group['duplicate_rows'] }} | Invalid {{ $group['invalid_rows'] }}
                                        </div>
                                    </td>
                                    <td>
                                        @if(isset($batch) && $batch)
                                            <input class="sf-import-field"
                                                   name="campaign_groups[{{ $groupKey }}][journey_label]"
                                                   value="{{ $group['journey_label'] }}"
                                                   placeholder="Journey label"
                                                   form="campaign-group-mapping-form">
                                        @else
                                            {{ $group['journey_label'] ?? '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($batch) && $batch)
                                            <input class="sf-import-field"
                                                   name="campaign_groups[{{ $groupKey }}][journey_key]"
                                                   value="{{ $group['journey_key'] }}"
                                                   placeholder="journey_key"
                                                   form="campaign-group-mapping-form">
                                        @else
                                            {{ $group['journey_key'] ?? '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($batch) && $batch)
                                            <input class="sf-import-field"
                                                   name="campaign_groups[{{ $groupKey }}][journey_trigger_key]"
                                                   value="{{ $group['journey_trigger_key'] }}"
                                                   placeholder="lead.imported.example"
                                                   form="campaign-group-mapping-form">
                                        @else
                                            {{ $group['journey_trigger_key'] ?? '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($batch) && $batch)
                                            <input class="sf-import-field"
                                                   name="campaign_groups[{{ $groupKey }}][whatsapp_template_name]"
                                                   value="{{ $group['whatsapp_template_name'] }}"
                                                   placeholder="approved_template_name"
                                                   form="campaign-group-mapping-form">
                                        @else
                                            {{ $group['whatsapp_template_name'] ?? '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($batch) && $batch)
                                            <input class="sf-import-field"
                                                   name="campaign_groups[{{ $groupKey }}][followup_template_name]"
                                                   value="{{ $group['followup_template_name'] }}"
                                                   placeholder="optional_followup_template"
                                                   form="campaign-group-mapping-form">
                                        @else
                                            {{ $group['followup_template_name'] ?? '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($batch) && $batch)
                                            <div class="space-y-2 text-xs font-extrabold text-slate-300">
                                                <input type="hidden" name="campaign_groups[{{ $groupKey }}][preview_only]" value="0" form="campaign-group-mapping-form">
                                                <label class="flex items-center gap-2">
                                                    <input type="checkbox"
                                                           name="campaign_groups[{{ $groupKey }}][preview_only]"
                                                           value="1"
                                                           @checked($group['preview_only'])
                                                           class="rounded border-slate-600 bg-slate-950 text-orange-500"
                                                           form="campaign-group-mapping-form">
                                                    Preview only
                                                </label>
                                                <input type="hidden" name="campaign_groups[{{ $groupKey }}][whatsapp_enabled]" value="0" form="campaign-group-mapping-form">
                                                <label class="flex items-center gap-2">
                                                    <input type="checkbox"
                                                           name="campaign_groups[{{ $groupKey }}][whatsapp_enabled]"
                                                           value="1"
                                                           @checked($group['whatsapp_enabled'])
                                                           class="rounded border-slate-600 bg-slate-950 text-orange-500"
                                                           form="campaign-group-mapping-form">
                                                    WhatsApp enabled
                                                </label>
                                            </div>
                                        @else
                                            <div class="flex flex-wrap gap-1.5">
                                                <span class="{{ $group['preview_only'] ? 'sf-badge-orange' : 'sf-badge-green' }}">{{ $group['preview_only'] ? 'Preview Only' : 'Live Ready' }}</span>
                                                <span class="{{ $group['whatsapp_enabled'] ? 'sf-badge-green' : 'sf-badge-slate' }}">{{ $group['whatsapp_enabled'] ? 'WhatsApp On' : 'WhatsApp Off' }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex flex-wrap gap-1.5">
                                            <span class="{{ $groupStatusBadge($group['mapping_status']) }}">{{ $group['mapping_status'] }}</span>
                                            <span class="{{ $whatsappBadge($group['whatsapp_status']) }}">{{ $group['whatsapp_status'] }}</span>
                                            <span class="{{ $groupStatusBadge($group['send_status']) }}">{{ $group['send_status'] }}</span>
                                        </div>
                                    </td>
                                    @if(isset($batch) && $batch)
                                        <td>
                                            <input type="hidden" name="campaign_groups[{{ $groupKey }}][save_as_default]" value="0" form="campaign-group-mapping-form">
                                            <label class="flex items-center gap-2 text-xs font-extrabold text-slate-300">
                                                <input type="checkbox"
                                                       name="campaign_groups[{{ $groupKey }}][save_as_default]"
                                                       value="1"
                                                       class="rounded border-slate-600 bg-slate-950 text-orange-500"
                                                       form="campaign-group-mapping-form">
                                                Save as default
                                            </label>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if(isset($batch) && $batch)
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">Saved batch</h2>
                    <p class="sf-section-subtitle">This preview is persisted for later review. Review/apply/send actions are not enabled in Phase 9C.</p>
                </div>

                <div class="grid grid-cols-1 gap-3 p-5 md:grid-cols-4">
                    <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-400">Filename</div>
                        <div class="mt-2 break-words text-sm font-black text-white">{{ $batch->original_filename }}</div>
                    </div>
                    <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-400">Uploaded By</div>
                        <div class="mt-2 text-sm font-black text-white">{{ $batch->uploadedBy?->name ?? 'Unknown user' }}</div>
                    </div>
                    <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-400">Uploaded</div>
                        <div class="mt-2 text-sm font-black text-white">{{ optional($batch->created_at)->format('d M Y H:i') }}</div>
                    </div>
                    <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-400">Mode / Status</div>
                        <div class="mt-2 text-sm font-black text-white">{{ \Illuminate\Support\Str::headline($batch->mode ?? 'preview') }} / {{ \Illuminate\Support\Str::headline($batch->status) }}</div>
                    </div>
                </div>
            </div>

            <div class="sf-card">
                <div class="sf-card-header flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="sf-section-title">Import approved rows</h2>
                        <p class="sf-section-subtitle">
                            Choose import-only, or explicitly queue template-based instant ACK messages for send-ready campaign groups.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('admin.leads.import.preview.batches.apply', $batch) }}">
                            @csrf
                            <input type="hidden" name="mode" value="dry_run">
                            <button type="submit" class="sf-btn-secondary">
                                Preview Apply
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.leads.import.preview.batches.apply', $batch) }}">
                            @csrf
                            <input type="hidden" name="mode" value="apply">
                            <input type="hidden" name="ack_mode" value="import_only">
                            <button type="submit" class="sf-btn-primary">
                                Import Leads Only
                            </button>
                        </form>

                        <form method="POST"
                              action="{{ route('admin.leads.import.preview.batches.apply', $batch) }}"
                              onsubmit="return confirm('You are about to send WhatsApp ACK messages to {{ $sendReadyContacts }} contacts across {{ $sendReadyGroupCount }} campaign groups. This action cannot be undone. Continue?')">
                            @csrf
                            <input type="hidden" name="mode" value="apply">
                            <input type="hidden" name="ack_mode" value="send_ack">
                            <button type="submit" class="sf-btn-secondary">
                                Import + Send Instant ACK
                            </button>
                        </form>
                    </div>
                </div>

                <div class="border-t border-slate-800 p-5">
                    <p class="text-sm font-semibold text-orange-100">
                        ACK sending is explicit: it queues approved WhatsApp templates only for eligible rows. Broad LeadCreated journeys are still bypassed.
                    </p>
                </div>
            </div>
        @endif

        @if(session('apply_readiness'))
            @php($applyReadiness = session('apply_readiness'))
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        {{ ($applyReadiness['mode'] ?? 'dry_run') === 'apply' ? 'Apply Summary' : 'Apply Dry-Run Summary' }}
                    </h2>
                    <p class="sf-section-subtitle">
                        Dry-run/apply report. No WhatsApp messages, campaigns, or journeys were created.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 p-5 md:grid-cols-4 xl:grid-cols-8">
                    @foreach([
                        'Eligible' => $applyReadiness['eligible_rows'] ?? 0,
                        'Applied' => $applyReadiness['rows_applied'] ?? 0,
                        'Ready' => $applyReadiness['ready_to_apply'] ?? 0,
                        'Dup Blocked' => $applyReadiness['blocked_duplicate_recent_lead'] ?? 0,
                        'Clients Create' => $applyReadiness['clients_to_create'] ?? 0,
                        'Clients Reuse' => $applyReadiness['clients_to_reuse'] ?? 0,
                        'Leads Create' => $applyReadiness['leads_to_create'] ?? 0,
                        'Vehicles Create' => $applyReadiness['vehicles_to_create'] ?? 0,
                        'ACK Queued' => $applyReadiness['ack_queued'] ?? 0,
                        'ACK Failed' => $applyReadiness['ack_failed'] ?? 0,
                    ] as $label => $value)
                        <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                            <div class="text-xs font-extrabold uppercase tracking-wide text-slate-400">{{ $label }}</div>
                            <div class="mt-2 text-2xl font-black text-white">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>

                @if(! empty($applyReadiness['records']))
                    <div class="sf-table-scroll overflow-x-auto border-t border-slate-800">
                        <table class="sf-table sf-import-table min-w-[980px]">
                            <thead>
                                <tr>
                                    <th>Row</th>
                                    <th>Overall</th>
                                    <th>Client</th>
                                    <th>Lead</th>
                                    <th>Vehicle</th>
                                    <th>ACK</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($applyReadiness['records'] as $record)
                                    <tr>
                                        <td class="font-extrabold">#{{ $record['row_number'] ?? '-' }}</td>
                                        <td>{{ \Illuminate\Support\Str::headline($record['overall_status'] ?? '-') }}</td>
                                        <td>
                                            {{ \Illuminate\Support\Str::headline($record['client_action'] ?? '-') }}
                                            @if(! empty($record['client_id']))
                                                <span class="text-slate-400">#{{ $record['client_id'] }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ \Illuminate\Support\Str::headline($record['lead_action'] ?? '-') }}
                                            @if(! empty($record['lead_id']))
                                                <span class="text-slate-400">#{{ $record['lead_id'] }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ \Illuminate\Support\Str::headline($record['vehicle_action'] ?? '-') }}
                                            @if(! empty($record['vehicle_id']))
                                                <span class="text-slate-400">#{{ $record['vehicle_id'] }}</span>
                                            @endif
                                        </td>
                                        <td>{{ \Illuminate\Support\Str::headline($record['ack_status'] ?? 'not_sent_phase_9f') }}</td>
                                        <td class="max-w-md text-sm text-slate-300">{{ $record['reason'] ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif

        @if($summary['truncated'] ?? false)
            <div class="rounded-2xl border border-orange-400/20 bg-orange-500/10 p-4 text-sm font-semibold text-orange-100">
                This preview was limited to {{ $summary['limit'] }} rows. Larger lead upload batches should use persisted review storage in Phase 9C.
            </div>
        @endif

        <div class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-8">
            @foreach([
                'Rows Uploaded' => $summary['rows_read'],
                'Rows Shown' => $summary['rows_shown'],
                'Valid' => $summary['valid'],
                'Warnings' => $summary['warnings'],
                'Invalid' => $summary['invalid'],
                'Dup Clients' => $summary['duplicate_clients'],
                'Dup Leads' => $summary['duplicate_leads'],
                'ACK Ready' => $summary['ready_for_ack'],
                'ACK Blocked' => $summary['blocked_or_not_ready'],
            ] as $label => $value)
                <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-slate-400">{{ $label }}</div>
                    <div class="mt-2 text-2xl font-black text-white">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        @if(isset($batch) && $batch)
            <div class="grid grid-cols-2 gap-3 md:grid-cols-5 xl:grid-cols-9">
                @foreach([
                    'Pending' => $summary['review_pending'] ?? 0,
                    'Approved' => $summary['review_approved'] ?? 0,
                    'Rejected' => $summary['review_rejected'] ?? 0,
                    'Skipped' => $summary['review_skipped'] ?? 0,
                    'Applied' => $summary['review_applied'] ?? 0,
                    'Invalid' => $summary['invalid'],
                    'Warning' => $summary['warnings'],
                    'ACK Ready' => $summary['ready_for_ack'],
                    'ACK Blocked' => $summary['blocked_or_not_ready'],
                ] as $label => $value)
                    <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-400">{{ $label }}</div>
                        <div class="mt-2 text-2xl font-black text-white">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="sf-card">
            <div class="sf-card-header">
                <h2 class="sf-section-title">Preview rows</h2>
                <p class="sf-section-subtitle">
                    ACK key checked: {{ $preview['event_key'] }}. Fallback displayed only: {{ $preview['fallback_event_key'] }}.
                </p>
            </div>

            @if(isset($batch) && $batch)
                <form id="bulk-review-form"
                      method="POST"
                      action="{{ route('admin.leads.import.preview.batches.bulk-review', $batch) }}"
                      class="border-t border-slate-800 p-4">
                    @csrf
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div class="text-sm font-extrabold text-white">Bulk review</div>
                            <p class="mt-1 text-xs font-semibold text-slate-400">
                                Select visible rows, then mark them for future apply/send phases. Applied rows are locked.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <select name="action" class="sf-import-select w-auto min-w-[12rem]">
                                <option value="approve">Approve selected</option>
                                <option value="reject">Reject selected</option>
                                <option value="skip">Skip selected</option>
                                <option value="reset">Reset selected to pending</option>
                            </select>

                            <button type="submit" class="sf-btn-primary">
                                Apply Bulk Action
                            </button>
                        </div>
                    </div>
                </form>
            @endif

            <div class="sf-table-scroll overflow-x-auto">
                <table class="sf-table sf-import-table min-w-[1740px]">
                    <thead>
                        <tr>
                            @if(isset($batch) && $batch)
                                <th>
                                    <label class="inline-flex items-center gap-2 text-xs font-extrabold text-slate-300">
                                        <input type="checkbox"
                                               onclick="document.querySelectorAll('[data-lead-upload-row-checkbox]').forEach((checkbox) => checkbox.checked = this.checked)"
                                               class="rounded border-slate-600 bg-slate-950 text-orange-500">
                                        All
                                    </label>
                                </th>
                                <th>Review</th>
                            @endif
                            <th>Row</th>
                            <th>Status</th>
                            <th>Customer</th>
                            <th>Phone / WA</th>
                            <th>Source</th>
                            <th>Campaign Type</th>
                            <th>Mapped Journey</th>
                            <th>Mapping / WhatsApp</th>
                            <th>Service</th>
                            <th>Campaign</th>
                            <th>Duplicate Client</th>
                            <th>Duplicate Lead</th>
                            <th>ACK Readiness</th>
                            <th>Suggested Message</th>
                            <th>Warnings / Errors</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $readiness = $row['ack_readiness'] ?? [];
                                $statusClass = $statusClasses[$row['status']] ?? 'bg-slate-500/10 text-slate-200 ring-slate-400/20';
                                $readinessClass = $readinessClasses[$readiness['status'] ?? 'needs_review'] ?? 'bg-slate-500/10 text-slate-200 ring-slate-400/20';
                                $reviewStatus = $row['review_status'] ?? 'pending_review';
                                $reviewClass = $reviewClasses[$reviewStatus] ?? 'bg-slate-500/10 text-slate-200 ring-slate-400/20';
                                $rowId = $row['row_id'] ?? null;
                            @endphp
                            <tr>
                                @if(isset($batch) && $batch)
                                    <td>
                                        @if($rowId && $reviewStatus !== 'applied')
                                            <input type="checkbox"
                                                   name="row_ids[]"
                                                   value="{{ $rowId }}"
                                                   form="bulk-review-form"
                                                   data-lead-upload-row-checkbox
                                                   class="rounded border-slate-600 bg-slate-950 text-orange-500">
                                        @else
                                            <span class="text-slate-500">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-extrabold ring-1 {{ $reviewClass }}">
                                            {{ \Illuminate\Support\Str::headline($reviewStatus) }}
                                        </span>

                                        @if($rowId && $reviewStatus !== 'applied')
                                            <div class="mt-2 flex flex-wrap gap-1.5">
                                                @foreach([
                                                    'approved' => 'Approve',
                                                    'rejected' => 'Reject',
                                                    'skipped' => 'Skip',
                                                    'pending_review' => 'Reset',
                                                ] as $nextStatus => $label)
                                                    <form method="POST"
                                                          action="{{ route('admin.leads.import.preview.rows.review', [$batch, $rowId]) }}">
                                                        @csrf
                                                        <input type="hidden" name="review_status" value="{{ $nextStatus }}">
                                                        <button type="submit"
                                                                @disabled($nextStatus === 'approved' && $row['status'] === 'invalid')
                                                                class="rounded-lg border border-slate-700 bg-slate-900/80 px-2 py-1 text-[11px] font-extrabold text-slate-200 hover:border-orange-400/40 hover:text-orange-200 disabled:cursor-not-allowed disabled:opacity-40">
                                                            {{ $label }}
                                                        </button>
                                                    </form>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                @endif
                                <td class="font-extrabold">#{{ $row['row_number'] }}</td>
                                <td>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-extrabold ring-1 {{ $statusClass }}">
                                        {{ ucfirst($row['status']) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="font-extrabold text-white">{{ $row['name'] ?: 'Missing name' }}</div>
                                    <div class="text-xs text-slate-400">{{ $row['email'] ?: 'No email' }}</div>
                                    @if($row['vehicle'])
                                        <div class="text-xs text-slate-400">{{ $row['vehicle'] }}</div>
                                    @endif
                                </td>
                                <td>{{ $row['contact_phone'] ?: '-' }}</td>
                                <td>{{ $row['source'] ?: '-' }}</td>
                                <td>
                                    <div class="font-semibold text-white">{{ $row['campaign_type'] ?: '-' }}</div>
                                </td>
                                <td>
                                    <div class="font-semibold text-white">{{ $row['journey_label'] ?? 'No journey label' }}</div>
                                    <div class="mt-1 text-xs text-slate-400">{{ $row['journey_key'] ?? 'No journey key' }}</div>
                                    @if(! empty($row['journey_trigger_key']))
                                        <div class="text-xs text-slate-400">{{ $row['journey_trigger_key'] }}</div>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $mappingStatus = $row['mapping_status'] ?? 'Missing';
                                        $whatsappStatus = $row['whatsapp_status'] ?? 'Disabled';
                                    @endphp
                                    <div class="flex flex-wrap gap-1.5">
                                        <span class="{{ $mappingBadge($mappingStatus) }}">{{ $mappingStatus }}</span>
                                        <span class="{{ $whatsappBadge($whatsappStatus) }}">{{ $whatsappStatus }}</span>
                                    </div>
                                </td>
                                <td>{{ $row['service'] ?: '-' }}</td>
                                <td>{{ $row['campaign'] ?: '-' }}</td>
                                <td>
                                    @if($row['client_match'])
                                        <span class="font-semibold text-orange-200">#{{ $row['client_match']['id'] }} {{ $row['client_match']['name'] }}</span>
                                    @else
                                        <span class="text-slate-400">No match</span>
                                    @endif
                                </td>
                                <td>
                                    @if($row['lead_match'])
                                        <div class="font-semibold text-orange-200">#{{ $row['lead_match']['id'] }} {{ $row['lead_match']['status'] }}</div>
                                        <div class="text-xs text-slate-400">{{ $row['lead_match']['source'] }} | {{ $row['lead_match']['created_at'] }}</div>
                                    @else
                                        <span class="text-slate-400">No recent match</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-extrabold ring-1 {{ $readinessClass }}">
                                        {{ $readiness['label'] ?? 'Needs review' }}
                                    </span>
                                    <div class="mt-1 max-w-xs text-xs leading-5 text-slate-400">
                                        {{ $readiness['reason'] ?? '' }}
                                        @if(! empty($readiness['template']))
                                            <br>Template: {{ $readiness['template'] }}
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="max-w-md text-sm leading-6 text-slate-200">{{ $row['suggested_message'] }}</div>
                                </td>
                                <td>
                                    <div class="max-w-sm space-y-1 text-xs leading-5">
                                        @forelse(array_merge($row['errors'], $row['warnings']) as $message)
                                            <div class="{{ in_array($message, $row['errors'], true) ? 'text-rose-200' : 'text-orange-200' }}">
                                                {{ $message }}
                                            </div>
                                        @empty
                                            <span class="text-slate-400">No issues</span>
                                        @endforelse
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ isset($batch) && $batch ? 17 : 15 }}" class="py-8 text-center text-slate-400">
                                    No preview rows found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
