@extends('layouts.app')

@section('content')
<div class="container max-w-3xl">
    @if (session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif

    <h1 class="h4 mb-4">AI Policy</h1>

    <form method="POST" action="{{ route('admin.ai.policy.update') }}">
        @csrf

        <div class="card mb-3">
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="enabled" name="enabled" value="1" {{ old('enabled', $initial['enabled']) ? 'checked' : '' }}>
                    <label class="form-check-label" for="enabled">Enable AI Policy</label>
                </div>

                <div class="mb-3">
                    <label class="form-label">Confidence Threshold (0–1)</label>
                    <input type="number" step="0.01" min="0" max="1" class="form-control" name="confidence_threshold" value="{{ old('confidence_threshold', $initial['confidence_threshold']) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Policy Reply (used on handoff/blocked)</label>
                    <textarea class="form-control" name="policy_reply" rows="2">{{ old('policy_reply', $initial['policy_reply']) }}</textarea>
                    <div class="form-text">Max 480 characters.</div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Intent Matrix</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Handle (comma-separated)</label>
                    <input type="text" class="form-control" name="intent_handle" value="{{ old('intent_handle', $initial['intent_handle']) }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Handoff (comma-separated)</label>
                    <input type="text" class="form-control" name="intent_handoff" value="{{ old('intent_handoff', $initial['intent_handoff']) }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Forbidden (comma-separated)</label>
                    <input type="text" class="form-control" name="intent_forbidden" value="{{ old('intent_forbidden', $initial['intent_forbidden']) }}">
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Forbidden Topics</div>
            <div class="card-body">
                <input type="text" class="form-control" name="forbidden_topics" value="{{ old('forbidden_topics', $initial['forbidden_topics']) }}">
                <div class="form-text">Comma-separated keywords to block (e.g., “pickup, drop, payment link, refund”).</div>
            </div>
        </div>

        <button class="btn btn-primary">Save Policy</button>
    </form>
</div>
@endsection
