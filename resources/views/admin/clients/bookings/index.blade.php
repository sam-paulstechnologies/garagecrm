@extends('layouts.app') {{-- or your correct layout file --}}

@section('title', 'Client Bookings')

@section('content')
    <h2 class="text-xl font-semibold mb-4">Bookings for {{ $client->name }}</h2>

    @if ($bookings->isEmpty())
        <p class="text-gray-600">No bookings found.</p>
    @else
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr>
                    <th class="px-4 py-2 border">Date</th>
                    <th class="px-4 py-2 border">Service</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bookings as $booking)
                    <tr>
                        <td class="px-4 py-2 border">{{ $booking->date }}</td>
                        <td class="px-4 py-2 border">{{ $booking->service }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
