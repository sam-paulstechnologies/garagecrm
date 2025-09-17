@extends('emails.layouts.base')

@section('content')
  @if(!empty($lines) && is_array($lines))
    @foreach($lines as $line)
      <p>{{ $line }}</p>
    @endforeach
  @endif
@endsection
