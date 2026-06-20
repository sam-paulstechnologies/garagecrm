{{-- resources/views/admin/leads/show-partials/_details.blade.php --}}

<div class="sf-leads-show-panel rounded-2xl border shadow-sm">
    <div class="border-b border-slate-800 p-5">
        <h2 class="sf-leads-show-title text-lg font-extrabold tracking-tight">Basic Details</h2>
    </div>

    <div class="grid grid-cols-1 gap-5 p-5 text-sm md:grid-cols-2">
        @foreach([
            'Name' => $lead->name ?? '-',
            'Email' => $lead->email ?? '-',
            'Phone' => $lead->phone ?? $lead->phone_norm ?? '-',
            'Assigned To' => $lead->assignee?->name ?? 'Unassigned',
            'Preferred Channel' => $lead->preferred_channel ?? '-',
            'Vehicle' => $lead->vehicle_label ?? '-',
        ] as $label => $value)
            <div>
                <div class="sf-leads-show-muted text-xs font-black uppercase tracking-wide">{{ $label }}</div>
                <div class="sf-leads-show-value mt-1 font-bold">{{ $value }}</div>
            </div>
        @endforeach

        <div>
            <div class="sf-leads-show-muted text-xs font-black uppercase tracking-wide">Status</div>
            <div class="mt-1 flex flex-wrap gap-2">
                <span class="{{ $badgeBase }} {{ $statusBadgeClass }}">
                    {{ $lead->status_label ?? ucfirst(str_replace('_', ' ', (string) $lead->status)) }}
                </span>

                @if($lead->status === 'attempting_contact')
                    <span class="{{ $badgeBase }} bg-yellow-500/10 text-yellow-300 ring-yellow-400/20">In Progress</span>
                @endif
            </div>
        </div>

        <div>
            <div class="sf-leads-show-muted text-xs font-black uppercase tracking-wide">Lead Score</div>
            <div class="mt-1">
                <span class="{{ $badgeBase }} {{ $scoreBadgeClass }}">{{ $score }}/100</span>
            </div>
        </div>

        <div>
            <div class="sf-leads-show-muted text-xs font-black uppercase tracking-wide">Hot Lead</div>
            <div class="mt-1 font-bold {{ $lead->is_hot ? 'text-red-300' : 'sf-leads-show-value' }}">
                {{ $lead->is_hot ? 'Yes' : 'No' }}
            </div>
        </div>

        <div>
            <div class="sf-leads-show-muted text-xs font-black uppercase tracking-wide">Last Contacted</div>
            <div class="sf-leads-show-value mt-1 font-bold">{{ $lead->last_contacted_at?->format('d M Y, h:i A') ?? '-' }}</div>
        </div>

        <div class="md:col-span-2">
            <div class="sf-leads-show-muted text-xs font-black uppercase tracking-wide">Lead Score Reason</div>
            <div class="sf-leads-show-value mt-1 font-bold">
                {{ $lead->lead_score_reason ?? 'Calculated from phone, source, vehicle, hot flag, status, and WhatsApp activity.' }}
            </div>
        </div>

        <div class="md:col-span-2">
            <div class="sf-leads-show-muted text-xs font-black uppercase tracking-wide">Notes</div>
            <div class="sf-leads-show-value mt-1 whitespace-pre-wrap font-bold">{{ $lead->notes ?? '-' }}</div>
        </div>

        <div>
            <div class="sf-leads-show-muted text-xs font-black uppercase tracking-wide">Created</div>
            <div class="sf-leads-show-value mt-1 font-bold">{{ $lead->created_at?->format('d M Y, h:i A') ?? '-' }}</div>
        </div>

        <div>
            <div class="sf-leads-show-muted text-xs font-black uppercase tracking-wide">Updated</div>
            <div class="sf-leads-show-value mt-1 font-bold">{{ $lead->updated_at?->format('d M Y, h:i A') ?? '-' }}</div>
        </div>
    </div>
</div>
