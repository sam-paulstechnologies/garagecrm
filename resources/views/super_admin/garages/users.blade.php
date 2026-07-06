@extends('super_admin.layout')

@section('title', 'Garage Users')

@section('super_admin_content')
    <div class="mb-6 rounded-3xl sa-card p-6">
        <p class="text-xs font-extrabold uppercase tracking-wide text-orange-300">Garage Users</p>
        <h1 class="mt-2 text-3xl font-black text-white">{{ $garage->name }}</h1>
        <p class="mt-2 text-sm font-semibold sa-muted">Admins, managers, mechanics, receptionists, media users, and their current status.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($users as $user)
            <div class="rounded-3xl sa-card p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-lg font-black text-white">{{ $user->name }}</p>
                        <p class="mt-1 text-sm font-bold sa-muted">{{ $user->email }}</p>
                        <p class="mt-1 text-sm font-bold sa-muted">{{ $user->phone ?? 'No phone' }}</p>
                    </div>
                    @include('super_admin.partials._badge', ['tone' => ($user->status ?? true) ? 'green' : 'red', 'label' => ($user->status ?? true) ? 'Active' : 'Inactive'])
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    @include('super_admin.partials._badge', ['tone' => 'blue', 'label' => str($user->role ?? 'user')->headline()])
                    @include('super_admin.partials._badge', ['tone' => ($user->must_change_password ?? false) ? 'orange' : 'slate', 'label' => ($user->must_change_password ?? false) ? 'Must change password' : 'Password OK'])
                </div>
            </div>
        @empty
            <div class="rounded-3xl sa-card p-6 text-sm font-bold sa-muted">No users found for this garage.</div>
        @endforelse
    </div>
@endsection
