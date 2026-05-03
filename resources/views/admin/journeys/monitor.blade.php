@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Journey Monitor</h3>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Journey</th>
                <th>Entity</th>
                <th>Step</th>
                <th>Status</th>
                <th>Updated</th>
            </tr>
        </thead>
        <tbody>
            @foreach($enrollments as $e)
                <tr>
                    <td>{{ $e->journey->name }}</td>
                    <td>{{ class_basename($e->enrollable_type) }} #{{ $e->enrollable_id }}</td>
                    <td>{{ $e->current_step_position }}</td>
                    <td>{{ $e->status }}</td>
                    <td>{{ $e->updated_at }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
