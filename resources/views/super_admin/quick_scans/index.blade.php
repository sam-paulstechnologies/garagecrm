@extends('super_admin.layout')

@section('title', 'Quick Scans')

@section('super_admin_content')
    <section class="rounded-3xl sa-card p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-orange-300">Sales discovery</p>
                <h1 class="mt-2 text-3xl font-black text-white">SayaraForce Quick Scans</h1>
                <p class="mt-3 max-w-3xl text-sm font-semibold sa-muted">Temporary, observational WhatsApp history workspaces. No tenant, subscription, CRM record, campaign or customer message is created by a scan.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('super-admin.quick-scans.create') }}" class="rounded-xl bg-orange-500 px-5 py-3 text-sm font-black text-white">New Quick Scan</a>
                @if(app()->environment(['local', 'testing', 'staging']))
                    <form method="POST" action="{{ route('super-admin.quick-scans.synthetic') }}">@csrf
                        <button class="rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-black text-white">Build 500-contact synthetic demo</button>
                    </form>
                @endif
            </div>
        </div>
    </section>

    <section class="mt-6 overflow-hidden rounded-3xl sa-card">
        <div class="overflow-x-auto">
            <table class="sa-table min-w-full text-left text-sm">
                <thead><tr><th class="px-5 py-4">Garage</th><th class="px-5 py-4">Status</th><th class="px-5 py-4">Analysed</th><th class="px-5 py-4">Retention</th><th class="px-5 py-4">Outcome</th><th class="px-5 py-4"></th></tr></thead>
                <tbody>
                @forelse($scans as $scan)
                    @php($metrics = (array) $scan->report_metrics)
                    <tr>
                        <td class="px-5 py-4"><div class="font-black text-white">{{ $scan->garage_name }}</div><div class="mt-1 text-xs sa-muted">{{ $scan->created_at->format('d M Y, H:i') }}</div></td>
                        <td class="px-5 py-4"><span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-black uppercase tracking-wide">{{ str($scan->status)->replace('_', ' ') }}</span></td>
                        <td class="px-5 py-4 font-bold">{{ number_format((int) ($metrics['conversations_analysed'] ?? $scan->contacts_analysed)) }}</td>
                        <td class="px-5 py-4 font-bold">{{ number_format((int) ($metrics['retention_customers'] ?? 0)) }}</td>
                        <td class="px-5 py-4">{{ str($scan->outcome ?: 'pending')->replace('_', ' ')->title() }}</td>
                        <td class="px-5 py-4 text-right"><a href="{{ route('super-admin.quick-scans.show', $scan) }}" class="font-black text-orange-300">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center sa-muted">No Quick Scans have been created.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-white/10 p-4">{{ $scans->links() }}</div>
    </section>
@endsection
