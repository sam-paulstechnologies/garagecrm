{{-- resources/views/admin/opportunities/partials/booking-modal.blade.php --}}

<div class="sf-card mt-10">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            Book Service Appointment
        </h2>

        <p class="sf-section-subtitle">
            Create a booking from this opportunity after customer confirmation.
        </p>
    </div>

    <form method="POST" action="{{ route('admin.bookings.store') }}">
        @csrf

        <input type="hidden" name="client_id" value="{{ $opportunity->client_id ?? '' }}">
        <input type="hidden" name="opportunity_id" value="{{ $opportunity->id ?? '' }}">

        <div class="sf-card-body space-y-6">

            {{-- Services --}}
            <div>
                <label class="sf-label">
                    Services
                </label>

                @php
                    $selected = array_map('trim', explode(',', $opportunity->service_type ?? ''));

                    $allServices = [
                        'Engine Repair',
                        'Tinting',
                        'Polishing',
                        'Interior Cleaning',
                        'Vehicle Inspection',
                        'Oil Change',
                        'AC Service',
                        'Detailing',
                        'Brake Inspection',
                        'Transmission Service',
                        'Battery Check',
                        'Suspension Work',
                        'Wheel Alignment',
                        'Emissions Test',
                        'Other',
                    ];
                @endphp

                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($allServices as $service)
                        <label class="rounded-2xl border border-white/10 bg-slate-950/60 p-4 transition hover:border-orange-400/30 hover:bg-slate-900">
                            <div class="flex items-start gap-3">
                                <input type="checkbox"
                                       name="services[]"
                                       value="{{ $service }}"
                                       @checked(in_array($service, $selected))
                                       class="mt-1 rounded border-white/10 bg-slate-950 text-orange-500 shadow-sm focus:ring-orange-400">

                                <span class="text-sm font-bold text-slate-200">
                                    {{ $service }}
                                </span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Appointment Date --}}
            <div>
                <label class="sf-label">
                    Appointment Date <span class="text-red-300">*</span>
                </label>

                <input type="date"
                       name="date"
                       class="sf-input"
                       value="{{ \Carbon\Carbon::tomorrow()->format('Y-m-d') }}"
                       required>
            </div>

            {{-- Time Slot --}}
            <div>
                <label class="sf-label">
                    Time Slot <span class="text-red-300">*</span>
                </label>

                <select name="slot" class="sf-select" required>
                    <option value="">Select</option>
                    <option value="Morning">Morning</option>
                    <option value="Afternoon">Afternoon</option>
                    <option value="Evening">Evening</option>
                </select>
            </div>

            {{-- Notes --}}
            <div>
                <label class="sf-label">
                    Notes
                </label>

                <textarea name="notes"
                          rows="3"
                          class="sf-textarea"
                          placeholder="Add booking notes, pickup/drop details, or customer instructions..."></textarea>
            </div>

        </div>

        <div class="sf-card-footer">
            <div class="flex flex-wrap justify-end gap-2">
                <button type="submit" class="sf-btn-primary">
                    Create Booking
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('toggleServiceSelect');
    const serviceSelectBox = document.getElementById('serviceSelectBox');
    const stageSelect = document.querySelector('select[name="stage"]');
    const bookingModalEl = document.getElementById('bookingModal');

    if (toggleBtn && serviceSelectBox) {
        toggleBtn.addEventListener('click', function () {
            serviceSelectBox.style.display = serviceSelectBox.style.display === 'none' ? 'block' : 'none';
        });
    }

    if (stageSelect && bookingModalEl && window.bootstrap) {
        const bookingModal = new bootstrap.Modal(bookingModalEl);

        stageSelect.addEventListener('change', function () {
            const normalized = this.value.toLowerCase().replace(/\s/g, '_');

            if (normalized === 'closed_won') {
                setTimeout(function () {
                    bookingModal.show();
                }, 300);
            }
        });
    }
});
</script>
@endpush