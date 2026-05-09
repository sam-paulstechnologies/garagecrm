<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf

    @if(!empty($isEdit))
        @method('PUT')
    @endif

    @php
        $bk = $booking ?? null;

        $oldOr = function ($key, $default = null) use ($bk) {
            return old($key, $bk?->{$key} ?? $default);
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

        $selectedOpportunityId = (string) old('opportunity_id', $bk?->opportunity_id ?? '');
        $selectedClientId = (string) old('client_id', $bk?->client_id ?? '');
        $selectedVehicleId = (string) old('vehicle_id', $bk?->vehicle_id ?? '');
        $selectedAssignedTo = (string) old('assigned_to', $bk?->assigned_to ?? '');

        $selectedNewVehicleMakeId = (string) old('new_vehicle_make_id', '');
        $selectedNewVehicleModelId = (string) old('new_vehicle_model_id', '');

        $priorityVal = old('priority', $bk?->priority ?? 'medium');
        $slotVal = old('slot', $bk?->slot ?? 'morning');
        $statusVal = old('status', $bk?->status ?? (!empty($isEdit) ? 'pending' : 'scheduled'));
        $pickupRequiredVal = old('pickup_required', $bk?->pickup_required ? '1' : '0');

        $bookingDateVal = old('booking_date', $fmtDate($bk?->booking_date ?? null));
        $expectedCloseDateVal = old('expected_close_date', $fmtDate($bk?->expected_close_date ?? null));

        $lostReasonVal = old('lost_reason', $bk?->lost_reason ?? '');
        $allowOverbookingVal = old('allow_overbooking', '0');

        $bookingStatuses = $bookingStatuses ?? [
            'pending',
            'scheduled',
            'converted_to_job',
            'lost',
        ];

        $lostReasons = $lostReasons ?? [
            'cancelled_by_customer',
            'rejected_by_garage',
            'no_show',
            'slot_unavailable',
            'duplicate',
            'wrong_booking',
            'price_issue',
            'customer_postponed',
            'other',
        ];

        $statusLabels = [
            'pending' => 'Pending',
            'scheduled' => 'Scheduled',
            'converted_to_job' => 'Converted To Job',
            'lost' => 'Lost Booking',
        ];

        $statusHelp = [
            'pending' => 'Use when booking needs manager review, usually from WhatsApp / AI.',
            'scheduled' => 'Use when date and slot are confirmed manually by admin or manager.',
            'converted_to_job' => 'Use when vehicle is received and the work should move to Job module.',
            'lost' => 'Use when booking did not happen. A lost reason is required.',
        ];

        $lostReasonLabels = [
            'cancelled_by_customer' => 'Cancelled by customer',
            'rejected_by_garage' => 'Rejected by garage',
            'no_show' => 'No show',
            'slot_unavailable' => 'Slot unavailable',
            'duplicate' => 'Duplicate',
            'wrong_booking' => 'Wrong booking',
            'price_issue' => 'Price issue',
            'customer_postponed' => 'Customer postponed',
            'other' => 'Other',
        ];

        $opportunitiesForJs = collect($opportunities ?? [])
            ->map(function ($opportunity) {
                return [
                    'id' => (string) $opportunity->id,
                    'client_id' => (string) ($opportunity->client_id ?? ''),
                    'vehicle_id' => (string) ($opportunity->vehicle_id ?? ''),
                    'title' => $opportunity->title ?? '',
                    'service_type' => $opportunity->service_type ?? '',
                    'priority' => $opportunity->priority ?? 'medium',
                    'expected_close_date' => !empty($opportunity->expected_close_date)
                        ? \Illuminate\Support\Carbon::parse($opportunity->expected_close_date)->format('Y-m-d')
                        : '',
                ];
            })
            ->values();

        $clientsForJs = collect($clients ?? [])
            ->map(fn ($client) => [
                'id' => (string) $client->id,
                'name' => $client->name,
                'phone' => $client->phone ?? '',
            ])
            ->values();

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
                    'label' => $label !== '' ? $label : 'Vehicle #' . $vehicle->id,
                ];
            })
            ->values();

        $vehicleModelsForJs = collect($vehicleModels ?? [])
            ->map(fn ($model) => [
                'id' => (string) $model->id,
                'make_id' => (string) $model->make_id,
                'name' => $model->name,
            ])
            ->values();

        $slotUsageForJs = $slotUsage ?? [];

        $slotCapacitiesForJs = $slotCapacities ?? [
            'morning' => 3,
            'afternoon' => 3,
            'evening' => 3,
            'full_day' => 1,
        ];

        $sourceOpportunityLabel = $bk?->opportunity
            ? ('#' . $bk->opportunity->id . ' — ' . ($bk->opportunity->title ?? 'Opportunity'))
            : 'No opportunity linked';

        $sourceClientLabel = $bk?->client
            ? trim($bk->client->name . ($bk->client->phone ? ' — ' . $bk->client->phone : ''))
            : 'No client linked';
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

    {{-- Header --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">
                    {{ !empty($isEdit) ? 'Edit Booking #'.$bk?->id : 'Create Booking' }}
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    Confirm booking details before moving the work into a job.
                </p>
            </div>

            <a href="{{ route('admin.bookings.index') }}"
               class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-gray-100 text-gray-700 text-sm hover:bg-gray-200">
                ← Back to Bookings
            </a>
        </div>
    </div>

    {{-- Source Details --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Source Details
        </h3>

        @if(!empty($isEdit))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="bg-gray-50 border border-gray-100 rounded-lg p-4">
                    <div class="text-xs text-gray-500 mb-1">Opportunity</div>
                    <div class="font-semibold text-gray-900">
                        {{ $sourceOpportunityLabel }}
                    </div>

                    <p class="text-xs text-gray-500 mt-2">
                        Source opportunity is locked after booking creation.
                    </p>
                </div>

                <div class="bg-gray-50 border border-gray-100 rounded-lg p-4">
                    <div class="text-xs text-gray-500 mb-1">Client</div>
                    <div class="font-semibold text-gray-900">
                        {{ $sourceClientLabel }}
                    </div>

                    <p class="text-xs text-gray-500 mt-2">
                        Client is locked after booking creation.
                    </p>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Opportunity
                    </label>

                    <select id="opportunity_id"
                            name="opportunity_id"
                            class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                        <option value="">— None —</option>

                        @foreach(($opportunities ?? collect()) as $opportunity)
                            <option value="{{ $opportunity->id }}"
                                    data-client-id="{{ $opportunity->client_id }}"
                                    data-vehicle-id="{{ $opportunity->vehicle_id }}"
                                    @selected($selectedOpportunityId === (string) $opportunity->id)>
                                #{{ $opportunity->id }} — {{ $opportunity->title ?? 'Opportunity' }}
                            </option>
                        @endforeach
                    </select>

                    <p class="text-xs text-gray-500 mt-1">
                        Select the opportunity if this booking came from the opportunity pipeline.
                    </p>

                    @error('opportunity_id')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Client
                    </label>

                    <select id="client_id"
                            name="client_id"
                            class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                        <option value="">— Walk-in / New Client —</option>

                        @foreach(($clients ?? collect()) as $client)
                            <option value="{{ $client->id }}" @selected($selectedClientId === (string) $client->id)>
                                {{ $client->name }}{{ $client->phone ? ' — '.$client->phone : '' }}
                            </option>
                        @endforeach
                    </select>

                    <p class="text-xs text-gray-500 mt-1">
                        Leave empty for walk-in and fill the new client fields below.
                    </p>

                    @error('client_id')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div id="new_client_fields" class="hidden mt-5 border-t border-gray-100 pt-5">
                <div class="rounded-lg bg-blue-50 border border-blue-100 text-blue-800 px-4 py-3 text-sm mb-5">
                    Use this section only when the booking is for a new walk-in client.
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            New Client Name <span class="text-red-500">*</span>
                        </label>

                        <input type="text"
                               name="new_client_name"
                               value="{{ old('new_client_name') }}"
                               class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                        @error('new_client_name')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            New Client Phone
                        </label>

                        <input type="text"
                               name="new_client_phone"
                               value="{{ old('new_client_phone') }}"
                               class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                        @error('new_client_phone')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            New Client Email
                        </label>

                        <input type="email"
                               name="new_client_email"
                               value="{{ old('new_client_email') }}"
                               class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                        @error('new_client_email')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Booking Details --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Booking Details
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Booking Name <span class="text-red-500">*</span>
                </label>

                <input type="text"
                       name="name"
                       id="booking_name"
                       value="{{ $oldOr('name') }}"
                       required
                       placeholder="Example: Manjula - Cadillac Escalade - General Service"
                       class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                @error('name')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Priority
                </label>

                <select name="priority"
                        id="priority"
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

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Booking Date <span class="text-red-500">*</span>
                </label>

                <input type="date"
                       name="booking_date"
                       id="booking_date"
                       value="{{ $bookingDateVal }}"
                       required
                       class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                @error('booking_date')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Slot <span class="text-red-500">*</span>
                </label>

                <select name="slot"
                        id="slot"
                        required
                        class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                    <option value="morning" @selected($slotVal === 'morning')>Morning</option>
                    <option value="afternoon" @selected($slotVal === 'afternoon')>Afternoon</option>
                    <option value="evening" @selected($slotVal === 'evening')>Evening</option>
                    <option value="full_day" @selected($slotVal === 'full_day')>Full Day</option>
                </select>

                <p id="slot_capacity_help" class="text-xs text-gray-500 mt-1">
                    Slot capacity is checked automatically.
                </p>

                @error('slot')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Expected Duration (days)
                </label>

                <input type="number"
                       name="expected_duration"
                       value="{{ $oldOr('expected_duration', 1) }}"
                       min="1"
                       class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                @error('expected_duration')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Expected Close Date
                </label>

                <input type="date"
                       name="expected_close_date"
                       value="{{ $expectedCloseDateVal }}"
                       class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                @error('expected_close_date')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Service Type
                </label>

                <input type="text"
                       name="service_type"
                       id="service_type"
                       value="{{ $oldOr('service_type') }}"
                       placeholder="General Service, AC Repair, Detailing"
                       class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                @error('service_type')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Status
                </label>

                <select name="status"
                        id="status"
                        class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                    @foreach($bookingStatuses as $statusOption)
                        <option value="{{ $statusOption }}" @selected($statusVal === $statusOption)>
                            {{ $statusLabels[$statusOption] ?? ucwords(str_replace('_', ' ', $statusOption)) }}
                        </option>
                    @endforeach
                </select>

                <p id="status_help" class="text-xs text-gray-500 mt-1">
                    {{ $statusHelp[$statusVal] ?? 'Select booking status.' }}
                </p>

                @error('status')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div id="lost_reason_wrap" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Lost Reason <span class="text-red-500">*</span>
                </label>

                <select name="lost_reason"
                        id="lost_reason"
                        class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                    <option value="">— Select lost reason —</option>

                    @foreach($lostReasons as $reason)
                        <option value="{{ $reason }}" @selected($lostReasonVal === $reason)>
                            {{ $lostReasonLabels[$reason] ?? ucwords(str_replace('_', ' ', $reason)) }}
                        </option>
                    @endforeach
                </select>

                <p class="text-xs text-gray-500 mt-1">
                    This will help later for retention and marketing campaigns.
                </p>

                @error('lost_reason')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Vehicle / Assignment --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Vehicle & Assignment
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Existing Vehicle
                </label>

                <select name="vehicle_id"
                        id="vehicle_id"
                        class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                    <option value="">— No vehicle selected —</option>
                </select>

                <p class="text-xs text-gray-500 mt-1">
                    Select an existing vehicle, or capture a new vehicle below.
                </p>

                @error('vehicle_id')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Assigned To
                </label>

                <select name="assigned_to"
                        class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                    <option value="">— Unassigned —</option>

                    @foreach(($users ?? collect()) as $user)
                        <option value="{{ $user->id }}" @selected($selectedAssignedTo === (string) $user->id)>
                            {{ $user->name }}{{ $user->role ? ' — '.ucfirst($user->role) : '' }}
                        </option>
                    @endforeach
                </select>

                @error('assigned_to')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- New Vehicle Capture --}}
        <div id="new_vehicle_fields" class="mt-5 border-t border-gray-100 pt-5">
            <div class="rounded-lg bg-blue-50 border border-blue-100 text-blue-800 px-4 py-3 text-sm mb-5">
                If the vehicle is not listed, capture it here. It will be saved to the selected client profile and linked to this booking.
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Vehicle Make
                    </label>

                    <select name="new_vehicle_make_id"
                            id="new_vehicle_make_id"
                            class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                        <option value="">— Select Make —</option>

                        @foreach(($vehicleMakes ?? collect()) as $make)
                            <option value="{{ $make->id }}" @selected($selectedNewVehicleMakeId === (string) $make->id)>
                                {{ $make->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('new_vehicle_make_id')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Vehicle Model
                    </label>

                    <select name="new_vehicle_model_id"
                            id="new_vehicle_model_id"
                            class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                        <option value="">— Select Make First —</option>
                    </select>

                    @error('new_vehicle_model_id')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Plate Number
                    </label>

                    <input type="text"
                           name="new_vehicle_plate_number"
                           value="{{ old('new_vehicle_plate_number') }}"
                           placeholder="Example: Dubai A 12345"
                           class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                    @error('new_vehicle_plate_number')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Year
                    </label>

                    <input type="text"
                           name="new_vehicle_year"
                           value="{{ old('new_vehicle_year') }}"
                           placeholder="Example: 2022"
                           class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                    @error('new_vehicle_year')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Color
                    </label>

                    <input type="text"
                           name="new_vehicle_color"
                           value="{{ old('new_vehicle_color') }}"
                           placeholder="Example: White"
                           class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                    @error('new_vehicle_color')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Overbooking Exception --}}
    <div id="overbooking_section"
         class="hidden bg-white rounded-xl border border-yellow-200 shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Overbooking Exception
        </h3>

        <div class="rounded-lg bg-yellow-50 border border-yellow-100 text-yellow-800 px-4 py-3 text-sm mb-5">
            This section appears only when the selected date and slot is already full.
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Allow Overbooking?
                </label>

                <select name="allow_overbooking"
                        id="allow_overbooking"
                        class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                    <option value="0" @selected($allowOverbookingVal === '0')>No</option>
                    <option value="1" @selected($allowOverbookingVal === '1')>Yes</option>
                </select>

                <p class="text-xs text-gray-500 mt-1">
                    Only admin/manager should use this for exceptions.
                </p>
            </div>

            <div id="overbooking_reason_wrap" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Overbooking Reason <span class="text-red-500">*</span>
                </label>

                <input type="text"
                       name="overbooking_reason"
                       id="overbooking_reason"
                       value="{{ old('overbooking_reason') }}"
                       placeholder="Example: Manager approved urgent repeat customer"
                       class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                @error('overbooking_reason')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Pickup --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Pickup Details
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Pickup Required?
                </label>

                <select name="pickup_required"
                        id="pickup_required"
                        class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                    <option value="0" @selected($pickupRequiredVal === '0')>No</option>
                    <option value="1" @selected($pickupRequiredVal === '1')>Yes</option>
                </select>

                @error('pickup_required')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div id="pickup_fields" class="hidden mt-5 grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Pickup Address
                </label>

                <input type="text"
                       name="pickup_address"
                       value="{{ $oldOr('pickup_address') }}"
                       class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                @error('pickup_address')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Pickup Contact Number
                </label>

                <input type="text"
                       name="pickup_contact_number"
                       value="{{ $oldOr('pickup_contact_number') }}"
                       class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                @error('pickup_contact_number')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Notes --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Notes
        </h3>

        <textarea name="notes"
                  rows="5"
                  placeholder="Add booking notes..."
                  class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ $oldOr('notes') }}</textarea>

        @error('notes')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Actions --}}
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('admin.bookings.index') }}"
           class="text-sm text-gray-600 hover:underline">
            ← Back to Bookings
        </a>

        <button type="submit"
                class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
            {{ !empty($isEdit) ? 'Update Booking' : 'Create Booking' }}
        </button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const isEdit = @json(!empty($isEdit));

    const opportunities = @json($opportunitiesForJs);
    const clients = @json($clientsForJs);
    const vehicles = @json($vehiclesForJs);
    const vehicleModels = @json($vehicleModelsForJs);

    const slotUsage = @json($slotUsageForJs);
    const slotCapacities = @json($slotCapacitiesForJs);

    const selectedOpportunityId = @json($selectedOpportunityId);
    const selectedClientId = @json($selectedClientId);
    const selectedVehicleId = @json($selectedVehicleId);
    const selectedNewVehicleMakeId = @json($selectedNewVehicleMakeId);
    const selectedNewVehicleModelId = @json($selectedNewVehicleModelId);

    const statusHelpText = @json($statusHelp);

    const opportunitySelect = document.getElementById('opportunity_id');
    const clientSelect = document.getElementById('client_id');
    const vehicleSelect = document.getElementById('vehicle_id');
    const newClientFields = document.getElementById('new_client_fields');

    const bookingName = document.getElementById('booking_name');
    const priority = document.getElementById('priority');
    const bookingDate = document.getElementById('booking_date');
    const expectedCloseDate = document.querySelector('[name="expected_close_date"]');
    const serviceType = document.getElementById('service_type');

    const slotSelect = document.getElementById('slot');
    const slotCapacityHelp = document.getElementById('slot_capacity_help');

    const pickupRequired = document.getElementById('pickup_required');
    const pickupFields = document.getElementById('pickup_fields');

    const statusSelect = document.getElementById('status');
    const statusHelp = document.getElementById('status_help');
    const lostReasonWrap = document.getElementById('lost_reason_wrap');
    const lostReason = document.getElementById('lost_reason');

    const overbookingSection = document.getElementById('overbooking_section');
    const allowOverbooking = document.getElementById('allow_overbooking');
    const overbookingReasonWrap = document.getElementById('overbooking_reason_wrap');
    const overbookingReason = document.getElementById('overbooking_reason');

    const newVehicleFields = document.getElementById('new_vehicle_fields');
    const newVehicleMake = document.getElementById('new_vehicle_make_id');
    const newVehicleModel = document.getElementById('new_vehicle_model_id');

    let bookingNameTouched = false;

    bookingName?.addEventListener('input', function () {
        bookingNameTouched = true;
    });

    function getSelectedOpportunity() {
        const id = opportunitySelect?.value || '';

        return opportunities.find(function (opportunity) {
            return String(opportunity.id) === String(id);
        }) || null;
    }

    function getSelectedClientId() {
        if (isEdit) {
            return selectedClientId || '';
        }

        return clientSelect?.value || '';
    }

    function getSelectedClient() {
        const id = getSelectedClientId();

        return clients.find(function (client) {
            return String(client.id) === String(id);
        }) || null;
    }

    function populateVehicles() {
        if (!vehicleSelect) {
            return;
        }

        const clientId = getSelectedClientId();

        vehicleSelect.innerHTML = '';

        const empty = document.createElement('option');
        empty.value = '';
        empty.textContent = '— No vehicle selected —';
        vehicleSelect.appendChild(empty);

        vehicles
            .filter(function (vehicle) {
                return clientId && String(vehicle.client_id) === String(clientId);
            })
            .forEach(function (vehicle) {
                const option = document.createElement('option');
                option.value = vehicle.id;
                option.textContent = vehicle.label;

                if (String(vehicle.id) === String(selectedVehicleId)) {
                    option.selected = true;
                }

                vehicleSelect.appendChild(option);
            });

        syncNewVehicleFields();
    }

    function populateNewVehicleModels() {
        if (!newVehicleMake || !newVehicleModel) {
            return;
        }

        const makeId = newVehicleMake.value || '';

        newVehicleModel.innerHTML = '';

        const empty = document.createElement('option');
        empty.value = '';
        empty.textContent = makeId ? '— Select Model —' : '— Select Make First —';
        newVehicleModel.appendChild(empty);

        vehicleModels
            .filter(function (model) {
                return makeId && String(model.make_id) === String(makeId);
            })
            .forEach(function (model) {
                const option = document.createElement('option');
                option.value = model.id;
                option.textContent = model.name;

                if (String(model.id) === String(selectedNewVehicleModelId)) {
                    option.selected = true;
                }

                newVehicleModel.appendChild(option);
            });
    }

    function syncNewClientFields() {
        if (!newClientFields || !clientSelect) {
            return;
        }

        newClientFields.classList.toggle('hidden', !!clientSelect.value);
    }

    function syncNewVehicleFields() {
        if (!newVehicleFields || !vehicleSelect) {
            return;
        }

        const hasExistingVehicle = !!vehicleSelect.value;

        newVehicleFields.classList.toggle('hidden', hasExistingVehicle);

        if (hasExistingVehicle) {
            if (newVehicleMake) {
                newVehicleMake.value = '';
            }

            if (newVehicleModel) {
                newVehicleModel.innerHTML = '<option value="">— Select Make First —</option>';
            }
        }
    }

    function syncPickupFields() {
        if (!pickupFields || !pickupRequired) {
            return;
        }

        pickupFields.classList.toggle('hidden', pickupRequired.value !== '1');
    }

    function syncStatusFields() {
        const status = statusSelect?.value || '';

        if (statusHelp) {
            statusHelp.textContent = statusHelpText[status] || 'Select booking status.';
        }

        const isLost = status === 'lost';

        if (lostReasonWrap) {
            lostReasonWrap.classList.toggle('hidden', !isLost);
        }

        if (lostReason) {
            lostReason.required = isLost;

            if (!isLost) {
                lostReason.value = '';
            }
        }
    }

    function isSelectedSlotFull() {
        const date = bookingDate?.value || '';
        const slot = slotSelect?.value || '';

        if (!date || !slot) {
            return false;
        }

        const dayUsage = slotUsage[date] || {};
        const fullDayCount = Number(dayUsage.full_day || 0);

        if (slot !== 'full_day' && fullDayCount > 0) {
            return true;
        }

        if (slot === 'full_day') {
            const totalBookings = Object.values(dayUsage).reduce(function (sum, count) {
                return sum + Number(count || 0);
            }, 0);

            return totalBookings > 0;
        }

        const used = Number(dayUsage[slot] || 0);
        const capacity = Number(slotCapacities[slot] || 1);

        return used >= capacity;
    }

    function syncOverbookingFields() {
        const date = bookingDate?.value || '';
        const slot = slotSelect?.value || '';

        const dayUsage = slotUsage[date] || {};
        const used = Number(dayUsage[slot] || 0);
        const capacity = Number(slotCapacities[slot] || 1);
        const slotFull = isSelectedSlotFull();
        const isAllowed = allowOverbooking?.value === '1';

        if (slotCapacityHelp) {
            if (!date || !slot) {
                slotCapacityHelp.textContent = 'Slot capacity is checked automatically.';
            } else if (slot === 'full_day') {
                const totalBookings = Object.values(dayUsage).reduce(function (sum, count) {
                    return sum + Number(count || 0);
                }, 0);

                slotCapacityHelp.textContent = totalBookings > 0
                    ? 'This day already has bookings. Full Day needs overbooking approval.'
                    : 'Full Day is available.';
            } else if (slotFull) {
                slotCapacityHelp.textContent = `This ${slot} slot is full (${used}/${capacity}). Overbooking exception is available.`;
            } else {
                slotCapacityHelp.textContent = `Slot available (${used}/${capacity} used).`;
            }
        }

        if (overbookingSection) {
            overbookingSection.classList.toggle('hidden', !slotFull);
        }

        if (!slotFull && allowOverbooking) {
            allowOverbooking.value = '0';
        }

        if (overbookingReasonWrap) {
            overbookingReasonWrap.classList.toggle('hidden', !(slotFull && isAllowed));
        }

        if (overbookingReason) {
            overbookingReason.required = slotFull && isAllowed;

            if (!(slotFull && isAllowed)) {
                overbookingReason.value = '';
            }
        }
    }

    function syncBookingName() {
        if (!bookingName || bookingNameTouched) {
            return;
        }

        const client = getSelectedClient();
        const selectedVehicleOption = vehicleSelect?.selectedOptions?.[0]?.textContent?.trim() || '';
        const vehicleText = selectedVehicleOption.startsWith('—') ? '' : selectedVehicleOption;
        const service = serviceType?.value || '';

        const makeText = newVehicleMake?.selectedOptions?.[0]?.textContent?.trim() || '';
        const modelText = newVehicleModel?.selectedOptions?.[0]?.textContent?.trim() || '';

        const newVehicleText = [
            makeText && !makeText.startsWith('—') ? makeText : '',
            modelText && !modelText.startsWith('—') ? modelText : '',
        ].filter(Boolean).join(' ');

        const parts = [
            client?.name || '',
            vehicleText || newVehicleText,
            service.split(',')[0] || '',
        ].filter(Boolean);

        if (parts.length) {
            bookingName.value = parts.join(' - ');
        }
    }

    function applyOpportunityData() {
        if (isEdit) {
            return;
        }

        const opportunity = getSelectedOpportunity();

        if (!opportunity) {
            return;
        }

        if (clientSelect && opportunity.client_id) {
            clientSelect.value = opportunity.client_id;
        }

        if (priority && opportunity.priority) {
            priority.value = opportunity.priority;
        }

        if (serviceType && opportunity.service_type) {
            serviceType.value = opportunity.service_type;
        }

        if (bookingDate && opportunity.expected_close_date && !bookingDate.value) {
            bookingDate.value = opportunity.expected_close_date;
        }

        if (expectedCloseDate && opportunity.expected_close_date && !expectedCloseDate.value) {
            expectedCloseDate.value = opportunity.expected_close_date;
        }

        populateVehicles();

        if (vehicleSelect && opportunity.vehicle_id) {
            vehicleSelect.value = opportunity.vehicle_id;
        }

        syncNewClientFields();
        syncNewVehicleFields();
        syncBookingName();
        syncOverbookingFields();
    }

    opportunitySelect?.addEventListener('change', function () {
        applyOpportunityData();
    });

    clientSelect?.addEventListener('change', function () {
        populateVehicles();
        syncNewClientFields();
        syncBookingName();
    });

    vehicleSelect?.addEventListener('change', function () {
        syncNewVehicleFields();
        syncBookingName();
    });

    newVehicleMake?.addEventListener('change', function () {
        populateNewVehicleModels();
        syncBookingName();
    });

    newVehicleModel?.addEventListener('change', syncBookingName);

    serviceType?.addEventListener('input', syncBookingName);
    pickupRequired?.addEventListener('change', syncPickupFields);
    statusSelect?.addEventListener('change', syncStatusFields);
    allowOverbooking?.addEventListener('change', syncOverbookingFields);
    bookingDate?.addEventListener('change', syncOverbookingFields);
    slotSelect?.addEventListener('change', syncOverbookingFields);

    populateVehicles();

    if (selectedVehicleId && vehicleSelect) {
        vehicleSelect.value = selectedVehicleId;
    }

    if (newVehicleMake && selectedNewVehicleMakeId) {
        newVehicleMake.value = selectedNewVehicleMakeId;
    }

    populateNewVehicleModels();

    if (newVehicleModel && selectedNewVehicleModelId) {
        newVehicleModel.value = selectedNewVehicleModelId;
    }

    if (selectedOpportunityId) {
        applyOpportunityData();
    }

    syncNewClientFields();
    syncNewVehicleFields();
    syncPickupFields();
    syncStatusFields();
    syncOverbookingFields();
    syncBookingName();
});
</script>