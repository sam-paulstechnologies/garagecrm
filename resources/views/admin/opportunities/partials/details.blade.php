{{-- resources/views/admin/opportunities/partials/details.blade.php --}}

<div class="grid grid-cols-1 gap-6 md:grid-cols-2">

    {{-- Vehicle Make --}}
    <div>
        <label for="vehicle_make_id" class="sf-label">
            Vehicle Make
        </label>

        <select id="vehicle_make_id"
                name="vehicle_make_id"
                class="sf-select"
                onchange="if (typeof toggleOtherMake === 'function') toggleOtherMake(this.value)">
            <option value="">-- Select Make --</option>

            @foreach($makes as $make)
                <option value="{{ $make->id }}"
                        @selected(old('vehicle_make_id', $opportunity->vehicle_make_id ?? '') == $make->id)>
                    {{ $make->name }}
                </option>
            @endforeach

            <option value="other" @selected(old('vehicle_make_id') === 'other')>
                Other
            </option>
        </select>

        @error('vehicle_make_id')
            <div class="sf-error">{{ $message }}</div>
        @enderror

        <input type="text"
               name="other_make"
               placeholder="Other make"
               class="sf-input mt-3"
               value="{{ old('other_make', $opportunity->other_make ?? '') }}">

        @error('other_make')
            <div class="sf-error">{{ $message }}</div>
        @enderror
    </div>

    {{-- Vehicle Model --}}
    <div>
        <label for="vehicle_model_id" class="sf-label">
            Vehicle Model
        </label>

        <select id="vehicle_model_id"
                name="vehicle_model_id"
                class="sf-select">
            <option value="">-- Select Model --</option>

            @foreach($models as $model)
                <option value="{{ $model->id }}"
                        @selected(old('vehicle_model_id', $opportunity->vehicle_model_id ?? '') == $model->id)>
                    {{ $model->name }}
                </option>
            @endforeach
        </select>

        @error('vehicle_model_id')
            <div class="sf-error">{{ $message }}</div>
        @enderror

        <input type="text"
               name="other_model"
               placeholder="Other model"
               class="sf-input mt-3"
               value="{{ old('other_model', $opportunity->other_model ?? '') }}">

        @error('other_model')
            <div class="sf-error">{{ $message }}</div>
        @enderror
    </div>

    {{-- Value --}}
    <div>
        <label class="sf-label">
            Estimated Value (AED)
        </label>

        <input type="number"
               name="value"
               step="0.01"
               value="{{ old('value', $opportunity->value ?? '') }}"
               class="sf-input"
               placeholder="0.00">

        @error('value')
            <div class="sf-error">{{ $message }}</div>
        @enderror
    </div>

    {{-- Expected Close Date --}}
    <div>
        <label class="sf-label">
            Expected Close Date
        </label>

        <input type="date"
               name="expected_close_date"
               value="{{ old('expected_close_date', optional($opportunity?->expected_close_date)->format('Y-m-d')) }}"
               class="sf-input">

        @error('expected_close_date')
            <div class="sf-error">{{ $message }}</div>
        @enderror
    </div>

    {{-- Expected Duration --}}
    <div>
        <label class="sf-label">
            Expected Duration (Days)
        </label>

        <input type="number"
               name="expected_duration"
               value="{{ old('expected_duration', $opportunity->expected_duration ?? '') }}"
               class="sf-input"
               placeholder="Example: 3">

        @error('expected_duration')
            <div class="sf-error">{{ $message }}</div>
        @enderror
    </div>

    {{-- Next Follow-Up Date --}}
    <div>
        <label class="sf-label">
            Next Follow-Up
        </label>

        <input type="date"
               name="next_follow_up"
               value="{{ old('next_follow_up', optional($opportunity?->next_follow_up)->format('Y-m-d')) }}"
               class="sf-input">

        @error('next_follow_up')
            <div class="sf-error">{{ $message }}</div>
        @enderror
    </div>

</div>