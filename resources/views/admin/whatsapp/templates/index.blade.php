@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-semibold">WhatsApp Templates</h1>
  <a href="{{ route('admin.whatsapp.templates.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md">New Template</a>
</div>

@if(session('success'))
  <div class="mb-4 p-3 rounded bg-green-50 text-green-800">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="mb-4 p-3 rounded bg-red-50 text-red-800">{{ session('error') }}</div>
@endif

<form method="GET" class="mb-4">
  <input name="q" value="{{ request('q') }}" placeholder="Search name or provider template"
         class="border rounded-md px-3 py-2 w-72">
  <button class="ml-2 px-3 py-2 border rounded-md">Search</button>
</form>

<div class="bg-white shadow-sm rounded-lg overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50">
      <tr>
        <th class="text-left p-3">Name</th>
        <th class="text-left p-3">Provider Template</th>
        <th class="text-left p-3">Language</th>
        <th class="text-left p-3">Status</th>
        <th class="text-right p-3">Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse($templates as $tpl)
        <tr class="border-t">
          <td class="p-3">{{ $tpl->name }}</td>
          <td class="p-3">{{ $tpl->provider_template }}</td>
          <td class="p-3">{{ $tpl->language }}</td>
          <td class="p-3">
            <span class="px-2 py-1 rounded text-xs
                @if($tpl->status==='active') bg-green-100 text-green-700
                @elseif($tpl->status==='draft') bg-yellow-100 text-yellow-700
                @else bg-gray-200 text-gray-800 @endif">
              {{ ucfirst($tpl->status) }}
            </span>
          </td>
          <td class="p-3 text-right space-x-2">
            <a href="{{ route('admin.whatsapp.templates.edit',$tpl) }}" class="text-indigo-600">Edit</a>
            <form action="{{ route('admin.whatsapp.templates.destroy',$tpl) }}" method="POST" class="inline"
                  onsubmit="return confirm('Delete template?');">
              @csrf @method('DELETE')
              <button class="text-red-600">Delete</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td class="p-6 text-center text-gray-500" colspan="5">No templates yet.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="mt-4">{{ $templates->withQueryString()->links() }}</div>
@endsection
