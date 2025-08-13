@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Create Lead</h1>
    <form action="{{ route('admin.leads.store') }}" method="POST">
        @csrf
        @include('admin.leads.partials.form', ['lead' => null])
        <button type="submit" class="btn btn-primary">Save Lead</button>
    </form>
</div>
@endsection
