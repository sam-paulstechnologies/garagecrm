<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">Vehicle Details</h2>
        <p class="sf-section-subtitle">Link an existing vehicle or capture vehicle details manually.</p>
    </div>

    <div class="sf-card-body space-y-6">
        <div>
            <label class="sf-label">Existing Vehicle</label>
            <select name="vehicle_id" id="vehicle_id" class="sf-select">
                <option value="">-- Select Existing Vehicle --</option>
                @foreach($vehiclesCollection as $vehicle)
                    @php
                        $vehicleLabel = trim(
                            ($vehicle->year ? $vehicle->year . ' ' : '') .
                            ($vehicle->make?->name ?? $vehicle->vehicleMake?->name ?? '') . ' ' .
                            ($vehicle->model?->name ?? $vehicle->vehicleModel?->name ?? '') . ' ' .
                            ($vehicle->plate_number ? '(' . $vehicle->plate_number . ')' : '')
                        );
                    @endphp
                    <option value="{{ $vehicle->id }}" data-client-id="{{ $vehicle->client_id }}" @selected($selectedVehicleId === (string) $vehicle->id)>
                        {{ $vehicleLabel ?: 'Vehicle #' . $vehicle->id }}
                    </option>
                @endforeach
            </select>
            <p class="sf-help">Existing vehicle list can be filtered by selected client.</p>
            @error('vehicle_id')<div class="sf-error">{{ $message }}</div>@enderror
        </div>

        <div class="sf-divider"></div>

        <div>
            <h3 class="sf-section-title">Manual Vehicle Capture</h3>
            <p class="sf-section-subtitle">Use this when the vehicle does not exist yet.</p>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
                <label class="sf-label">Make</label>
                <select name="manual_make_id" id="manual_make_id" class="sf-select">
                    <option value="">-- Select Make --</option>
                    @foreach($makesCollection as $make)
                        <option value="{{ $make->id }}" @selected($selectedManualMakeId === (string) $make->id)>{{ $make->name }}</option>
                    @endforeach
                </select>
                @error('manual_make_id')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="sf-label">Model</label>
                <select name="manual_model_id" id="manual_model_id" class="sf-select">
                    <option value="">-- Select Model --</option>
                    @foreach($modelsCollection as $model)
                        <option value="{{ $model->id }}" data-make-id="{{ $model->make_id }}" @selected($selectedManualModelId === (string) $model->id)>{{ $model->name }}</option>
                    @endforeach
                </select>
                @error('manual_model_id')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="sf-label">Other Make</label>
                <input type="text" name="other_make" value="{{ $oldOr('other_make') }}" class="sf-input" placeholder="If make is not listed">
                @error('other_make')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="sf-label">Other Model</label>
                <input type="text" name="other_model" value="{{ $oldOr('other_model') }}" class="sf-input" placeholder="If model is not listed">
                @error('other_model')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="sf-label">Vehicle Year</label>
                <input type="text" name="vehicle_year" value="{{ $oldOr('vehicle_year') }}" class="sf-input" placeholder="2021">
                @error('vehicle_year')<div class="sf-error">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="sf-label">Plate Number</label>
                <input type="text" name="plate_number" value="{{ $oldOr('plate_number') }}" class="sf-input" placeholder="Dubai A 12345">
                @error('plate_number')<div class="sf-error">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>
