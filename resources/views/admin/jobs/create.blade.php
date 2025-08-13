@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="text-xl font-bold mb-4">Create Job</h2>

    <form method="POST" action="{{ route('admin.jobs.store') }}">
        @csrf

        <div class="mb-4">
            <label class="block">Client:</label>
            <select name="client_id" class="form-control" required>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block">Description:</label>
            <textarea name="description" class="form-control" required></textarea>
        </div>

        <div class="mb-4">
            <label class="block">Status:</label>
            <select name="status" class="form-control" required>
                <option value="new">New</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="block">Assign To:</label>
            <select name="assigned_to" class="form-control">
                <option value="">Unassigned</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Create</button>
    </form>
</div>
@endsection
