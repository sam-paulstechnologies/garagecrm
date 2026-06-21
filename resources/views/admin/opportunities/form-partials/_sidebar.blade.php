<div class="sf-crm-sidebar space-y-4 lg:sticky lg:top-24">
    @if($isEdit && $opp)
        <div class="sf-opportunity-edit-panel rounded-2xl border shadow-sm">
            <div class="border-b border-slate-800 px-5 py-4">
                <h2 class="sf-opportunity-edit-title text-base font-extrabold tracking-tight">Opportunity Snapshot</h2>
            </div>

            <div class="divide-y divide-slate-800 text-sm">
                @foreach([
                    'Opportunity' => $opp->title ?? 'Untitled Opportunity',
                    'Client' => $opp->client?->name ?? 'Not set',
                    'Stage' => \App\Models\Client\Opportunity::stageLabel($opp->stage ?? 'new'),
                    'Priority' => ucfirst($opp->priority ?? 'Medium'),
                    'Created' => $opp->created_at?->format('d M Y, h:i A') ?? '-',
                ] as $label => $value)
                    <div class="sf-crm-snapshot-row px-5 py-3">
                        <div class="sf-opportunity-edit-muted text-xs font-black uppercase tracking-wide">{{ $label }}</div>
                        <div class="sf-opportunity-edit-value mt-1 font-bold">{{ $value }}</div>
                    </div>
                @endforeach

                @if($opp->bookings->isNotEmpty())
                    @php $latestBooking = $opp->bookings->sortByDesc('created_at')->first(); @endphp
                    <div class="sf-crm-snapshot-row px-5 py-3">
                        <div class="sf-opportunity-edit-muted text-xs font-black uppercase tracking-wide">Booking</div>
                        <a href="{{ route('admin.bookings.show', $latestBooking) }}" class="sf-crm-link mt-1 inline-flex text-sm font-bold">
                            Open Booking #{{ $latestBooking->id }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="sf-opportunity-edit-panel rounded-2xl border p-5 shadow-sm">
        <h2 class="sf-opportunity-edit-title text-base font-extrabold tracking-tight">Edit Guidelines</h2>
        <ul class="mt-3 space-y-2 text-sm">
            <li class="sf-opportunity-edit-muted">Use stage changes only for real pipeline movement.</li>
            <li class="sf-opportunity-edit-muted">Booking Confirmed creates or opens one Booking.</li>
            <li class="sf-opportunity-edit-muted">Vehicle and service details improve booking context.</li>
        </ul>
    </div>

    <div class="rounded-2xl border border-orange-400/25 bg-orange-500/10 p-5 shadow-sm">
        <h3 class="sf-opportunity-edit-note-title font-extrabold">WhatsApp Note</h3>
        <p class="sf-opportunity-edit-note-text mt-2 text-sm font-medium leading-6">
            Editing an opportunity does not automatically resend WhatsApp messages. Continue messaging from Inbox or automations.
        </p>
    </div>
</div>
