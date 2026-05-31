@include('admin.opportunities.form', [
    'action' => route('admin.opportunities.update', $opportunity->id),
    'isEdit' => true,
    'opportunity' => $opportunity
])
