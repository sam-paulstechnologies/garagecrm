<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\DB;

class AiPolicyService
{
    public function __construct(protected int $companyId) {}

    protected function get(string $key, $default = null): ?string
    {
        return DB::table('company_settings')
            ->where('company_id', $this->companyId)
            ->where('key', $key)
            ->value('value') ?? $default;
    }

    public function enabled(): bool                        { return $this->get('ai.enabled','0') === '1'; }
    public function confidence(): float                    { $v=$this->get('ai.confidence_threshold','0.60'); return is_numeric($v)?(float)$v:0.60; }
    public function firstReply(): bool                     { return $this->get('ai.first_reply', env('AI_FIRST_REPLY', false)?'1':'0') === '1'; }
    public function policyReply(): string                  { return (string) $this->get('ai.policy_reply', "I can’t help with that. I’ll connect you to our manager."); }

    public function intentsHandle(): array    { return $this->csv('ai.intent.handle'); }
    public function intentsHandoff(): array   { return $this->csv('ai.intent.handoff'); }
    public function intentsForbidden(): array { return $this->csv('ai.intent.forbidden'); }
    public function forbiddenTopics(): array  { return $this->csv('ai.forbidden_topics'); }

    protected function csv(string $key): array
    {
        $raw = (string) $this->get($key, '');
        if ($raw === '') return [];
        $parts = preg_split('/[\r\n,]+/', $raw);
        return array_values(array_filter(array_map('trim', $parts)));
    }
}
