@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-semibold mb-6">New WhatsApp Template</h1>
<form method="POST" action="{{ route('admin.whatsapp.templates.store') }}">
  @include('admin.whatsapp.templates._form')
</form>
@endsection
