@extends('super_admin.layout')

@section('title', 'Super Admin Audit')

@section('super_admin_content')
    <div class="mb-6 rounded-3xl sa-card p-6">
        <p class="text-xs font-extrabold uppercase tracking-wide text-orange-300">Audit</p>
        <h1 class="mt-2 text-3xl font-black text-white">Super Admin Activity Audit</h1>
        <p class="mt-2 text-sm font-semibold sa-muted">Platform-owner actions with actor, target, company, IP, and timestamp.</p>
        <form method="GET" class="mt-5 grid gap-2 md:grid-cols-3 xl:grid-cols-6">
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
            <select name="company_id" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
                <option value="">All garages</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected(($filters['company_id'] ?? '') == $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
            <select name="super_admin_user_id" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
                <option value="">All super admins</option>
                @foreach($superAdmins as $user)
                    <option value="{{ $user->id }}" @selected(($filters['super_admin_user_id'] ?? '') == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
            <input name="action" value="{{ $filters['action'] ?? '' }}" placeholder="Action" class="sa-input rounded-2xl px-3 py-2 text-sm font-bold">
            <button class="rounded-2xl bg-orange-500 px-4 py-2 text-sm font-black text-white">Filter</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-3xl sa-card">
        <div class="overflow-x-auto">
            <table class="min-w-full sa-table">
                <thead><tr><th class="px-5 py-4 text-left">Date</th><th class="px-5 py-4 text-left">Actor</th><th class="px-5 py-4 text-left">Garage</th><th class="px-5 py-4 text-left">Action</th><th class="px-5 py-4 text-left">Target</th><th class="px-5 py-4 text-left">IP</th></tr></thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="px-5 py-4 text-sm font-bold">{{ $log->created_at?->format('d M Y, h:i A') }}</td>
                            <td class="px-5 py-4 text-sm font-black text-white">{{ $log->superAdmin?->name ?? 'Unknown' }}</td>
                            <td class="px-5 py-4 text-sm font-bold sa-muted">{{ $log->company?->name ?? 'Platform' }}</td>
                            <td class="px-5 py-4">@include('super_admin.partials._badge', ['tone' => 'blue', 'label' => $log->action])</td>
                            <td class="px-5 py-4 text-sm font-bold sa-muted">{{ $log->target_type ? class_basename($log->target_type).' #'.$log->target_id : 'Platform' }}</td>
                            <td class="px-5 py-4 text-sm font-bold sa-muted">{{ $log->ip_address ?? 'No IP' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-sm font-bold sa-muted">No Super Admin audit logs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-700/50 px-5 py-4">{{ $logs->links() }}</div>
    </div>
@endsection
