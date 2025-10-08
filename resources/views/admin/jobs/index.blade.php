@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6">

  {{-- Header --}}
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-5">
    <div>
      <h2 class="text-xl font-bold">Jobs</h2>
      <p class="text-sm text-gray-500">Track jobs by status, search by client, code, or description.</p>
    </div>
    <a href="{{ route('admin.jobs.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
      + Create Job
    </a>
  </div>

  {{-- Toolbar --}}
  <form method="GET" action="{{ route('admin.jobs.index') }}" class="grid md:grid-cols-3 gap-3 mb-4">
    <div class="md:col-span-2 flex">
      <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Search job code, description, client…"
             class="w-full border rounded-l px-3 py-2" />
      <button class="border border-l-0 rounded-r px-4 py-2 bg-gray-50">Search</button>
    </div>
    <div class="flex items-center gap-2">
      @php $statuses = ['' => 'All', 'pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed']; @endphp
      <select name="status" class="border rounded px-3 py-2 w-full">
        @foreach($statuses as $key => $label)
          <option value="{{ $key }}" {{ ($status ?? '')===$key ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
      </select>
      <a href="{{ route('admin.jobs.index') }}" class="text-sm text-gray-600 underline whitespace-nowrap">Reset</a>
    </div>
  </form>

  {{-- Table --}}
  <div class="overflow-x-auto bg-white rounded border">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left">Job Code</th>
          <th class="px-4 py-2 text-left">Client</th>
          <th class="px-4 py-2 text-left">Description</th>
          <th class="px-4 py-2 text-left">Status</th>
          <th class="px-4 py-2 text-left">Assigned To</th>
          <th class="px-4 py-2 text-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($jobs as $job)
          @php
            $badge = match($job->status) {
              'completed' => 'bg-green-100 text-green-800',
              'in_progress' => 'bg-blue-100 text-blue-800',
              default => 'bg-yellow-100 text-yellow-800'
            };
          @endphp
          <tr class="border-t hover:bg-gray-50">
            <td class="px-4 py-2 font-medium">{{ $job->job_code }}</td>
            <td class="px-4 py-2">{{ $job->client->name ?? 'N/A' }}</td>
            <td class="px-4 py-2 max-w-[520px]"><span class="block truncate" title="{{ $job->description }}">{{ $job->description }}</span></td>
            <td class="px-4 py-2"><span class="px-2 py-0.5 rounded text-xs {{ $badge }}">{{ str_replace('_',' ', ucfirst($job->status)) }}</span></td>
            <td class="px-4 py-2">{{ $job->assignedUser->name ?? 'Unassigned' }}</td>
            <td class="px-4 py-2">
              <div class="flex justify-end gap-3">
                <a href="{{ route('admin.jobs.show', $job->id) }}" class="text-blue-600 hover:underline">View</a>
                <a href="{{ route('admin.jobs.edit', $job->id) }}" class="text-green-600 hover:underline">Edit</a>
                <form action="{{ route('admin.jobs.destroy', $job->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Archive this job?')">
                  @csrf @method('DELETE')
                  <button type="submit" class="text-red-600 hover:underline">Archive</button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">No jobs found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $jobs->links() }}</div>
</div>
@endsection
