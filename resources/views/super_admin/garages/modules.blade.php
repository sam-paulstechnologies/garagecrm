@extends('super_admin.layout')

@section('title', 'Garage Modules')

@section('super_admin_content')
    <div class="mb-6 rounded-3xl sa-card p-6">
        <p class="text-xs font-extrabold uppercase tracking-wide text-orange-300">Module Access</p>
        <h1 class="mt-2 text-3xl font-black text-white">{{ $garage->name }}</h1>
        <p class="mt-2 text-sm font-semibold sa-muted">This creates the module-control foundation. Full menu/page enforcement can be expanded module by module.</p>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach($modules as $module)
            <form method="POST" action="{{ route('super-admin.garages.modules.update', $garage) }}" class="rounded-3xl sa-card p-5">
                @csrf
                @method('PATCH')
                <input type="hidden" name="module_key" value="{{ $module->module_key }}">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-lg font-black text-white">{{ $module->name }}</p>
                        <p class="mt-1 text-sm font-semibold sa-muted">{{ $module->description }}</p>
                    </div>
                    @include('super_admin.partials._badge', ['tone' => $module->enabled ? 'green' : 'red', 'label' => $module->enabled ? 'Enabled' : 'Disabled'])
                </div>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <label class="flex items-center gap-2 rounded-2xl sa-soft p-3 text-sm font-black text-white">
                        <input type="checkbox" name="enabled" value="1" @checked($module->enabled) class="rounded border-slate-400 bg-transparent text-orange-500">
                        Enabled
                    </label>
                    <label class="flex items-center gap-2 rounded-2xl sa-soft p-3 text-sm font-black text-white">
                        <input type="checkbox" name="locked" value="1" @checked($module->locked) class="rounded border-slate-400 bg-transparent text-orange-500">
                        Locked
                    </label>
                </div>
                <textarea name="notes" rows="2" placeholder="Internal notes" class="sa-input mt-3 w-full rounded-2xl px-3 py-2 text-sm">{{ $module->notes }}</textarea>
                <button class="mt-3 rounded-2xl bg-orange-500 px-4 py-2 text-sm font-black text-white">Save Module</button>
            </form>
        @endforeach
    </div>
@endsection
