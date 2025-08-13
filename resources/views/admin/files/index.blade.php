@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Files for {{ $client->name }}</h2>

    <a href="{{ route('admin.clients.files.create', $client->id) }}" class="btn btn-primary mb-3">Upload New File</a>

    @if ($files->count())
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>File Name</th>
                    <th>Type</th>
                    <th>Uploaded At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($files as $file)
                    <tr>
                        <td>{{ $file->file_name }}</td>
                        <td>{{ $file->file_type }}</td>
                        <td>{{ $file->uploaded_at ? $file->uploaded_at->format('Y-m-d H:i') : '—' }}</td>
                        <td>
                            <a href="{{ asset('storage/' . $file->file_path) }}" target="_blank" class="btn btn-sm btn-success">View</a>
                            <form action="{{ route('admin.clients.files.destroy', [$client->id, $file->id]) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this file?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No files uploaded yet.</p>
    @endif
</div>
@endsection
