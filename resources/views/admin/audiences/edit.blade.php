// resources/views/admin/audiences/edit.blade.php

@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3 class="mb-3">Edit Audience</h3>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.audiences.update', $audience->id) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input name="name" class="form-control" required value="{{ old('name', $audience->name) }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $audience->description) }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Rules JSON (new version)</label>
                    <textarea name="rules_json" class="form-control" rows="8">{{ old('rules_json', json_encode($rules?->rules_json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) }}</textarea>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $audience->is_active ? 'checked' : '' }}>
                    <label class="form-check-label">Active</label>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Save</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.audiences.show', $audience->id) }}">Cancel</a>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection
