@extends('layouts.app')

@section('content')
<div class="container mx-auto">
  <h1 class="text-2xl font-bold mb-4">Unified Inbox</h1>
  <div class="grid gap-2">
    @foreach($threads as $t)
      <a href="{{ route('admin.chat.show', $t->id) }}" class="p-3 border rounded hover:bg-gray-50 flex justify-between">
        <div>
          <div class="font-semibold">#{{ $t->id }} — {{ $t->subject ?? 'Conversation' }}</div>
          <div class="text-xs text-gray-500">Latest: {{ optional($t->latest_message_at)->diffForHumans() }}</div>
        </div>
        <div class="text-xs self-center">{{ $t->is_whatsapp_linked ? 'WhatsApp' : 'In-App' }}</div>
      </a>
    @endforeach
  </div>
</div>
@endsection
