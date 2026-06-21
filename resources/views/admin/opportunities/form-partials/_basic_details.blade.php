<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">Basic Opportunity Details</h2>
        <p class="sf-section-subtitle">Link the opportunity to the customer, lead, and record title.</p>
    </div>

    <div class="sf-card-body">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label class="sf-label">Client <span class="text-red-300">*</span></label>

                @if($isEdit)
                    <input type="hidden" name="client_id" id="client_id" value="{{ $opp?->client_id }}">
                    <input type="text" value="{{ $opp?->client?->name ?? 'Client' }}" class="sf-input bg-slate-950/70" readonly>
                @else
                    <select name="client_id" id="client_id" required class="sf-select">
                        <option value="">-- Select Client --</option>
                        @foreach($clientsCollection as $client)
                            <option value="{{ $client->id }}" @selected($selectedClientId === (string) $client->id)>
                                {{ $client->name }}{{ $client->phone ? ' - '.$client->phone : '' }}
                            </option>
                        @endforeach
                    </select>
                @endif

                @error('client_id')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="sf-label">Lead</label>

                @if($isEdit)
                    <input type="hidden" name="lead_id" value="{{ $opp?->lead_id }}">
                    <input type="text" value="{{ $opp?->lead?->name ?? '-' }}" class="sf-input bg-slate-950/70" readonly>
                @else
                    <select name="lead_id" class="sf-select">
                        <option value="">-- None --</option>
                        @foreach($leadsCollection as $lead)
                            <option value="{{ $lead->id }}" @selected($selectedLeadId === (string) $lead->id)>
                                {{ $lead->name }}{{ $lead->phone ? ' - '.$lead->phone : '' }}
                            </option>
                        @endforeach
                    </select>
                @endif

                @error('lead_id')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="sf-label">Opportunity Title <span class="text-red-300">*</span></label>
                <input type="text" name="title" id="opportunity_title" value="{{ $oldOr('title') }}" required placeholder="Example: Manjula - Cadillac Escalade - General Service" class="sf-input">
                <p class="sf-help">Title can be auto-updated using client, vehicle, and service details. You can still edit it manually.</p>

                @error('title')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>
