<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppFromTemplate;
use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\Conversation;
use App\Models\LeadActivityLog;
use App\Models\LeadDuplicate;
use App\Models\LeadSource;
use App\Models\MessageLog;
use App\Models\Shared\Communication;
use App\Models\User;
use App\Services\Leads\LeadResolver;
use App\Services\PhoneNumberService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
        $leadFilters = $this->leadIndexFilters($request);

        $query = $this->baseLeadQuery($companyId, $q)
            ->where('is_active', 1)
            ->whereIn('status', [
                Lead::STATUS_NEW,
                Lead::STATUS_ATTEMPTING,
                Lead::STATUS_HOLD,
            ]);

        $this->applyBucketFilter($query, $bucket);
        $this->applyLeadIndexFilters($query, $leadFilters);

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
            'leadFilters' => $leadFilters,
            'assignedUsers' => User::where('company_id', $companyId)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function qualified(Request $request)
    {
        $companyId = $this->companyId();
        $q = trim((string) $request->get('q', ''));

        $leads = $this->baseLeadQuery($companyId, $q)
            ->whereIn('status', [
                Lead::STATUS_QUALIFIED,
                'converted',
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
            'pageTitle' => 'Qualified Leads',
            'pageSubtitle' => 'Leads qualified into clients/opportunities.',
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
                Lead::STATUS_DISQUALIFIED,
                'lost',
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
            'pageSubtitle' => 'Invalid, duplicate, or non-serviceable leads.',
            'leadCounts' => $this->leadCounts($companyId),
            'bucketCounts' => $this->bucketCounts($companyId),
            'leadScores' => $leadScores,
            'whatsappByLead' => $whatsappByLead,
        ]);
    }

    public function archived(Request $request)
    {
        $companyId = $this->companyId();
        $q = trim((string) $request->get('q', ''));
        $leadFilters = $this->leadIndexFilters($request);

        $query = $this->baseLeadQuery($companyId, $q)
            ->where(function ($query) {
                $query->where('is_active', 0)
                    ->orWhereIn('status', [
                        Lead::STATUS_DISQUALIFIED,
                        'lost',
                    ]);
            });

        $this->applyLeadIndexFilters($query, $leadFilters);

        $leads = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        [$leadScores, $whatsappByLead] = $this->leadPageMetrics($leads, $companyId);

        return view('admin.leads.index', [
            'leads' => $leads,
            'q' => $q,
            'bucket' => '',
            'pageMode' => 'archived',
            'pageTitle' => 'Archived Leads',
            'pageSubtitle' => 'Inactive, archived, or disqualified leads excluded from the active lead bucket.',
            'leadCounts' => $this->leadCounts($companyId),
            'bucketCounts' => $this->bucketCounts($companyId),
            'leadScores' => $leadScores,
            'whatsappByLead' => $whatsappByLead,
            'leadFilters' => $leadFilters,
            'assignedUsers' => User::where('company_id', $companyId)
                ->orderBy('name')
                ->get(['id', 'name']),
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
            'status' => ['nullable', Rule::in(array_keys($this->leadStatusOptions()))],
            'status_sub_status' => 'nullable|string|max:100',
            'status_reason' => 'nullable|string|max:1000',
            'follow_up_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'lead_score_reason' => 'nullable|string',
            'last_contacted_at' => 'nullable|date',
            'preferred_channel' => 'nullable|in:email,phone,whatsapp',

            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
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
            'external_source' => 'nullable|string|max:100',
        ]);

        $data['company_id'] = $companyId;
        $data['status'] = Lead::normalizeStatus($data['status'] ?? Lead::STATUS_NEW);
        $data = array_merge($data, $this->validateStatusContext($request, $data['status']));
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
                'status' => $data['status'],
                'status_sub_status' => $data['status_sub_status'] ?? null,
                'status_reason' => $data['status_reason'] ?? null,
                'follow_up_at' => $data['follow_up_at'] ?? null,
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

            if ($lead->fresh()->status === Lead::STATUS_QUALIFIED) {
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

        $communications = Communication::where('company_id', $companyId)
            ->where('lead_id', $lead->id)
            ->latest()
            ->paginate(10);

        $messageLogs = MessageLog::where('company_id', $companyId)
            ->where('lead_id', $lead->id)
            ->latest()
            ->paginate(10);

        $latestLog = MessageLog::where('company_id', $companyId)
            ->where('lead_id', $lead->id)
            ->where('channel', 'whatsapp')
            ->latest()
            ->first();

        $leadScore = $this->calculateLeadScore($lead, $companyId, $latestLog);
        $phoneService = app(PhoneNumberService::class);
        $phoneE164 = $phoneService->normalizeToE164($lead->phone_norm ?: $lead->phone);
        $conversation = $this->conversationForLead($lead, $phoneE164);

        return view('admin.leads.show', [
            'lead' => $lead,
            'communications' => $communications,
            'messageLogs' => $messageLogs,
            'leadScore' => $leadScore,
            'scoreInsights' => $this->leadScoreInsights($lead, $leadScore, $latestLog),
            'statusPath' => $this->leadStatusPath(),
            'activityTimeline' => $this->leadActivityTimeline($lead, $communications->getCollection(), $messageLogs->getCollection()),
            'phoneE164' => $phoneE164,
            'telUrl' => $phoneService->buildTelUrl($lead->phone_norm ?: $lead->phone),
            'conversation' => $conversation,
            'whatsappInboxUrl' => $this->whatsappInboxUrl($lead, $conversation, $phoneE164),
            'leadStatusOptions' => $this->leadStatusOptions(),
            'contactOnHoldSubStatuses' => $this->contactOnHoldSubStatuses(),
            'disqualifiedSubStatuses' => $this->disqualifiedSubStatuses(),
            'leadSources' => LeadSource::where('company_id', $companyId)
                ->orderBy('name')
                ->get(['id', 'name']),
            'assignedUsers' => User::where('company_id', $companyId)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function edit(Lead $lead)
    {
        $this->authorizeCompany($lead);

        $companyId = $this->companyId();
        $lead->loadMissing('opportunity');

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

    public function updateStatus(Request $request, Lead $lead)
    {
        $this->authorizeCompany($lead);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys($this->leadStatusOptions()))],
        ]);

        $status = Lead::normalizeStatus($data['status']);
        $context = $this->validateStatusContext($request, $status);
        try {
            $result = $this->transitionLeadStatus($lead, $status, $context);
        } catch (\Throwable $e) {
            Log::warning('Lead status transition failed', [
                'lead_id' => $lead->id,
                'company_id' => $lead->company_id,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.leads.show', $lead)
                ->with('error', 'Lead status update failed: ' . $e->getMessage());
        }

        if (($result['redirect_route'] ?? null) && ($result['redirect_model'] ?? null)) {
            return redirect()
                ->route($result['redirect_route'], $result['redirect_model'])
                ->with($result['type'], $result['message']);
        }

        return redirect()
            ->route('admin.leads.show', $lead)
            ->with($result['type'], $result['message']);
    }

    public function quickUpdate(Request $request, Lead $lead)
    {
        $this->authorizeCompany($lead);

        $companyId = $this->companyId();
        $field = (string) $request->input('field');
        $allowedFields = array_keys($this->leadQuickEditableFields($companyId));

        $request->validate([
            'field' => ['required', Rule::in($allowedFields)],
        ]);

        $rules = $this->leadQuickEditableFields($companyId)[$field]['rules'];

        $data = $request->validate([
            'value' => $rules,
        ]);

        $before = $lead->replicate();
        $updates = $this->leadQuickUpdatePayload($field, $data['value'] ?? null);

        $lead->update($updates);

        $this->logLeadFieldChanges($lead->fresh(), $before, array_keys($updates));

        return redirect()
            ->to(route('admin.leads.show', $lead) . '#lead-field-' . str_replace('_', '-', $field))
            ->with('success', 'Lead field updated.');
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
            'status' => ['nullable', Rule::in(array_keys($this->leadStatusOptions()))],
            'status_sub_status' => 'nullable|string|max:100',
            'status_reason' => 'nullable|string|max:1000',
            'follow_up_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'preferred_channel' => 'nullable|in:email,phone,whatsapp',

            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

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
            'campaign_name' => 'nullable|string|max:150',
            'retention_tag' => 'nullable|string|max:80',
            'external_source' => 'nullable|string|max:100',
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

        $data['conversation_data'] = $memory;

        if (! empty($data['status'])) {
            $data['status'] = Lead::normalizeStatus($data['status']);
            $data = array_merge($data, $this->validateStatusContext($request, $data['status']));
        }

        $before = $lead->replicate();

        $lead->update($data);

        $this->logLeadFieldChanges($lead->fresh(), $before, array_keys($data));

        if (($data['status'] ?? null) === Lead::STATUS_QUALIFIED) {
            try {
                $opportunity = $this->convertToOpportunity($lead->fresh());

                return redirect()
                    ->route('admin.opportunities.show', $opportunity)
                    ->with('success', 'Lead qualified and opportunity opened.');
            } catch (\Throwable $e) {
                Log::warning('Lead qualification from edit failed', [
                    'lead_id' => $lead->id,
                    'company_id' => $lead->company_id,
                    'error' => $e->getMessage(),
                ]);

                return redirect()
                    ->route('admin.leads.edit', $lead)
                    ->withInput()
                    ->with('error', 'Lead qualification failed: ' . $e->getMessage());
            }
        }

        return redirect()
            ->route('admin.leads.show', $lead)
            ->with('success', 'Lead updated successfully.');
    }

    public function toggleHot(Lead $lead)
    {
        $this->authorizeCompany($lead);

        $oldValue = (bool) $lead->is_hot;

        $lead->update([
            'is_hot' => ! $oldValue,
        ]);

        $this->logLeadActivity($lead->fresh(), 'updated', 'is_hot', $oldValue ? 'yes' : 'no', $lead->is_hot ? 'yes' : 'no');

        return back()->with('success', 'Lead hot status updated.');
    }

    public function destroy(Lead $lead)
    {
        $this->authorizeCompany($lead);

        $oldStatus = $lead->status;

        $lead->update([
            'is_active' => 0,
            'status' => Lead::STATUS_DISQUALIFIED,
        ]);

        $this->logLeadActivity($lead->fresh(), 'archived', 'status', $oldStatus, Lead::STATUS_DISQUALIFIED);

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

    protected function leadIndexFilters(Request $request): array
    {
        return [
            'date_range' => $request->get('date_range', 'all_time'),
            'from_date' => $request->get('from_date'),
            'to_date' => $request->get('to_date'),
            'lead_source' => $request->get('lead_source', 'all'),
            'assigned_user' => $request->get('assigned_user', 'all'),
            'service_type' => $request->get('service_type', 'all'),
            'customer_type' => $request->get('customer_type', 'all'),
        ];
    }

    protected function applyLeadIndexFilters($query, array $filters): void
    {
        [$from, $to] = $this->leadDateRange($filters);

        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        if (($filters['lead_source'] ?? 'all') !== 'all') {
            $source = trim((string) $filters['lead_source']);

            $query->where(function ($sub) use ($source) {
                $sub->where('source', 'like', "%{$source}%")
                    ->orWhere('external_source', 'like', "%{$source}%")
                    ->orWhereHas('leadSource', function ($sourceQuery) use ($source) {
                        $sourceQuery->where('type', 'like', "%{$source}%")
                            ->orWhere('name', 'like', "%{$source}%");
                    });
            });
        }

        if (($filters['assigned_user'] ?? 'all') !== 'all' && is_numeric($filters['assigned_user'])) {
            $query->where('assigned_to', (int) $filters['assigned_user']);
        }

        if (($filters['service_type'] ?? 'all') !== 'all') {
            $serviceType = trim((string) $filters['service_type']);
            $serviceLabel = str_replace('_', ' ', $serviceType);

            $query->where(function ($sub) use ($serviceType, $serviceLabel) {
                $sub->where('service_type', 'like', "%{$serviceType}%")
                    ->orWhere('service_type', 'like', "%{$serviceLabel}%")
                    ->orWhere('service_category', $serviceType)
                    ->orWhere('service_category', $serviceLabel);
            });
        }

        if (($filters['customer_type'] ?? 'all') !== 'all') {
            $customerType = trim((string) $filters['customer_type']);

            if ($customerType === 'returning') {
                $customerType = 'existing';
            }

            $query->where('customer_type', $customerType);
        }
    }

    protected function leadDateRange(array $filters): array
    {
        return match ((string) ($filters['date_range'] ?? 'all_time')) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'last_7_days' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'custom' => [
                $this->parseLeadFilterDate($filters['from_date'] ?? null)?->startOfDay(),
                $this->parseLeadFilterDate($filters['to_date'] ?? null)?->endOfDay(),
            ],
            default => [null, null],
        };
    }

    protected function parseLeadFilterDate(?string $date): ?\Illuminate\Support\Carbon
    {
        if (! filled($date)) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($date);
        } catch (\Throwable) {
            return null;
        }
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
                Lead::STATUS_HOLD,
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
                    Lead::STATUS_HOLD,
                ])
                ->count(),

            'qualified' => Lead::where('company_id', $companyId)
                ->whereIn('status', [
                    Lead::STATUS_QUALIFIED,
                    'converted',
                ])
                ->count(),

            'disqualified' => Lead::where('company_id', $companyId)
                ->whereIn('status', [
                    Lead::STATUS_DISQUALIFIED,
                    'lost',
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

    protected function leadStatusPath(): array
    {
        return collect($this->leadStatusOptions())
            ->map(fn (string $label, string $value) => [
                'label' => $label,
                'value' => $value,
                'stored' => $value,
            ])
            ->values()
            ->all();
    }

    protected function leadStatusOptions(): array
    {
        return [
            Lead::STATUS_NEW => 'New',
            Lead::STATUS_ATTEMPTING => 'Attempting Contact',
            Lead::STATUS_HOLD => 'Contact On Hold',
            Lead::STATUS_QUALIFIED => 'Qualified',
            Lead::STATUS_DISQUALIFIED => 'Disqualified',
        ];
    }

    protected function contactOnHoldSubStatuses(): array
    {
        return [
            'call_back_requested' => 'Call back requested',
            'customer_requested_later' => 'Customer requested later',
            'waiting_for_customer_response' => 'Waiting for customer response',
            'awaiting_vehicle_details' => 'Awaiting vehicle details',
            'awaiting_service_confirmation' => 'Awaiting service confirmation',
            'awaiting_estimate_approval' => 'Awaiting estimate approval',
            'other' => 'Other',
        ];
    }

    protected function disqualifiedSubStatuses(): array
    {
        return [
            'not_interested' => 'Not interested',
            'wrong_number' => 'Wrong number',
            'duplicate' => 'Duplicate',
            'unreachable_after_attempts' => 'Unreachable after multiple attempts',
            'out_of_service_area' => 'Out of service area',
            'service_not_offered' => 'Service not offered',
            'price_not_accepted' => 'Price not accepted',
            'already_serviced_elsewhere' => 'Already serviced elsewhere',
            'spam_or_test' => 'Spam / test lead',
            'other' => 'Other',
        ];
    }

    protected function validateStatusContext(Request $request, string $status): array
    {
        if ($status === Lead::STATUS_HOLD) {
            $data = $request->validate([
                'status_sub_status' => ['required', Rule::in(array_keys($this->contactOnHoldSubStatuses()))],
                'follow_up_at' => [
                    Rule::requiredIf(fn () => in_array($request->input('status_sub_status'), ['call_back_requested', 'customer_requested_later'], true)),
                    'nullable',
                    'date',
                ],
                'status_reason' => [
                    Rule::requiredIf(fn () => $request->input('status_sub_status') === 'other'),
                    'nullable',
                    'string',
                    'max:1000',
                ],
            ]);

            return [
                'status_sub_status' => $data['status_sub_status'],
                'status_reason' => $data['status_reason'] ?? null,
                'follow_up_at' => $data['follow_up_at'] ?? null,
                'follow_up_required' => filled($data['follow_up_at'] ?? null),
            ];
        }

        if ($status === Lead::STATUS_DISQUALIFIED) {
            $data = $request->validate([
                'status_sub_status' => ['required', Rule::in(array_keys($this->disqualifiedSubStatuses()))],
                'status_reason' => [
                    Rule::requiredIf(fn () => $request->input('status_sub_status') === 'other'),
                    'nullable',
                    'string',
                    'max:1000',
                ],
            ]);

            return [
                'status_sub_status' => $data['status_sub_status'],
                'status_reason' => $data['status_reason'] ?? null,
                'follow_up_at' => null,
                'follow_up_required' => false,
            ];
        }

        return [
            'status_sub_status' => null,
            'status_reason' => null,
            'follow_up_at' => null,
        ];
    }

    protected function transitionLeadStatus(Lead $lead, string $requestedStatus, array $context = []): array
    {
        $oldStatus = (string) $lead->status;
        $newStatus = Lead::normalizeStatus($requestedStatus);

        if ($newStatus === Lead::STATUS_QUALIFIED) {
            $hadOpportunity = Opportunity::where('company_id', $lead->company_id)
                ->where('lead_id', $lead->id)
                ->exists();

            $opportunity = DB::transaction(fn () => $this->convertToOpportunity($lead->fresh()));

            $freshLead = $lead->fresh();
            $this->logLeadActivity($freshLead, 'status_changed', 'status', $oldStatus, $freshLead->status, [
                'requested_status' => $requestedStatus,
                'conversion_flow' => 'convert_to_opportunity',
                'opportunity' => $hadOpportunity ? 'already_exists' : 'created',
            ]);

            return [
                'type' => 'success',
                'message' => $hadOpportunity
                    ? 'Lead is already qualified. Existing opportunity opened.'
                    : 'Lead qualified and opportunity created.',
                'redirect_route' => 'admin.opportunities.show',
                'redirect_model' => $opportunity,
            ];
        }

        $lead->update(array_merge([
            'status' => $newStatus,
        ], $context));

        $this->logLeadActivity($lead->fresh(), 'status_changed', 'status', $oldStatus, $newStatus, [
            'requested_status' => $requestedStatus,
            'status_sub_status' => $context['status_sub_status'] ?? null,
            'status_reason' => $context['status_reason'] ?? null,
            'follow_up_at' => $context['follow_up_at'] ?? null,
        ]);

        return [
            'type' => 'success',
            'message' => $oldStatus === $newStatus ? 'Lead status is already up to date.' : 'Lead status updated.',
        ];
    }

    protected function leadQuickEditableFields(int $companyId): array
    {
        return [
            'name' => ['rules' => ['required', 'string', 'max:255']],
            'phone' => ['rules' => ['nullable', 'string', 'max:20']],
            'source' => ['rules' => ['nullable', 'string', 'max:100']],
            'service_type' => ['rules' => ['nullable', 'string', 'max:100']],
            'service_category' => ['rules' => ['nullable', 'string', 'max:50']],
            'vehicle_make' => ['rules' => ['nullable', 'string', 'max:100']],
            'vehicle_model' => ['rules' => ['nullable', 'string', 'max:100']],
            'vehicle_year' => ['rules' => ['nullable', 'integer', 'min:1900', 'max:2100']],
            'plate_number' => ['rules' => ['nullable', 'string', 'max:50']],
            'lead_source_id' => [
                'rules' => [
                    'nullable',
                    Rule::exists('lead_sources', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
                ],
            ],
            'assigned_to' => [
                'rules' => [
                    'nullable',
                    Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
                ],
            ],
            'notes' => ['rules' => ['nullable', 'string']],
            'campaign_name' => ['rules' => ['nullable', 'string', 'max:150']],
            'external_source' => ['rules' => ['nullable', 'string', 'max:100']],
            'preferred_channel' => ['rules' => ['nullable', Rule::in(['email', 'phone', 'whatsapp'])]],
        ];
    }

    protected function leadQuickUpdatePayload(string $field, mixed $value): array
    {
        if (in_array($field, ['lead_source_id', 'assigned_to', 'vehicle_year'], true)) {
            return [$field => filled($value) ? (int) $value : null];
        }

        return [$field => filled($value) ? $value : null];
    }

    protected function leadScoreInsights(Lead $lead, int $score, ?MessageLog $latestLog = null): array
    {
        $reasons = [];

        if ($lead->lead_score_reason) {
            $reasons[] = $lead->lead_score_reason;
        }

        if ($lead->is_hot || $lead->lead_temperature === 'hot') {
            $reasons[] = 'Marked as hot or high-intent.';
        }

        if (in_array($lead->lead_priority, ['high', 'urgent'], true)) {
            $reasons[] = 'Priority is ' . str_replace('_', ' ', $lead->lead_priority) . '.';
        }

        if ($lead->follow_up_required) {
            $reasons[] = $lead->follow_up_date && $lead->follow_up_date->isPast()
                ? 'Follow-up is due or overdue.'
                : 'Follow-up is required.';
        }

        if ($lead->service_type || $lead->service_category) {
            $reasons[] = 'Service request details are available.';
        }

        if ($lead->vehicle_label || $lead->vehicle_make || $lead->vehicle_model) {
            $reasons[] = 'Vehicle context is available.';
        }

        if ($latestLog?->direction === 'in') {
            $reasons[] = 'Recent inbound WhatsApp activity was found.';
        }

        if ($latestLog && in_array($latestLog->provider_status, ['failed', 'undelivered', 'error'], true)) {
            $reasons[] = 'Latest WhatsApp delivery status needs attention.';
        }

        if (! $reasons) {
            $reasons[] = 'Score justification based on available lead fields.';
        }

        $label = $score >= 75 ? 'Hot' : ($score >= 45 ? 'Warm' : 'Cold');
        $nextAction = match (true) {
            $lead->status === Lead::STATUS_NEW => 'Start contact attempt and confirm the service request.',
            $lead->follow_up_required => 'Complete the scheduled follow-up.',
            $latestLog?->direction === 'in' => 'Reply in the WhatsApp inbox.',
            $score >= 75 => 'Prioritize this lead for immediate qualification.',
            default => 'Review details and choose the next contact step.',
        };

        return [
            'label' => $label,
            'reasons' => array_values(array_unique($reasons)),
            'next_action' => $nextAction,
            'source_label' => 'Score justification based on available lead fields',
        ];
    }

    protected function leadActivityTimeline(Lead $lead, $communications, $messageLogs): array
    {
        $items = [];

        $logs = LeadActivityLog::query()
            ->with('user:id,name')
            ->where('company_id', $lead->company_id)
            ->where('lead_id', $lead->id)
            ->latest()
            ->limit(30)
            ->get();

        foreach ($logs as $log) {
            $items[] = [
                'timestamp' => $log->created_at,
                'actor' => $log->user?->name ?? 'System',
                'action' => Str::headline($log->action),
                'field' => $log->field ? Str::headline($log->field) : null,
                'old' => $log->old_value,
                'new' => $log->new_value,
                'source' => strtoupper((string) ($log->source ?? 'ui')),
            ];
        }

        $items[] = [
            'timestamp' => $lead->created_at,
            'actor' => 'System',
            'action' => 'Lead Created',
            'field' => null,
            'old' => null,
            'new' => $lead->name,
            'source' => $lead->source ?: 'CRM',
        ];

        if ($lead->updated_at && $lead->created_at && $lead->updated_at->gt($lead->created_at)) {
            $items[] = [
                'timestamp' => $lead->updated_at,
                'actor' => 'System',
                'action' => 'Lead Updated',
                'field' => null,
                'old' => null,
                'new' => 'Latest saved snapshot',
                'source' => 'CRM',
            ];
        }

        foreach ($communications as $communication) {
            $items[] = [
                'timestamp' => $communication->communication_date ?? $communication->created_at,
                'actor' => 'CRM',
                'action' => 'Communication Logged',
                'field' => $communication->communication_type ?? 'Communication',
                'old' => null,
                'new' => Str::limit((string) ($communication->content ?? ''), 90),
                'source' => 'UI',
            ];
        }

        foreach ($messageLogs as $messageLog) {
            $items[] = [
                'timestamp' => $messageLog->created_at,
                'actor' => $messageLog->is_ai ? 'AI Assistant' : 'WhatsApp',
                'action' => $messageLog->direction === 'in' ? 'WhatsApp Received' : 'WhatsApp Sent',
                'field' => $messageLog->provider_status,
                'old' => null,
                'new' => Str::limit((string) ($messageLog->body ?? ''), 90),
                'source' => strtoupper((string) ($messageLog->source ?? 'whatsapp')),
            ];
        }

        foreach ($items as &$item) {
            if ($item['timestamp'] && ! $item['timestamp'] instanceof \DateTimeInterface) {
                try {
                    $item['timestamp'] = \Illuminate\Support\Carbon::parse($item['timestamp']);
                } catch (\Throwable) {
                    $item['timestamp'] = null;
                }
            }
        }
        unset($item);

        usort($items, fn ($a, $b) => optional($b['timestamp'])->timestamp <=> optional($a['timestamp'])->timestamp);

        return array_slice($items, 0, 40);
    }

    protected function conversationForLead(Lead $lead, ?string $phoneE164): ?Conversation
    {
        return Conversation::query()
            ->where('company_id', $lead->company_id)
            ->where(function ($query) use ($lead, $phoneE164) {
                $query->where('lead_id', $lead->id);

                if ($phoneE164) {
                    $query->orWhere('customer_phone', $phoneE164);
                }
            })
            ->orderByDesc('last_message_at')
            ->first();
    }

    protected function whatsappInboxUrl(Lead $lead, ?Conversation $conversation, ?string $phoneE164): string
    {
        $params = array_filter([
            'conversation' => $conversation?->id,
            'lead_id' => $lead->id,
            'phone' => $phoneE164,
        ]);

        return route('admin.inbox.index') . ($params ? '?' . http_build_query($params) : '');
    }

    protected function logLeadFieldChanges(Lead $lead, Lead $before, array $fields): void
    {
        $ignored = ['conversation_data', 'conversation_state'];

        foreach ($fields as $field) {
            if (in_array($field, $ignored, true)) {
                continue;
            }

            $old = $before->{$field} ?? null;
            $new = $lead->{$field} ?? null;

            if ((string) $old === (string) $new) {
                continue;
            }

            $this->logLeadActivity($lead, $field === 'status' ? 'status_changed' : 'updated', $field, $old, $new);
        }
    }

    protected function logLeadActivity(Lead $lead, string $action, ?string $field = null, mixed $oldValue = null, mixed $newValue = null, array $metadata = []): void
    {
        LeadActivityLog::create([
            'company_id' => $lead->company_id,
            'lead_id' => $lead->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'field' => $field,
            'old_value' => $this->activityValue($field, $oldValue),
            'new_value' => $this->activityValue($field, $newValue),
            'source' => 'ui',
            'metadata' => $metadata ?: null,
        ]);
    }

    protected function activityValue(?string $field, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (in_array($field, ['phone', 'phone_norm', 'email', 'email_norm'], true)) {
            $value = (string) $value;
            return strlen($value) <= 4 ? 'masked' : str_repeat('*', max(0, strlen($value) - 4)) . substr($value, -4);
        }

        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return Str::limit((string) $value, 180, '...');
    }
    protected function authorizeCompany(Lead $lead): void
    {
        abort_if((int) $lead->company_id !== $this->companyId(), 403);
    }

    protected function convertToOpportunity(Lead $lead): Opportunity
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

        $opportunity = Opportunity::firstOrCreate(
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

        $this->logLeadActivity($lead->fresh(), $opportunity->wasRecentlyCreated ? 'opportunity_created' : 'opportunity_reused', 'opportunity_id', null, (string) $opportunity->id, [
            'source' => 'Lead qualification',
        ]);

        if ($lead->phone_norm) {
            Lead::where('company_id', $lead->company_id)
                ->where('phone_norm', $lead->phone_norm)
                ->where('id', '!=', $lead->id)
                ->where('is_active', 1)
                ->update(['is_active' => 0]);
        }

        $lead->update([
            'is_active' => 0,
            'status' => Lead::STATUS_QUALIFIED,
            'status_sub_status' => null,
            'status_reason' => null,
            'follow_up_at' => null,
        ]);

        return $opportunity->fresh();
    }
}
