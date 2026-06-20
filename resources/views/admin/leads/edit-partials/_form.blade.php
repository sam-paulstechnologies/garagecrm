{{-- resources/views/admin/leads/edit-partials/_form.blade.php --}}

<form action="{{ route('admin.leads.update', $lead) }}" method="POST" class="sf-leads-edit-panel sf-crm-edit-card rounded-2xl border shadow-sm">
    @csrf
    @method('PUT')

    <div class="sf-crm-card-header border-b border-slate-800 px-5 py-4">
        <h2 class="sf-leads-edit-title text-base font-extrabold tracking-tight">Lead Information</h2>
    </div>

    <div class="p-4 sm:p-5">
        @include('admin.leads.partials.form', ['lead' => $lead])
    </div>

    <div class="sf-crm-action-bar border-t border-slate-800 px-5 py-4">
        <div class="flex flex-wrap items-center gap-2">
            <button type="submit" class="sf-btn-primary">
                Update Lead
            </button>

            <a href="{{ route('admin.leads.show', $lead) }}" class="sf-btn-secondary">
                Cancel
            </a>
        </div>
    </div>
</form>
