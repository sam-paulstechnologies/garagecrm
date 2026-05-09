@extends('layouts.app')

@section('content')
@php
    $pageMode = $pageMode ?? 'open';
    $bucket = $bucket ?? '';

    $cardClass = function ($active) {
        return $active
            ? 'border-indigo-200 bg-indigo-50 text-indigo-900'
            : 'border-gray-100 bg-white text-gray-900 hover:bg-gray-50';
    };

    $bucketCardClass = function ($active) {
        return $active
            ? 'border-blue-300 bg-blue-50 text-blue-900 ring-1 ring-blue-200'
            : 'border-gray-100 bg-white text-gray-900 hover:bg-gray-50';
    };

    $scoreBadge = function ($score) {
        if ($score >= 75) {
            return 'bg-green-100 text-green-800';
        }

        if ($score >= 45) {
            return 'bg-yellow-100 text-yellow-800';
        }

        return 'bg-gray-100 text-gray-700';
    };

    $statusBadge = function ($status) {
        $status = strtolower((string) $status);

        return match ($status) {
            'new' => 'bg-blue-100 text-blue-800',
            'attempting_contact' => 'bg-yellow-100 text-yellow-800',
            'contact_on_hold' => 'bg-orange-100 text-orange-800',
            'qualified' => 'bg-green-100 text-green-800',
            'converted' => 'bg-emerald-100 text-emerald-800',
            'disqualified', 'lost' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-700',
        };
    };

    $categoryBadge = function ($category) {
        $category = strtolower((string) $category);

        return match ($category) {
            'service' => 'bg-blue-100 text-blue-800',
            'quote' => 'bg-purple-100 text-purple-800',
            'complaint' => 'bg-red-100 text-red-800',
            'emergency' => 'bg-orange-100 text-orange-800',
            'repair' => 'bg-yellow-100 text-yellow-800',
            'enquiry' => 'bg-gray-100 text-gray-700',
            default => 'bg-gray-100 text-gray-700',
        };
    };

    $priorityBadge = function ($priority) {
        $priority = strtolower((string) $priority);

        return match ($priority) {
            'urgent' => 'bg-red-100 text-red-800',
            'high' => 'bg-orange-100 text-orange-800',
            'medium' => 'bg-yellow-100 text-yellow-800',
            'low' => 'bg-gray-100 text-gray-700',
            default => 'bg-gray-100 text-gray-700',
        };
    };

    $temperatureBadge = function ($temperature) {
        $temperature = strtolower((string) $temperature);

        return match ($temperature) {
            'hot' => 'bg-red-100 text-red-800',
            'warm' => 'bg-yellow-100 text-yellow-800',
            'cold' => 'bg-gray-100 text-gray-700',
            default => 'bg-gray-100 text-gray-700',
        };
    };

    $whatsappStatus = function ($lead, $log) {
        if (! $lead->phone && ! $lead->phone_norm) {
            return [
                'label' => 'No phone',
                'class' => 'bg-gray-100 text-gray-700',
            ];
        }

        if (! $log) {
            return [
                'label' => 'Not contacted',
                'class' => 'bg-yellow-100 text-yellow-800',
            ];
        }

        if (in_array($log->provider_status, ['failed', 'undelivered', 'error'])) {
            return [
                'label' => 'Message failed',
                'class' => 'bg-red-100 text-red-800',
            ];
        }

        if ($log->direction === 'in') {
            return [
                'label' => 'Customer replied',
                'class' => 'bg-green-100 text-green-800',
            ];
        }

        if (in_array($log->provider_status, ['delivered', 'read'])) {
            return [
                'label' => ucfirst($log->provider_status),
                'class' => 'bg-green-100 text-green-800',
            ];
        }

        if (in_array($log->provider_status, ['queued', 'sent'])) {
            return [
                'label' => ucfirst($log->provider_status),
                'class' => 'bg-blue-100 text-blue-800',
            ];
        }

        return [
            'label' => 'Phone available',
            'class' => 'bg-gray-100 text-gray-700',
        ];
    };

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

<div class="px-6 py-6 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">
                {{ $pageTitle ?? 'Leads' }}
            </h1>
            <p class="text-sm text-gray-500">
                {{ $pageSubtitle ?? 'Manage leads and qualification flow.' }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.leads.create') }}"
               class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm shadow">
                + Add Lead
            </a>

            <a href="{{ route('admin.leads.import.options') }}"
               class="inline-flex items-center gap-2 bg-gray-900 hover:bg-gray-800 text-white px-4 py-2 rounded-md text-sm shadow">
                Import
            </a>
        </div>
    </div>

    {{-- Lead Command Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
        <a href="{{ route('admin.leads.index') }}"
           class="rounded-xl border p-5 block transition {{ $cardClass($pageMode === 'open' && blank($bucket)) }}">
            <div class="text-sm font-medium text-gray-500">Open Leads</div>
            <div class="text-3xl font-bold mt-2">{{ $leadCounts['open'] ?? 0 }}</div>
            <div class="text-xs text-gray-500 mt-1">Needs action</div>
        </a>

        <a href="{{ route('admin.leads.qualified') }}"
           class="rounded-xl border p-5 block transition {{ $cardClass($pageMode === 'qualified') }}">
            <div class="text-sm font-medium text-gray-500">Qualified / Converted</div>
            <div class="text-3xl font-bold mt-2">{{ $leadCounts['qualified'] ?? 0 }}</div>
            <div class="text-xs text-gray-500 mt-1">Won or moved ahead</div>
        </a>

        <a href="{{ route('admin.leads.disqualified') }}"
           class="rounded-xl border p-5 block transition {{ $cardClass($pageMode === 'disqualified') }}">
            <div class="text-sm font-medium text-gray-500">Disqualified</div>
            <div class="text-3xl font-bold mt-2">{{ $leadCounts['disqualified'] ?? 0 }}</div>
            <div class="text-xs text-gray-500 mt-1">Invalid/lost leads</div>
        </a>

        <a href="{{ route('admin.leads.duplicates.index') }}"
           class="rounded-xl border border-amber-100 bg-amber-50 p-5 block hover:bg-amber-100 transition">
            <div class="text-sm font-medium text-amber-700">Duplicates</div>
            <div class="text-3xl font-bold text-amber-900 mt-2">{{ $leadCounts['duplicates'] ?? 0 }}</div>
            <div class="text-xs text-amber-700 mt-1">Review same numbers</div>
        </a>

        <a href="{{ route('admin.leads.import.options') }}"
           class="rounded-xl border border-blue-100 bg-blue-50 p-5 block hover:bg-blue-100 transition">
            <div class="text-sm font-medium text-blue-700">Import</div>
            <div class="text-3xl font-bold text-blue-900 mt-2">⬆</div>
            <div class="text-xs text-blue-700 mt-1">Bulk upload leads</div>
        </a>
    </div>

    {{-- Bucket Cards --}}
    @if($pageMode === 'open')
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Lead Buckets</h2>
                    <p class="text-sm text-gray-500">
                        Quick filters for categorization, retention, and follow-up.
                    </p>
                </div>

                @if(! blank($bucket))
                    <a href="{{ route('admin.leads.index') }}"
                       class="text-sm text-blue-600 hover:underline">
                        Clear bucket filter
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8 gap-3">
                @foreach($bucketCards as $bucketCard)
                    <a href="{{ route('admin.leads.index', ['bucket' => $bucketCard['key'], 'q' => $q]) }}"
                       class="rounded-xl border p-4 block transition {{ $bucketCardClass($bucket === $bucketCard['key']) }}">
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-xl">{{ $bucketCard['emoji'] }}</div>
                            <div class="text-2xl font-bold">{{ $bucketCard['count'] }}</div>
                        </div>

                        <div class="text-sm font-semibold mt-3 leading-tight">
                            {{ $bucketCard['title'] }}
                        </div>

                        <div class="text-xs text-gray-500 mt-1">
                            {{ $bucketCard['note'] }}
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Search --}}
    <form method="GET" class="max-w-2xl">
        <div class="flex flex-col sm:flex-row gap-2">
            @if(! blank($bucket))
                <input type="hidden" name="bucket" value="{{ $bucket }}">
            @endif

            <input
                name="q"
                value="{{ $q }}"
                placeholder="Search name, phone, email, source, category, vehicle, campaign..."
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
            />

            <button type="submit"
                    class="px-4 py-2 rounded-md bg-gray-900 text-white text-sm">
                Search
            </button>

            @if(! blank($q) || ! blank($bucket))
                <a href="{{ route('admin.leads.index') }}"
                   class="px-4 py-2 rounded-md bg-gray-100 text-gray-700 text-sm text-center hover:bg-gray-200">
                    Reset
                </a>
            @endif
        </div>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Lead</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Phone</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Bucket</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Vehicle</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Score</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Priority</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Follow-up</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">WhatsApp</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Source</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Created</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y">
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

                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 min-w-[220px]">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.leads.show', $lead) }}"
                                   class="text-blue-600 font-medium hover:underline">
                                    {{ $lead->name ?? 'Unnamed Lead' }}
                                </a>

                                @if((bool) ($lead->is_hot ?? false))
                                    <span class="px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-700">
                                        🔥 Hot
                                    </span>
                                @endif
                            </div>

                            <div class="text-xs text-gray-500 mt-1">
                                {{ $lead->email ?? 'No email' }}
                            </div>
                        </td>

                        <td class="px-4 py-3 text-gray-700 whitespace-nowrap">
                            {{ $lead->phone ?? $lead->phone_norm ?? '—' }}
                        </td>

                        <td class="px-4 py-3 min-w-[190px]">
                            @if($lead->service_category)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $categoryBadge($lead->service_category) }}">
                                    {{ ucfirst(str_replace('_', ' ', $lead->service_category)) }}
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif

                            @if($lead->service_type)
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ ucfirst($lead->service_type) }}
                                </div>
                            @endif

                            @if($lead->retention_tag)
                                <div class="text-xs text-blue-600 mt-1">
                                    {{ str_replace('_', ' ', $lead->retention_tag) }}
                                </div>
                            @endif
                        </td>

                        <td class="px-4 py-3 min-w-[160px] text-gray-700">
                            @if($vehicleText)
                                <div>{{ $vehicleText }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ $lead->vehicle_year ?? '' }}
                                    @if($lead->plate_number)
                                        · {{ $lead->plate_number }}
                                    @endif
                                </div>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $scoreBadge($score) }}">
                                {{ $score }}/100
                            </span>
                        </td>

                        <td class="px-4 py-3 min-w-[130px]">
                            @if($lead->lead_temperature)
                                <span class="px-2 py-0.5 rounded-full text-xs {{ $temperatureBadge($lead->lead_temperature) }}">
                                    {{ ucfirst($lead->lead_temperature) }}
                                </span>
                            @endif

                            @if($lead->lead_priority)
                                <span class="px-2 py-0.5 rounded-full text-xs {{ $priorityBadge($lead->lead_priority) }}">
                                    {{ ucfirst($lead->lead_priority) }}
                                </span>
                            @endif

                            @if(! $lead->lead_temperature && ! $lead->lead_priority)
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        <td class="px-4 py-3 min-w-[140px]">
                            @if($lead->follow_up_required)
                                <div class="text-xs font-medium text-gray-900">
                                    Required
                                </div>

                                <div class="text-xs {{ $lead->follow_up_date && \Carbon\Carbon::parse($lead->follow_up_date)->isPast() ? 'text-red-600' : 'text-gray-500' }}">
                                    {{ optional($lead->follow_up_date)->format('d M Y') ?? 'No date' }}
                                </div>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $wa['class'] }}">
                                {{ $wa['label'] }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $statusBadge($lead->status) }}">
                                {{ ucfirst(str_replace('_',' ', $lead->status)) }}
                            </span>
                        </td>

                        <td class="px-4 py-3 text-gray-600">
                            <div>{{ $lead->source ?? '—' }}</div>

                            @if($lead->campaign_name)
                                <div class="text-xs text-gray-400">
                                    {{ $lead->campaign_name }}
                                </div>
                            @elseif($lead->leadSource)
                                <div class="text-xs text-gray-400">
                                    {{ $lead->leadSource->name }}
                                </div>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                            {{ optional($lead->created_at)->format('d M Y') }}
                        </td>

                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.leads.show', $lead) }}"
                               class="text-blue-600 hover:underline text-sm">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="py-10 text-center text-gray-400">
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
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div>
        {{ $leads->links() }}
    </div>

</div>
@endsection