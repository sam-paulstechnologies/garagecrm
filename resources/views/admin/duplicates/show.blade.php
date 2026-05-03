// resources/views/admin/duplicates/show.blade.php

@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="mb-0">Review Duplicate</h3>
            <div class="text-muted small">Score: {{ $candidate->match_score }} | Status: {{ $candidate->status }}</div>
        </div>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.duplicates.index') }}">Back</a>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="fw-semibold mb-2">Client A ({{ $candidate->client_a_id }})</div>
                    <div><strong>Name:</strong> {{ $candidate->clientA?->name }}</div>
                    <div><strong>Phone:</strong> {{ $candidate->clientA?->phone }}</div>
                    <div><strong>WhatsApp:</strong> {{ $candidate->clientA?->whatsapp }}</div>
                    <div><strong>Email:</strong> {{ $candidate->clientA?->email }}</div>
                    <div><strong>Status:</strong> {{ $candidate->clientA?->status }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="fw-semibold mb-2">Client B ({{ $candidate->client_b_id }})</div>
                    <div><strong>Name:</strong> {{ $candidate->clientB?->name }}</div>
                    <div><strong>Phone:</strong> {{ $candidate->clientB?->phone }}</div>
                    <div><strong>WhatsApp:</strong> {{ $candidate->clientB?->whatsapp }}</div>
                    <div><strong>Email:</strong> {{ $candidate->clientB?->email }}</div>
                    <div><strong>Status:</strong> {{ $candidate->clientB?->status }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-3">
        <div class="card-body">
            <div class="fw-semibold mb-2">Merge Action</div>

            <form method="POST" action="{{ route('admin.duplicates.merge', $candidate->id) }}" class="d-flex gap-3 align-items-center">
                @csrf
                <div>
                    <label class="form-label mb-1">Keep</label>
                    <select name="keep" class="form-select form-select-sm" style="width:200px">
                        <option value="a">Keep A (#{{ $candidate->client_a_id }})</option>
                        <option value="b">Keep B (#{{ $candidate->client_b_id }})</option>
                    </select>
                </div>
                <button class="btn btn-danger btn-sm mt-4" type="submit" onclick="return confirm('Merge clients? This will archive the merged client.')">Merge</button>
            </form>

            <form method="POST" action="{{ route('admin.duplicates.ignore', $candidate->id) }}" class="mt-3">
                @csrf
                <button class="btn btn-outline-secondary btn-sm" type="submit">Ignore</button>
            </form>

            <div class="mt-3 text-muted small">
                Reasons: {{ is_array($candidate->reasons_json) ? implode(', ', $candidate->reasons_json) : '' }}
            </div>
        </div>
    </div>
</div>
@endsection
