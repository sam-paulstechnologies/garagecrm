{{-- resources/views/admin/opportunities/form.blade.php --}}

@php
    $opp = $opportunity ?? null;
    $isEdit = (bool) ($isEdit ?? false);

    $oldOr = function ($key, $fallback = null) use ($opp) {
        return old($key, data_get($opp, $key, $fallback));
    };

    $fmtDate = function ($value) {
        if (! $value) {
            return '';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return '';
        }
    };

    $stageOptions = [
        'new' => 'New',
        'attempting_contact' => 'Attempting Contact',
        'manager_confirmation_pending' => 'Manager Confirmation Pending',
        'appointment' => 'Appointment Planned',
        'closed_won' => 'Booking Confirmed',
        'closed_lost' => 'Closed Lost',
    ];

    $priorityOptions = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    $serviceList = [
        'General Service',
        'Oil Change',
        'AC Repair',
        'Battery Check',
        'Brake Service',
        'Transmission Service',
        'Car Wash',
        'Detailing',
        'Vehicle Inspection',
        'Registration Renewal',
        'Suspension Work',
        'Tinting',
        'Other',
    ];

    $closeReasonOptions = [
        'Price too high',
        'Customer not responding',
        'Went to another garage',
        'Not serviceable',
        'Wrong lead',
        'Duplicate',
        'Customer postponed',
        'Other',
    ];

    $selectedStage = $oldOr('stage', 'new');
    $selectedPriority = $oldOr('priority', 'medium');
    $selectedClientId = (string) old('client_id', $opp?->client_id ?? request('client_id', ''));
    $selectedLeadId = (string) old('lead_id', $opp?->lead_id ?? request('lead_id', ''));
    $selectedVehicleId = (string) old('vehicle_id', $opp?->vehicle_id ?? request('vehicle_id', ''));
    $selectedAssignedTo = (string) old('assigned_to', $opp?->assigned_to ?? '');

    /*
    |--------------------------------------------------------------------------
    | IMPORTANT
    |--------------------------------------------------------------------------
    | Backend expects service_type as a STRING.
    | So we DO NOT submit service_type[].
    | Checkboxes are UI-only and JS writes comma-separated values into hidden service_type.
    |--------------------------------------------------------------------------
    */
    $serviceRaw = old('service_type', $opp?->service_type ?? '');
    $selectedServices = is_array($serviceRaw)
        ? collect($serviceRaw)
        : collect(explode(',', (string) $serviceRaw));

    $selectedServices = $selectedServices
        ->map(fn ($service) => trim((string) $service))
        ->filter()
        ->values()
        ->all();

    $customService = old('custom_service_type');

    if (! $customService) {
        $customService = collect($selectedServices)
            ->first(fn ($service) => ! in_array($service, $serviceList, true));
    }

    $bookingDateVal = old('booking_date', $fmtDate($opp?->booking_date ?? $opp?->expected_close_date ?? null));
    $bookingSlotVal = old('booking_slot', $opp?->booking_slot ?? '');
    $bookingNotesVal = old('booking_notes', $opp?->booking_notes ?? '');

    $clientsCollection = collect($clients ?? []);
    $leadsCollection = collect($leads ?? []);
    $vehiclesCollection = collect($vehicles ?? []);
    $usersCollection = collect($users ?? []);
    $makesCollection = collect($makes ?? []);
    $modelsCollection = collect($models ?? []);

    $selectedManualMakeId = (string) old('manual_make_id', $opp?->manual_make_id ?? '');
    $selectedManualModelId = (string) old('manual_model_id', $opp?->manual_model_id ?? '');
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf

    @if($isEdit)
        @method('PUT')
    @endif

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="sf-alert-danger">
            <div class="mb-2 font-extrabold">
                Please fix the following:
            </div>

            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Pipeline Guide --}}
    <div class="sf-card">
        <div class="sf-card-header">
            <h2 class="sf-section-title">
                Pipeline Guide
            </h2>

            <p class="sf-section-subtitle">
                New → Attempting Contact → Manager Confirmation Pending → Appointment Planned → Booking Confirmed → Booking → Job → Invoice
            </p>
        </div>

        <div class="sf-card-body">
            <div class="rounded-3xl border border-orange-400/20 bg-orange-500/10 p-5">
                <div class="font-extrabold text-orange-300">
                    Important
                </div>

                <p class="mt-2 text-sm font-medium leading-6 text-orange-100/80">
                    Appointment Planned is not a booking. Select Booking Confirmed only when the customer has agreed to proceed, then confirm the actual booking date and slot.
                </p>
            </div>
        </div>
    </div>

    {{-- Main Grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Main Form --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- Basic Details --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Basic Details
                    </h2>

                    <p class="sf-section-subtitle">
                        Link the opportunity to a client, lead, and title.
                    </p>
                </div>

                <div class="sf-card-body">
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                        {{-- Client --}}
                        <div>
                            <label class="sf-label">
                                Client <span class="text-red-300">*</span>
                            </label>

                            @if($isEdit)
                                <input type="hidden" name="client_id" id="client_id" value="{{ $opp?->client_id }}">

                                <input type="text"
                                       value="{{ $opp?->client?->name ?? 'Client' }}"
                                       class="sf-input bg-slate-950/70"
                                       readonly>
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

                        {{-- Lead --}}
                        <div>
                            <label class="sf-label">
                                Lead
                            </label>

                            @if($isEdit)
                                <input type="hidden" name="lead_id" value="{{ $opp?->lead_id }}">

                                <input type="text"
                                       value="{{ $opp?->lead?->name ?? '—' }}"
                                       class="sf-input bg-slate-950/70"
                                       readonly>
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

                        {{-- Title --}}
                        <div class="md:col-span-2">
                            <label class="sf-label">
                                Opportunity Title <span class="text-red-300">*</span>
                            </label>

                            <input type="text"
                                   name="title"
                                   id="opportunity_title"
                                   value="{{ $oldOr('title') }}"
                                   required
                                   placeholder="Example: Manjula - Cadillac Escalade - General Service"
                                   class="sf-input">

                            <p class="sf-help">
                                Title can be auto-updated using client, vehicle, and service details. You can still edit it manually.
                            </p>

                            @error('title')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- Pipeline Details --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Pipeline Details
                    </h2>

                    <p class="sf-section-subtitle">
                        Control the sales stage, priority, value, owner, and expected appointment date.
                    </p>
                </div>

                <div class="sf-card-body">
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                        {{-- Stage --}}
                        <div>
                            <label class="sf-label">
                                Stage <span class="text-red-300">*</span>
                            </label>

                            <select name="stage" id="stage_select" required class="sf-select">
                                @foreach($stageOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($selectedStage === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>

                            <p class="sf-help">
                                Select the current stage of this opportunity.
                            </p>

                            @error('stage')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Priority --}}
                        <div>
                            <label class="sf-label">
                                Priority
                            </label>

                            <select name="priority" class="sf-select">
                                @foreach($priorityOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($selectedPriority === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>

                            @error('priority')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Tentative Date --}}
                        <div>
                            <label class="sf-label">
                                Tentative Appointment / Planning Date
                            </label>

                            <input type="date"
                                   name="expected_close_date"
                                   id="expected_close_date"
                                   value="{{ old('expected_close_date', $fmtDate($opp?->expected_close_date ?? null)) }}"
                                   class="sf-input">

                            <p class="sf-help">
                                This is only a tentative or planning date. Confirmed booking date is captured when stage is Booking Confirmed.
                            </p>

                            @error('expected_close_date')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Value --}}
                        <div>
                            <label class="sf-label">
                                Estimated Value (AED)
                            </label>

                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   name="value"
                                   value="{{ $oldOr('value') }}"
                                   placeholder="0.00"
                                   class="sf-input">

                            @error('value')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Assigned To --}}
                        <div>
                            <label class="sf-label">
                                Owner / Manager
                            </label>

                            <select name="assigned_to" class="sf-select">
                                <option value="">Unassigned</option>

                                @foreach($usersCollection as $user)
                                    <option value="{{ $user->id }}" @selected($selectedAssignedTo === (string) $user->id)>
                                        {{ $user->name }}{{ !empty($user->role) ? ' - '.ucfirst($user->role) : '' }}
                                    </option>
                                @endforeach
                            </select>

                            <p class="sf-help">
                                Only admin and manager users should be shown here.
                            </p>

                            @error('assigned_to')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Close Reason --}}
                        <div id="close_reason_wrap" class="hidden">
                            <label class="sf-label">
                                Close Reason <span class="text-red-300">*</span>
                            </label>

                            <select name="close_reason" id="close_reason" class="sf-select">
                                <option value="">-- Select reason --</option>

                                @foreach($closeReasonOptions as $reason)
                                    <option value="{{ $reason }}" @selected(old('close_reason', $opp?->close_reason ?? '') === $reason)>
                                        {{ $reason }}
                                    </option>
                                @endforeach
                            </select>

                            <p class="sf-help">
                                Used later for retention and marketing analysis.
                            </p>

                            @error('close_reason')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- Booking Confirmation --}}
            <div id="booking_confirmation_wrap" class="hidden sf-card border-green-400/20">
                <div class="sf-card-header flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h2 class="sf-section-title">
                            Booking Confirmation
                        </h2>

                        <p class="sf-section-subtitle">
                            Required only when stage is Booking Confirmed. This creates or updates the booking record.
                        </p>
                    </div>

                    <span class="sf-badge-green">
                        Customer Agreed
                    </span>
                </div>

                <div class="sf-card-body space-y-5">
                    <div class="rounded-3xl border border-green-400/20 bg-green-500/10 p-5">
                        <div class="font-extrabold text-green-300">
                            Confirm actual booking details
                        </div>

                        <p class="mt-2 text-sm font-medium leading-6 text-green-100/80">
                            Do not rely only on tentative appointment date. Confirm the real booking date and slot here.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label class="sf-label">
                                Confirmed Booking Date <span class="text-red-300">*</span>
                            </label>

                            <input type="date"
                                   name="booking_date"
                                   id="booking_date"
                                   value="{{ $bookingDateVal }}"
                                   class="sf-input">

                            @error('booking_date')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="sf-label">
                                Confirmed Slot <span class="text-red-300">*</span>
                            </label>

                            <select name="booking_slot" id="booking_slot" class="sf-select">
                                <option value="">-- Select Slot --</option>
                                <option value="morning" @selected($bookingSlotVal === 'morning')>Morning</option>
                                <option value="afternoon" @selected($bookingSlotVal === 'afternoon')>Afternoon</option>
                                <option value="evening" @selected($bookingSlotVal === 'evening')>Evening</option>
                            </select>

                            @error('booking_slot')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="sf-label">
                                Booking Notes
                            </label>

                            <textarea name="booking_notes"
                                      id="booking_notes"
                                      rows="3"
                                      placeholder="Example: Customer requested pickup from office basement parking."
                                      class="sf-textarea">{{ $bookingNotesVal }}</textarea>

                            @error('booking_notes')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Vehicle --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Vehicle Details
                    </h2>

                    <p class="sf-section-subtitle">
                        Link an existing vehicle or capture vehicle details manually.
                    </p>
                </div>

                <div class="sf-card-body space-y-6">

                    {{-- Existing Vehicle --}}
                    <div>
                        <label class="sf-label">
                            Existing Vehicle
                        </label>

                        <select name="vehicle_id" id="vehicle_id" class="sf-select">
                            <option value="">-- Select Existing Vehicle --</option>

                            @foreach($vehiclesCollection as $vehicle)
                                @php
                                    $vehicleLabel = trim(
                                        ($vehicle->year ? $vehicle->year . ' ' : '') .
                                        ($vehicle->make?->name ?? $vehicle->vehicleMake?->name ?? '') . ' ' .
                                        ($vehicle->model?->name ?? $vehicle->vehicleModel?->name ?? '') . ' ' .
                                        ($vehicle->plate_number ? '(' . $vehicle->plate_number . ')' : '')
                                    );
                                @endphp

                                <option value="{{ $vehicle->id }}"
                                        data-client-id="{{ $vehicle->client_id }}"
                                        @selected($selectedVehicleId === (string) $vehicle->id)>
                                    {{ $vehicleLabel ?: 'Vehicle #' . $vehicle->id }}
                                </option>
                            @endforeach
                        </select>

                        <p class="sf-help">
                            Existing vehicle list can be filtered by selected client.
                        </p>

                        @error('vehicle_id')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="sf-divider"></div>

                    {{-- Manual Vehicle --}}
                    <div>
                        <h3 class="sf-section-title">
                            Manual Vehicle Capture
                        </h3>

                        <p class="sf-section-subtitle">
                            Use this when the vehicle does not exist yet.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label class="sf-label">
                                Make
                            </label>

                            <select name="manual_make_id" id="manual_make_id" class="sf-select">
                                <option value="">-- Select Make --</option>

                                @foreach($makesCollection as $make)
                                    <option value="{{ $make->id }}" @selected($selectedManualMakeId === (string) $make->id)>
                                        {{ $make->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('manual_make_id')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="sf-label">
                                Model
                            </label>

                            <select name="manual_model_id" id="manual_model_id" class="sf-select">
                                <option value="">-- Select Model --</option>

                                @foreach($modelsCollection as $model)
                                    <option value="{{ $model->id }}"
                                            data-make-id="{{ $model->make_id }}"
                                            @selected($selectedManualModelId === (string) $model->id)>
                                        {{ $model->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('manual_model_id')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="sf-label">
                                Other Make
                            </label>

                            <input type="text"
                                   name="other_make"
                                   value="{{ $oldOr('other_make') }}"
                                   class="sf-input"
                                   placeholder="If make is not listed">

                            @error('other_make')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="sf-label">
                                Other Model
                            </label>

                            <input type="text"
                                   name="other_model"
                                   value="{{ $oldOr('other_model') }}"
                                   class="sf-input"
                                   placeholder="If model is not listed">

                            @error('other_model')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="sf-label">
                                Vehicle Year
                            </label>

                            <input type="text"
                                   name="vehicle_year"
                                   value="{{ $oldOr('vehicle_year') }}"
                                   class="sf-input"
                                   placeholder="2021">

                            @error('vehicle_year')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="sf-label">
                                Plate Number
                            </label>

                            <input type="text"
                                   name="plate_number"
                                   value="{{ $oldOr('plate_number') }}"
                                   class="sf-input"
                                   placeholder="Dubai A 12345">

                            @error('plate_number')
                                <div class="sf-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Services --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Services Needed
                    </h2>

                    <p class="sf-section-subtitle">
                        Select one or more services discussed with the customer.
                    </p>
                </div>

                <div class="sf-card-body space-y-5">

                    {{-- IMPORTANT: backend expects service_type as STRING --}}
                    <input type="hidden"
                           name="service_type"
                           id="service_type"
                           value="{{ old('service_type', implode(', ', $selectedServices)) }}">

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach($serviceList as $service)
                            <label class="rounded-2xl border border-white/10 bg-slate-950/60 p-4 transition hover:border-orange-400/30">
                                <div class="flex items-start gap-3">
                                    <input type="checkbox"
                                           value="{{ $service }}"
                                           data-service-checkbox
                                           @checked(in_array($service, $selectedServices, true))
                                           class="mt-1 rounded border-white/10 bg-slate-950 text-orange-500 shadow-sm focus:ring-orange-400">

                                    <span class="text-sm font-bold text-slate-200">
                                        {{ $service }}
                                    </span>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <div>
                        <label class="sf-label">
                            Custom Service
                        </label>

                        <input type="text"
                               name="custom_service_type"
                               id="custom_service_type"
                               value="{{ $customService }}"
                               class="sf-input"
                               placeholder="Enter custom service if not listed">

                        @error('custom_service_type')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    @error('service_type')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Notes --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Notes
                    </h2>
                </div>

                <div class="sf-card-body">
                    <textarea name="notes"
                              rows="5"
                              class="sf-textarea"
                              placeholder="Add internal notes, quotation context, customer preference, or follow-up details...">{{ $oldOr('notes') }}</textarea>

                    @error('notes')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- Submit --}}
            <div class="sf-card">
                <div class="sf-card-body">
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <a href="{{ route('admin.opportunities.index') }}" class="sf-btn-secondary">
                            Cancel
                        </a>

                        <button type="submit" class="sf-btn-primary">
                            {{ $isEdit ? 'Update Opportunity' : 'Create Opportunity' }}
                        </button>
                    </div>
                </div>
            </div>

        </div>

        {{-- Side Panel --}}
        <div class="space-y-6">

            {{-- Pipeline Notes --}}
            <div class="sf-card">
                <div class="sf-card-header">
                    <h2 class="sf-section-title">
                        Stage Rules
                    </h2>
                </div>

                <div class="sf-card-body">
                    <ul class="space-y-3 text-sm text-slate-300">
                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">1</span>
                            <span><strong class="text-white">Appointment Planned</strong> is only tentative planning.</span>
                        </li>

                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">2</span>
                            <span><strong class="text-white">Booking Confirmed</strong> means customer agreed and booking details must be captured.</span>
                        </li>

                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-extrabold text-orange-300 ring-1 ring-orange-400/20">3</span>
                            <span><strong class="text-white">Closed Lost</strong> should include a close reason.</span>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Current Snapshot --}}
            @if($isEdit && $opp)
                <div class="sf-card">
                    <div class="sf-card-header">
                        <h2 class="sf-section-title">
                            Current Snapshot
                        </h2>
                    </div>

                    <div class="sf-card-body space-y-4 text-sm">
                        <div>
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                                Opportunity
                            </div>

                            <div class="mt-1 font-extrabold text-white">
                                {{ $opp->title ?? 'Untitled Opportunity' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                                Client
                            </div>

                            <div class="mt-1 font-bold text-slate-200">
                                {{ $opp->client?->name ?? '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                                Stage
                            </div>

                            <div class="mt-1">
                                <span class="sf-badge-orange">
                                    {{ $stageOptions[$opp->stage] ?? ucwords(str_replace('_', ' ', $opp->stage ?? 'new')) }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">
                                Created
                            </div>

                            <div class="mt-1 font-bold text-slate-200">
                                {{ $opp->created_at?->format('d M Y, h:i A') ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="rounded-3xl border border-blue-400/20 bg-blue-500/10 p-5 shadow-xl shadow-black/20">
                <h3 class="font-extrabold text-blue-300">
                    Vehicle Tip
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-blue-100/80">
                    Use an existing vehicle when possible. Manual vehicle capture should be used only if the vehicle does not exist yet.
                </p>
            </div>

            <div class="rounded-3xl border border-orange-400/20 bg-orange-500/10 p-5 shadow-xl shadow-black/20">
                <h3 class="font-extrabold text-orange-300">
                    WhatsApp Flow
                </h3>

                <p class="mt-2 text-sm font-medium leading-6 text-orange-100/80">
                    Stage changes may trigger follow-up logic depending on your WhatsApp event mapping and automation setup.
                </p>
            </div>

        </div>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const stageSelect = document.getElementById('stage_select');
    const bookingWrap = document.getElementById('booking_confirmation_wrap');
    const closeReasonWrap = document.getElementById('close_reason_wrap');
    const closeReason = document.getElementById('close_reason');
    const bookingDate = document.getElementById('booking_date');
    const bookingSlot = document.getElementById('booking_slot');

    const expectedDate = document.getElementById('expected_close_date');
    const clientSelect = document.getElementById('client_id');
    const vehicleSelect = document.getElementById('vehicle_id');
    const makeSelect = document.getElementById('manual_make_id');
    const modelSelect = document.getElementById('manual_model_id');
    const titleInput = document.getElementById('opportunity_title');

    const serviceTypeInput = document.getElementById('service_type');
    const customServiceInput = document.getElementById('custom_service_type');

    function refreshStageFields() {
        const stage = stageSelect?.value || '';

        if (bookingWrap) {
            bookingWrap.classList.toggle('hidden', stage !== 'closed_won');
        }

        if (closeReasonWrap) {
            closeReasonWrap.classList.toggle('hidden', stage !== 'closed_lost');
        }

        if (closeReason) {
            if (stage === 'closed_lost') {
                closeReason.setAttribute('required', 'required');
            } else {
                closeReason.removeAttribute('required');
            }
        }

        if (bookingDate && bookingSlot) {
            if (stage === 'closed_won') {
                bookingDate.setAttribute('required', 'required');
                bookingSlot.setAttribute('required', 'required');

                if (!bookingDate.value && expectedDate?.value) {
                    bookingDate.value = expectedDate.value;
                }
            } else {
                bookingDate.removeAttribute('required');
                bookingSlot.removeAttribute('required');
            }
        }
    }

    function filterVehiclesByClient() {
        if (!clientSelect || !vehicleSelect) return;

        const selectedClientId = clientSelect.value;

        [...vehicleSelect.options].forEach(function (option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            const optionClientId = option.getAttribute('data-client-id');

            option.hidden = selectedClientId && optionClientId && optionClientId !== selectedClientId;
        });

        const selectedOption = vehicleSelect.options[vehicleSelect.selectedIndex];

        if (selectedOption && selectedOption.hidden) {
            vehicleSelect.value = '';
        }
    }

    function filterModelsByMake() {
        if (!makeSelect || !modelSelect) return;

        const selectedMakeId = makeSelect.value;

        [...modelSelect.options].forEach(function (option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            const optionMakeId = option.getAttribute('data-make-id');
            option.hidden = selectedMakeId && optionMakeId && optionMakeId !== selectedMakeId;
        });

        const selectedOption = modelSelect.options[modelSelect.selectedIndex];

        if (selectedOption && selectedOption.hidden) {
            modelSelect.value = '';
        }
    }

    function syncServiceTypeString() {
        if (!serviceTypeInput) return;

        const checkedServices = [...document.querySelectorAll('[data-service-checkbox]:checked')]
            .map(input => input.value)
            .filter(Boolean);

        const customService = customServiceInput?.value?.trim();

        if (customService) {
            checkedServices.push(customService);
        }

        serviceTypeInput.value = checkedServices.join(', ');
    }

    function autoTitle() {
        if (!titleInput || titleInput.dataset.manual === '1') return;

        const clientText = clientSelect?.selectedOptions?.[0]?.textContent?.trim()?.split(' - ')[0] || '';
        const vehicleText = vehicleSelect?.selectedOptions?.[0]?.textContent?.trim() || '';

        const checkedService = document.querySelector('[data-service-checkbox]:checked')?.value || customServiceInput?.value?.trim() || '';

        const parts = [clientText, vehicleText.replace('-- Select Existing Vehicle --', ''), checkedService]
            .map(part => part.trim())
            .filter(Boolean);

        if (parts.length) {
            titleInput.value = parts.join(' - ');
        }
    }

    titleInput?.addEventListener('input', function () {
        titleInput.dataset.manual = '1';
    });

    stageSelect?.addEventListener('change', refreshStageFields);

    clientSelect?.addEventListener('change', function () {
        filterVehiclesByClient();
        autoTitle();
    });

    vehicleSelect?.addEventListener('change', autoTitle);
    makeSelect?.addEventListener('change', filterModelsByMake);

    document.querySelectorAll('[data-service-checkbox]').forEach(function (input) {
        input.addEventListener('change', function () {
            syncServiceTypeString();
            autoTitle();
        });
    });

    customServiceInput?.addEventListener('input', function () {
        syncServiceTypeString();
        autoTitle();
    });

    document.querySelector('form')?.addEventListener('submit', function () {
        syncServiceTypeString();
    });

    refreshStageFields();
    filterVehiclesByClient();
    filterModelsByMake();
    syncServiceTypeString();
});
</script>
@endpush