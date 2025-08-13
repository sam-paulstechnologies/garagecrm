@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="text-2xl font-bold mb-4">Edit Garage</h1>

    <form action="{{ route('admin.garages.update', $garage->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group mb-3">
            <label>Garage Name</label>
            <input name="name" class="form-control" value="{{ $garage->name }}" required />
        </div>

        <div class="form-group mb-3">
            <label>Location</label>
            <input name="location" class="form-control" value="{{ $garage->location }}" required />
        </div>

        <div class="form-group mb-3">
            <label>Manager Name</label>
            <input name="manager_name" class="form-control" value="{{ $garage->manager_name }}" required />
        </div>

        <button class="btn btn-primary">Update Garage</button>
    </form>
</div>
@endsection
