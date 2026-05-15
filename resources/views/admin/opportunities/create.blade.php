@extends('layouts.app')

@section('title', 'Create Opportunity')

@section('content')
<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Sales Pipeline
            </div>

            <h1 class="sf-page-title mt-3">
                Create Opportunity
            </h1>

            <p class="sf-page-subtitle">
                Create a new opportunity from a client or lead and move it through the booking pipeline.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.opportunities.index') }}" class="sf-btn-secondary">
                ← Back to Opportunities
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="sf-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="sf-alert-warning">
            {{ session('warning') }}
        </div>
    @endif

    @if(session('error'))
        <div class="sf-alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @include('admin.opportunities.form', [
        'action' => route('admin.opportunities.store'),
        'isEdit' => false,
        'opportunity' => null
    ])

</div>
@endsection