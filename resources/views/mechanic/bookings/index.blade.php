@extends('layouts.app')
@section('content')
<h2 class="text-xl font-bold mb-4">Bookings</h2>
<table class="min-w-full bg-white">
    <thead><tr><th>ID</th><th>Date</th><th>Client</th></tr></thead>
    <tbody>
        @foreach($bookings as $booking)
        <tr>
            <td>{{ $booking->id }}</td>
            <td>{{ $booking->date }}</td>
            <td>{{ $booking->client->name }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection