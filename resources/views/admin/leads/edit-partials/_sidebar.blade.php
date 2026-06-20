{{-- resources/views/admin/leads/edit-partials/_sidebar.blade.php --}}

<div class="sf-crm-sidebar space-y-4 lg:sticky lg:top-24">
    <div class="sf-leads-edit-panel rounded-2xl border shadow-sm">
        <div class="border-b border-slate-800 px-5 py-4">
            <h2 class="sf-leads-edit-title text-base font-extrabold tracking-tight">Lead Snapshot</h2>
        </div>

        <div class="divide-y divide-slate-800 text-sm">
            @foreach([
                'Name' => $lead->name ?? 'Unnamed Lead',
                'Phone' => $lead->phone ?? $lead->phone_norm ?? 'Not set',
                'Status' => $lead->status_label ?? ucfirst(str_replace('_', ' ', $lead->status ?? 'new')),
                'Source' => $lead->source ?? $lead->leadSource?->name ?? 'Manual',
                'Created' => $lead->created_at?->format('d M Y, h:i A') ?? '-',
            ] as $label => $value)
                <div class="sf-crm-snapshot-row px-5 py-3">
                    <div class="sf-leads-edit-muted text-xs font-black uppercase tracking-wide">{{ $label }}</div>
                    <div class="sf-leads-edit-value mt-1 font-bold">{{ $value }}</div>
                </div>
            @endforeach

            @if($lead->opportunity)
                <div class="sf-crm-snapshot-row px-5 py-3">
                    <div class="sf-leads-edit-muted text-xs font-black uppercase tracking-wide">Opportunity</div>
                    <a href="{{ route('admin.opportunities.show', $lead->opportunity) }}" class="sf-crm-link mt-1 inline-flex text-sm font-bold">
                        Open Opportunity #{{ $lead->opportunity->id }}
                    </a>
                </div>
            @endif
        </div>
    </div>

    <div class="sf-leads-edit-panel rounded-2xl border p-5 shadow-sm">
        <h2 class="sf-leads-edit-title text-base font-extrabold tracking-tight">Edit Guidelines</h2>
        <ul class="mt-3 space-y-2 text-sm">
            <li class="sf-leads-edit-muted">Use lifecycle status only for real journey changes.</li>
            <li class="sf-leads-edit-muted">Qualified creates or opens one Opportunity.</li>
            <li class="sf-leads-edit-muted">Vehicle and service details improve follow-up context.</li>
        </ul>
    </div>

    <div class="rounded-2xl border border-orange-400/25 bg-orange-500/10 p-5 shadow-sm">
        <h3 class="sf-leads-edit-note-title font-extrabold">WhatsApp Note</h3>
        <p class="sf-leads-edit-note-text mt-2 text-sm font-medium leading-6">
            Editing a lead does not automatically resend WhatsApp messages. Continue messaging from Inbox or automations.
        </p>
    </div>
</div>
