<?php

namespace App\Jobs\Middleware;

use App\Services\Ai\AiPolicyService;
use Illuminate\Support\Facades\Log;

/**
 * Enforces company AI policy for inbound WhatsApp jobs.
 *  - Blocks forbidden intents/topics.
 *  - Routes handoff when required.
 *  - Allows continuation only if intent is allowed.
 */
class EnforceAiPolicy
{
    public function handle($job, $next)
    {
        try {
            $companyId = (int) ($job->companyId ?? 1);
            $policy = new AiPolicyService($companyId);

            if (!$policy->enabled()) {
                Log::info('[AI][Policy] Disabled, skipping enforcement.', ['company_id' => $companyId]);
                return $next($job);
            }

            // Basic AI output structure assumed on job instance
            $nlp = $job->nlp ?? null;
            if (!$nlp || !is_array($nlp)) {
                Log::warning('[AI][Policy] No NLP data found, allowing by default.');
                return $next($job);
            }

            $intent     = strtolower((string)($nlp['intent'] ?? 'fallback'));
            $confidence = (float)($nlp['confidence'] ?? 0);
            $text       = (string)($job->body ?? '');
            $confTh     = $policy->confidence();

            // --- Forbidden Topics ---
            foreach ($policy->forbiddenTopics() as $topic) {
                if (stripos($text, $topic) !== false) {
                    Log::notice('[AI][Policy] Forbidden topic detected', [
                        'company_id' => $companyId,
                        'topic' => $topic,
                        'intent' => $intent
                    ]);
                    return $job->policyBlock("Sorry, I’m not allowed to discuss {$topic}. Our manager will assist you shortly.");
                }
            }

            // --- Intent-based gating ---
            if (in_array($intent, $policy->intentsForbidden(), true)) {
                Log::notice('[AI][Policy] Forbidden intent', ['intent' => $intent]);
                return $job->policyBlock("Sorry, I can’t handle that. I’ll connect you to our manager.");
            }

            if (in_array($intent, $policy->intentsHandoff(), true) || $confidence < $confTh) {
                Log::info('[AI][Policy] Handoff triggered', [
                    'intent' => $intent,
                    'confidence' => $confidence,
                    'threshold' => $confTh
                ]);
                return $job->policyHandoff($intent, $confidence);
            }

            if (in_array($intent, $policy->intentsHandle(), true)) {
                Log::debug('[AI][Policy] Allowed intent', ['intent' => $intent]);
                return $next($job);
            }

            // Default fallback: allow
            return $next($job);

        } catch (\Throwable $e) {
            Log::error('[AI][PolicyMiddleware] '.$e->getMessage());
            return $next($job);
        }
    }
}
