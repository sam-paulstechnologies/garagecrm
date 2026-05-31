<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            Pickup Details
        </h2>

        <p class="sf-section-subtitle">
            Enable pickup only if customer needs vehicle collection.
        </p>
    </div>

    <div class="sf-card-body space-y-5">
        <div class="rounded-2xl border border-blue-400/20 bg-blue-500/10 p-4">
            <label class="flex items-start gap-3">
                <input type="checkbox"
                       name="pickup_required"
                       id="pickup_required"
                       value="1"
                       @checked($pickupRequiredVal === '1' || $pickupRequiredVal === 1)
                       class="mt-1 rounded border-white/10 bg-slate-950 text-blue-500 shadow-sm focus:ring-blue-400">

                <span>
                    <span class="block text-sm font-extrabold text-blue-300">
                        Pickup Required
                    </span>

                    <span class="mt-1 block text-xs font-medium leading-5 text-blue-100/80">
                        Capture pickup address and contact number below.
                    </span>
                </span>
            </label>
        </div>

        <div id="pickup_fields" class="grid grid-cols-1 gap-5 md:grid-cols-2 hidden">
            <div>
                <label class="sf-label">
                    Pickup Address
                </label>

                <textarea name="pickup_address"
                          rows="3"
                          class="sf-textarea"
                          placeholder="Pickup address">{{ $oldOr('pickup_address') }}</textarea>

                @error('pickup_address')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="sf-label">
                    Pickup Contact Number
                </label>

                <input type="text"
                       name="pickup_contact_number"
                       value="{{ $oldOr('pickup_contact_number') }}"
                       class="sf-input"
                       placeholder="9715XXXXXXXX">

                @error('pickup_contact_number')
                    <div class="sf-error">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>
