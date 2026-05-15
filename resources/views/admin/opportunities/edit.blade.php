@extends('layouts.app')

@section('title', 'Edit Opportunity')

@section('content')
<div class="sf-page space-y-6">

    {{-- Header --}}
    <div class="sf-page-header">
        <div>
            <div class="sf-kicker">
                Sales Pipeline
            </div>

            <h1 class="sf-page-title mt-3">
                Edit Opportunity
            </h1>

            <p class="sf-page-subtitle">
                Update opportunity details, stage, vehicle, services, appointment planning, and booking confirmation.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.opportunities.show', $opportunity->id) }}" class="sf-btn-secondary">
                View Opportunity
            </a>

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
        'action' => route('admin.opportunities.update', $opportunity->id),
        'isEdit' => true,
        'opportunity' => $opportunity
    ])

</div>
@endsection