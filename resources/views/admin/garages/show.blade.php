{{-- resources/views/admin/garages/show.blade.php --}}

@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0">Garage Details</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.garages.edit', $garage->id) }}" class="btn btn-outline-primary">Edit</a>
            <a href="{{ route('admin.garages.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card p-3">
        <div class="row">
            <div class="col-md-6 mb-2"><strong>Name:</strong> {{ $garage->name }}</div>
            <div class="col-md-6 mb-2"><strong>Default:</strong> {{ $garage->is_default ? 'Yes' : 'No' }}</div>

            <div class="col-md-6 mb-2"><strong>Phone:</strong> {{ $garage->phone ?? '-' }}</div>
            <div class="col-md-6 mb-2"><strong>Email:</strong> {{ $garage->email ?? '-' }}</div>

            <div class="col-12 mb-2"><strong>Address:</strong> {{ $garage->address ?? '-' }}</div>

            <div class="col-md-6 mb-2"><strong>Created:</strong> {{ optional($garage->created_at)->format('d M Y, h:i A') }}</div>
            <div class="col-md-6 mb-2"><strong>Updated:</strong> {{ optional($garage->updated_at)->format('d M Y, h:i A') }}</div>
        </div>
    </div>
</div>
@endsection
