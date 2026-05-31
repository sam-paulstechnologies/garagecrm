<div class="sf-booking-panel rounded-2xl border shadow-sm">
    <div class="border-b border-white/10 p-5">
        <h2 class="sf-section-title">
            Vehicle
        </h2>
    </div>

    <div class="space-y-4 p-5 text-sm">
        <div>
            <div class="text-xs font-bold uppercase tracking-wide sf-booking-faint">Vehicle</div>
            <div class="mt-1 font-extrabold sf-booking-value">
                {{ $vehicleLabel !== '' ? $vehicleLabel : 'No vehicle added' }}
            </div>
        </div>

        @if(!empty($booking->vehicle?->plate_number))
            <div>
                <div class="text-xs font-bold uppercase tracking-wide sf-booking-faint">Plate Number</div>
                <div class="mt-1 font-bold sf-booking-muted">
                    {{ $booking->vehicle->plate_number }}
                </div>
            </div>
        @endif

        @if(!empty($booking->vehicle?->vin))
            <div>
                <div class="text-xs font-bold uppercase tracking-wide sf-booking-faint">VIN</div>
                <div class="mt-1 break-all font-bold sf-booking-muted">
                    {{ $booking->vehicle->vin }}
                </div>
            </div>
        @endif
    </div>
</div>
