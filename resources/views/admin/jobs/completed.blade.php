@extends('layouts.app')

@section('title', 'Completed Jobs')

@push('styles')
    @include('admin.jobs.completed-partials._styles')
@endpush

@section('content')
<div class="sf-page sf-jobs-page mx-auto max-w-7xl px-4 py-6 space-y-6">
    <div class="sf-index-sticky-panel space-y-6">
        @include('admin.jobs.completed-partials._hero')
        @include('admin.jobs.completed-partials._filters')
        @include('admin.jobs.completed-partials._service_buckets')
    </div>

    @include('admin.jobs.completed-partials._table')
    @include('admin.jobs.completed-partials._pagination')
</div>
@endsection
