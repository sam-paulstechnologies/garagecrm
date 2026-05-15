@extends('layouts.app')

@section('title', $pageTitle ?? 'Leads')

@section('content')
@php
    $pageMode = $pageMode ?? 'open';
    $bucket = $bucket ?? '';
    $q = $q ?? request('q');

    $cardClass = function ($active) {
        return $active
            ? 'border-orange-400/30 bg-orange-500/10 text-white ring-1 ring-orange-400/20'
            : 'border-white/10 bg-slate-900/80 text-slate-200 hover:border-orange-400/30 hover:bg-slate-900';
    };

    $bucketCardClass = function ($active) {
        return $active
            ? 'border-orange-400/40 bg-orange-500/10 text-white ring-1 ring-orange-400/30'
            : 'border-white/10 bg-slate-950/60 text-slate-200 hover:border-orange-400/30 hover:bg-slate-900';
    };

    $scoreBadge = function ($score) {
        if ($score >= 75) {
            return 'bg-green-500/10 text-green-300 ring-green-400/20';
        }

        if ($score >= 45) {
            return 'bg-yellow-500/10 text-yellow-300 ring-yellow-400/20';
        }

        return 'bg-white/5 text-slate-300 ring-white/10';
    };

    $statusBadge = function ($status) {
        $status = strtolower((string) $status);

        return match ($status) {
            'new' => 'bg-blue-500/10 text-blue-300 ring-blue-400/20',
            'attempting_contact' => 'bg-yellow-500/10 text-yellow-300 ring-yellow-400/20',
            'contact_on_hold' => 'bg-orange-500/10 text-orange-300 ring-orange-400/20',
            'qualified' => 'bg-green-500/10 text-green-300 ring-green-400/20',
            'converted' => 'bg-emerald-500/10 text-emerald-300 ring-emerald-400/20',
            'disqualified', 'lost' => 'bg-red-500/10 text-red-300 ring-red-400/20',
            default => 'bg-white/5 text-slate-300 ring-white/10',
        };
    };

    $categoryBadge = function ($category) {
        $category = strtolower((string) $category);

        return match ($category) {
            'service' => 'bg-blue-500/10 text-blue-300 ring-blue-400/20',
            'quote' => 'bg-purple-500/10 text-purple-300 ring-purple-400/20',
            'complaint' => 'bg-red-500/10 text-red-300 ring-red-400/20',
            'emergency' => 'bg-orange-500/10 text-orange-300 ring-orange-400/20',
            'repair' => 'bg-yellow-500/10 text-yellow-300 ring-yellow-400/20',
            'enquiry' => 'bg-white/5 text-slate-300 ring-white/10',
            default => 'bg-white/5 text-slate-300 ring-white/10',
        };
    };

    $priorityBadge = function ($priority) {
        $priority = strtolower((string) $priority);

        return match ($priority) {
            'urgent' => 'bg-red-500/10 text-red-300 ring-red-400/20',
            'high' => 'bg-orange-500/10 text-orange-300 ring-orange-400/20',
            'medium' => 'bg-yellow-500/10 text-yellow-300 ring-yellow-400/20',
            'low' => 'bg-white/5 text-slate-300 ring-white/10',
            default => 'bg-white/5 text-slate-300 ring-white/10',
        };
    };

    $temperatureBadge = function ($temperature) {
        $temperature = strtolower((string) $temperature);

        return match ($temperature) {
            'hot' => 'bg-red-500/10 text-red-300 ring-red-400/20',
            'warm' => 'bg-yellow-500/10 text-yellow-300 ring-yellow-400/20',
            'cold' => 'bg-white/5 text-slate-300 ring-white/10',
            default => 'bg-white/5 text-slate-300 ring-white/10',
        };
    };

    $whatsappStatus = function ($lead, $log) {
        if (! $lead->phone && ! $lead->phone_norm) {
            return [
                'label' => 'No phone',
                'class' => 'bg-white/5 text-slate-300 ring-white/10',
            ];
        }

        if (! $log) {
            return [
                'label' => 'Not contacted',
                'class' => 'bg-yellow-500/10 text-yellow-300 ring-yellow-400/20',
            ];
        }

        if (in_array($log->provider_status, ['failed', 'undelivered', 'error'])) {
            return [
                'label' => 'Message failed',
                'class' => 'bg-red-500/10 text-red-300 ring-red-400/20',
            ];
        }

        if ($log->direction === 'in') {
            return [
                'label' => 'Customer replied',
                'class' => 'bg-green-500/10 text-green-300 ring-green-400/20',
            ];
        }

        if (in_array($log->provider_status, ['delivered', 'read'])) {
            return [
                'label' => ucfirst($log->provider_status),
                'class' => 'bg-green-500/10 text-green-300 ring-green-400/20',
            ];
        }

        if (in_array($log->provider_status, ['queued', 'sent'])) {
            return [
                'label' => ucfirst($log->provider_status),
                'class' => 'bg-blue-500/10 text-blue-300 ring-blue-400/20',
            ];
        }

        return [
            'label' => 'Phone available',
            'class' => 'bg-white/5 text-slate-300 ring-white/10',
        ];
    };

    $badgeBase = 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold ring-1';

    $bucketCards = [
        [
            'key' => 'service',
            'title' => 'Service Requests',
            'count' => $bucketCounts['service'] ?? 0,
            'note' => 'Service enquiries',
            'emoji' => '🔧',
        ],
        [
            'key' => 'quote_followup',
            'title' => 'Quote Follow-up',
            'count' => $bucketCounts['quote_followup'] ?? 0,
            'note' => 'Quote + follow-up',
            'emoji' => '💬',
        ],
        [
            'key' => 'complaints',
            'title' => 'Complaints',
            'count' => $bucketCounts['complaints'] ?? 0,
            'note' => 'Needs attention',
            'emoji' => '⚠️',
        ],
        [
            'key' => 'hot',
            'title' => 'Hot Leads',
            'count' => $bucketCounts['hot'] ?? 0,
            'note' => 'High intent',
            'emoji' => '🔥',
        ],
        [
            'key' => 'high_priority',
            'title' => 'High Priority',
            'count' => $bucketCounts['high_priority'] ?? 0,
            'note' => 'High / urgent',
            'emoji' => '🚨',
        ],
        [
            'key' => 'followup_due',
            'title' => 'Follow-up Due',
            'count' => $bucketCounts['followup_due'] ?? 0,
            'note' => 'Due today/overdue',
            'emoji' => '📅',
        ],
        [
            'key' => 'service_due',
            'title' => 'Service Due',
            'count' => $bucketCounts['service_due'] ?? 0,
            'note' => 'Retention bucket',
            'emoji' => '🔁',
        ],
        [
            'key' => 'fleet_corporate',
            'title' => 'Fleet / Corporate',
            'count' => $bucketCounts['fleet_corporate'] ?? 0,
            'note' => 'B2B leads',
            'emoji' => '🏢',
        ],
    ];
@endphp

<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Lead Command Center
            </div>

            <h1 class="sf-page-title mt-3">
                {{ $pageTitle ?? 'Leads' }}
            </h1>

            <p class="sf-page-subtitle">
                {{ $pageSubtitle ?? 'Manage leads, qualification flow, follow-ups, WhatsApp status, and lead buckets.' }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.leads.create') }}" class="sf-btn-primary">
                + Add Lead
            </a>

            <a href="{{ route('admin.leads.import.options') }}" class="sf-btn-secondary">
                Import
            </a>
        </div>
    </div>

    {{-- Lead Command Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <a href="{{ route('admin.leads.index') }}"
           class="rounded-3xl border p-5 shadow-xl shadow-black/20 transition {{ $cardClass($pageMode === 'open' && blank($bucket)) }}">
            <div class="text-sm font-bold text-slate-400">Open Leads</div>
            <div class="mt-2 text-3xl font-extrabold text-white">{{ $leadCounts['open'] ?? 0 }}</div>
            <div class="mt-1 text-xs font-medium text-slate-500">Needs action</div>
        </a>

        <a href="{{ route('admin.leads.qualified') }}"
           class="rounded-3xl border p-5 shadow-xl shadow-black/20 transition {{ $cardClass($pageMode === 'qualified') }}">
            <div class="text-sm font-bold text-slate-400">Qualified / Converted</div>
            <div class="mt-2 text-3xl font-extrabold text-white">{{ $leadCounts['qualified'] ?? 0 }}</div>
            <div class="mt-1 text-xs font-medium text-slate-500">Won or moved ahead</div>
        </a>

        <a href="{{ route('admin.leads.disqualified') }}"
           class="rounded-3xl border p-5 shadow-xl shadow-black/20 transition {{ $cardClass($pageMode === 'disqualified') }}">
            <div class="text-sm font-bold text-slate-400">Disqualified</div>
            <div class="mt-2 text-3xl font-extrabold text-white">{{ $leadCounts['disqualified'] ?? 0 }}</div>
            <div class="mt-1 text-xs font-medium text-slate-500">Invalid / lost leads</div>
        </a>

        <a href="{{ route('admin.leads.duplicates.index') }}"
           class="rounded-3xl border border-yellow-400/20 bg-yellow-500/10 p-5 text-yellow-200 shadow-xl shadow-black/20 transition hover:border-yellow-400/40 hover:bg-yellow-500/20">
            <div class="text-sm font-bold text-yellow-300">Duplicates</div>
            <div class="mt-2 text-3xl font-extrabold text-white">{{ $leadCounts['duplicates'] ?? 0 }}</div>
            <div class="mt-1 text-xs font-medium text-yellow-200">Review same numbers</div>
        </a>

        <a href="{{ route('admin.leads.import.options') }}"
           class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-5 text-blue-200 shadow-xl shadow-black/20 transition hover:border-blue-400/40 hover:bg-blue-500/20">
            <div class="text-sm font-bold text-blue-300">Import</div>
            <div class="mt-2 text-3xl font-extrabold text-white">⬆</div>
            <div class="mt-1 text-xs font-medium text-blue-200">Bulk upload leads</div>
        </a>
    </div>

    {{-- Bucket Cards --}}
    @if($pageMode === 'open')
        <div class="sf-card">
            <div class="sf-card-header flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="sf-section-title">Lead Buckets</h2>
                    <p class="sf-section-subtitle">
                        Quick filters for categorization, retention, and follow-up.
                    </p>
                </div>

                @if(! blank($bucket))
                    <a href="{{ route('admin.leads.index') }}" class="sf-link">
                        Clear bucket filter
                    </a>
                @endif
            </div>

            <div class="sf-card-body">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
                    @foreach($bucketCards as $bucketCard)
                        <a href="{{ route('admin.leads.index', ['bucket' => $bucketCard['key'], 'q' => $q]) }}"
                           class="block rounded-2xl border p-4 transition {{ $bucketCardClass($bucket === $bucketCard['key']) }}">
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-xl">{{ $bucketCard['emoji'] }}</div>
                                <div class="text-2xl font-extrabold text-white">{{ $bucketCard['count'] }}</div>
                            </div>

                            <div class="mt-3 text-sm font-extrabold leading-tight text-white">
                                {{ $bucketCard['title'] }}
                            </div>

                            <div class="mt-1 text-xs font-medium text-slate-500">
                                {{ $bucketCard['note'] }}
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Search --}}
    <form method="GET" class="sf-card">
        <div class="sf-card-body">
            <div class="flex flex-col gap-2 lg:flex-row">
                @if(! blank($bucket))
                    <input type="hidden" name="bucket" value="{{ $bucket }}">
                @endif

                <input
                    name="q"
                    value="{{ $q }}"
                    placeholder="Search name, phone, email, source, category, vehicle, campaign..."
                    class="sf-input lg:flex-1"
                />

                <button type="submit" class="sf-btn-primary">
                    Search
                </button>

                @if(! blank($q) || ! blank($bucket))
                    <a href="{{ route('admin.leads.index') }}" class="sf-btn-secondary">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="sf-table-wrap">
        <div class="sf-table-scroll">
            <table class="sf-table">
                <thead>
                    <tr>
                        <th class="w-[24%]">Lead</th>
                        <th class="w-[18%]">Request</th>
                        <th class="w-[16%]">Vehicle</th>
                        <th class="w-[14%]">Score / Priority</th>
                        <th class="w-[12%]">Follow-up</th>
                        <th class="w-[12%]">WhatsApp / Status</th>
                        <th class="w-[4%] text-right">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($leads as $lead)
                        @php
                            $score = $leadScores[$lead->id] ?? 0;
                            $log = $whatsappByLead[$lead->id] ?? null;
                            $wa = $whatsappStatus($lead, $log);

                            $vehicleText = trim(
                                ($lead->vehicle_make ?? $lead->other_make ?? '') . ' ' .
                                ($lead->vehicle_model ?? $lead->other_model ?? '')
                            );

                            if ($vehicleText === '') {
                                $vehicleText = $lead->vehicle_label ?? '';
                            }
                        @endphp

                        <tr>
                            {{-- Lead --}}
                            <td>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('admin.leads.show', $lead) }}"
                                           class="font-extrabold text-white hover:text-orange-300">
                                            {{ $lead->name ?? 'Unnamed Lead' }}
                                        </a>

                                        @if((bool) ($lead->is_hot ?? false))
                                            <span class="{{ $badgeBase }} bg-red-500/10 text-red-300 ring-red-400/20">
                                                🔥
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-1 truncate text-xs font-medium text-slate-500">
                                        {{ $lead->email ?? 'No email' }}
                                    </div>

                                    <div class="mt-1 text-sm font-bold text-slate-300">
                                        {{ $lead->phone ?? $lead->phone_norm ?? 'No phone' }}
                                    </div>
                                </div>
                            </td>

                            {{-- Request --}}
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @if($lead->service_category)
                                        <span class="{{ $badgeBase }} {{ $categoryBadge($lead->service_category) }}">
                                            {{ ucfirst(str_replace('_', ' ', $lead->service_category)) }}
                                        </span>
                                    @else
                                        <span class="text-slate-600">—</span>
                                    @endif
                                </div>

                                @if($lead->service_type)
                                    <div class="mt-1 truncate text-xs text-slate-500">
                                        {{ ucfirst($lead->service_type) }}
                                    </div>
                                @endif

                                @if($lead->retention_tag)
                                    <div class="mt-1 truncate text-xs font-bold text-orange-300">
                                        {{ str_replace('_', ' ', $lead->retention_tag) }}
                                    </div>
                                @endif

                                <div class="mt-1 truncate text-xs text-slate-500">
                                    {{ $lead->source ?? 'No source' }}
                                    @if($lead->campaign_name)
                                        · {{ $lead->campaign_name }}
                                    @elseif($lead->leadSource)
                                        · {{ $lead->leadSource->name }}
                                    @endif
                                </div>
                            </td>

                            {{-- Vehicle --}}
                            <td>
                                @if($vehicleText)
                                    <div class="font-bold text-slate-200">
                                        {{ $vehicleText }}
                                    </div>

                                    <div class="mt-1 truncate text-xs text-slate-500">
                                        {{ $lead->vehicle_year ?? '' }}

                                        @if($lead->plate_number)
                                            · {{ $lead->plate_number }}
                                        @endif
                                    </div>
                                @else
                                    <span class="text-slate-600">—</span>
                                @endif
                            </td>

                            {{-- Score / Priority --}}
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    <span class="{{ $badgeBase }} {{ $scoreBadge($score) }}">
                                        {{ $score }}/100
                                    </span>
                                </div>

                                <div class="mt-2 flex flex-wrap gap-1">
                                    @if($lead->lead_temperature)
                                        <span class="{{ $badgeBase }} {{ $temperatureBadge($lead->lead_temperature) }}">
                                            {{ ucfirst($lead->lead_temperature) }}
                                        </span>
                                    @endif

                                    @if($lead->lead_priority)
                                        <span class="{{ $badgeBase }} {{ $priorityBadge($lead->lead_priority) }}">
                                            {{ ucfirst($lead->lead_priority) }}
                                        </span>
                                    @endif

                                    @if(! $lead->lead_temperature && ! $lead->lead_priority)
                                        <span class="text-slate-600">—</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Follow-up --}}
                            <td>
                                @if($lead->follow_up_required)
                                    <div class="text-xs font-extrabold text-white">
                                        Required
                                    </div>

                                    <div class="mt-1 text-xs font-bold {{ $lead->follow_up_date && \Carbon\Carbon::parse($lead->follow_up_date)->isPast() ? 'text-red-300' : 'text-orange-300' }}">
                                        {{ optional($lead->follow_up_date)->format('d M Y') ?? 'No date' }}
                                    </div>
                                @else
                                    <span class="text-slate-600">—</span>
                                @endif
                            </td>

                            {{-- WhatsApp / Status --}}
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    <span class="{{ $badgeBase }} {{ $wa['class'] }}">
                                        {{ $wa['label'] }}
                                    </span>
                                </div>

                                <div class="mt-2 flex flex-wrap gap-1">
                                    <span class="{{ $badgeBase }} {{ $statusBadge($lead->status) }}">
                                        {{ ucfirst(str_replace('_',' ', $lead->status)) }}
                                    </span>
                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    {{ optional($lead->created_at)->format('d M Y') }}
                                </div>
                            </td>

                            {{-- Action --}}
                            <td class="text-right">
                                <a href="{{ route('admin.leads.show', $lead) }}" class="sf-link">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="sf-empty">
                                    @if($pageMode === 'open' && blank($bucket))
                                        🎉 No open leads need action right now.
                                    @elseif($pageMode === 'open' && ! blank($bucket))
                                        No leads found in this bucket.
                                    @elseif($pageMode === 'qualified')
                                        No qualified or converted leads found.
                                    @elseif($pageMode === 'disqualified')
                                        No disqualified leads found.
                                    @else
                                        No leads found.
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="text-slate-300">
        {{ $leads->links() }}
    </div>

</div>
@endsection