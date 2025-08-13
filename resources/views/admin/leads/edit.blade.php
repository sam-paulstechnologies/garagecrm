@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Edit Lead</h1>
    <form action="{{ route('admin.leads.update', $lead) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.leads.partials.form', ['lead' => $lead])
        <button type="submit" class="btn btn-success">Update Lead</button>
    </form>
</div>
@endsection
