@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Notes for {{ $client->name }}</h2>

    <a href="{{ route('admin.notes.create', $client->id) }}" class="btn btn-primary mb-3">Add New Note</a>

    @foreach ($client->notes as $note)
        <div class="card mb-3">
            <div class="card-body">
                <p>{{ $note->content }}</p>
                <small class="text-muted">By: {{ $note->user->name ?? 'Unknown' }} on {{ $note->created_at->format('d M Y h:i A') }}</small>
            </div>
        </div>
    @endforeach
</div>
@endsection
