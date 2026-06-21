@php
    $statusFormFields = [
        'booking_date' => $bookingDate ? $bookingDate->format('Y-m-d') : '',
        'slot' => $booking->slot ?? 'morning',
        'name' => $booking->name,
        'service_type' => $booking->service_type,
        'vehicle_id' => $booking->vehicle_id,
        'assigned_to' => $booking->assigned_to,
        'priority' => $booking->priority ?? 'medium',
        'expected_duration' => $booking->expected_duration,
        'expected_close_date' => $expectedCloseDate ? $expectedCloseDate->format('Y-m-d') : '',
        'pickup_required' => $booking->pickup_required ? '1' : '0',
        'pickup_address' => $booking->pickup_address,
        'pickup_contact_number' => $booking->pickup_contact_number,
        'notes' => $booking->notes,
    ];
@endphp

<div class="sf-booking-panel rounded-2xl border shadow-sm">
    <div class="flex flex-col gap-3 border-b border-white/10 p-5 lg:flex-row lg:items-center lg:justify-between">
        <h2 class="sf-section-title">Booking Status</h2>

        <div class="sf-booking-next-action rounded-full border px-4 py-2 text-sm font-bold">
            Next Action:
            <span class="sf-booking-value">{{ $nextAction }}</span>
        </div>
    </div>

    <div class="p-5">
        <div class="sf-booking-stage-grid">
            @foreach(['pending', 'scheduled', 'converted_to_job'] as $stage)
                <form method="POST" action="{{ route('admin.bookings.update', $booking) }}">
                    @csrf
                    @method('PUT')

                    @foreach($statusFormFields as $field => $value)
                        <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                    @endforeach

                    <input type="hidden" name="status" value="{{ $stage }}">

                    <button type="submit"
                            class="sf-booking-stage-button {{ $stage === $status || ($stage === 'scheduled' && in_array($status, ['confirmed', 'approved'], true)) || ($stage === 'converted_to_job' && $status === 'completed') ? 'is-active' : '' }}"
                            title="{{ $bookingStatusHelp[$stage] ?? '' }}">
                        <span>{{ $bookingStatusLabels[$stage] ?? ucfirst(str_replace('_', ' ', $stage)) }}</span>
                    </button>
                </form>
            @endforeach

            <details class="sf-booking-stage-lost">
                <summary class="sf-booking-stage-button {{ $status === 'reschedule_required' ? 'is-danger-active' : 'is-danger' }}"
                         title="{{ $bookingStatusHelp['reschedule_required'] ?? '' }}">
                    Rescheduling Required
                </summary>

                <form method="POST" action="{{ route('admin.bookings.update', $booking) }}" class="mt-3 rounded-2xl border border-red-300/30 bg-red-500/10 p-3">
                    @csrf
                    @method('PUT')

                    @foreach($statusFormFields as $field => $value)
                        <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                    @endforeach

                    <input type="hidden" name="status" value="reschedule_required">

                    <label for="status_reschedule_reason" class="sf-booking-mini-label">Reschedule Reason</label>
                    <textarea
                        id="status_reschedule_reason"
                        name="reschedule_reason"
                        class="sf-booking-mini-select min-h-[96px]"
                        required
                        placeholder="Why does this booking need rescheduling?"
                    >{{ $booking->reschedule_reason }}</textarea>

                    <button type="submit" class="sf-booking-stage-submit">
                        Save Reschedule Status
                    </button>
                </form>
            </details>

            <details class="sf-booking-stage-lost">
                <summary class="sf-booking-stage-button {{ in_array($status, ['lost', 'cancelled', 'canceled', 'rejected'], true) ? 'is-danger-active' : 'is-danger' }}"
                         title="{{ $bookingStatusHelp['lost'] ?? '' }}">
                    Lost Booking
                </summary>

                <form method="POST" action="{{ route('admin.bookings.update', $booking) }}" class="mt-3 rounded-2xl border border-red-300/30 bg-red-500/10 p-3">
                    @csrf
                    @method('PUT')

                    @foreach($statusFormFields as $field => $value)
                        <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                    @endforeach

                    <input type="hidden" name="status" value="lost">

                    <label for="status_lost_reason" class="sf-booking-mini-label">Lost Reason</label>
                    <select id="status_lost_reason" name="lost_reason" class="sf-booking-mini-select" required>
                        <option value="">Select reason</option>
                        @foreach($bookingLostReasons as $reason => $label)
                            <option value="{{ $reason }}" @selected($booking->lost_reason === $reason)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="sf-booking-stage-submit">
                        Save Lost Status
                    </button>
                </form>
            </details>
        </div>
    </div>
</div>
