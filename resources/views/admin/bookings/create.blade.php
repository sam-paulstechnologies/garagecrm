@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto mt-8 bg-white p-6 rounded shadow">
    <h2 class="text-xl font-semibold mb-6">Create Booking</h2>

    <form method="POST" action="{{ route('admin.bookings.store') }}">
        @include('admin.bookings.partials.form', [
            'booking' => new \App\Models\Tenant\Booking,
            'clients' => $clients,
            'opportunities' => $opportunities,
            'users' => $users,
            'vehicleMakes' => $vehicleMakes,
            'vehicleModels' => $vehicleModels,
            'isEdit' => false
        ])
    </form>
</div>
@endsection
