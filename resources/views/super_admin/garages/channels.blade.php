@extends('super_admin.layout')

@section('title', 'Garage Channels')

@section('super_admin_content')
    <div class="mb-6 rounded-3xl sa-card p-6">
        <p class="text-xs font-extrabold uppercase tracking-wide text-orange-300">WhatsApp / Meta Channel Health</p>
        <h1 class="mt-2 text-3xl font-black text-white">{{ $garage->name }}</h1>
        <p class="mt-2 text-sm font-semibold sa-muted">Operational channel status only. Tokens, verify secrets, and raw credentials are never displayed.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl sa-card p-5"><p class="text-xs font-black uppercase tracking-wide sa-label">Provider</p><p class="mt-3 text-xl font-black text-white">{{ $channel['provider'] }}</p></div>
        <div class="rounded-3xl sa-card p-5"><p class="text-xs font-black uppercase tracking-wide sa-label">Phone Number ID</p><p class="mt-3 text-xl font-black text-white">{{ $channel['phone_number_id'] }}</p></div>
        <div class="rounded-3xl sa-card p-5"><p class="text-xs font-black uppercase tracking-wide sa-label">WABA ID</p><p class="mt-3 text-xl font-black text-white">{{ $channel['waba_id'] }}</p></div>
        <div class="rounded-3xl sa-card p-5"><p class="text-xs font-black uppercase tracking-wide sa-label">Provider Status</p><p class="mt-3 text-xl font-black text-white">{{ $channel['status'] }}</p></div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
        <section class="rounded-3xl sa-card p-5">
            <h2 class="text-lg font-black text-white">Warnings</h2>
            <div class="mt-4 space-y-3">
                @forelse($channel['warnings'] as $warning)
                    <div class="rounded-2xl border border-orange-400/25 bg-orange-500/10 p-4 text-sm font-bold text-orange-200">{{ $warning }}</div>
                @empty
                    <div class="rounded-2xl border border-emerald-400/25 bg-emerald-500/10 p-4 text-sm font-bold text-emerald-200">No channel warnings found.</div>
                @endforelse
            </div>
        </section>

        <section class="rounded-3xl sa-card p-5">
            <h2 class="text-lg font-black text-white">Recent Message Logs</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full sa-table">
                    <thead><tr><th class="py-3 text-left">Time</th><th class="py-3 text-left">Direction</th><th class="py-3 text-left">Phone</th><th class="py-3 text-left">Status</th></tr></thead>
                    <tbody>
                        @forelse($messages as $message)
                            <tr>
                                <td class="py-3 text-sm font-bold">{{ \Carbon\Carbon::parse($message->created_at)->format('d M Y, h:i A') }}</td>
                                <td class="py-3 text-sm font-bold">{{ strtoupper($message->direction ?? '-') }}</td>
                                <td class="py-3 text-sm font-bold">{{ $message->from_number ?? $message->to_number ?? 'No phone' }}</td>
                                <td class="py-3 text-sm font-bold">{{ $message->provider_status ?? 'No status' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-6 text-center text-sm font-bold sa-muted">No message records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
