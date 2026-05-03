<?php

namespace App\Services\WhatsApp;

use App\Services\Leads\LeadFactory;

class WhatsAppLeadIngestService
{
    public function ingest(array $payload, LeadFactory $factory)
    {
        return $factory->createOrDetectDuplicate([
            'company_id'        => $payload['company_id'],
            'name'              => $payload['name'] ?? 'WhatsApp Lead',
            'phone'             => $payload['from'],
            'preferred_channel' => 'whatsapp',
            'source'            => 'whatsapp',
            'external_source'   => 'whatsapp',
            'external_payload'  => $payload,
        ]);
    }
}
