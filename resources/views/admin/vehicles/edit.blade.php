@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6 bg-white rounded-lg shadow mt-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Edit Vehicle</h1>

    @include('admin.vehicles.form', [
        'action' => route('admin.vehicles.update', $vehicle->id),
        'method' => 'PUT',
        'vehicle' => $vehicle,
        'clients' => $clients,
    ])
</div>
@endsection
