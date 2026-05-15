{{-- resources/views/admin/bookings/partials/form.blade.php --}}

@php
    $bk = $booking ?? null;
    $isEdit = !empty($isEdit);

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

    $selectedOpportunityId = (string) old('opportunity_id', $bk?->opportunity_id ?? request('opportunity_id', ''));
    $selectedClientId = (string) old('client_id', $bk?->client_id ?? request('client_id', ''));
    $selectedVehicleId = (string) old('vehicle_id', $bk?->vehicle_id ?? request('vehicle_id', ''));
    $selectedAssignedTo = (string) old('assigned_to', $bk?->assigned_to ?? '');
    $selectedNewVehicleMakeId = (string) old('new_vehicle_make_id', '');
    $selectedNewVehicleModelId = (string) old('new_vehicle_model_id', '');

    $priorityVal = old('priority', $bk?->priority ?? 'medium');
    $slotVal = old('slot', $bk?->slot ?? 'morning');
    $statusVal = old('status', $bk?->status ?? ($isEdit ? 'pending' : 'scheduled'));
    $pickupRequiredVal = old('pickup_required', $bk?->pickup_required ? '1' : '0');
    $allowOverbookingVal = old('allow_overbooking', '0');

    $bookingDateVal = old('booking_date', $fmtDate($bk?->booking_date ?? $bk?->scheduled_at ?? null));
    $expectedCloseDateVal = old('expected_close_date', $fmtDate($bk?->expected_close_date ?? null));

    $lostReasonVal = old('lost_reason', $bk?->lost_reason ?? '');

    $bookingStatuses = $bookingStatuses ?? [
        'pending',
        'scheduled',
        'confirmed',
        'converted_to_job',
        'lost',
    ];

    $statusLabels = [
        'pending' => 'Pending',
        'scheduled' => 'Scheduled',
        'confirmed' => 'Confirmed',
        'converted_to_job' => 'Converted To Job',
        'lost' => 'Lost Booking',
    ];

    $statusHelp = [
        'pending' => 'Use when booking needs manager review.',
        'scheduled' => 'Use when date and slot are confirmed.',
        'confirmed' => 'Use when customer confirmation is completed.',
        'converted_to_job' => 'Use when vehicle is received and work moves to Job module.',
        'lost' => 'Use when booking did not happen. Lost reason is required.',
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

    $priorityOptions = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    $slotOptions = [
        'morning' => 'Morning',
        'afternoon' => 'Afternoon',
        'evening' => 'Evening',
        'full_day' => 'Full Day',
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

    $vehiclesForJs = collect($vehicles ?? [])
        ->map(function ($vehicle) {
            $make = $vehicle->make?->name ?? $vehicle->vehicleMake?->name ?? '';
            $model = $vehicle->model?->name ?? $vehicle->vehicleModel?->name ?? '';

            $label = trim(
                ($vehicle->year ? $vehicle->year . ' ' : '') .
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

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf

    @if($isEdit)
        @method('PUT')
    @endif

    {{-- Source Details --}}
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
                    <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                            Opportunity
                        </div>

                        <div class="mt-1 font-extrabold text-white">
                            {{ $sourceOpportunityLabel }}
                        </div>

                        <p class="mt-2 text-xs font-medium text-slate-500">
                            Source opportunity is locked after booking creation.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-950/60 p-4">
                        <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                            Client
                        </div>

                        <div class="mt-1 font-extrabold text-white">
                            {{ $sourceClientLabel }}
                        </div>

                        <p class="mt-2 text-xs font-medium text-slate-500">
                            Client is locked after booking creation.
                        </p>
                    </div>
                </div>

                <input type="hidden" name="opportunity_id" value="{{ $bk?->opportunity_id }}">
                <input type="hidden" name="client_id" value="{{ $bk?->client_id }}">
            @else
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    {{-- Opportunity --}}
                    <div>
                        <label for="opportunity_id" class="sf-label">
                            Opportunity
                        </label>

                        <select id="opportunity_id"
                                name="opportunity_id"
                                class="sf-select">
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

                        @error('opportunity_id')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror

                        <p class="sf-help">
                            Selecting an opportunity can auto-fill client, vehicle, priority, and date.
                        </p>
                    </div>

                    {{-- Client --}}
                    <div>
                        <label for="client_id" class="sf-label">
                            Client <span class="text-red-300">*</span>
                        </label>

                        <select id="client_id"
                                name="client_id"
                                class="sf-select"
                                required>
                            <option value="">— Select Client —</option>

                            @foreach(($clients ?? collect()) as $client)
                                <option value="{{ $client->id }}" @selected($selectedClientId === (string) $client->id)>
                                    {{ $client->name }}{{ $client->phone ? ' — '.$client->phone : '' }}
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

    {{-- Booking Details --}}
    <div class="sf-card">
        <div class="sf-card-header">
            <h2 class="sf-section-title">
                Booking Details
            </h2>

            <p class="sf-section-subtitle">
                Confirm the appointment date, slot, status, priority, and owner.
            </p>
        </div>

        <div class="sf-card-body">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                {{-- Booking Date --}}
                <div>
                    <label for="booking_date" class="sf-label">
                        Booking Date <span class="text-red-300">*</span>
                    </label>

                    <input type="date"
                           id="booking_date"
                           name="booking_date"
                           value="{{ $bookingDateVal }}"
                           required
                           class="sf-input">

                    @error('booking_date')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Slot --}}
                <div>
                    <label for="slot" class="sf-label">
                        Slot <span class="text-red-300">*</span>
                    </label>

                    <select id="slot" name="slot" required class="sf-select">
                        @foreach($slotOptions as $value => $label)
                            <option value="{{ $value }}" @selected($slotVal === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    @error('slot')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror

                    <div id="slot_capacity_hint" class="sf-help"></div>
                </div>

                {{-- Status --}}
                <div>
                    <label for="status" class="sf-label">
                        Status <span class="text-red-300">*</span>
                    </label>

                    <select id="status" name="status" required class="sf-select">
                        @foreach($bookingStatuses as $status)
                            <option value="{{ $status }}" @selected($statusVal === $status)>
                                {{ $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>

                    @error('status')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror

                    <div id="status_help" class="sf-help">
                        {{ $statusHelp[$statusVal] ?? 'Select the current booking status.' }}
                    </div>
                </div>

                {{-- Priority --}}
                <div>
                    <label for="priority" class="sf-label">
                        Priority
                    </label>

                    <select id="priority" name="priority" class="sf-select">
                        @foreach($priorityOptions as $value => $label)
                            <option value="{{ $value }}" @selected($priorityVal === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>

                    @error('priority')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Assigned To --}}
                <div>
                    <label for="assigned_to" class="sf-label">
                        Assigned To
                    </label>

                    <select id="assigned_to" name="assigned_to" class="sf-select">
                        <option value="">Unassigned</option>

                        @foreach(($users ?? collect()) as $user)
                            <option value="{{ $user->id }}" @selected($selectedAssignedTo === (string) $user->id)>
                                {{ $user->name }}{{ !empty($user->role) ? ' — '.ucfirst($user->role) : '' }}
                            </option>
                        @endforeach
                    </select>

                    @error('assigned_to')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Expected Close Date --}}
                <div>
                    <label for="expected_close_date" class="sf-label">
                        Expected Close Date
                    </label>

                    <input type="date"
                           id="expected_close_date"
                           name="expected_close_date"
                           value="{{ $expectedCloseDateVal }}"
                           class="sf-input">

                    @error('expected_close_date')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Expected Duration --}}
                <div>
                    <label for="expected_duration" class="sf-label">
                        Expected Duration (Days)
                    </label>

                    <input type="number"
                           id="expected_duration"
                           name="expected_duration"
                           value="{{ $oldOr('expected_duration') }}"
                           min="0"
                           class="sf-input"
                           placeholder="Example: 1">

                    @error('expected_duration')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Allow Overbooking --}}
                <div class="rounded-2xl border border-orange-400/20 bg-orange-500/10 p-4">
                    <label class="flex items-start gap-3">
                        <input type="checkbox"
                               name="allow_overbooking"
                               id="allow_overbooking"
                               value="1"
                               @checked($allowOverbookingVal === '1' || $allowOverbookingVal === 1)
                               class="mt-1 rounded border-white/10 bg-slate-950 text-orange-500 shadow-sm focus:ring-orange-400">

                        <span>
                            <span class="block text-sm font-extrabold text-orange-300">
                                Allow Overbooking
                            </span>

                            <span class="mt-1 block text-xs font-medium leading-5 text-orange-100/80">
                                Use this only when you intentionally want to exceed slot capacity.
                            </span>
                        </span>
                    </label>

                    @error('allow_overbooking')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>

            </div>
        </div>
    </div>

    {{-- Lost Booking --}}
    <div id="lost_reason_wrap" class="sf-card hidden border-red-400/20">
        <div class="sf-card-header">
            <h2 class="sf-section-title">
                Lost Booking Details
            </h2>

            <p class="sf-section-subtitle">
                Required when status is Lost Booking.
            </p>
        </div>

        <div class="sf-card-body">
            <label for="lost_reason" class="sf-label">
                Lost Reason <span class="text-red-300">*</span>
            </label>

            <select id="lost_reason" name="lost_reason" class="sf-select">
                <option value="">— Select Reason —</option>

                @foreach($lostReasons as $reason)
                    <option value="{{ $reason }}" @selected($lostReasonVal === $reason)>
                        {{ $lostReasonLabels[$reason] ?? ucfirst(str_replace('_', ' ', $reason)) }}
                    </option>
                @endforeach
            </select>

            @error('lost_reason')
                <div class="sf-error">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Vehicle --}}
    <div class="sf-card">
        <div class="sf-card-header">
            <h2 class="sf-section-title">
                Vehicle Details
            </h2>

            <p class="sf-section-subtitle">
                Link an existing vehicle or add new vehicle details.
            </p>
        </div>

        <div class="sf-card-body space-y-6">

            {{-- Existing Vehicle --}}
            <div>
                <label for="vehicle_id" class="sf-label">
                    Existing Vehicle
                </label>

                <select id="vehicle_id" name="vehicle_id" class="sf-select">
                    <option value="">— Select Vehicle —</option>

                    @foreach(($vehicles ?? collect()) as $vehicle)
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

                @error('vehicle_id')
                    <div class="sf-error">{{ $message }}</div>
                @enderror

                <p class="sf-help">
                    Existing vehicles are filtered by selected client.
                </p>
            </div>

            <div class="sf-divider"></div>

            {{-- New Vehicle --}}
            <div class="space-y-5">
                <div>
                    <h3 class="sf-section-title">
                        Add New Vehicle
                    </h3>

                    <p class="sf-section-subtitle">
                        Fill this only when the vehicle does not already exist.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="new_vehicle_make_id" class="sf-label">
                            Make
                        </label>

                        <select id="new_vehicle_make_id" name="new_vehicle_make_id" class="sf-select">
                            <option value="">— Select Make —</option>

                            @foreach(($vehicleMakes ?? collect()) as $make)
                                <option value="{{ $make->id }}" @selected($selectedNewVehicleMakeId === (string) $make->id)>
                                    {{ $make->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('new_vehicle_make_id')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="new_vehicle_model_id" class="sf-label">
                            Model
                        </label>

                        <select id="new_vehicle_model_id" name="new_vehicle_model_id" class="sf-select">
                            <option value="">— Select Model —</option>

                            @foreach(($vehicleModels ?? collect()) as $model)
                                <option value="{{ $model->id }}"
                                        data-make-id="{{ $model->make_id }}"
                                        @selected($selectedNewVehicleModelId === (string) $model->id)>
                                    {{ $model->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('new_vehicle_model_id')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="sf-label">
                            Year
                        </label>

                        <input type="text"
                               name="new_vehicle_year"
                               value="{{ old('new_vehicle_year') }}"
                               class="sf-input"
                               placeholder="2022">

                        @error('new_vehicle_year')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="sf-label">
                            Plate Number
                        </label>

                        <input type="text"
                               name="new_vehicle_plate_number"
                               value="{{ old('new_vehicle_plate_number') }}"
                               class="sf-input"
                               placeholder="Dubai A 12345">

                        @error('new_vehicle_plate_number')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="sf-label">
                            VIN
                        </label>

                        <input type="text"
                               name="new_vehicle_vin"
                               value="{{ old('new_vehicle_vin') }}"
                               class="sf-input"
                               placeholder="Vehicle VIN">

                        @error('new_vehicle_vin')
                            <div class="sf-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pickup --}}
    <div class="sf-card">
        <div class="sf-card-header">
            <h2 class="sf-section-title">
                Pickup Details
            </h2>

            <p class="sf-section-subtitle">
                Enable pickup only if customer needs vehicle collection.
            </p>
        </div>

        <div class="sf-card-body space-y-5">
            <div class="rounded-2xl border border-blue-400/20 bg-blue-500/10 p-4">
                <label class="flex items-start gap-3">
                    <input type="checkbox"
                           name="pickup_required"
                           id="pickup_required"
                           value="1"
                           @checked($pickupRequiredVal === '1' || $pickupRequiredVal === 1)
                           class="mt-1 rounded border-white/10 bg-slate-950 text-blue-500 shadow-sm focus:ring-blue-400">

                    <span>
                        <span class="block text-sm font-extrabold text-blue-300">
                            Pickup Required
                        </span>

                        <span class="mt-1 block text-xs font-medium leading-5 text-blue-100/80">
                            Capture pickup address and contact number below.
                        </span>
                    </span>
                </label>
            </div>

            <div id="pickup_fields" class="grid grid-cols-1 gap-5 md:grid-cols-2 hidden">
                <div>
                    <label class="sf-label">
                        Pickup Address
                    </label>

                    <textarea name="pickup_address"
                              rows="3"
                              class="sf-textarea"
                              placeholder="Pickup address">{{ $oldOr('pickup_address') }}</textarea>

                    @error('pickup_address')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="sf-label">
                        Pickup Contact Number
                    </label>

                    <input type="text"
                           name="pickup_contact_number"
                           value="{{ $oldOr('pickup_contact_number') }}"
                           class="sf-input"
                           placeholder="9715XXXXXXXX">

                    @error('pickup_contact_number')
                        <div class="sf-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>
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
                      rows="4"
                      class="sf-textarea"
                      placeholder="Add booking notes, customer instructions, pickup/drop details, or internal context...">{{ $oldOr('notes') }}</textarea>

            @error('notes')
                <div class="sf-error">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex flex-wrap justify-end gap-2">
        <a href="{{ route('admin.bookings.index') }}" class="sf-btn-secondary">
            Cancel
        </a>

        <button type="submit" class="sf-btn-primary">
            {{ $isEdit ? 'Update Booking' : 'Create Booking' }}
        </button>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const opportunities = @json($opportunitiesForJs);
    const vehicles = @json($vehiclesForJs);
    const vehicleModels = @json($vehicleModelsForJs);
    const slotUsage = @json($slotUsageForJs);
    const slotCapacities = @json($slotCapacitiesForJs);
    const statusHelp = @json($statusHelp);

    const opportunitySelect = document.getElementById('opportunity_id');
    const clientSelect = document.getElementById('client_id');
    const vehicleSelect = document.getElementById('vehicle_id');
    const bookingDateInput = document.getElementById('booking_date');
    const expectedCloseDateInput = document.getElementById('expected_close_date');
    const prioritySelect = document.getElementById('priority');
    const slotSelect = document.getElementById('slot');
    const statusSelect = document.getElementById('status');
    const statusHelpEl = document.getElementById('status_help');
    const lostReasonWrap = document.getElementById('lost_reason_wrap');
    const lostReasonSelect = document.getElementById('lost_reason');
    const pickupRequired = document.getElementById('pickup_required');
    const pickupFields = document.getElementById('pickup_fields');
    const newVehicleMakeSelect = document.getElementById('new_vehicle_make_id');
    const newVehicleModelSelect = document.getElementById('new_vehicle_model_id');
    const slotCapacityHint = document.getElementById('slot_capacity_hint');

    function refreshStatusFields() {
        const status = statusSelect?.value || '';

        if (statusHelpEl) {
            statusHelpEl.textContent = statusHelp[status] || 'Select the current booking status.';
        }

        if (lostReasonWrap) {
            lostReasonWrap.classList.toggle('hidden', status !== 'lost');
        }

        if (lostReasonSelect) {
            if (status === 'lost') {
                lostReasonSelect.setAttribute('required', 'required');
            } else {
                lostReasonSelect.removeAttribute('required');
            }
        }
    }

    function refreshPickupFields() {
        if (!pickupFields || !pickupRequired) return;

        pickupFields.classList.toggle('hidden', !pickupRequired.checked);
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

    function filterNewVehicleModels() {
        if (!newVehicleMakeSelect || !newVehicleModelSelect) return;

        const selectedMakeId = newVehicleMakeSelect.value;

        [...newVehicleModelSelect.options].forEach(function (option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            const optionMakeId = option.getAttribute('data-make-id');
            option.hidden = selectedMakeId && optionMakeId && optionMakeId !== selectedMakeId;
        });

        const selectedOption = newVehicleModelSelect.options[newVehicleModelSelect.selectedIndex];

        if (selectedOption && selectedOption.hidden) {
            newVehicleModelSelect.value = '';
        }
    }

    function refreshSlotCapacity() {
        if (!slotCapacityHint || !slotSelect || !bookingDateInput) return;

        const date = bookingDateInput.value;
        const slot = slotSelect.value;

        if (!date || !slot) {
            slotCapacityHint.textContent = '';
            return;
        }

        const dateUsage = slotUsage[date] || {};
        const used = parseInt(dateUsage[slot] || 0, 10);
        const capacity = parseInt(slotCapacities[slot] || 0, 10);

        if (!capacity) {
            slotCapacityHint.textContent = '';
            return;
        }

        slotCapacityHint.textContent = `${used}/${capacity} bookings already used for this slot.`;

        if (used >= capacity) {
            slotCapacityHint.classList.add('text-red-400');
            slotCapacityHint.classList.remove('text-slate-500');
        } else {
            slotCapacityHint.classList.remove('text-red-400');
            slotCapacityHint.classList.add('text-slate-500');
        }
    }

    function applyOpportunitySelection() {
        if (!opportunitySelect) return;

        const selectedOpportunity = opportunities.find(function (item) {
            return item.id === opportunitySelect.value;
        });

        if (!selectedOpportunity) return;

        if (clientSelect && selectedOpportunity.client_id) {
            clientSelect.value = selectedOpportunity.client_id;
            filterVehiclesByClient();
        }

        if (vehicleSelect && selectedOpportunity.vehicle_id) {
            vehicleSelect.value = selectedOpportunity.vehicle_id;
        }

        if (prioritySelect && selectedOpportunity.priority) {
            prioritySelect.value = selectedOpportunity.priority;
        }

        if (expectedCloseDateInput && selectedOpportunity.expected_close_date) {
            expectedCloseDateInput.value = selectedOpportunity.expected_close_date;
        }

        if (bookingDateInput && !bookingDateInput.value && selectedOpportunity.expected_close_date) {
            bookingDateInput.value = selectedOpportunity.expected_close_date;
        }

        refreshSlotCapacity();
    }

    opportunitySelect?.addEventListener('change', applyOpportunitySelection);

    clientSelect?.addEventListener('change', function () {
        filterVehiclesByClient();
    });

    statusSelect?.addEventListener('change', refreshStatusFields);
    pickupRequired?.addEventListener('change', refreshPickupFields);
    newVehicleMakeSelect?.addEventListener('change', filterNewVehicleModels);
    slotSelect?.addEventListener('change', refreshSlotCapacity);
    bookingDateInput?.addEventListener('change', refreshSlotCapacity);

    refreshStatusFields();
    refreshPickupFields();
    filterVehiclesByClient();
    filterNewVehicleModels();
    refreshSlotCapacity();
});
</script>
@endpush