{{-- resources/views/admin/opportunities/partials/services.blade.php --}}

@php
    $selectedServices = old('service_type', $opportunity->service_type ?? []);

    if (!is_array($selectedServices)) {
        $selectedServices = explode(',', $selectedServices);
    }

    $selectedServices = collect($selectedServices)
        ->map(fn ($service) => trim((string) $service))
        ->filter()
        ->values()
        ->all();

    $services = [
        'Oil Change',
        'Brake Inspection',
        'Tire Rotation',
        'Battery Check',
        'Wheel Alignment',
        'Engine Repair',
        'Transmission Service',
        'AC Repair',
        'Suspension Work',
        'Car Wash',
        'Detailing',
        'Tinting',
        'Polishing',
        'Interior Cleaning',
        'Vehicle Inspection',
        'Emissions Test',
        'Registration Renewal',
    ];
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div>
        <label class="sf-label">
            Services Opted
        </label>

        <p class="sf-help">
            Select the services discussed with the customer.
        </p>
    </div>

    {{-- Service List --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($services as $service)
            <label class="rounded-2xl border border-white/10 bg-slate-950/60 p-4 transition hover:border-orange-400/30 hover:bg-slate-900">
                <div class="flex items-start gap-3">
                    <input type="checkbox"
                           name="service_type[]"
                           value="{{ $service }}"
                           @checked(in_array($service, $selectedServices))
                           class="mt-1 rounded border-white/10 bg-slate-950 text-orange-500 shadow-sm focus:ring-orange-400">

                    <span class="text-sm font-bold text-slate-200">
                        {{ $service }}
                    </span>
                </div>
            </label>
        @endforeach

        {{-- Other --}}
        <label class="rounded-2xl border border-orange-400/20 bg-orange-500/10 p-4 transition hover:border-orange-400/40 hover:bg-orange-500/20">
            <div class="flex items-start gap-3">
                <input type="checkbox"
                       id="service_other_checkbox"
                       name="service_type[]"
                       value="Other"
                       @checked(in_array('Other', $selectedServices))
                       onchange="toggleOtherServiceInput(this)"
                       class="mt-1 rounded border-white/10 bg-slate-950 text-orange-500 shadow-sm focus:ring-orange-400">

                <span>
                    <span class="block text-sm font-extrabold text-orange-300">
                        Other
                    </span>

                    <span class="mt-1 block text-xs font-medium text-orange-100/70">
                        Use this if the service is not listed.
                    </span>
                </span>
            </div>
        </label>
    </div>

    {{-- Other Service --}}
    <div id="other_service_input"
         class="rounded-2xl border border-white/10 bg-slate-950/60 p-4"
         style="{{ in_array('Other', $selectedServices) ? '' : 'display:none;' }}">
        <label class="sf-label">
            Specify Other Service
        </label>

        <input type="text"
               name="other_service_text"
               placeholder="Specify other service"
               value="{{ old('other_service_text', '') }}"
               class="sf-input">

        @error('other_service_text')
            <div class="sf-error">{{ $message }}</div>
        @enderror
    </div>

    @error('service_type')
        <div class="sf-error">{{ $message }}</div>
    @enderror

    @error('service_type.*')
        <div class="sf-error">{{ $message }}</div>
    @enderror

</div>

@push('scripts')
<script>
function toggleOtherServiceInput(checkbox) {
    const otherInput = document.getElementById('other_service_input');

    if (!otherInput) {
        return;
    }

    otherInput.style.display = checkbox.checked ? 'block' : 'none';
}
</script>
@endpush