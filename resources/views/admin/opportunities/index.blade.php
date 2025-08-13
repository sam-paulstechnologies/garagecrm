@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6 bg-white shadow rounded-lg">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">Opportunities</h2>
        <div class="flex space-x-4">
            <a href="{{ route('admin.opportunities.archived') }}" class="text-sm text-gray-600 hover:text-blue-700 underline self-center">
                View Archived Opportunities
            </a>
            <a href="{{ route('admin.opportunities.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                + Create Opportunity
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full table-auto text-sm border">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="p-2 text-left border">Title</th>
                    <th class="p-2 text-left border">Client</th>
                    <th class="p-2 text-left border">Lead</th>
                    <th class="p-2 text-left border">Stage</th>
                    <th class="p-2 text-left border">Priority</th>
                    <th class="p-2 text-left border">Value</th>
                    <th class="p-2 text-left border">Assigned To</th>
                    <th class="p-2 text-left border">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($opportunities as $opportunity)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="p-2 border">{{ $opportunity->title }}</td>
                        <td class="p-2 border">{{ $opportunity->client->name ?? 'N/A' }}</td>
                        <td class="p-2 border">{{ $opportunity->lead->name ?? 'N/A' }}</td>
                        <td class="p-2 border capitalize">{{ str_replace('_', ' ', $opportunity->stage) }}</td>
                        <td class="p-2 border capitalize">{{ $opportunity->priority }}</td>
                        <td class="p-2 border">AED {{ number_format($opportunity->value, 2) }}</td>
                        <td class="p-2 border">{{ $opportunity->assignedUser->name ?? 'N/A' }}</td>
                        <td class="p-2 border space-x-2 whitespace-nowrap">
                            <a href="{{ route('admin.opportunities.show', $opportunity->id) }}" class="text-gray-700 hover:underline">View</a>
                            <a href="{{ route('admin.opportunities.edit', $opportunity->id) }}" class="text-blue-600 hover:underline">Edit</a>
                            <form action="{{ route('admin.opportunities.destroy', $opportunity->id) }}" method="POST" class="inline-block"
                                onsubmit="return confirm('Are you sure you want to archive this opportunity?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Archive</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-4 text-center text-gray-500">No opportunities found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $opportunities->links() }}
    </div>
</div>
@endsection
