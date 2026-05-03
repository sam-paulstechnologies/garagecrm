@extends('layouts.app')

@section('content')
<div class="px-6 py-4">
    <h2 class="text-xl font-semibold mb-4">Garage Calendar</h2>

    <div
        id="calendar"
        data-events="{{ route('admin.calendar.events') }}"
        style="background:#fff;padding:12px;border-radius:8px;"
    ></div>
</div>
@endsection
