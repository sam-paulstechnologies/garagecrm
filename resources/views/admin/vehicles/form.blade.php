<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf

    @if(($method ?? 'POST') === 'PUT')
        @method('PUT')
    @endif

    @php
        $veh = $vehicle ?? null;
        $oldOr = fn($k, $fallback = null) => old($k, $veh?->$k ?? $fallback);

        // If coming from /admin/vehicles/create?client_id=#
        $prefillClientId = isset($prefillClientId) ? $prefillClientId : request()->integer('client_id');
        $prefillClient = $prefillClientId ? ($clients->firstWhere('id', $prefillClientId) ?? null) : null;

        $selectedMakeId = (string) old('make_id', $veh->make_id ?? '');
        $selectedModelId = (string) old('model_id', $veh->model_id ?? '');

        $fmtDate = function ($v) {
            if (! $v) {
                return '';
            }

            try {
                return \Illuminate\Support\Carbon::parse($v)->format('Y-m-d');
            } catch (\Throwable $e) {
                return '';
            }
        };
    @endphp

    {{-- Errors --}}
    @if($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-100 text-red-800 px-4 py-3 text-sm">
            <div class="font-semibold mb-1">Please fix the following:</div>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Owner + Vehicle --}}
    <div class="bg-white border border-gray-100 rounded-xl p-5">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            Vehicle Details
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            {{-- Client --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Client</label>

                @if($prefillClientId)
                    <input type="hidden" name="client_id" value="{{ $prefillClientId }}">

                    <input type="text"
                           value="{{ $prefillClient?->name ?? 'Client' }}"
                           class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 text-sm"
                           readonly>
                @else
                    <select name="client_id"
                            required
                            class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                        <option value="">-- Select Client --</option>

                        @foreach($clients as $client)
                            <option value="{{ $client->id }}"
                                @selected(old('client_id', $veh->client_id ?? '') == $client->id)>
                                {{ $client->name }}
                            </option>
                        @endforeach
                    </select>
                @endif

                @error('client_id')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Make --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Make</label>

                <select id="make_id"
                        name="make_id"
                        class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm">
                    <option value="">-- Select Make --</option>

                    @foreach(($makes ?? collect()) as $m)
                        <option value="{{ $m->id }}" @selected($selectedMakeId === (string) $m->id)>
                            {{ $m->name }}
                        </option>
                    @endforeach
                </select>

                @error('make_id')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Model --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>

                <select id="model_id"
                        name="model_id"
                        class="block w-full border border-gray-300 rounded-lg px-3 py-2 bg-white text-sm"
                        disabled>
                    <option value="">{{ $selectedMakeId ? 'Select Model' : 'Select make first' }}</option>
                </select>

                @error('model_id')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Year --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>

                <input type="text"
                       name="year"
                       value="{{ $oldOr('year') }}"
                       placeholder="2021"
                       class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                @error('year')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Plate Number --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Plate Number</label>

                <input type="text"
                       name="plate_number"
                       value="{{ $oldOr('plate_number') }}"
                       placeholder="Dubai A 12345"
                       class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                @error('plate_number')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- VIN --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">VIN</label>

                <input type="text"
                       name="vin"
                       value="{{ $oldOr('vin') }}"
                       maxlength="17"
                       placeholder="17-character VIN"
                       class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase">

                <p class="text-xs text-gray-500 mt-1">
                    Optional for now, but improves profile completion.
                </p>

                @error('vin')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Color --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>

                <input type="text"
                       name="color"
                       value="{{ $oldOr('color') }}"
                       placeholder="White"
                       class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                @error('color')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Current Mileage --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Current Mileage</label>

                <input type="number"
                       name="current_mileage"
                       value="{{ $oldOr('current_mileage') }}"
                       min="0"
                       placeholder="85000"
                       class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                @error('current_mileage')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Expiry / Compliance --}}
    <div class="bg-white border border-gray-100 rounded-xl p-5">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            Expiry & Compliance
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            {{-- Registration / Mulkia Expiry --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Mulkia / Registration Expiry Date
                </label>

                <input type="date"
                       name="registration_expiry_date"
                       value="{{ old('registration_expiry_date', $fmtDate($veh->registration_expiry_date ?? null)) }}"
                       class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                @error('registration_expiry_date')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Insurance Expiry --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Insurance Expiry Date
                </label>

                <input type="date"
                       name="insurance_expiry_date"
                       value="{{ old('insurance_expiry_date', $fmtDate($veh->insurance_expiry_date ?? null)) }}"
                       class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                @error('insurance_expiry_date')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Last Inspection Date --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Last Inspection Date
                </label>

                <input type="date"
                       name="last_inspection_date"
                       value="{{ old('last_inspection_date', $fmtDate($veh->last_inspection_date ?? null)) }}"
                       class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                @error('last_inspection_date')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Inspection Expiry Date --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Inspection Expiry Date
                </label>

                <input type="date"
                       name="inspection_expiry_date"
                       value="{{ old('inspection_expiry_date', $fmtDate($veh->inspection_expiry_date ?? null)) }}"
                       class="block w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">

                @error('inspection_expiry_date')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex items-center justify-between gap-3">
        <a href="{{ url()->previous() }}"
           class="text-sm text-gray-600 hover:underline">
            ← Back
        </a>

        <button type="submit"
                class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
            {{ (($method ?? 'POST') === 'PUT') ? 'Update' : 'Save' }} Vehicle
        </button>
    </div>
</form>

{{-- Dependent Model dropdown --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const makeSel = document.getElementById('make_id');
    const modelSel = document.getElementById('model_id');

    const modelsByMake = @json(
        ($models ?? collect())
            ->groupBy('make_id')
            ->map(fn($g) => $g->map(fn($m) => ['id' => $m->id, 'name' => $m->name])->values())
            ->toArray()
    );

    const selectedMake = @json($selectedMakeId);
    const selectedModel = @json($selectedModelId);

    function resetModels(placeholder) {
        modelSel.innerHTML = '';

        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = placeholder || 'Select make first';

        modelSel.appendChild(opt);
        modelSel.disabled = true;
    }

    function populateModels(makeId, preselectId = '') {
        if (!makeId) {
            resetModels('Select make first');
            return;
        }

        const rows = modelsByMake[makeId] || [];

        modelSel.innerHTML = '';

        const head = document.createElement('option');
        head.value = '';
        head.textContent = rows.length ? '-- Select Model --' : 'No models found';

        modelSel.appendChild(head);

        rows.forEach(function (row) {
            const opt = document.createElement('option');
            opt.value = row.id;
            opt.textContent = row.name;

            if (String(preselectId) === String(row.id)) {
                opt.selected = true;
            }

            modelSel.appendChild(opt);
        });

        modelSel.disabled = false;
    }

    if (selectedMake) {
        populateModels(selectedMake, selectedModel);
    } else {
        resetModels('Select make first');
    }

    makeSel?.addEventListener('change', function (event) {
        populateModels(event.target.value);
    });
});
</script>