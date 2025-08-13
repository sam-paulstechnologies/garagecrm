@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="text-xl font-bold mb-4">Job Details</h2>

    <div class="space-y-4">
        <div><strong>Client:</strong> {{ $job->client->name ?? 'N/A' }}</div>
        <div><strong>Description:</strong> {{ $job->description }}</div>
        <div><strong>Status:</strong> {{ ucfirst($job->status) }}</div>
        <div><strong>Assigned To:</strong> {{ $job->assignedUser->name ?? 'Unassigned' }}</div>
    </div>

    <div class="mt-6">
        <a href="{{ route('admin.jobs.index') }}" class="text-blue-600">← Back to Jobs</a>
    </div>
</div>
@endsection
