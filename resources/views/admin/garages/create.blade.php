{{-- resources/views/admin/garages/create.blade.php --}}

@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0">Create Garage</h1>
        <a href="{{ route('admin.garages.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please fix the errors below.</strong>
        </div>
    @endif

    <form action="{{ route('admin.garages.store') }}" method="POST" class="card p-3">
        @csrf

        <div class="mb-3">
            <label class="form-label">Garage Name <span class="text-danger">*</span></label>
            <input name="name" class="form-control" value="{{ old('name') }}" required maxlength="191">
            @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Phone</label>
                <input name="phone" class="form-control" value="{{ old('phone') }}" maxlength="30">
                @error('phone') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Email</label>
                <input name="email" type="email" class="form-control" value="{{ old('email') }}" maxlength="191">
                @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Default Garage</label>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}>
                    <label class="form-check-label">Set as default</label>
                </div>
                @error('is_default') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="3" maxlength="255">{{ old('address') }}</textarea>
            @error('address') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary">Create</button>
            <a href="{{ route('admin.garages.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
