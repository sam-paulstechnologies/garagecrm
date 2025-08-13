@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="text-2xl font-bold mb-4">Company Settings</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('admin.settings.company.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="form-group mb-3">
            <label>Company Name</label>
            <input name="name" value="{{ old('name', $company->name ?? '') }}" class="form-control" required />
        </div>

        <div class="form-group mb-3">
            <label>Email</label>
            <input name="email" value="{{ old('email', $company->email ?? '') }}" type="email" class="form-control" />
        </div>

        <div class="form-group mb-3">
            <label>Address</label>
            <textarea name="address" class="form-control" required>{{ old('address', $company->address ?? '') }}</textarea>
        </div>

        <div class="form-group mb-3">
            <label>Phone</label>
            <input name="phone" value="{{ old('phone', $company->phone ?? '') }}" class="form-control" />
        </div>

        {{-- Optional: Logo upload field --}}
        {{-- 
        <div class="form-group mb-3">
            <label>Logo</label>
            <input name="logo" type="file" class="form-control" />
            @if($company->logo)
                <img src="{{ asset($company->logo) }}" height="60" class="mt-2">
            @endif
        </div>
        --}}

        <button class="btn btn-primary">Update Settings</button>
    </form>
</div>
@endsection
