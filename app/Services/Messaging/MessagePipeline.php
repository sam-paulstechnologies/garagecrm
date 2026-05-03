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
     * Main entry point for all inbound messages
     */
    public function handle(array $payload): ?array
    {
        $companyId = $payload['company_id'] ?? null;
        $from      = $payload['from'] ?? null;
        $to        = $payload['to'] ?? null;
        $text      = trim((string)($payload['body'] ?? ''));

        if (!$companyId || !$from) {
            Log::warning('[Pipeline] Missing required payload fields', [
                'payload' => $payload
            ]);
            return null;
        }

        $digits = preg_replace('/\D+/', '', $from);

        if ($text === '') {
            Log::info('[Pipeline] Ignoring empty message', [
                'from' => $from
            ]);
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | 1. Resolve Lead
        |--------------------------------------------------------------------------
        */

        $lead = $this->leadResolver->resolve([
            'company_id' => $companyId,
            'phone'      => $from,
            'phone_norm' => $digits,
            'name'       => $payload['profile_name'] ?? 'WhatsApp Lead',
            'source'     => 'whatsapp'
        ]);

        if (!$lead) {

            Log::warning('[Pipeline] Lead resolution failed', [
                'company_id' => $companyId,
                'phone'      => $from
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
            'company_id'         => $companyId,
            'lead_id'            => $lead->id,
            'conversation_id'    => $conversation?->id,
            'from'               => $from,
            'to'                 => $to,
            'body'               => $text,
            'provider_message_id'=> $payload['sid'] ?? null,
            'meta'               => $payload
        ]);

        /*
        |--------------------------------------------------------------------------
        | 4. Route conversation
        |--------------------------------------------------------------------------
        */

        try {

            $response = $this->router->route($lead, $text);

        } catch (\Throwable $e) {

            Log::error('[Pipeline] Router failed', [
                'lead_id' => $lead->id,
                'error'   => $e->getMessage()
            ]);

            return null;
        }

        if (!$response) {

            Log::info('[Pipeline] Router returned no response', [
                'lead_id' => $lead->id
            ]);

            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Return pipeline result
        |--------------------------------------------------------------------------
        */

        return [
            'lead'         => $lead,
            'conversation' => $conversation,
            'response'     => $response
        ];
    }
}