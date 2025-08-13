<!-- Services -->
<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">Services Opted</label>
    @php
        $selectedServices = old('service_type', $opportunity->service_type ?? []);
        if (!is_array($selectedServices)) {
            $selectedServices = explode(',', $selectedServices);
        }

        $services = [
            'Oil Change', 'Brake Inspection', 'Tire Rotation', 'Battery Check', 'Wheel Alignment',
            'Engine Repair', 'Transmission Service', 'AC Repair', 'Suspension Work',
            'Car Wash', 'Detailing', 'Tinting', 'Polishing', 'Interior Cleaning',
            'Vehicle Inspection', 'Emissions Test', 'Registration Renewal',
        ];
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
        @foreach($services as $service)
            <label class="flex items-center space-x-2">
                <input type="checkbox" name="service_type[]" value="{{ $service }}"
                       @checked(in_array($service, $selectedServices))
                       class="rounded border-gray-300 text-indigo-600 shadow-sm">
                <span>{{ $service }}</span>
            </label>
        @endforeach

        <!-- Other -->
        <label class="flex items-center space-x-2">
            <input type="checkbox" id="service_other_checkbox"
                   name="service_type[]" value="Other"
                   @checked(in_array('Other', $selectedServices))
                   onchange="toggleOtherServiceInput(this)"
                   class="rounded border-gray-300 text-indigo-600 shadow-sm">
            <span>Other</span>
        </label>
    </div>

    <div id="other_service_input" class="mt-2" style="{{ in_array('Other', $selectedServices) ? '' : 'display:none;' }}">
        <input type="text" name="other_service_text" placeholder="Specify other service"
               value="{{ old('other_service_text', '') }}"
               class="block w-full border-gray-300 rounded-md shadow-sm">
    </div>
</div>
