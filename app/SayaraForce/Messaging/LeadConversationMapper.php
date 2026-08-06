<?php

namespace App\SayaraForce\Messaging;

/**
 * Extraction seam for the existing SayaraForce lead/conversation workflow.
 * Phase 1 intentionally delegates durable processing to ProcessInboundWhatsApp.
 */
class LeadConversationMapper
{
    public function normalisePhone(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?: '';
    }
}
