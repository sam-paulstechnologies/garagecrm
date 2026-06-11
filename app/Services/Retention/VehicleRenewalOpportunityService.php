<?php

namespace App\Services\Retention;

use App\Models\Client\Client;
use App\Models\Vehicle\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VehicleRenewalOpportunityService
{
    public const INSURANCE_RENEWAL = 'insurance_renewal_due';
    public const MULKIA_RENEWAL = 'mulkia_renewal_due';
    public const INSPECTION_RENEWAL = 'inspection_renewal_due';

    public function opportunitiesForClient(Client $client, bool $includeUpcoming = true): Collection
    {
        $client->loadMissing(['vehicles.make', 'vehicles.model']);

        return $client->vehicles
            ->flatMap(fn (Vehicle $vehicle) => $this->opportunitiesForVehicle($vehicle, $client))
            ->when(! $includeUpcoming, fn (Collection $items) => $items->reject(
                fn (array $item) => ($item['status_code'] ?? null) === 'upcoming'
            ))
            ->sortBy(fn (array $item) => [
                $item['priority'] ?? 9,
                $item['sort_date'] ?? '9999-12-31',
                $item['vehicle_id'] ?? 0,
            ])
            ->values();
    }

    public function nextForClient(Client $client, bool $includeUpcoming = true): ?array
    {
        return $this->opportunitiesForClient($client, $includeUpcoming)->first();
    }

    public function audienceForCompany(int $companyId, string $segmentKey): Collection
    {
        $rule = $this->ruleForSegment($segmentKey);

        if (! $rule) {
            return collect();
        }

        return Vehicle::query()
            ->where('company_id', $companyId)
            ->whereNotNull($rule['field'])
            ->with(['client', 'make', 'model'])
            ->get()
            ->map(function (Vehicle $vehicle) use ($rule) {
                if (! $vehicle->client) {
                    return null;
                }

                return $this->opportunityForRule($vehicle, $vehicle->client, $rule);
            })
            ->filter()
            ->reject(fn (array $item) => ($item['status_code'] ?? null) === 'upcoming')
            ->sortBy(fn (array $item) => [
                $item['priority'] ?? 9,
                $item['sort_date'] ?? '9999-12-31',
                $item['vehicle_id'] ?? 0,
            ])
            ->unique('client_id')
            ->map(fn (array $item) => $this->formatAudienceItem($item))
            ->values();
    }

    public function rules(): array
    {
        return [
            [
                'field' => 'insurance_expiry_date',
                'segment_key' => self::INSURANCE_RENEWAL,
                'segment_label' => 'Insurance Renewal Due',
            ],
            [
                'field' => 'registration_expiry_date',
                'segment_key' => self::MULKIA_RENEWAL,
                'segment_label' => 'Mulkia Renewal Due',
            ],
            [
                'field' => 'inspection_expiry_date',
                'segment_key' => self::INSPECTION_RENEWAL,
                'segment_label' => 'Inspection Renewal Due',
            ],
        ];
    }

    protected function opportunitiesForVehicle(Vehicle $vehicle, Client $client): Collection
    {
        return collect($this->rules())
            ->map(fn (array $rule) => $this->opportunityForRule($vehicle, $client, $rule))
            ->filter()
            ->values();
    }

    protected function opportunityForRule(Vehicle $vehicle, Client $client, array $rule): ?array
    {
        $expiryDate = $vehicle->{$rule['field']};

        if (! $expiryDate) {
            return null;
        }

        $today = today();
        $expiry = Carbon::parse($expiryDate)->startOfDay();
        $suggested = $expiry->copy()->subDays(30);

        if ($expiry->lt($today)) {
            $statusCode = 'overdue';
            $statusLabel = 'Overdue';
            $followUp = $today->copy();
            $priority = 0;
        } elseif ($suggested->lte($today->copy()->addDays(30))) {
            $statusCode = 'due_soon';
            $statusLabel = 'Due Soon';
            $followUp = $suggested->lt($today) ? $today->copy() : $suggested->copy();
            $priority = 1;
        } else {
            $statusCode = 'upcoming';
            $statusLabel = 'Upcoming';
            $followUp = $suggested->copy();
            $priority = 2;
        }

        $vehicleName = $this->vehicleName($vehicle);

        return [
            'priority' => $priority,
            'sort_date' => $priority === 0 ? $expiry->toDateString() : $followUp->toDateString(),
            'state' => 'suggested',
            'status_label' => $statusLabel,
            'status_code' => $statusCode,
            'segment_label' => $rule['segment_label'],
            'segment_code' => $rule['segment_key'],
            'follow_up_date' => $followUp->toDateString(),
            'suggested_message_date' => $suggested->toDateString(),
            'expiry_date' => $expiry->toDateString(),
            'vehicle_id' => $vehicle->id,
            'vehicle_label' => $vehicleName,
            'client_id' => $client->id,
            'client_name' => $this->clientName($client),
            'client_phone' => $client->phone ?? $client->whatsapp ?? null,
            'client_email' => $client->email,
            'channel' => $this->preferredChannel($client),
            'message' => $this->messageFor($this->clientName($client), $vehicleName, $expiry, $rule['segment_key']),
            'source_label' => 'Vehicle renewal dates',
            'safety_note' => 'Suggestion only. No message has been scheduled or sent.',
        ];
    }

    protected function ruleForSegment(string $segmentKey): ?array
    {
        return collect($this->rules())->firstWhere('segment_key', $segmentKey);
    }

    protected function formatAudienceItem(array $item): array
    {
        return [
            'type' => 'client',
            'id' => $item['client_id'],
            'name' => $item['client_name'],
            'phone' => $item['client_phone'],
            'email' => $item['client_email'],
            'status' => $item['status_label'],
            'reason' => $item['segment_label'] . ' - ' . $item['status_label'],
            'source' => $item['vehicle_label'],
            'last_activity' => $item['expiry_date'],
            'segment_key' => $item['segment_code'],
            'follow_up_date' => $item['follow_up_date'],
            'expiry_date' => $item['expiry_date'],
            'vehicle_id' => $item['vehicle_id'],
        ];
    }

    protected function preferredChannel(Client $client): string
    {
        $channel = strtolower((string) ($client->preferred_channel ?? ''));

        if (in_array($channel, ['whatsapp', 'phone', 'email'], true)) {
            return $channel === 'whatsapp' ? 'WhatsApp' : Str::headline($channel);
        }

        return filled($client->whatsapp) ? 'WhatsApp' : (filled($client->phone) ? 'Phone' : 'Email');
    }

    protected function vehicleName(Vehicle $vehicle): string
    {
        $name = trim(implode(' ', array_filter([
            $vehicle->make?->name,
            $vehicle->model?->name,
        ])));

        return $name ?: 'your vehicle';
    }

    protected function clientName(Client $client): string
    {
        return $client->name
            ?? $client->full_name
            ?? $client->customer_name
            ?? $client->first_name
            ?? ('Client #' . $client->id);
    }

    protected function messageFor(string $clientName, string $vehicleName, Carbon $expiryDate, string $segmentKey): string
    {
        $formattedExpiry = $expiryDate->format('d M Y');

        return match ($segmentKey) {
            self::INSURANCE_RENEWAL => "Hi {$clientName}, your {$vehicleName} insurance is coming up for renewal on {$formattedExpiry}. Would you like us to help with a quick vehicle check before renewal?",
            self::MULKIA_RENEWAL => "Hi {$clientName}, your {$vehicleName} registration/Mulkia renewal is coming up on {$formattedExpiry}. Would you like us to help with inspection and renewal preparation?",
            self::INSPECTION_RENEWAL => "Hi {$clientName}, your {$vehicleName} inspection is coming up on {$formattedExpiry}. Would you like us to help with a pre-inspection check?",
            default => "Hi {$clientName}, your {$vehicleName} may have an upcoming renewal on {$formattedExpiry}. Would you like us to help prepare?",
        };
    }
}
