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
        if ($text === '') return $this->fallback();

        return $this->analyzeViaChat($text, $context);
    }

    /**
     * Generate a SHORT WhatsApp reply that respects business rules
     * and avoids asking for things we already know.
     *
     * @param string $from  E164 of customer
     * @param string $to    E164 of our WA number
     * @param string $body  Customer message
     * @param array  $extra Optional: ['lead' => [...], 'nlp' => [...]]
     */
    public function replyText(string $from, string $to, string $body, array $extra = []): string
    {
        $lead = $extra['lead'] ?? [];
        $nlp  = $extra['nlp']  ?? null;

        // Known facts from DB (lead) – keep it lightweight
        $known = [
            'name'               => $lead['name']            ?? null,
            'make_id'            => $lead['vehicle_make_id'] ?? null,
            'model_id'           => $lead['vehicle_model_id']?? null,
            'other_make'         => $lead['other_make']      ?? null,
            'other_model'        => $lead['other_model']     ?? null,
            'conversation_state' => $lead['conversation_state'] ?? null,
        ];

        // Entities detected just now by NLU (if caller passed it)
        $entities = is_array($nlp['entities'] ?? null) ? $nlp['entities'] : [];

        // Capabilities / business rules
        $caps = [
            'offer_pickup_drop' => false, // hard off
        ];

        $system = <<<SYS
You are a succinct WhatsApp service agent for a UAE auto-garage.
STRICT rules:
- NEVER mention pickup or drop service (it is not offered).
- Ask at most ONE clarifying question at a time.
- If the message already includes (or we already know) MAKE and MODEL, do NOT ask for them again.
- If the user already proposed a date/time, try to confirm it or offer one close alternative if ambiguous.
- If year is given, do not ask again. If missing AND truly needed, ask once (optional).
- Prefer short, friendly sentences (<= 2 lines). No emojis, no markdown.
- Do not invent prices, offers, or unavailable services.
- If time window is needed, use only these: "Morning 8–12" or "Afternoon 2–6".
- Keep total length under 480 characters.
Your ONLY output is the WhatsApp message text (no JSON, no markup).
SYS;

        // Summarize what we already know so the model doesn’t repeat questions
        $leadSummary = [];
        if ($known['name'])                           $leadSummary[] = "name={$known['name']}";
        if ($known['make_id'] || $known['other_make'])  $leadSummary[] = "make=known";
        if ($known['model_id'] || $known['other_model'])$leadSummary[] = "model=known";
        if ($known['conversation_state'])               $leadSummary[] = "state={$known['conversation_state']}";

        // What the NLU caught in this message
        $nluSummary = [];
        foreach (['vehicle_make','vehicle_model','vehicle_year','preferred_date','preferred_time'] as $k) {
            if (!empty($entities[$k])) $nluSummary[] = "$k={$entities[$k]}";
        }

        $user = "Customer said: <<<{$body}>>>\n"
              . "Lead context: " . ($leadSummary ? implode(', ', $leadSummary) : 'none') . "\n"
              . "Detected entities: " . ($nluSummary ? implode(', ', $nluSummary) : 'none') . "\n"
              . "Capabilities: offer_pickup_drop=" . ($caps['offer_pickup_drop'] ? 'true' : 'false') . "\n"
              . "Task: Write the exact reply text.\n"
              . "- If make/model are known (either from context or message), do NOT ask again.\n"
              . "- If user proposed a time (e.g., \"Monday 4 PM\"), confirm or ask ONE precise follow-up if needed.\n"
              . "- If time not given, politely ask for preferred day + 'Morning 8–12' or 'Afternoon 2–6'.\n";

        try {
            $resp = $this->http()->post($this->base().'/chat/completions', [
                'model'       => $this->model(),
                'messages'    => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user',   'content' => $user],
                ],
                'temperature' => 0.2,
            ])->throw()->json();

            $text = trim((string)($resp['choices'][0]['message']['content'] ?? ''));
            if ($text === '') $text = "Thanks! Could you share your preferred day and time window (Morning 8–12 / Afternoon 2–6)?";
            return $text;
        } catch (\Throwable $e) {
            Log::warning('[NlpService.replyText] fallback: '.$e->getMessage());
            return "Thanks! Could you share your preferred day and time window (Morning 8–12 / Afternoon 2–6)?";
        }
    }

    /* ---------------- internal: analyze via Chat ---------------- */

    protected function analyzeViaChat(string $text, array $context = []): array
    {
        $system = "You are an NLU for a UAE auto-garage CRM. "
                . "Extract user intent and entities from WhatsApp-like messages. "
                . "Return ONLY JSON with keys: intent, sentiment, confidence, language, "
                . "entities{vehicle_make,vehicle_model,vehicle_year,preferred_date,preferred_time,plate,vin,note}. "
                . "If the user wants to schedule service, set intent='booking'. No prose, no markdown.";

        $leadBits = [];
        if (!empty($context['lead'])) {
            $l = $context['lead'];
            if (!empty($l['name']))        $leadBits[] = "name={$l['name']}";
            if (!empty($l['phone']))       $leadBits[] = "phone={$l['phone']}";
            if (!empty($l['last_intent'])) $leadBits[] = "last_intent={$l['last_intent']}";
        }
        $leadLine = $leadBits ? ("Lead context: " . implode(', ', $leadBits) . "\n") : "";

        $user = "Task: Classify and extract.\n"
              . $leadLine
              . "Message: <<<{$text}>>>\n"
              . "Notes:\n"
              . "- If brand/model/year present → could be vehicle_info or booking depending on phrasing.\n"
              . "- If asking for time/date/appointment → booking.\n"
              . "- If changing time/date → reschedule.\n"
              . "- If unclear → fallback.";

        try {
            $resp = $this->http()->post($this->base().'/chat/completions', [
                'model'           => $this->model(),
                'messages'        => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user',   'content' => $user],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature'     => 0.2,
            ])->throw()->json();

            $content = $resp['choices'][0]['message']['content'] ?? null;
            $json    = is_string($content) ? json_decode($content, true) : null;

            if (is_array($json)) {
                if (($json['intent'] ?? null) === 'schedule_service') {
                    $json['intent'] = 'booking';
                }
                $json['intent']     = $json['intent']     ?? 'fallback';
                $json['sentiment']  = $json['sentiment']  ?? 'neutral';
                $json['confidence'] = isset($json['confidence']) ? (float)$json['confidence'] : 0.7;
                $json['language']   = $json['language']   ?? 'en';
                $json['entities']   = is_array($json['entities'] ?? null) ? $json['entities'] : [];

                if (isset($json['entities']['vehicle_year']) && is_int($json['entities']['vehicle_year'])) {
                    $json['entities']['vehicle_year'] = (string)$json['entities']['vehicle_year'];
                }
                return $json;
            }

            Log::warning('[NlpService] Chat returned non-decodable content; using fallback.');
            return $this->fallback();
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::warning('[NlpService] Chat HTTP failed', [
                'status' => optional($e->response)->status(),
                'body'   => optional($e->response)->body(),
                'msg'    => $e->getMessage(),
            ]);
            return $this->fallback();
        } catch (\Throwable $e) {
            Log::warning('[NlpService] Chat call failed: '.$e->getMessage());
            return $this->fallback();
        }
    }

    /* ---------------- HTTP + config helpers ---------------- */

    protected function http()
    {
        $verify = true;
        $bundle = env('CURL_CA_BUNDLE');
        if (is_string($bundle) && ($bundle === '0' || strtolower($bundle) === 'false')) {
            $verify = false;
        } elseif ($bundle && is_string($bundle) && file_exists($bundle)) {
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
        return (int) (config('services.openai.timeout') ?? (int) env('OPENAI_TIMEOUT', 20));
    }

    protected function fallback(): array
    {
        return [
            'intent'     => 'fallback',
            'sentiment'  => 'neutral',
            'confidence' => 0.0,
            'language'   => 'en',
            'entities'   => new \stdClass(),
        ];
    }
}
