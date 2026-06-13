<style>
    .sf-vehicle-edit-hero,
    .sf-vehicle-form-panel {
        border-color: rgba(30, 41, 59, 1);
        background: rgba(15, 23, 42, 0.70);
        color: #ffffff;
    }

    .sf-vehicle-form-title {
        color: #ffffff;
    }

    .sf-vehicle-form-label {
        color: #cbd5e1;
    }

    .sf-vehicle-form-input {
        border-color: #334155;
        background: rgba(2, 6, 23, 0.70);
        color: #e2e8f0;
    }

    .sf-vehicle-form-input::placeholder {
        color: #64748b;
    }

    .sf-vehicle-form-input:disabled {
        opacity: 0.72;
    }

    .sf-vehicle-form-help,
    .sf-vehicle-form-secondary {
        color: #94a3b8;
    }

    .sf-vehicle-form-secondary:hover {
        color: #fb923c;
    }

    html[data-theme="light"] .sf-vehicle-edit-hero,
    html[data-theme="light"] .sf-vehicle-form-panel {
        border-color: #d9e1ec !important;
        background: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08) !important;
    }

    html[data-theme="light"] .sf-vehicle-edit-hero h1,
    html[data-theme="light"] .sf-vehicle-form-title {
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-vehicle-edit-hero p {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-vehicle-edit-hero p:first-child {
        color: #ea580c !important;
    }

    html[data-theme="light"] .sf-vehicle-form-label {
        color: #334155 !important;
    }

    html[data-theme="light"] .sf-vehicle-form-input {
        border-color: #cbd5e1 !important;
        background: #ffffff !important;
        color: #0f172a !important;
    }

    html[data-theme="light"] .sf-vehicle-form-input[readonly],
    html[data-theme="light"] .sf-vehicle-form-input:disabled {
        background: #f8fafc !important;
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-vehicle-form-input::placeholder {
        color: #94a3b8 !important;
    }

    html[data-theme="light"] .sf-vehicle-form-help,
    html[data-theme="light"] .sf-vehicle-form-secondary {
        color: #475569 !important;
    }

    html[data-theme="light"] .sf-vehicle-form-secondary:hover {
        color: #ea580c !important;
    }
</style>

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
        $selectedModels = $selectedMakeId
            ? ($models ?? collect())->filter(fn ($m) => (string) ($m->make_id ?? $m->vehicle_make_id ?? '') === $selectedMakeId)
            : collect();

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
    <div class="sf-vehicle-form-panel rounded-2xl border p-5 shadow-sm">
        <h2 class="sf-vehicle-form-title text-lg font-extrabold mb-4">
            Vehicle Details
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            {{-- Client --}}
            <div>
                <label class="sf-vehicle-form-label block text-sm font-bold mb-1">Client</label>

                @if($prefillClientId)
                    <input type="hidden" name="client_id" value="{{ $prefillClientId }}">

                    <input type="text"
                           value="{{ $prefillClient?->name ?? 'Client' }}"
                           class="sf-vehicle-form-input block w-full rounded-lg border px-3 py-2 text-sm font-semibold"
                           readonly>
                @else
                    <select name="client_id"
                            required
                            class="sf-vehicle-form-input block w-full rounded-lg border px-3 py-2 text-sm font-semibold">
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
                <label class="sf-vehicle-form-label block text-sm font-bold mb-1">Make</label>

                <select id="make_id"
                        name="make_id"
                        class="sf-vehicle-form-input block w-full rounded-lg border px-3 py-2 text-sm font-semibold">
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
                <label class="sf-vehicle-form-label block text-sm font-bold mb-1">Model</label>

                <select id="model_id"
                        name="model_id"
                        class="sf-vehicle-form-input block w-full rounded-lg border px-3 py-2 text-sm font-semibold"
                        {{ $selectedMakeId ? '' : 'disabled' }}>
                    <option value="">{{ $selectedMakeId ? 'Select Model' : 'Select make first' }}</option>

                    @foreach($selectedModels as $model)
                        <option value="{{ $model->id }}" @selected($selectedModelId === (string) $model->id)>
                            {{ $model->name }}
                        </option>
                    @endforeach
                </select>

                @error('model_id')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Year --}}
            <div>
                <label class="sf-vehicle-form-label block text-sm font-bold mb-1">Year</label>

                <input type="text"
                       name="year"
                       value="{{ $oldOr('year') }}"
                       placeholder="2021"
                       class="sf-vehicle-form-input block w-full rounded-lg border px-3 py-2 text-sm font-semibold">

                @error('year')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Plate Number --}}
            <div id="vehicle-plate">
                <label class="sf-vehicle-form-label block text-sm font-bold mb-1">Plate Number</label>

                <input type="text"
                       name="plate_number"
                       value="{{ $oldOr('plate_number') }}"
                       placeholder="Dubai A 12345"
                       class="sf-vehicle-form-input block w-full rounded-lg border px-3 py-2 text-sm font-semibold">

                @error('plate_number')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- VIN --}}
            <div id="vehicle-vin">
                <label class="sf-vehicle-form-label block text-sm font-bold mb-1">VIN</label>

                <input type="text"
                       name="vin"
                       value="{{ $oldOr('vin') }}"
                       maxlength="17"
                       placeholder="17-character VIN"
                       class="sf-vehicle-form-input block w-full rounded-lg border px-3 py-2 text-sm font-semibold uppercase">

                <p class="sf-vehicle-form-help text-xs mt-1">
                    Optional for now, but improves profile completion.
                </p>

                @error('vin')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Color --}}
            <div>
                <label class="sf-vehicle-form-label block text-sm font-bold mb-1">Color</label>

                <input type="text"
                       name="color"
                       value="{{ $oldOr('color') }}"
                       placeholder="White"
                       class="sf-vehicle-form-input block w-full rounded-lg border px-3 py-2 text-sm font-semibold">

                @error('color')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Current Mileage --}}
            <div id="vehicle-mileage">
                <label class="sf-vehicle-form-label block text-sm font-bold mb-1">Current Mileage</label>

                <input type="number"
                       name="current_mileage"
                       value="{{ $oldOr('current_mileage') }}"
                       min="0"
                       placeholder="85000"
                       class="sf-vehicle-form-input block w-full rounded-lg border px-3 py-2 text-sm font-semibold">

                @error('current_mileage')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Expiry / Compliance --}}
    <div class="sf-vehicle-form-panel rounded-2xl border p-5 shadow-sm">
        <h2 class="sf-vehicle-form-title text-lg font-extrabold mb-4">
            Expiry & Compliance
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            {{-- Registration / Mulkia Expiry --}}
            <div id="vehicle-registration">
                <label class="sf-vehicle-form-label block text-sm font-bold mb-1">
                    Mulkia / Registration Expiry Date
                </label>

                <input type="date"
                       name="registration_expiry_date"
                       value="{{ old('registration_expiry_date', $fmtDate($veh->registration_expiry_date ?? null)) }}"
                       class="sf-vehicle-form-input block w-full rounded-lg border px-3 py-2 text-sm font-semibold">

                @error('registration_expiry_date')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Insurance Expiry --}}
            <div id="vehicle-insurance">
                <label class="sf-vehicle-form-label block text-sm font-bold mb-1">
                    Insurance Expiry Date
                </label>

                <input type="date"
                       name="insurance_expiry_date"
                       value="{{ old('insurance_expiry_date', $fmtDate($veh->insurance_expiry_date ?? null)) }}"
                       class="sf-vehicle-form-input block w-full rounded-lg border px-3 py-2 text-sm font-semibold">

                @error('insurance_expiry_date')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Last Inspection Date --}}
            <div>
                <label class="sf-vehicle-form-label block text-sm font-bold mb-1">
                    Last Inspection Date
                </label>

                <input type="date"
                       name="last_inspection_date"
                       value="{{ old('last_inspection_date', $fmtDate($veh->last_inspection_date ?? null)) }}"
                       max="{{ today()->toDateString() }}"
                       class="sf-vehicle-form-input block w-full rounded-lg border px-3 py-2 text-sm font-semibold">

                @error('last_inspection_date')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Inspection Expiry Date --}}
            <div>
                <label class="sf-vehicle-form-label block text-sm font-bold mb-1">
                    Inspection Expiry Date
                </label>

                <input type="date"
                       name="inspection_expiry_date"
                       value="{{ old('inspection_expiry_date', $fmtDate($veh->inspection_expiry_date ?? null)) }}"
                       class="sf-vehicle-form-input block w-full rounded-lg border px-3 py-2 text-sm font-semibold">

                @error('inspection_expiry_date')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex items-center justify-between gap-3">
        <a href="{{ url()->previous() }}"
           class="sf-vehicle-form-secondary text-sm font-bold hover:underline">
            ← Back
        </a>

        <button type="submit"
                class="rounded-xl bg-orange-500 px-6 py-2 text-sm font-extrabold text-white shadow-lg shadow-orange-950/20 transition hover:bg-orange-600">
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
            ->groupBy(fn($m) => $m->make_id ?? $m->vehicle_make_id ?? '')
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
