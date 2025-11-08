@extends('layouts.app')

@section('title', 'Chat · Conversations')

@section('content')
<div class="container mx-auto">
    <h1 class="text-2xl font-semibold mb-4">Conversations</h1>

    @if($conversations->isEmpty())
        <div class="p-6 bg-white rounded shadow">No conversations yet.</div>
    @else
        <div class="bg-white rounded shadow overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Subject</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Latest</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">Unread</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @foreach ($conversations as $c)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $c->subject ?? 'WhatsApp Thread' }}</div>
                            <div class="text-xs text-gray-500">#{{ $c->id }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ optional($c->latest_message_at)->diffForHumans() ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if(($c->unread_count ?? 0) > 0)
                                <span class="inline-flex items-center justify-center text-xs font-semibold rounded-full h-6 w-6 bg-red-100 text-red-700">
                                    {{ $c->unread_count }}
                                </span>
                            @else
                                <span class="text-gray-400 text-sm">0</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a class="inline-flex items-center px-3 py-2 rounded border text-sm hover:bg-gray-50"
                               href="{{ route('admin.chat.show', $c->id) }}">Open</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $conversations->links() }}
        </div>
    @endif
</div>
@endsection
