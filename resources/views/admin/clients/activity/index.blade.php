@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Activity — {{ $client->name }}</h1>
        <a href="{{ route('admin.clients.show', $client->id) }}" class="text-indigo-600 hover:underline">
            ← Back to Client
        </a>
    </div>

    <div class="bg-white rounded shadow divide-y">
        @forelse ($feed as $row)
            <div class="p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 mr-2">
                                {{ $row['type'] }}
                            </span>
                            {{ $row['line'] }}
                            @if(!empty($row['meta']))
                                <span class="text-gray-500">— {{ $row['meta'] }}</span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            {{ optional($row['when'])->format('M j, Y H:i') }}
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-6 text-sm text-gray-500">No activity found.</div>
        @endforelse
    </div>

    <div>{{ $feed->links() }}</div>
</div>
@endsection
