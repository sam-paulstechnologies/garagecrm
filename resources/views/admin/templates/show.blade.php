@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Template: {{ $template->name }}</h2>
    <p><strong>Category:</strong> {{ $template->category }}</p>
    <p><strong>Type:</strong> {{ ucfirst($template->type) }}</p>
    <p><strong>Global?</strong> {{ $template->is_global ? 'Yes' : 'No' }}</p>

    <div class="card mt-3 p-3">
        <pre style="white-space: pre-wrap">{{ $template->content }}</pre>
    </div>
</div>
@endsection
