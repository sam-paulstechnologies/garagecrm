@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6 bg-white rounded-lg shadow mt-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Edit Booking #{{ $booking->id }}</h1>
    @include('admin.bookings.partials.form', [
        'action'        => route('admin.bookings.update', $booking),
        'isEdit'        => true,
        'booking'       => $booking,
        'clients'       => $clients,
        'opportunities' => $opportunities,
        'vehicles'      => $vehicles,
        'users'         => $users,
        'vehicleMakes'  => $vehicleMakes,
        'vehicleModels' => $vehicleModels,
    ])
</div>
@endsection
