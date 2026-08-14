@extends('super_admin.layout')

@section('title', 'Quick Scan')

@section('super_admin_content')
    @php($metrics = (array) $scan->report_metrics)
    <section class="rounded-3xl sa-card p-6 sm:p-8">
        <div class="grid gap-7 xl:grid-cols-[1fr_340px]">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-orange-300">Quick Scan workspace</p>
                <h1 class="mt-2 text-3xl font-black text-white">{{ $scan->garage_name }}</h1>
                <div class="mt-4 flex flex-wrap gap-2"><span class="rounded-full bg-white/10 px-3 py-1 text-xs font-black uppercase">{{ str($scan->status)->replace('_', ' ') }}</span><span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold">Expires {{ $scan->link_expires_at?->diffForHumans() }}</span></div>
                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach([['Discovered',$scan->contacts_discovered],['Analysed',$scan->contacts_analysed],['Retention',$metrics['retention_customers'] ?? 0],['Potential missed',$metrics['potential_missed'] ?? 0]] as [$label,$value])
                        <div class="sa-soft rounded-2xl p-4"><div class="text-xs font-black uppercase sa-label">{{ $label }}</div><div class="mt-2 text-3xl font-black text-white">{{ number_format((int) $value) }}</div></div>
                    @endforeach
                </div>
                <div class="mt-6 rounded-2xl border border-white/10 bg-black/10 p-4">
                    <label class="text-xs font-black uppercase sa-label">Secure garage link</label>
                    <div class="mt-2 flex gap-2"><input id="scan-link" readonly value="{{ $scanUrl }}" class="sa-input min-w-0 flex-1 rounded-xl px-3 py-2 text-xs"><button type="button" onclick="navigator.clipboard.writeText(document.getElementById('scan-link').value)" class="rounded-xl bg-orange-500 px-4 py-2 text-xs font-black text-white">Copy link</button></div>
                </div>
            </div>
            <aside class="rounded-3xl bg-white p-5 text-center text-slate-900"><img src="{{ route('super-admin.quick-scans.qr', $scan) }}" alt="Secure Quick Scan QR code" class="mx-auto w-full max-w-[280px]"><p class="mt-3 text-xs font-bold text-slate-500">QR contains only the secure expiring scan URL.</p></aside>
        </div>
    </section>

    <section class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl sa-card p-6">
            <h2 class="text-xl font-black text-white">Sales follow-up</h2>
            <form method="POST" action="{{ route('super-admin.quick-scans.follow-up', $scan) }}" class="mt-4 grid gap-4 sm:grid-cols-2">@csrf @method('PATCH')
                <input type="date" name="follow_up_at" value="{{ $scan->follow_up_at?->format('Y-m-d') }}" class="sa-input rounded-xl px-4 py-3">
                <select name="outcome" class="sa-input rounded-xl px-4 py-3"><option value="">Pending</option>@foreach(['follow_up','start_sayaraforce','converted','not_now'] as $outcome)<option value="{{ $outcome }}" @selected($scan->outcome === $outcome)>{{ str($outcome)->replace('_',' ')->title() }}</option>@endforeach</select>
                <button class="sm:col-span-2 rounded-xl border border-white/15 px-5 py-3 text-sm font-black text-white">Save follow-up</button>
            </form>
        </div>
        <div class="rounded-3xl sa-card p-6">
            <h2 class="text-xl font-black text-white">Privacy controls</h2>
            <p class="mt-2 text-sm font-semibold sa-muted">Revoking stops public access. Purging permanently removes customer-level scan data and retains only garage-level prospect details and aggregates.</p>
            <div class="mt-4 flex flex-wrap gap-3">
                <form method="POST" action="{{ route('super-admin.quick-scans.regenerate', $scan) }}">@csrf<button class="rounded-xl border border-white/15 px-4 py-2 text-sm font-black text-white">Regenerate link</button></form>
                <form method="POST" action="{{ route('super-admin.quick-scans.revoke', $scan) }}">@csrf<button class="rounded-xl border border-amber-400/30 bg-amber-500/10 px-4 py-2 text-sm font-black text-amber-200">Revoke</button></form>
                @if($scan->status !== 'purged')<form method="POST" action="{{ route('super-admin.quick-scans.purge', $scan) }}" onsubmit="return confirm('Permanently purge customer-level Quick Scan data?');">@csrf @method('DELETE')<button class="rounded-xl border border-red-400/30 bg-red-500/10 px-4 py-2 text-sm font-black text-red-200">Purge customer data</button></form>@endif
            </div>
        </div>
    </section>
@endsection
