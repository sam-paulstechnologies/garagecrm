@extends('layouts.app')

@section('title', 'Archived Bookings')

@section('content')
<div class="max-w-7xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Archived Bookings</h1>
        <a href="{{ route('admin.bookings.index') }}" class="text-blue-600 hover:underline">
            ← Back to Active Bookings
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded bg-green-50 text-green-800 px-4 py-3">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded bg-red-50 text-red-800 px-4 py-3">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($bookings->isEmpty())
        <div class="rounded border bg-white p-8 text-center text-gray-600">
            No archived bookings found.
            <div class="mt-3">
                <a href="{{ route('admin.bookings.index') }}" class="text-blue-600 hover:underline">
                    Go to Active Bookings
                </a>
            </div>
        </div>
    @else
        <div class="overflow-x-auto rounded border bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-700">
                    <tr>
                        <th class="p-3 border-b">Client</th>
                        <th class="p-3 border-b">Vehicle</th>
                        <th class="p-3 border-b">Date</th>
                        <th class="p-3 border-b">Slot</th>
                        <th class="p-3 border-b">Priority</th>
                        <th class="p-3 border-b">Assigned</th>
                        <th class="p-3 border-b">Status</th>
                        <th class="p-3 border-b text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bookings as $booking)
                        @php
                            $vehicle    = optional($booking->vehicleData);
                            $makeName   = optional($vehicle->make)->name;
                            $modelName  = optional($vehicle->model)->name;
                            $dateValue  = $booking->booking_date
                                            ?? optional($booking->scheduled_at)->format('Y-m-d')
                                            ?? ($booking->date ?? null);
                            $assigned   = optional($booking->assignedUser)->name;
                        @endphp
                        <tr class="border-t">
                            <td class="p-3 align-top">{{ optional($booking->client)->name ?? '—' }}</td>
                            <td class="p-3 align-top">
                                @if($makeName || $modelName)
                                    {{ trim(($makeName ?? '').' '.($modelName ?? '')) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="p-3 align-top">{{ $dateValue ?? '—' }}</td>
                            <td class="p-3 align-top">{{ $booking->slot ?? '—' }}</td>
                            <td class="p-3 align-top">{{ $booking->priority ? ucfirst($booking->priority) : '—' }}</td>
                            <td class="p-3 align-top">{{ $assigned ?? '—' }}</td>
                            <td class="p-3 align-top text-red-600 font-semibold">Archived</td>
                            <td class="p-3 align-top text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('admin.bookings.show', $booking) }}"
                                       class="px-3 py-1 rounded border hover:bg-gray-50">View</a>

                                    <form action="{{ route('admin.bookings.restore', $booking) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit"
                                                class="px-3 py-1 rounded border text-green-700 hover:bg-green-50">
                                            Restore
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $bookings->links() }}
        </div>
    @endif
</div>
@endsection
