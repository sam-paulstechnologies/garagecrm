@csrf
@if($isEdit)
    @method('PUT')
@endif

@include('admin.bookings.errors')

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label>Opportunity</label>
        <input type="text" class="w-full border rounded p-2 bg-gray-100" readonly
               value="#{{ $booking->opportunity->id ?? '-' }} - {{ $booking->opportunity->client->name ?? '-' }}">
    </div>

    <div>
        <label>Client</label>
        <input type="text" class="w-full border rounded p-2 bg-gray-100" readonly
               value="{{ $booking->client->name ?? '-' }}">
    </div>

    <div>
        <label>Make</label>
        <select name="vehicle_make_id" id="vehicle_make_id" class="w-full border rounded p-2">
            <option value="">Select Make</option>
            @foreach($vehicleMakes as $make)
                <option value="{{ $make->id }}"
                    data-name="{{ strtolower($make->name) }}"
                    {{ old('vehicle_make_id', $booking->vehicle_make_id) == $make->id ? 'selected' : '' }}>
                    {{ $make->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label>Model</label>
        <select name="vehicle_model_id" id="vehicle_model_id" class="w-full border rounded p-2">
            <option value="">Select Model</option>
            @foreach($vehicleModels as $model)
                <option value="{{ $model->id }}"
                    data-make="{{ $model->make_id }}"
                    data-name="{{ strtolower($model->name) }}"
                    {{ old('vehicle_model_id', $booking->vehicle_model_id) == $model->id ? 'selected' : '' }}>
                    {{ $model->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div id="other-make-group" style="display: none;">
        <label>Other Make</label>
        <input type="text" name="other_make" class="w-full border rounded p-2"
               value="{{ old('other_make', $booking->other_make) }}">
    </div>

    <div id="other-model-group" style="display: none;">
        <label>Other Model</label>
        <input type="text" name="other_model" class="w-full border rounded p-2"
               value="{{ old('other_model', $booking->other_model) }}">
    </div>

    <div>
        <label>Service Type (comma separated)</label>
        <input type="text" name="service_type" class="w-full border rounded p-2"
               value="{{ old('service_type', $booking->service_type) }}">
    </div>

    <div>
        <label>Date</label>
        <input type="date" name="date" class="w-full border rounded p-2"
               value="{{ old('date', optional($booking->date)->format('Y-m-d')) }}">
    </div>

    <div>
        <label>Slot</label>
        <select name="slot" class="w-full border rounded p-2">
            <option value="morning" {{ old('slot', $booking->slot) == 'morning' ? 'selected' : '' }}>Morning</option>
            <option value="afternoon" {{ old('slot', $booking->slot) == 'afternoon' ? 'selected' : '' }}>Afternoon</option>
            <option value="evening" {{ old('slot', $booking->slot) == 'evening' ? 'selected' : '' }}>Evening</option>
            <option value="full_day" {{ old('slot', $booking->slot) == 'full_day' ? 'selected' : '' }}>Full Day</option>
        </select>
    </div>

    <div>
        <label>Priority</label>
        <select name="priority" class="w-full border rounded p-2">
            <option value="">--</option>
            <option value="low" {{ old('priority', $booking->priority) == 'low' ? 'selected' : '' }}>Low</option>
            <option value="medium" {{ old('priority', $booking->priority) == 'medium' ? 'selected' : '' }}>Medium</option>
            <option value="high" {{ old('priority', $booking->priority) == 'high' ? 'selected' : '' }}>High</option>
        </select>
    </div>

    <div>
        <label>Status</label>
        <select name="status" class="w-full border rounded p-2">
            @php
                $statuses = ['pending', 'vehicle_received', 'in_progress', 'completed', 'cancelled'];
            @endphp
            @foreach($statuses as $status)
                <option value="{{ $status }}"
                    {{ old('status', $booking->status) === $status ? 'selected' : '' }}>
                    {{ ucwords(str_replace('_', ' ', $status)) }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label>Expected Duration (in days)</label>
        <input type="number" name="expected_duration" id="expected_duration" class="w-full border rounded p-2"
               value="{{ old('expected_duration', $booking->expected_duration) }}">
    </div>

    <div>
        <label>Expected Close Date</label>
        <input type="date" name="expected_close_date" id="expected_close_date" class="w-full border rounded p-2"
               value="{{ old('expected_close_date', optional($booking->expected_close_date)->format('Y-m-d')) }}">
    </div>

    <div>
        <label>Pickup Required?</label>
        <select name="pickup_required" id="pickup_required" class="w-full border rounded p-2">
            <option value="0" {{ old('pickup_required', $booking->pickup_required) == 0 ? 'selected' : '' }}>No</option>
            <option value="1" {{ old('pickup_required', $booking->pickup_required) == 1 ? 'selected' : '' }}>Yes</option>
        </select>
    </div>

    <div id="pickup-fields" style="display: none;">
        <label>Pickup Address</label>
        <input type="text" name="pickup_address" class="w-full border rounded p-2"
               value="{{ old('pickup_address', $booking->pickup_address) }}">

        <label>Pickup Contact Number</label>
        <input type="text" name="pickup_contact_number" class="w-full border rounded p-2"
               value="{{ old('pickup_contact_number', $booking->pickup_contact_number) }}">
    </div>

    <div class="col-span-2">
        <label>Notes</label>
        <textarea name="notes" rows="3" class="w-full border rounded p-2">{{ old('notes', $booking->notes) }}</textarea>
    </div>
</div>

<div class="mt-6">
    <button class="bg-blue-600 text-white px-4 py-2 rounded">
        {{ $isEdit ? 'Update Booking' : 'Create Booking' }}
    </button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const makeSelect = document.getElementById('vehicle_make_id');
    const modelSelect = document.getElementById('vehicle_model_id');
    const allModelOptions = [...modelSelect.querySelectorAll('option[data-make]')];
    const otherMakeGroup = document.getElementById('other-make-group');
    const otherModelGroup = document.getElementById('other-model-group');
    const pickupRequired = document.getElementById('pickup_required');
    const pickupFields = document.getElementById('pickup-fields');
    const durationInput = document.getElementById('expected_duration');
    const closeDateInput = document.getElementById('expected_close_date');
    const bookingDateInput = document.querySelector('input[name="date"]');

    function updateModelOptions() {
        const selectedMakeId = makeSelect.value;
        const selectedMakeName = makeSelect.options[makeSelect.selectedIndex]?.dataset.name || '';

        otherMakeGroup.style.display = selectedMakeName === 'other' ? 'block' : 'none';

        modelSelect.innerHTML = '<option value="">Select Model</option>';
        allModelOptions.forEach(option => {
            if (option.dataset.make === selectedMakeId) {
                modelSelect.appendChild(option.cloneNode(true));
            }
        });

        updateOtherModelVisibility();
    }

    function updateOtherModelVisibility() {
        const selectedModel = modelSelect.options[modelSelect.selectedIndex];
        const selectedModelName = selectedModel?.dataset?.name || '';
        otherModelGroup.style.display = selectedModelName === 'other' ? 'block' : 'none';
    }

    function togglePickupFields() {
        pickupFields.style.display = pickupRequired.value == "1" ? 'block' : 'none';
    }

    function updateExpectedCloseDate() {
        const startDate = bookingDateInput.value;
        const duration = parseInt(durationInput.value);

        if (startDate && duration) {
            const date = new Date(startDate);
            date.setDate(date.getDate() + duration);
            closeDateInput.value = date.toISOString().split('T')[0];
        }
    }

    makeSelect.addEventListener('change', updateModelOptions);
    modelSelect.addEventListener('change', updateOtherModelVisibility);
    pickupRequired.addEventListener('change', togglePickupFields);
    durationInput.addEventListener('input', updateExpectedCloseDate);

    updateModelOptions();
    togglePickupFields();
});
</script>
