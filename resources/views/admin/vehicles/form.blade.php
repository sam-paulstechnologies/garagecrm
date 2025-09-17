<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if(($method ?? 'POST') === 'PUT')
        @method('PUT')
    @endif

    @php
        $veh      = $vehicle ?? null;
        $oldOr    = fn($k, $fallback=null) => old($k, $veh?->$k ?? $fallback);

        // If coming from /admin/vehicles/create?client_id=#
        $prefillClientId = isset($prefillClientId) ? $prefillClientId : request()->integer('client_id');
        $prefillClient   = $prefillClientId ? ($clients->firstWhere('id', $prefillClientId) ?? null) : null;

        $selectedMakeId  = (string) old('make_id',  $veh->make_id  ?? '');
        $selectedModelId = (string) old('model_id', $veh->model_id ?? '');

        $fmtDate = function ($v) {
            if (!$v) return '';
            try { return \Illuminate\Support\Carbon::parse($v)->format('Y-m-d'); } catch (\Throwable $e) { return ''; }
        };
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Client (locks when prefilled) --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Client</label>
            @if($prefillClientId)
                <input type="hidden" name="client_id" value="{{ $prefillClientId }}">
                <input type="text" value="{{ $prefillClient?->name ?? 'Client' }}"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50" readonly>
            @else
                <select name="client_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">-- Select Client --</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" @selected(old('client_id', $veh->client_id ?? '') == $client->id)>
                            {{ $client->name }}
                        </option>
                    @endforeach
                </select>
            @endif
            @error('client_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Make --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Make</label>
            <select id="make_id" name="make_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">-- Select Make --</option>
                @foreach(($makes ?? collect()) as $m)
                    <option value="{{ $m->id }}" @selected($selectedMakeId === (string)$m->id)>{{ $m->name }}</option>
                @endforeach
            </select>
            @error('make_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Model (dependent on Make) --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Model</label>
            <select id="model_id" name="model_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" disabled>
                <option value="">{{ $selectedMakeId ? 'Select Model' : 'Select make first' }}</option>
            </select>
            @error('model_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Trim --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Trim</label>
            <input type="text" name="trim" value="{{ $oldOr('trim') }}"
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
        </div>

        {{-- Plate Number --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Plate Number</label>
            <input type="text" name="plate_number" value="{{ $oldOr('plate_number') }}"
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            @error('plate_number')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Year --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Year</label>
            <input type="text" name="year" value="{{ $oldOr('year') }}"
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
        </div>

        {{-- Color --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Color</label>
            <input type="text" name="color" value="{{ $oldOr('color') }}"
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
        </div>

        {{-- Registration Expiry --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Registration Expiry Date</label>
            <input type="date" name="registration_expiry_date"
                   value="{{ old('registration_expiry_date', $fmtDate($veh->registration_expiry_date ?? null)) }}"
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
        </div>

        {{-- Insurance Expiry --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Insurance Expiry Date</label>
            <input type="date" name="insurance_expiry_date"
                   value="{{ old('insurance_expiry_date', $fmtDate($veh->insurance_expiry_date ?? null)) }}"
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
        </div>
    </div>

    <div>
        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
            {{ (($method ?? 'POST') === 'PUT') ? 'Update' : 'Save' }} Vehicle
        </button>
    </div>
</form>

{{-- Dependent Model dropdown (same pattern as Opportunities) --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const makeSel  = document.getElementById('make_id');
    const modelSel = document.getElementById('model_id');

    // Build a { make_id: [{id,name}, ...] } map from backend
    const modelsByMake = @json(
        ($models ?? collect())
            ->groupBy('make_id')
            ->map(fn($g) => $g->map(fn($m) => ['id'=>$m->id,'name'=>$m->name])->values())
            ->toArray()
    );

    const selectedMake  = @json($selectedMakeId);
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
        if (!makeId) { resetModels('Select make first'); return; }
        const rows = modelsByMake[makeId] || [];
        modelSel.innerHTML = '';
        const head = document.createElement('option');
        head.value = '';
        head.textContent = '-- Select Model --';
        modelSel.appendChild(head);

        rows.forEach(r => {
            const opt = document.createElement('option');
            opt.value = r.id;
            opt.textContent = r.name;
            if (String(preselectId) === String(r.id)) opt.selected = true;
            modelSel.appendChild(opt);
        });
        modelSel.disabled = false;
    }

    // Init
    if (selectedMake) populateModels(selectedMake, selectedModel);
    else resetModels('Select make first');

    // Change
    makeSel?.addEventListener('change', (e) => populateModels(e.target.value));
});
</script>
