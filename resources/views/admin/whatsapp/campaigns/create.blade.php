@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6">
  <h1 class="text-xl font-semibold mb-4">New WhatsApp Campaign</h1>

  <form method="POST" action="{{ route('admin.whatsapp.campaigns.store') }}" class="space-y-4">
    @csrf

    @if ($errors->any())
      <div class="p-3 rounded bg-red-50 text-red-800">
        <ul class="list-disc pl-5">
          @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
        </ul>
      </div>
    @endif

    <div>
      <label class="block text-sm font-medium mb-1">Name</label>
      <input name="name" value="{{ old('name') }}" required
             class="w-full border rounded px-3 py-2">
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Template</label>
      <select name="template_id" class="w-full border rounded px-3 py-2">
        <option value="">— Select later —</option>
        {{-- TODO: loop templates --}}
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Audience (segment/filter)</label>
      <input name="audience" value="{{ old('audience') }}"
             class="w-full border rounded px-3 py-2" placeholder="e.g., All Leads: New">
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Schedule at (optional)</label>
      <input type="datetime-local" name="schedule_at" value="{{ old('schedule_at') }}"
             class="w-full border rounded px-3 py-2">
    </div>

    <div class="pt-2">
      <button class="px-4 py-2 bg-indigo-600 text-white rounded">Save</button>
      <a href="{{ route('admin.whatsapp.campaigns.index') }}" class="ml-2 px-3 py-2 border rounded">Cancel</a>
    </div>
  </form>
</div>
@endsection
