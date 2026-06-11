<?php

namespace App\Services\AudienceSegmentation;

use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\Booking\Booking;
use App\Models\Job\Job;
use App\Services\Retention\VehicleRenewalOpportunityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AudienceSegmentationAudienceService
{
    public function __construct(
        protected VehicleRenewalOpportunityService $vehicleRenewalOpportunityService
    ) {
    }

    public function getAudienceForSegment(string $segmentKey, int $companyId): Collection
    {
        return match ($segmentKey) {
            'new_lead_conversation' => $this->newLeadConversation($companyId),
            'follow_up_required' => $this->followUpRequired($companyId),
            'high_intent_lead' => $this->highIntentLead($companyId),
            'general_service_retention' => $this->generalServiceRetention($companyId),
            'inactive_customer' => $this->inactiveCustomer($companyId),
            'lost_lead_winback' => $this->lostLeadWinback($companyId),
            'job_completed_feedback' => $this->jobCompletedFeedback($companyId),
            'promotion_eligible' => $this->promotionEligible($companyId),
            'repeat_customer' => $this->repeatCustomer($companyId),
            VehicleRenewalOpportunityService::INSURANCE_RENEWAL => $this->vehicleRenewalAudience(VehicleRenewalOpportunityService::INSURANCE_RENEWAL, $companyId),
            VehicleRenewalOpportunityService::MULKIA_RENEWAL => $this->vehicleRenewalAudience(VehicleRenewalOpportunityService::MULKIA_RENEWAL, $companyId),
            VehicleRenewalOpportunityService::INSPECTION_RENEWAL => $this->vehicleRenewalAudience(VehicleRenewalOpportunityService::INSPECTION_RENEWAL, $companyId),
            default => collect(),
        };
    }

    public function getAudienceCountForSegment(string $segmentKey, int $companyId): int
    {
        return $this->getAudienceForSegment($segmentKey, $companyId)->count();
    }

    protected function newLeadConversation(int $companyId): Collection
    {
        return $this->leadBase($companyId)
            ->where(function (Builder $query) {
                $query->whereIn('status', ['New', 'new'])
                    ->orWhereNull('status');
            })
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn ($lead) => $this->formatLead($lead, 'New lead captured'));
    }

    protected function followUpRequired(int $companyId): Collection
    {
        return $this->leadBase($companyId)
            ->where(function (Builder $query) {
                $query->whereIn('status', [
                    'New',
                    'new',
                    'Attempting Contact',
                    'attempting_contact',
                    'Contact on Hold',
                    'contact_on_hold',
                    'Follow Up',
                    'follow_up',
                ]);
            })
            ->where('created_at', '<=', now()->subHours(24))
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn ($lead) => $this->formatLead($lead, 'No response / follow-up pending'));
    }

    protected function highIntentLead(int $companyId): Collection
    {
        return $this->leadBase($companyId)
            ->where(function (Builder $query) {
                $query->whereIn('status', [
                    'Qualified',
                    'qualified',
                    'Appointment',
                    'appointment',
                    'Offer',
                    'offer',
                    'Attempting Contact',
                    'attempting_contact',
                ]);

                if (Schema::hasColumn('leads', 'conversation_state')) {
                    $query->orWhereIn('conversation_state', [
                        'awaiting_vehicle',
                        'awaiting_timeslot',
                        'booking_intent',
                        'high_intent',
                    ]);
                }

                if (Schema::hasColumn('leads', 'notes')) {
                    $query->orWhere('notes', 'like', '%price%')
                        ->orWhere('notes', 'like', '%booking%')
                        ->orWhere('notes', 'like', '%service%')
                        ->orWhere('notes', 'like', '%pickup%');
                }
            })
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn ($lead) => $this->formatLead($lead, 'High booking/service intent'));
    }

    protected function generalServiceRetention(int $companyId): Collection
    {
        if (! class_exists(Job::class)) {
            return collect();
        }

        return Job::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['Completed', 'completed', 'closed', 'Closed'])
            ->where('created_at', '<=', now()->subMonths(6))
            ->with(['client'])
            ->latest()
            ->limit(100)
            ->get()
            ->map(function ($job) {
                $client = $job->client ?? null;

                return [
                    'type' => 'client',
                    'id' => $client?->id ?? $job->client_id,
                    'name' => $this->clientName($client),
                    'phone' => $client->phone ?? $client->mobile ?? $client->whatsapp_number ?? null,
                    'email' => $client->email ?? null,
                    'reason' => 'Last completed service is older than 6 months',
                    'source' => 'Job #' . $job->id,
                    'last_activity' => optional($job->created_at)->format('d M Y'),
                ];
            })
            ->unique(fn ($item) => $item['type'] . '-' . $item['id'])
            ->values();
    }

    protected function inactiveCustomer(int $companyId): Collection
    {
        return Client::query()
            ->where('company_id', $companyId)
            ->where('created_at', '<=', now()->subDays(90))
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn ($client) => [
                'type' => 'client',
                'id' => $client->id,
                'name' => $this->clientName($client),
                'phone' => $client->phone ?? $client->mobile ?? $client->whatsapp_number ?? null,
                'email' => $client->email ?? null,
                'reason' => 'No recent activity detected in the last 90 days',
                'source' => 'Client record',
                'last_activity' => optional($client->updated_at ?? $client->created_at)->format('d M Y'),
            ]);
    }

    protected function lostLeadWinback(int $companyId): Collection
    {
        return $this->leadBase($companyId)
            ->where(function (Builder $query) {
                $query->whereIn('status', [
                    'Lost',
                    'lost',
                    'Closed Lost',
                    'closed_lost',
                    'Disqualified',
                    'disqualified',
                ]);

                if (Schema::hasColumn('leads', 'is_active')) {
                    $query->orWhere('is_active', 0);
                }
            })
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn ($lead) => $this->formatLead($lead, 'Lead marked lost / disqualified'));
    }

    protected function jobCompletedFeedback(int $companyId): Collection
    {
        if (! class_exists(Job::class)) {
            return collect();
        }

        return Job::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['Completed', 'completed', 'closed', 'Closed'])
            ->with(['client'])
            ->latest()
            ->limit(100)
            ->get()
            ->map(function ($job) {
                $client = $job->client ?? null;

                return [
                    'type' => 'client',
                    'id' => $client?->id ?? $job->client_id,
                    'name' => $this->clientName($client),
                    'phone' => $client->phone ?? $client->mobile ?? $client->whatsapp_number ?? null,
                    'email' => $client->email ?? null,
                    'reason' => 'Job completed and ready for feedback message',
                    'source' => 'Job #' . $job->id,
                    'last_activity' => optional($job->updated_at ?? $job->created_at)->format('d M Y'),
                ];
            })
            ->unique(fn ($item) => $item['type'] . '-' . $item['id'])
            ->values();
    }

    protected function promotionEligible(int $companyId): Collection
    {
        return Client::query()
            ->where('company_id', $companyId)
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn ($client) => [
                'type' => 'client',
                'id' => $client->id,
                'name' => $this->clientName($client),
                'phone' => $client->phone ?? $client->mobile ?? $client->whatsapp_number ?? null,
                'email' => $client->email ?? null,
                'reason' => 'Active customer eligible for campaign/promotional messaging',
                'source' => 'Client record',
                'last_activity' => optional($client->updated_at ?? $client->created_at)->format('d M Y'),
            ]);
    }

    protected function repeatCustomer(int $companyId): Collection
    {
        if (! class_exists(Job::class)) {
            return collect();
        }

        return Job::query()
            ->selectRaw('client_id, COUNT(*) as completed_jobs, MAX(updated_at) as last_completed_at')
            ->where('company_id', $companyId)
            ->whereNotNull('client_id')
            ->whereIn('status', ['Completed', 'completed', 'closed', 'Closed'])
            ->groupBy('client_id')
            ->havingRaw('COUNT(*) >= 2')
            ->with(['client'])
            ->limit(100)
            ->get()
            ->map(function ($row) {
                $client = $row->client ?? null;

                return [
                    'type' => 'client',
                    'id' => $client?->id ?? $row->client_id,
                    'name' => $this->clientName($client),
                    'phone' => $client->phone ?? $client->mobile ?? $client->whatsapp_number ?? null,
                    'email' => $client->email ?? null,
                    'reason' => 'Customer has ' . $row->completed_jobs . ' completed jobs',
                    'source' => 'Completed jobs',
                    'last_activity' => $row->last_completed_at
                        ? date('d M Y', strtotime($row->last_completed_at))
                        : null,
                ];
            });
    }

    protected function vehicleRenewalAudience(string $segmentKey, int $companyId): Collection
    {
        return $this->vehicleRenewalOpportunityService->audienceForCompany($companyId, $segmentKey);
    }

    protected function leadBase(int $companyId): Builder
    {
        $query = Lead::query()->where('company_id', $companyId);

        if (Schema::hasColumn('leads', 'is_active')) {
            $query->where(function (Builder $q) {
                $q->where('is_active', 1)
                    ->orWhereNull('is_active');
            });
        }

        return $query;
    }

    protected function formatLead($lead, string $reason): array
    {
        return [
            'type' => 'lead',
            'id' => $lead->id,
            'name' => $this->leadName($lead),
            'phone' => $lead->phone ?? $lead->mobile ?? $lead->whatsapp_number ?? null,
            'email' => $lead->email ?? null,
            'reason' => $reason,
            'source' => $lead->source ?? $lead->external_source ?? 'Lead record',
            'last_activity' => optional($lead->updated_at ?? $lead->created_at)->format('d M Y'),
        ];
    }

    protected function leadName($lead): string
    {
        return $lead->name
            ?? $lead->full_name
            ?? $lead->customer_name
            ?? $lead->first_name
            ?? ('Lead #' . $lead->id);
    }

    protected function clientName($client): string
    {
        if (! $client) {
            return 'Unknown Client';
        }

        return $client->name
            ?? $client->full_name
            ?? $client->customer_name
            ?? $client->first_name
            ?? ('Client #' . $client->id);
    }
}
