<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if($method === 'PUT')
        @method('PUT')
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Client -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Client</label>
            <select name="client_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">-- Select Client --</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected(old('client_id', $vehicle->client_id ?? '') == $client->id)>
                        {{ $client->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Make -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Make</label>
            <input type="text" name="make" value="{{ old('make', $vehicle->make ?? '') }}"
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
        </div>

        <!-- Model -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Model</label>
            <input type="text" name="model" value="{{ old('model', $vehicle->model ?? '') }}"
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
        </div>

        <!-- Trim -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Trim</label>
            <input type="text" name="trim" value="{{ old('trim', $vehicle->trim ?? '') }}"
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
        </div>

        <!-- Plate Number -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Plate Number</label>
            <input type="text" name="plate_number" value="{{ old('plate_number', $vehicle->plate_number ?? '') }}"
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
        </div>

        <!-- Year -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Year</label>
            <input type="text" name="year" value="{{ old('year', $vehicle->year ?? '') }}"
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
        </div>

        <!-- Color -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Color</label>
            <input type="text" name="color" value="{{ old('color', $vehicle->color ?? '') }}"
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
        </div>

        <!-- Registration Expiry -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Registration Expiry Date</label>
            <input type="date" name="registration_expiry_date"
                   value="{{ old('registration_expiry_date', $vehicle->registration_expiry_date ?? '') }}"
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
        </div>

        <!-- Insurance Expiry -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Insurance Expiry Date</label>
            <input type="date" name="insurance_expiry_date"
                   value="{{ old('insurance_expiry_date', $vehicle->insurance_expiry_date ?? '') }}"
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
        </div>
    </div>

    <!-- Submit -->
    <div>
        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
            {{ $method === 'PUT' ? 'Update' : 'Save' }} Vehicle
        </button>
    </div>
</form>
