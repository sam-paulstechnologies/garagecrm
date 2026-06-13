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
                    <label for="client_search" class="sf-label">
                        Search Client
                    </label>

                    <input id="client_search"
                           type="search"
                           class="sf-input"
                           placeholder="Search by name, phone, WhatsApp, or email"
                           autocomplete="off"
                           aria-controls="client_combobox_results"
                           aria-expanded="false">

                    <div id="client_combobox_results"
                         class="hidden mt-2 max-h-64 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-lg dark:border-slate-700 dark:bg-slate-900"
                         role="listbox"></div>

                    <p class="sf-help">
                        Search above, then choose an existing client or select + Add New Client.
                    </p>
                </div>

                <div>
                    <label for="client_id" class="sf-label">
                        Client <span class="text-red-300">*</span>
                    </label>

                    <select id="client_id"
                            name="client_id"
                            class="sf-select hidden"
                            aria-hidden="true"
                            tabindex="-1">
                        <option value="">- Select Client -</option>
                        <option value="new_client" @selected($selectedClientId === 'new_client')>
                            + Add New Client
                        </option>

                        @foreach(($clients ?? collect()) as $client)
                            @php
                                $clientSearch = trim(implode(' ', array_filter([
                                    $client->name,
                                    $client->phone,
                                    $client->whatsapp,
                                    $client->email,
                                ])));
                            @endphp

                            <option value="{{ $client->id }}"
                                    data-search="{{ strtolower($clientSearch) }}"
                                    @selected($selectedClientId === (string) $client->id)>
                                {{ $client->name }}{{ $client->phone ? ' - '.$client->phone : ($client->whatsapp ? ' - '.$client->whatsapp : '') }}
                            </option>
                        @endforeach
                    </select>

                    <div id="client_selected_summary"
                         class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200">
                        No client selected
                    </div>

                    @error('client_id')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror

                    <p class="sf-help">
                        Selected client is controlled by the searchable list.
                    </p>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label for="opportunity_search" class="sf-label">
                        Search Opportunity
                    </label>

                    <input id="opportunity_search"
                           type="search"
                           class="sf-input"
                           placeholder="Search by title, client, service, vehicle, or plate"
                           autocomplete="off"
                           aria-controls="opportunity_combobox_results"
                           aria-expanded="false">

                    <div id="opportunity_combobox_results"
                         class="hidden mt-2 max-h-64 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-lg dark:border-slate-700 dark:bg-slate-900"
                         role="listbox"></div>

                    <p class="sf-help">
                        Search above, then choose an open opportunity if this booking belongs to one.
                    </p>
                </div>

                <div>
                    <label for="opportunity_id" class="sf-label">
                        Opportunity
                    </label>

                    <select id="opportunity_id"
                            name="opportunity_id"
                            class="sf-select hidden"
                            aria-hidden="true"
                            tabindex="-1">
                        <option value="">- None -</option>

                        @foreach(($opportunities ?? collect()) as $opportunity)
                            @php
                                $vehicle = $opportunity->vehicle;
                                $vehicleLabel = trim(implode(' ', array_filter([
                                    $vehicle?->make?->name,
                                    $vehicle?->model?->name,
                                    $vehicle?->plate_number,
                                ])));
                                $opportunitySearch = trim(implode(' ', array_filter([
                                    '#' . $opportunity->id,
                                    $opportunity->title,
                                    $opportunity->client?->name,
                                    $opportunity->client?->phone,
                                    $opportunity->service_type,
                                    $vehicleLabel,
                                ])));
                            @endphp

                            <option value="{{ $opportunity->id }}"
                                    data-client-id="{{ $opportunity->client_id }}"
                                    data-vehicle-id="{{ $opportunity->vehicle_id }}"
                                    data-stage="{{ $opportunity->stage }}"
                                    data-search="{{ strtolower($opportunitySearch) }}"
                                    @selected($selectedOpportunityId === (string) $opportunity->id)>
                                #{{ $opportunity->id }} - {{ $opportunity->title ?? 'Opportunity' }}{{ $opportunity->client?->name ? ' - '.$opportunity->client->name : '' }}{{ $opportunity->service_type ? ' - '.$opportunity->service_type : '' }}{{ $vehicleLabel ? ' - '.$vehicleLabel : '' }}
                            </option>
                        @endforeach
                    </select>

                    <div id="opportunity_selected_summary"
                         class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200">
                        No opportunity selected
                    </div>

                    @error('opportunity_id')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div id="quick_client_panel" class="mt-5 rounded-2xl border border-dashed border-orange-400/30 bg-orange-500/5 p-4">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-sm font-extrabold sf-booking-value">
                            Create New Client
                        </h3>

                        <p class="mt-1 text-xs font-medium sf-booking-muted">
                            Use this only when the customer is not already in the client list.
                        </p>
                    </div>

                    <span class="inline-flex w-fit rounded-full border border-orange-400/30 bg-orange-500/10 px-3 py-1 text-xs font-bold text-orange-300">
                        Requires vehicle below
                    </span>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label for="new_client_name" class="sf-label">
                            New Client Name
                        </label>

                        <input id="new_client_name"
                               name="new_client_name"
                               type="text"
                               value="{{ old('new_client_name') }}"
                               class="sf-input"
                               placeholder="Customer name">

                        @error('new_client_name')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="new_client_phone" class="sf-label">
                            Phone
                        </label>

                        <input id="new_client_phone"
                               name="new_client_phone"
                               type="text"
                               value="{{ old('new_client_phone') }}"
                               class="sf-input"
                               placeholder="9715XXXXXXXX">

                        @error('new_client_phone')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="new_client_whatsapp" class="sf-label">
                            WhatsApp
                        </label>

                        <input id="new_client_whatsapp"
                               name="new_client_whatsapp"
                               type="text"
                               value="{{ old('new_client_whatsapp') }}"
                               class="sf-input"
                               placeholder="9715XXXXXXXX">

                        @error('new_client_whatsapp')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <p class="sf-help mt-3">
                    If phone or WhatsApp matches an existing client in this garage, the booking will reuse that client.
                </p>
            </div>
        @endif
    </div>
</div>
