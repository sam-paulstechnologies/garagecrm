@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3>📅 Bookings</h3>

    <table class="table mt-3">
        <thead>
            <tr>
                <th>Client</th>
                <th>Date</th>
                <th>Slot</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bookings as $booking)
                <tr>
                    <td>{{ $booking->client->name }}</td>
                    <td>{{ $booking->booking_date }}</td>
                    <td>{{ $booking->slot }}</td>
                    <td>{{ $booking->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $bookings->links() }}
</div>
@endsection