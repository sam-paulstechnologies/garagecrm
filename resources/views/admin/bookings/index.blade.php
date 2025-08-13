@extends('layouts.app')

@section('content')
<div class="container mx-auto">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-xl font-bold">All Bookings</h1>
        <div class="space-x-2">
            <a href="{{ route('admin.bookings.archived') }}" class="bg-gray-500 text-white px-4 py-2 rounded">Archived</a>
            <a href="{{ route('admin.bookings.create') }}" class="bg-green-600 text-white px-4 py-2 rounded">+ New Booking</a>
        </div>
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
                <th class="py-2 px-4 border-b">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bookings as $booking)
                <tr>
                    <td class="py-2 px-4 border-b">{{ $booking->client->name ?? '—' }}</td>
                    <td class="py-2 px-4 border-b">
                        {{ $booking->vehicleMake->name ?? $booking->other_make ?? '—' }}
                    </td>
                    <td class="py-2 px-4 border-b">
                        {{ $booking->vehicleModel->name ?? $booking->other_model ?? '—' }}
                    </td>
                    <td class="py-2 px-4 border-b">
                        {{ optional($booking->date)->format('Y-m-d') ?? '—' }}
                    </td>
                    <td class="py-2 px-4 border-b">{{ ucfirst($booking->slot ?? '—') }}</td>
                    <td class="py-2 px-4 border-b">{{ ucfirst($booking->priority ?? '—') }}</td>
                    <td class="py-2 px-4 border-b">{{ ucfirst(str_replace('_', ' ', $booking->status ?? '—')) }}</td>
                    <td class="py-2 px-4 border-b space-x-2">
                        <a href="{{ route('admin.bookings.show', $booking->id) }}" class="text-blue-600 hover:underline">View</a>
                        <a href="{{ route('admin.bookings.edit', $booking->id) }}" class="text-yellow-600 hover:underline">Edit</a>
                        <form action="{{ route('admin.bookings.archive', $booking->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to archive this booking?');">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="text-red-600 hover:underline">Archive</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="py-4 px-4 text-center">No bookings found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
