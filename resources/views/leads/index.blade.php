@extends('layouts.app')

@section('title', 'Leads')

@section('content')
<div class="container mx-auto py-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Leads</h1>
        <a href="{{ route('leads.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">+ New Lead</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if($leads->isEmpty())
        <p class="text-gray-600">No leads found.</p>
    @else
        <table class="min-w-full bg-white border rounded shadow">
            <thead>
                <tr class="bg-gray-100 text-left">
                    <th class="p-3">Name</th>
                    <th class="p-3">Contact</th>
                    <th class="p-3">Vehicle</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($leads as $lead)
                    <tr class="border-t">
                        <td class="p-3">{{ $lead->name }}</td>
                        <td class="p-3">{{ $lead->contact }}</td>
                        <td class="p-3">{{ $lead->vehicle }}</td>
                        <td class="p-3">{{ $lead->status }}</td>
                        <td class="p-3 space-x-2">
                            <a href="{{ route('leads.show', $lead->id) }}" class="text-blue-600 hover:underline">View</a>
                            <a href="{{ route('leads.edit', $lead->id) }}" class="text-yellow-600 hover:underline">Edit</a>
                            <form action="{{ route('leads.destroy', $lead->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
