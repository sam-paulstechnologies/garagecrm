@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6">
  <h1 class="text-xl font-semibold mb-4">Edit WhatsApp Campaign</h1>

  <form method="POST" action="{{ route('admin.whatsapp.campaigns.update', 0) }}" class="space-y-4">
    @csrf
    @method('PUT')

    <div class="p-3 rounded bg-yellow-50 text-yellow-800 mb-2">
      Demo view. Wire to real record when model is ready.
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">Name</label>
      <input name="name" value="{{ old('name') }}" required
             class="w-full border rounded px-3 py-2">
    </div>

    <div class="pt-2">
      <button class="px-4 py-2 bg-indigo-600 text-white rounded">Update</button>
      <a href="{{ route('admin.whatsapp.campaigns.index') }}" class="ml-2 px-3 py-2 border rounded">Cancel</a>
    </div>
  </form>
</div>
@endsection
