// resources/views/admin/audiences/create.blade.php

@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3 class="mb-3">Create Audience</h3>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.audiences.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input name="name" class="form-control" required value="{{ old('name') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Rules JSON</label>
                    <textarea name="rules_json" class="form-control" rows="8" placeholder='{"operator":"AND","conditions":[{"field":"preferred_channel","op":"=","value":"whatsapp"}]}'>{{ old('rules_json') }}</textarea>
                    <div class="text-muted small mt-1">
                        Supported ops: =, !=, contains, in, not_in, &gt;, &gt;=, &lt;, &lt;=, is_null, not_null, &gt;=days_ago, &lt;=days_ago
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                    <label class="form-check-label">Active</label>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Create</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.audiences.index') }}">Cancel</a>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection
