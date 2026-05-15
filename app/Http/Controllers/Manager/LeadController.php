<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Client\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    protected array $leadStatuses = [
        'New',
        'Attempting Contact',
        'Contacted',
        'Qualified',
        'Disqualified',
        'On Hold',
        'Assigned',
    ];

    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(! $companyId, 403);

        return $companyId;
    }

    public function index(Request $request)
    {
        $companyId = $this->companyId();

        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));
        $source = trim((string) $request->get('source', ''));

        $leads = Lead::query()
            ->where('company_id', $companyId)
            ->when(Schema::hasColumn('leads', 'is_active'), function ($query) {
                $query->where('is_active', 1);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    foreach (['name', 'full_name', 'customer_name', 'phone', 'mobile', 'email', 'vehicle_make', 'vehicle_model', 'notes'] as $column) {
                        if (Schema::hasColumn('leads', $column)) {
                            $sub->orWhere($column, 'like', '%' . $q . '%');
                        }
                    }
                });
            })
            ->when($status !== '' && Schema::hasColumn('leads', 'status'), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($source !== '' && Schema::hasColumn('leads', 'source'), function ($query) use ($source) {
                $query->where('source', $source);
            })
            ->when(Schema::hasColumn('leads', 'status'), function ($query) {
                $query->whereNotIn('status', [
                    'Disqualified',
                    'Closed',
                    'Converted',
                    'Converted to Opportunity',
                ]);
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $sources = $this->leadSources($companyId);
        $managers = $this->assignableUsers($companyId);

        return view('manager.leads.index', compact(
            'leads',
            'sources',
            'managers',
            'q',
            'status',
            'source'
        ));
    }

    public function show(Lead $lead)
    {
        $this->authorizeLead($lead);

        $managers = $this->assignableUsers($this->companyId());

        return view('manager.leads.show', compact('lead', 'managers'));
    }

    public function updateStatus(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in($this->leadStatuses)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($lead, $validated) {
            if (Schema::hasColumn('leads', 'status')) {
                $lead->status = $validated['status'];
            }

            if (! empty($validated['notes'])) {
                $this->appendNotes($lead, $validated['notes']);
            }

            if (Schema::hasColumn('leads', 'last_contacted_at') && in_array($validated['status'], ['Attempting Contact', 'Contacted'], true)) {
                $lead->last_contacted_at = now();
            }

            if (Schema::hasColumn('leads', 'qualified_at') && $validated['status'] === 'Qualified') {
                $lead->qualified_at = now();
            }

            if (Schema::hasColumn('leads', 'disqualified_at') && $validated['status'] === 'Disqualified') {
                $lead->disqualified_at = now();
            }

            $lead->save();
        });

        return back()->with('success', 'Lead status updated successfully.');
    }

    public function assign(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead);

        $validated = $request->validate([
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ]);

        $assignee = User::query()
            ->where('company_id', $this->companyId())
            ->findOrFail($validated['assigned_to']);

        DB::transaction(function () use ($lead, $assignee) {
            $assignedColumn = $this->firstExistingColumn('leads', [
                'assigned_to',
                'assigned_to_id',
                'assigned_user_id',
                'manager_id',
                'user_id',
            ]);

            if ($assignedColumn) {
                $lead->{$assignedColumn} = $assignee->id;
            }

            if (Schema::hasColumn('leads', 'status') && in_array((string) $lead->status, ['New', 'Attempting Contact'], true)) {
                $lead->status = 'Assigned';
            }

            if (Schema::hasColumn('leads', 'assigned_at')) {
                $lead->assigned_at = now();
            }

            $lead->save();
        });

        return back()->with('success', 'Lead assigned successfully.');
    }

    public function updateFollowUp(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead);

        $validated = $request->validate([
            'follow_up_date' => ['nullable', 'date'],
            'follow_up_required' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($lead, $validated) {
            if (Schema::hasColumn('leads', 'follow_up_date')) {
                $lead->follow_up_date = $validated['follow_up_date'] ?? null;
            }

            if (Schema::hasColumn('leads', 'follow_up_required')) {
                $lead->follow_up_required = (bool) ($validated['follow_up_required'] ?? false);
            }

            if (! empty($validated['notes'])) {
                $this->appendNotes($lead, $validated['notes']);
            }

            $lead->save();
        });

        return back()->with('success', 'Lead follow-up updated successfully.');
    }

    protected function authorizeLead(Lead $lead): void
    {
        abort_if((int) $lead->company_id !== $this->companyId(), 403);
    }

    protected function appendNotes(Lead $lead, string $note): void
    {
        $note = trim($note);

        if ($note === '') {
            return;
        }

        $noteColumn = $this->firstExistingColumn('leads', [
            'manager_notes',
            'internal_notes',
            'notes',
        ]);

        if (! $noteColumn) {
            return;
        }

        $existing = trim((string) ($lead->{$noteColumn} ?? ''));

        $entry = '[' . now()->format('Y-m-d H:i') . '] '
            . auth()->user()?->name
            . ': '
            . $note;

        $lead->{$noteColumn} = $existing
            ? $existing . PHP_EOL . PHP_EOL . $entry
            : $entry;
    }

    protected function leadSources(int $companyId)
    {
        if (! Schema::hasColumn('leads', 'source')) {
            return collect();
        }

        return Lead::query()
            ->where('company_id', $companyId)
            ->whereNotNull('source')
            ->select('source')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');
    }

    protected function assignableUsers(int $companyId)
    {
        return User::query()
            ->where('company_id', $companyId)
            ->when(Schema::hasColumn('users', 'is_active'), function ($query) {
                $query->where('is_active', 1);
            })
            ->when(Schema::hasColumn('users', 'status'), function ($query) {
                $query->whereIn('status', ['active', 'Active', 1]);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    protected function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }
}