{{-- resources/views/admin/leads/edit-partials/_sidebar.blade.php --}}

<div class="sf-leads-edit-panel rounded-2xl border shadow-sm">
    <div class="border-b border-slate-800 p-5">
        <h2 class="sf-leads-edit-title text-lg font-extrabold tracking-tight">Lead Snapshot</h2>
        <p class="sf-leads-edit-muted mt-1 text-sm font-medium">Current lead context.</p>
    </div>

    <div class="space-y-4 p-5 text-sm">
        <div class="sf-leads-edit-soft rounded-2xl border p-4">
            <div class="sf-leads-edit-muted text-xs font-black uppercase tracking-wide">Name</div>
            <div class="sf-leads-edit-value mt-2 font-extrabold">{{ $lead->name ?? 'Unnamed Lead' }}</div>
        </div>

        <div class="sf-leads-edit-soft rounded-2xl border p-4">
            <div class="sf-leads-edit-muted text-xs font-black uppercase tracking-wide">Contact</div>
            <div class="sf-leads-edit-value mt-2 font-bold">{{ $lead->phone ?? $lead->phone_norm ?? $lead->email ?? 'No contact available' }}</div>
        </div>

        <div class="sf-leads-edit-soft rounded-2xl border p-4">
            <div class="sf-leads-edit-muted text-xs font-black uppercase tracking-wide">Status</div>
            <div class="mt-2">
                <span class="sf-badge-blue">
                    {{ ucfirst(str_replace('_', ' ', $lead->status ?? 'new')) }}
                </span>
            </div>
        </div>

        <div class="sf-leads-edit-soft rounded-2xl border p-4">
            <div class="sf-leads-edit-muted text-xs font-black uppercase tracking-wide">Source</div>
            <div class="sf-leads-edit-value mt-2 font-bold">{{ $lead->source ?? $lead->leadSource?->name ?? 'Manual' }}</div>
        </div>

        <div class="sf-leads-edit-soft rounded-2xl border p-4">
            <div class="sf-leads-edit-muted text-xs font-black uppercase tracking-wide">Created</div>
            <div class="sf-leads-edit-value mt-2 font-bold">{{ $lead->created_at?->format('d M Y, h:i A') ?? '-' }}</div>
        </div>
    </div>
</div>

<div class="sf-leads-edit-panel rounded-2xl border shadow-sm">
    <div class="border-b border-slate-800 p-5">
        <h2 class="sf-leads-edit-title text-lg font-extrabold tracking-tight">Edit Guidelines</h2>
    </div>

    <div class="p-5">
        <ul class="space-y-4 text-sm">
            <li class="flex gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">1</span>
                <span class="sf-leads-edit-muted font-medium">Keep phone number with country code where possible.</span>
            </li>

            <li class="flex gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">2</span>
                <span class="sf-leads-edit-muted font-medium">Use status updates only when the lead journey has actually changed.</span>
            </li>

            <li class="flex gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">3</span>
                <span class="sf-leads-edit-muted font-medium">Vehicle details help improve booking and job context.</span>
            </li>

            <li class="flex gap-3">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">4</span>
                <span class="sf-leads-edit-muted font-medium">Assignment changes affect team ownership and follow-ups.</span>
            </li>
        </ul>
    </div>
</div>

<div class="rounded-2xl border border-orange-400/25 bg-orange-500/10 p-5 shadow-sm">
    <h3 class="sf-leads-edit-note-title font-extrabold">WhatsApp Note</h3>
    <p class="sf-leads-edit-note-text mt-2 text-sm font-medium leading-6">
        Editing a lead will not automatically resend WhatsApp messages. Messaging flow should continue from Inbox or automation triggers.
    </p>
</div>
