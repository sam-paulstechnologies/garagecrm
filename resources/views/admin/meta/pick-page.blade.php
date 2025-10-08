@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6 space-y-4">
  <h1 class="text-xl font-bold mb-2">Select a Facebook Page</h1>

  @if(empty($pages))
    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900">
      No managed Pages found on this Facebook account.
    </div>
  @else
    <form method="POST" action="{{ route('admin.meta.select_page') }}" class="space-y-3">
      @csrf
      <select name="page_id" class="w-full border rounded p-2"
              onchange="document.getElementById('pagename').value=this.selectedOptions[0].dataset.name" required>
        <option value="" disabled selected>Choose a page…</option>
        @foreach($pages as $p)
          <option value="{{ $p['id'] }}" data-name="{{ $p['name'] }}">{{ $p['name'] }}</option>
        @endforeach
      </select>
      <input type="hidden" id="pagename" name="page_name">
      <div class="flex gap-2">
        <button class="px-4 py-2 rounded bg-indigo-600 text-white">Connect</button>
        <a href="{{ route('admin.settings.index') }}" class="px-4 py-2 rounded border">Cancel</a>
      </div>
    </form>
  @endif
</div>
@endsection
