@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold">WhatsApp Templates</h1>
        <a href="{{ route('admin.whatsapp.templates.create') }}" class="inline-flex items-center px-4 py-2 rounded bg-gray-900 text-white">
            New Template
        </a>
    </div>

    @if (session('success'))
      <div class="mb-3 rounded bg-green-50 text-green-800 px-3 py-2">{{ session('success') }}</div>
    @endif
    @if (session('error'))
      <div class="mb-3 rounded bg-red-50 text-red-800 px-3 py-2">{{ session('error') }}</div>
    @endif

    <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-5 gap-3">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name / language / category"
               class="border rounded px-3 py-2 md:col-span-2">
        <select name="status" class="border rounded px-3 py-2">
            <option value="">Any status</option>
            @foreach(['draft','active','archived'] as $s)
                <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <select name="category" class="border rounded px-3 py-2">
            <option value="">All categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat }}" @selected(request('category')===$cat)>{{ $cat }}</option>
            @endforeach
        </select>
        <button class="rounded px-4 py-2 border">Filter</button>
    </form>

    <div class="overflow-x-auto bg-white rounded shadow">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="text-left px-3 py-2">Name</th>
                    <th class="text-left px-3 py-2">Provider Tpl</th>
                    <th class="text-left px-3 py-2">Language</th>
                    <th class="text-left px-3 py-2">Category</th>
                    <th class="text-left px-3 py-2">Status</th>
                    <th class="text-left px-3 py-2">Updated</th>
                    <th class="text-left px-3 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $t)
                <tr class="border-t">
                    <td class="px-3 py-2 font-medium">
                        <a class="underline text-blue-700" href="{{ route('admin.whatsapp.templates.show', $t) }}">{{ $t->name }}</a>
                    </td>
                    <td class="px-3 py-2">{{ $t->provider_template ?: '—' }}</td>
                    <td class="px-3 py-2">{{ strtoupper($t->language) }}</td>
                    <td class="px-3 py-2">{{ $t->category ?: '—' }}</td>
                    <td class="px-3 py-2">
                        <span class="inline-block text-xs px-2 py-1 rounded bg-gray-100 border">
                            {{ ucfirst($t->status) }}
                        </span>
                    </td>
                    <td class="px-3 py-2">{{ optional($t->updated_at)->diffForHumans() }}</td>
                    <td class="px-3 py-2">
                        <div class="flex flex-wrap gap-2 items-center">
                            <a href="{{ route('admin.whatsapp.templates.edit', $t) }}" class="text-blue-600 underline">Edit</a>

                            <form action="{{ route('admin.whatsapp.templates.destroy', $t) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Delete this template?');">
                                @csrf @method('DELETE')
                                <button class="text-red-600 underline">Delete</button>
                            </form>

                            {{-- Test send requires to_phone (controller validates it) --}}
                            <form action="{{ route('admin.whatsapp.templates.test_send', $t) }}" method="POST" class="inline-flex items-center gap-2">
                                @csrf
                                <input name="to_phone" placeholder="+9715XXXXXXX"
                                       class="border rounded px-2 py-1 text-xs" required pattern="^\+\d{8,20}$">
                                <button class="text-gray-700 underline text-sm">Test send</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-3 py-6 text-center text-gray-500">No templates yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $templates->links() }}
    </div>
</div>
@endsection
