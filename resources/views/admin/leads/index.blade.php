@extends('layouts.app')

@section('title', $pageTitle ?? 'Leads')

@section('content')
    @include('admin.leads.index-partials._styles')

    <div class="sf-page sf-leads-page mx-auto max-w-7xl px-4 py-6 space-y-6">
        @include('admin.leads.index-partials._hero')

        @include('admin.leads.index-partials._stats')

        @include('admin.leads.index-partials._bucket_cards')

        @include('admin.leads.index-partials._filters')

        @include('admin.leads.index-partials._table')

        @include('admin.leads.index-partials._pagination')
    </div>
@endsection
