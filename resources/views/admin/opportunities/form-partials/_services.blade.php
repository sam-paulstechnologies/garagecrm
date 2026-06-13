<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">Services Needed</h2>
        <p class="sf-section-subtitle">Select one or more services discussed with the customer.</p>
    </div>

    <div class="sf-card-body space-y-5">
        <input type="hidden" name="service_type" id="service_type" value="{{ old('service_type', implode(', ', $selectedServices)) }}">

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            @foreach($serviceList as $service)
                <label class="rounded-2xl border border-white/10 bg-slate-950/60 p-4 transition hover:border-orange-400/30">
                    <div class="flex items-start gap-3">
                        <input type="checkbox"
                               name="services[]"
                               value="{{ $service }}"
                               data-service-checkbox
                               @checked(in_array($service, $selectedServices, true))
                               class="mt-1 rounded border-white/10 bg-slate-950 text-orange-500 shadow-sm focus:ring-orange-400">

                        <span class="text-sm font-bold text-slate-200">{{ $service }}</span>
                    </div>
                </label>
            @endforeach
        </div>

        <div>
            <label class="sf-label">Custom Service</label>
            <input type="text" name="custom_service_type" id="custom_service_type" value="{{ $customService }}" class="sf-input" placeholder="Enter custom service if not listed">
            @error('custom_service_type')<div class="sf-error">{{ $message }}</div>@enderror
        </div>

        @error('service_type')<div class="sf-error">{{ $message }}</div>@enderror
    </div>
</div>
