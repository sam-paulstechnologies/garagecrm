@extends('layouts.app')
@section('content')
<h2 class="text-xl font-bold mb-4">Edit Profile</h2>
<form action="{{ route('mechanic.profile.update') }}" method="POST" class="space-y-4">
    @csrf
    @method('PATCH')
    <div>
        <label>Name</label>
        <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" class="border p-2 w-full">
    </div>
    <div>
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" class="border p-2 w-full">
    </div>
    <button type="submit" class="bg-blue-500 text-white px-4 py-2">Update</button>
</form>
@endsection