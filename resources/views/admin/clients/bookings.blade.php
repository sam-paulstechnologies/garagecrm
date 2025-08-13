@extends('layouts.app')

@section('content')
    <div class="p-6">
        <h2 class="text-2xl font-bold mb-4">Client Bookings – {{ $client->name }}</h2>
        <p>This is the bookings page for client ID: {{ $client->id }}</p>
    </div>
@endsection
