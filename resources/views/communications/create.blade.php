@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6">
  <h1 class="text-2xl font-semibold mb-4">New Communication</h1>

  <form method="post" action="{{ route('communications.store') }}" class="bg-white p-6 rounded shadow space-y-4">
    @csrf

    <div>
      <label class="block text-sm font-medium mb-1">Client</label>
      <select name="client_id" class="border rounded p-2 w-full" required>
        <option value="">Select…</option>
        @foreach($clients as $c)
          <option value="{{ $c->id }}" @selected(($prefill['client_id'] ?? null) == $c->id)>{{ $c->name }}</option>
        @endforeach
      </select>
      @error('client_id') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
    </div>

    <input type="hidden" name="company_id" value="{{ company_id() }}"/>

    <div>
      <label class="block text-sm font-medium mb-1">Type</label>
      <select name="type" class="border rounded p-2 w-full" required>
        @foreach(['call','email','whatsapp'] as $t)
          <option value="{{ $t }}" @selected(($prefill['type'] ?? null) === $t)>{{ ucfirst($t) }}</option>
        @endforeach
      </select>
      @error('type') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Date & Time</label>
      <input type="datetime-local" name="communication_date"
             value="{{ old('communication_date') }}"
             class="border rounded p-2 w-full" />
      @error('communication_date') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Content / Notes</label>
      <textarea name="content" rows="6" class="border rounded p-2 w-full">{{ old('content') }}</textarea>
      @error('content') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
    </div>

    <label class="inline-flex items-center gap-2">
      <input type="checkbox" name="follow_up_required" value="1" class="rounded"
             @checked(old('follow_up_required')) />
      <span>Follow-up required</span>
    </label>

    <div class="pt-2">
      <button class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
      <a href="{{ route('communications.index') }}" class="px-4 py-2 bg-gray-100 rounded">Cancel</a>
    </div>
  </form>
</div>
@endsection
