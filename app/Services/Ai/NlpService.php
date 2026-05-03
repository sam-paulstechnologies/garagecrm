<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NlpService
{
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
        $nlp  = $extra['nlp']  ?? null;

        $known = [
            'name'               => $lead['name'] ?? null,
            'make_id'            => $lead['vehicle_make_id'] ?? null,
            'model_id'           => $lead['vehicle_model_id'] ?? null,
            'other_make'         => $lead['other_make'] ?? null,
            'other_model'        => $lead['other_model'] ?? null,
            'conversation_state' => $lead['conversation_state'] ?? null,
        ];

        $entities = is_array($nlp['entities'] ?? null) ? $nlp['entities'] : [];

        $caps = [
            'offer_pickup_drop' => false,
        ];

        $system = <<<SYS
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

        if ($known['name']) $leadSummary[] = "name={$known['name']}";
        if ($known['make_id'] || $known['other_make']) $leadSummary[] = "make=known";
        if ($known['model_id'] || $known['other_model']) $leadSummary[] = "model=known";
        if ($known['conversation_state']) $leadSummary[] = "state={$known['conversation_state']}";

        $nluSummary = [];

        foreach (['vehicle_make','vehicle_model','vehicle_year','preferred_date','preferred_time'] as $k) {
            if (!empty($entities[$k])) {
                $nluSummary[] = "$k={$entities[$k]}";
            }
        }

        $user =
            "Customer said: <<<{$body}>>>\n".
            "Lead context: ".($leadSummary ? implode(', ', $leadSummary) : 'none')."\n".
            "Detected entities: ".($nluSummary ? implode(', ', $nluSummary) : 'none')."\n".
            "Capabilities: offer_pickup_drop=".($caps['offer_pickup_drop'] ? 'true' : 'false');

        try {

            $resp = $this->http()->post(
                $this->base().'/chat/completions',
                [
                    'model' => $this->model(),
                    'messages' => [
                        ['role'=>'system','content'=>$system],
                        ['role'=>'user','content'=>$user]
                    ],
                    'temperature' => 0.2
                ]
            )->throw()->json();

            $text = trim((string)($resp['choices'][0]['message']['content'] ?? ''));

            if ($text === '') {
                $text = "Thanks! Could you share your preferred day and time window (Morning 8–12 / Afternoon 2–6)?";
            }

            return $text;

        } catch (\Throwable $e) {

            Log::warning('[NlpService.replyText] fallback: '.$e->getMessage());

            return "Thanks! Could you share your preferred day and time window (Morning 8–12 / Afternoon 2–6)?";
        }
    }

    /**
     * Analyze message using AI
     */
    protected function analyzeViaChat(string $text, array $context = []): array
    {
        $system = "You are an NLU for a UAE auto garage CRM.
Return JSON ONLY with:
intent, sentiment, confidence, language,
entities{vehicle_make,vehicle_model,vehicle_year,preferred_date,preferred_time,plate,vin,note}.";

        $leadBits = [];

        if (!empty($context['lead'])) {

            $l = $context['lead'];

            if (!empty($l['name']))  $leadBits[] = "name={$l['name']}";
            if (!empty($l['phone'])) $leadBits[] = "phone={$l['phone']}";
            if (!empty($l['last_intent'])) $leadBits[] = "last_intent={$l['last_intent']}";
        }

        $leadLine = $leadBits ? ("Lead context: ".implode(', ', $leadBits)."\n") : "";

        $user =
            "Classify and extract.\n".
            $leadLine.
            "Message: <<<{$text}>>>\n".
            "Rules:\n".
            "- appointment or service request → booking\n".
            "- change time → reschedule\n".
            "- unclear → fallback";

        try {

            $resp = $this->http()->post(
                $this->base().'/chat/completions',
                [
                    'model'=>$this->model(),
                    'messages'=>[
                        ['role'=>'system','content'=>$system],
                        ['role'=>'user','content'=>$user],
                    ],
                    'response_format'=>['type'=>'json_object'],
                    'temperature'=>0.2
                ]
            )->throw()->json();

            $content = $resp['choices'][0]['message']['content'] ?? null;

            if (is_string($content)) {

                $json = json_decode($content, true);

                if (!is_array($json)) {
                    $json = $this->fallback();
                }

            } else {
                $json = $this->fallback();
            }

            $intent = strtolower($json['intent'] ?? 'fallback');

            if (in_array($intent, ['schedule_service','book_service','appointment'])) {
                $intent = 'booking';
            }

            return [
                'intent' => $intent,
                'sentiment' => $json['sentiment'] ?? 'neutral',
                'confidence' => isset($json['confidence']) ? (float)$json['confidence'] : 0.7,
                'language' => $json['language'] ?? 'en',
                'entities' => is_array($json['entities'] ?? null) ? $json['entities'] : []
            ];

        } catch (\Throwable $e) {

            Log::warning('[NlpService] Chat failed '.$e->getMessage());

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

        if ($bundle === '0' || strtolower((string)$bundle) === 'false') {
            $verify = false;
        } elseif ($bundle && file_exists($bundle)) {
            $verify = $bundle;
        }

        return Http::withToken($this->apiKey())
            ->timeout($this->timeout())
            ->withOptions(['verify'=>$verify]);
    }

    protected function apiKey(): string
    {
        return (string)(config('services.openai.api_key') ?? env('OPENAI_API_KEY',''));
    }

    protected function base(): string
    {
        return rtrim((string)(config('services.openai.base_url') ?? env('OPENAI_BASE_URL','https://api.openai.com/v1')),'/');
    }

    protected function model(): string
    {
        return (string)(config('services.openai.model') ?? env('OPENAI_MODEL','gpt-4o-mini'));
    }

    protected function timeout(): int
    {
        return (int)(config('services.openai.timeout') ?? env('OPENAI_TIMEOUT',20));
    }

    protected function fallback(): array
    {
        return [
            'intent' => 'fallback',
            'sentiment' => 'neutral',
            'confidence' => 0,
            'language' => 'en',
            'entities' => []
        ];
    }
}