@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6">
  <div class="flex items-center justify-between mb-4">
    <div>
      <h2 class="text-xl font-bold">Create Job</h2>
      <p class="text-sm text-gray-500">Capture only what you need now; you can update later.</p>
    </div>
    <a href="{{ route('admin.jobs.index') }}" class="text-blue-600">Back to Jobs</a>
  </div>

  <form method="POST" action="{{ route('admin.jobs.store') }}" class="bg-white rounded border p-5">
    @csrf

    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm mb-1">Client <span class="text-red-600">*</span></label>
        <select name="client_id" class="border rounded px-3 py-2 w-full" required>
          @foreach($clients as $client)
            <option value="{{ $client->id }}">{{ $client->name }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-sm mb-1">Assign To</label>
        <select name="assigned_to" class="border rounded px-3 py-2 w-full">
          <option value="">Unassigned</option>
          @foreach($users as $user)
            <option value="{{ $user->id }}">{{ $user->name }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-sm mb-1">Start Time</label>
        <input type="datetime-local" name="start_time" class="border rounded px-3 py-2 w-full">
      </div>
      <div>
        <label class="block text-sm mb-1">End Time</label>
        <input type="datetime-local" name="end_time" class="border rounded px-3 py-2 w-full">
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm mb-1">Description <span class="text-red-600">*</span></label>
        <textarea name="description" class="border rounded px-3 py-2 w-full" rows="3" required></textarea>
      </div>

      <div>
        <label class="block text-sm mb-1">Issues Found (optional)</label>
        <textarea name="issues_found" class="border rounded px-3 py-2 w-full" rows="3"></textarea>
      </div>

      <div>
        <label class="block text-sm mb-1">Parts Used (optional)</label>
        <textarea name="parts_used" class="border rounded px-3 py-2 w-full" rows="3"></textarea>
      </div>

      <div>
        <label class="block text-sm mb-1">Status</label>
        <select name="status" class="border rounded px-3 py-2 w-full">
          <option value="pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
        </select>
      </div>

      <div>
        <label class="block text-sm mb-1">Total Time (minutes)</label>
        <input type="number" min="0" name="total_time_minutes" class="border rounded px-3 py-2 w-full">
      </div>
    </div>

    <div class="mt-4 flex items-center gap-3">
      <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Create Job</button>
      <a href="{{ route('admin.jobs.index') }}" class="text-gray-600">Cancel</a>
    </div>
  </form>
</div>
@endsection
