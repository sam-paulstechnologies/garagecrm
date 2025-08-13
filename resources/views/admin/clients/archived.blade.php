@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Archived Clients</h1>
        <a href="{{ route('admin.clients.index') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Back to Clients
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($clients as $client)
        <div class="bg-gray-100 shadow rounded-lg p-4">
            <h2 class="text-xl font-semibold mb-1">{{ $client->name }}</h2>
            <p class="text-sm text-gray-600">{{ $client->email }}</p>
            <p class="text-sm text-gray-600">{{ $client->phone }}</p>

            <form action="{{ route('admin.clients.restore', $client->id) }}" method="POST" class="mt-4 inline-block">
                @csrf
                <button type="submit" class="text-green-600 hover:underline text-sm">Restore</button>
            </form>
        </div>
        @empty
        <p class="text-gray-500">No archived clients found.</p>
        @endforelse
    </div>
</div>
@endsection
