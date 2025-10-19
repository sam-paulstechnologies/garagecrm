@extends('layouts.app')
@section('title','Triggers')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-10">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Triggers</h1>
        <a href="{{ route('admin.marketing.triggers.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded px-4 py-2">New Trigger</a>
    </div>

    @if(session('ok')) <div class="mb-4 p-3 rounded bg-green-50 text-green-700">{{ session('ok') }}</div> @endif

    <div class="bg-white border rounded">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left p-3">Name</th>
                    <th class="text-left p-3">Event</th>
                    <th class="text-left p-3">Campaign</th>
                    <th class="text-left p-3">Status</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $t)
                    <tr class="border-t">
                        <td class="p-3">{{ $t->name }}</td>
                        <td class="p-3">{{ $t->event }}</td>
                        <td class="p-3">{{ $t->campaign->name ?? '—' }}</td>
                        <td class="p-3">{{ ucfirst($t->status) }}</td>
                        <td class="p-3 text-right">
                            <a class="text-indigo-600" href="{{ route('admin.marketing.triggers.edit',$t) }}">Edit</a>
                            <form action="{{ route('admin.marketing.triggers.destroy',$t) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button onclick="return confirm('Delete this trigger?')" class="text-red-600 ml-3">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-5 text-center text-gray-500">No triggers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $items->links() }}</div>
</div>
@endsection
