@extends('layouts.app')

@section('title', 'Lead Sources')

@section('content')
    @include('admin.lead_sources.index-partials._styles')

    <div class="sf-page sf-lead-sources-page mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        @include('admin.lead_sources.index-partials._hero')
        @include('admin.lead_sources.index-partials._info')
        @include('admin.lead_sources.index-partials._source_cards')
        @include('admin.lead_sources.index-partials._journey_note')
    </div>
@endsection
