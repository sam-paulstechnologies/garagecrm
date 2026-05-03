@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2>Coming Soon</h2>

    <p>This manager section will be connected shortly.</p>

    <a href="{{ route('manager.dashboard') }}" class="btn btn-primary">
        Back to Manager Dashboard
    </a>
</div>
@endsection