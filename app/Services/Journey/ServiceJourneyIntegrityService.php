<?php

namespace App\Services\Journey;

use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ServiceJourneyIntegrityService
{
    protected const NOTE_PREFIX = 'Auto-created from direct booking';

    public function ensureBookingHasUpstreamJourney(Booking $booking, array $context = []): Booking
    {
        if (! $this->requiredColumnsReady()) {
            return $booking;
        }

        return DB::transaction(function () use ($booking, $context) {
            $booking = Booking::query()
                ->where('company_id', $booking->company_id)
                ->where('id', $booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->lead_id && $booking->opportunity_id) {
                return $booking;
            }

            $client = Client::query()
                ->where('company_id', $booking->company_id)
                ->where('id', $booking->client_id)
                ->first();

            if (! $client) {
                Log::warning('[ServiceJourneyIntegrity] Booking upstream skipped: client missing', [
                    'booking_id' => $booking->id,
                    'company_id' => $booking->company_id,
                    'client_id' => $booking->client_id,
                ]);

                return $booking;
            }

            $ambiguousNotes = [];
            $opportunity = $this->resolveExistingOpportunity($booking);
            $lead = $opportunity?->lead_id
                ? $this->sameCompanyLead((int) $booking->company_id, (int) $opportunity->lead_id)
                : null;

            if (! $lead && $booking->lead_id) {
                $lead = $this->sameCompanyLead((int) $booking->company_id, (int) $booking->lead_id);
            }

            if (! $lead) {
                [$lead, $leadAmbiguity] = $this->resolveOrCreateLead($booking, $client, $context);
                $ambiguousNotes = array_merge($ambiguousNotes, $leadAmbiguity);
            }

            if (! $opportunity) {
                [$opportunity, $opportunityAmbiguity] = $this->resolveOrCreateOpportunity($booking, $client, $lead, $context);
                $ambiguousNotes = array_merge($ambiguousNotes, $opportunityAmbiguity);
            } elseif (! $opportunity->lead_id && $lead) {
                $opportunity->update(['lead_id' => $lead->id]);
            }

            $fields = [];

            if (! $booking->lead_id && $lead) {
                $fields['lead_id'] = $lead->id;
            }

            if (! $booking->opportunity_id && $opportunity) {
                $fields['opportunity_id'] = $opportunity->id;
            }

            if ($fields !== []) {
                $booking->update($fields);
                $booking->refresh();
            }

            if ($ambiguousNotes !== []) {
                Log::info('[ServiceJourneyIntegrity] Booking upstream created with ambiguity note', [
                    'booking_id' => $booking->id,
                    'company_id' => $booking->company_id,
                    'notes' => $ambiguousNotes,
                ]);
            }

            Log::info('[ServiceJourneyIntegrity] Booking upstream ensured', [
                'booking_id' => $booking->id,
                'company_id' => $booking->company_id,
                'lead_id' => $booking->lead_id,
                'opportunity_id' => $booking->opportunity_id,
                'source' => $context['source'] ?? 'booking_flow',
            ]);

            return $booking;
        });
    }

    protected function resolveExistingOpportunity(Booking $booking): ?Opportunity
    {
        if (! $booking->opportunity_id) {
            return null;
        }

        return Opportunity::query()
            ->where('company_id', $booking->company_id)
            ->where('id', $booking->opportunity_id)
            ->first();
    }

    protected function resolveOrCreateLead(Booking $booking, Client $client, array $context): array
    {
        $matches = $this->safeLeadMatches($booking);
        $ambiguityNotes = [];

        if ($matches->count() === 1) {
            return [$matches->first(), $ambiguityNotes];
        }

        if ($matches->count() > 1) {
            $ambiguityNotes[] = 'Multiple safe lead candidates existed; a new lead was created instead of guessing. Candidate IDs: '
                . $matches->pluck('id')->implode(', ');
        }

        return [
            Lead::create($this->leadPayload($booking, $client, $ambiguityNotes, $context)),
            $ambiguityNotes,
        ];
    }

    protected function resolveOrCreateOpportunity(Booking $booking, Client $client, Lead $lead, array $context): array
    {
        $matches = $this->safeOpportunityMatches($booking, $lead);
        $ambiguityNotes = [];

        if ($matches->count() === 1) {
            return [$matches->first(), $ambiguityNotes];
        }

        if ($matches->count() > 1) {
            $ambiguityNotes[] = 'Multiple safe opportunity candidates existed; a new opportunity was created instead of guessing. Candidate IDs: '
                . $matches->pluck('id')->implode(', ');
        }

        return [
            Opportunity::create($this->opportunityPayload($booking, $client, $lead, $ambiguityNotes, $context)),
            $ambiguityNotes,
        ];
    }

    protected function safeLeadMatches(Booking $booking)
    {
        $date = $this->dateValue($booking->booking_date ?: $booking->created_at);
        $windowStart = $date?->copy()->subDays(30);
        $windowEnd = $date?->copy()->addDays(30);
        $service = $this->normalizeText($booking->service_type ?: $booking->name);

        return Lead::query()
            ->where('company_id', $booking->company_id)
            ->where('client_id', $booking->client_id)
            ->where('status', '!=', Lead::STATUS_LOST)
            ->when($windowStart && $windowEnd, function ($query) use ($windowStart, $windowEnd) {
                $query->whereBetween('created_at', [$windowStart->startOfDay(), $windowEnd->endOfDay()]);
            })
            ->get()
            ->filter(function (Lead $lead) use ($service) {
                if ($service === '') {
                    return true;
                }

                return $this->servicesSimilar($service, $lead->service_type)
                    || $this->servicesSimilar($service, $lead->service_category)
                    || $this->servicesSimilar($service, $lead->notes);
            })
            ->filter(function (Lead $lead) use ($booking) {
                $existingOpportunityCount = Opportunity::query()
                    ->where('company_id', $booking->company_id)
                    ->where('lead_id', $lead->id)
                    ->count();

                if ($existingOpportunityCount === 0) {
                    return true;
                }

                return $this->safeOpportunityMatches($booking, $lead)->count() === 1;
            })
            ->sortBy(function (Lead $lead) {
                return array_search($lead->status, [
                    Lead::STATUS_QUALIFIED,
                    Lead::STATUS_CONVERTED,
                    Lead::STATUS_ATTEMPTING,
                    Lead::STATUS_NEW,
                ], true) ?: 99;
            })
            ->values();
    }

    protected function safeOpportunityMatches(Booking $booking, Lead $lead)
    {
        $date = $this->dateValue($booking->booking_date ?: $booking->created_at);
        $windowStart = $date?->copy()->subDays(30);
        $windowEnd = $date?->copy()->addDays(30);
        $service = $this->normalizeText($booking->service_type ?: $booking->name);

        return Opportunity::query()
            ->where('company_id', $booking->company_id)
            ->where('client_id', $booking->client_id)
            ->where('lead_id', $lead->id)
            ->where('stage', '!=', Opportunity::STAGE_CLOSED_LOST)
            ->when($booking->vehicle_id, fn ($query) => $query->where('vehicle_id', $booking->vehicle_id))
            ->when($windowStart && $windowEnd, function ($query) use ($windowStart, $windowEnd) {
                $query->where(function ($dates) use ($windowStart, $windowEnd) {
                    $dates->whereBetween('expected_close_date', [$windowStart->toDateString(), $windowEnd->toDateString()])
                        ->orWhereBetween('created_at', [$windowStart->startOfDay(), $windowEnd->endOfDay()]);
                });
            })
            ->get()
            ->filter(function (Opportunity $opportunity) use ($service) {
                if ($service === '') {
                    return true;
                }

                return $this->servicesSimilar($service, $opportunity->service_type)
                    || $this->servicesSimilar($service, $opportunity->title);
            })
            ->values();
    }

    protected function leadPayload(Booking $booking, Client $client, array $ambiguityNotes, array $context): array
    {
        $phone = $client->whatsapp ?: $client->phone;
        $note = $this->integrityNote($booking, $ambiguityNotes, $context);

        return $this->columnSafe('leads', [
            'company_id' => $booking->company_id,
            'client_id' => $client->id,
            'name' => $client->name ?: $booking->name ?: 'Booking #' . $booking->id,
            'email' => $client->email,
            'phone' => $phone,
            'phone_norm' => Lead::normalizePhone($phone),
            'status' => Lead::STATUS_CONVERTED,
            'source' => 'Manual',
            'service_type' => $booking->service_type ?: $booking->name,
            'assigned_to' => $booking->assigned_to,
            'preferred_channel' => $client->whatsapp ? 'whatsapp' : 'phone',
            'notes' => $note,
            'follow_up_required' => false,
            'is_active' => false,
        ]);
    }

    protected function opportunityPayload(Booking $booking, Client $client, Lead $lead, array $ambiguityNotes, array $context): array
    {
        $stage = $booking->status === Booking::STATUS_CONVERTED_TO_JOB
            ? Opportunity::STAGE_CLOSED_WON
            : Opportunity::STAGE_APPOINTMENT;

        $note = $this->integrityNote($booking, $ambiguityNotes, $context);

        return $this->columnSafe('opportunities', [
            'company_id' => $booking->company_id,
            'client_id' => $client->id,
            'lead_id' => $lead->id,
            'vehicle_id' => $booking->vehicle_id,
            'title' => $booking->name ?: ($booking->service_type ?: 'Booking #' . $booking->id),
            'service_type' => $booking->service_type ?: $booking->name,
            'stage' => $stage,
            'source' => 'Manual Booking',
            'assigned_to' => $booking->assigned_to,
            'priority' => in_array($booking->priority, ['low', 'medium', 'high'], true) ? $booking->priority : 'medium',
            'value' => 0,
            'expected_close_date' => $booking->booking_date,
            'is_converted' => $stage === Opportunity::STAGE_CLOSED_WON,
            'notes' => $note,
        ]);
    }

    protected function integrityNote(Booking $booking, array $ambiguityNotes, array $context): string
    {
        $lines = [
            self::NOTE_PREFIX . " #{$booking->id} to preserve service journey integrity.",
        ];

        if (! empty($context['source'])) {
            $lines[] = 'Source: ' . $context['source'] . '.';
        }

        return implode("\n", array_merge($lines, $ambiguityNotes));
    }

    protected function sameCompanyLead(int $companyId, int $leadId): ?Lead
    {
        return Lead::query()
            ->where('company_id', $companyId)
            ->where('id', $leadId)
            ->first();
    }

    protected function columnSafe(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
            ->toArray();
    }

    protected function requiredColumnsReady(): bool
    {
        return Schema::hasTable('bookings')
            && Schema::hasColumn('bookings', 'lead_id')
            && Schema::hasColumn('bookings', 'opportunity_id')
            && Schema::hasTable('leads')
            && Schema::hasTable('opportunities');
    }

    protected function normalizeText(mixed $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', (string) $value)));
    }

    protected function servicesSimilar(string $left, mixed $right): bool
    {
        $right = $this->normalizeText($right);

        if ($left === '' || $right === '') {
            return false;
        }

        return str_contains($right, $left)
            || str_contains($left, $right)
            || count(array_intersect(explode(' ', $left), explode(' ', $right))) > 0;
    }

    protected function dateValue(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
