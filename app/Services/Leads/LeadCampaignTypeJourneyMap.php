<?php

namespace App\Services\Leads;

use App\Models\LeadCampaignJourneyMapping;
use Illuminate\Support\Facades\Schema;

class LeadCampaignTypeJourneyMap
{
    public const MAP = [
        'New Lead Campaign' => 'new_lead_fast_ack',
        'Service Offer Campaign' => 'service_offer_followup',
        'Retention Campaign' => 'retention_reactivation',
        'Lost Lead Revival Campaign' => 'lost_lead_revival',
        'WhatsApp Campaign' => 'whatsapp_campaign_followup',
        'Meta Lead Form Campaign' => 'meta_lead_instant_ack',
        'Website Form Campaign' => 'website_lead_response',
        'Walk-in / Manual Entry' => 'manual_followup',
        'Referral Campaign' => 'referral_followup',
        'Fleet Campaign' => 'fleet_lead_qualification',
    ];

    private const DEFAULT_META = [
        'New Lead Campaign' => ['label' => 'New Lead Fast ACK', 'trigger' => 'lead.imported.new_lead'],
        'Service Offer Campaign' => ['label' => 'Service Offer Follow-up', 'trigger' => 'lead.imported.service_offer'],
        'Retention Campaign' => ['label' => 'Retention Reactivation', 'trigger' => 'lead.imported.retention'],
        'Lost Lead Revival Campaign' => ['label' => 'Lost Lead Revival', 'trigger' => 'lead.imported.lost_lead_revival'],
        'WhatsApp Campaign' => ['label' => 'WhatsApp Campaign Follow-up', 'trigger' => 'lead.imported.whatsapp_campaign'],
        'Meta Lead Form Campaign' => ['label' => 'Meta Lead Form Instant ACK', 'trigger' => 'lead.imported.meta_lead_form'],
        'Website Form Campaign' => ['label' => 'Website Lead Response', 'trigger' => 'lead.imported.website_form'],
        'Walk-in / Manual Entry' => ['label' => 'Manual Follow-up', 'trigger' => 'lead.imported.manual'],
        'Referral Campaign' => ['label' => 'Referral Follow-up', 'trigger' => 'lead.imported.referral'],
        'Fleet Campaign' => ['label' => 'Fleet Lead Qualification', 'trigger' => 'lead.imported.fleet'],
    ];

    public static function labels(): array
    {
        return array_keys(self::MAP);
    }

    public static function defaults(): array
    {
        return collect(self::MAP)
            ->map(function (string $journeyKey, string $campaignType) {
                return [
                    'campaign_type' => $campaignType,
                    'journey_key' => $journeyKey,
                    'journey_label' => self::DEFAULT_META[$campaignType]['label'] ?? str($campaignType)->replace('Campaign', 'Journey')->trim()->toString(),
                    'journey_trigger_key' => self::DEFAULT_META[$campaignType]['trigger'] ?? 'lead.imported.' . self::lookupKey($campaignType),
                    'is_active' => false,
                    'preview_only' => true,
                    'whatsapp_enabled' => false,
                    'whatsapp_template_name' => null,
                    'followup_template_name' => null,
                    'notes' => null,
                ];
            })
            ->values()
            ->all();
    }

    public static function ensureDefaultsForCompany(int $companyId, ?int $garageId = null, ?int $userId = null): void
    {
        if ($companyId <= 0 || ! self::mappingTableExists()) {
            return;
        }

        foreach (self::defaults() as $default) {
            $mapping = LeadCampaignJourneyMapping::query()
                ->where('company_id', $companyId)
                ->where('campaign_type', $default['campaign_type'])
                ->first();

            if ($mapping) {
                continue;
            }

            LeadCampaignJourneyMapping::create(array_merge($default, [
                'company_id' => $companyId,
                'garage_id' => $garageId,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]));
        }
    }

    public static function resolve(int $companyId, ?string $campaignType, ?int $garageId = null): array
    {
        $label = self::normalize($campaignType);

        if (! $label) {
            return self::emptyResolution($campaignType);
        }

        $mapping = self::mappingFor($companyId, $label, $garageId);

        if ($mapping) {
            return self::resolutionFromMapping($mapping);
        }

        $default = collect(self::defaults())->firstWhere('campaign_type', $label);

        return array_merge($default ?? self::emptyResolution($label), [
            'campaign_type' => $label,
            'mapped' => false,
            'mapping_id' => null,
            'mapping_status' => 'Missing',
            'whatsapp_status' => 'Disabled',
        ]);
    }

    public static function statusFor(int $companyId, ?string $campaignType, ?int $garageId = null): array
    {
        $resolved = self::resolve($companyId, $campaignType, $garageId);

        $mappingStatus = $resolved['mapping_status'] ?? 'Missing';
        $whatsappStatus = $resolved['whatsapp_status'] ?? 'Disabled';

        return [
            'mapping_status' => $mappingStatus,
            'whatsapp_status' => $whatsappStatus,
            'mapped' => (bool) ($resolved['mapped'] ?? false),
            'is_active' => (bool) ($resolved['is_active'] ?? false),
            'preview_only' => (bool) ($resolved['preview_only'] ?? true),
            'whatsapp_enabled' => (bool) ($resolved['whatsapp_enabled'] ?? false),
        ];
    }

    public static function journeyKeyFor(?string $campaignType): ?string
    {
        $label = self::normalize($campaignType);

        return $label ? self::MAP[$label] : null;
    }

    public static function normalize(?string $campaignType): ?string
    {
        $campaignType = trim((string) $campaignType);

        if ($campaignType === '') {
            return null;
        }

        $lookup = self::lookupKey($campaignType);

        foreach (self::MAP as $label => $journeyKey) {
            if ($lookup === self::lookupKey($label) || $lookup === self::lookupKey($journeyKey)) {
                return $label;
            }
        }

        return null;
    }

    public static function lookupKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = str_replace(['&', '+'], ' and ', $value);
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?: '';

        return trim($value, '_');
    }

    private static function mappingFor(int $companyId, string $campaignType, ?int $garageId): ?LeadCampaignJourneyMapping
    {
        if ($companyId <= 0 || ! self::mappingTableExists()) {
            return null;
        }

        return LeadCampaignJourneyMapping::query()
            ->where('company_id', $companyId)
            ->where('campaign_type', $campaignType)
            ->when($garageId, function ($query) use ($garageId) {
                $query->where(function ($garageQuery) use ($garageId) {
                    $garageQuery->whereNull('garage_id')
                        ->orWhere('garage_id', $garageId);
                });
            })
            ->orderByRaw('garage_id is null')
            ->first();
    }

    private static function resolutionFromMapping(LeadCampaignJourneyMapping $mapping): array
    {
        $hasJourneyKey = trim((string) $mapping->journey_key) !== '';
        $mappingStatus = ! $hasJourneyKey
            ? 'Missing'
            : (! $mapping->is_active ? 'Inactive' : ($mapping->preview_only ? 'Preview Only' : 'Mapped'));

        $whatsappStatus = ! $mapping->whatsapp_enabled
            ? 'Disabled'
            : (trim((string) $mapping->whatsapp_template_name) === '' ? 'Template Missing' : ($mapping->preview_only ? 'Preview Only' : 'Enabled'));

        return [
            'mapping_id' => $mapping->id,
            'mapped' => true,
            'campaign_type' => $mapping->campaign_type,
            'journey_key' => $mapping->journey_key,
            'journey_label' => $mapping->journey_label,
            'journey_trigger_key' => $mapping->journey_trigger_key,
            'is_active' => (bool) $mapping->is_active,
            'preview_only' => (bool) $mapping->preview_only,
            'whatsapp_enabled' => (bool) $mapping->whatsapp_enabled,
            'whatsapp_template_name' => $mapping->whatsapp_template_name,
            'followup_template_name' => $mapping->followup_template_name,
            'notes' => $mapping->notes,
            'mapping_status' => $mappingStatus,
            'whatsapp_status' => $whatsappStatus,
        ];
    }

    private static function emptyResolution(?string $campaignType): array
    {
        return [
            'mapping_id' => null,
            'mapped' => false,
            'campaign_type' => $campaignType,
            'journey_key' => null,
            'journey_label' => null,
            'journey_trigger_key' => null,
            'is_active' => false,
            'preview_only' => true,
            'whatsapp_enabled' => false,
            'whatsapp_template_name' => null,
            'followup_template_name' => null,
            'notes' => null,
            'mapping_status' => 'Missing',
            'whatsapp_status' => 'Disabled',
        ];
    }

    private static function mappingTableExists(): bool
    {
        try {
            return Schema::hasTable('lead_campaign_journey_mappings');
        } catch (\Throwable) {
            return false;
        }
    }
}
