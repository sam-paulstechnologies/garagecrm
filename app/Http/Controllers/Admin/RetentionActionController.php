<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\RetentionAction;
use App\Services\Retention\RetentionTemplateResolver;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RetentionActionController extends Controller
{
    private const EDITABLE_STATUSES = [
        'pending_review',
        'approved',
        'skipped',
        'cancelled',
    ];

    private const LOCKED_STATUSES = [
        'scheduled',
        'sent',
    ];

    private const SEGMENT_LABELS = [
        'general_service_due' => 'General Service Due',
        'oil_change_due' => 'Oil Change Due',
        'tyre_check_due' => 'Tyre Check Due',
        'battery_follow_up' => 'Battery Follow-up',
        'ac_service_reminder' => 'AC Service Reminder',
        'brake_check_reminder' => 'Brake Check Reminder',
        'insurance_expiry_reminder' => 'Insurance Expiry Reminder',
        'mulkia_renewal_reminder' => 'Mulkia Renewal Reminder',
        'inactive_customer_winback' => 'Inactive Customer Winback',
        'vip_follow_up' => 'VIP Follow-up',
        'unclassified' => 'Unclassified',
    ];

    public function index(Request $request, RetentionTemplateResolver $templateResolver): View
    {
        $companyId = (int) auth()->user()->company_id;

        $baseQuery = RetentionAction::query()
            ->where('company_id', $companyId);

        $summary = [
            'pending_review' => (clone $baseQuery)->where('status', 'pending_review')->count(),
            'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
            'skipped' => (clone $baseQuery)->where('status', 'skipped')->count(),
            'cancelled' => (clone $baseQuery)->where('status', 'cancelled')->count(),
            'scheduled' => (clone $baseQuery)->where('status', 'scheduled')->count(),
            'sent' => (clone $baseQuery)->where('status', 'sent')->count(),
        ];

        $segments = (clone $baseQuery)
            ->select('segment_code', 'segment_label')
            ->whereNotNull('segment_code')
            ->orderBy('segment_code')
            ->get()
            ->mapWithKeys(function (RetentionAction $action) {
                return [
                    $action->segment_code => $action->segment_label
                        ?: (self::SEGMENT_LABELS[$action->segment_code] ?? str($action->segment_code)->headline()->toString()),
                ];
            })
            ->all();

        $previewRelations = [
            'company:id,name',
            'client:id,company_id,name,phone,whatsapp,email',
            'vehicle:id,company_id,client_id,make_id,model_id,plate_number',
            'vehicle.make:id,name',
            'vehicle.model:id,name',
            'importRow:id,batch_id,company_id,row_number,review_status',
            'vehicleServiceHistory:id,company_id,client_id,vehicle_id,source_type,source_id,service_type,service_date',
        ];

        $query = (clone $baseQuery)->with($previewRelations);

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('suggested_message', 'like', "%{$search}%")
                    ->orWhere('segment_code', 'like', "%{$search}%")
                    ->orWhere('segment_label', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('whatsapp', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($segment = $request->query('segment')) {
            $query->where('segment_code', $segment);
        }

        if ($sourceType = $request->query('source_type')) {
            $query->where('source_type', $sourceType);
        }

        if ($from = $request->query('from')) {
            $query->whereDate('suggested_follow_up_date', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('suggested_follow_up_date', '<=', $to);
        }

        if ($request->boolean('overdue')) {
            $query->whereDate('suggested_follow_up_date', '<', today());
        } elseif ($request->boolean('due_soon')) {
            $query->whereBetween('suggested_follow_up_date', [today(), today()->addDays(7)]);
        }

        if ($readiness = $request->query('readiness')) {
            $matchingIds = (clone $query)
                ->get()
                ->filter(function (RetentionAction $action) use ($templateResolver, $readiness) {
                    return ($templateResolver->resolve($action)['readiness'] ?? null) === $readiness;
                })
                ->pluck('id')
                ->all();

            if (empty($matchingIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id', $matchingIds);
            }
        }

        $retentionActions = $query
            ->orderByRaw("FIELD(status, 'pending_review', 'approved', 'skipped', 'cancelled', 'scheduled', 'sent')")
            ->orderByRaw('suggested_follow_up_date IS NULL')
            ->orderBy('suggested_follow_up_date')
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        $retentionActions->getCollection()->transform(function (RetentionAction $action) use ($templateResolver) {
            $action->template_preview = $templateResolver->resolve($action);

            return $action;
        });

        return view('admin.retention-actions.index', [
            'retentionActions' => $retentionActions,
            'summary' => $summary,
            'segments' => $segments,
            'statuses' => $this->statusLabels(),
            'sourceTypes' => (clone $baseQuery)
                ->whereNotNull('source_type')
                ->distinct()
                ->orderBy('source_type')
                ->pluck('source_type')
                ->all(),
            'readinessOptions' => $this->readinessLabels(),
        ]);
    }

    public function report(Request $request, RetentionTemplateResolver $templateResolver): View
    {
        $companyId = (int) auth()->user()->company_id;
        $today = today();
        $terminalStatuses = ['sent', 'skipped', 'cancelled'];
        $statuses = $this->statusLabels();

        $baseQuery = RetentionAction::query()
            ->where('company_id', $companyId);

        $segments = (clone $baseQuery)
            ->select('segment_code', 'segment_label')
            ->whereNotNull('segment_code')
            ->orderBy('segment_code')
            ->get()
            ->mapWithKeys(function (RetentionAction $action) {
                return [
                    $action->segment_code => $action->segment_label
                        ?: (self::SEGMENT_LABELS[$action->segment_code] ?? str($action->segment_code)->headline()->toString()),
                ];
            })
            ->all();

        $sourceBatches = DB::table('client_import_batches')
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'original_filename']);

        $filteredQuery = $this->reportFilteredQuery($request, $companyId);

        $summary = [
            'total' => (clone $filteredQuery)->count(),
            'pending_review' => (clone $filteredQuery)->where('status', 'pending_review')->count(),
            'approved' => (clone $filteredQuery)->where('status', 'approved')->count(),
            'scheduled' => (clone $filteredQuery)->where('status', 'scheduled')->count(),
            'sent' => (clone $filteredQuery)->where('status', 'sent')->count(),
            'skipped' => (clone $filteredQuery)->where('status', 'skipped')->count(),
            'cancelled' => (clone $filteredQuery)->where('status', 'cancelled')->count(),
            'due_today' => $this->dueSignalQuery(clone $filteredQuery, $today, '=')->whereNotIn('status', $terminalStatuses)->count(),
            'overdue' => $this->dueSignalQuery(clone $filteredQuery, $today, '<')->whereNotIn('status', $terminalStatuses)->count(),
            'template_pending' => 0,
            'missing_template' => 0,
        ];

        $readinessActions = (clone $filteredQuery)
            ->with([
                'company:id,name',
                'client:id,company_id,name,phone,whatsapp,email',
                'vehicle:id,company_id,client_id,make_id,model_id,plate_number',
                'vehicle.make:id,name',
                'vehicle.model:id,name',
            ])
            ->get();

        foreach ($readinessActions as $action) {
            $readiness = $templateResolver->resolve($action)['readiness'] ?? null;

            if ($readiness === 'template_pending') {
                $summary['template_pending']++;
            } elseif (in_array($readiness, ['warning_missing_template', 'template_rejected'], true)) {
                $summary['missing_template']++;
            }
        }

        $segmentBreakdown = (clone $filteredQuery)
            ->select('segment_code', 'segment_label')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(status = 'pending_review') as pending_review")
            ->selectRaw("SUM(status = 'approved') as approved")
            ->selectRaw("SUM(status = 'scheduled') as scheduled")
            ->selectRaw("SUM(status = 'sent') as sent")
            ->selectRaw("SUM(status = 'skipped') as skipped")
            ->selectRaw("SUM(status = 'cancelled') as cancelled")
            ->groupBy('segment_code', 'segment_label')
            ->orderByDesc('total')
            ->get();

        $upcomingFollowUps = (clone $filteredQuery)
            ->with([
                'client:id,company_id,name,phone,whatsapp,email',
                'vehicle:id,company_id,client_id,make_id,model_id,plate_number',
                'vehicle.make:id,name',
                'vehicle.model:id,name',
            ])
            ->whereNotIn('status', $terminalStatuses)
            ->where(function ($query) use ($today) {
                $query->whereDate('suggested_follow_up_date', '>=', $today)
                    ->orWhereDate('scheduled_at', '>=', $today);
            })
            ->orderByRaw('COALESCE(scheduled_at, suggested_follow_up_date) IS NULL')
            ->orderByRaw('COALESCE(scheduled_at, suggested_follow_up_date) ASC')
            ->limit(12)
            ->get()
            ->map(function (RetentionAction $action) use ($templateResolver) {
                $action->template_preview = $templateResolver->resolve($action);

                return $action;
            });

        $batchContribution = DB::table('retention_actions as ra')
            ->join('client_import_rows as cir', function ($join) {
                $join->on('cir.id', '=', 'ra.source_id')
                    ->where('ra.source_type', '=', 'client_import_row');
            })
            ->join('client_import_batches as cib', 'cib.id', '=', 'cir.batch_id')
            ->where('ra.company_id', $companyId)
            ->when($request->filled('source_batch'), fn ($query) => $query->where('cib.id', (int) $request->query('source_batch')))
            ->select('cib.id', 'cib.original_filename')
            ->selectRaw('COUNT(ra.id) as actions_created')
            ->selectRaw("SUM(ra.status = 'approved') as approved")
            ->selectRaw("SUM(ra.status = 'scheduled') as scheduled")
            ->selectRaw("SUM(ra.status = 'sent') as sent")
            ->selectRaw("SUM(ra.status = 'skipped') as skipped")
            ->groupBy('cib.id', 'cib.original_filename')
            ->orderByDesc('actions_created')
            ->limit(10)
            ->get();

        return view('admin.retention-actions.report', [
            'summary' => $summary,
            'segments' => $segments,
            'statuses' => $statuses,
            'sourceBatches' => $sourceBatches,
            'segmentBreakdown' => $segmentBreakdown,
            'upcomingFollowUps' => $upcomingFollowUps,
            'batchContribution' => $batchContribution,
        ]);
    }

    public function update(Request $request, RetentionAction $retentionAction): RedirectResponse
    {
        $this->authorizeCompany($retentionAction);

        if ($this->isLocked($retentionAction)) {
            return back()->withErrors([
                'retention_action' => 'Scheduled and sent retention actions are read-only in this phase.',
            ]);
        }

        $data = $request->validate([
            'status' => ['nullable', 'string', 'in:' . implode(',', self::EDITABLE_STATUSES)],
            'suggested_follow_up_date' => ['nullable', 'date'],
            'suggested_message' => ['nullable', 'string', 'max:2000'],
        ]);

        $updates = [
            'suggested_follow_up_date' => $data['suggested_follow_up_date'] ?? null,
            'suggested_message' => $data['suggested_message'] ?? null,
        ];

        if (! empty($data['status'])) {
            if (! $this->canTransition($retentionAction->status, $data['status'])) {
                return back()->withErrors([
                    'status' => 'That retention action status change is not allowed.',
                ]);
            }

            $updates['status'] = $data['status'];
            $this->applyApprovalAuditFields($updates, $retentionAction->status, $data['status']);
        }

        $retentionAction->update($updates);

        return back()->with('success', 'Retention action updated. No messages were sent.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $companyId = (int) auth()->user()->company_id;

        $data = $request->validate([
            'retention_action_ids' => ['required', 'array', 'min:1'],
            'retention_action_ids.*' => ['integer'],
            'bulk_action' => ['required', 'string', 'in:approved,skipped,cancelled,pending_review'],
        ]);

        $targetStatus = $data['bulk_action'];
        $updated = 0;
        $locked = 0;
        $notAllowed = 0;

        RetentionAction::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $data['retention_action_ids'])
            ->orderBy('id')
            ->chunkById(100, function ($actions) use ($targetStatus, &$updated, &$locked, &$notAllowed) {
                foreach ($actions as $action) {
                    if ($this->isLocked($action)) {
                        $locked++;
                        continue;
                    }

                    if (! $this->canTransition($action->status, $targetStatus)) {
                        $notAllowed++;
                        continue;
                    }

                    $updates = ['status' => $targetStatus];
                    $this->applyApprovalAuditFields($updates, $action->status, $targetStatus);

                    $action->update($updates);
                    $updated++;
                }
            });

        return back()->with(
            'success',
            "Bulk update complete: {$updated} action(s) updated, {$locked} locked scheduled/sent action(s) skipped, {$notAllowed} invalid transition(s) skipped. No messages were sent."
        );
    }

    public function scheduleDraft(Request $request, RetentionAction $retentionAction, RetentionTemplateResolver $templateResolver): RedirectResponse
    {
        $this->authorizeCompany($retentionAction);

        $data = $request->validate([
            'scheduled_at' => ['required', 'date', 'after_or_equal:today'],
        ]);

        [$allowed, $reason, $preview] = $this->scheduleEligibility($retentionAction, $templateResolver);

        if (! $allowed) {
            return back()->withErrors([
                'schedule_draft' => $reason,
            ]);
        }

        $this->applyScheduleDraft($retentionAction, Carbon::parse($data['scheduled_at']), $preview);

        return back()->with('success', 'Schedule draft created. No WhatsApp message was sent.');
    }

    public function bulkScheduleDraft(Request $request, RetentionTemplateResolver $templateResolver): RedirectResponse
    {
        $companyId = (int) auth()->user()->company_id;

        $data = $request->validate([
            'retention_action_ids' => ['required', 'array', 'min:1'],
            'retention_action_ids.*' => ['integer'],
            'scheduled_at' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $scheduledAt = Carbon::parse($data['scheduled_at']);
        $summary = [
            'scheduled' => 0,
            'skipped_not_approved' => 0,
            'skipped_not_ready' => 0,
            'skipped_opted_out' => 0,
            'skipped_already_scheduled_or_sent' => 0,
        ];

        RetentionAction::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $data['retention_action_ids'])
            ->with([
                'company:id,name',
                'client:id,company_id,name,phone,whatsapp,email',
                'vehicle:id,company_id,client_id,make_id,model_id,plate_number',
                'vehicle.make:id,name',
                'vehicle.model:id,name',
            ])
            ->orderBy('id')
            ->chunkById(100, function ($actions) use ($templateResolver, $scheduledAt, &$summary) {
                foreach ($actions as $action) {
                    [$allowed, $reason, $preview] = $this->scheduleEligibility($action, $templateResolver);

                    if ($allowed) {
                        $this->applyScheduleDraft($action, $scheduledAt, $preview);
                        $summary['scheduled']++;
                        continue;
                    }

                    if (in_array($action->status, ['scheduled', 'sent'], true)) {
                        $summary['skipped_already_scheduled_or_sent']++;
                    } elseif ($action->status !== 'approved') {
                        $summary['skipped_not_approved']++;
                    } elseif (($preview['readiness'] ?? null) === 'blocked_opted_out') {
                        $summary['skipped_opted_out']++;
                    } else {
                        $summary['skipped_not_ready']++;
                    }
                }
            });

        return back()->with(
            'success',
            "Schedule draft complete: {$summary['scheduled']} scheduled, {$summary['skipped_not_approved']} not approved, {$summary['skipped_not_ready']} not ready, {$summary['skipped_opted_out']} opted out, {$summary['skipped_already_scheduled_or_sent']} already scheduled/sent. No messages were sent."
        );
    }

    public function unscheduleDraft(RetentionAction $retentionAction): RedirectResponse
    {
        $this->authorizeCompany($retentionAction);

        if ($retentionAction->status === 'sent') {
            return back()->withErrors([
                'schedule_draft' => 'Sent retention actions cannot be unscheduled.',
            ]);
        }

        if ($retentionAction->status !== 'scheduled') {
            return back()->withErrors([
                'schedule_draft' => 'Only scheduled draft retention actions can be unscheduled.',
            ]);
        }

        $meta = is_array($retentionAction->meta) ? $retentionAction->meta : [];

        foreach ([
            'schedule_draft_created_by',
            'schedule_draft_created_at',
            'template_key',
            'template_readiness',
            'resolved_variables',
            'final_message_preview',
        ] as $key) {
            unset($meta[$key]);
        }

        $meta['schedule_draft_unscheduled_by'] = auth()->id();
        $meta['schedule_draft_unscheduled_at'] = now()->toDateTimeString();

        $retentionAction->update([
            'status' => 'approved',
            'scheduled_at' => null,
            'meta' => $meta,
        ]);

        return back()->with('success', 'Schedule draft removed and action returned to approved. No messages were sent.');
    }

    private function statusLabels(): array
    {
        return [
            'pending_review' => 'Pending Review',
            'approved' => 'Approved',
            'skipped' => 'Skipped',
            'cancelled' => 'Cancelled',
            'scheduled' => 'Scheduled',
            'sent' => 'Sent',
        ];
    }

    private function reportFilteredQuery(Request $request, int $companyId)
    {
        $query = RetentionAction::query()
            ->where('company_id', $companyId);

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('suggested_message', 'like', "%{$search}%")
                    ->orWhere('segment_code', 'like', "%{$search}%")
                    ->orWhere('segment_label', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('whatsapp', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($segment = $request->query('segment')) {
            $query->where('segment_code', $segment);
        }

        if ($from = $request->query('from')) {
            $query->where(function ($q) use ($from) {
                $q->whereDate('suggested_follow_up_date', '>=', $from)
                    ->orWhereDate('scheduled_at', '>=', $from);
            });
        }

        if ($to = $request->query('to')) {
            $query->where(function ($q) use ($to) {
                $q->whereDate('suggested_follow_up_date', '<=', $to)
                    ->orWhereDate('scheduled_at', '<=', $to);
            });
        }

        if ($batchId = $request->query('source_batch')) {
            $rowIds = DB::table('client_import_rows')
                ->where('company_id', $companyId)
                ->where('batch_id', (int) $batchId)
                ->select('id');

            $query->where('source_type', 'client_import_row')
                ->whereIn('source_id', $rowIds);
        }

        if ($request->boolean('due_soon')) {
            $query->where(function ($q) {
                $q->whereBetween('suggested_follow_up_date', [today(), today()->addDays(7)])
                    ->orWhereBetween('scheduled_at', [today(), today()->addDays(7)->endOfDay()]);
            });
        }

        if ($request->boolean('overdue')) {
            $query->where(function ($q) {
                $q->whereDate('suggested_follow_up_date', '<', today())
                    ->orWhereDate('scheduled_at', '<', today());
            });
        }

        return $query;
    }

    private function dueSignalQuery($query, Carbon $date, string $operator)
    {
        return $query->where(function ($q) use ($date, $operator) {
            $q->where(function ($scheduled) use ($date, $operator) {
                $scheduled->whereNotNull('scheduled_at')
                    ->whereDate('scheduled_at', $operator, $date);
            })->orWhere(function ($suggested) use ($date, $operator) {
                $suggested->whereNull('scheduled_at')
                    ->whereDate('suggested_follow_up_date', $operator, $date);
            });
        });
    }

    private function readinessLabels(): array
    {
        return [
            'ready' => 'Ready',
            'warning_missing_template' => 'Missing Template',
            'template_pending' => 'Template Pending',
            'template_rejected' => 'Template Rejected',
            'blocked_no_phone' => 'Missing Phone',
            'warning_missing_vehicle' => 'Missing Vehicle',
            'blocked_opted_out' => 'Opted Out',
            'needs_review' => 'Needs Review',
        ];
    }

    private function authorizeCompany(RetentionAction $retentionAction): void
    {
        abort_if((int) $retentionAction->company_id !== (int) auth()->user()->company_id, 404);
    }

    private function isLocked(RetentionAction $retentionAction): bool
    {
        return in_array($retentionAction->status, self::LOCKED_STATUSES, true);
    }

    private function scheduleEligibility(RetentionAction $action, RetentionTemplateResolver $templateResolver): array
    {
        if ($action->status === 'sent') {
            return [false, 'Sent retention actions cannot be scheduled again.', []];
        }

        if ($action->status === 'scheduled') {
            return [false, 'This retention action already has a schedule draft.', []];
        }

        if ($action->status !== 'approved') {
            return [false, 'Only approved retention actions can be schedule-drafted.', []];
        }

        $preview = $templateResolver->resolve($action);

        if (($preview['readiness'] ?? null) !== 'ready') {
            return [false, 'This retention action is not ready for scheduling: ' . ($preview['readiness_label'] ?? 'Needs review') . '.', $preview];
        }

        return [true, null, $preview];
    }

    private function applyScheduleDraft(RetentionAction $action, Carbon $scheduledAt, array $preview): void
    {
        $meta = is_array($action->meta) ? $action->meta : [];
        $meta['schedule_draft_created_by'] = auth()->id();
        $meta['schedule_draft_created_at'] = now()->toDateTimeString();
        $meta['template_key'] = $preview['template_key'] ?? null;
        $meta['template_readiness'] = $preview['readiness'] ?? null;
        $meta['resolved_variables'] = $preview['variables'] ?? [];
        $meta['final_message_preview'] = $preview['final_message_preview'] ?? ($preview['template_preview'] ?? $preview['fallback_message'] ?? null);

        $action->update([
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt,
            'meta' => $meta,
        ]);
    }

    private function canTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return match ($from) {
            'pending_review' => in_array($to, ['approved', 'skipped', 'cancelled'], true),
            'approved' => in_array($to, ['pending_review', 'skipped', 'cancelled'], true),
            'skipped', 'cancelled' => $to === 'pending_review',
            default => false,
        };
    }

    private function applyApprovalAuditFields(array &$updates, string $from, string $to): void
    {
        if ($to === 'approved' && $from !== 'approved') {
            $updates['approved_by'] = auth()->id();
            $updates['approved_at'] = now();

            return;
        }

        if ($from === 'approved' && $to !== 'approved') {
            $updates['approved_by'] = null;
            $updates['approved_at'] = null;
        }
    }
}
