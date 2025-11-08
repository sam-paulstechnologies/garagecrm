@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6 space-y-6">
  <h1 class="text-2xl font-semibold">AI Control Center</h1>

  @if(session('success'))
    <div class="p-3 rounded bg-green-50 text-green-700 border border-green-100">{{ session('success') }}</div>
  @endif

  <form method="POST" action="{{ route('admin.ai.update') }}" class="space-y-6">
    @csrf

    <div class="bg-white rounded shadow p-5 space-y-4">
      <label class="flex items-center gap-2">
        <input type="checkbox" name="enabled" value="1" {{ ($initial['enabled']??false) ? 'checked' : '' }}>
        <span>Enable AI</span>
      </label>

      <div>
        <label class="block text-sm mb-1">Confidence Threshold (0–1)</label>
        <input type="number" step="0.01" min="0" max="1" name="confidence_threshold"
               class="w-full rounded border px-3 py-2"
               value="{{ $initial['confidence_threshold'] ?? 0.6 }}">
      </div>

      <label class="flex items-center gap-2">
        <input type="checkbox" name="first_reply" value="1" {{ ($initial['first_reply']??false) ? 'checked' : '' }}>
        <span>AI first reply</span>
      </label>
    </div>

    <div class="bg-white rounded shadow p-5 space-y-3">
      <div>
        <label class="block text-sm mb-1">Handle (CSV)</label>
        <input type="text" name="intent_handle" class="w-full rounded border px-3 py-2"
               value="{{ $initial['intent_handle'] ?? '' }}">
      </div>
      <div>
        <label class="block text-sm mb-1">Handoff (CSV)</label>
        <input type="text" name="intent_handoff" class="w-full rounded border px-3 py-2"
               value="{{ $initial['intent_handoff'] ?? '' }}">
      </div>
      <div>
        <label class="block text-sm mb-1">Forbidden (CSV)</label>
        <input type="text" name="intent_forbidden" class="w-full rounded border px-3 py-2"
               value="{{ $initial['intent_forbidden'] ?? '' }}">
      </div>
      <div>
        <label class="block text-sm mb-1">Policy Text</label>
        <textarea name="policy_text" class="w-full rounded border px-3 py-2 min-h-[100px]">{{ $initial['policy_text'] ?? '' }}</textarea>
      </div>
    </div>

    <button class="bg-black text-white rounded px-4 py-2">Save</button>
  </form>
</div>
@endsection
