@extends('layouts.app')

@section('content')
<div class="container py-4">

    <h4>{{ $leadSource->name }}</h4>

    <div class="mb-3">
        <label class="form-label">Hosted Form URL</label>
        <input class="form-control" readonly value="{{ $formUrl }}">
    </div>

    <div class="mb-3">
        <label class="form-label">Embed Snippet</label>
        <textarea class="form-control" rows="5" readonly>{{ $embed }}</textarea>
    </div>

    <div class="mt-4">
        <h6>Preview</h6>
        {!! $embed !!}
    </div>

</div>
@endsection
