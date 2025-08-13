@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="text-2xl font-bold mb-4">Garage Details</h1>

    <div class="card p-4">
        <p><strong>Name:</strong> {{ $garage->name }}</p>
        <p><strong>Location:</strong> {{ $garage->location ?? 'N/A' }}</p>
        <p><strong>Contact Email:</strong> {{ $garage->contact_email ?? 'N/A' }}</p>
        <p><strong>Created:</strong> {{ $garage->created_at->format('d M Y') }}</p>
    </div>

    <a href="{{ route('admin.garages.index') }}" class="btn btn-secondary mt-4">Back to List</a>
</div>
@endsection
