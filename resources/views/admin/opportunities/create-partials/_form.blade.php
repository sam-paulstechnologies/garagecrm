@include('admin.opportunities.form', [
    'action' => route('admin.opportunities.store'),
    'isEdit' => false,
    'opportunity' => null
])
