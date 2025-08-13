@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6 bg-white shadow rounded-lg">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold">Archived Opportunities</h2>
        <a href="{{ route('admin.opportunities.index') }}" class="text-sm text-blue-600 hover:underline">← Back to Opportunities</a>
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
                    <th class="p-2 text-left border">Stage</th>
                    <th class="p-2 text-left border">Priority</th>
                    <th class="p-2 text-left border">Value</th>
                    <th class="p-2 text-left border">Deleted At</th>
                    <th class="p-2 text-left border">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($opportunities as $opportunity)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="p-2 border">{{ $opportunity->title }}</td>
                        <td class="p-2 border">{{ $opportunity->client->name ?? 'N/A' }}</td>
                        <td class="p-2 border capitalize">{{ str_replace('_', ' ', $opportunity->stage) }}</td>
                        <td class="p-2 border capitalize">{{ $opportunity->priority }}</td>
                        <td class="p-2 border">AED {{ number_format($opportunity->value, 2) }}</td>
                        <td class="p-2 border">{{ $opportunity->deleted_at?->format('d M Y, h:i A') }}</td>
                        <td class="p-2 border">
                            <form action="{{ route('admin.opportunities.restore', $opportunity->id) }}" method="POST" onsubmit="return confirm('Restore this opportunity?');">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="text-green-600 hover:underline">Restore</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-4 text-center text-gray-500">No archived opportunities found.</td>
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
