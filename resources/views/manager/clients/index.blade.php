@extends('layouts.manager')

@section('title', 'Manager Clients')

@section('content')
@php
    $clientName = function ($client) {
        return $client->name
            ?? $client->full_name
            ?? $client->customer_name
            ?? $client->client_name
            ?? 'Client #' . $client->id;
    };

    $clientPhone = function ($client) {
        return $client->phone
            ?? $client->mobile
            ?? $client->phone_number
            ?? $client->whatsapp_number
            ?? '-';
    };

    $clientVehicle = function ($client) {
        $make = $client->vehicle_make ?? $client->make ?? null;
        $model = $client->vehicle_model ?? $client->model ?? null;
        $plate = $client->plate_number ?? $client->registration_number ?? null;

        $vehicle = trim(($make ?? '') . ' ' . ($model ?? ''));

        if ($plate) {
            $vehicle = $vehicle ? $vehicle . ' · ' . $plate : $plate;
        }

        return $vehicle ?: '-';
    };
@endphp

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Clients</h1>
            <p class="text-muted mb-0">
                View customer records linked to your garage. Manager access is read-only here.
            </p>
        </div>

        <a href="{{ route('manager.dashboard') }}" class="btn btn-outline-secondary">
            Back to Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('manager.clients.index') }}" class="row g-3 align-items-end">
                <div class="col-md-10">
                    <label class="form-label">Search Clients</label>
                    <input
                        type="text"
                        name="q"
                        value="{{ $q ?? request('q') }}"
                        class="form-control"
                        placeholder="Search name, phone, email, vehicle, plate number"
                    >
                </div>

                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary">
                        Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Client List</h5>
                <small class="text-muted">
                    Customers created from leads, bookings, and jobs.
                </small>
            </div>

            <span class="badge bg-light text-dark">
                {{ method_exists($clients, 'total') ? $clients->total() : $clients->count() }} client(s)
            </span>
        </div>

        <div class="card-body p-0">
            @if($clients->count())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Client</th>
                                <th>Contact</th>
                                <th>Vehicle / Plate</th>
                                <th>Source</th>
                                <th>Created</th>
                                <th>Notes</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($clients as $client)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $clientName($client) }}
                                        </div>

                                        <small class="text-muted">
                                            #{{ $client->id }}
                                            @if(!empty($client->email))
                                                · {{ $client->email }}
                                            @endif
                                        </small>
                                    </td>

                                    <td>
                                        <div>{{ $clientPhone($client) }}</div>

                                        @if(!empty($client->preferred_channel))
                                            <small class="text-muted">
                                                Preferred: {{ $client->preferred_channel }}
                                            </small>
                                        @endif
                                    </td>

                                    <td>
                                        {{ $clientVehicle($client) }}
                                    </td>

                                    <td>
                                        <span class="badge bg-light text-dark">
                                            {{ $client->source ?? '-' }}
                                        </span>
                                    </td>

                                    <td>
                                        @if(!empty($client->created_at))
                                            {{ \Carbon\Carbon::parse($client->created_at)->format('d M Y') }}
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td style="max-width: 320px;">
                                        <span class="small text-muted">
                                            {{ \Illuminate\Support\Str::limit($client->notes ?? $client->internal_notes ?? '-', 120) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-5 text-center">
                    <h5 class="mb-2">No clients found</h5>
                    <p class="text-muted mb-0">
                        Client records will appear here once leads/bookings are converted.
                    </p>
                </div>
            @endif
        </div>

        @if(method_exists($clients, 'links'))
            <div class="card-footer bg-white">
                {{ $clients->links() }}
            </div>
        @endif
    </div>
</div>
@endsection