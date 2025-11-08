<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\DB;

class AiContextService
{
    public function __construct(protected int $companyId) {}

    protected function get(string $key, $default = null): ?string
    {
        return DB::table('company_settings')
            ->where('company_id', $this->companyId)
            ->where('key', $key)
            ->value('value') ?? $default;
    }

    // Business profile
    public function managerPhone(): ?string { return $this->get('business.manager_phone', null); }
    public function workHours(): string     { return (string) $this->get('business.work_hours', 'Mon–Sat 09:00–18:00'); }
    public function location(): string      { return (string) $this->get('business.location', ''); }

    public function holidays(): array
    {
        $json = $this->get('business.holidays', '[]');
        $arr  = json_decode($json, true);
        return is_array($arr) ? $arr : [];
    }

    public function locationCoords(): ?array
    {
        $raw = (string) $this->get('business.location_coords', '');
        if (!$raw) return null;
        if (preg_match('/^\s*(-?\d+(\.\d+)?)\s*,\s*(-?\d+(\.\d+)?)\s*$/', $raw, $m)) {
            return [(float)$m[1], (float)$m[3]];
        }
        return null;
    }

    // Escalations
    public function escalateOnLowConfidence(): bool { return $this->get('escalation.low_confidence','1') === '1'; }
    public function escalateOnNegativeSentiment(): bool { return $this->get('escalation.sentiment','1') === '1'; }
    public function timeoutMinutes(): int { return (int) $this->get('escalation.timeout_minutes','120'); }
}
