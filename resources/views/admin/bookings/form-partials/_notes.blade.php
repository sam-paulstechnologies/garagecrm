<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">
            Notes
        </h2>
    </div>

    <div class="sf-card-body">
        <textarea name="notes"
                  rows="4"
                  class="sf-textarea"
                  placeholder="Add booking notes, customer instructions, pickup/drop details, or internal context...">{{ $oldOr('notes') }}</textarea>

        @error('notes')
            <div class="sf-error">{{ $message }}</div>
        @enderror
    </div>
</div>
