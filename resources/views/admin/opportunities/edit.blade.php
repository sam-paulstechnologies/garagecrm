@extends('layouts.app')

@section('title', 'Edit Opportunity')

@section('content')
    @include('admin.opportunities.edit-partials._styles')

    <div class="sf-page sf-opportunity-form-page mx-auto max-w-7xl px-4 py-6 space-y-6">
        @include('admin.opportunities.edit-partials._hero')
        @include('admin.opportunities.edit-partials._alerts')
        @include('admin.opportunities.edit-partials._form')
    </div>
@endsection
