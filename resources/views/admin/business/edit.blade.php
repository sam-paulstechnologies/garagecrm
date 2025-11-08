@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6 space-y-6">
  <h1 class="text-2xl font-semibold">Business Profile & Escalation</h1>

  @if(session('success'))
    <div class="p-3 rounded bg-green-50 text-green-700 border border-green-100">{{ session('success') }}</div>
  @endif

  <form method="POST" action="{{ route('admin.business.update') }}" class="space-y-6">
    @csrf

    <div class="bg-white rounded shadow p-5 space-y-3">
      <div>
        <label class="block text-sm mb-1">Manager Phone (E.164)</label>
        <input type="text" name="manager_phone" class="w-full rounded border px-3 py-2"
               value="{{ $initial['manager_phone'] ?? '' }}">
      </div>
      <div>
        <label class="block text-sm mb-1">Location</label>
        <input type="text" name="location" class="w-full rounded border px-3 py-2"
               value="{{ $initial['location'] ?? '' }}">
      </div>
      <div>
        <label class="block text-sm mb-1">Work Hours</label>
        <input type="text" name="work_hours" class="w-full rounded border px-3 py-2"
               value="{{ $initial['work_hours'] ?? '' }}">
      </div>
      <div>
        <label class="block text-sm mb-1">Holidays (comma separated)</label>
        <textarea name="holidays" class="w-full rounded border px-3 py-2 min-h-[80px]">{{ $initial['holidays'] ?? '' }}</textarea>
      </div>
    </div>

    <div class="bg-white rounded shadow p-5 space-y-3">
      <label class="flex items-center gap-2">
        <input type="checkbox" name="esc_low_confidence" value="1" {{ ($initial['esc_low_confidence']??false) ? 'checked' : '' }}>
        <span>Handoff on low confidence</span>
      </label>
      <label class="flex items-center gap-2">
        <input type="checkbox" name="esc_negative_sentiment" value="1" {{ ($initial['esc_negative_sentiment']??false) ? 'checked' : '' }}>
        <span>Handoff on negative sentiment</span>
      </label>
      <label class="flex items-center gap-2">
        <input type="checkbox" name="esc_timeout_enabled" value="1" {{ ($initial['esc_timeout_enabled']??false) ? 'checked' : '' }}>
        <span>Handoff on no-reply timeout</span>
      </label>
      <div>
        <label class="block text-sm mb-1">Timeout minutes</label>
        <input type="number" min="10" max="1440" name="esc_timeout_minutes" class="w-full rounded border px-3 py-2"
               value="{{ $initial['esc_timeout_minutes'] ?? 120 }}">
      </div>
    </div>

    <button class="bg-black text-white rounded px-4 py-2">Save</button>
  </form>
</div>
@endsection
