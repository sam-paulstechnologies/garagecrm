@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="text-2xl font-bold mb-4">Edit Client</h1>

        <form action="{{ route('admin.clients.update', $client->id) }}" method="POST" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
    @csrf
    @method('PUT')

    {{-- Name --}}
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="name">Name</label>
        <input name="name" id="name" type="text" value="{{ old('name', $client->name) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
    </div>

    {{-- Email --}}
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
        <input name="email" id="email" type="email" value="{{ old('email', $client->email) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
    </div>

    {{-- Phone --}}
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">Phone</label>
        <input name="phone" id="phone" type="text" value="{{ old('phone', $client->phone) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
    </div>

    {{-- Location --}}
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="location">Location</label>
        <input name="location" id="location" type="text" value="{{ old('location', $client->location) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
    </div>

    {{-- Source --}}
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="source">Source</label>
        <input name="source" id="source" type="text" value="{{ old('source', $client->source) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
    </div>

    {{-- Last Service --}}
    <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="last_service">Last Service</label>
        <input name="last_service" id="last_service" type="date" value="{{ old('last_service', $client->last_service) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
    </div>

    {{-- Actions --}}
    <div class="mt-6 flex gap-4">
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-6 rounded shadow">
            Update Client
        </button>
        <a href="{{ route('admin.clients.index') }}" class="text-indigo-600 hover:underline self-center">Cancel</a>
    </div>
</form>

    </div>
@endsection
