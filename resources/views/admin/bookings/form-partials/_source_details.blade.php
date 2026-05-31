<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            Source Details
        </h2>

        <p class="sf-section-subtitle">
            Select the client, opportunity, and vehicle connected to this booking.
        </p>
    </div>

    <div class="sf-card-body">
        @if($isEdit)
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div class="sf-booking-soft-panel rounded-2xl border p-4">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                        Opportunity
                    </div>

                    <div class="mt-1 font-extrabold sf-booking-value">
                        {{ $sourceOpportunityLabel }}
                    </div>

                    <p class="mt-2 text-xs font-medium sf-booking-muted">
                        Source opportunity is locked after booking creation.
                    </p>
                </div>

                <div class="sf-booking-soft-panel rounded-2xl border p-4">
                    <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                        Client
                    </div>

                    <div class="mt-1 font-extrabold sf-booking-value">
                        {{ $sourceClientLabel }}
                    </div>

                    <p class="mt-2 text-xs font-medium sf-booking-muted">
                        Client is locked after booking creation.
                    </p>
                </div>
            </div>

            <input type="hidden" name="opportunity_id" value="{{ $bk?->opportunity_id }}">
            <input type="hidden" name="client_id" value="{{ $bk?->client_id }}">
        @else
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label for="opportunity_id" class="sf-label">
                        Opportunity
                    </label>

                    <select id="opportunity_id"
                            name="opportunity_id"
                            class="sf-select">
                        <option value="">- None -</option>

                        @foreach(($opportunities ?? collect()) as $opportunity)
                            <option value="{{ $opportunity->id }}"
                                    data-client-id="{{ $opportunity->client_id }}"
                                    data-vehicle-id="{{ $opportunity->vehicle_id }}"
                                    @selected($selectedOpportunityId === (string) $opportunity->id)>
                                #{{ $opportunity->id }} - {{ $opportunity->title ?? 'Opportunity' }}
                            </option>
                        @endforeach
                    </select>

                    @error('opportunity_id')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror

                    <p class="sf-help">
                        Selecting an opportunity can auto-fill client, vehicle, priority, and date.
                    </p>
                </div>

                <div>
                    <label for="client_id" class="sf-label">
                        Client <span class="text-red-300">*</span>
                    </label>

                    <select id="client_id"
                            name="client_id"
                            class="sf-select"
                            required>
                        <option value="">- Select Client -</option>

                        @foreach(($clients ?? collect()) as $client)
                            <option value="{{ $client->id }}" @selected($selectedClientId === (string) $client->id)>
                                {{ $client->name }}{{ $client->phone ? ' - '.$client->phone : '' }}
                            </option>
                        @endforeach
                    </select>

                    @error('client_id')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        @endif
    </div>
</div>
