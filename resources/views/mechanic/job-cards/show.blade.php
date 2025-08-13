@extends('layouts.app')
@section('content')
<h2 class="text-xl font-bold mb-4">Job Card #{{ $jobCard->id }}</h2>
<p>Status: {{ $jobCard->status }}</p>
<p>Description: {{ $jobCard->description }}</p>
@endsection