@extends('super_admin.layout')

@section('super_admin_content')
    @include('super_admin.marketing.partials.nav')
    @include('super_admin.marketing.partials.hero', ['title' => 'Segments', 'subtitle' => 'Build controlled recipient groups from opted-in platform prospects.'])
    <form method="POST" action="{{ route('super-admin.marketing.segments.store') }}" class="sa-card mb-5 rounded-3xl p-5">
        @csrf
        <div class="grid gap-3 lg:grid-cols-3">
            <input name="name" placeholder="Segment name" class="sa-input rounded-2xl px-4 py-3 text-sm" required>
            <input name="description" placeholder="Description" class="sa-input rounded-2xl px-4 py-3 text-sm lg:col-span-2">
        </div>
        <select name="prospect_ids[]" multiple class="sa-input mt-3 min-h-40 w-full rounded-2xl px-4 py-3 text-sm">
            @foreach($prospects as $prospect)
                <option value="{{ $prospect->id }}">{{ $prospect->business_name ?? $prospect->contact_name }} · {{ $prospect->whatsapp_number }}</option>
            @endforeach
        </select>
        <button class="mt-3 rounded-2xl bg-emerald-500 px-5 py-3 text-sm font-black text-white">Create Segment</button>
    </form>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($segments as $segment)
            <a href="{{ route('super-admin.marketing.segments.show', $segment) }}" class="sa-card rounded-3xl p-5">
                <h2 class="font-black">{{ $segment->name }}</h2>
                <p class="sa-muted mt-2 text-sm">{{ $segment->description ?: 'No description.' }}</p>
                <p class="mt-4 text-sm font-bold">{{ $segment->prospects_count }} prospects</p>
            </a>
        @empty
            <div class="sa-card rounded-3xl p-8 text-center sa-muted md:col-span-2 xl:col-span-3">No segments yet.</div>
        @endforelse
    </div>
@endsection
