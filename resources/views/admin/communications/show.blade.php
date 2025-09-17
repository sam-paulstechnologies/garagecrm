@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6 space-y-4">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-semibold">Communication #{{ $communication->id }}</h1>
    <div class="space-x-2">
      <a href="{{ route('admin.communications.edit', $communication) }}" class="px-3 py-1 bg-gray-800 text-white rounded">Edit</a>
      <form action="{{ route('admin.communications.destroy', $communication) }}" method="post" class="inline"
            onsubmit="return confirm('Delete this communication?');">
        @csrf @method('DELETE')
        <button class="px-3 py-1 bg-red-600 text-white rounded">Delete</button>
      </form>
    </div>
  </div>

  <div class="bg-white rounded shadow p-4 space-y-2">
    <div><span class="font-medium">Client:</span>
      @if($communication->client)
        <a class="text-blue-600 hover:underline" href="{{ route('admin.clients.show', $communication->client) }}">
          {{ $communication->client->name }}
        </a>
      @else — @endif
    </div>

    <div><span class="font-medium">Type:</span> {{ ucfirst($communication->type) }}</div>
    <div><span class="font-medium">Date:</span> {{ optional($communication->communication_date)->format('Y-m-d H:i') ?? '—' }}</div>
    <div>
      <span class="font-medium">Follow-up:</span>
      @if($communication->follow_up_required)
        <span class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-800">Required</span>
      @else
        <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">No</span>
      @endif
    </div>

    <div class="pt-2">
      <div class="font-medium mb-1">Content</div>
      <div class="whitespace-pre-wrap">{{ $communication->content ?? '—' }}</div>
    </div>
  </div>

  <a href="{{ route('admin.communications.index') }}" class="text-blue-600 hover:underline">← All Communications</a>
</div>
@endsection
