<div class="flex flex-wrap justify-end gap-2">
    <a href="{{ route('admin.bookings.index') }}" class="sf-btn-secondary">
        Cancel
    </a>

    <button type="submit" class="sf-btn-primary">
        {{ $isEdit ? 'Update Booking' : 'Create Booking' }}
    </button>
</div>
