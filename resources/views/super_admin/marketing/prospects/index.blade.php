@extends('super_admin.layout')

@section('super_admin_content')
    @include('super_admin.marketing.partials.nav')
    @include('super_admin.marketing.partials.hero', [
        'title' => 'Prospect Manager',
        'subtitle' => 'Search, segment, qualify, and export platform prospects without writing to garage lead tables.',
        'action' => new Illuminate\Support\HtmlString('<a href="'.route('super-admin.marketing.prospects.create').'" class="rounded-2xl bg-emerald-500 px-5 py-3 text-sm font-black text-white">New Prospect</a>')
    ])

    <div class="mb-5 flex flex-wrap gap-2">
        @foreach($buckets as $status => $total)
            <a href="{{ route('super-admin.marketing.prospects.index', ['status' => $status]) }}" class="sa-soft rounded-2xl px-4 py-2 text-xs font-black">{{ str($status)->headline() }} · {{ $total }}</a>
        @endforeach
    </div>

    <form method="GET" class="sa-card mb-5 grid gap-3 rounded-3xl p-5 md:grid-cols-4">
        <input name="search" value="{{ request('search') }}" placeholder="Search business, contact, phone" class="sa-input rounded-2xl px-4 py-3 text-sm md:col-span-2">
        <select name="status" class="sa-input rounded-2xl px-4 py-3 text-sm">
            <option value="">All statuses</option>
            @foreach($statuses as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->headline() }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button class="rounded-2xl bg-orange-500 px-5 py-3 text-sm font-black text-white">Filter</button>
            <a href="{{ route('super-admin.marketing.prospects.export') }}" class="rounded-2xl bg-white/10 px-5 py-3 text-sm font-black text-white">Export</a>
        </div>
    </form>

    <div class="sa-card overflow-hidden rounded-3xl">
        <table class="sa-table w-full text-left text-sm">
            <thead><tr><th class="p-4">Prospect</th><th>Phone</th><th>Status</th><th>Consent</th><th>Score</th><th></th></tr></thead>
            <tbody>
                @forelse($prospects as $prospect)
                    <tr>
                        <td class="p-4"><span class="font-bold">{{ $prospect->business_name ?? 'Unnamed business' }}</span><span class="sa-label block">{{ $prospect->contact_name ?? 'No contact' }}</span></td>
                        <td>{{ $prospect->whatsapp_number }}</td>
                        <td>{{ str($prospect->status)->headline() }}</td>
                        <td>{{ str($prospect->consent_status)->headline() }}</td>
                        <td>{{ $prospect->lead_score }}/100</td>
                        <td><a class="font-bold text-emerald-300" href="{{ route('super-admin.marketing.prospects.show', $prospect) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-8 text-center sa-muted">No prospects match this view.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $prospects->links() }}</div>
@endsection
