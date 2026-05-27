<?php

namespace App\Services\Leads;

use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\LeadDuplicate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadResolver
{
    public function __construct(
        protected LeadClientResolver $clientResolver
    ) {}

    public function resolve(array $data, ?int $companyId = null): ?Lead
    {
        $companyId = (int) ($companyId ?: ($data['company_id'] ?? 0));

        if (! $companyId) {
            Log::warning('[LeadResolver] Missing company_id', [
                'source' => $data['source'] ?? null,
                'external_source' => $data['external_source'] ?? null,
            ]);

            return null;
        }

        $phoneNorm = Lead::normalizePhone($data['phone'] ?? $data['phone_norm'] ?? null);
        $emailNorm = Lead::normalizeEmail($data['email'] ?? null);
        $source = strtolower(trim((string) ($data['source'] ?? 'inbound')));

        if ($source === '') {
            $source = 'inbound';
        }

        try {
            return DB::transaction(function () use ($data, $companyId, $phoneNorm, $emailNorm, $source) {

                /*
                |--------------------------------------------------------------------------
                | 1. Find Active Lead First
                |--------------------------------------------------------------------------
                | Active lead should be reused for WhatsApp conversation continuity.
                */

                $lead = $this->findActiveLead($companyId, $phoneNorm, $emailNorm);

                if ($lead) {
                    Log::info('[LeadResolver] Active lead reused', [
                        'lead_id' => $lead->id,
                        'company_id' => $companyId,
                        'source' => $source,
                    ]);

                    $this->logDuplicateSafely(
                        companyId: $companyId,
                        primaryLeadId: $lead->id,
                        phoneNorm: $phoneNorm,
                        emailNorm: $emailNorm,
                        matchedOn: $phoneNorm ? 'phone' : 'email',
                        reason: 'active_lead_reused'
                    );

                    return $lead;
                }

                /*
                |--------------------------------------------------------------------------
                | 2. Check Last Lead For Awareness Only
                |--------------------------------------------------------------------------
                | If last lead is inactive/converted/lost, create a new lead.
                */

                $oldLead = $this->findLastLead($companyId, $phoneNorm, $emailNorm);

                if ($oldLead) {
                    Log::info('[LeadResolver] Closed/inactive lead found, new lead will be created', [
                        'old_lead_id' => $oldLead->id,
                        'status' => $oldLead->status,
                        'is_active' => $oldLead->is_active,
                        'company_id' => $companyId,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | 3. Resolve Client
                |--------------------------------------------------------------------------
                */

                $client = $this->clientResolver->resolve([
                    'name' => $data['name'] ?? 'Customer',
                    'phone' => $phoneNorm,
                    'email' => $emailNorm,
                    'source' => $source,
                ], $companyId);

                if (! $client) {
                    Log::error('[LeadResolver] Client resolution failed', [
                        'company_id' => $companyId,
                        'has_phone' => (bool) $phoneNorm,
                        'has_email' => (bool) $emailNorm,
                    ]);

                    return null;
                }

                /*
                |--------------------------------------------------------------------------
                | 4. Create Lead
                |--------------------------------------------------------------------------
                | IMPORTANT:
                | Only use values supported by leads.status enum:
                | new, attempting_contact, qualified, converted, lost
                */

                try {
                    $lead = Lead::create([
                        'company_id' => $companyId,
                        'client_id' => $client->id,
                        'name' => $data['name'] ?? $client->name ?? 'Customer',
                        'phone' => $phoneNorm,
                        'phone_norm' => $phoneNorm,
                        'email' => $emailNorm,
                        'email_norm' => $emailNorm,
                        'source' => $source,
                        'status' => $this->initialStatusForSource($source),
                        'preferred_channel' => $source === 'whatsapp' ? 'whatsapp' : ($data['preferred_channel'] ?? null),
                        'conversation_state' => $source === 'whatsapp' ? 'idle' : null,
                        'external_source' => $data['external_source'] ?? $source,
                        'external_id' => $data['external_id'] ?? null,
                        'external_form_id' => $data['external_form_id'] ?? null,
                        'external_payload' => $this->safeExternalPayload($data),
                        'external_received_at' => $data['external_received_at'] ?? now(),
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('[LeadResolver] Create failed, retrying active lead fetch', [
                        'company_id' => $companyId,
                        'has_phone' => (bool) $phoneNorm,
                        'has_email' => (bool) $emailNorm,
                        'source' => $source,
                        'error' => $e->getMessage(),
                    ]);

                    $lead = $this->findActiveLead($companyId, $phoneNorm, $emailNorm);

                    if ($lead) {
                        return $lead;
                    }

                    throw $e;
                }

                Log::info('[LeadResolver] New lead created', [
                    'lead_id' => $lead->id,
                    'company_id' => $companyId,
                    'source' => $source,
                ]);

                /*
                |--------------------------------------------------------------------------
                | 5. Auto Opportunity For WhatsApp Leads
                |--------------------------------------------------------------------------
                | Website flow can create/update opportunity later through conversion service.
                */

                if ($source === 'whatsapp') {
                    Opportunity::firstOrCreate([
                        'lead_id' => $lead->id,
                        'company_id' => $companyId,
                    ], [
                        'client_id' => $client->id,
                        'title' => ($lead->name ?: 'WhatsApp Lead') . ' Opportunity',
                        'stage' => Opportunity::STAGE_NEW,
                        'source' => 'whatsapp',
                        'ai_status' => 'collecting_details',
                    ]);
                }

                return $lead;
            });

        } catch (\Throwable $e) {
            Log::error('[LeadResolver] Lead creation failed', [
                'company_id' => $companyId,
                'has_phone' => (bool) $phoneNorm,
                'has_email' => (bool) $emailNorm,
                'source' => $source,
                'err' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function initialStatusForSource(string $source): string
    {
        return match ($source) {
            'whatsapp' => Lead::STATUS_QUALIFIED,
            default => Lead::STATUS_NEW,
        };
    }

    protected function findActiveLead(int $companyId, ?string $phoneNorm, ?string $emailNorm): ?Lead
    {
        if (! $phoneNorm && ! $emailNorm) {
            return null;
        }

        return Lead::query()
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->where(function ($q) use ($phoneNorm, $emailNorm) {
                if ($phoneNorm) {
                    $q->orWhere('phone_norm', $phoneNorm);
                }

                if ($emailNorm) {
                    $q->orWhere('email_norm', $emailNorm);
                }
            })
            ->latest()
            ->first();
    }

    protected function findLastLead(int $companyId, ?string $phoneNorm, ?string $emailNorm): ?Lead
    {
        if (! $phoneNorm && ! $emailNorm) {
            return null;
        }

        return Lead::query()
            ->where('company_id', $companyId)
            ->where(function ($q) use ($phoneNorm, $emailNorm) {
                if ($phoneNorm) {
                    $q->orWhere('phone_norm', $phoneNorm);
                }

                if ($emailNorm) {
                    $q->orWhere('email_norm', $emailNorm);
                }
            })
            ->latest()
            ->first();
    }

    protected function logDuplicateSafely(
        int $companyId,
        int $primaryLeadId,
        ?string $phoneNorm,
        ?string $emailNorm,
        string $matchedOn,
        string $reason
    ): void {
        try {
            LeadDuplicate::create([
                'company_id' => $companyId,
                'primary_lead_id' => $primaryLeadId,
                'phone' => $phoneNorm,
                'phone_norm' => $phoneNorm,
                'email' => $emailNorm,
                'email_norm' => $emailNorm,
                'matched_on' => $matchedOn,
                'reason' => $reason,
                'detected_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::debug('[LeadResolver] Duplicate log skipped', [
                'lead_id' => $primaryLeadId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function safeExternalPayload(array $data): mixed
    {
        $payload = $data['external_payload'] ?? $data['payload'] ?? null;

        if (! is_array($payload)) {
            return $payload;
        }

        return [
            'source' => $payload['source'] ?? $data['source'] ?? null,
            'external_source' => $payload['external_source'] ?? $data['external_source'] ?? null,
            'provider_message_id' => $payload['provider_message_id'] ?? $payload['sid'] ?? $data['external_id'] ?? null,
            'message_type' => $payload['message_type'] ?? $payload['type'] ?? null,
            'profile_name' => $payload['profile_name'] ?? $data['name'] ?? null,
            'received_at' => $payload['received_at'] ?? now()->toIso8601String(),
        ];
    }
}