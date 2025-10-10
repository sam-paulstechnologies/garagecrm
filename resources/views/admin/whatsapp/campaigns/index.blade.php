@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">WhatsApp Campaigns</h1>
    <a href="{{ route('admin.whatsapp.campaigns.create') }}"
       class="px-3 py-2 rounded bg-indigo-600 text-white">New Campaign</a>
  </div>

  @if (session('success'))
    <div class="mb-4 p-3 rounded bg-green-50 text-green-800">{{ session('success') }}</div>
  @endif

  <div class="border rounded">
    <div class="p-4 text-gray-600">No campaigns yet. Click “New Campaign”.</div>
  </div>
</div>
@endsection
