@extends('layouts.app')

@section('title', 'SayaraForce | Edit Vehicle')

@section('content')
<div class="sf-page sf-vehicle-edit-page mx-auto max-w-5xl px-4 py-6 space-y-6">
    <div class="sf-vehicle-edit-hero rounded-2xl border border-slate-800 bg-slate-900/70 p-5 shadow-sm">
        <p class="text-xs font-black uppercase tracking-[0.22em] text-orange-300">
            Vehicle Profile
        </p>

        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-white">
            Edit Vehicle
        </h1>

        <p class="mt-2 max-w-2xl text-sm font-medium text-slate-300">
            Keep vehicle details accurate for service history, reminders, bookings, and client profile completion.
        </p>
    </div>

    @include('admin.vehicles.form', [
        'action' => route('admin.vehicles.update', $vehicle->id),
        'method' => 'PUT',
        'vehicle' => $vehicle,
        'clients' => $clients,
    ])
</div>
@endsection
