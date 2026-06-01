@extends('layouts.app')

@section('title', 'Lead Details')

@section('content')
    @include('admin.leads.show-partials._styles')

    @php
        $payload = is_array($lead->external_payload) ? $lead->external_payload : [];
        $webhook = $payload['_webhook'] ?? [];
        $sourceLabel = $lead->leadSource?->name ?? $lead->source ?? 'Manual';
        $leadSourceType = $lead->leadSource?->type;
        $leadSourceStatus = $lead->leadSource?->status;
        $pageName = $lead->leadSource?->configValue('page_name') ?? data_get($webhook, 'page_name');
        $pageId = $lead->leadSource?->configValue('page_id') ?? data_get($webhook, 'page_id');
        $formName = $lead->leadSource?->configValue('form_name') ?? data_get($webhook, 'form_name');
        $formId = $lead->external_form_id ?? $lead->leadSource?->configValue('form_id') ?? data_get($webhook, 'form_id');
        $leadgenId = $lead->external_id ?? data_get($webhook, 'leadgen_id');

        $statusBadgeClass = match (strtolower((string) $lead->status)) {
            'new' => 'bg-blue-500/10 text-blue-300 ring-blue-400/20',
            'attempting_contact' => 'bg-yellow-500/10 text-yellow-300 ring-yellow-400/20',
            'contact_on_hold' => 'bg-orange-500/10 text-orange-300 ring-orange-400/20',
            'qualified' => 'bg-green-500/10 text-green-300 ring-green-400/20',
            'converted' => 'bg-emerald-500/10 text-emerald-300 ring-emerald-400/20',
            'disqualified', 'lost' => 'bg-red-500/10 text-red-300 ring-red-400/20',
            default => 'bg-slate-500/10 text-slate-300 ring-slate-400/20',
        };

        $score = (int) ($leadScore ?? $lead->score ?? 0);
        $scoreBadgeClass = $score >= 75
            ? 'bg-green-500/10 text-green-300 ring-green-400/20'
            : ($score >= 45 ? 'bg-yellow-500/10 text-yellow-300 ring-yellow-400/20' : 'bg-slate-500/10 text-slate-300 ring-slate-400/20');
        $badgeBase = 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold ring-1';
    @endphp

    <div class="sf-page sf-leads-show mx-auto max-w-7xl px-4 py-6 space-y-6">
        @include('admin.leads.show-partials._header')

        @include('admin.leads.show-partials._summary')

        @include('admin.leads.show-partials._details')

        @include('admin.leads.show-partials._source_attribution')

        @include('admin.leads.show-partials._communications')

        @include('admin.leads.show-partials._message_logs')
    </div>
@endsection
