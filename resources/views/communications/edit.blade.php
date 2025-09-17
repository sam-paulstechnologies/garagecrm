@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6">
  <h1 class="text-2xl font-semibold mb-4">Edit Communication</h1>

  <form method="post" action="{{ route('communications.update', $communication) }}" class="bg-white p-6 rounded shadow space-y-4">
    @csrf @method('PUT')

    <div>
      <label class="block text-sm font-medium mb-1">Client</label>
      <select name="client_id" class="border rounded p-2 w-full" disabled>
        <option value="{{ $communication->client_id }}">{{ optional($communication->client)->name ?? '—' }}</option>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Type</label>
      <select name="type" class="border rounded p-2 w-full" required>
        @foreach(['call','email','whatsapp'] as $t)
          <option value="{{ $t }}" @selected($communication->type === $t)>{{ ucfirst($t) }}</option>
        @endforeach
      </select>
      @error('type') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Date & Time</label>
      <input type="datetime-local" name="communication_date"
             value="{{ optional($communication->communication_date)->format('Y-m-d\TH:i') }}"
             class="border rounded p-2 w-full" />
      @error('communication_date') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Content / Notes</label>
      <textarea name="content" rows="6" class="border rounded p-2 w-full">{{ old('content', $communication->content) }}</textarea>
      @error('content') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
    </div>

    <label class="inline-flex items-center gap-2">
      <input type="checkbox" name="follow_up_required" value="1" class="rounded"
             @checked($communication->follow_up_required) />
      <span>Follow-up required</span>
    </label>

    <div class="pt-2">
      <button class="px-4 py-2 bg-blue-600 text-white rounded">Update</button>
      <a href="{{ route('communications.show', $communication) }}" class="px-4 py-2 bg-gray-100 rounded">Cancel</a>
    </div>
  </form>
</div>
@endsection
