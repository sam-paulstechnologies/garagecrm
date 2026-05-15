<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Client\Opportunity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class OpportunityController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | IMPORTANT
    |--------------------------------------------------------------------------
    | These values must match the Admin pipeline values.
    | Do not store display labels like "Closed Won" in the database.
    |--------------------------------------------------------------------------
    */
    protected array $opportunityStages = [
        'new',
        'attempting_contact',
        'manager_confirmation_pending',
        'appointment',
        'offer',
        'follow_up',
        'closed_won',
        'closed_lost',
    ];

    protected array $stageLabels = [
        'new' => 'New',
        'attempting_contact' => 'Attempting Contact',
        'manager_confirmation_pending' => 'Manager Confirmation Pending',
        'appointment' => 'Appointment',
        'offer' => 'Offer',
        'follow_up' => 'Follow Up',
        'closed_won' => 'Closed Won',
        'closed_lost' => 'Closed Lost',
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
        $stage = trim((string) $request->get('stage', ''));
        $status = trim((string) $request->get('status', ''));

        $opportunities = Opportunity::query()
            ->where('company_id', $companyId)
            ->when(Schema::hasColumn('opportunities', 'is_active'), function ($query) {
                $query->where('is_active', 1);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    foreach ([
                        'title',
                        'name',
                        'customer_name',
                        'phone',
                        'mobile',
                        'phone_number',
                        'whatsapp_number',
                        'email',
                        'vehicle_make',
                        'vehicle_model',
                        'notes',
                        'manager_notes',
                        'internal_notes',
                    ] as $column) {
                        if (Schema::hasColumn('opportunities', $column)) {
                            $sub->orWhere($column, 'like', '%' . $q . '%');
                        }
                    }
                });
            })
            ->when($stage !== '' && Schema::hasColumn('opportunities', 'stage'), function ($query) use ($stage) {
                $query->where('stage', $stage);
            })
            ->when($status !== '' && Schema::hasColumn('opportunities', 'status'), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when(Schema::hasColumn('opportunities', 'stage'), function ($query) {
                $query->whereNotIn('stage', [
                    'closed_won',
                    'closed_lost',
                    'Closed Won',
                    'Closed Lost',
                ]);
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $managers = $this->assignableUsers($companyId);
        $opportunityStages = $this->opportunityStages;
        $stageLabels = $this->stageLabels;

        return view('manager.opportunities.index', compact(
            'opportunities',
            'managers',
            'q',
            'stage',
            'status',
            'opportunityStages',
            'stageLabels'
        ));
    }

    public function show(Opportunity $opportunity)
    {
        $this->authorizeOpportunity($opportunity);

        $managers = $this->assignableUsers($this->companyId());
        $opportunityStages = $this->opportunityStages;
        $stageLabels = $this->stageLabels;

        return view('manager.opportunities.show', compact(
            'opportunity',
            'managers',
            'opportunityStages',
            'stageLabels'
        ));
    }

    public function updateStage(Request $request, Opportunity $opportunity)
    {
        $this->authorizeOpportunity($opportunity);

        $validated = $request->validate([
            'stage' => ['required', 'string', Rule::in($this->opportunityStages)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($opportunity, $validated) {
            $stage = $validated['stage'];

            if (Schema::hasColumn('opportunities', 'stage')) {
                $opportunity->stage = $stage;
            }

            if (Schema::hasColumn('opportunities', 'status')) {
                $opportunity->status = $this->statusFromStage($stage);
            }

            if ($stage === 'closed_won' && Schema::hasColumn('opportunities', 'won_at')) {
                $opportunity->won_at = now();
            }

            if ($stage === 'closed_lost' && Schema::hasColumn('opportunities', 'lost_at')) {
                $opportunity->lost_at = now();
            }

            if (! empty($validated['notes'])) {
                $this->appendNotes($opportunity, $validated['notes']);
            }

            $opportunity->save();
        });

        return back()->with('success', 'Opportunity stage updated successfully.');
    }

    public function assign(Request $request, Opportunity $opportunity)
    {
        $this->authorizeOpportunity($opportunity);

        $validated = $request->validate([
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ]);

        $assignee = User::query()
            ->where('company_id', $this->companyId())
            ->findOrFail($validated['assigned_to']);

        DB::transaction(function () use ($opportunity, $assignee) {
            $assignedColumn = $this->firstExistingColumn('opportunities', [
                'assigned_to',
                'assigned_to_id',
                'assigned_user_id',
                'manager_id',
                'user_id',
                'owner_id',
            ]);

            if ($assignedColumn) {
                $opportunity->{$assignedColumn} = $assignee->id;
            }

            if (Schema::hasColumn('opportunities', 'assigned_at')) {
                $opportunity->assigned_at = now();
            }

            $opportunity->save();
        });

        return back()->with('success', 'Opportunity assigned successfully.');
    }

    public function updateFollowUp(Request $request, Opportunity $opportunity)
    {
        $this->authorizeOpportunity($opportunity);

        $validated = $request->validate([
            'follow_up_date' => ['nullable', 'date'],
            'follow_up_required' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($opportunity, $validated) {
            if (Schema::hasColumn('opportunities', 'follow_up_date')) {
                $opportunity->follow_up_date = $validated['follow_up_date'] ?? null;
            }

            if (Schema::hasColumn('opportunities', 'follow_up_required')) {
                $opportunity->follow_up_required = (bool) ($validated['follow_up_required'] ?? false);
            }

            if (! empty($validated['notes'])) {
                $this->appendNotes($opportunity, $validated['notes']);
            }

            $opportunity->save();
        });

        return back()->with('success', 'Opportunity follow-up updated successfully.');
    }

    public function markLost(Request $request, Opportunity $opportunity)
    {
        $this->authorizeOpportunity($opportunity);

        $validated = $request->validate([
            'lost_reason' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($opportunity, $validated) {
            if (Schema::hasColumn('opportunities', 'stage')) {
                $opportunity->stage = 'closed_lost';
            }

            if (Schema::hasColumn('opportunities', 'status')) {
                $opportunity->status = 'lost';
            }

            if (Schema::hasColumn('opportunities', 'lost_reason')) {
                $opportunity->lost_reason = $validated['lost_reason'] ?? null;
            }

            if (Schema::hasColumn('opportunities', 'lost_at')) {
                $opportunity->lost_at = now();
            }

            if (! empty($validated['notes'])) {
                $this->appendNotes($opportunity, $validated['notes']);
            }

            $opportunity->save();
        });

        return redirect()
            ->route('manager.opportunities.index')
            ->with('success', 'Opportunity marked as lost.');
    }

    public function markWon(Request $request, Opportunity $opportunity)
    {
        $this->authorizeOpportunity($opportunity);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Temporary behaviour
        |--------------------------------------------------------------------------
        | This only fixes the stage/status mismatch.
        | Booking creation from closed_won will be added in the next correction batch
        | through the full Manager Opportunity edit/capture flow.
        |--------------------------------------------------------------------------
        */
        DB::transaction(function () use ($opportunity, $validated) {
            if (Schema::hasColumn('opportunities', 'stage')) {
                $opportunity->stage = 'closed_won';
            }

            if (Schema::hasColumn('opportunities', 'status')) {
                $opportunity->status = 'won';
            }

            if (Schema::hasColumn('opportunities', 'won_at')) {
                $opportunity->won_at = now();
            }

            if (! empty($validated['notes'])) {
                $this->appendNotes($opportunity, $validated['notes']);
            }

            $opportunity->save();
        });

        return redirect()
            ->route('manager.opportunities.index')
            ->with('success', 'Opportunity marked as won. Booking capture will be handled from the Manage Opportunity screen.');
    }

    protected function authorizeOpportunity(Opportunity $opportunity): void
    {
        abort_if((int) $opportunity->company_id !== $this->companyId(), 403);
    }

    protected function statusFromStage(string $stage): string
    {
        return match ($stage) {
            'closed_won' => 'won',
            'closed_lost' => 'lost',
            'appointment', 'offer', 'follow_up', 'manager_confirmation_pending' => 'open',
            default => 'active',
        };
    }

    protected function appendNotes(Opportunity $opportunity, string $note): void
    {
        $note = trim($note);

        if ($note === '') {
            return;
        }

        $noteColumn = $this->firstExistingColumn('opportunities', [
            'manager_notes',
            'internal_notes',
            'notes',
        ]);

        if (! $noteColumn) {
            return;
        }

        $existing = trim((string) ($opportunity->{$noteColumn} ?? ''));

        $entry = '[' . now()->format('Y-m-d H:i') . '] '
            . auth()->user()?->name
            . ': '
            . $note;

        $opportunity->{$noteColumn} = $existing
            ? $existing . PHP_EOL . PHP_EOL . $entry
            : $entry;
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
            ->whereIn('role', ['admin', 'manager'])
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