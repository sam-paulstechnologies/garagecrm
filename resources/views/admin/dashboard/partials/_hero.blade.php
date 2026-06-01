{{-- resources/views/admin/dashboard/partials/_hero.blade.php --}}

@php
    $adminName = auth()->user()->name ?? 'Admin';
@endphp

<div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-sm">
    <div class="min-w-0">
        <h1 class="text-3xl font-extrabold tracking-tight text-white">
            Admin Dashboard
        </h1>

        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-400">
            Welcome back, {{ $adminName }}. Track leads, bookings, jobs, revenue, and WhatsApp activity from one place.
        </p>
    </div>
</div>