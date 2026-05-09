{{-- resources/views/admin/opportunities/form.blade.php --}}

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf

    @if($isEdit)
        @method('PUT')
    @endif

    @php
        $opp = $opportunity ?? null;

        $oldOr = function ($key, $fallback = null) use ($opp) {
            return old($key, $opp?->{$key} ?? $fallback);
        };

        $stageVal = $oldOr('stage', 'new');
        $priorityVal = $oldOr('priority', 'medium');

        $selectedClientId = (string) old('client_id', $opp?->client_id ?? '');
        $selectedVehicleId = (string) old('vehicle_id', $opp?->vehicle_id ?? '');
        $selectedAssignedTo = (string) old('assigned_to', $opp?->assigned_to ?? '');

        $servicesInitial = collect(explode(',', (string) $oldOr('service_type', '')))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->values()
            ->all();

        $stageOptions = [
            'new' => 'New',
            'attempting_contact' => 'Attempting Contact',
            'manager_confirmation_pending' => 'Manager Confirmation Pending',
            'appointment' => 'Appointment Planned',
            'closed_won' => 'Booking Confirmed',
            'closed_lost' => 'Closed Lost',
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

        $customService = collect($servicesInitial)
            ->first(fn ($s) => ! in_array($s, $serviceList, true));

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

        $closeReasonVal = old('close_reason', $opp?->close_reason ?? '');

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

        /*
        |--------------------------------------------------------------------------
        | Booking Confirmation Defaults
        |--------------------------------------------------------------------------
        | If booking date is not posted yet, default from expected_close_date so
        | Appointment Planned date can be reused but still actively confirmed.
        |--------------------------------------------------------------------------
        */
        $bookingDateVal = old('booking_date', $fmtDate($opp?->expected_close_date ?? null));
        $bookingSlotVal = old('booking_slot', '');
        $bookingNotesVal = old('booking_notes', '');

        $vehiclesForJs = collect($vehicles ?? [])
            ->map(function ($vehicle) {
                $make = $vehicle->make?->name ?? '';
                $model = $vehicle->model?->name ?? '';

                $label = trim(
                    $make . ' ' .
                    $model . ' ' .
                    ($vehicle->plate_number ? '(' . $vehicle->plate_number . ')' : '')
                );

                return [
                    'id' => (string) $vehicle->id,
                    'client_id' => (string) $vehicle->client_id,
                    'make' => $make,
                    'model' => $model,
                    'plate_number' => $vehicle->plate_number,
                    'label' => $label !== '' ? $label : 'Vehicle #' . $vehicle->id,
                ];
            })
            ->values();

        $clientsForJs = collect($clients ?? [])
            ->map(fn ($client) => [
                'id' => (string) $client->id,
                'name' => $client->name,
            ])
            ->values();

        $modelsByMakeForJs = collect($models ?? [])
            ->groupBy('make_id')
            ->map(fn ($group) => $group->map(fn ($model) => [
                'id' => (string) $model->id,
                'name' => $model->name,
                'make_id' => (string) $model->make_id,
            ])->values())
            ->toArray();

        $selectedManualMakeId = (string) old('manual_make_id', '');
        $selectedManualModelId = (string) old('manual_model_id', '');
    @endphp

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-100 text-red-800 px-4 py-3 text-sm">
            <div class="font-semibold mb-1">Please fix the following:</div>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header Card --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">
                    {{ $isEdit ? 'Edit Opportunity' : 'Create Opportunity' }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Move the customer from opportunity to appointment, booking confirmation, job, and invoice.
                </p>
            </div>

            <a href="{{ route('admin.opportunities.index') }}"
               class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm hover:bg-gray-200">
                ← Back to Opportunities
            </a>
        </div>
    </div>

    {{-- Pipeline Guide --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">
            Pipeline Guide
        </h3>

        <div class="text-sm text-gray-600">
            New → Attempting Contact → Manager Confirmation Pending → Appointment Planned → Booking Confirmed → Booking → Job → Invoice
        </div>

        <div class="mt-3 rounded-lg bg-yellow-50 border border-yellow-100 text-yellow-800 px-4 py-3 text-sm">
            <strong>Note:</strong> Appointment Planned is not a booking. Select <strong>Booking Confirmed</strong> only when the customer has agreed to proceed, then confirm the actual booking date and slot.
        </div>
    </div>

    {{-- Basic Details --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Basic Details
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            {{-- Client --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Client <span class="text-red-500">*</span>
                </label>

                @if($isEdit)
                    <input type="hidden" name="client_id" id="client_id" value="{{ $opp?->client_id }}">

                    <input type="text"
                           value="{{ $opp?->client?->name ?? 'Client' }}"
                           class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 text-sm"
                           readonly>
                @else
                    <select name="client_id"
                            id="client_id"
                            required
                            class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                        <option value="">-- Select Client --</option>

                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" @selected($selectedClientId === (string) $client->id)>
                                {{ $client->name }}{{ $client->phone ? ' - '.$client->phone : '' }}
                            </option>
                        @endforeach
                    </select>
                @endif

                @error('client_id')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Lead --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Lead
                </label>

                @if($isEdit)
                    <input type="text"
                           value="{{ $opp?->lead?->name ?? '—' }}"
                           class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 text-sm"
                           readonly>
                @else
                    <select name="lead_id"
                            class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                        <option value="">-- None --</option>

                        @foreach($leads as $lead)
                            <option value="{{ $lead->id }}" @selected(old('lead_id', $opp?->lead_id ?? '') == $lead->id)>
                                {{ $lead->name }}{{ $lead->phone ? ' - '.$lead->phone : '' }}
                            </option>
                        @endforeach
                    </select>
                @endif

                @error('lead_id')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Title --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Opportunity Title <span class="text-red-500">*</span>
                </label>

                <input type="text"
                       name="title"
                       id="opportunity_title"
                       value="{{ $oldOr('title') }}"
                       required
                       placeholder="Example: Manjula - Cadillac Escalade - General Service"
                       class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                <p class="text-xs text-gray-500 mt-1">
                    Title auto-updates when client, vehicle, or service changes. You can still edit it manually.
                </p>

                @error('title')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Pipeline Details --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Pipeline Details
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            {{-- Stage --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Stage <span class="text-red-500">*</span>
                </label>

                <select name="stage"
                        id="stage_select"
                        required
                        class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                    @foreach($stageOptions as $value => $label)
                        <option value="{{ $value }}" @selected($stageVal === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                <p id="stage_help" class="text-xs text-gray-500 mt-1">
                    Select the current stage of this customer opportunity.
                </p>

                @error('stage')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Priority --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Priority
                </label>

                <select name="priority"
                        class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                    <option value="low" @selected($priorityVal === 'low')>Low</option>
                    <option value="medium" @selected($priorityVal === 'medium')>Medium</option>
                    <option value="high" @selected($priorityVal === 'high')>High</option>
                    <option value="urgent" @selected($priorityVal === 'urgent')>Urgent</option>
                </select>

                @error('priority')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tentative Appointment / Planning Date --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Tentative Appointment / Planning Date
                </label>

                <input type="date"
                       name="expected_close_date"
                       id="expected_close_date"
                       value="{{ old('expected_close_date', $fmtDate($opp?->expected_close_date ?? null)) }}"
                       class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                <p class="text-xs text-gray-500 mt-1">
                    This is only a tentative/planning date. Confirmed booking date is captured below when stage is Booking Confirmed.
                </p>

                @error('expected_close_date')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Value --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Estimated Value (AED)
                </label>

                <input type="number"
                       step="0.01"
                       min="0"
                       name="value"
                       value="{{ $oldOr('value') }}"
                       placeholder="0.00"
                       class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                @error('value')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Assigned To --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Owner / Manager
                </label>

                <select name="assigned_to"
                        class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                    <option value="">Unassigned</option>

                    @if(isset($users))
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected($selectedAssignedTo === (string) $user->id)>
                                {{ $user->name }}{{ $user->role ? ' - '.ucfirst($user->role) : '' }}
                            </option>
                        @endforeach
                    @endif
                </select>

                <p class="text-xs text-gray-500 mt-1">
                    Only admin and manager users are shown here.
                </p>

                @error('assigned_to')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Close Reason --}}
            <div id="close_reason_wrap" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Close Reason <span class="text-red-500">*</span>
                </label>

                <select name="close_reason"
                        id="close_reason"
                        class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                    <option value="">-- Select reason --</option>

                    @foreach($closeReasonOptions as $reason)
                        <option value="{{ $reason }}" @selected($closeReasonVal === $reason)>
                            {{ $reason }}
                        </option>
                    @endforeach
                </select>

                <p class="text-xs text-gray-500 mt-1">
                    Used later for retention and marketing campaigns.
                </p>

                @error('close_reason')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Booking Confirmation --}}
    <div id="booking_confirmation_wrap"
         class="hidden bg-white rounded-xl border border-green-100 shadow-sm p-5">
        <div class="flex items-start justify-between gap-4 mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">
                    Booking Confirmation
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                    Required only when stage is <strong>Booking Confirmed</strong>. This creates or updates the booking record.
                </p>
            </div>

            <span class="inline-flex px-3 py-1 rounded-full bg-green-50 text-green-700 text-xs font-semibold">
                Customer Agreed
            </span>
        </div>

        <div class="rounded-lg bg-green-50 border border-green-100 text-green-800 px-4 py-3 text-sm mb-5">
            Confirm the real booking date and slot here. Do not rely only on the tentative appointment date above.
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            {{-- Booking Date --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Confirmed Booking Date <span class="text-red-500">*</span>
                </label>

                <input type="date"
                       name="booking_date"
                       id="booking_date"
                       value="{{ $bookingDateVal }}"
                       class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                @error('booking_date')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Booking Slot --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Confirmed Slot <span class="text-red-500">*</span>
                </label>

                <select name="booking_slot"
                        id="booking_slot"
                        class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                    <option value="">-- Select Slot --</option>
                    <option value="morning" @selected($bookingSlotVal === 'morning')>Morning</option>
                    <option value="afternoon" @selected($bookingSlotVal === 'afternoon')>Afternoon</option>
                    <option value="evening" @selected($bookingSlotVal === 'evening')>Evening</option>
                </select>

                @error('booking_slot')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Booking Notes --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Booking Notes
                </label>

                <textarea name="booking_notes"
                          id="booking_notes"
                          rows="3"
                          placeholder="Example: Customer requested pickup from office basement parking."
                          class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ $bookingNotesVal }}</textarea>

                @error('booking_notes')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Vehicle --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Vehicle
        </h3>

        <div class="rounded-lg bg-blue-50 border border-blue-100 text-blue-800 px-4 py-3 text-sm mb-5">
            Select an existing vehicle if already available. For new customers, enter vehicle details below and the system will create the vehicle under this client.
        </div>

        {{-- Existing Vehicle --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Existing Vehicle
                </label>

                <select name="vehicle_id"
                        id="vehicle_id"
                        class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                    <option value="">-- No existing vehicle selected --</option>
                </select>

                <p class="text-xs text-gray-500 mt-1">
                    Existing vehicles are filtered based on selected client.
                </p>

                @error('vehicle_id')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-lg bg-gray-50 border border-gray-100 p-4 text-sm text-gray-600">
                <div class="font-medium text-gray-800 mb-1">Selected / Current Vehicle</div>
                <div id="current_vehicle_label">—</div>
            </div>
        </div>

        {{-- Manual Vehicle Capture --}}
        <div class="mt-6 border-t border-gray-100 pt-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h4 class="text-base font-semibold text-gray-900">
                        Create / Update Vehicle From Opportunity
                    </h4>
                    <p class="text-sm text-gray-500 mt-1">
                        Use this when the lead came in without vehicle details or the customer is new.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- Make --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Vehicle Make
                    </label>

                    <select name="manual_make_id"
                            id="manual_make_id"
                            class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                        <option value="">-- Select Make --</option>

                        @foreach(($makes ?? collect()) as $make)
                            <option value="{{ $make->id }}" @selected($selectedManualMakeId === (string) $make->id)>
                                {{ $make->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('manual_make_id')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Model --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Vehicle Model
                    </label>

                    <select name="manual_model_id"
                            id="manual_model_id"
                            class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                        <option value="">-- Select make first --</option>
                    </select>

                    @error('manual_model_id')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Year --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Year
                    </label>

                    <input type="text"
                           name="manual_year"
                           id="manual_year"
                           value="{{ old('manual_year') }}"
                           placeholder="Example: 2022"
                           class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                    @error('manual_year')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Color --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Color
                    </label>

                    <input type="text"
                           name="manual_color"
                           value="{{ old('manual_color') }}"
                           placeholder="Example: Black"
                           class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                    @error('manual_color')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Plate --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Plate Number
                    </label>

                    <input type="text"
                           name="manual_plate_number"
                           value="{{ old('manual_plate_number') }}"
                           placeholder="Example: Dubai A 12345"
                           class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                    @error('manual_plate_number')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- VIN --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        VIN
                    </label>

                    <input type="text"
                           name="manual_vin"
                           value="{{ old('manual_vin') }}"
                           maxlength="17"
                           placeholder="17-character VIN"
                           class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                    @error('manual_vin')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Mileage --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Current Mileage
                    </label>

                    <input type="number"
                           name="manual_current_mileage"
                           value="{{ old('manual_current_mileage') }}"
                           min="0"
                           placeholder="Example: 45000"
                           class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                    @error('manual_current_mileage')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Registration Expiry --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Mulkia / Registration Expiry
                    </label>

                    <input type="date"
                           name="manual_registration_expiry_date"
                           value="{{ old('manual_registration_expiry_date') }}"
                           class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                    @error('manual_registration_expiry_date')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Insurance Expiry --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Insurance Expiry
                    </label>

                    <input type="date"
                           name="manual_insurance_expiry_date"
                           value="{{ old('manual_insurance_expiry_date') }}"
                           class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                    @error('manual_insurance_expiry_date')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Services --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Services
        </h3>

        <input type="hidden"
               name="service_type"
               id="service_type"
               value="{{ e($oldOr('service_type', '')) }}">

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($serviceList as $service)
                @php
                    $isChecked = $service === 'Other'
                        ? (bool) $customService || in_array('Other', $servicesInitial, true)
                        : in_array($service, $servicesInitial, true);
                @endphp

                <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm hover:bg-gray-50">
                    <input type="checkbox"
                           class="svc-checkbox rounded border-gray-300"
                           value="{{ $service }}"
                           @checked($isChecked)>

                    <span>{{ $service }}</span>
                </label>
            @endforeach
        </div>

        <input type="text"
               id="other_service_input"
               value="{{ $customService }}"
               class="mt-3 hidden w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
               placeholder="Specify other service">
    </div>

    {{-- Notes --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Notes
        </h3>

        <textarea name="notes"
                  rows="4"
                  placeholder="Add opportunity notes..."
                  class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ $oldOr('notes') }}</textarea>

        @error('notes')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Actions --}}
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('admin.opportunities.index') }}"
           class="text-sm text-gray-600 hover:underline">
            ← Back to Opportunities
        </a>

        <button type="submit"
                class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
            {{ $isEdit ? 'Update Opportunity' : 'Create Opportunity' }}
        </button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const vehicles = @json($vehiclesForJs);
    const clients = @json($clientsForJs);
    const modelsByMake = @json($modelsByMakeForJs);

    const selectedVehicleId = @json($selectedVehicleId);
    const selectedClientId = @json($selectedClientId);
    const selectedManualMakeId = @json($selectedManualMakeId);
    const selectedManualModelId = @json($selectedManualModelId);

    const clientSelect = document.getElementById('client_id');
    const vehicleSelect = document.getElementById('vehicle_id');
    const currentVehicleLabel = document.getElementById('current_vehicle_label');

    const manualMakeSelect = document.getElementById('manual_make_id');
    const manualModelSelect = document.getElementById('manual_model_id');

    const titleInput = document.getElementById('opportunity_title');

    const serviceCheckboxes = Array.from(document.querySelectorAll('.svc-checkbox'));
    const serviceHidden = document.getElementById('service_type');
    const otherServiceInput = document.getElementById('other_service_input');

    const stageSelect = document.getElementById('stage_select');
    const stageHelp = document.getElementById('stage_help');

    const closeReasonWrap = document.getElementById('close_reason_wrap');
    const closeReason = document.getElementById('close_reason');

    const bookingConfirmationWrap = document.getElementById('booking_confirmation_wrap');
    const bookingDate = document.getElementById('booking_date');
    const bookingSlot = document.getElementById('booking_slot');
    const bookingNotes = document.getElementById('booking_notes');
    const expectedCloseDate = document.getElementById('expected_close_date');

    let titleTouched = false;

    const stageHelpText = {
        new: 'New opportunity. Customer has not been contacted yet.',
        attempting_contact: 'Team is trying to contact or follow up with the customer.',
        manager_confirmation_pending: 'Manager needs to confirm before the opportunity moves forward.',
        appointment: 'Appointment is planned or being discussed. This is not yet a confirmed booking.',
        closed_won: 'Booking Confirmed. Customer agreed to proceed. Confirm actual booking date and slot below.',
        closed_lost: 'Customer did not proceed, cancelled, or the opportunity is no longer valid.'
    };

    titleInput?.addEventListener('input', function () {
        titleTouched = true;
    });

    function getClientId() {
        return clientSelect?.value || selectedClientId || '';
    }

    function getClientName() {
        const clientId = getClientId();

        const found = clients.find(function (client) {
            return String(client.id) === String(clientId);
        });

        if (found?.name) {
            return found.name;
        }

        if (clientSelect && clientSelect.options && clientSelect.selectedIndex >= 0) {
            const text = clientSelect.options[clientSelect.selectedIndex].textContent || '';
            return text.split(' - ')[0].trim();
        }

        return '';
    }

    function getSelectedVehicle() {
        const vehicleId = vehicleSelect?.value || '';

        return vehicles.find(function (vehicle) {
            return String(vehicle.id) === String(vehicleId);
        }) || null;
    }

    function populateVehicles() {
        if (!vehicleSelect) {
            return;
        }

        const clientId = getClientId();

        vehicleSelect.innerHTML = '';

        const emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.textContent = '-- No existing vehicle selected --';
        vehicleSelect.appendChild(emptyOption);

        const filtered = vehicles.filter(function (vehicle) {
            if (!clientId) {
                return false;
            }

            return String(vehicle.client_id) === String(clientId);
        });

        filtered.forEach(function (vehicle) {
            const option = document.createElement('option');
            option.value = vehicle.id;
            option.textContent = vehicle.label;

            if (String(selectedVehicleId) === String(vehicle.id)) {
                option.selected = true;
            }

            vehicleSelect.appendChild(option);
        });

        syncCurrentVehicle();
    }

    function syncCurrentVehicle() {
        const vehicle = getSelectedVehicle();

        if (currentVehicleLabel) {
            currentVehicleLabel.textContent = vehicle ? vehicle.label : '—';
        }
    }

    function populateManualModels(makeId, preselectId = '') {
        if (!manualModelSelect) {
            return;
        }

        manualModelSelect.innerHTML = '';

        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = makeId ? '-- Select Model --' : '-- Select make first --';
        manualModelSelect.appendChild(defaultOption);

        if (!makeId || !modelsByMake[makeId]) {
            return;
        }

        modelsByMake[makeId].forEach(function (model) {
            const option = document.createElement('option');
            option.value = model.id;
            option.textContent = model.name;

            if (String(preselectId) === String(model.id)) {
                option.selected = true;
            }

            manualModelSelect.appendChild(option);
        });
    }

    function getManualVehicleName() {
        const makeName = manualMakeSelect?.selectedOptions?.[0]?.textContent?.trim() || '';
        const modelName = manualModelSelect?.selectedOptions?.[0]?.textContent?.trim() || '';

        const cleanMake = makeName.startsWith('--') ? '' : makeName;
        const cleanModel = modelName.startsWith('--') ? '' : modelName;

        return [cleanMake, cleanModel].filter(Boolean).join(' ').trim();
    }

    function getSelectedServices() {
        const selected = [];

        serviceCheckboxes.forEach(function (checkbox) {
            if (checkbox.checked) {
                selected.push(checkbox.value);
            }
        });

        const hasOther = selected.includes('Other');

        if (otherServiceInput) {
            otherServiceInput.classList.toggle('hidden', !hasOther);

            if (hasOther && otherServiceInput.value.trim() !== '') {
                selected[selected.indexOf('Other')] = otherServiceInput.value.trim();
            }
        }

        return selected;
    }

    function syncServices() {
        const selected = getSelectedServices();

        if (serviceHidden) {
            serviceHidden.value = selected.join(', ');
        }

        syncTitle();
    }

    function syncTitle() {
        if (!titleInput || titleTouched) {
            return;
        }

        const clientName = getClientName();

        const selectedVehicle = getSelectedVehicle();

        const existingVehicleName = selectedVehicle
            ? [selectedVehicle.make, selectedVehicle.model].filter(Boolean).join(' ').trim()
            : '';

        const manualVehicleName = getManualVehicleName();

        const vehicleName = existingVehicleName || manualVehicleName;

        const services = getSelectedServices();
        const firstService = services.length ? services[0] : '';

        const parts = [clientName, vehicleName, firstService]
            .map(function (part) {
                return (part || '').trim();
            })
            .filter(Boolean);

        if (parts.length) {
            titleInput.value = parts.join(' - ');
        }
    }

    function syncStageHelp() {
        if (!stageSelect || !stageHelp) {
            return;
        }

        const selectedStage = stageSelect.value;

        stageHelp.textContent = stageHelpText[selectedStage] || 'Select the current stage of this customer opportunity.';

        const isClosedLost = selectedStage === 'closed_lost';
        const isBookingConfirmed = selectedStage === 'closed_won';

        if (closeReasonWrap) {
            closeReasonWrap.classList.toggle('hidden', !isClosedLost);
        }

        if (closeReason) {
            closeReason.required = isClosedLost;

            if (!isClosedLost) {
                closeReason.value = '';
            }
        }

        if (bookingConfirmationWrap) {
            bookingConfirmationWrap.classList.toggle('hidden', !isBookingConfirmed);
        }

        if (bookingDate) {
            bookingDate.required = isBookingConfirmed;

            if (isBookingConfirmed && !bookingDate.value && expectedCloseDate?.value) {
                bookingDate.value = expectedCloseDate.value;
            }
        }

        if (bookingSlot) {
            bookingSlot.required = isBookingConfirmed;
        }

        if (!isBookingConfirmed) {
            if (bookingDate) {
                bookingDate.required = false;
            }

            if (bookingSlot) {
                bookingSlot.required = false;
            }
        }
    }

    clientSelect?.addEventListener('change', function () {
        populateVehicles();
        syncTitle();
    });

    vehicleSelect?.addEventListener('change', function () {
        syncCurrentVehicle();
        syncTitle();
    });

    manualMakeSelect?.addEventListener('change', function () {
        populateManualModels(manualMakeSelect.value);
        syncTitle();
    });

    manualModelSelect?.addEventListener('change', syncTitle);

    serviceCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', syncServices);
    });

    otherServiceInput?.addEventListener('input', syncServices);
    stageSelect?.addEventListener('change', syncStageHelp);

    expectedCloseDate?.addEventListener('change', function () {
        if (stageSelect?.value === 'closed_won' && bookingDate && !bookingDate.value) {
            bookingDate.value = expectedCloseDate.value;
        }
    });

    populateVehicles();
    populateManualModels(selectedManualMakeId || manualMakeSelect?.value || '', selectedManualModelId);
    syncServices();
    syncStageHelp();
    syncTitle();
});
</script>