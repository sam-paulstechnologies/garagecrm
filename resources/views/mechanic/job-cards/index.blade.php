@extends('layouts.app')
@section('content')
<h2 class="text-xl font-bold mb-4">Job Cards</h2>
<table class="min-w-full bg-white">
    <thead><tr><th>ID</th><th>Status</th><th>Created At</th></tr></thead>
    <tbody>
        @foreach($jobCards as $card)
        <tr>
            <td>{{ $card->id }}</td>
            <td>{{ $card->status }}</td>
            <td>{{ $card->created_at->format('d M Y') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection