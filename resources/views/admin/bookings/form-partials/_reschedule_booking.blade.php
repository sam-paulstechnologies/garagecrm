<div id="reschedule_reason_wrap" class="sf-card hidden border-red-400/20">
    <div class="sf-card-header">
        <div>
            <h2 class="sf-section-title">Rescheduling Required</h2>
            <p class="sf-section-subtitle">
                Capture why this booking cannot stay in the current confirmation slot.
            </p>
        </div>
    </div>

    <div class="sf-card-body">
        <label for="reschedule_reason" class="sf-label">
            Reschedule Reason <span class="text-red-500">*</span>
        </label>

        <textarea
            id="reschedule_reason"
            name="reschedule_reason"
            rows="3"
            class="sf-textarea"
            placeholder="Example: Customer requested a later date, slot unavailable, or confirmation details changed."
        >{{ $rescheduleReasonVal }}</textarea>

        @error('reschedule_reason')
            <p class="sf-error">{{ $message }}</p>
        @enderror
    </div>
</div>
