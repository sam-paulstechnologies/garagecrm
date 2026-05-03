// resources/views/admin/audiences/unassigned.blade.php

@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="mb-0">Clients not in any Audience</h3>
            <div class="text-muted small">Safety net list</div>
        </div>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.audiences.index') }}">Back</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th class="p-3">Client</th>
                        <th class="p-3">Phone</th>
                        <th class="p-3">Email</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $c)
                        <tr>
                            <td class="p-3">{{ $c->name }} <span class="text-muted">#{{ $c->id }}</span></td>
                            <td class="p-3">{{ $c->phone ?? $c->whatsapp }}</td>
                            <td class="p-3">{{ $c->email }}</td>
                            <td class="p-3">{{ $c->status }}</td>
                        </tr>
                    @empty
                        <tr><td class="p-3 text-muted" colspan="4">None. Great!</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $clients->links() }}</div>
</div>
@endsection
