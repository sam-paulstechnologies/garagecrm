<?php

namespace App\Http\Controllers\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LogController extends SuperAdminController
{
    public function messages(Request $request)
    {
        $query = DB::table('message_logs')
            ->leftJoin('companies', 'companies.id', '=', 'message_logs.company_id')
            ->when(Schema::hasTable('leads'), function ($query) {
                $query->leftJoin('leads', 'leads.id', '=', 'message_logs.lead_id');
            });

        if ($request->filled('company_id')) {
            $query->where('message_logs.company_id', (int) $request->input('company_id'));
        }

        if ($request->filled('direction') && Schema::hasColumn('message_logs', 'direction')) {
            $query->where('message_logs.direction', $request->input('direction'));
        }

        if ($request->filled('provider_status') && Schema::hasColumn('message_logs', 'provider_status')) {
            $query->where('message_logs.provider_status', $request->input('provider_status'));
        }

        if ($request->filled('phone')) {
            $phone = trim((string) $request->input('phone'));
            $query->where(function ($q) use ($phone) {
                if (Schema::hasColumn('message_logs', 'from_number')) {
                    $q->orWhere('message_logs.from_number', 'like', "%{$phone}%");
                }

                if (Schema::hasColumn('message_logs', 'to_number')) {
                    $q->orWhere('message_logs.to_number', 'like', "%{$phone}%");
                }
            });
        }

        if ($request->filled('search') && Schema::hasColumn('message_logs', 'body')) {
            $search = trim((string) $request->input('search'));
            $query->where('message_logs.body', 'like', "%{$search}%");
        }

        if ($request->filled('date_from')) {
            $query->whereDate('message_logs.created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('message_logs.created_at', '<=', $request->date('date_to'));
        }

        $select = [
            'message_logs.id',
            'message_logs.company_id',
            'companies.name as company_name',
            'message_logs.created_at',
            'message_logs.direction',
            'message_logs.channel',
            'message_logs.from_number',
            'message_logs.to_number',
            'message_logs.provider_status',
            'message_logs.body',
        ];

        if (Schema::hasTable('leads')) {
            $select[] = 'leads.id as lead_id';
            $select[] = 'leads.name as lead_name';
        }

        $messages = $query
            ->select($select)
            ->latest('message_logs.created_at')
            ->paginate(25)
            ->withQueryString();

        return view('super_admin.logs.messages', [
            'messages' => $messages,
            'companies' => $this->companiesForFilter(),
            'filters' => $request->only(['date_from', 'date_to', 'company_id', 'provider_status', 'phone', 'direction', 'search']),
        ]);
    }

    public function leads(Request $request)
    {
        $query = DB::table('leads')
            ->leftJoin('companies', 'companies.id', '=', 'leads.company_id');

        if (Schema::hasTable('users') && Schema::hasColumn('leads', 'assigned_to')) {
            $query->leftJoin('users', 'users.id', '=', 'leads.assigned_to');
        }

        if ($request->filled('company_id')) {
            $query->where('leads.company_id', (int) $request->input('company_id'));
        }

        if ($request->filled('status') && Schema::hasColumn('leads', 'status')) {
            $query->where('leads.status', $request->input('status'));
        }

        if ($request->filled('source') && Schema::hasColumn('leads', 'source')) {
            $query->where('leads.source', 'like', '%'.trim((string) $request->input('source')).'%');
        }

        if ($request->filled('phone') && Schema::hasColumn('leads', 'phone')) {
            $query->where('leads.phone', 'like', '%'.trim((string) $request->input('phone')).'%');
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                if (Schema::hasColumn('leads', 'name')) {
                    $q->orWhere('leads.name', 'like', "%{$search}%");
                }

                if (Schema::hasColumn('leads', 'phone')) {
                    $q->orWhere('leads.phone', 'like', "%{$search}%");
                }
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('leads.created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('leads.created_at', '<=', $request->date('date_to'));
        }

        $select = [
            'leads.id',
            'leads.company_id',
            'companies.name as company_name',
            'leads.created_at',
            'leads.name',
            'leads.phone',
            'leads.source',
            'leads.status',
        ];

        foreach (['external_source', 'external_id'] as $column) {
            if (Schema::hasColumn('leads', $column)) {
                $select[] = 'leads.'.$column;
            }
        }

        if (Schema::hasTable('users') && Schema::hasColumn('leads', 'assigned_to')) {
            $select[] = 'users.name as assigned_user_name';
        }

        $leads = $query
            ->select($select)
            ->latest('leads.created_at')
            ->paginate(25)
            ->withQueryString();

        return view('super_admin.logs.leads', [
            'leads' => $leads,
            'companies' => $this->companiesForFilter(),
            'filters' => $request->only(['date_from', 'date_to', 'company_id', 'source', 'status', 'phone', 'search']),
        ]);
    }
}
