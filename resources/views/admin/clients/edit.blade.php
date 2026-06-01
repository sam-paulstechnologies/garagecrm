{{-- resources/views/admin/clients/edit.blade.php --}}

@extends('layouts.app')

@section('title', 'SayaraForce | Edit Client')

@section('content')
    @include('admin.clients.edit-partials._styles')

    <div class="sf-page sf-client-edit mx-auto max-w-7xl px-4 py-6 space-y-6">

        @include('admin.clients.edit-partials._header')

        @include('admin.clients.edit-partials._alerts')

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <div class="lg:col-span-2">
                @include('admin.clients.edit-partials._form')
            </div>

            <div class="space-y-6">
                @include('admin.clients.edit-partials._sidebar')
            </div>

        </div>
    </div>
@endsection