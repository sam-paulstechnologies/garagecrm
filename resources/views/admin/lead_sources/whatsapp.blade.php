@extends('layouts.app')

@section('title', 'WhatsApp Lead Intake')

@section('content')
    @include('admin.lead_sources.whatsapp-partials._styles')

    <div class="sf-page sf-lead-sources-page mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        @include('admin.lead_sources.whatsapp-partials._header')
        @include('admin.lead_sources.whatsapp-partials._info')

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                @include('admin.lead_sources.whatsapp-partials._configuration')
                @include('admin.lead_sources.whatsapp-partials._journey_flow')
            </div>

            @include('admin.lead_sources.whatsapp-partials._sidebar')
        </div>
    </div>

    @include('admin.lead_sources.whatsapp-partials._scripts')
@endsection
