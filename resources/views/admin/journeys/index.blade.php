@extends('layouts.app')

@section('title', 'Journeys')

@section('content')
<div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Journeys</h1>
    <a href="{{ route('admin.journeys.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded">+ New Journey</a>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 p-2 rounded mb-4">{{ session('success') }}</div>
@endif

<table class="w-full bg-white shadow rounded">
    <thead>
        <tr class="bg-gray-100 text-left">
            <th class="p-2">Name</th>
            <th class="p-2">Description</th>
            <th class="p-2">Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($journeys as $journey)
        <tr>
            <td class="p-2">{{ $journey->name }}</td>
            <td class="p-2">{{ $journey->description }}</td>
            <td class="p-2">
                <form method="POST" action="{{ route('admin.journeys.destroy', $journey->id) }}">
                    @csrf @method('DELETE')
                    <button type="submit" onclick="return confirm('Delete this journey?')" class="text-red-600">Delete</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="mt-4">{{ $journeys->links() }}</div>
@endsection
