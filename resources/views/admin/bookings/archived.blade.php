@extends('layouts.app')

@section('content')
<div class="container mx-auto">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-xl font-bold">Archived Bookings</h1>
        <a href="{{ route('admin.bookings.index') }}" class="text-blue-600 underline">← Back to Active Bookings</a>
    </div>

    <table class="min-w-full bg-white border">
        <thead>
            <tr class="bg-gray-100 text-left">
                <th class="py-2 px-4 border-b">Client</th>
                <th class="py-2 px-4 border-b">Make</th>
                <th class="py-2 px-4 border-b">Model</th>
                <th class="py-2 px-4 border-b">Date</th>
                <th class="py-2 px-4 border-b">Slot</th>
                <th class="py-2 px-4 border-b">Priority</th>
                <th class="py-2 px-4 border-b">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bookings as $booking)
                <tr>
                    <td class="py-2 px-4 border-b">{{ $booking->client->name ?? '—' }}</td>
                    <td class="py-2 px-4 border-b">{{ $booking->vehicleData->make->name ?? '—' }}</td>
                    <td class="py-2 px-4 border-b">{{ $booking->vehicleData->model->name ?? '—' }}</td>
                    <td class="py-2 px-4 border-b">{{ $booking->date }}</td>
                    <td class="py-2 px-4 border-b">{{ $booking->slot }}</td>
                    <td class="py-2 px-4 border-b">{{ ucfirst($booking->priority) }}</td>
                    <td class="py-2 px-4 border-b text-red-600 font-semibold">Archived</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="py-4 px-4 text-center">No archived bookings found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
