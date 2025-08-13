@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="text-2xl font-bold mb-4">All Garages</h1>
    <a href="{{ route('admin.garages.create') }}" class="btn btn-success mb-3">+ Add Garage</a>

    <table class="table table-bordered">
        <thead>
            <tr><th>Name</th><th>Location</th><th>Manager</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @foreach($garages as $garage)
            <tr>
                <td>{{ $garage->name }}</td>
                <td>{{ $garage->location }}</td>
                <td>{{ $garage->manager_name }}</td>
                <td>
                    <a href="{{ route('admin.garages.edit', $garage->id) }}" class="btn btn-sm btn-primary">Edit</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
