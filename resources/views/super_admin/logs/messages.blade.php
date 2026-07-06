@extends('super_admin.layout')

@section('title', 'Platform Message Logs')

@section('super_admin_content')
    <div class="mb-6 rounded-3xl sa-card p-6">
        <p class="text-xs font-extrabold uppercase tracking-wide text-orange-300">Platform Logs</p>
        <h1 class="mt-2 text-3xl font-black text-white">Message Logs</h1>
        <p class="mt-2 text-sm font-semibold sa-muted">Cross-tenant WhatsApp/message visibility without exposing credentials or raw provider secrets.</p>
        <form method="GET" class="mt-5 grid gap-2 md:grid-cols-3 xl:grid-cols-7">
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
            <select name="company_id" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
                <option value="">All garages</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected(($filters['company_id'] ?? '') == $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
            <select name="direction" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
                <option value="">Any direction</option>
                <option value="in" @selected(($filters['direction'] ?? '') === 'in')>Inbound</option>
                <option value="out" @selected(($filters['direction'] ?? '') === 'out')>Outbound</option>
            </select>
            <input name="provider_status" value="{{ $filters['provider_status'] ?? '' }}" placeholder="Provider status" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
            <input name="phone" value="{{ $filters['phone'] ?? '' }}" placeholder="Phone" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
            <button class="rounded-2xl bg-orange-500 px-4 py-2 text-sm font-black text-white">Filter</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-3xl sa-card">
        <div class="overflow-x-auto">
            <table class="min-w-full sa-table">
                <thead><tr><th class="px-5 py-4 text-left">Date</th><th class="px-5 py-4 text-left">Garage</th><th class="px-5 py-4 text-left">Phone</th><th class="px-5 py-4 text-left">Direction</th><th class="px-5 py-4 text-left">Status</th><th class="px-5 py-4 text-left">Preview</th><th class="px-5 py-4 text-left">Related Lead</th></tr></thead>
                <tbody>
                    @forelse($messages as $message)
                        <tr>
                            <td class="px-5 py-4 text-sm font-bold">{{ \Carbon\Carbon::parse($message->created_at)->format('d M Y, h:i A') }}</td>
                            <td class="px-5 py-4 text-sm font-black text-white">{{ $message->company_name ?? 'Unknown garage' }}</td>
                            <td class="px-5 py-4 text-sm font-bold sa-muted">{{ $message->from_number ?? $message->to_number ?? 'No phone' }}</td>
                            <td class="px-5 py-4">@include('super_admin.partials._badge', ['tone' => ($message->direction ?? '') === 'in' ? 'green' : 'blue', 'label' => strtoupper($message->direction ?? '-')])</td>
                            <td class="px-5 py-4 text-sm font-bold sa-muted">{{ $message->provider_status ?? 'No status' }}</td>
                            <td class="max-w-md px-5 py-4 text-sm font-bold sa-muted">{{ str($message->body ?? 'No body')->limit(120) }}</td>
                            <td class="px-5 py-4 text-sm font-bold sa-muted">{{ $message->lead_name ?? ($message->lead_id ? '#'.$message->lead_id : 'Not linked') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-sm font-bold sa-muted">No message logs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-700/50 px-5 py-4">{{ $messages->links() }}</div>
    </div>
@endsection
