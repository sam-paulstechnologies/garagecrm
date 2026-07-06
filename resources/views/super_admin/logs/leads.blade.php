@extends('super_admin.layout')

@section('title', 'Platform Lead Intake Logs')

@section('super_admin_content')
    <div class="mb-6 rounded-3xl sa-card p-6">
        <p class="text-xs font-extrabold uppercase tracking-wide text-orange-300">Platform Logs</p>
        <h1 class="mt-2 text-3xl font-black text-white">Lead Intake Logs</h1>
        <p class="mt-2 text-sm font-semibold sa-muted">Cross-tenant lead intake and source visibility.</p>
        <form method="GET" class="mt-5 grid gap-2 md:grid-cols-3 xl:grid-cols-7">
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
            <select name="company_id" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
                <option value="">All garages</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected(($filters['company_id'] ?? '') == $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
            <input name="source" value="{{ $filters['source'] ?? '' }}" placeholder="Source" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
            <input name="status" value="{{ $filters['status'] ?? '' }}" placeholder="Status" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
            <input name="phone" value="{{ $filters['phone'] ?? '' }}" placeholder="Phone" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
            <button class="rounded-2xl bg-orange-500 px-4 py-2 text-sm font-black text-white">Filter</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-3xl sa-card">
        <div class="overflow-x-auto">
            <table class="min-w-full sa-table">
                <thead><tr><th class="px-5 py-4 text-left">Date</th><th class="px-5 py-4 text-left">Garage</th><th class="px-5 py-4 text-left">Lead</th><th class="px-5 py-4 text-left">Source</th><th class="px-5 py-4 text-left">Status</th><th class="px-5 py-4 text-left">Assigned</th><th class="px-5 py-4 text-left">External</th></tr></thead>
                <tbody>
                    @forelse($leads as $lead)
                        <tr>
                            <td class="px-5 py-4 text-sm font-bold">{{ \Carbon\Carbon::parse($lead->created_at)->format('d M Y, h:i A') }}</td>
                            <td class="px-5 py-4 text-sm font-black text-white">{{ $lead->company_name ?? 'Unknown garage' }}</td>
                            <td class="px-5 py-4">
                                <div class="font-black text-white">{{ $lead->name ?? 'Unnamed lead' }}</div>
                                <div class="mt-1 text-xs font-bold sa-muted">{{ $lead->phone ?? 'No phone' }}</div>
                            </td>
                            <td class="px-5 py-4 text-sm font-bold sa-muted">{{ $lead->source ?? 'Not set' }}</td>
                            <td class="px-5 py-4">@include('super_admin.partials._badge', ['tone' => 'blue', 'label' => str($lead->status ?? 'new')->headline()])</td>
                            <td class="px-5 py-4 text-sm font-bold sa-muted">{{ $lead->assigned_user_name ?? 'Unassigned' }}</td>
                            <td class="px-5 py-4 text-sm font-bold sa-muted">{{ $lead->external_source ?? $lead->external_id ?? 'No external source' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-sm font-bold sa-muted">No lead logs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-700/50 px-5 py-4">{{ $leads->links() }}</div>
    </div>
@endsection
