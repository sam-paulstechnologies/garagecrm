@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-4">Manager Dashboard</h2>

    <div class="row g-3">

        <div class="col-md-3">
            <a href="{{ route('manager.escalations') }}" class="card p-3 text-center">
                <h5>🔥 Escalations</h5>
                <small>Human takeover required</small>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('manager.bookings.index') }}" class="card p-3 text-center">
                <h5>📅 Bookings</h5>
                <small>Upcoming jobs</small>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('manager.clients.index') }}" class="card p-3 text-center">
                <h5>👤 Clients</h5>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('manager.leads.index') }}" class="card p-3 text-center">
                <h5>📈 Leads</h5>
            </a>
        </div>

    </div>
</div>
@endsection