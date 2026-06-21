@extends('layouts.app')

@section('title', 'Garage Calendar')

@push('styles')
    @include('admin.calendar.index-partials._styles')
@endpush

@section('content')
<div class="sf-page sf-calendar-page space-y-6">
    @include('admin.calendar.index-partials._hero')
    @include('admin.calendar.index-partials._note')
    @include('admin.calendar.index-partials._filters')
    @include('admin.calendar.index-partials._calendar')
</div>
@endsection
