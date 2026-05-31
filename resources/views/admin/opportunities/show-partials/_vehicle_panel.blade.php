<div class="sf-opportunity-panel rounded-2xl border shadow-sm">
    <div class="border-b border-white/10 p-5">
        <h2 class="sf-section-title">Vehicle</h2>
    </div>

    <div class="space-y-4 p-5 text-sm">
        <div>
            <div class="text-xs font-bold uppercase tracking-wide sf-opportunity-muted">Vehicle</div>
            <div class="mt-1 font-extrabold sf-opportunity-value">{{ $vehicleLabel !== '' ? $vehicleLabel : 'No vehicle added' }}</div>
        </div>

        @if(!empty($opportunity->vehicle_year))
            <div>
                <div class="text-xs font-bold uppercase tracking-wide sf-opportunity-muted">Year</div>
                <div class="mt-1 font-bold sf-opportunity-value">{{ $opportunity->vehicle_year }}</div>
            </div>
        @endif

        @if(!empty($opportunity->plate_number))
            <div>
                <div class="text-xs font-bold uppercase tracking-wide sf-opportunity-muted">Plate Number</div>
                <div class="mt-1 font-bold sf-opportunity-value">{{ $opportunity->plate_number }}</div>
            </div>
        @endif
    </div>
</div>
