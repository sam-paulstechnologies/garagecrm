@extends('layouts.app')

@section('title', 'Campaign Journey Mapping')

@section('content')
<style>
    .sf-journey-page {
        width: 100% !important;
        max-width: none !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        color: #e2e8f0;
    }

    .sf-journey-panel {
        width: 100%;
        max-width: none;
        border-color: #1e293b;
        background: rgba(11, 18, 32, 0.86);
        color: #e2e8f0;
    }

    .sf-journey-soft-panel {
        border-color: rgba(30, 41, 59, 0.95);
        background: rgba(8, 17, 31, 0.72);
    }

    .sf-journey-title,
    .sf-journey-value,
    .sf-journey-table td {
        color: #f8fafc;
    }

    .sf-journey-muted,
    .sf-journey-table th {
        color: #94a3b8;
    }

    .sf-journey-input,
    .sf-journey-textarea {
        width: 100%;
        border-radius: 0.75rem;
        border: 1px solid #334155;
        background: #08111f;
        color: #f8fafc;
        font-size: 0.8125rem;
        font-weight: 700;
    }

    .sf-journey-input {
        min-height: 2.5rem;
        padding: 0.55rem 0.75rem;
    }

    .sf-journey-textarea {
        min-height: 4.75rem;
        padding: 0.7rem 0.75rem;
    }

    .sf-journey-input:focus,
    .sf-journey-textarea:focus {
        border-color: #ff7a1a;
        outline: none;
        box-shadow: 0 0 0 3px rgba(255, 122, 26, 0.18);
    }

    .sf-journey-table {
        background: transparent;
        border-collapse: separate;
        border-spacing: 0;
    }

    .sf-journey-table thead tr,
    .sf-journey-table th {
        background: rgba(8, 17, 31, 0.92);
        border-color: rgba(30, 41, 59, 0.95);
    }

    .sf-journey-table tbody tr {
        border-color: rgba(30, 41, 59, 0.9);
        background: rgba(11, 18, 32, 0.62);
    }

    .sf-journey-table tbody tr:hover {
        background: rgba(255, 122, 26, 0.07);
    }

    .sf-journey-page .sf-btn-primary,
    .sf-journey-page .sf-btn-secondary,
    .sf-journey-page .sf-btn-row {
        display: inline-flex;
        min-height: 2.5rem;
        align-items: center;
        justify-content: center;
        border-radius: 0.75rem;
        padding: 0 1rem;
        font-size: 0.875rem;
        font-weight: 800;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .sf-journey-page .sf-btn-primary {
        border: 1px solid #ff7a1a;
        background: #ff7a1a;
        color: #ffffff;
        box-shadow: 0 12px 24px rgba(249, 115, 22, 0.22);
    }

    .sf-journey-page .sf-btn-secondary,
    .sf-journey-page .sf-btn-row {
        border: 1px solid #334155;
        background: #0f172a;
        color: #e2e8f0;
    }

    .sf-journey-page .sf-btn-primary:hover,
    .sf-journey-page .sf-btn-secondary:hover,
    .sf-journey-page .sf-btn-row:hover {
        transform: translateY(-1px);
    }

    .sf-badge-blue,
    .sf-badge-orange,
    .sf-badge-yellow,
    .sf-badge-green,
    .sf-badge-red,
    .sf-badge-slate {
        display: inline-flex;
        align-items: center;
        border-radius: 9999px;
        padding: 0.25rem 0.6rem;
        font-size: 0.75rem;
        font-weight: 800;
        line-height: 1;
        white-space: nowrap;
    }

    .sf-badge-blue { background: #dbeafe; color: #1e3a8a; }
    .sf-badge-orange { background: #ffedd5; color: #9a3412; }
    .sf-badge-yellow { background: #fef3c7; color: #92400e; }
    .sf-badge-green { background: #dcfce7; color: #166534; }
    .sf-badge-red { background: #fee2e2; color: #991b1b; }
    .sf-badge-slate { background: #e2e8f0; color: #334155; }

    html[data-theme="light"] .sf-journey-page {
        color: #0f172a;
    }

    html[data-theme="light"] .sf-journey-panel {
        border-color: #dbe3ef !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-journey-soft-panel {
        border-color: #dbe3ef !important;
        background: #f8fafc !important;
    }

    html[data-theme="light"] .sf-journey-title,
    html[data-theme="light"] .sf-journey-value,
    html[data-theme="light"] .sf-journey-table td {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-journey-muted,
    html[data-theme="light"] .sf-journey-table th {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-journey-input,
    html[data-theme="light"] .sf-journey-textarea {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-journey-table thead tr,
    html[data-theme="light"] .sf-journey-table th {
        background: #f8fafc !important;
        border-color: #dbe3ef !important;
    }

    html[data-theme="light"] .sf-journey-table tbody tr {
        border-color: #e2e8f0 !important;
        background: #ffffff !important;
    }

    html[data-theme="light"] .sf-journey-table tbody tr:nth-child(even) {
        background: #fbfdff !important;
    }

    html[data-theme="light"] .sf-journey-page .sf-btn-secondary,
    html[data-theme="light"] .sf-journey-page .sf-btn-row {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }
</style>

<div class="sf-page sf-journey-page w-full px-4 py-6 space-y-6 sm:px-6 lg:px-8">
    <div class="sf-journey-panel rounded-2xl border p-5 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="sf-badge-orange">Preview Mode Only</div>

                <h1 class="sf-journey-title mt-3 text-3xl font-extrabold tracking-tight">
                    Campaign Journey Mapping
                </h1>

                <p class="sf-journey-muted mt-2 max-w-3xl text-sm font-medium">
                    Map imported campaign types to journey keys. Live WhatsApp sending remains disabled until templates and journey activation are approved.
                </p>
            </div>

            <form method="POST" action="{{ route('admin.growth.journey-mapping.reset-missing-defaults') }}">
                @csrf
                <button type="submit" class="sf-btn-secondary">
                    Reset Missing Defaults
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-red-400/20 bg-red-500/10 p-4 text-sm font-bold text-red-200">
            <div class="mb-2 font-extrabold">Please fix the following:</div>
            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="sf-journey-panel rounded-2xl border p-4 shadow-sm">
        <p class="text-sm font-bold leading-6 text-orange-200">
            Mappings are currently used for preview and planning. Live journey enrollment and WhatsApp sending are disabled until explicitly enabled in a later phase.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach([
            ['label' => 'Total campaign types', 'value' => $summary['total'] ?? 0, 'badge' => 'sf-badge-blue'],
            ['label' => 'Active mappings', 'value' => $summary['active'] ?? 0, 'badge' => 'sf-badge-green'],
            ['label' => 'Preview-only mappings', 'value' => $summary['preview_only'] ?? 0, 'badge' => 'sf-badge-orange'],
            ['label' => 'WhatsApp-enabled', 'value' => $summary['whatsapp_enabled'] ?? 0, 'badge' => 'sf-badge-yellow'],
            ['label' => 'Missing journey keys', 'value' => $summary['missing_journey_keys'] ?? 0, 'badge' => 'sf-badge-red'],
        ] as $card)
            <div class="sf-journey-panel rounded-2xl border p-5 shadow-sm">
                <div class="{{ $card['badge'] }}">{{ $card['label'] }}</div>
                <div class="sf-journey-value mt-3 text-3xl font-extrabold">{{ $card['value'] }}</div>
            </div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('admin.growth.journey-mapping.bulk-update') }}" class="sf-journey-panel overflow-hidden rounded-2xl border shadow-sm">
        @csrf

        <div class="flex flex-col gap-3 border-b border-slate-800 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="sf-journey-title text-lg font-extrabold">Bulk editable mapping table</h2>
                <p class="sf-journey-muted mt-1 text-sm font-medium">
                    Toggle and save settings safely. Active and WhatsApp-enabled flags do not start live automation in this phase.
                </p>
            </div>

            <button type="submit" class="sf-btn-primary">
                Save All
            </button>
        </div>

        <div class="sf-table-scroll overflow-x-auto">
            <table class="sf-table sf-journey-table min-w-[1680px]">
                <thead>
                    <tr>
                        <th class="w-[13%]">Campaign Type</th>
                        <th class="w-[12%]">Journey Label</th>
                        <th class="w-[11%]">Journey Key</th>
                        <th class="w-[12%]">Trigger Key</th>
                        <th class="w-[7%]">Active?</th>
                        <th class="w-[7%]">Preview Only?</th>
                        <th class="w-[8%]">WhatsApp Enabled?</th>
                        <th class="w-[10%]">WhatsApp Template</th>
                        <th class="w-[10%]">Follow-up Template</th>
                        <th class="w-[12%]">Notes</th>
                        <th class="w-[6%] text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mappings as $mapping)
                        @php
                            $mappingBadge = blank($mapping->journey_key)
                                ? 'sf-badge-red'
                                : (! $mapping->is_active ? 'sf-badge-slate' : ($mapping->preview_only ? 'sf-badge-orange' : 'sf-badge-green'));
                            $mappingLabel = blank($mapping->journey_key)
                                ? 'Missing'
                                : (! $mapping->is_active ? 'Inactive' : ($mapping->preview_only ? 'Preview Only' : 'Mapped'));
                        @endphp
                        <tr>
                            <td>
                                <div class="sf-journey-title font-extrabold">{{ $mapping->campaign_type }}</div>
                                <div class="mt-2 {{ $mappingBadge }}">{{ $mappingLabel }}</div>
                            </td>
                            <td>
                                <input name="mappings[{{ $mapping->id }}][journey_label]" value="{{ old("mappings.{$mapping->id}.journey_label", $mapping->journey_label) }}" class="sf-journey-input">
                            </td>
                            <td>
                                <input name="mappings[{{ $mapping->id }}][journey_key]" value="{{ old("mappings.{$mapping->id}.journey_key", $mapping->journey_key) }}" class="sf-journey-input">
                            </td>
                            <td>
                                <input name="mappings[{{ $mapping->id }}][journey_trigger_key]" value="{{ old("mappings.{$mapping->id}.journey_trigger_key", $mapping->journey_trigger_key) }}" class="sf-journey-input">
                            </td>
                            <td>
                                <input type="hidden" name="mappings[{{ $mapping->id }}][is_active]" value="0">
                                <label class="inline-flex items-center gap-2 text-xs font-extrabold">
                                    <input type="checkbox" name="mappings[{{ $mapping->id }}][is_active]" value="1" @checked(old("mappings.{$mapping->id}.is_active", $mapping->is_active)) class="rounded border-slate-600 bg-slate-950 text-orange-500">
                                    Active
                                </label>
                            </td>
                            <td>
                                <input type="hidden" name="mappings[{{ $mapping->id }}][preview_only]" value="0">
                                <label class="inline-flex items-center gap-2 text-xs font-extrabold">
                                    <input type="checkbox" name="mappings[{{ $mapping->id }}][preview_only]" value="1" @checked(old("mappings.{$mapping->id}.preview_only", $mapping->preview_only)) class="rounded border-slate-600 bg-slate-950 text-orange-500">
                                    Preview
                                </label>
                            </td>
                            <td>
                                <input type="hidden" name="mappings[{{ $mapping->id }}][whatsapp_enabled]" value="0">
                                <label class="inline-flex items-center gap-2 text-xs font-extrabold">
                                    <input type="checkbox" name="mappings[{{ $mapping->id }}][whatsapp_enabled]" value="1" @checked(old("mappings.{$mapping->id}.whatsapp_enabled", $mapping->whatsapp_enabled)) class="rounded border-slate-600 bg-slate-950 text-orange-500">
                                    Enabled
                                </label>
                                <div class="mt-2 sf-badge-slate">Not live yet</div>
                            </td>
                            <td>
                                <input name="mappings[{{ $mapping->id }}][whatsapp_template_name]" value="{{ old("mappings.{$mapping->id}.whatsapp_template_name", $mapping->whatsapp_template_name) }}" class="sf-journey-input">
                            </td>
                            <td>
                                <input name="mappings[{{ $mapping->id }}][followup_template_name]" value="{{ old("mappings.{$mapping->id}.followup_template_name", $mapping->followup_template_name) }}" class="sf-journey-input">
                            </td>
                            <td>
                                <textarea name="mappings[{{ $mapping->id }}][notes]" class="sf-journey-textarea">{{ old("mappings.{$mapping->id}.notes", $mapping->notes) }}</textarea>
                            </td>
                            <td class="text-right">
                                <button type="submit" name="save_row" value="{{ $mapping->id }}" class="sf-btn-row">
                                    Save Row
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </form>
</div>
@endsection
