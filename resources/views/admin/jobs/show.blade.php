@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 space-y-6">

  {{-- Header --}}
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">

    <div>
      <h2 class="text-xl font-bold">
        Job #{{ $job->job_code ?? '—' }}
      </h2>

      <p class="text-sm text-gray-500">
        Created for
        <span class="font-medium">
          {{ $job->client?->name ?? 'N/A' }}
        </span>
      </p>
    </div>

    <div class="flex items-center gap-2">

      @php
      $status = $job->status ?? 'pending';

      $badge = match($status) {
          'completed' => 'bg-green-100 text-green-800',
          'in_progress' => 'bg-blue-100 text-blue-800',
          default => 'bg-yellow-100 text-yellow-800'
      };
      @endphp

      <span class="px-2 py-1 rounded text-xs {{ $badge }}">
        {{ ucwords(str_replace('_',' ', $status)) }}
      </span>

      <a href="{{ route('admin.jobs.edit', $job->id) }}"
         class="px-3 py-2 bg-gray-900 text-white rounded">
         Edit Job
      </a>

      <a href="{{ route('admin.jobs.index') }}"
         class="text-blue-600">
         Back to Jobs
      </a>

    </div>

  </div>


  <div class="grid lg:grid-cols-3 gap-6">

    {{-- LEFT --}}
    <div class="lg:col-span-2 space-y-6">


      {{-- Details --}}
      <div class="bg-white rounded border p-5">

        <h3 class="font-semibold mb-3">Details</h3>

        <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">

          <div>
            <dt class="text-gray-500">Description</dt>
            <dd class="font-medium">{{ $job->description ?? '—' }}</dd>
          </div>

          <div>
            <dt class="text-gray-500">Assigned To</dt>
            <dd class="font-medium">
              {{ $job->assignedUser?->name ?? 'Unassigned' }}
            </dd>
          </div>

          <div>
            <dt class="text-gray-500">Start</dt>
            <dd class="font-medium">
              {{ $job->start_time?->format('Y-m-d H:i') ?? '—' }}
            </dd>
          </div>

          <div>
            <dt class="text-gray-500">End</dt>
            <dd class="font-medium">
              {{ $job->end_time?->format('Y-m-d H:i') ?? '—' }}
            </dd>
          </div>

          <div>
            <dt class="text-gray-500">Issues Found</dt>
            <dd class="font-medium">{{ $job->issues_found ?: '—' }}</dd>
          </div>

          <div>
            <dt class="text-gray-500">Parts Used</dt>
            <dd class="font-medium">{{ $job->parts_used ?: '—' }}</dd>
          </div>

          <div>
            <dt class="text-gray-500">Total Time (min)</dt>
            <dd class="font-medium">{{ $job->total_time_minutes ?? '—' }}</dd>
          </div>

        </dl>

      </div>


      {{-- Job Cards --}}
      <div class="bg-white rounded border p-5">

        <h3 class="font-semibold mb-3">Job Cards</h3>

        <form method="POST"
              action="{{ route('admin.jobs.card.upload', $job) }}"
              enctype="multipart/form-data"
              class="space-y-3 mb-6">

          @csrf

          <input type="file"
                 name="file"
                 required
                 class="block w-full">

          <input type="text"
                 name="description"
                 placeholder="Description (optional)"
                 class="border w-full px-3 py-2 rounded">

          <button class="px-3 py-2 bg-gray-900 text-white rounded">
            Upload Job Card
          </button>

        </form>

        <ul class="divide-y">

        @forelse($job->jobCards as $c)

          <li class="py-3 flex items-center justify-between">

            <div>
              <div class="font-medium">
                {{ $c->description ?: 'Job Card' }}
              </div>
              <div class="text-xs text-gray-500">
                {{ $c->file_type }}
              </div>
            </div>

            <div>
              <a href="{{ Storage::url($c->file_path) }}"
                 target="_blank"
                 class="text-blue-600">
                 View
              </a>
            </div>

          </li>

        @empty

          <li class="py-3 text-gray-500">
            No job card uploaded yet.
          </li>

        @endforelse

        </ul>

      </div>

    </div>


    {{-- RIGHT --}}
    <aside class="space-y-6">

      <div class="bg-white rounded border p-5">

        <h3 class="font-semibold mb-3">Client</h3>

        <div class="text-sm">

          <div class="font-medium">
            {{ $job->client?->name ?? '—' }}
          </div>

          @if($job->client)

            <div class="text-gray-500">
              {{ $job->client->phone ?? '' }}
            </div>

            <div class="text-gray-500">
              {{ $job->client->email ?? '' }}
            </div>

          @endif

        </div>

      </div>

    </aside>

  </div>

</div>
@endsection