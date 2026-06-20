{{-- resources/views/admin/leads/index-partials/_table.blade.php --}}

@php
    $scoreBadge = function ($score) {
        if ($score >= 75) {
            return 'bg-green-500/10 text-green-300 ring-green-400/20';
        }

        if ($score >= 45) {
            return 'bg-yellow-500/10 text-yellow-300 ring-yellow-400/20';
        }

        return 'bg-slate-500/10 text-slate-300 ring-slate-400/20';
    };

    $statusBadge = function ($status) {
        return match (strtolower((string) $status)) {
            'new' => 'bg-blue-500/10 text-blue-300 ring-blue-400/20',
            'attempting_contact' => 'bg-yellow-500/10 text-yellow-300 ring-yellow-400/20',
            'contact_on_hold' => 'bg-orange-500/10 text-orange-300 ring-orange-400/20',
            'qualified', 'converted' => 'bg-green-500/10 text-green-300 ring-green-400/20',
            'disqualified', 'lost' => 'bg-red-500/10 text-red-300 ring-red-400/20',
            default => 'bg-slate-500/10 text-slate-300 ring-slate-400/20',
        };
    };

    $categoryBadge = function ($category) {
        return match (strtolower((string) $category)) {
            'service' => 'bg-blue-500/10 text-blue-300 ring-blue-400/20',
            'quote' => 'bg-purple-500/10 text-purple-300 ring-purple-400/20',
            'complaint' => 'bg-red-500/10 text-red-300 ring-red-400/20',
            'emergency' => 'bg-orange-500/10 text-orange-300 ring-orange-400/20',
            'repair' => 'bg-yellow-500/10 text-yellow-300 ring-yellow-400/20',
            default => 'bg-slate-500/10 text-slate-300 ring-slate-400/20',
        };
    };

    $priorityBadge = function ($priority) {
        return match (strtolower((string) $priority)) {
            'urgent' => 'bg-red-500/10 text-red-300 ring-red-400/20',
            'high' => 'bg-orange-500/10 text-orange-300 ring-orange-400/20',
            'medium' => 'bg-yellow-500/10 text-yellow-300 ring-yellow-400/20',
            default => 'bg-slate-500/10 text-slate-300 ring-slate-400/20',
        };
    };

    $temperatureBadge = function ($temperature) {
        return match (strtolower((string) $temperature)) {
            'hot' => 'bg-red-500/10 text-red-300 ring-red-400/20',
            'warm' => 'bg-yellow-500/10 text-yellow-300 ring-yellow-400/20',
            default => 'bg-slate-500/10 text-slate-300 ring-slate-400/20',
        };
    };

    $whatsappStatus = function ($lead, $log) {
        if (! $lead->phone && ! $lead->phone_norm) {
            return ['label' => 'No phone', 'class' => 'bg-slate-500/10 text-slate-300 ring-slate-400/20'];
        }

        if (! $log) {
            return ['label' => 'Not contacted', 'class' => 'bg-yellow-500/10 text-yellow-300 ring-yellow-400/20'];
        }

        if (in_array($log->provider_status, ['failed', 'undelivered', 'error'], true)) {
            return ['label' => 'Message failed', 'class' => 'bg-red-500/10 text-red-300 ring-red-400/20'];
        }

        if ($log->direction === 'in') {
            return ['label' => 'Customer replied', 'class' => 'bg-green-500/10 text-green-300 ring-green-400/20'];
        }

        if (in_array($log->provider_status, ['delivered', 'read'], true)) {
            return ['label' => ucfirst($log->provider_status), 'class' => 'bg-green-500/10 text-green-300 ring-green-400/20'];
        }

        if (in_array($log->provider_status, ['queued', 'sent'], true)) {
            return ['label' => ucfirst($log->provider_status), 'class' => 'bg-blue-500/10 text-blue-300 ring-blue-400/20'];
        }

        return ['label' => 'Phone available', 'class' => 'bg-slate-500/10 text-slate-300 ring-slate-400/20'];
    };

    $badgeBase = 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold ring-1';
    $phoneService = app(\App\Services\PhoneNumberService::class);
@endphp

<div class="sf-leads-panel overflow-hidden rounded-2xl border shadow-sm">
    <div class="overflow-x-auto">
        <table class="sf-leads-table min-w-full divide-y divide-slate-800 text-sm">
            <thead>
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Lead</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Request</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Vehicle</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Score / Priority</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide">Follow-up</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase tracking-wide">WhatsApp / Status</th>
                    <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-wide">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-800">
                @forelse($leads as $lead)
                    @php
                        $score = $leadScores[$lead->id] ?? 0;
                        $log = $whatsappByLead[$lead->id] ?? null;
                        $wa = $whatsappStatus($lead, $log);
                        $vehicleText = trim(($lead->vehicle_make ?? $lead->other_make ?? '') . ' ' . ($lead->vehicle_model ?? $lead->other_model ?? ''));
                        $leadPhone = $lead->phone ?? $lead->phone_norm;
                        $leadPhoneDisplay = $leadPhone ? $phoneService->formatForDisplay($leadPhone) : null;
                        $leadTelUrl = $leadPhone ? $phoneService->buildTelUrl($leadPhone) : null;
                        $leadWhatsappKey = $leadPhone ? $phoneService->buildWhatsappLookupKey($leadPhone) : null;
                        $leadWhatsappUrl = $leadWhatsappKey && \Illuminate\Support\Facades\Route::has('admin.inbox.index')
                            ? route('admin.inbox.index', ['search' => $leadWhatsappKey])
                            : null;

                        if ($vehicleText === '') {
                            $vehicleText = $lead->vehicle_label ?? '';
                        }
                    @endphp

                    <tr class="transition hover:bg-slate-800/30">
                        <td class="px-5 py-4 align-top">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('admin.leads.show', $lead) }}" class="sf-leads-title font-extrabold hover:text-orange-400">
                                        {{ $lead->name ?? 'Unnamed Lead' }}
                                    </a>

                                    @if((bool) ($lead->is_hot ?? false))
                                        <span class="{{ $badgeBase }} bg-red-500/10 text-red-300 ring-red-400/20">Hot</span>
                                    @endif
                                </div>

                                <div class="sf-leads-value mt-1 text-sm font-bold">
                                    @if($leadTelUrl)
                                        <a href="{{ $leadTelUrl }}" class="sf-link break-all">
                                            {{ $leadPhoneDisplay }}
                                        </a>
                                    @else
                                        <span class="sf-leads-muted">No phone</span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td class="px-5 py-4 align-top">
                            <div class="flex flex-wrap gap-1">
                                @if($lead->service_category)
                                    <span class="{{ $badgeBase }} {{ $categoryBadge($lead->service_category) }}">
                                        {{ ucfirst(str_replace('_', ' ', $lead->service_category)) }}
                                    </span>
                                @else
                                    <span class="sf-leads-muted">-</span>
                                @endif
                            </div>

                            @if($lead->service_type)
                                <div class="sf-leads-muted mt-1 truncate text-xs">{{ ucfirst($lead->service_type) }}</div>
                            @endif

                            @if($lead->retention_tag)
                                <div class="mt-1 truncate text-xs font-bold text-orange-300">{{ str_replace('_', ' ', $lead->retention_tag) }}</div>
                            @endif

                            <div class="sf-leads-muted mt-1 truncate text-xs">
                                {{ $lead->source ?? 'No source' }}
                                @if($lead->campaign_name)
                                    · {{ $lead->campaign_name }}
                                @elseif($lead->leadSource)
                                    · {{ $lead->leadSource->name }}
                                @endif
                            </div>
                        </td>

                        <td class="px-5 py-4 align-top">
                            @if($vehicleText)
                                <div class="sf-leads-value font-bold">{{ $vehicleText }}</div>
                                <div class="sf-leads-muted mt-1 truncate text-xs">
                                    {{ $lead->vehicle_year ?? '' }}
                                    @if($lead->plate_number)
                                        · {{ $lead->plate_number }}
                                    @endif
                                </div>
                            @else
                                <span class="sf-leads-muted">-</span>
                            @endif
                        </td>

                        <td class="px-5 py-4 align-top">
                            <span class="{{ $badgeBase }} {{ $scoreBadge($score) }}">{{ $score }}/100</span>

                            <div class="mt-2 flex flex-wrap gap-1">
                                @if($lead->lead_temperature)
                                    <span class="{{ $badgeBase }} {{ $temperatureBadge($lead->lead_temperature) }}">{{ ucfirst($lead->lead_temperature) }}</span>
                                @endif

                                @if($lead->lead_priority)
                                    <span class="{{ $badgeBase }} {{ $priorityBadge($lead->lead_priority) }}">{{ ucfirst($lead->lead_priority) }}</span>
                                @endif

                                @if(! $lead->lead_temperature && ! $lead->lead_priority)
                                    <span class="sf-leads-muted">-</span>
                                @endif
                            </div>
                        </td>

                        <td class="px-5 py-4 align-top">
                            @if($lead->follow_up_required)
                                <div class="sf-leads-value text-xs font-extrabold">Required</div>
                                <div class="mt-1 text-xs font-bold {{ $lead->follow_up_date && \Carbon\Carbon::parse($lead->follow_up_date)->isPast() ? 'text-red-300' : 'text-orange-300' }}">
                                    {{ optional($lead->follow_up_date)->format('d M Y') ?? 'No date' }}
                                </div>
                            @else
                                <span class="sf-leads-muted">-</span>
                            @endif
                        </td>

                        <td class="px-5 py-4 align-top">
                            @if($leadWhatsappUrl)
                                <a href="{{ $leadWhatsappUrl }}" class="{{ $badgeBase }} {{ $wa['class'] }}" title="Open WhatsApp conversation in Inbox">
                                    {{ $wa['label'] }}
                                </a>
                            @else
                                <span class="{{ $badgeBase }} {{ $wa['class'] }}">{{ $wa['label'] }}</span>
                            @endif

                            <div class="mt-2">
                                <span class="{{ $badgeBase }} {{ $statusBadge($lead->status) }}">
                                    {{ $lead->status_label ?? ucfirst(str_replace('_',' ', $lead->status)) }}
                                </span>
                            </div>

                            <div class="sf-leads-muted mt-1 text-xs">{{ optional($lead->created_at)->format('d M Y') }}</div>
                        </td>

                        <td class="px-5 py-4 text-right align-top">
                            <div class="sf-leads-action-group">
                                <a href="{{ route('admin.leads.show', $lead) }}" class="sf-leads-action-pill">View</a>
                                <a href="{{ route('admin.leads.edit', $lead) }}" class="sf-leads-action-pill">Edit</a>

                                @if(($pageMode ?? 'open') !== 'archived')
                                    <form method="POST" action="{{ route('admin.leads.destroy', $lead) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="sf-leads-action-pill sf-leads-action-pill-danger">
                                            Archive
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-10">
                            <div class="sf-leads-soft-panel rounded-2xl border p-8 text-center">
                                <div class="sf-leads-title text-base font-extrabold">
                                    @if(($pageMode ?? 'open') === 'open' && blank($bucket ?? ''))
                                        No open leads need action right now.
                                    @elseif(($pageMode ?? 'open') === 'open' && ! blank($bucket ?? ''))
                                        No leads found in this bucket.
                                    @elseif(($pageMode ?? 'open') === 'qualified')
                                        No qualified leads found.
                                    @elseif(($pageMode ?? 'open') === 'disqualified')
                                        No disqualified leads found.
                                    @else
                                        No leads found.
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
