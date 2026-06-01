{{-- resources/views/admin/leads/index-partials/_stats.blade.php --}}

@php
    $pageMode = $pageMode ?? 'open';
    $bucket = $bucket ?? '';

    $cardClass = function ($active) {
        return $active
            ? 'border-orange-400/40 bg-orange-500/10 ring-1 ring-orange-400/25'
            : 'sf-leads-panel hover:border-orange-400/35';
    };
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
    <a href="{{ route('admin.leads.index') }}"
       class="rounded-2xl border p-5 shadow-sm transition {{ $cardClass($pageMode === 'open' && blank($bucket)) }}">
        <div class="sf-leads-muted text-sm font-bold">Open Leads</div>
        <div class="sf-leads-value mt-2 text-3xl font-extrabold">{{ $leadCounts['open'] ?? 0 }}</div>
        <div class="sf-leads-muted mt-1 text-xs font-medium">Needs action</div>
    </a>

    <a href="{{ route('admin.leads.qualified') }}"
       class="rounded-2xl border p-5 shadow-sm transition {{ $cardClass($pageMode === 'qualified') }}">
        <div class="sf-leads-muted text-sm font-bold">Qualified / Converted</div>
        <div class="sf-leads-value mt-2 text-3xl font-extrabold">{{ $leadCounts['qualified'] ?? 0 }}</div>
        <div class="sf-leads-muted mt-1 text-xs font-medium">Won or moved ahead</div>
    </a>

    <a href="{{ route('admin.leads.disqualified') }}"
       class="rounded-2xl border p-5 shadow-sm transition {{ $cardClass($pageMode === 'disqualified') }}">
        <div class="sf-leads-muted text-sm font-bold">Disqualified</div>
        <div class="sf-leads-value mt-2 text-3xl font-extrabold">{{ $leadCounts['disqualified'] ?? 0 }}</div>
        <div class="sf-leads-muted mt-1 text-xs font-medium">Invalid / lost leads</div>
    </a>

    <a href="{{ route('admin.leads.duplicates.index') }}"
       class="rounded-2xl border border-yellow-400/25 bg-yellow-500/10 p-5 text-yellow-200 shadow-sm transition hover:border-yellow-400/45">
        <div class="sf-leads-accent-title text-sm font-bold">Duplicates</div>
        <div class="sf-leads-accent-value mt-2 text-3xl font-extrabold">{{ $leadCounts['duplicates'] ?? 0 }}</div>
        <div class="sf-leads-accent-muted mt-1 text-xs font-medium">Review same numbers</div>
    </a>

    <a href="{{ route('admin.leads.import.options') }}"
       class="rounded-2xl border border-blue-400/25 bg-blue-500/10 p-5 text-blue-200 shadow-sm transition hover:border-blue-400/45">
        <div class="sf-leads-accent-title text-sm font-bold">Import</div>
        <div class="sf-leads-accent-value mt-2 text-3xl font-extrabold">Upload</div>
        <div class="sf-leads-accent-muted mt-1 text-xs font-medium">Bulk upload leads</div>
    </a>
</div>
