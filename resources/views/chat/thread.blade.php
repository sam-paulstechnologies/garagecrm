@extends('layouts.app')

@section('content')
<div class="container mx-auto">
  <a href="{{ route('admin.chat.index') }}" class="text-sm text-blue-600">&larr; Back</a>
  <h1 class="text-2xl font-bold mb-4">Conversation #{{ $conv->id }}</h1>

  <div class="border rounded p-3 mb-3 bg-white">
    @foreach($messages as $m)
      <div class="mb-3">
        <div class="text-xs text-gray-500">
          {{ strtoupper($m->direction) }} · {{ $m->channel }} · {{ $m->created_at }}
          @if($m->source) · src: {{ $m->source }} @endif
          @if($m->ai_confidence) · conf: {{ number_format($m->ai_confidence,2) }} @endif
        </div>
        <div class="p-2 border rounded mt-1 @if($m->direction==='out') bg-gray-50 @endif">
          {{ $m->body }}
        </div>
      </div>
    @endforeach
  </div>

  <form method="POST" action="{{ url('/api/v1/chat/send/'.$conv->id) }}" onsubmit="return sendMsg(event)">
    @csrf
    <div class="flex gap-2">
      <input id="msg" name="text" class="flex-1 border rounded p-2" placeholder="Type your message..." />
      <button class="px-4 py-2 bg-black text-white rounded">Send</button>
      <button class="px-4 py-2 border rounded" onclick="return suggest(event)">AI Suggest</button>
    </div>
  </form>
</div>

<script>
async function sendMsg(e){
  e.preventDefault();
  const el = document.getElementById('msg');
  const res = await fetch(e.target.action, {
    method:'POST',
    headers:{'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept':'application/json'},
    body: new FormData(e.target)
  });
  if(res.ok) location.reload();
  return false;
}
async function suggest(e){
  e.preventDefault();
  await fetch('{{ url('/api/v1/chat/suggest/'.$conv->id) }}', {method:'POST', headers:{'X-CSRF-TOKEN': '{{ csrf_token() }}'}});
  alert('AI suggestion generation queued.');
  return false;
}
</script>
@endsection
