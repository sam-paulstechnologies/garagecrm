@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Upload File for {{ $client->name }}</h2>

    <form action="{{ route('admin.clients.files.store', $client->id) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label for="file" class="form-label">Choose File</label>
            <input type="file" name="file" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="file_type" class="form-label">File Type</label>
            <input type="text" name="file_type" class="form-control" placeholder="e.g., Contract, Invoice, ID Proof" required>
        </div>

        <button type="submit" class="btn btn-primary">Upload</button>
        <a href="{{ route('admin.clients.show', $client->id) }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
