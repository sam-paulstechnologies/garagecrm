@extends('layouts.app')

@section('title', 'Create Opportunity')

@section('content')
    @include('admin.opportunities.create-partials._styles')

    <div class="sf-page sf-opportunity-form-page mx-auto max-w-7xl px-4 py-6 space-y-6">
        @include('admin.opportunities.create-partials._hero')
        @include('admin.opportunities.create-partials._alerts')
        @include('admin.opportunities.create-partials._form')
    </div>
@endsection
