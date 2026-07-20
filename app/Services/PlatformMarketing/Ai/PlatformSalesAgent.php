<?php

namespace App\Services\PlatformMarketing\Ai;

use App\Models\PlatformMarketing\PlatformMarketingAiRun;
use App\Models\PlatformMarketing\PlatformMarketingConversation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlatformSalesAgent
{
    public function __construct(
        private PlatformSalesPromptBuilder $promptBuilder,
        private PlatformSalesResponseValidator $validator,
        private PlatformSalesFallback $fallback
    ) {
    }

    public function respond(PlatformMarketingConversation $conversation, string $inboundBody): array
    {
        if ($this->apiKey() === '') {
            return $this->fallbackWithRun($conversation, $inboundBody, 'missing_api_key');
        }

        try {
            $response = Http::withToken($this->apiKey())
                ->timeout((int) config('services.openai.timeout', 20))
                ->post(rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/').'/chat/completions', [
                    'model' => config('services.openai.model', 'gpt-4o-mini'),
                    'messages' => $this->promptBuilder->messages($conversation, $inboundBody),
                    'temperature' => 0.2,
                ])
                ->throw()
                ->json();

            $body = $this->validator->clean((string) data_get($response, 'choices.0.message.content', ''));
            $usage = $response['usage'] ?? [];

            PlatformMarketingAiRun::query()->create([
                'conversation_id' => $conversation->id,
                'prospect_id' => $conversation->prospect_id,
                'model' => (string) config('services.openai.model', 'gpt-4o-mini'),
                'input_tokens' => (int) ($usage['prompt_tokens'] ?? 0),
                'output_tokens' => (int) ($usage['completion_tokens'] ?? 0),
                'status' => 'completed',
                'metadata' => ['prompt_version' => 'platform-sales-v1'],
            ]);

            return [
                'body' => $body,
                'state' => $conversation->state,
                'qualification_status' => $conversation->qualification_status,
                'source' => 'openai',
            ];
        } catch (\Throwable $e) {
            $reason = str_contains(strtolower($e->getMessage()), '401') ? 'auth_failed' : 'request_failed';

            Log::warning('[PlatformMarketing][AI] deterministic fallback active', [
                'conversation_id' => $conversation->id,
                'reason' => $reason,
                'api_key_configured' => $this->apiKey() !== '',
            ]);

            return $this->fallbackWithRun($conversation, $inboundBody, $reason);
        }
    }

    private function fallbackWithRun(PlatformMarketingConversation $conversation, string $inboundBody, string $reason): array
    {
        PlatformMarketingAiRun::query()->create([
            'conversation_id' => $conversation->id,
            'prospect_id' => $conversation->prospect_id,
            'model' => (string) config('services.openai.model', 'gpt-4o-mini'),
            'status' => 'fallback',
            'failure_reason' => $reason,
            'metadata' => ['prompt_version' => 'platform-sales-v1'],
        ]);

        return array_merge($this->fallback->reply($conversation, $inboundBody), [
            'source' => 'fallback',
        ]);
    }

    private function apiKey(): string
    {
        return (string) config('services.openai.api_key', '');
    }
}
