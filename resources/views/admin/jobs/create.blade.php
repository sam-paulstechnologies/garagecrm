@extends('layouts.app')

@section('title', 'Create Job')

@push('styles')
    @include('admin.jobs.create-partials._styles')
@endpush

@section('content')
<div class="sf-page sf-jobs-page space-y-6">
    @include('admin.jobs.create-partials._hero')
    @include('admin.jobs.create-partials._errors')
    @include('admin.jobs.create-partials._form')
</div>
@endsection
