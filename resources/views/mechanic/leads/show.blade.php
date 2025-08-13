@extends('layouts.app')
@section('content')
<h2 class="text-xl font-bold mb-4">Lead #{{ $lead->id }}</h2>
<p>Name: {{ $lead->name }}</p>
<p>Status: {{ $lead->status }}</p>
<p>Details: {{ $lead->details }}</p>
@endsection