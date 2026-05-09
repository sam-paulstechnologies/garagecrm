@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3>Conversation - {{ $lead->name }}</h3>

    <div class="card p-3 mb-3" style="height:400px; overflow-y:auto;">

        @forelse($messages as $msg)
            <div class="mb-2">
                <strong>{{ $msg->direction === 'in' ? 'User' : 'Bot/Manager' }}:</strong>
                <br>
                {{ $msg->body ?? $msg->message ?? '' }}
            </div>
        @empty
            <p class="text-muted mb-0">No messages yet.</p>
        @endforelse

    </div>

    <!-- Reply -->
    <form method="POST" action="{{ route('manager.conversation.reply', $lead->id) }}">
        @csrf

        <div class="input-group">
            <input type="text" name="message" class="form-control" placeholder="Type reply..." required>
            <button class="btn btn-success" type="submit">Send</button>
        </div>
    </form>

    <!-- Resume Bot -->
    <form method="POST" action="{{ route('manager.conversation.resume', $lead->id) }}" class="mt-2">
        @csrf
        <button class="btn btn-warning" type="submit">Resume Bot</button>
    </form>

</div>
@endsection