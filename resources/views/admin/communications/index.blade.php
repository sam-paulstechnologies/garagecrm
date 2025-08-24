@extends('layouts.app')

@section('title', 'All Communications')

@section('content')
<h1 class="text-2xl font-bold mb-6">All Communications</h1>

<form method="GET" action="{{ route('admin.communications.index') }}" class="mb-4 flex flex-wrap gap-4">
    <select name="client_id" class="form-input">
        <option value="">All Clients</option>
        @foreach($clients as $client)
            <option value="{{ $client->id }}" @selected(request('client_id') == $client->id)>
                {{ $client->name }}
            </option>
        @endforeach
    </select>

    <select name="type" class="form-input">
        <option value="">All Types</option>
        @foreach($types as $type)
            <option value="{{ $type }}" @selected(request('type') == $type)>
                {{ $type }}
            </option>
        @endforeach
    </select>

    <button type="submit" class="btn btn-primary">Filter</button>
    <a href="{{ route('admin.communications.index') }}" class="btn btn-secondary">Reset</a>
</form>

@if($communications->isEmpty())
    <p class="text-gray-600">No communications found.</p>
@else
    <div class="bg-white rounded shadow overflow-x-auto">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">Date</th>
                    <th class="px-4 py-2">Client</th>
                    <th class="px-4 py-2">Type</th>
                    <th class="px-4 py-2">Content</th>
                    <th class="px-4 py-2">Follow-up</th>
                </tr>
            </thead>
            <tbody>
                @foreach($communications as $comm)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ optional($comm->communication_date)->format('d M Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $comm->client->name ?? 'N/A' }}</td>
                        <td class="px-4 py-2">{{ $comm->type }}</td>
                        <td class="px-4 py-2">{{ \Illuminate\Support\Str::limit($comm->content, 40) }}</td>
                        <td class="px-4 py-2">
                            @if($comm->follow_up_required)
                                <span class="text-red-600 font-semibold">Yes</span>
                            @else
                                <span class="text-gray-500">No</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $communications->withQueryString()->links() }}
    </div>
@endif
@endsection
