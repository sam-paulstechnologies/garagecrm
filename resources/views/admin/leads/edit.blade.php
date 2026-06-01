@extends('layouts.app')

@section('title', 'Edit Lead')

@section('content')
    @include('admin.leads.edit-partials._styles')

    <div class="sf-page sf-leads-edit mx-auto max-w-7xl px-4 py-6 space-y-6">
        @include('admin.leads.edit-partials._header')

        @include('admin.leads.edit-partials._alerts')

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                @include('admin.leads.edit-partials._form')
            </div>

            <div class="space-y-6">
                @include('admin.leads.edit-partials._sidebar')
            </div>
        </div>
    </div>
@endsection
