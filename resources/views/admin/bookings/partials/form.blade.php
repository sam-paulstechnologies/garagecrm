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
    $rescheduleReasonVal = old('reschedule_reason', $bk?->reschedule_reason ?? '');

    $bookingStatuses = $bookingStatuses ?? [
        'pending',
        'scheduled',
        'reschedule_required',
        'converted_to_job',
        'lost',
    ];

    $statusLabels = [
        'pending' => 'Manager Confirmation',
        'scheduled' => 'Booking Confirmed',
        'reschedule_required' => 'Rescheduling Required',
        'converted_to_job' => 'Converted To Job',
        'lost' => 'Lost Booking',
    ];

    $statusHelp = [
        'pending' => 'Use when booking needs manager confirmation.',
        'scheduled' => 'Use when customer date and slot are confirmed.',
        'reschedule_required' => 'Use when booking needs a new date, slot, or customer confirmation. Reason is required.',
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
        ? ('#' . $bk->opportunity->id . ' - ' . ($bk->opportunity->title ?? 'Opportunity'))
        : 'No opportunity linked';

    $sourceClientLabel = $bk?->client
        ? trim($bk->client->name . ($bk->client->phone ? ' - ' . $bk->client->phone : ''))
        : 'No client linked';
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf

    @if($isEdit)
        @method('PUT')
    @endif

    @include('admin.bookings.form-partials._source_details')
    @include('admin.bookings.form-partials._booking_details')
    @include('admin.bookings.form-partials._reschedule_booking')
    @include('admin.bookings.form-partials._lost_booking')
    @include('admin.bookings.form-partials._vehicle')
    @include('admin.bookings.form-partials._pickup')
    @include('admin.bookings.form-partials._notes')
    @include('admin.bookings.form-partials._actions')
</form>

@include('admin.bookings.form-partials._scripts')
