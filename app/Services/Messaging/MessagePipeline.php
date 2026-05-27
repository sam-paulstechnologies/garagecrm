<?php

namespace App\Services\Messaging;

use App\Services\Leads\LeadResolver;
use App\Services\Conversation\ConversationService;
use App\Services\Conversation\MessageLogger;
use App\Services\Conversation\ConversationRouter;
use Illuminate\Support\Facades\Log;

class MessagePipeline
{
    public function __construct(
        protected LeadResolver $leadResolver,
        protected ConversationService $conversationService,
        protected MessageLogger $messageLogger,
        protected ConversationRouter $router
    ) {}

    /**
     * Main entry point for all inbound messages.
     */
    public function handle(array $payload): ?array
    {
        $companyId = (int) ($payload['company_id'] ?? 0);
        $from = $payload['from'] ?? null;
        $to = $payload['to'] ?? null;
        $text = trim((string) ($payload['body'] ?? ''));

        if (! $companyId || ! $from) {
            Log::warning('[Pipeline] Missing required payload fields', [
                'has_company_id' => (bool) $companyId,
                'has_from' => (bool) $from,
                'source' => $payload['source'] ?? null,
                'provider_message_id' => $payload['sid'] ?? $payload['provider_message_id'] ?? null,
            ]);

            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $from);

        if ($text === '') {
            Log::info('[Pipeline] Ignoring empty message', [
                'company_id' => $companyId,
                'provider_message_id' => $payload['sid'] ?? $payload['provider_message_id'] ?? null,
            ]);

            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | 1. Resolve Lead
        |--------------------------------------------------------------------------
        | LeadResolver requires companyId as the second argument.
        |--------------------------------------------------------------------------
        */

        $lead = $this->leadResolver->resolve([
            'company_id' => $companyId,
            'phone' => $from,
            'phone_norm' => $digits,
            'name' => $payload['profile_name'] ?? 'WhatsApp Lead',
            'source' => 'whatsapp',
            'preferred_channel' => 'whatsapp',
            'external_source' => $payload['external_source'] ?? 'whatsapp',
            'external_id' => $payload['sid'] ?? $payload['provider_message_id'] ?? null,
            'external_payload' => $this->safePayloadForLead($payload),
            'external_received_at' => now(),
        ], $companyId);

        if (! $lead) {
            Log::warning('[Pipeline] Lead resolution failed', [
                'company_id' => $companyId,
                'provider_message_id' => $payload['sid'] ?? $payload['provider_message_id'] ?? null,
            ]);

            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Resolve Conversation
        |--------------------------------------------------------------------------
        */

        $conversation = $this->conversationService->resolve(
            $companyId,
            $lead
        );

        /*
        |--------------------------------------------------------------------------
        | 3. Log inbound message
        |--------------------------------------------------------------------------
        */

        $this->messageLogger->logInbound([
            'company_id' => $companyId,
            'lead_id' => $lead->id,
            'conversation_id' => $conversation?->id,
            'from' => $from,
            'to' => $to,
            'body' => $text,
            'provider_message_id' => $payload['sid'] ?? $payload['provider_message_id'] ?? null,
            'meta' => $this->safePayloadForMessageLog($payload),
        ]);

        /*
        |--------------------------------------------------------------------------
        | 4. Route Conversation
        |--------------------------------------------------------------------------
        */

        try {
            $response = $this->router->route($lead, $text);
        } catch (\Throwable $e) {
            Log::error('[Pipeline] Router failed', [
                'lead_id' => $lead->id,
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $response) {
            Log::info('[Pipeline] Router returned no response', [
                'lead_id' => $lead->id,
                'company_id' => $companyId,
            ]);

            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Return pipeline result
        |--------------------------------------------------------------------------
        */

        return [
            'lead' => $lead,
            'conversation' => $conversation,
            'response' => $response,
        ];
    }

    protected function safePayloadForLead(array $payload): array
    {
        return [
            'source' => $payload['source'] ?? 'whatsapp',
            'external_source' => $payload['external_source'] ?? 'whatsapp',
            'provider_message_id' => $payload['sid'] ?? $payload['provider_message_id'] ?? null,
            'message_type' => $payload['message_type'] ?? $payload['type'] ?? null,
            'profile_name' => $payload['profile_name'] ?? null,
            'received_at' => now()->toIso8601String(),
        ];
    }

    protected function safePayloadForMessageLog(array $payload): array
    {
        return [
            'source' => $payload['source'] ?? 'whatsapp',
            'external_source' => $payload['external_source'] ?? 'whatsapp',
            'provider_message_id' => $payload['sid'] ?? $payload['provider_message_id'] ?? null,
            'message_type' => $payload['message_type'] ?? $payload['type'] ?? null,
            'profile_name' => $payload['profile_name'] ?? null,
            'to' => $payload['to'] ?? null,
            'received_at' => now()->toIso8601String(),
        ];
    }
}