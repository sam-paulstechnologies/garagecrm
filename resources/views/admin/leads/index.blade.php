@extends('layouts.app')

@section('title', $pageTitle ?? 'Leads')

@section('content')
    @include('admin.leads.index-partials._styles')

    <div class="sf-page sf-leads-page w-full px-4 py-6 space-y-6 sm:px-6 lg:px-8">
        <div class="sf-index-sticky-panel space-y-6">
            @include('admin.leads.index-partials._hero')

            {{-- Search and filter first --}}
            @include('admin.leads.index-partials._filters')

            {{-- Lead buckets second --}}
            @include('admin.leads.index-partials._bucket_cards')

            {{-- KPI tiles third --}}
            @include('admin.leads.index-partials._stats')
        </div>

        {{-- Table --}}
        @include('admin.leads.index-partials._table')

        @include('admin.leads.index-partials._pagination')
    </div>
@endsection
