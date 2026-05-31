<div class="sf-card">
    <div class="sf-card-body">
        <div class="flex flex-wrap items-center justify-end gap-2">
            <a href="{{ route('admin.opportunities.index') }}" class="sf-btn-secondary">Cancel</a>
            <button type="submit" class="sf-btn-primary">{{ $isEdit ? 'Update Opportunity' : 'Create Opportunity' }}</button>
        </div>
    </div>
</div>
