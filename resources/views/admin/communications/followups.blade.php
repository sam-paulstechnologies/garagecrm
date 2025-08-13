@extends('layouts.app')

@section('title', 'Follow-ups Required')

@section('content')
<h1 class="text-2xl font-bold mb-6">Follow-ups Required</h1>

@if($communications->isEmpty())
    <p class="text-gray-600">No follow-up communications found.</p>
@else
    <div class="bg-white rounded shadow overflow-x-auto">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">Date</th>
                    <th class="px-4 py-2">Client</th>
                    <th class="px-4 py-2">Type</th>
                    <th class="px-4 py-2">Content</th>
                </tr>
            </thead>
            <tbody>
                @foreach($communications as $comm)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $comm->communication_date->format('d M Y H:i') }}</td>
                        <td class="px-4 py-2">{{ $comm->client->name ?? 'N/A' }}</td>
                        <td class="px-4 py-2">{{ $comm->communication_type }}</td>
                        <td class="px-4 py-2">{{ Str::limit($comm->content, 50) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $communications->links() }}
    </div>
@endif
@endsection
