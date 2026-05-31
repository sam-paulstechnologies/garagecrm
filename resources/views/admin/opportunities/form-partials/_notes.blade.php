<div class="sf-card">
    <div class="sf-card-header">
        <h2 class="sf-section-title">Notes</h2>
    </div>

    <div class="sf-card-body">
        <textarea name="notes" rows="5" class="sf-textarea" placeholder="Add internal notes, quotation context, customer preference, or follow-up details...">{{ $oldOr('notes') }}</textarea>
        @error('notes')<div class="sf-error">{{ $message }}</div>@enderror
    </div>
</div>
