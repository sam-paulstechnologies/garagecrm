<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    @include('admin.opportunities.partials.errors')
    @include('admin.opportunities.partials.basic')
    @include('admin.opportunities.partials.details')
    @include('admin.opportunities.partials.services')
    @include('admin.opportunities.partials.notes')

    @if (strtolower($opportunity->stage ?? '') !== 'closed_won')
        <div>
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                {{ $isEdit ? 'Update' : 'Create' }} Opportunity
            </button>
        </div>
    @endif
</form>

@if (strtolower($opportunity->stage ?? '') === 'closed_won')
    @include('admin.opportunities.partials.booking-modal')
@endif

<script>
function toggleOtherServiceInput(checkbox) {
    const input = document.getElementById('other_service_input');
    input.style.display = checkbox.checked ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', function () {
    const stageSelect = document.querySelector('select[name="stage"]');
    const form = stageSelect.closest('form');

    if (stageSelect && form) {
        stageSelect.addEventListener('change', function () {
            const value = this.value.toLowerCase().replace(/\s/g, '_');
            if (value === 'closed_won') {
                form.submit(); // auto submit on change to "Closed Won"
            }
        });
    }
});
</script>
