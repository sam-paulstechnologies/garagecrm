@extends('layouts.app')

@section('title', 'Follow-ups Required')

@section('content')
<div class="max-w-7xl mx-auto p-6">

<h1 class="text-2xl font-bold mb-6">Follow-ups Required</h1>

@if($communications->isEmpty())
    <p class="text-gray-600">No follow-up communications found.</p>
@else
    <div class="bg-white rounded shadow overflow-x-auto">
        <table class="min-w-full table-auto text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">Date</th>
                    <th class="px-4 py-2">Client</th>
                    <th class="px-4 py-2">Type</th>
                    <th class="px-4 py-2">Content</th>
                    <th class="px-4 py-2">Action</th>
                </tr>
            </thead>
            <tbody>
            @foreach($communications as $comm)
                <tr class="border-t">
                    <td class="px-4 py-2">
                        {{ optional($comm->communication_date)->format('d M Y H:i') }}
                    </td>
                    <td class="px-4 py-2">{{ $comm->client->name ?? '—' }}</td>
                    <td class="px-4 py-2 capitalize">{{ $comm->type }}</td>
                    <td class="px-4 py-2">
                        {{ \Illuminate\Support\Str::limit($comm->content, 60) }}
                    </td>
                    <td class="px-4 py-2">
                        <form method="POST" action="{{ route('admin.communications.complete', $comm) }}">
                            @csrf
                            <button class="text-green-600 hover:underline text-sm">
                                Mark Complete
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $communications->links() }}
    </div>
@endif

</div>
@endsection
