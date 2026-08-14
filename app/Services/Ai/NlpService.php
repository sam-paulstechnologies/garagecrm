<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NlpService
{
    /**
     * Classify a bounded historical conversation for review. This is
     * observational only: it cannot select Track, create CRM records, or run
     * an action. A null result tells the caller to use its conservative local
     * evidence rules.
     *
     * @return array{classification:string,classification_confidence:float,classification_reason:string,retention_level:string,retention_confidence:float,retention_reason:string}|null
     */
    public function analyzeHistoryConversation(string $text, int $daysSinceLastMessage): ?array
    {
        $text = trim($text);
        if ($text === '' || $this->apiKey() === '') {
            if ($text !== '') {
                $this->logUnavailable('missing_api_key', 'history_review');
            }

            return null;
        }

        $system = <<<'PROMPT'
You review historical WhatsApp conversations for an automotive garage CRM.
Return JSON only with classification, classification_confidence, classification_reason,
retention_level, retention_confidence, retention_reason.
classification must be one of likely_customer, possible_personal, possible_colleague, unknown.
retention_level must be high, medium, low, or none.
Be conservative. Never infer a completed service, booking, ownership, revenue, mileage,
service interval, visit date, or future need unless explicitly evidenced. Personal and
colleague classifications are suggestions, not facts. Reasons must be concise evidence
summaries, not hidden reasoning or chain-of-thought. Use none for retention unless a
customer/service relationship is supported.
PROMPT;

        try {
            $response = $this->http()->post($this->base().'/chat/completions', [
                'model' => $this->model(),
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => "Days since last message: {$daysSinceLastMessage}\nConversation:\n<<<{$text}>>>"],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.1,
            ])->throw()->json();
            $decoded = json_decode((string) data_get($response, 'choices.0.message.content'), true);
            $classifications = ['likely_customer', 'possible_personal', 'possible_colleague', 'unknown'];
            $retention = ['high', 'medium', 'low', 'none'];
            if (! is_array($decoded)
                || ! in_array($decoded['classification'] ?? null, $classifications, true)
                || ! in_array($decoded['retention_level'] ?? null, $retention, true)) {
                return null;
            }

            return [
                'classification' => $decoded['classification'],
                'classification_confidence' => max(0, min(1, (float) ($decoded['classification_confidence'] ?? 0))),
                'classification_reason' => mb_substr(trim((string) ($decoded['classification_reason'] ?? '')), 0, 240),
                'retention_level' => $decoded['classification'] === 'likely_customer' ? $decoded['retention_level'] : 'none',
                'retention_confidence' => max(0, min(1, (float) ($decoded['retention_confidence'] ?? 0))),
                'retention_reason' => mb_substr(trim((string) ($decoded['retention_reason'] ?? '')), 0, 240),
            ];
        } catch (\Throwable $exception) {
            $this->logUnavailable($this->failureReason($exception), 'history_review');

            return null;
        }
    }

    /**
     * Run observational analysis with cost-safe telemetry. The result contains
     * no prompt text, credentials, or customer identity.
     *
     * @return array{analysis: array, telemetry: array<string, int|string|null>}
     */
    public function analyzeWithTelemetry(string $text, array $context = []): array
    {
        $started = hrtime(true);
        $analysis = $this->analyze($text, $context);

        return [
            'analysis' => $analysis,
            'telemetry' => [
                'provider' => 'openai',
                'model' => $this->model(),
                'input_tokens' => null,
                'output_tokens' => null,
                'total_tokens' => null,
                'estimated_cost_micros' => null,
                'duration_ms' => (int) round((hrtime(true) - $started) / 1_000_000),
            ],
        ];
    }

    /**
     * Main entry: analyze incoming text and return structured JSON.
     */
    public function analyze(string $text, array $context = []): array
    {
        $text = trim($text);

        if ($text === '') {
            return $this->fallback();
        }

        return $this->analyzeViaChat($text, $context);
    }

    /**
     * Generate a SHORT WhatsApp reply respecting business rules
     */
    public function replyText(string $from, string $to, string $body, array $extra = []): string
    {
        $lead = $extra['lead'] ?? [];
        $nlp = $extra['nlp'] ?? null;

        $known = [
            'name' => $lead['name'] ?? null,
            'make_id' => $lead['vehicle_make_id'] ?? null,
            'model_id' => $lead['vehicle_model_id'] ?? null,
            'other_make' => $lead['other_make'] ?? null,
            'other_model' => $lead['other_model'] ?? null,
            'conversation_state' => $lead['conversation_state'] ?? null,
        ];

        $entities = is_array($nlp['entities'] ?? null) ? $nlp['entities'] : [];

        $caps = [
            'offer_pickup_drop' => false,
        ];

        $system = <<<'SYS'
You are a succinct WhatsApp service agent for a UAE auto-garage.

STRICT rules:
- NEVER mention pickup or drop service.
- Ask at most ONE clarifying question.
- If make/model already known do NOT ask again.
- Confirm provided date/time if possible.
- Use short friendly sentences.
- Do not invent prices.
- Time windows allowed: "Morning 8–12" or "Afternoon 2–6".
- Max length 480 characters.

Output ONLY the WhatsApp message text.
SYS;

        $leadSummary = [];

        if ($known['name']) {
            $leadSummary[] = "name={$known['name']}";
        }
        if ($known['make_id'] || $known['other_make']) {
            $leadSummary[] = 'make=known';
        }
        if ($known['model_id'] || $known['other_model']) {
            $leadSummary[] = 'model=known';
        }
        if ($known['conversation_state']) {
            $leadSummary[] = "state={$known['conversation_state']}";
        }

        $nluSummary = [];

        foreach (['vehicle_make', 'vehicle_model', 'vehicle_year', 'preferred_date', 'preferred_time'] as $k) {
            if (! empty($entities[$k])) {
                $nluSummary[] = "$k={$entities[$k]}";
            }
        }

        $user =
            "Customer said: <<<{$body}>>>\n".
            'Lead context: '.($leadSummary ? implode(', ', $leadSummary) : 'none')."\n".
            'Detected entities: '.($nluSummary ? implode(', ', $nluSummary) : 'none')."\n".
            'Capabilities: offer_pickup_drop='.($caps['offer_pickup_drop'] ? 'true' : 'false');

        try {
            if ($this->apiKey() === '') {
                $this->logUnavailable('missing_api_key', 'replyText');

                return 'Thanks! Could you share your preferred day and time window (Morning 8–12 / Afternoon 2–6)?';
            }

            $resp = $this->http()->post(
                $this->base().'/chat/completions',
                [
                    'model' => $this->model(),
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                    'temperature' => 0.2,
                ]
            )->throw()->json();

            $text = trim((string) ($resp['choices'][0]['message']['content'] ?? ''));

            if ($text === '') {
                $text = 'Thanks! Could you share your preferred day and time window (Morning 8–12 / Afternoon 2–6)?';
            }

            return $text;

        } catch (\Throwable $e) {

            $this->logUnavailable($this->failureReason($e), 'replyText');

            return 'Thanks! Could you share your preferred day and time window (Morning 8–12 / Afternoon 2–6)?';
        }
    }

    /**
     * Analyze message using AI
     */
    protected function analyzeViaChat(string $text, array $context = []): array
    {
        $system = 'You are an NLU for a UAE auto garage CRM.
Return JSON ONLY with:
intent, sentiment, confidence, language,
entities{vehicle_make,vehicle_model,vehicle_year,preferred_date,preferred_time,plate,vin,note}.';

        $leadBits = [];

        if (! empty($context['lead'])) {

            $l = $context['lead'];

            if (! empty($l['name'])) {
                $leadBits[] = "name={$l['name']}";
            }
            if (! empty($l['phone'])) {
                $leadBits[] = "phone={$l['phone']}";
            }
            if (! empty($l['last_intent'])) {
                $leadBits[] = "last_intent={$l['last_intent']}";
            }
        }

        $leadLine = $leadBits ? ('Lead context: '.implode(', ', $leadBits)."\n") : '';

        $user =
            "Classify and extract.\n".
            $leadLine.
            "Message: <<<{$text}>>>\n".
            "Rules:\n".
            "- appointment or service request → booking\n".
            "- change time → reschedule\n".
            '- unclear → fallback';

        try {
            if ($this->apiKey() === '') {
                $this->logUnavailable('missing_api_key', 'analyze');

                return $this->fallback();
            }

            $resp = $this->http()->post(
                $this->base().'/chat/completions',
                [
                    'model' => $this->model(),
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.2,
                ]
            )->throw()->json();

            $content = $resp['choices'][0]['message']['content'] ?? null;

            if (is_string($content)) {

                $json = json_decode($content, true);

                if (! is_array($json)) {
                    $json = $this->fallback();
                }

            } else {
                $json = $this->fallback();
            }

            $intent = strtolower($json['intent'] ?? 'fallback');

            if (in_array($intent, ['schedule_service', 'book_service', 'appointment'])) {
                $intent = 'booking';
            }

            return [
                'intent' => $intent,
                'sentiment' => $json['sentiment'] ?? 'neutral',
                'confidence' => isset($json['confidence']) ? (float) $json['confidence'] : 0.7,
                'language' => $json['language'] ?? 'en',
                'entities' => is_array($json['entities'] ?? null) ? $json['entities'] : [],
            ];

        } catch (\Throwable $e) {

            $this->logUnavailable($this->failureReason($e), 'analyze');

            return $this->fallback();
        }
    }

    /**
     * HTTP client
     */
    protected function http()
    {
        $verify = true;

        $bundle = env('CURL_CA_BUNDLE');

        if ($bundle === '0' || strtolower((string) $bundle) === 'false') {
            $verify = false;
        } elseif ($bundle && file_exists($bundle)) {
            $verify = $bundle;
        }

        return Http::withToken($this->apiKey())
            ->timeout($this->timeout())
            ->withOptions(['verify' => $verify]);
    }

    protected function apiKey(): string
    {
        return (string) (config('services.openai.api_key') ?? env('OPENAI_API_KEY', ''));
    }

    protected function base(): string
    {
        return rtrim((string) (config('services.openai.base_url') ?? env('OPENAI_BASE_URL', 'https://api.openai.com/v1')), '/');
    }

    protected function model(): string
    {
        return (string) (config('services.openai.model') ?? env('OPENAI_MODEL', 'gpt-4o-mini'));
    }

    protected function timeout(): int
    {
        return (int) (config('services.openai.timeout') ?? env('OPENAI_TIMEOUT', 20));
    }

    protected function fallback(): array
    {
        return [
            'intent' => 'fallback',
            'sentiment' => 'neutral',
            'confidence' => 0,
            'language' => 'en',
            'entities' => [],
        ];
    }

    protected function failureReason(\Throwable $e): string
    {
        $message = strtolower($e->getMessage());

        if (str_contains($message, '401') || str_contains($message, 'unauthorized')) {
            return 'auth_failed';
        }

        if (str_contains($message, '429')) {
            return 'rate_limited';
        }

        return 'request_failed';
    }

    protected function logUnavailable(string $reason, string $operation): void
    {
        Log::warning('[NlpService] deterministic fallback active', [
            'operation' => $operation,
            'reason' => $reason,
            'base_url_configured' => $this->base() !== '',
            'model_configured' => $this->model() !== '',
            'api_key_configured' => $this->apiKey() !== '',
        ]);
    }
}
