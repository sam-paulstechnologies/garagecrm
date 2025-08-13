@extends('layouts.admin')

@section('content')
<div id="react-root"></div>
@push('scripts')
    @vite(['resources/js/app.jsx'])
@endpush
@endsection
