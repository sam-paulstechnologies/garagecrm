@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6 bg-white rounded shadow">
    <div class="flex justify-between mb-4">
        <h1 class="text-2xl font-bold">Vehicles</h1>
        <a href="{{ route('admin.vehicles.create') }}" class="btn btn-primary">+ Add Vehicle</a>
    </div>

    @if($vehicles->isEmpty())
        <p class="text-gray-500">No vehicles found.</p>
    @else
    <table class="table table-bordered w-full">
        <thead>
            <tr>
                <th>Client</th>
                <th>Make</th>
                <th>Model</th>
                <th>Plate</th>
                <th>Insurance Expiry</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @foreach($vehicles as $vehicle)
            <tr>
                <td>{{ $vehicle->client->name }}</td>
                <td>{{ optional($vehicle->make)->name ?? '—' }}</td>
                <td>{{ optional($vehicle->model)->name ?? '—' }}</td>
                <td>{{ $vehicle->plate_number ?? '—' }}</td>
                <td>{{ optional($vehicle->insurance_expiry_date)->format('d M Y') ?? '—' }}</td>
                <td class="flex gap-2">
                    <a href="{{ route('admin.vehicles.edit', $vehicle) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form method="POST" action="{{ route('admin.vehicles.destroy', $vehicle) }}">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger"
                            onclick="return confirm('Delete this vehicle?')">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="mt-4">{{ $vehicles->links() }}</div>
    @endif
</div>
@endsection
