@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Add Note for {{ $client->name }}</h2>

    <form action="{{ route('admin.notes.store', $client->id) }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="content">Note Content</label>
            <textarea name="content" id="content" rows="5" class="form-control" required>{{ old('content') }}</textarea>
        </div>

        <button type="submit" class="btn btn-success mt-3">Save Note</button>
        <a href="{{ route('admin.notes.index', $client->id) }}" class="btn btn-secondary mt-3">Back</a>
    </form>
</div>
@endsection
