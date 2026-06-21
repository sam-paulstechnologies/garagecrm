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
        'appointment' => 'Appointment',
        'offer' => 'Offer',
        'manager_confirmation_pending' => 'Manager Confirmation Pending',
        'booking_confirmed' => 'Booking Confirmed',
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
        'not_interested' => 'Not interested',
        'price_not_accepted' => 'Price not accepted',
        'customer_cancelled' => 'Customer cancelled',
        'unreachable_after_follow_up' => 'Unreachable after follow-up',
        'service_not_required' => 'Service no longer required',
        'service_not_offered' => 'Service not offered',
        'duplicate' => 'Duplicate opportunity',
        'booked_elsewhere' => 'Booked elsewhere',
        'spam_or_test' => 'Spam / test',
        'other' => 'Other',
    ];

    $selectedStage = \App\Models\Client\Opportunity::normalizeStage($oldOr('stage', 'new'));
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

<form method="POST" action="{{ $action }}" class="{{ $isEdit ? 'sf-opportunity-edit-form space-y-6' : 'space-y-6' }}">
    @csrf

    @if($isEdit)
        @method('PUT')
    @endif

    @if($isEdit)
        @include('admin.opportunities.form-partials._errors')

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div class="sf-opportunity-edit-panel sf-opportunity-edit-card rounded-2xl border shadow-sm">
                    <div class="sf-crm-card-header border-b border-slate-800 px-5 py-4">
                        <h2 class="sf-opportunity-edit-title text-base font-extrabold tracking-tight">Opportunity Information</h2>
                    </div>

                    <div class="p-4 sm:p-5">
                        <div class="sf-opportunity-edit-sections">
                            @include('admin.opportunities.form-partials._basic_details')
                            @include('admin.opportunities.form-partials._pipeline_details')
                            @include('admin.opportunities.form-partials._booking_confirmation')
                            @include('admin.opportunities.form-partials._vehicle')
                            @include('admin.opportunities.form-partials._services')
                            @include('admin.opportunities.form-partials._notes')
                        </div>
                    </div>

                    <div class="sf-crm-action-bar border-t border-slate-800 px-5 py-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="submit" class="sf-btn-primary">
                                Update Opportunity
                            </button>

                            <a href="{{ route('admin.opportunities.show', $opp) }}" class="sf-btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            @include('admin.opportunities.form-partials._sidebar')
        </div>
    @else
        @include('admin.opportunities.form-partials._errors')
        @include('admin.opportunities.form-partials._pipeline_guide')

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                @include('admin.opportunities.form-partials._basic_details')
                @include('admin.opportunities.form-partials._pipeline_details')
                @include('admin.opportunities.form-partials._booking_confirmation')
                @include('admin.opportunities.form-partials._vehicle')
                @include('admin.opportunities.form-partials._services')
                @include('admin.opportunities.form-partials._notes')
                @include('admin.opportunities.form-partials._actions')
            </div>

            @include('admin.opportunities.form-partials._sidebar')
        </div>
    @endif
</form>

@include('admin.opportunities.form-partials._scripts')
