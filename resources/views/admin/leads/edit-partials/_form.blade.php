{{-- resources/views/admin/leads/edit-partials/_form.blade.php --}}

<form action="{{ route('admin.leads.update', $lead) }}" method="POST" class="sf-leads-edit-panel rounded-2xl border shadow-sm">
    @csrf
    @method('PUT')

    <div class="border-b border-slate-800 p-5">
        <h2 class="sf-leads-edit-title text-lg font-extrabold tracking-tight">
            Lead Information
        </h2>

        <p class="sf-leads-edit-muted mt-1 text-sm font-medium">
            Edit the lead details carefully. Changes may impact assignment, reporting, and follow-up flow.
        </p>
    </div>

    <div class="p-5">
        @include('admin.leads.partials.form', ['lead' => $lead])
    </div>

    <div class="border-t border-slate-800 p-5">
        <div class="flex flex-wrap gap-2">
            <button type="submit" class="sf-btn-primary">
                Update Lead
            </button>

            <a href="{{ route('admin.leads.show', $lead) }}" class="sf-btn-secondary">
                Cancel
            </a>
        </div>
    </div>
</form>
