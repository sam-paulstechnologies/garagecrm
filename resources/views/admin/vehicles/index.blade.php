@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6 bg-white rounded-lg shadow">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Vehicles</h1>
        <a href="{{ route('admin.vehicles.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
            + Add Vehicle
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 text-green-700 bg-green-100 p-3 rounded">{{ session('success') }}</div>
    @endif

    <table class="table-auto w-full border">
        <thead>
            <tr class="bg-gray-100">
                <th class="p-2 text-left">Client</th>
                <th class="p-2 text-left">Make</th>
                <th class="p-2 text-left">Model</th>
                <th class="p-2 text-left">Trim</th>
                <th class="p-2 text-left">Plate</th>
                <th class="p-2 text-left">Year</th>
                <th class="p-2 text-left">Insurance Expiry</th>
                <th class="p-2 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vehicles as $vehicle)
            <tr class="border-t">
                <td class="p-2">{{ $vehicle->client->name }}</td>
                <td class="p-2">{{ $vehicle->make }}</td>
                <td class="p-2">{{ $vehicle->model }}</td>
                <td class="p-2">{{ $vehicle->trim }}</td>
                <td class="p-2">{{ $vehicle->plate_number }}</td>
                <td class="p-2">{{ $vehicle->year }}</td>
                <td class="p-2">{{ $vehicle->insurance_expiry_date }}</td>
                <td class="p-2 space-x-2">
                    <a href="{{ route('admin.vehicles.edit', $vehicle->id) }}" class="text-indigo-600">Edit</a>
                    <form method="POST" action="{{ route('admin.vehicles.destroy', $vehicle->id) }}" class="inline">
                        @csrf @method('DELETE')
                        <button onclick="return confirm('Delete this vehicle?')" class="text-red-600">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
