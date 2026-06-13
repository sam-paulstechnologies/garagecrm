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
    const opportunitySearch = document.getElementById('opportunity_search');
    const opportunityResults = document.getElementById('opportunity_combobox_results');
    const opportunitySelectedSummary = document.getElementById('opportunity_selected_summary');
    const clientSelect = document.getElementById('client_id');
    const clientSearch = document.getElementById('client_search');
    const clientResults = document.getElementById('client_combobox_results');
    const clientSelectedSummary = document.getElementById('client_selected_summary');
    const quickClientPanel = document.getElementById('quick_client_panel');
    const vehicleSelect = document.getElementById('vehicle_id');
    const existingVehiclePanel = document.getElementById('existing_vehicle_panel');
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
    const newClientRequiredMarkers = document.querySelectorAll('[data-new-client-required]');
    const slotCapacityHint = document.getElementById('slot_capacity_hint');
    let clientComboboxOpen = false;
    let opportunityComboboxOpen = false;

    function setClientComboboxOpen(isOpen) {
        clientComboboxOpen = isOpen;

        if (clientResults) {
            clientResults.classList.toggle('hidden', !isOpen);
        }

        if (clientSearch) {
            clientSearch.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
    }

    function setOpportunityComboboxOpen(isOpen) {
        opportunityComboboxOpen = isOpen;

        if (opportunityResults) {
            opportunityResults.classList.toggle('hidden', !isOpen);
        }

        if (opportunitySearch) {
            opportunitySearch.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
    }

    function closeComboboxes() {
        setClientComboboxOpen(false);
        setOpportunityComboboxOpen(false);
    }

    function selectedClientMode() {
        return clientSelect?.value === 'new_client' ? 'new' : 'existing';
    }

    function hasSelectedOpportunity() {
        return !!(opportunitySelect && opportunitySelect.value);
    }

    function optionLabel(option) {
        return (option?.textContent || '').replace(/\s+/g, ' ').trim();
    }

    function optionSearchText(option) {
        return (option?.getAttribute('data-search') || optionLabel(option)).toLowerCase();
    }

    function selectedOption(select) {
        return select?.options[select.selectedIndex] || null;
    }

    function comboboxButton(label, isSelected) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = [
            'flex w-full items-start justify-between gap-3 rounded-xl px-3 py-2 text-left text-sm font-semibold transition',
            isSelected
                ? 'bg-orange-500 text-white shadow-sm'
                : 'text-slate-700 hover:bg-orange-50 hover:text-orange-700 dark:text-slate-200 dark:hover:bg-orange-500/10 dark:hover:text-orange-200',
        ].join(' ');
        button.setAttribute('role', 'option');
        button.setAttribute('aria-selected', isSelected ? 'true' : 'false');
        button.textContent = label;

        return button;
    }

    function emptyComboboxMessage(message) {
        const element = document.createElement('div');
        element.className = 'px-3 py-2 text-xs font-semibold text-slate-500 dark:text-slate-400';
        element.textContent = message;

        return element;
    }

    function syncClientSearchFromSelection() {
        if (!clientSearch || !clientSelect) return;

        const option = selectedOption(clientSelect);
        clientSearch.value = option && option.value ? optionLabel(option) : '';

        if (clientSelectedSummary) {
            clientSelectedSummary.textContent = option && option.value ? optionLabel(option) : 'No client selected';
        }
    }

    function syncOpportunitySearchFromSelection() {
        if (!opportunitySearch || !opportunitySelect) return;

        const option = selectedOption(opportunitySelect);
        opportunitySearch.value = option && option.value ? optionLabel(option) : '';

        if (opportunitySelectedSummary) {
            opportunitySelectedSummary.textContent = option && option.value ? optionLabel(option) : 'No opportunity selected';
        }
    }

    function renderClientResults() {
        if (!clientResults || !clientSelect) return;

        const term = (clientSearch?.value || '').trim().toLowerCase();
        clientResults.innerHTML = '';

        const newClientOption = [...clientSelect.options].find(function (option) {
            return option.value === 'new_client';
        });

        if (newClientOption) {
            const button = comboboxButton(optionLabel(newClientOption), clientSelect.value === 'new_client');
            button.classList.add('border', 'border-orange-400/30', 'bg-orange-500/10', 'text-orange-700', 'dark:text-orange-200');
            button.addEventListener('click', function () {
                clientSelect.value = 'new_client';
                syncClientSearchFromSelection();
                clientSelect.dispatchEvent(new Event('change', { bubbles: true }));
                renderClientResults();
                setClientComboboxOpen(false);
            });
            clientResults.appendChild(button);
        }

        let matches = 0;

        [...clientSelect.options].forEach(function (option) {
            if (!option.value || option.value === 'new_client') return;

            const haystack = optionSearchText(option);
            if (term && !haystack.includes(term)) return;

            matches++;
            const button = comboboxButton(optionLabel(option), clientSelect.value === option.value);
            button.addEventListener('click', function () {
                clientSelect.value = option.value;
                syncClientSearchFromSelection();
                clientSelect.dispatchEvent(new Event('change', { bubbles: true }));
                renderClientResults();
                setClientComboboxOpen(false);
            });
            clientResults.appendChild(button);
        });

        if (!matches) {
            clientResults.appendChild(emptyComboboxMessage('No matching existing clients. Use + Add New Client to create one.'));
        }
    }

    function renderOpportunityResults() {
        if (!opportunityResults || !opportunitySelect) return;

        const clientId = selectedExistingClientId();
        const term = (opportunitySearch?.value || '').trim().toLowerCase();
        opportunityResults.innerHTML = '';

        const noneButton = comboboxButton('- None -', !opportunitySelect.value);
        noneButton.addEventListener('click', function () {
            opportunitySelect.value = '';
            syncOpportunitySearchFromSelection();
            opportunitySelect.dispatchEvent(new Event('change', { bubbles: true }));
            renderOpportunityResults();
            setOpportunityComboboxOpen(false);
        });
        opportunityResults.appendChild(noneButton);

        let matches = 0;

        [...opportunitySelect.options].forEach(function (option) {
            if (!option.value) return;

            const optionClientId = option.getAttribute('data-client-id') || '';
            const matchesClient = !clientId || optionClientId === clientId;
            const matchesSearch = !term || optionSearchText(option).includes(term);

            if (!matchesClient || !matchesSearch) return;

            matches++;
            const button = comboboxButton(optionLabel(option), opportunitySelect.value === option.value);
            button.addEventListener('click', function () {
                opportunitySelect.value = option.value;
                syncOpportunitySearchFromSelection();
                opportunitySelect.dispatchEvent(new Event('change', { bubbles: true }));
                renderOpportunityResults();
                setOpportunityComboboxOpen(false);
            });
            opportunityResults.appendChild(button);
        });

        if (!matches) {
            opportunityResults.appendChild(emptyComboboxMessage('No matching open opportunities for this selection.'));
        }
    }

    function filterSelectOptions(select, searchValue) {
        if (!select) return;

        const term = (searchValue || '').trim().toLowerCase();

        [...select.options].forEach(function (option) {
            if (!option.value || option.value === 'new_client') {
                option.hidden = false;
                return;
            }

            const haystack = (option.getAttribute('data-search') || option.textContent || '').toLowerCase();
            option.hidden = term !== '' && !haystack.includes(term);
        });

        const selectedOption = select.options[select.selectedIndex];

        if (selectedOption && selectedOption.hidden) {
            select.value = '';
        }
    }

    function selectedExistingClientId() {
        if (!clientSelect || !clientSelect.value || clientSelect.value === 'new_client') {
            return '';
        }

        return clientSelect.value;
    }

    function opportunityMatchesClient(opportunity, clientId) {
        return !clientId || opportunity.client_id === clientId;
    }

    function filterOpportunityOptions() {
        if (!opportunitySelect) return;

        const clientId = selectedExistingClientId();
        const term = (opportunitySearch?.value || '').trim().toLowerCase();

        [...opportunitySelect.options].forEach(function (option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            const optionClientId = option.getAttribute('data-client-id') || '';
            const haystack = (option.getAttribute('data-search') || option.textContent || '').toLowerCase();
            const matchesClient = !clientId || optionClientId === clientId;
            const matchesSearch = !term || haystack.includes(term);

            option.hidden = !matchesClient || !matchesSearch;
        });

        const selectedOption = opportunitySelect.options[opportunitySelect.selectedIndex];

        if (selectedOption && selectedOption.hidden) {
            opportunitySelect.value = '';
        }
    }

    function openOpportunitiesForSelectedClient() {
        const clientId = selectedExistingClientId();

        if (!clientId) {
            return [];
        }

        return opportunities.filter(function (opportunity) {
            return opportunityMatchesClient(opportunity, clientId);
        });
    }

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

    function refreshQuickClientPanel() {
        if (!quickClientPanel) return;

        const shouldShow = selectedClientMode() === 'new' && !hasSelectedOpportunity();

        quickClientPanel.classList.toggle('hidden', !shouldShow);
    }

    function refreshVehicleMode() {
        const isNewClient = selectedClientMode() === 'new' && !hasSelectedOpportunity();

        if (existingVehiclePanel) {
            existingVehiclePanel.classList.toggle('hidden', isNewClient);
        }

        if (vehicleSelect && isNewClient) {
            vehicleSelect.value = '';
        }

        if (newVehicleMakeSelect) {
            if (isNewClient) {
                newVehicleMakeSelect.setAttribute('required', 'required');
            } else {
                newVehicleMakeSelect.removeAttribute('required');
            }
        }

        if (newVehicleModelSelect) {
            if (isNewClient) {
                newVehicleModelSelect.setAttribute('required', 'required');
            } else {
                newVehicleModelSelect.removeAttribute('required');
            }
        }

        newClientRequiredMarkers.forEach(function (marker) {
            marker.classList.toggle('hidden', !isNewClient);
        });
    }

    function filterVehiclesByClient() {
        if (!clientSelect || !vehicleSelect) return;

        const selectedClientId = clientSelect.value === 'new_client' ? '' : clientSelect.value;

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

        refreshVehicleMode();
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

        if (!selectedOpportunity) {
            refreshQuickClientPanel();
            refreshVehicleMode();
            return;
        }

        if (clientSelect && selectedOpportunity.client_id) {
            clientSelect.value = selectedOpportunity.client_id;
            filterVehiclesByClient();
            refreshQuickClientPanel();
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

    opportunitySelect?.addEventListener('change', function () {
        applyOpportunitySelection();
        filterOpportunityOptions();
        syncClientSearchFromSelection();
        syncOpportunitySearchFromSelection();
        renderClientResults();
        renderOpportunityResults();
        refreshQuickClientPanel();
        refreshVehicleMode();
    });

    clientSelect?.addEventListener('change', function () {
        const isNewClient = clientSelect.value === 'new_client';

        if (isNewClient && opportunitySelect) {
            opportunitySelect.value = '';
        }

        if (opportunitySelect && opportunitySelect.value) {
            const selectedOpportunity = opportunities.find(function (item) {
                return item.id === opportunitySelect.value;
            });

            if (selectedOpportunity && selectedOpportunity.client_id !== clientSelect.value) {
                opportunitySelect.value = '';
            }
        }

        filterOpportunityOptions();

        if (!isNewClient && opportunitySelect && !opportunitySelect.value) {
            const clientOpportunities = openOpportunitiesForSelectedClient();

            if (clientOpportunities.length === 1) {
                opportunitySelect.value = clientOpportunities[0].id;
                applyOpportunitySelection();
                syncOpportunitySearchFromSelection();
                filterOpportunityOptions();
            }
        }

        filterVehiclesByClient();
        syncClientSearchFromSelection();
        renderClientResults();
        renderOpportunityResults();
        refreshQuickClientPanel();
        refreshVehicleMode();
    });

    clientSearch?.addEventListener('input', function () {
        renderClientResults();
        setClientComboboxOpen(true);
    });

    opportunitySearch?.addEventListener('input', function () {
        filterOpportunityOptions();
        renderOpportunityResults();
        setOpportunityComboboxOpen(true);
    });

    clientSearch?.addEventListener('focus', function () {
        renderClientResults();
        setClientComboboxOpen(true);
        setOpportunityComboboxOpen(false);
    });

    clientSearch?.addEventListener('click', function () {
        renderClientResults();
        setClientComboboxOpen(true);
        setOpportunityComboboxOpen(false);
    });

    opportunitySearch?.addEventListener('focus', function () {
        renderOpportunityResults();
        setOpportunityComboboxOpen(true);
        setClientComboboxOpen(false);
    });

    opportunitySearch?.addEventListener('click', function () {
        renderOpportunityResults();
        setOpportunityComboboxOpen(true);
        setClientComboboxOpen(false);
    });

    document.addEventListener('click', function (event) {
        const target = event.target;
        const insideClientCombobox = clientSearch?.contains(target) || clientResults?.contains(target);
        const insideOpportunityCombobox = opportunitySearch?.contains(target) || opportunityResults?.contains(target);

        if (!insideClientCombobox) {
            setClientComboboxOpen(false);
        }

        if (!insideOpportunityCombobox) {
            setOpportunityComboboxOpen(false);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeComboboxes();
        }
    });

    statusSelect?.addEventListener('change', refreshStatusFields);
    pickupRequired?.addEventListener('change', refreshPickupFields);
    newVehicleMakeSelect?.addEventListener('change', filterNewVehicleModels);
    slotSelect?.addEventListener('change', refreshSlotCapacity);
    bookingDateInput?.addEventListener('change', refreshSlotCapacity);

    refreshStatusFields();
    refreshPickupFields();
    refreshQuickClientPanel();
    refreshVehicleMode();
    filterVehiclesByClient();
    filterOpportunityOptions();
    syncClientSearchFromSelection();
    syncOpportunitySearchFromSelection();
    renderClientResults();
    renderOpportunityResults();
    closeComboboxes();
    filterNewVehicleModels();
    refreshSlotCapacity();
});
</script>
@endpush
