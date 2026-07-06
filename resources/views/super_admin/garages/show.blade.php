@extends('super_admin.layout')

@section('title', 'Garage Detail')

@section('super_admin_content')
    <div class="mb-6 rounded-3xl sa-card p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-wide text-orange-300">Garage Detail</p>
                <h1 class="mt-2 text-3xl font-black text-white">{{ $garage->name }}</h1>
                <p class="mt-2 text-sm font-semibold sa-muted">{{ $garage->email ?? 'No email' }} | {{ $garage->phone ?? 'No phone' }} | {{ $garage->address ?? 'No location set' }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @include('super_admin.partials._badge', ['tone' => ($garage->status ?? 'active') === 'active' ? 'green' : 'orange', 'label' => str($garage->status ?? 'active')->headline()])
                    @include('super_admin.partials._badge', ['tone' => 'blue', 'label' => $garage->plan?->name ?? 'No plan'])
                    @include('super_admin.partials._badge', ['tone' => count($channel['warnings']) ? 'orange' : 'green', 'label' => $channel['status']])
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('super-admin.garages.users', $garage) }}" class="rounded-2xl border border-slate-400/30 px-4 py-2 text-sm font-black sa-muted">Users</a>
                <a href="{{ route('super-admin.garages.modules', $garage) }}" class="rounded-2xl border border-slate-400/30 px-4 py-2 text-sm font-black sa-muted">Modules</a>
                <a href="{{ route('super-admin.garages.channels', $garage) }}" class="rounded-2xl border border-slate-400/30 px-4 py-2 text-sm font-black sa-muted">Channels</a>
                @if(($garage->status ?? 'active') === 'suspended')
                    <form method="POST" action="{{ route('super-admin.garages.activate', $garage) }}">@csrf<button class="rounded-2xl bg-emerald-500 px-4 py-2 text-sm font-black text-white">Activate</button></form>
                @else
                    <form method="POST" action="{{ route('super-admin.garages.suspend', $garage) }}">@csrf<button class="rounded-2xl bg-red-500/15 px-4 py-2 text-sm font-black text-red-300 ring-1 ring-red-400/25">Suspend</button></form>
                @endif
            </div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-7">
        @foreach($metrics as $label => $value)
            <div class="rounded-3xl sa-card p-5">
                <p class="text-xs font-black uppercase tracking-wide sa-label">{{ str($label)->headline() }}</p>
                <p class="mt-3 text-3xl font-black text-white">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
        <section class="rounded-3xl sa-card p-5">
            <h2 class="text-lg font-black text-white">Basic Garage Details</h2>
            <p class="mt-1 text-sm font-semibold sa-muted">Safe platform fields only. Meta tokens are never shown here.</p>

            <form method="POST" action="{{ route('super-admin.garages.update', $garage) }}" class="mt-5 grid gap-3">
                @csrf
                @method('PATCH')
                <label class="grid gap-1 text-xs font-black uppercase tracking-wide sa-label">Name<input name="name" value="{{ old('name', $garage->name) }}" class="sa-input rounded-2xl px-3 py-2 text-sm normal-case tracking-normal"></label>
                <label class="grid gap-1 text-xs font-black uppercase tracking-wide sa-label">Email<input name="email" value="{{ old('email', $garage->email) }}" class="sa-input rounded-2xl px-3 py-2 text-sm normal-case tracking-normal"></label>
                <label class="grid gap-1 text-xs font-black uppercase tracking-wide sa-label">Phone<input name="phone" value="{{ old('phone', $garage->phone) }}" class="sa-input rounded-2xl px-3 py-2 text-sm normal-case tracking-normal"></label>
                <label class="grid gap-1 text-xs font-black uppercase tracking-wide sa-label">Location<input name="address" value="{{ old('address', $garage->address) }}" class="sa-input rounded-2xl px-3 py-2 text-sm normal-case tracking-normal"></label>
                <label class="grid gap-1 text-xs font-black uppercase tracking-wide sa-label">Status
                    <select name="status" class="sa-input rounded-2xl px-3 py-2 text-sm normal-case tracking-normal">
                        @foreach(['active' => 'Active', 'trial' => 'Trial', 'pilot' => 'Pilot', 'suspended' => 'Suspended', 'inactive' => 'Inactive'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $garage->status ?? 'active') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="mt-2 rounded-2xl bg-orange-500 px-4 py-3 text-sm font-black text-white">Save Garage Details</button>
            </form>
        </section>

        <section class="rounded-3xl sa-card p-5">
            <h2 class="text-lg font-black text-white">Risk / Health</h2>
            <p class="mt-1 text-sm font-semibold sa-muted">Current operational warnings for this garage.</p>
            <div class="mt-5 grid gap-3 md:grid-cols-2">
                @forelse($risks as $risk)
                    <div class="rounded-2xl border border-orange-400/25 bg-orange-500/10 p-4 text-sm font-bold text-orange-200">{{ $risk }}</div>
                @empty
                    <div class="rounded-2xl border border-emerald-400/25 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-200">No immediate risk signals found.</div>
                @endforelse
            </div>

            <h2 class="mt-8 text-lg font-black text-white">Channels</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <div class="rounded-2xl sa-soft p-4">
                    <p class="text-xs font-black uppercase tracking-wide sa-label">Provider</p>
                    <p class="mt-2 font-black text-white">{{ $channel['provider'] }}</p>
                </div>
                <div class="rounded-2xl sa-soft p-4">
                    <p class="text-xs font-black uppercase tracking-wide sa-label">Phone Number ID</p>
                    <p class="mt-2 font-black text-white">{{ $channel['phone_number_id'] }}</p>
                </div>
                <div class="rounded-2xl sa-soft p-4">
                    <p class="text-xs font-black uppercase tracking-wide sa-label">Last Inbound</p>
                    <p class="mt-2 font-black text-white">{{ $channel['last_inbound']?->created_at ? \Carbon\Carbon::parse($channel['last_inbound']->created_at)->format('d M Y, h:i A') : 'No inbound messages' }}</p>
                </div>
                <div class="rounded-2xl sa-soft p-4">
                    <p class="text-xs font-black uppercase tracking-wide sa-label">Last Outbound</p>
                    <p class="mt-2 font-black text-white">{{ $channel['last_outbound']?->created_at ? \Carbon\Carbon::parse($channel['last_outbound']->created_at)->format('d M Y, h:i A') : 'No outbound messages' }}</p>
                </div>
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="rounded-3xl sa-card p-5">
            <h2 class="text-lg font-black text-white">Users</h2>
            <div class="mt-4 space-y-3">
                @forelse($users as $user)
                    <div class="flex items-center justify-between gap-3 rounded-2xl sa-soft p-4">
                        <div>
                            <p class="font-black text-white">{{ $user->name }}</p>
                            <p class="text-xs font-bold sa-muted">{{ $user->email }}</p>
                        </div>
                        @include('super_admin.partials._badge', ['tone' => 'blue', 'label' => str($user->role ?? 'user')->headline()])
                    </div>
                @empty
                    <div class="rounded-2xl sa-soft p-5 text-sm font-bold sa-muted">No users found for this garage.</div>
                @endforelse
            </div>
        </section>

        <section class="rounded-3xl sa-card p-5">
            <h2 class="text-lg font-black text-white">Recent Activity</h2>
            <div class="mt-4 space-y-4">
                @foreach($activity as $label => $rows)
                    <div class="rounded-2xl sa-soft p-4">
                        <p class="text-xs font-black uppercase tracking-wide sa-label">{{ str($label)->headline() }}</p>
                        <div class="mt-3 space-y-2">
                            @forelse($rows as $row)
                                <div class="text-sm font-bold sa-muted">
                                    #{{ $row->id }} {{ $row->name ?? $row->job_code ?? $row->number ?? $row->body ?? 'Record' }}
                                </div>
                            @empty
                                <div class="text-sm font-bold sa-label">No records.</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
