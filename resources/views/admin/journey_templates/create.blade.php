@extends('layouts.app')

@section('title', 'Create Journey Template')

@section('content')
<h1 class="text-2xl font-bold mb-4">Create New Template</h1>

<form action="{{ route('admin.journey_templates.store') }}" method="POST" class="space-y-4">
    @csrf

    <div>
        <label class="block">Name</label>
        <input type="text" name="name" class="w-full border p-2" required>
    </div>

    <div>
        <label class="block">Type</label>
        <select name="type" class="w-full border p-2" required>
            <option value="Email">Email</option>
            <option value="WhatsApp">WhatsApp</option>
            <option value="SMS">SMS</option>
        </select>
    </div>

    <div>
        <label class="block">Subject (Optional)</label>
        <input type="text" name="subject" class="w-full border p-2">
    </div>

    <div>
        <label class="block">Content</label>
        <textarea name="content" rows="6" class="w-full border p-2" required></textarea>
    </div>

    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded">Create Template</button>
</form>
@endsection
