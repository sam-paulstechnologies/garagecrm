@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6 bg-white rounded-lg shadow mt-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Edit Opportunity</h1>

    @include('admin.opportunities.form', [
        'action' => route('admin.opportunities.update', $opportunity->id),
        'isEdit' => true,
        'opportunity' => $opportunity
    ])
</div>
@endsection
