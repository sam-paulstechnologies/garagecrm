// resources/views/admin/duplicates/index.blade.php

@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="mb-0">Duplicate Clients</h3>
            <div class="text-muted small">Review and merge duplicates</div>
        </div>
        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('admin.duplicates.scan') }}">
                @csrf
                <button class="btn btn-outline-primary btn-sm" type="submit">Scan Now</button>
            </form>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.dashboard') }}">Back</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif

    <div class="mb-3 d-flex gap-2">
        <a class="btn btn-sm {{ $status==='open' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.duplicates.index', ['status'=>'open']) }}">Open</a>
        <a class="btn btn-sm {{ $status==='merged' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.duplicates.index', ['status'=>'merged']) }}">Merged</a>
        <a class="btn btn-sm {{ $status==='ignored' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.duplicates.index', ['status'=>'ignored']) }}">Ignored</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th class="p-3">Client A</th>
                        <th class="p-3">Client B</th>
                        <th class="p-3">Score</th>
                        <th class="p-3">Reasons</th>
                        <th class="p-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($candidates as $c)
                        <tr>
                            <td class="p-3">{{ $c->clientA?->name }} <span class="text-muted">#{{ $c->client_a_id }}</span></td>
                            <td class="p-3">{{ $c->clientB?->name }} <span class="text-muted">#{{ $c->client_b_id }}</span></td>
                            <td class="p-3"><span class="badge bg-dark">{{ $c->match_score }}</span></td>
                            <td class="p-3 text-muted small">{{ is_array($c->reasons_json) ? implode(', ', $c->reasons_json) : '' }}</td>
                            <td class="p-3 text-end">
                                <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.duplicates.show', $c->id) }}">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="p-3 text-muted" colspan="5">No records.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $candidates->links() }}</div>
</div>
@endsection
