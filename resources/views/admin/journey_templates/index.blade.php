@extends('layouts.app')

@section('title', 'Journey Templates')

@section('content')
<div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Journey Templates</h1>
    <a href="{{ route('admin.journey_templates.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded">+ New Template</a>
</div>

@if(session('success'))
    <div class="bg-green-100 text-green-800 p-2 rounded mb-4">{{ session('success') }}</div>
@endif

<table class="w-full bg-white shadow rounded">
    <thead>
        <tr class="bg-gray-100 text-left">
            <th class="p-2">Name</th>
            <th class="p-2">Type</th>
            <th class="p-2">Subject</th>
            <th class="p-2">Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($templates as $template)
        <tr>
            <td class="p-2">{{ $template->name }}</td>
            <td class="p-2">{{ $template->type }}</td>
            <td class="p-2">{{ $template->subject ?? '-' }}</td>
            <td class="p-2">
                <form method="POST" action="{{ route('admin.journey_templates.destroy', $template->id) }}">
                    @csrf @method('DELETE')
                    <button type="submit" onclick="return confirm('Delete this template?')" class="text-red-600">Delete</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="mt-4">{{ $templates->links() }}</div>
@endsection
