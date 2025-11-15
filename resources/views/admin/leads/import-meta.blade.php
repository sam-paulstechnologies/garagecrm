@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto p-6 space-y-4">
  <h1 class="text-xl font-semibold">Import Meta Leads</h1>

  @if (session('success'))
    <div class="p-3 rounded bg-green-50 border border-green-200 text-green-800">{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="p-3 rounded bg-red-50 border border-red-200 text-red-800">{{ session('error') }}</div>
  @endif
  @if (session('meta_output'))
    <pre class="p-3 bg-gray-900 text-gray-100 rounded">{{ session('meta_output') }}</pre>
  @endif

  <form method="POST" action="{{ route('admin.leads.import.meta.run') }}" class="space-y-3">
    @csrf
    <label class="block">
      <span class="text-sm">Access Token (optional, overrides saved)</span>
      <input name="meta_access_token" class="w-full border rounded p-2" />
    </label>
    <label class="block">
      <span class="text-sm">Form ID (optional, overrides saved)</span>
      <input name="meta_form_id" class="w-full border rounded p-2" />
    </label>
    <label class="block">
      <span class="text-sm">Limit</span>
      <input type="number" name="limit" value="100" min="1" class="w-full border rounded p-2" />
    </label>
    <div class="flex gap-2">
      <button class="px-4 py-2 rounded bg-indigo-600 text-white">Run Import</button>
      <a href="{{ route('admin.leads.index') }}" class="px-4 py-2 rounded border">Back</a>
    </div>
  </form>
</div>
@endsection
