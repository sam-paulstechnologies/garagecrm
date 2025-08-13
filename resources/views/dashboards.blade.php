@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Dashboard</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Card -->
        <div class="bg-white border rounded-xl shadow-sm p-6">
            <h2 class="text-sm text-gray-500 mb-2">Bookings Today</h2>
            <p class="text-4xl font-bold text-indigo-600">{{ $bookingsToday }}</p>
        </div>

        <div class="bg-white border rounded-xl shadow-sm p-6">
            <h2 class="text-sm text-gray-500 mb-2">Leads Pending</h2>
            <p class="text-4xl font-bold text-blue-600">{{ $leadsPending }}</p>
        </div>

        <div class="bg-white border rounded-xl shadow-sm p-6">
            <h2 class="text-sm text-gray-500 mb-2">Jobs In Progress</h2>
            <p class="text-4xl font-bold text-yellow-500">{{ $jobsInProgress }}</p>
        </div>

        <div class="bg-white border rounded-xl shadow-sm p-6">
            <h2 class="text-sm text-gray-500 mb-2">Invoices Due</h2>
            <p class="text-4xl font-bold text-red-500">{{ $invoicesDue }}</p>
        </div>
    </div>

    <div class="bg-white border rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Top 5 Clients</h2>
        @if(count($topClients))
            <ul class="space-y-2">
                @foreach($topClients as $client)
                    <li class="flex justify-between text-gray-700">
                        <span>{{ $client->name }}</span>
                        <span class="font-semibold">{{ $client->totalBookings }} bookings</span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-gray-500">No client data available.</p>
        @endif
    </div>
</div>
@endsection
