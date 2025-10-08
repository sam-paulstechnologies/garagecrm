@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-6 py-8">
    <h2 class="text-2xl font-semibold mb-4">Connect a Facebook Page</h2>
    <p class="text-gray-600 mb-6">Choose which Page to connect for Meta Lead Ads.</p>

    <form method="POST" action="{{ route('admin.settings.meta.save') }}" class="bg-white shadow rounded p-6">
        @csrf
        <div class="space-y-3">
            @foreach ($pages as $p)
                <label class="flex items-center gap-3 p-3 border rounded hover:bg-gray-50">
                    <input type="radio" name="page_id" value="{{ $p['id'] }}" required>
                    <div>
                        <div class="font-medium">{{ $p['name'] ?? 'Page' }}</div>
                        <div class="text-xs text-gray-500">ID: {{ $p['id'] }}</div>
                    </div>
                </label>
            @endforeach
        </div>

        <div class="mt-6">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                Connect Selected Page
            </button>
            <a href="{{ route('admin.settings.index') }}" class="ml-3 text-gray-600 hover:underline">Cancel</a>
        </div>
    </form>
</div>
@endsection
