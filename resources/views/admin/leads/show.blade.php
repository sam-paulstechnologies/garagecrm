{{-- resources/views/admin/leads/show.blade.php --}}
@extends('layouts.app')

@section('content')
@include('admin.leads.show-partials._styles')

@php
    $badgeBase = 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset';
    $statusLabel = $lead->status_label ?? \Illuminate\Support\Str::headline((string) $lead->status);
    $sourceLabel = $lead->leadSource?->name ?? $lead->source ?? $lead->external_source ?? 'Manual';
    $vehicleLabel = $lead->vehicle_label
        ?? trim(implode(' ', array_filter([$lead->vehicle_make, $lead->vehicle_model, $lead->vehicle_year])))
        ?: ($lead->vehicleMake?->name || $lead->vehicleModel?->name
            ? trim(($lead->vehicleMake?->name ?? '') . ' ' . ($lead->vehicleModel?->name ?? ''))
            : null);
    $serviceLabel = $lead->service_type ?? $lead->service_category ?? data_get($lead->conversation_data, 'service_type');
    $ownerLabel = $lead->assignee?->name ?? 'Unassigned';
    $createdByLabel = $lead->createdBy?->name ?? 'System / unavailable';
    $updatedByLabel = $lead->updatedBy?->name ?? 'System / unavailable';
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
    $displayStatus = \App\Models\Client\Lead::normalizeStatus($lead->status);
    $currentStatusIndex = collect($statusPath)->search(fn ($step) => $step['value'] === $displayStatus);
    $currentStatusIndex = $currentStatusIndex === false ? 0 : $currentStatusIndex;
    $subStatusOptions = array_merge($contactOnHoldSubStatuses ?? [], $disqualifiedSubStatuses ?? []);
    $statusSubStatusLabel = $subStatusOptions[$lead->status_sub_status] ?? ($lead->status_sub_status ? \Illuminate\Support\Str::headline($lead->status_sub_status) : null);
    $leadWhatsappLookup = $phoneE164
        ? ltrim($phoneE164, '+')
        : ($lead->phone || $lead->phone_norm
            ? app(\App\Services\PhoneNumberService::class)->buildWhatsappLookupKey($lead->phone ?? $lead->phone_norm)
            : null);
    $leadWhatsappInboxUrl = \Illuminate\Support\Facades\Route::has('admin.inbox.index')
        ? route('admin.inbox.index', $leadWhatsappLookup ? ['search' => $leadWhatsappLookup] : [])
        : '#';
    $whatsappFloatingUrl = $leadWhatsappInboxUrl;
    $leadEmail = trim((string) ($lead->email ?? ''));
    $leadMailtoUrl = $leadEmail !== '' ? 'mailto:' . $leadEmail : null;
    $resolveWhatsappVerification = function (array $models): array {
        foreach ($models as $model) {
            if (! $model || ! method_exists($model, 'getAttributes')) {
                continue;
            }

            $attributes = $model->getAttributes();

            foreach (['whatsapp_verified', 'is_whatsapp_verified', 'verified_on_whatsapp'] as $field) {
                if (array_key_exists($field, $attributes)) {
                    return [
                        'state' => (bool) $attributes[$field] ? 'verified' : 'not_verified',
                        'source' => get_class($model) . '::' . $field,
                    ];
                }
            }

            foreach (['whatsapp_verified_at', 'phone_verified_at'] as $field) {
                if (array_key_exists($field, $attributes)) {
                    return [
                        'state' => filled($attributes[$field]) ? 'verified' : 'not_verified',
                        'source' => get_class($model) . '::' . $field,
                    ];
                }
            }
        }

        return ['state' => 'unknown', 'source' => null];
    };
    $latestWhatsappLog = collect($messageLogs?->getCollection() ?? [])
        ->first(fn ($log) => strtolower((string) $log->channel) === 'whatsapp');
    $latestWhatsappStatus = strtolower((string) ($latestWhatsappLog?->provider_status ?? ''));
    $whatsappHasFailed = in_array($latestWhatsappStatus, ['failed', 'undelivered', 'error'], true);
    $whatsappHasUsableLink = filled($leadWhatsappLookup) && \Illuminate\Support\Facades\Route::has('admin.inbox.index');
    $whatsappVerification = $resolveWhatsappVerification([$lead, $lead->client]);
    $whatsappIndicatorState = $whatsappHasFailed || ! $whatsappHasUsableLink
        ? 'failed'
        : ($whatsappVerification['state'] === 'verified' ? 'verified' : 'unverified');
    $whatsappTagMeta = match ($whatsappIndicatorState) {
        'verified' => [
            'class' => 'sf-wa-verified',
            'label' => 'WhatsApp verified',
            'title' => 'This phone number is confirmed as reachable on WhatsApp.',
        ],
        'failed' => [
            'class' => 'sf-wa-failed',
            'label' => 'WhatsApp unavailable or failed',
            'title' => $whatsappHasFailed
                ? 'The latest WhatsApp send for this lead failed.'
                : 'No usable WhatsApp inbox link is available for this phone number.',
        ],
        default => [
            'class' => 'sf-wa-unverified',
            'label' => 'WhatsApp not verified',
            'title' => $whatsappVerification['state'] === 'not_verified'
                ? 'This phone number has not been verified for WhatsApp.'
                : 'No WhatsApp verification status is available for this phone number.',
        ],
    };
    $leadStatusExplainers = [
        'new' => 'Fresh lead received and not yet worked.',
        'attempting_contact' => 'Team is trying to reach the customer.',
        'contact_on_hold' => 'Follow-up is paused until the customer or team is ready.',
        'qualified' => 'Requirement confirmed; opportunity is created or linked.',
        'disqualified' => 'Lead is closed because it is invalid or not worth pursuing.',
    ];
    $leadTemperatureExplainers = [
        'hot' => 'High-intent lead that should be prioritized quickly.',
        'warm' => 'Interested lead that needs follow-up.',
        'cold' => 'Low-intent or less responsive lead.',
    ];
    $leadPriorityExplainers = [
        'urgent' => 'Needs immediate action.',
        'high' => 'Important lead with strong potential.',
        'medium' => 'Normal priority follow-up.',
        'low' => 'Lower urgency follow-up.',
    ];
    $scoreLabel = $scoreInsights['label'] ?? 'Warm';
    $scoreExplainer = $leadTemperatureExplainers[strtolower((string) $scoreLabel)] ?? 'Lead temperature based on available lead fields.';
    $heroChips = [
        ['value' => $serviceLabel, 'title' => 'Customer requested service or enquiry type.'],
        ['value' => $vehicleLabel, 'title' => 'Vehicle context for the lead.'],
        ['value' => $sourceLabel, 'title' => 'Where this lead came from.'],
        ['value' => $ownerLabel, 'title' => 'Team member responsible for follow-up.'],
    ];

    $editUrl = route('admin.leads.edit', $lead);
    $quickUpdateUrl = route('admin.leads.quick-update', $lead);
    $fieldSections = [
        'Lead Information' => [
            ['label' => 'Name', 'value' => $lead->name, 'editable' => true, 'field' => 'name'],
            ['label' => 'Phone', 'value' => $phoneE164 ?: ($lead->phone ?? $lead->phone_norm), 'editable' => true, 'field' => 'phone', 'link' => $telUrl],
            ['label' => 'Status', 'value' => $statusLabel, 'editable' => false],
            ['label' => 'Sub Status', 'value' => $statusSubStatusLabel, 'editable' => false],
            ['label' => 'Status Reason', 'value' => $lead->status_reason, 'editable' => false],
            ['label' => 'Source', 'value' => $lead->source, 'editable' => true, 'field' => 'source'],
            ['label' => 'Service / Request Type', 'value' => $serviceLabel, 'editable' => true, 'field' => 'service_type'],
            ['label' => 'Brand', 'value' => $lead->vehicle_make ?? $lead->vehicleMake?->name, 'editable' => true, 'field' => 'vehicle_make'],
            ['label' => 'Line / Model', 'value' => $lead->vehicle_model ?? $lead->vehicleModel?->name, 'editable' => true, 'field' => 'vehicle_model'],
            ['label' => 'Dealer / Showroom', 'value' => $lead->company?->name ?? null, 'editable' => false],
            ['label' => 'Assigned User / Owner', 'value' => $ownerLabel, 'editable' => true, 'field' => 'assigned_to', 'type' => 'select', 'options' => $assignedUsers->pluck('name', 'id')->prepend('Unassigned', '')->all()],
            ['label' => 'City / Location', 'value' => data_get($lead->conversation_data, 'city') ?? data_get($lead->external_payload, 'city'), 'editable' => false],
            ['label' => 'Notes / Description', 'value' => $lead->notes, 'editable' => true, 'field' => 'notes', 'type' => 'textarea'],
        ],
        'Vehicle / Service Information' => [
            ['label' => 'Vehicle', 'value' => $vehicleLabel, 'editable' => false],
            ['label' => 'Vehicle Make', 'value' => $lead->vehicle_make ?? $lead->vehicleMake?->name, 'editable' => true, 'field' => 'vehicle_make'],
            ['label' => 'Vehicle Model', 'value' => $lead->vehicle_model ?? $lead->vehicleModel?->name, 'editable' => true, 'field' => 'vehicle_model'],
            ['label' => 'Vehicle Year', 'value' => $lead->vehicle_year, 'editable' => true, 'field' => 'vehicle_year', 'type' => 'number'],
            ['label' => 'Plate Number', 'value' => $lead->plate_number, 'editable' => true, 'field' => 'plate_number'],
            ['label' => 'Service Type', 'value' => $lead->service_type, 'editable' => true, 'field' => 'service_type'],
            ['label' => 'Request Type', 'value' => $lead->service_category, 'editable' => true, 'field' => 'service_category'],
            ['label' => 'Priority', 'value' => $lead->lead_priority, 'editable' => false],
            ['label' => 'Score', 'value' => $leadScore . '/100', 'editable' => false],
        ],
        'Follow-up / Qualification' => [
            ['label' => 'Follow-up Required', 'value' => $lead->follow_up_required ? 'Yes' : null, 'editable' => false],
            ['label' => 'Follow-up Date', 'value' => ($lead->follow_up_at ?: $lead->follow_up_date)?->format('d M Y, h:i A'), 'editable' => false],
            ['label' => 'Qualification Status', 'value' => $displayStatus === 'qualified' ? 'Qualified' : null, 'editable' => false],
            ['label' => 'Disqualification Reason', 'value' => $displayStatus === 'disqualified' ? ($lead->status_reason ?: $statusSubStatusLabel) : null, 'editable' => false],
            ['label' => 'Duplicate Flag', 'value' => $lead->duplicate_of_id ? 'Possible duplicate' : null, 'editable' => false],
            ['label' => 'Lead Bucket', 'value' => $lead->is_active ? 'Active' : 'Archived / inactive', 'editable' => false],
            ['label' => 'Last Contacted', 'value' => $lead->last_contacted_at?->format('d M Y, h:i A'), 'editable' => false],
        ],
        'Marketing / Source' => [
            ['label' => 'Lead Source', 'value' => $sourceLabel, 'editable' => true, 'field' => 'lead_source_id', 'type' => 'select', 'options' => $leadSources->pluck('name', 'id')->prepend('None', '')->all()],
            ['label' => 'Campaign Name', 'value' => $lead->campaign_name, 'editable' => true, 'field' => 'campaign_name'],
            ['label' => 'Campaign Type', 'value' => $lead->campaign_type, 'editable' => false],
            ['label' => 'Retention Tag', 'value' => $lead->retention_tag, 'editable' => false],
            ['label' => 'Entry Form / Form ID', 'value' => $lead->external_form_id ?? data_get($lead->external_payload, 'form_name'), 'editable' => false],
            ['label' => 'External Source', 'value' => $lead->external_source, 'editable' => true, 'field' => 'external_source'],
            ['label' => 'External ID', 'value' => $lead->external_id, 'editable' => false],
            ['label' => 'WhatsApp Source', 'value' => $lead->preferred_channel === 'whatsapp' ? 'Preferred channel' : $lead->preferred_channel, 'editable' => true, 'field' => 'preferred_channel', 'type' => 'select', 'options' => ['whatsapp' => 'WhatsApp', 'phone' => 'Phone', 'email' => 'Email', '' => 'Not set']],
        ],
        'System Information' => [
            ['label' => 'Created By', 'value' => $createdByLabel, 'editable' => false],
            ['label' => 'Created At', 'value' => $lead->created_at?->format('d M Y, h:i A'), 'editable' => false],
            ['label' => 'Last Modified By', 'value' => $updatedByLabel, 'editable' => false],
            ['label' => 'Last Modified At', 'value' => $lead->updated_at?->format('d M Y, h:i A'), 'editable' => false],
            ['label' => 'Archived Status', 'value' => $lead->is_active ? 'Not archived' : 'Archived / inactive', 'editable' => false],
            ['label' => 'Received At', 'value' => $lead->external_received_at?->format('d M Y, h:i A'), 'editable' => false],
        ],
    ];
@endphp

<div class="sf-leads-show min-h-screen px-4 py-6 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-5">
        @if(session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <a href="{{ route('admin.leads.index') }}" class="sf-lead-back-link inline-flex text-sm font-bold">Back to Leads</a>

        <section class="sf-leads-show-panel sf-lead-hero-sticky rounded-2xl border p-5 shadow-sm">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                <div class="min-w-0 space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="{{ $badgeBase }} sf-lead-badge sf-has-explainer ring-1" title="Lead record in the SayaraForce CRM." aria-label="Lead record in the SayaraForce CRM.">Lead</span>
                        <span class="{{ $badgeBase }} sf-lead-chip sf-has-explainer ring-1" title="{{ $leadStatusExplainers[$displayStatus] ?? 'Current lead lifecycle status.' }}" aria-label="{{ $statusLabel }}: {{ $leadStatusExplainers[$displayStatus] ?? 'Current lead lifecycle status.' }}">{{ $statusLabel }}</span>
                        @if($lead->is_hot || ($scoreInsights['label'] ?? null) === 'Hot')
                            <span class="{{ $badgeBase }} sf-lead-badge-hot sf-has-explainer ring-1" title="{{ $leadTemperatureExplainers['hot'] }}" aria-label="Hot: {{ $leadTemperatureExplainers['hot'] }}">Hot</span>
                        @endif
                    </div>

                    <div>
                        <h1 class="sf-leads-show-title text-2xl font-black tracking-tight sm:text-3xl">{{ $lead->name ?: 'Unnamed Lead' }}</h1>
                        <div class="mt-3 flex flex-wrap gap-2 text-sm font-semibold text-slate-300">
                            @if($phoneE164 || $lead->phone)
                                @if($telUrl)
                                    <a href="{{ $telUrl }}" class="sf-lead-chip sf-has-explainer rounded-full border px-3 py-1.5 hover:border-orange-400" title="Click to call this customer." aria-label="Click to call this customer.">{{ $phoneE164 ?: $lead->phone }}</a>
                                @else
                                    <span class="sf-lead-chip sf-has-explainer rounded-full border px-3 py-1.5" title="Customer phone number." aria-label="Customer phone number.">{{ $lead->phone }}</span>
                                @endif
                                <a href="{{ $leadWhatsappInboxUrl }}" class="sf-wa-tag {{ $whatsappTagMeta['class'] }}" title="{{ $whatsappTagMeta['title'] }}" aria-label="{{ $whatsappTagMeta['label'] }}">
                                    <svg class="sf-wa-tag-icon" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
                                        <path d="M16.01 3C8.83 3 3 8.83 3 16.01c0 2.29.6 4.53 1.74 6.5L3 29l6.67-1.7A12.92 12.92 0 0 0 16.01 29C23.18 29 29 23.18 29 16.01 29 8.83 23.18 3 16.01 3Zm0 23.75c-2.01 0-3.97-.54-5.69-1.57l-.41-.24-3.96 1.01 1.06-3.86-.27-.43a10.63 10.63 0 0 1-1.5-5.65c0-5.94 4.83-10.77 10.77-10.77s10.76 4.83 10.76 10.77-4.83 10.74-10.76 10.74Zm5.9-8.06c-.32-.16-1.9-.94-2.2-1.04-.29-.11-.51-.16-.72.16-.21.32-.83 1.04-1.02 1.25-.19.21-.38.24-.7.08-.32-.16-1.36-.5-2.59-1.59-.96-.86-1.6-1.91-1.79-2.23-.19-.32-.02-.5.14-.66.14-.14.32-.38.48-.56.16-.19.21-.32.32-.54.11-.21.05-.4-.03-.56-.08-.16-.72-1.74-.99-2.39-.26-.62-.52-.54-.72-.55h-.61c-.21 0-.56.08-.85.4-.29.32-1.12 1.09-1.12 2.66s1.15 3.09 1.31 3.3c.16.21 2.26 3.45 5.48 4.84.77.33 1.37.53 1.84.68.77.24 1.47.21 2.03.13.62-.09 1.9-.78 2.17-1.53.27-.75.27-1.39.19-1.53-.08-.13-.29-.21-.61-.37Z"/>
                                    </svg>
                                </a>
                            @endif
                            @foreach($heroChips as $headerChip)
                                @if(filled($headerChip['value']))
                                    <span class="sf-lead-chip sf-has-explainer rounded-full border px-3 py-1.5" title="{{ $headerChip['title'] }}" aria-label="{{ $headerChip['title'] }}">{{ $headerChip['value'] }}</span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 xl:justify-end">
                    <a href="#lead-activity-timeline" class="sf-btn-secondary rounded-full px-4 py-2 text-sm font-bold">View All Activity</a>
                    <a href="{{ $editUrl }}" class="sf-btn-primary rounded-full px-4 py-2 text-sm font-bold">Edit</a>
                    <form method="POST" action="{{ route('admin.leads.destroy', $lead) }}" onsubmit="return confirm('Archive this lead? This will not hard-delete it.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="sf-btn-danger rounded-full bg-red-600 px-4 py-2 text-sm font-bold hover:bg-red-500">Archive</button>
                    </form>
                </div>
            </div>
        </section>

        <section class="sf-leads-show-panel rounded-2xl border p-4 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                @foreach($statusPath as $index => $statusStep)
                    @php
                        $isCurrent = $index === $currentStatusIndex;
                        $isDone = $index < $currentStatusIndex;
                        $stepClass = $isCurrent
                            ? 'is-current'
                            : ($isDone ? 'is-complete' : '');
                        $needsContext = in_array($statusStep['value'], ['contact_on_hold', 'disqualified'], true);
                        $statusHasError = old('status') === $statusStep['value'] && ($errors->has('status_sub_status') || $errors->has('follow_up_at') || $errors->has('status_reason'));
                        $statusExplainer = $leadStatusExplainers[$statusStep['value']] ?? 'Update the lead lifecycle status.';
                    @endphp
                    <div class="min-w-0 flex-1">
                        @if($needsContext)
                            <details class="sf-status-context" @if($statusHasError) open @endif>
                                <summary class="sf-lead-status-step sf-has-explainer w-full cursor-pointer rounded-full border px-4 py-2 text-center text-xs font-black uppercase tracking-wide transition {{ $stepClass }}" title="{{ $statusExplainer }}" aria-label="{{ $statusStep['label'] }}: {{ $statusExplainer }}">
                                    {{ $statusStep['label'] }}
                                </summary>
                                <form method="POST" action="{{ route('admin.leads.status', $lead) }}" class="mt-3 space-y-3 rounded-2xl border border-orange-200 bg-orange-50 p-4">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $statusStep['value'] }}">

                                    <label class="block text-xs font-black uppercase text-slate-700">
                                        {{ $statusStep['value'] === 'contact_on_hold' ? 'Hold Reason' : 'Disqualification Reason' }}
                                    </label>
                                    <select name="status_sub_status" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-950">
                                        <option value="">Select reason</option>
                                        @foreach($statusStep['value'] === 'contact_on_hold' ? $contactOnHoldSubStatuses : $disqualifiedSubStatuses as $optionValue => $optionLabel)
                                            <option value="{{ $optionValue }}" @selected(old('status') === $statusStep['value'] && old('status_sub_status') === $optionValue)>{{ $optionLabel }}</option>
                                        @endforeach
                                    </select>
                                    @if($statusHasError && $errors->has('status_sub_status'))
                                        <div class="text-xs font-bold text-red-700">{{ $errors->first('status_sub_status') }}</div>
                                    @endif

                                    @if($statusStep['value'] === 'contact_on_hold')
                                        <label class="block text-xs font-black uppercase text-slate-700">Follow-up Date / Time</label>
                                        <input type="datetime-local" name="follow_up_at" value="{{ old('status') === $statusStep['value'] && old('follow_up_at') ? \Illuminate\Support\Carbon::parse(old('follow_up_at'))->format('Y-m-d\\TH:i') : '' }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-950">
                                        @if($statusHasError && $errors->has('follow_up_at'))
                                            <div class="text-xs font-bold text-red-700">{{ $errors->first('follow_up_at') }}</div>
                                        @endif
                                    @endif

                                    <label class="block text-xs font-black uppercase text-slate-700">Note / Reason</label>
                                    <textarea name="status_reason" rows="3" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-950" placeholder="Required when Other is selected">{{ old('status') === $statusStep['value'] ? old('status_reason') : '' }}</textarea>
                                    @if($statusHasError && $errors->has('status_reason'))
                                        <div class="text-xs font-bold text-red-700">{{ $errors->first('status_reason') }}</div>
                                    @endif

                                    <div class="flex flex-wrap gap-2">
                                        <button type="submit" class="sf-btn-primary rounded-full px-4 py-2 text-xs font-black">Save Status</button>
                                        <button type="button" class="sf-btn-secondary rounded-full px-4 py-2 text-xs font-black" onclick="this.closest('details').open = false">Cancel</button>
                                    </div>
                                </form>
                            </details>
                        @else
                            <form method="POST" action="{{ route('admin.leads.status', $lead) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $statusStep['value'] }}">
                                <button type="submit" class="sf-lead-status-step sf-has-explainer w-full rounded-full border px-4 py-2 text-center text-xs font-black uppercase tracking-wide transition {{ $stepClass }}" title="{{ $statusExplainer }}" aria-label="{{ $statusStep['label'] }}: {{ $statusExplainer }}">
                                    {{ $statusStep['label'] }}
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
            <main class="space-y-5">
                @foreach($fieldSections as $sectionTitle => $fields)
                    @php
                        $visibleFields = collect($fields)->filter(fn ($field) => filled($field['value'] ?? null) || (bool) ($field['editable'] ?? false));
                        $sectionEditable = $sectionTitle !== 'System Information';
                    @endphp
                    @if($visibleFields->isNotEmpty())
                        <section class="sf-leads-show-panel rounded-2xl border shadow-sm">
                            <div class="sf-lead-section-header flex items-center justify-between gap-3 border-b p-5">
                                <h2 class="sf-leads-show-title text-lg font-black">{{ $sectionTitle }}</h2>
                                @if($sectionEditable)
                                    <a href="{{ $editUrl }}" class="sf-lead-edit-link text-xs font-bold">Edit</a>
                                @endif
                            </div>
                            <div class="sf-lead-cube-grid grid grid-cols-1 gap-4 p-5 md:grid-cols-2">
                                @foreach($visibleFields as $field)
                                    @php
                                        $label = $field['label'];
                                        $value = $field['value'];
                                        $editable = (bool) ($field['editable'] ?? false);
                                        $quickField = $field['field'] ?? null;
                                        $inputType = $field['type'] ?? 'text';
                                        $options = $field['options'] ?? [];
                                        $link = $field['link'] ?? null;
                                        $fieldId = $quickField ? 'lead-field-' . str_replace('_', '-', $quickField) : null;
                                        $rawValue = $quickField ? ($lead->{$quickField} ?? '') : '';
                                        $hasFieldError = old('field') === $quickField && $errors->has('value');
                                    @endphp
                                    <div class="sf-lead-field-cube rounded-xl border p-4" @if($fieldId) id="{{ $fieldId }}" @endif>
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="sf-lead-field-label text-xs font-black uppercase tracking-wide">{{ $label }}</div>
                                            @if($editable && $quickField)
                                                <button type="button" class="sf-lead-edit-link text-xs font-bold" onclick="this.closest('.sf-lead-field-cube').querySelector('details').open = true">Edit</button>
                                            @endif
                                        </div>
                                        <div class="sf-lead-field-value mt-2 break-words text-sm font-bold">
                                            @if(filled($value))
                                                @if($link)
                                                    <a href="{{ $link }}" class="hover:text-orange-700">{{ $value }}</a>
                                                @else
                                                    {{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}
                                                @endif
                                            @else
                                                <span class="sf-lead-not-set">Not set</span>
                                            @endif
                                        </div>

                                        @if($editable && $quickField)
                                            <details class="sf-row-edit mt-3" @if($hasFieldError) open @endif>
                                                <summary class="sr-only">Quick edit {{ $label }}</summary>
                                                <form method="POST" action="{{ $quickUpdateUrl }}" class="grid gap-3 rounded-xl border border-orange-200 bg-orange-50 p-3 md:grid-cols-[minmax(0,1fr)_auto] md:items-start">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="field" value="{{ $quickField }}">

                                                    <div class="space-y-2">
                                                        @if($inputType === 'select')
                                                            <select name="value" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-950 focus:border-orange-500 focus:ring-orange-500">
                                                                @foreach($options as $optionValue => $optionLabel)
                                                                    <option value="{{ $optionValue }}" @selected((string) old('value', $rawValue) === (string) $optionValue)>
                                                                        {{ $optionLabel }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        @elseif($inputType === 'textarea')
                                                            <textarea name="value" rows="3" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-950 focus:border-orange-500 focus:ring-orange-500">{{ old('value', $rawValue) }}</textarea>
                                                        @else
                                                            <input type="{{ $inputType }}" name="value" value="{{ old('value', $rawValue) }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-950 focus:border-orange-500 focus:ring-orange-500">
                                                        @endif

                                                        @if($hasFieldError)
                                                            <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-bold text-red-700">
                                                                {{ $errors->first('value') }}
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="flex flex-wrap gap-2 md:justify-end">
                                                        <button type="submit" class="sf-btn-primary rounded-full px-4 py-2 text-xs font-black">Save</button>
                                                        <button type="button" class="sf-btn-secondary rounded-full px-4 py-2 text-xs font-black" onclick="this.closest('details').open = false">Cancel</button>
                                                    </div>
                                                </form>
                                            </details>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                @endforeach
            </main>

            <aside class="space-y-5">
                <section class="sf-leads-show-panel rounded-2xl border p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="sf-lead-field-label text-xs font-black uppercase tracking-wide">Lead Score</div>
                            <div class="sf-leads-show-title mt-2 text-4xl font-black">{{ $leadScore }}</div>
                        </div>
                        <span class="{{ $badgeBase }} sf-lead-badge sf-has-explainer ring-1" title="{{ $scoreExplainer }}" aria-label="{{ $scoreLabel }}: {{ $scoreExplainer }}">{{ $scoreLabel }}</span>
                    </div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-200">
                        <div class="h-full rounded-full bg-orange-500" style="width: {{ min(100, max(0, (int) $leadScore)) }}%"></div>
                    </div>
                    <div class="sf-lead-field-label mt-5 text-xs font-bold uppercase tracking-wide">{{ $scoreInsights['source_label'] }}</div>
                    <ul class="mt-3 space-y-2 text-sm font-semibold">
                        @foreach($scoreInsights['reasons'] as $reason)
                            <li class="sf-lead-score-reason rounded-xl border p-3">{{ $reason }}</li>
                        @endforeach
                    </ul>
                    <div class="sf-lead-next-action mt-4 rounded-xl border p-3 text-sm font-bold">
                        {{ $scoreInsights['next_action'] }}
                    </div>
                </section>

                <section class="sf-leads-show-panel rounded-2xl border p-5 shadow-sm">
                    <h2 class="sf-leads-show-title text-lg font-black">Contact</h2>
                    <div class="sf-contact-list mt-4 space-y-3">
                        <div class="sf-contact-row rounded-xl border p-3">
                            <div class="sf-contact-label text-xs font-black uppercase tracking-wide">Call</div>
                            <div class="sf-contact-value mt-1 text-sm font-bold">
                                @if($telUrl)
                                    <a href="{{ $telUrl }}" class="sf-contact-link break-all" title="Click to call this customer." aria-label="Click to call this customer.">{{ $phoneE164 ?: $lead->phone }}</a>
                                @else
                                    <span class="sf-lead-not-set">Phone not set</span>
                                @endif
                            </div>
                        </div>

                        <div class="sf-contact-row rounded-xl border p-3">
                            <div class="sf-contact-label text-xs font-black uppercase tracking-wide">WhatsApp</div>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <a href="{{ $leadWhatsappInboxUrl }}" class="sf-wa-tag {{ $whatsappTagMeta['class'] }}" title="{{ $whatsappTagMeta['title'] }}" aria-label="{{ $whatsappTagMeta['label'] }}">
                                    <svg class="sf-wa-tag-icon" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
                                        <path d="M16.01 3C8.83 3 3 8.83 3 16.01c0 2.29.6 4.53 1.74 6.5L3 29l6.67-1.7A12.92 12.92 0 0 0 16.01 29C23.18 29 29 23.18 29 16.01 29 8.83 23.18 3 16.01 3Zm0 23.75c-2.01 0-3.97-.54-5.69-1.57l-.41-.24-3.96 1.01 1.06-3.86-.27-.43a10.63 10.63 0 0 1-1.5-5.65c0-5.94 4.83-10.77 10.77-10.77s10.76 4.83 10.76 10.77-4.83 10.74-10.76 10.74Zm5.9-8.06c-.32-.16-1.9-.94-2.2-1.04-.29-.11-.51-.16-.72.16-.21.32-.83 1.04-1.02 1.25-.19.21-.38.24-.7.08-.32-.16-1.36-.5-2.59-1.59-.96-.86-1.6-1.91-1.79-2.23-.19-.32-.02-.5.14-.66.14-.14.32-.38.48-.56.16-.19.21-.32.32-.54.11-.21.05-.4-.03-.56-.08-.16-.72-1.74-.99-2.39-.26-.62-.52-.54-.72-.55h-.61c-.21 0-.56.08-.85.4-.29.32-1.12 1.09-1.12 2.66s1.15 3.09 1.31 3.3c.16.21 2.26 3.45 5.48 4.84.77.33 1.37.53 1.84.68.77.24 1.47.21 2.03.13.62-.09 1.9-.78 2.17-1.53.27-.75.27-1.39.19-1.53-.08-.13-.29-.21-.61-.37Z"/>
                                    </svg>
                                </a>
                                <span class="sf-contact-muted text-xs font-semibold">Internal Inbox</span>
                            </div>
                        </div>

                        <div class="sf-contact-row rounded-xl border p-3">
                            <div class="sf-contact-label text-xs font-black uppercase tracking-wide">Email</div>
                            <div class="sf-contact-value mt-1 text-sm font-bold">
                                @if($leadMailtoUrl)
                                    <a href="{{ $leadMailtoUrl }}" class="sf-contact-link break-all">{{ $leadEmail }}</a>
                                @else
                                    <span class="sf-lead-not-set">Email not set</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @if($conversation)
                        <div class="mt-3 text-xs font-semibold text-slate-400">Conversation #{{ $conversation->id }} linked in the internal inbox.</div>
                    @endif
                </section>

                <section id="lead-activity-timeline" class="sf-leads-show-panel rounded-2xl border shadow-sm">
                    <div class="sf-lead-section-header border-b p-5">
                        <h2 class="sf-leads-show-title text-lg font-black">Activity Timeline</h2>
                    </div>
                    <div class="max-h-[680px] space-y-3 overflow-y-auto p-5">
                        @forelse($activityTimeline as $item)
                            <div class="sf-lead-activity-item rounded-xl border p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="sf-lead-field-value text-sm font-black">{{ $item['action'] }}</div>
                                        <div class="mt-1 text-xs font-semibold text-slate-500">{{ $item['actor'] }}{{ $item['source'] ? ' - ' . $item['source'] : '' }}</div>
                                    </div>
                                    <div class="whitespace-nowrap text-xs font-semibold text-slate-500">{{ optional($item['timestamp'])->format('d M, h:i A') }}</div>
                                </div>
                                @if($item['field'] || $item['old'] || $item['new'])
                                    <div class="mt-3 text-xs font-semibold text-slate-300">
                                        @if($item['field'])<span class="text-slate-500">{{ $item['field'] }}:</span>@endif
                                        @if($item['old']) <span>{{ $item['old'] }}</span> <span class="text-slate-500">to</span> @endif
                                        @if($item['new']) <span>{{ $item['new'] }}</span> @endif
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="sf-lead-activity-item rounded-xl border p-6 text-center text-sm font-bold">No activity yet.</div>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>
    </div>
</div>
@endsection
