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
