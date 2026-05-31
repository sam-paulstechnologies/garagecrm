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
