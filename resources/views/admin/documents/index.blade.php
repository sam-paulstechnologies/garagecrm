@extends('layouts.admin')

@section('content')
<div class="container mx-auto py-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold">Documents Inbox</h1>
        <form method="GET" class="flex gap-2">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search..."
                   class="border rounded px-3 py-2">
            <select name="status" class="border rounded px-3 py-2">
                <option value="">Any status</option>
                @foreach(['needs_review','assigned','matched'] as $st)
                    <option value="{{ $st }}" @selected(($filters['status'] ?? '')===$st)>{{ $st }}</option>
                @endforeach
            </select>
            <select name="source" class="border rounded px-3 py-2">
                <option value="">Any source</option>
                @foreach(['whatsapp','email','upload'] as $src)
                    <option value="{{ $src }}" @selected(($filters['source'] ?? '')===$src)>{{ $src }}</option>
                @endforeach
            </select>
            <select name="type" class="border rounded px-3 py-2">
                <option value="">Any type</option>
                @foreach(['invoice','job_card','other'] as $tp)
                    <option value="{{ $tp }}" @selected(($filters['type'] ?? '')===$tp)>{{ $tp }}</option>
                @endforeach
            </select>
            <button class="bg-gray-900 text-white px-4 py-2 rounded">Filter</button>
            <a href="{{ route('admin.documents.index') }}" class="px-3 py-2 text-gray-600 underline">Reset</a>
        </form>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-2 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white shadow rounded overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left">Received</th>
                    <th class="px-4 py-3 text-left">Source</th>
                    <th class="px-4 py-3 text-left">Type</th>
                    <th class="px-4 py-3 text-left">Sender</th>
                    <th class="px-4 py-3 text-left">File</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($docs as $doc)
                <tr class="border-t">
                    <td class="px-4 py-3">
                        {{ $doc->received_at?->format('Y-m-d H:i') ?? $doc->created_at->format('Y-m-d H:i') }}
                    </td>
                    <td class="px-4 py-3">{{ $doc->source }}</td>
                    <td class="px-4 py-3">{{ $doc->type }}</td>
                    <td class="px-4 py-3">
                        @if($doc->sender_email)
                            {{ $doc->sender_email }}
                        @elseif($doc->sender_phone)
                            {{ $doc->sender_phone }}
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="truncate max-w-xs" title="{{ $doc->original_name }}">
                            {{ $doc->original_name }}
                        </div>
                        @if($doc->public_url)
                            <a href="{{ $doc->public_url }}" class="text-blue-600 underline" target="_blank">Open</a>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        {{ $doc->status }}
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.documents.show', $doc) }}" class="text-indigo-600 underline">View</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">No documents found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $docs->links() }}
    </div>
</div>
@endsection
