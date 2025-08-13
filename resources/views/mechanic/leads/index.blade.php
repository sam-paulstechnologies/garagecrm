@extends('layouts.app')
@section('content')
<h2 class="text-xl font-bold mb-4">Assigned Leads</h2>
<table class="min-w-full bg-white">
    <thead><tr><th>ID</th><th>Name</th><th>Status</th></tr></thead>
    <tbody>
        @foreach($leads as $lead)
        <tr>
            <td>{{ $lead->id }}</td>
            <td>{{ $lead->name }}</td>
            <td>{{ $lead->status }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection