<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\SuperAdminAuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditController extends SuperAdminController
{
    public function index(Request $request)
    {
        $query = SuperAdminAuditLog::query()
            ->with(['superAdmin', 'company'])
            ->latest();

        if ($request->filled('company_id')) {
            $query->where('company_id', (int) $request->input('company_id'));
        }

        if ($request->filled('super_admin_user_id')) {
            $query->where('super_admin_user_id', (int) $request->input('super_admin_user_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        return view('super_admin.audit.index', [
            'logs' => $query->paginate(25)->withQueryString(),
            'companies' => $this->companiesForFilter(),
            'superAdmins' => User::query()->where('role', 'super_admin')->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['date_from', 'date_to', 'super_admin_user_id', 'action', 'company_id']),
        ]);
    }
}
