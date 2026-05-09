<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppFromTemplate;
use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\LeadDuplicate;
use App\Models\MessageLog;
use App\Models\Shared\Communication;
use App\Models\User;
use App\Services\Leads\LeadResolver;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
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
        $bucket = trim((string) $request->get('bucket', ''));

        $query = $this->baseLeadQuery($companyId, $q)
            ->where('is_active', 1)
            ->whereIn('status', [
                Lead::STATUS_NEW,
                Lead::STATUS_ATTEMPTING,
                'contact_on_hold',
            ]);

        $this->applyBucketFilter($query, $bucket);

        $leads = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        [$leadScores, $whatsappByLead] = $this->leadPageMetrics($leads, $companyId);

        return view('admin.leads.index', [
            'leads' => $leads,
            'q' => $q,
            'bucket' => $bucket,
            'pageMode' => 'open',
            'pageTitle' => $this->bucketTitle($bucket),
            'pageSubtitle' => $this->bucketSubtitle($bucket),
            'leadCounts' => $this->leadCounts($companyId),
            'bucketCounts' => $this->bucketCounts($companyId),
            'leadScores' => $leadScores,
            'whatsappByLead' => $whatsappByLead,
        ]);
    }

    public function qualified(Request $request)
    {
        $companyId = $this->companyId();
        $q = trim((string) $request->get('q', ''));

        $leads = $this->baseLeadQuery($companyId, $q)
            ->whereIn('status', [
                Lead::STATUS_QUALIFIED,
                Lead::STATUS_CONVERTED,
            ])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        [$leadScores, $whatsappByLead] = $this->leadPageMetrics($leads, $companyId);

        return view('admin.leads.index', [
            'leads' => $leads,
            'q' => $q,
            'bucket' => '',
            'pageMode' => 'qualified',
            'pageTitle' => 'Qualified / Converted Leads',
            'pageSubtitle' => 'Leads already qualified or converted into clients/opportunities.',
            'leadCounts' => $this->leadCounts($companyId),
            'bucketCounts' => $this->bucketCounts($companyId),
            'leadScores' => $leadScores,
            'whatsappByLead' => $whatsappByLead,
        ]);
    }

    public function disqualified(Request $request)
    {
        $companyId = $this->companyId();
        $q = trim((string) $request->get('q', ''));

        $leads = $this->baseLeadQuery($companyId, $q)
            ->whereIn('status', [
                'disqualified',
                Lead::STATUS_LOST,
            ])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        [$leadScores, $whatsappByLead] = $this->leadPageMetrics($leads, $companyId);

        return view('admin.leads.index', [
            'leads' => $leads,
            'q' => $q,
            'bucket' => '',
            'pageMode' => 'disqualified',
            'pageTitle' => 'Disqualified Leads',
            'pageSubtitle' => 'Invalid, lost, duplicate, or non-serviceable leads.',
            'leadCounts' => $this->leadCounts($companyId),
            'bucketCounts' => $this->bucketCounts($companyId),
            'leadScores' => $leadScores,
            'whatsappByLead' => $whatsappByLead,
        ]);
    }

    public function create()
    {
        $companyId = $this->companyId();

        return view('admin.leads.create', [
            'clients' => Client::where('company_id', $companyId)
                ->orderBy('name')
                ->get(),

            'managers' => User::where('company_id', $companyId)
                ->where('role', 'manager')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request, WhatsAppService $whatsapp, LeadResolver $leadResolver)
    {
        $companyId = $this->companyId();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:150|required_without:phone',
            'phone' => 'nullable|string|max:20|required_without:email',
            'source' => 'nullable|string|max:100',
            'status' => 'nullable|in:new,attempting_contact,contact_on_hold,qualified,disqualified,converted,lost',
            'notes' => 'nullable|string',
            'lead_score_reason' => 'nullable|string',
            'last_contacted_at' => 'nullable|date',
            'preferred_channel' => 'nullable|in:email,phone,whatsapp',

            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'client_id' => [
                'nullable',
                Rule::exists('clients', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'is_hot' => 'nullable|boolean',
            'other_make' => 'nullable|string|max:100',
            'other_model' => 'nullable|string|max:100',
            'tentative_service_type' => 'nullable|string|max:150',
            'send_whatsapp_now' => 'nullable|boolean',

            'service_category' => 'nullable|string|max:50',
            'service_type' => 'nullable|string|max:100',
            'vehicle_make' => 'nullable|string|max:100',
            'vehicle_model' => 'nullable|string|max:100',
            'vehicle_year' => 'nullable|integer|min:1900|max:2100',
            'plate_number' => 'nullable|string|max:50',
            'lead_temperature' => 'nullable|in:hot,warm,cold',
            'lead_priority' => 'nullable|in:low,medium,high,urgent',
            'customer_type' => 'nullable|in:new,existing,fleet,corporate',
            'follow_up_required' => 'nullable|boolean',
            'follow_up_date' => 'nullable|date',
            'campaign_name' => 'nullable|string|max:150',
            'retention_tag' => 'nullable|string|max:80',
        ]);

        $data['company_id'] = $companyId;
        $data['status'] = $data['status'] ?? Lead::STATUS_NEW;
        $data['source'] = $data['source'] ?? 'Manual';
        $data['preferred_channel'] = $data['preferred_channel'] ?? 'whatsapp';

        $lead = null;
        $messageType = 'success';
        $messageText = '✅ Lead saved successfully.';

        DB::transaction(function () use ($data, $leadResolver, $companyId, &$lead, &$messageType, &$messageText) {
            $lead = $leadResolver->resolve([
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'source' => $data['source'],
            ], $companyId);

            if (! $lead) {
                throw new \Exception('Lead creation failed');
            }

            if ($lead->wasRecentlyCreated) {
                $messageType = 'success';
                $messageText = '✅ New lead created successfully.';
            } elseif ($lead->is_active) {
                $messageType = 'warning';
                $messageText = '⚠️ Active lead already exists. Reusing existing record.';
            } else {
                $messageType = 'success';
                $messageText = '🔁 Previous enquiry found. Reusing existing lead record.';
            }

            $memory = $lead->getConversationMemory();

            $serviceType = $data['service_type']
                ?? $data['tentative_service_type']
                ?? ($memory['service_type'] ?? null);

            $memory = array_merge($memory, [
                'tentative_service_type' => $serviceType,
                'service_type' => $serviceType,
                'vehicle_make_text' => $data['vehicle_make'] ?? $data['other_make'] ?? ($memory['vehicle_make_text'] ?? null),
                'vehicle_model_text' => $data['vehicle_model'] ?? $data['other_model'] ?? ($memory['vehicle_model_text'] ?? null),
                'manual_lead_created_at' => now()->toDateTimeString(),
                'manual_lead_source' => true,
            ]);

            $lead->update([
                'name' => $data['name'],
                'email' => $data['email'] ?? $lead->email,
                'phone' => $data['phone'] ?? $lead->phone,
                'source' => $data['source'],
                'status' => Lead::normalizeStatus($data['status']),
                'assigned_to' => $data['assigned_to'] ?? $lead->assigned_to,
                'notes' => $data['notes'] ?? $lead->notes,
                'lead_score_reason' => $data['lead_score_reason'] ?? $lead->lead_score_reason,
                'last_contacted_at' => $data['last_contacted_at'] ?? $lead->last_contacted_at,
                'preferred_channel' => $data['preferred_channel'],
                'client_id' => $data['client_id'] ?? $lead->client_id,
                'is_hot' => (bool) ($data['is_hot'] ?? false),

                'other_make' => $data['other_make'] ?? $lead->other_make,
                'other_model' => $data['other_model'] ?? $lead->other_model,

                'service_category' => $data['service_category'] ?? $lead->service_category,
                'service_type' => $data['service_type'] ?? $serviceType ?? $lead->service_type,
                'vehicle_make' => $data['vehicle_make'] ?? $lead->vehicle_make,
                'vehicle_model' => $data['vehicle_model'] ?? $lead->vehicle_model,
                'vehicle_year' => $data['vehicle_year'] ?? $lead->vehicle_year,
                'plate_number' => $data['plate_number'] ?? $lead->plate_number,
                'lead_temperature' => $data['lead_temperature'] ?? $lead->lead_temperature,
                'lead_priority' => $data['lead_priority'] ?? $lead->lead_priority,
                'customer_type' => $data['customer_type'] ?? $lead->customer_type,
                'follow_up_required' => (bool) ($data['follow_up_required'] ?? $lead->follow_up_required ?? false),
                'follow_up_date' => $data['follow_up_date'] ?? $lead->follow_up_date,
                'campaign_name' => $data['campaign_name'] ?? $lead->campaign_name,
                'retention_tag' => $data['retention_tag'] ?? $lead->retention_tag,

                'conversation_state' => Lead::CONVERSATION_AWAITING_TIMESLOT,
                'conversation_data' => $memory,
            ]);

            if (in_array($lead->fresh()->status, [Lead::STATUS_QUALIFIED, Lead::STATUS_CONVERTED], true)) {
                $this->convertToOpportunity($lead->fresh());
            }
        });

        if (
            $lead
            && (bool) ($data['send_whatsapp_now'] ?? false)
            && ($data['preferred_channel'] ?? 'whatsapp') === 'whatsapp'
            && ($lead->phone_norm || $lead->phone)
        ) {
            $sent = $this->sendManualLeadWelcomeMessage($lead->fresh(), $whatsapp);

            if ($sent) {
                $messageText .= ' WhatsApp welcome message queued/sent.';
            } else {
                $messageType = 'warning';
                $messageText .= ' WhatsApp welcome message could not be sent. Please check WhatsApp settings/logs.';
            }
        }

        session()->flash($messageType, $messageText);

        return redirect()->route('admin.leads.show', $lead);
    }

    public function show(Lead $lead)
    {
        $this->authorizeCompany($lead);

        $companyId = $this->companyId();

        $lead->load([
            'client',
            'assignee',
            'leadSource',
            'vehicleMake',
            'vehicleModel',
        ]);

        return view('admin.leads.show', [
            'lead' => $lead,

            'communications' => Communication::where('company_id', $companyId)
                ->where('lead_id', $lead->id)
                ->latest()
                ->paginate(10),

            'messageLogs' => MessageLog::where('company_id', $companyId)
                ->where('lead_id', $lead->id)
                ->latest()
                ->paginate(10),

            'leadScore' => $this->calculateLeadScore($lead, $companyId),
        ]);
    }

    public function edit(Lead $lead)
    {
        $this->authorizeCompany($lead);

        $companyId = $this->companyId();

        return view('admin.leads.edit', [
            'lead' => $lead,

            'clients' => Client::where('company_id', $companyId)
                ->orderBy('name')
                ->get(),

            'managers' => User::where('company_id', $companyId)
                ->where('role', 'manager')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, Lead $lead)
    {
        $this->authorizeCompany($lead);

        $companyId = $this->companyId();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:150|required_without:phone',
            'phone' => 'nullable|string|max:20|required_without:email',
            'source' => 'nullable|string|max:100',
            'status' => 'nullable|in:new,attempting_contact,contact_on_hold,qualified,disqualified,converted,lost',
            'notes' => 'nullable|string',
            'lead_score_reason' => 'nullable|string',
            'last_contacted_at' => 'nullable|date',
            'preferred_channel' => 'nullable|in:email,phone,whatsapp',

            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'client_id' => [
                'nullable',
                Rule::exists('clients', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'is_hot' => 'nullable|boolean',
            'other_make' => 'nullable|string|max:100',
            'other_model' => 'nullable|string|max:100',
            'tentative_service_type' => 'nullable|string|max:150',

            'service_category' => 'nullable|string|max:50',
            'service_type' => 'nullable|string|max:100',
            'vehicle_make' => 'nullable|string|max:100',
            'vehicle_model' => 'nullable|string|max:100',
            'vehicle_year' => 'nullable|integer|min:1900|max:2100',
            'plate_number' => 'nullable|string|max:50',
            'lead_temperature' => 'nullable|in:hot,warm,cold',
            'lead_priority' => 'nullable|in:low,medium,high,urgent',
            'customer_type' => 'nullable|in:new,existing,fleet,corporate',
            'follow_up_required' => 'nullable|boolean',
            'follow_up_date' => 'nullable|date',
            'campaign_name' => 'nullable|string|max:150',
            'retention_tag' => 'nullable|string|max:80',
        ]);

        $memory = $lead->getConversationMemory();

        $serviceType = $data['service_type']
            ?? $data['tentative_service_type']
            ?? ($memory['service_type'] ?? null);

        $memory = array_merge($memory, [
            'tentative_service_type' => $serviceType,
            'service_type' => $serviceType,
            'vehicle_make_text' => $data['vehicle_make'] ?? $data['other_make'] ?? ($memory['vehicle_make_text'] ?? null),
            'vehicle_model_text' => $data['vehicle_model'] ?? $data['other_model'] ?? ($memory['vehicle_model_text'] ?? null),
        ]);

        $data['is_hot'] = (bool) ($data['is_hot'] ?? false);
        $data['follow_up_required'] = (bool) ($data['follow_up_required'] ?? false);
        $data['conversation_data'] = $memory;

        if (! empty($data['status'])) {
            $data['status'] = Lead::normalizeStatus($data['status']);
        }

        $lead->update($data);

        if (($data['status'] ?? null) === Lead::STATUS_QUALIFIED) {
            $this->convertToOpportunity($lead->fresh());
        }

        return redirect()
            ->route('admin.leads.show', $lead)
            ->with('success', 'Lead updated successfully.');
    }

    public function toggleHot(Lead $lead)
    {
        $this->authorizeCompany($lead);

        $lead->update([
            'is_hot' => ! (bool) $lead->is_hot,
        ]);

        return back()->with('success', 'Lead hot status updated.');
    }

    public function destroy(Lead $lead)
    {
        $this->authorizeCompany($lead);

        $lead->update([
            'is_active' => 0,
            'status' => Lead::STATUS_LOST,
        ]);

        return redirect()
            ->route('admin.leads.index')
            ->with('success', 'Lead archived/disqualified successfully.');
    }

    public function importOptions()
    {
        $this->companyId();

        return view('admin.leads.import.index');
    }

    public function importExcel()
    {
        $this->companyId();

        return redirect()->route('admin.leads.import.upload');
    }

    public function import(Request $request)
    {
        $this->companyId();

        return redirect()->route('admin.leads.import.upload');
    }

    public function downloadSample()
    {
        $this->companyId();

        return response()->download(public_path('samples/sample_lead_import.csv'));
    }

    public function customForm()
    {
        $this->companyId();

        return view('admin.leads.import.custom-form');
    }

    protected function sendManualLeadWelcomeMessage(Lead $lead, WhatsAppService $whatsapp): bool
    {
        $companyId = (int) $lead->company_id;
        $memory = $lead->getConversationMemory();

        $serviceType = $lead->service_type
            ?? $memory['service_type']
            ?? $memory['tentative_service_type']
            ?? 'your service request';

        $vehicle = $lead->vehicle_label
            ?? trim(($lead->vehicle_make ?? $lead->other_make ?? '') . ' ' . ($lead->vehicle_model ?? $lead->other_model ?? ''));

        $vehicle = trim((string) $vehicle);

        try {
            SendWhatsAppFromTemplate::dispatch(
                companyId: $companyId,
                leadId: $lead->id,
                toNumberE164: $lead->phone_norm ?: $lead->phone,
                templateName: 'manual_lead_welcome_v1',
                placeholders: [
                    $lead->name ?: 'there',
                    $serviceType,
                    $vehicle !== '' ? " for your {$vehicle}" : '',
                ],
                links: [],
                context: [
                    'company_id' => $companyId,
                    'lead_id' => $lead->id,
                    'manual_lead' => true,
                    'force_template' => false,
                ],
                action: 'manual_lead_welcome'
            );

            $lead->update([
                'last_contacted_at' => now(),
                'conversation_state' => Lead::CONVERSATION_AWAITING_TIMESLOT,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Manual lead WhatsApp welcome failed', [
                'lead_id' => $lead->id,
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function baseLeadQuery(int $companyId, string $q)
    {
        return Lead::query()
            ->with([
                'client:id,name,phone,email',
                'assignee:id,name',
                'leadSource:id,company_id,name,type,status,config,last_received_at',
                'vehicleMake:id,name',
                'vehicleModel:id,name',
            ])
            ->where('company_id', $companyId)
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('source', 'like', "%{$q}%")
                        ->orWhere('external_source', 'like', "%{$q}%")
                        ->orWhere('external_form_id', 'like', "%{$q}%")
                        ->orWhere('service_category', 'like', "%{$q}%")
                        ->orWhere('service_type', 'like', "%{$q}%")
                        ->orWhere('vehicle_make', 'like', "%{$q}%")
                        ->orWhere('vehicle_model', 'like', "%{$q}%")
                        ->orWhere('plate_number', 'like', "%{$q}%")
                        ->orWhere('lead_temperature', 'like', "%{$q}%")
                        ->orWhere('lead_priority', 'like', "%{$q}%")
                        ->orWhere('customer_type', 'like', "%{$q}%")
                        ->orWhere('campaign_name', 'like', "%{$q}%")
                        ->orWhere('retention_tag', 'like', "%{$q}%")
                        ->orWhereHas('leadSource', function ($sourceQuery) use ($q) {
                            $sourceQuery->where('name', 'like', "%{$q}%")
                                ->orWhere('type', 'like', "%{$q}%");
                        });
                });
            });
    }

    protected function applyBucketFilter($query, ?string $bucket): void
    {
        match ($bucket) {
            'service' => $query->where('service_category', 'service'),

            'quote_followup' => $query->where(function ($q) {
                $q->where('service_category', 'quote')
                    ->orWhere('retention_tag', 'quote_followup');
            }),

            'complaints' => $query->where('service_category', 'complaint'),

            'hot' => $query->where('lead_temperature', 'hot'),

            'high_priority' => $query->whereIn('lead_priority', ['high', 'urgent']),

            'followup_due' => $query->where('follow_up_required', 1)
                ->whereNotNull('follow_up_date')
                ->whereDate('follow_up_date', '<=', now()->toDateString()),

            'service_due' => $query->where('retention_tag', 'service_due'),

            'fleet_corporate' => $query->whereIn('customer_type', ['fleet', 'corporate']),

            default => null,
        };
    }

    protected function bucketCounts(int $companyId): array
    {
        $base = Lead::where('company_id', $companyId)
            ->where('is_active', 1)
            ->whereIn('status', [
                Lead::STATUS_NEW,
                Lead::STATUS_ATTEMPTING,
                'contact_on_hold',
            ]);

        return [
            'service' => (clone $base)
                ->where('service_category', 'service')
                ->count(),

            'quote_followup' => (clone $base)
                ->where(function ($q) {
                    $q->where('service_category', 'quote')
                        ->orWhere('retention_tag', 'quote_followup');
                })
                ->count(),

            'complaints' => (clone $base)
                ->where('service_category', 'complaint')
                ->count(),

            'hot' => (clone $base)
                ->where('lead_temperature', 'hot')
                ->count(),

            'high_priority' => (clone $base)
                ->whereIn('lead_priority', ['high', 'urgent'])
                ->count(),

            'followup_due' => (clone $base)
                ->where('follow_up_required', 1)
                ->whereNotNull('follow_up_date')
                ->whereDate('follow_up_date', '<=', now()->toDateString())
                ->count(),

            'service_due' => (clone $base)
                ->where('retention_tag', 'service_due')
                ->count(),

            'fleet_corporate' => (clone $base)
                ->whereIn('customer_type', ['fleet', 'corporate'])
                ->count(),
        ];
    }

    protected function bucketTitle(?string $bucket): string
    {
        return match ($bucket) {
            'service' => 'Service Request Leads',
            'quote_followup' => 'Quote Follow-up Leads',
            'complaints' => 'Complaint Leads',
            'hot' => 'Hot Leads',
            'high_priority' => 'High Priority Leads',
            'followup_due' => 'Follow-up Due Leads',
            'service_due' => 'Service Due Leads',
            'fleet_corporate' => 'Fleet / Corporate Leads',
            default => 'Open Leads',
        };
    }

    protected function bucketSubtitle(?string $bucket): string
    {
        return match ($bucket) {
            'service' => 'Leads categorized as service requests.',
            'quote_followup' => 'Quote enquiries and quote follow-up retention leads.',
            'complaints' => 'Complaint leads that need fast attention.',
            'hot' => 'High-intent leads marked as hot.',
            'high_priority' => 'High and urgent priority leads.',
            'followup_due' => 'Leads where follow-up is due today or overdue.',
            'service_due' => 'Retention leads tagged for service due.',
            'fleet_corporate' => 'Fleet and corporate customer leads.',
            default => 'New and active leads that need follow-up or qualification.',
        };
    }

    protected function leadCounts(int $companyId): array
    {
        return [
            'open' => Lead::where('company_id', $companyId)
                ->where('is_active', 1)
                ->whereIn('status', [
                    Lead::STATUS_NEW,
                    Lead::STATUS_ATTEMPTING,
                    'contact_on_hold',
                ])
                ->count(),

            'qualified' => Lead::where('company_id', $companyId)
                ->whereIn('status', [
                    Lead::STATUS_QUALIFIED,
                    Lead::STATUS_CONVERTED,
                ])
                ->count(),

            'disqualified' => Lead::where('company_id', $companyId)
                ->whereIn('status', [
                    'disqualified',
                    Lead::STATUS_LOST,
                ])
                ->count(),

            'duplicates' => LeadDuplicate::where('company_id', $companyId)->count(),
        ];
    }

    protected function leadPageMetrics($leads, int $companyId): array
    {
        $leadIds = $leads->getCollection()->pluck('id')->filter()->values();

        $latestLogs = MessageLog::where('company_id', $companyId)
            ->whereIn('lead_id', $leadIds)
            ->where('channel', 'whatsapp')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('lead_id')
            ->map(fn ($logs) => $logs->first());

        $leadScores = [];

        foreach ($leads->getCollection() as $lead) {
            $leadScores[$lead->id] = $this->calculateLeadScore($lead, $companyId, $latestLogs[$lead->id] ?? null);
        }

        return [$leadScores, $latestLogs];
    }

    protected function calculateLeadScore(Lead $lead, int $companyId, ?MessageLog $latestLog = null): int
    {
        $score = $lead->calculateScore();

        if (! $latestLog) {
            $latestLog = MessageLog::where('company_id', $companyId)
                ->where('lead_id', $lead->id)
                ->where('channel', 'whatsapp')
                ->latest()
                ->first();
        }

        if ($latestLog) {
            if ($latestLog->direction === 'in') {
                $score += 25;
            }

            if (in_array($latestLog->provider_status, ['delivered', 'read'], true)) {
                $score += 15;
            }

            if (in_array($latestLog->provider_status, ['failed', 'undelivered', 'error'], true)) {
                $score -= 30;
            }
        }

        return max(0, min(100, $score));
    }

    protected function authorizeCompany(Lead $lead): void
    {
        abort_if((int) $lead->company_id !== $this->companyId(), 403);
    }

    protected function convertToOpportunity(Lead $lead): void
    {
        $client = null;

        if ($lead->phone_norm) {
            $client = Client::where('company_id', $lead->company_id)
                ->where(function ($q) use ($lead) {
                    $q->where('phone_norm', $lead->phone_norm)
                        ->orWhere('phone', $lead->phone_norm);
                })
                ->first();
        }

        if (! $client && $lead->email) {
            $client = Client::where('company_id', $lead->company_id)
                ->where(function ($q) use ($lead) {
                    $q->where('email_norm', mb_strtolower($lead->email))
                        ->orWhere('email', $lead->email);
                })
                ->first();
        }

        if (! $client) {
            $client = Client::create([
                'company_id' => $lead->company_id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone_norm ?: $lead->phone,
                'whatsapp' => $lead->phone_norm ?: $lead->phone,
            ]);
        }

        $lead->update([
            'client_id' => $client->id,
        ]);

        Opportunity::firstOrCreate(
            [
                'lead_id' => $lead->id,
                'company_id' => $lead->company_id,
            ],
            [
                'client_id' => $client->id,
                'title' => 'Lead: ' . ($lead->name ?? 'New Opportunity'),
                'stage' => Opportunity::STAGE_NEW,
                'source' => $lead->source,
            ]
        );

        if ($lead->phone_norm) {
            Lead::where('company_id', $lead->company_id)
                ->where('phone_norm', $lead->phone_norm)
                ->where('id', '!=', $lead->id)
                ->where('is_active', 1)
                ->update(['is_active' => 0]);
        }

        $lead->update([
            'is_active' => 0,
            'status' => Lead::STATUS_CONVERTED,
        ]);
    }
}