@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-semibold">Edit Template</h1>

  <form method="POST" action="{{ route('admin.whatsapp.templates.test_send',$template) }}" class="flex flex-wrap items-center gap-2">
    @csrf
    <input name="to_phone" placeholder="+9715XXXXXXXX" class="border rounded-md px-3 py-2" required>
    @if(is_array($template->variables))
      @foreach($template->variables as $v)
        <input name="vars[{{ $v }}]" placeholder="{{ $v }}" class="border rounded-md px-3 py-2">
      @endforeach
    @endif
    <button class="px-3 py-2 bg-green-600 text-white rounded-md">Test Send</button>
  </form>
</div>

@if(session('success'))
  <div class="mb-4 p-3 rounded bg-green-50 text-green-800">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="mb-4 p-3 rounded bg-red-50 text-red-800">{{ session('error') }}</div>
@endif

<form method="POST" action="{{ route('admin.whatsapp.templates.update',$template) }}">
  @method('PUT')
  @include('admin.whatsapp.templates._form', ['template'=>$template])
</form>
@endsection
