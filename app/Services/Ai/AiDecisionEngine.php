<?php

namespace App\Services\Ai;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AiDecisionEngine
{
    public static function settings(int $companyId): array
    {
        $rows = DB::table('company_settings')
            ->where('company_id', $companyId)
            ->whereIn('key', [
                'ai.enabled',
                'ai.confidence_threshold',
                'ai.first_reply',
                'ai.intent.handle',
                'ai.intent.handoff',
                'ai.intent.forbidden',
                'ai.policy_reply',
            ])
            ->pluck('value', 'key');

        $v = fn($k, $d=null) => isset($rows[$k]) ? (string)$rows[$k] : $d;

        return [
            'enabled'              => $v('ai.enabled', '0') === '1',
            'confidence_threshold' => is_numeric($v('ai.confidence_threshold', '0.60')) ? (float)$v('ai.confidence_threshold','0.60') : 0.60,
            'first_reply'          => $v('ai.first_reply', '0') === '1',
            'intent_handle'        => array_values(array_filter(array_map('trim', explode(',', (string)$v('ai.intent.handle',''))))),
            'intent_handoff'       => array_values(array_filter(array_map('trim', explode(',', (string)$v('ai.intent.handoff',''))))),
            'intent_forbidden'     => array_values(array_filter(array_map('trim', explode(',', (string)$v('ai.intent.forbidden',''))))),
            'policy_reply'         => (string) $v('ai.policy_reply', "I'm not allowed to answer that. A manager will contact you shortly."),
        ];
    }

    public static function shouldGenerate(array $analysis, int $companyId): array
    {
        $s = self::settings($companyId);

        $confidence = (float) Arr::get($analysis, 'confidence', 0);
        $intent     = (string) Arr::get($analysis, 'intent', '');

        $isForbidden = $intent !== '' && in_array($intent, $s['intent_forbidden'], true);

        return [
            'enabled'     => $s['enabled'],
            'allowed'     => $s['enabled'] && !$isForbidden && ($confidence >= $s['confidence_threshold']),
            'handoff'     => $intent !== '' && in_array($intent, $s['intent_handoff'], true),
            'intent'      => $intent,
            'confidence'  => $confidence,
            'threshold'   => $s['confidence_threshold'],
            'policy_reply'=> $s['policy_reply'],
        ];
    }
}
