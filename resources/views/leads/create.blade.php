@extends('layouts.app')

@section('title', 'Create Lead')

@section('content')
<div class="container mx-auto py-6">
    <h1 class="text-2xl font-bold mb-4">Create New Lead</h1>

    @if($errors->any())
        <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4">
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('leads.store') }}" method="POST" class="space-y-4">
        @csrf

        <div>
            <label class="block font-medium">Name</label>
            <input type="text" name="name" class="w-full border rounded px-3 py-2" value="{{ old('name') }}" required>
        </div>

        <div>
            <label class="block font-medium">Contact</label>
            <input type="text" name="contact" class="w-full border rounded px-3 py-2" value="{{ old('contact') }}" required>
        </div>

        <div>
            <label class="block font-medium">Vehicle</label>
            <input type="text" name="vehicle" class="w-full border rounded px-3 py-2" value="{{ old('vehicle') }}" required>
        </div>

        <div>
            <label class="block font-medium">Assigned To (User ID)</label>
            <input type="number" name="assigned_to" class="w-full border rounded px-3 py-2" value="{{ old('assigned_to') }}">
        </div>

        <div>
            <label class="block font-medium">Status</label>
            <input type="text" name="status" class="w-full border rounded px-3 py-2" value="{{ old('status') }}" required>
        </div>

        <div>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Create Lead</button>
        </div>
    </form>
</div>
@endsection
