@extends('layouts.app')
@section('content')
<h2 class="text-xl font-bold mb-4">Booking Details</h2>
<p>Client: {{ $booking->client->name }}</p>
<p>Date: {{ $booking->date }}</p>
<p>Service: {{ $booking->service }}</p>
@endsection