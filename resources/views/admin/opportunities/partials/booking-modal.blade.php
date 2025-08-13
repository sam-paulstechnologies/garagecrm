<div class="mt-10 p-6 bg-gray-50 border border-gray-200 rounded">
    <h2 class="text-lg font-semibold mb-4">Book Service Appointment</h2>
    <form method="POST" action="{{ route('admin.bookings.store') }}">
        @csrf
        <input type="hidden" name="client_id" value="{{ $opportunity->client_id ?? '' }}">
        <input type="hidden" name="opportunity_id" value="{{ $opportunity->id ?? '' }}">

        <!-- Services -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Services</label>
            @php
                $selected = explode(',', $opportunity->service_type ?? '');
                $allServices = [
                    'Engine Repair', 'Tinting', 'Polishing', 'Interior Cleaning',
                    'Vehicle Inspection', 'Oil Change', 'AC Service', 'Detailing',
                    'Brake Inspection', 'Transmission Service', 'Battery Check',
                    'Suspension Work', 'Wheel Alignment', 'Emissions Test', 'Other'
                ];
            @endphp
            <div class="flex flex-wrap gap-2 mt-2">
                @foreach($allServices as $service)
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="services[]"
                               value="{{ $service }}" {{ in_array($service, $selected) ? 'checked' : '' }}>
                        <span>{{ $service }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Appointment Date -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Appointment Date</label>
            <input type="date" name="date" class="form-input mt-1 block w-full"
                   value="{{ \Carbon\Carbon::tomorrow()->format('Y-m-d') }}" required>
        </div>

        <!-- Time Slot -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Time Slot</label>
            <select name="slot" class="form-select mt-1 block w-full" required>
                <option value="">Select</option>
                <option value="Morning">Morning</option>
                <option value="Afternoon">Afternoon</option>
                <option value="Evening">Evening</option>
            </select>
        </div>

        <!-- Notes -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Notes</label>
            <textarea name="notes" rows="3" class="form-textarea mt-1 block w-full"></textarea>
        </div>

        <!-- Submit -->
        <div>
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                Create Booking
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('toggleServiceSelect');
        const serviceSelectBox = document.getElementById('serviceSelectBox');
        const stageSelect = document.querySelector('select[name="stage"]');
        const bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                serviceSelectBox.style.display = serviceSelectBox.style.display === 'none' ? 'block' : 'none';
            });
        }

        if (stageSelect) {
            stageSelect.addEventListener('change', function () {
                const normalized = this.value.toLowerCase().replace(/\s/g, '_');
                if (normalized === 'closed_won') {
                    setTimeout(() => bookingModal.show(), 300);
                }
            });
        }
    });
</script>
