{{-- resources/views/admin/opportunities/form.blade.php --}}
<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="p-4 rounded bg-red-50 text-red-700">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>— {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $opp = $opportunity ?? null;
        $oldOr = fn($k, $fallback=null) => old($k, $opp[$k] ?? $fallback);
        $selectedMakeId  = (string) old('vehicle_make_id', $opp->vehicle_make_id ?? '');
        $selectedModelId = (string) old('vehicle_model_id', $opp->vehicle_model_id ?? '');
        $stageVal        = $oldOr('stage', 'new');
        $priorityVal     = $oldOr('priority', null);
        $servicesInitial = collect(explode(',', (string) $oldOr('service_type', '')))
                            ->map(fn($s)=>trim($s))
                            ->filter()
                            ->values()->all();
    @endphp

    {{-- Hidden fields used for Closed Won → Booking --}}
    <input type="hidden" name="booking_date" id="booking_date" value="{{ old('booking_date') }}">
    <input type="hidden" name="booking_time" id="booking_time" value="{{ old('booking_time') }}">

    {{-- Row 1: Client / Lead --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700">Client *</label>
            <select name="client_id" class="mt-1 block w-full rounded border-gray-300" required>
                <option value="">-- Select Client --</option>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}" @selected(old('client_id', $opp->client_id ?? '') == $c->id)>
                        {{ $c->name }} - {{ $c->phone }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Lead (optional)</label>
            <select name="lead_id" class="mt-1 block w-full rounded border-gray-300">
                <option value="">-- None --</option>
                @foreach($leads as $l)
                    <option value="{{ $l->id }}" @selected(old('lead_id', $opp->lead_id ?? '') == $l->id)>
                        {{ $l->name }} {{ $l->phone ? ' - '.$l->phone : '' }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Row 2: Title / Stage --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700">Title *</label>
            <input type="text" name="title" value="{{ $oldOr('title') }}"
                   class="mt-1 block w-full rounded border-gray-300" required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Stage *</label>
            <select name="stage" id="stage_select" class="mt-1 block w-full rounded border-gray-300" required>
                @foreach (['new'=>'New','attempting_contact'=>'Attempting Contact','appointment'=>'Appointment','offer'=>'Offer','closed_won'=>'Closed Won','closed_lost'=>'Closed Lost'] as $val=>$label)
                    <option value="{{ $val }}" @selected($stageVal === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Row 3: Priority / Assigned To --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700">Priority</label>
            <select name="priority" class="mt-1 block w-full rounded border-gray-300">
                <option value="">-- None --</option>
                @foreach (['low'=>'Low','medium'=>'Medium','high'=>'High'] as $val=>$label)
                    <option value="{{ $val }}" @selected($priorityVal === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Assigned To (User ID)</label>
            <input type="number" name="assigned_to" value="{{ $oldOr('assigned_to') }}"
                   class="mt-1 block w-full rounded border-gray-300" min="1" step="1">
        </div>
    </div>

    {{-- Row 4: Vehicle Make / Model + other toggles --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700">Vehicle Make</label>
            <select id="vehicle_make_id" name="vehicle_make_id" class="mt-1 block w-full rounded border-gray-300">
                <option value="">-- Select Make --</option>
                @foreach($makes as $mk)
                    <option value="{{ $mk->id }}" @selected($selectedMakeId === (string)$mk->id)>{{ $mk->name }}</option>
                @endforeach
                <option value="other" @selected($selectedMakeId === 'other')>Other</option>
            </select>
            <input type="text" id="other_make" name="other_make"
                   value="{{ $oldOr('other_make') }}"
                   class="mt-2 block w-full rounded border-gray-300"
                   placeholder="Other make"
                   style="display:none;">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Vehicle Model</label>
            <select id="vehicle_model_id" name="vehicle_model_id" class="mt-1 block w-full rounded border-gray-300" disabled>
                <option value="">{{ $selectedMakeId ? 'Select Model' : 'Select make first' }}</option>
            </select>
            <input type="text" id="other_model" name="other_model"
                   value="{{ $oldOr('other_model') }}"
                   class="mt-2 block w-full rounded border-gray-300"
                   placeholder="Other model"
                   style="display:none;">
        </div>
    </div>

    {{-- Row 5: Money / Duration --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700">Estimated Value (AED)</label>
            <input type="number" step="0.01" name="value" value="{{ $oldOr('value') }}"
                   class="mt-1 block w-full rounded border-gray-300">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Expected Duration (Days)</label>
            <input type="number" name="expected_duration" value="{{ $oldOr('expected_duration') }}"
                   class="mt-1 block w-full rounded border-gray-300">
        </div>
    </div>

    {{-- Row 6: Next Follow Up / Score --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700">Next Follow-Up</label>
            <input type="date" name="next_follow_up" value="{{ $oldOr('next_follow_up') }}"
                   class="mt-1 block w-full rounded border-gray-300">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Opportunity Score</label>
            <input type="number" name="score" value="{{ $oldOr('score') }}"
                   class="mt-1 block w-full rounded border-gray-300">
        </div>
    </div>

    {{-- Services Opted --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Services Opted</label>
        {{-- hidden field your controller already accepts --}}
        <input type="hidden" name="service_type" id="service_type" value="{{ e($oldOr('service_type','')) }}">

        @php
            $serviceList = [
                'Oil Change','Battery Check','Transmission Service','Car Wash',
                'Polishing','Emissions Test','AC Repair','Detailing',
                'Interior Cleaning','Registration Renewal','Suspension Work','Tinting',
                'Vehicle Inspection','Other'
            ];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-2">
            @foreach($serviceList as $svc)
                @php $isChecked = in_array($svc, $servicesInitial, true); @endphp
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" class="svc-checkbox rounded border-gray-300"
                           value="{{ $svc }}" @checked($isChecked)>
                    <span>{{ $svc }}</span>
                </label>
            @endforeach
        </div>

        <input type="text" id="other_service_input"
               class="mt-3 w-full rounded border-gray-300"
               placeholder="Specify other service"
               style="display:none;">
    </div>

    {{-- Notes / Close Reason / Converted --}}
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Notes</label>
            <textarea name="notes" rows="4" class="mt-1 block w-full rounded border-gray-300">{{ $oldOr('notes') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Close Reason</label>
            <input type="text" name="close_reason" value="{{ $oldOr('close_reason') }}"
                   class="mt-1 block w-full rounded border-gray-300">
        </div>

        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="is_converted" value="1"
                   class="rounded border-gray-300"
                   @checked(old('is_converted', $opp->is_converted ?? false))>
            <span>Converted to Job/Booking</span>
        </label>
    </div>

    @if (strtolower($opp->stage ?? '') !== 'closed_won')
        <div>
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                {{ $isEdit ? 'Update' : 'Create' }} Opportunity
            </button>
        </div>
    @endif
</form>

@if (strtolower($opp->stage ?? '') === 'closed_won')
    @include('admin.opportunities.partials.booking-modal')
@endif

{{-- ===== Booking Modal (opens when Stage → Closed Won) ===== --}}
<div id="bookingModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-40">
  <div class="bg-white w-full max-w-md rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold mb-4">Create Booking</h3>

    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Booking Date *</label>
        <input type="date" id="bm_date" class="mt-1 w-full rounded border-gray-300">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Booking Time *</label>
        <input type="time" id="bm_time" class="mt-1 w-full rounded border-gray-300">
        {{-- If you prefer slots, swap this for a <select> with Morning/Evening --}}
      </div>
    </div>

    <div class="mt-6 flex justify-end gap-3">
      <button type="button" id="bm_cancel" class="px-4 py-2 rounded border">Cancel</button>
      <button type="button" id="bm_confirm" class="px-4 py-2 rounded bg-indigo-600 text-white">Confirm</button>
    </div>
  </div>
</div>

{{-- ===== Dependent dropdown + services + booking scripting ===== --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Data from backend ---
    const modelsByMake = @json(
        $models->groupBy('make_id')->map(fn($g) => $g->map(fn($m) => ['id'=>$m->id,'name'=>$m->name])->values())->toArray()
    );

    const selectedMake   = @json($selectedMakeId);
    const selectedModel  = @json($selectedModelId);

    // --- Elements ---
    const makeSel   = document.getElementById('vehicle_make_id');
    const modelSel  = document.getElementById('vehicle_model_id');
    const otherMake = document.getElementById('other_make');
    const otherModel= document.getElementById('other_model');

    const svcChecks = Array.from(document.querySelectorAll('.svc-checkbox'));
    const svcHidden = document.getElementById('service_type');
    const otherSvc  = document.getElementById('other_service_input');

    const stageSelect = document.getElementById('stage_select');
    const form        = stageSelect?.closest('form');
    let previousStage = stageSelect?.value || 'new';

    const bookingModal = document.getElementById('bookingModal');
    const bmDate       = document.getElementById('bm_date');
    const bmTime       = document.getElementById('bm_time');
    const bmCancel     = document.getElementById('bm_cancel');
    const bmOK         = document.getElementById('bm_confirm');
    const bookingDateHidden = document.getElementById('booking_date');
    const bookingTimeHidden = document.getElementById('booking_time');

    // -------- Vehicle Make/Model helpers --------
    function populateModels(makeId, preselectId = null) {
        modelSel.innerHTML = '';
        if (!makeId || makeId === 'other') {
            modelSel.disabled = true;
            modelSel.insertAdjacentHTML('beforeend', `<option value="">${makeId ? 'Select model' : 'Select make first'}</option>`);
            return;
        }
        const rows = modelsByMake[makeId] || [];
        modelSel.disabled = false;
        modelSel.insertAdjacentHTML('beforeend', `<option value="">-- Select Model --</option>`);
        rows.forEach(r => {
            const opt = document.createElement('option');
            opt.value = r.id;
            opt.textContent = r.name;
            if (String(preselectId || '') === String(r.id)) opt.selected = true;
            modelSel.appendChild(opt);
        });
        // add "Other" option
        const otherOpt = document.createElement('option');
        otherOpt.value = 'other';
        otherOpt.textContent = 'Other';
        if (String(preselectId) === 'other') otherOpt.selected = true;
        modelSel.appendChild(otherOpt);
    }

    function toggleOtherInputs() {
        const mk = makeSel.value;
        const md = modelSel.value;

        const showOtherMake  = (mk === 'other');
        otherMake.style.display = showOtherMake ? 'block' : 'none';

        const showOtherModel = (!showOtherMake && md === 'other');
        otherModel.style.display = showOtherModel ? 'block' : 'none';
    }

    // Init on load
    if (selectedMake) {
        if (selectedMake === 'other') {
            populateModels('other', null);
        } else {
            populateModels(selectedMake, selectedModel || null);
        }
    } else {
        populateModels('', null);
    }
    toggleOtherInputs();

    // Change handlers
    makeSel.addEventListener('change', function () {
        const mk = this.value;
        if (mk === 'other') {
            modelSel.value = '';
            populateModels('other', null);
        } else {
            populateModels(mk, null);
        }
        toggleOtherInputs();
    });
    modelSel.addEventListener('change', toggleOtherInputs);

    // -------- Services helpers --------
    function recomputeServiceTypeHidden() {
        const selected = svcChecks.filter(cb => cb.checked).map(cb => cb.value);
        const otherIdx = selected.indexOf('Other');
        let otherText = '';
        if (otherIdx !== -1) {
            otherText = otherSvc.value?.trim() || '';
            if (otherText.length) selected[otherIdx] = otherText;
        }
        svcHidden.value = selected.join(', ');
    }

    function toggleOtherServiceInput() {
        const otherChecked = svcChecks.some(cb => cb.value === 'Other' && cb.checked);
        otherSvc.style.display = otherChecked ? 'block' : 'none';
        if (!otherChecked) otherSvc.value = '';
        recomputeServiceTypeHidden();
    }

    // init services section from hidden field
    const preset = (svcHidden.value || '').split(',').map(s => s.trim()).filter(Boolean);
    if (preset.length) {
        const known = new Set(svcChecks.map(cb => cb.value));
        const unknown = preset.filter(v => !known.has(v) && v.length);
        if (unknown.length) {
            const otherCb = svcChecks.find(cb => cb.value === 'Other');
            if (otherCb) {
                otherCb.checked = true;
                otherSvc.style.display = 'block';
                otherSvc.value = unknown.join(', ');
            }
        } else {
            svcChecks.forEach(cb => cb.checked = preset.includes(cb.value));
        }
    }
    toggleOtherServiceInput();

    svcChecks.forEach(cb => cb.addEventListener('change', () => {
        if (cb.value === 'Other') toggleOtherServiceInput();
        else recomputeServiceTypeHidden();
    }));
    otherSvc.addEventListener('input', recomputeServiceTypeHidden);

    // -------- Booking modal (Stage: Closed Won) --------
    function openModal() {
        bookingModal.classList.remove('hidden');
        bookingModal.classList.add('flex');
        if (!bmDate.value) {
            const d = new Date();
            bmDate.value = d.toISOString().slice(0,10);
        }
        if (!bmTime.value) bmTime.value = '10:00';
    }
    function closeModal() {
        bookingModal.classList.add('hidden');
        bookingModal.classList.remove('flex');
    }

    if (stageSelect && form) {
        stageSelect.addEventListener('focus', () => previousStage = stageSelect.value);
        stageSelect.addEventListener('change', function () {
            const value = this.value.toLowerCase().replace(/\s/g, '_');
            if (value === 'closed_won') {
                openModal(); // collect booking date/time first
            } else {
                bookingDateHidden.value = '';
                bookingTimeHidden.value = '';
            }
        });
    }

    bmCancel?.addEventListener('click', () => {
        // revert stage
        if (stageSelect) stageSelect.value = previousStage || 'offer';
        closeModal();
    });

    bmOK?.addEventListener('click', () => {
        const d = bmDate.value;
        const t = bmTime.value;
        if (!d || !t) {
            alert('Please select booking date and time.');
            return;
        }
        bookingDateHidden.value = d;
        bookingTimeHidden.value = t;
        closeModal();
        form?.submit();
    });
});
</script>
