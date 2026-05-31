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
        <div>
            <label for="vehicle_id" class="sf-label">
                Existing Vehicle
            </label>

            <select id="vehicle_id" name="vehicle_id" class="sf-select">
                <option value="">- Select Vehicle -</option>

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
                        <option value="">- Select Make -</option>

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
                        <option value="">- Select Model -</option>

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
