@extends('layouts.app')

@section('content')
<div class="container">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Jobs</h2>
        <a href="{{ route('admin.jobs.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
            + Create Job
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded border border-green-300">
            {{ session('success') }}
        </div>
    @endif

    <table class="table-auto w-full border text-sm">
        <thead>
            <tr class="bg-gray-100">
                <th class="px-4 py-2 border">Client</th>
                <th class="px-4 py-2 border">Description</th>
                <th class="px-4 py-2 border">Status</th>
                <th class="px-4 py-2 border">Assigned To</th>
                <th class="px-4 py-2 border">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($jobs as $job)
                <tr>
                    <td class="px-4 py-2 border">{{ $job->client->name ?? 'N/A' }}</td>
                    <td class="px-4 py-2 border">{{ $job->description }}</td>
                    <td class="px-4 py-2 border capitalize">{{ $job->status }}</td>
                    <td class="px-4 py-2 border">{{ $job->assignedUser->name ?? 'Unassigned' }}</td>
                    <td class="px-4 py-2 border">
                        <a href="{{ route('admin.jobs.show', $job->id) }}" class="text-blue-600 hover:underline">View</a>
                        <span class="mx-1">|</span>
                        <a href="{{ route('admin.jobs.edit', $job->id) }}" class="text-green-600 hover:underline">Edit</a>
                        <span class="mx-1">|</span>
                        <form action="{{ route('admin.jobs.destroy', $job->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure? This will archive the job.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Archive</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-4">No jobs found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
