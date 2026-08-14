@extends('super_admin.layout')

@section('title', 'New Quick Scan')

@section('super_admin_content')
    <section class="mx-auto max-w-4xl rounded-3xl sa-card p-6 sm:p-8">
        <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-orange-300">Platform-only creation</p>
        <h1 class="mt-2 text-3xl font-black text-white">Create a temporary Quick Scan</h1>
        <p class="mt-3 text-sm font-semibold sa-muted">This creates a prospect workspace and secure expiring link only. It does not create a garage tenant or send any communication.</p>
        <form method="POST" action="{{ route('super-admin.quick-scans.store') }}" class="mt-7 grid gap-5 sm:grid-cols-2">@csrf
            <label class="sm:col-span-2 text-sm font-bold text-white">Garage name<input name="garage_name" value="{{ old('garage_name') }}" required maxlength="191" class="sa-input mt-2 w-full rounded-xl px-4 py-3"></label>
            <label class="text-sm font-bold text-white">Decision maker<input name="garage_contact_name" value="{{ old('garage_contact_name') }}" required maxlength="191" class="sa-input mt-2 w-full rounded-xl px-4 py-3"></label>
            <label class="text-sm font-bold text-white">Contact phone<input name="garage_phone" value="{{ old('garage_phone') }}" required maxlength="30" class="sa-input mt-2 w-full rounded-xl px-4 py-3"></label>
            <label class="text-sm font-bold text-white">Email (optional)<input name="garage_email" type="email" value="{{ old('garage_email') }}" maxlength="191" class="sa-input mt-2 w-full rounded-xl px-4 py-3"></label>
            <label class="text-sm font-bold text-white">Source<input name="source" value="{{ old('source', 'garage_visit') }}" maxlength="80" class="sa-input mt-2 w-full rounded-xl px-4 py-3"></label>
            <label class="text-sm font-bold text-white">Follow-up date<input name="follow_up_at" type="date" value="{{ old('follow_up_at') }}" class="sa-input mt-2 w-full rounded-xl px-4 py-3"></label>
            <label class="sm:col-span-2 text-sm font-bold text-white">Internal garage-level notes<textarea name="notes" rows="4" maxlength="2000" class="sa-input mt-2 w-full rounded-xl px-4 py-3">{{ old('notes') }}</textarea></label>
            <div class="sm:col-span-2 flex flex-wrap gap-3"><button class="rounded-xl bg-orange-500 px-6 py-3 text-sm font-black text-white">Create secure scan</button><a href="{{ route('super-admin.quick-scans.index') }}" class="rounded-xl border border-white/15 px-6 py-3 text-sm font-black text-white">Cancel</a></div>
        </form>
    </section>
@endsection
