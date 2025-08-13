@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="text-2xl font-bold mb-4">Create New Garage</h1>

    <form action="{{ route('admin.garages.store') }}" method="POST">
        @csrf
        <div class="form-group mb-3">
            <label>Garage Name</label>
            <input name="name" class="form-control" required />
        </div>

        <div class="form-group mb-3">
            <label>Location</label>
            <input name="location" class="form-control" required />
        </div>

        <div class="form-group mb-3">
            <label>Manager Name</label>
            <input name="manager_name" class="form-control" required />
        </div>

        <button class="btn btn-primary">Create Garage</button>
    </form>
</div>
@endsection
