<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
    <!-- Vehicle Make -->
    <div>
        <label for="vehicle_make_id">Vehicle Make</label>
        <select id="vehicle_make_id" name="vehicle_make_id" class="block w-full rounded" onchange="toggleOtherMake(this.value)">
            <option value="">-- Select Make --</option>
            @foreach($makes as $make)
                <option value="{{ $make->id }}" @selected(old('vehicle_make_id', $opportunity->vehicle_make_id ?? '') == $make->id)>
                    {{ $make->name }}
                </option>
            @endforeach
            <option value="other">Other</option>
        </select>
        <input type="text" name="other_make" placeholder="Other make"
               class="mt-2 block w-full border border-gray-300 rounded"
               value="{{ old('other_make', $opportunity->other_make ?? '') }}">
    </div>

    <!-- Vehicle Model -->
    <div>
        <label for="vehicle_model_id">Vehicle Model</label>
        <select id="vehicle_model_id" name="vehicle_model_id" class="block w-full rounded">
            <option value="">-- Select Model --</option>
            @foreach($models as $model)
                <option value="{{ $model->id }}" @selected(old('vehicle_model_id', $opportunity->vehicle_model_id ?? '') == $model->id)>
                    {{ $model->name }}
                </option>
            @endforeach
        </select>
        <input type="text" name="other_model" placeholder="Other model"
               class="mt-2 block w-full border border-gray-300 rounded"
               value="{{ old('other_model', $opportunity->other_model ?? '') }}">
    </div>

    <!-- Value -->
    <div>
        <label class="block text-sm font-medium text-gray-700">Estimated Value (AED)</label>
        <input type="number" name="value" step="0.01"
               value="{{ old('value', $opportunity->value ?? '') }}"
               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
    </div>

    <!-- Expected Close Date -->
    <div>
        <label class="block text-sm font-medium text-gray-700">Expected Close Date</label>
        <input type="date" name="expected_close_date"
               value="{{ old('expected_close_date', optional($opportunity?->expected_close_date)->format('Y-m-d')) }}"
               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
    </div>

    <!-- Expected Duration (Days) -->
    <div>
        <label class="block text-sm font-medium text-gray-700">Expected Duration (Days)</label>
        <input type="number" name="expected_duration"
               value="{{ old('expected_duration', $opportunity->expected_duration ?? '') }}"
               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
    </div>

    <!-- Next Follow-Up Date -->
    <div>
        <label class="block text-sm font-medium text-gray-700">Next Follow-Up</label>
        <input type="date" name="next_follow_up"
               value="{{ old('next_follow_up', optional($opportunity?->next_follow_up)->format('Y-m-d')) }}"
               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
    </div>
</div>
