<?php

namespace App\Services\Ai;

use App\Commercial\AiMonitoringDecision;
use App\Commercial\EntitlementService;
use App\Commercial\ProductFunnelMilestoneRecorder;
use App\Models\Commercial\AiAnalysisRun;
use App\Models\Commercial\AiCustomerUsage;
use App\Models\Commercial\EntitlementUsage;
use App\Models\Commercial\Subscription;
use App\Models\MessageLog;
use App\Models\System\Company;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class AiMonitoringMeter
{
    public const CAPABILITY = 'ai_observational';

    public const ALLOWANCE = 'limit.ai_monitored_customers';

    public function __construct(
        private readonly EntitlementService $entitlements,
        private readonly ProductFunnelMilestoneRecorder $milestones,
    ) {}

    public function claim(
        Company $company,
        ?int $messagingConnectionId,
        string $externalIdentifier,
        MessageLog $message,
        string $connectionScope = 'legacy'
    ): AiMonitoringDecision {
        $subscription = Subscription::query()->where('company_id', $company->id)->first();
        [$periodStart, $periodEnd] = $this->period($subscription);
        $limit = $this->entitlements->limit($company, self::ALLOWANCE);
        $used = $this->used($company, $periodStart);

        $capability = $this->entitlements->decide($company, self::CAPABILITY);
        if (! $capability->allowed) {
            return new AiMonitoringDecision(false, 'skipped_not_entitled', $capability->reason, null, $periodStart, $periodEnd, $limit, $used);
        }

        $normalized = $this->normalizeIdentity($externalIdentifier);
        if ($normalized === '') {
            return new AiMonitoringDecision(false, 'skipped_invalid_identity', 'external_identity_missing', null, $periodStart, $periodEnd, $limit, $used);
        }

        $scope = $messagingConnectionId ? 'connection:'.$messagingConnectionId : $connectionScope;
        $scopeHash = $this->hmac($scope);
        $identityHash = $this->hmac($normalized);

        return DB::transaction(function () use (
            $company, $messagingConnectionId, $message, $periodStart, $periodEnd, $limit, $scopeHash, $identityHash
        ): AiMonitoringDecision {
            $existing = AiCustomerUsage::query()
                ->where('company_id', $company->id)
                ->where('connection_scope_hash', $scopeHash)
                ->where('external_identity_hash', $identityHash)
                ->where('period_start', $periodStart)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $maxRuns = max(1, (int) config('commercial.ai_metering.max_analysis_runs_per_customer_period', 100));
                if ($existing->analysis_runs >= $maxRuns) {
                    return new AiMonitoringDecision(false, 'skipped_secondary_limit', 'customer_analysis_run_limit', $existing, $periodStart, $periodEnd, $limit, $this->used($company, $periodStart));
                }

                $existing->forceFill(['last_message_log_id' => $message->id])->save();

                return new AiMonitoringDecision(true, 'allowed', 'existing_customer_in_period', $existing, $periodStart, $periodEnd, $limit, $this->used($company, $periodStart));
            }

            DB::table('entitlement_usages')->insertOrIgnore([
                'company_id' => $company->id,
                'capability' => self::ALLOWANCE,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'used' => 0,
                'metadata' => json_encode(['unit' => 'unique_external_customer']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $aggregate = EntitlementUsage::query()
                ->where('company_id', $company->id)
                ->where('capability', self::ALLOWANCE)
                ->where('period_start', $periodStart)
                ->lockForUpdate()
                ->firstOrFail();

            if ($limit !== null && $aggregate->used >= $limit) {
                return new AiMonitoringDecision(false, 'skipped_quota', 'allowance_exhausted', null, $periodStart, $periodEnd, $limit, (int) $aggregate->used);
            }

            $inserted = DB::table('ai_customer_usages')->insertOrIgnore([
                'company_id' => $company->id,
                'messaging_connection_id' => $messagingConnectionId,
                'connection_scope_hash' => $scopeHash,
                'external_identity_hash' => $identityHash,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'first_message_log_id' => $message->id,
                'last_message_log_id' => $message->id,
                'analysis_runs' => 0,
                'input_tokens' => 0,
                'output_tokens' => 0,
                'estimated_cost_micros' => 0,
                'last_status' => 'claimed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $usage = AiCustomerUsage::query()
                ->where('company_id', $company->id)
                ->where('connection_scope_hash', $scopeHash)
                ->where('external_identity_hash', $identityHash)
                ->where('period_start', $periodStart)
                ->lockForUpdate()
                ->firstOrFail();

            if ($inserted === 1) {
                $aggregate->increment('used');
            }

            $currentUsed = (int) $aggregate->fresh()->used;
            if ($inserted === 1 && $limit !== null) {
                $this->milestones->aiUsageThresholds(
                    (int) $company->id,
                    $currentUsed,
                    $limit,
                    $periodStart,
                );
            }

            return new AiMonitoringDecision(
                true,
                'allowed',
                $inserted === 1 ? 'new_customer_claimed' : 'existing_customer_in_period',
                $usage,
                $periodStart,
                $periodEnd,
                $limit,
                $currentUsed,
                $inserted === 1,
            );
        }, 3);
    }

    public function recordRun(
        AiMonitoringDecision $decision,
        Company $company,
        MessageLog $message,
        string $status,
        array $telemetry = []
    ): AiAnalysisRun {
        return DB::transaction(function () use ($decision, $company, $message, $status, $telemetry): AiAnalysisRun {
            $existing = AiAnalysisRun::query()
                ->where('message_log_id', $message->id)
                ->where('capability', self::CAPABILITY)
                ->lockForUpdate()
                ->first();

            if ($existing && in_array($existing->status, ['completed', 'failed'], true)) {
                return $existing;
            }

            $run = AiAnalysisRun::query()->updateOrCreate([
                'message_log_id' => $message->id,
                'capability' => self::CAPABILITY,
            ], [
                'company_id' => $company->id,
                'ai_customer_usage_id' => $decision->usage?->id,
                'status' => $status,
                'provider' => $telemetry['provider'] ?? null,
                'model' => $telemetry['model'] ?? null,
                'input_tokens' => $telemetry['input_tokens'] ?? null,
                'output_tokens' => $telemetry['output_tokens'] ?? null,
                'total_tokens' => $telemetry['total_tokens'] ?? null,
                'estimated_cost_micros' => $telemetry['estimated_cost_micros'] ?? null,
                'skipped_reason' => str_starts_with($status, 'skipped_') ? $decision->reason : null,
                'error_code' => $telemetry['error_code'] ?? null,
                'duration_ms' => $telemetry['duration_ms'] ?? null,
                'metadata' => [
                    'period_start' => $decision->periodStart->toIso8601String(),
                    'period_end' => $decision->periodEnd->toIso8601String(),
                    'new_customer' => $decision->newCustomer,
                ],
            ]);

            if ($decision->usage) {
                $usage = AiCustomerUsage::query()->whereKey($decision->usage->id)->lockForUpdate()->firstOrFail();
                $terminal = in_array($status, ['completed', 'failed'], true);
                $usage->forceFill([
                    'analysis_runs' => $usage->analysis_runs + ($terminal ? 1 : 0),
                    'input_tokens' => $usage->input_tokens + ($terminal ? (int) ($telemetry['input_tokens'] ?? 0) : 0),
                    'output_tokens' => $usage->output_tokens + ($terminal ? (int) ($telemetry['output_tokens'] ?? 0) : 0),
                    'estimated_cost_micros' => $usage->estimated_cost_micros + ($terminal ? (int) ($telemetry['estimated_cost_micros'] ?? 0) : 0),
                    'latest_model' => $telemetry['model'] ?? $usage->latest_model,
                    'last_status' => $status,
                    'last_skipped_reason' => str_starts_with($status, 'skipped_') ? $decision->reason : null,
                    'last_message_log_id' => $message->id,
                ])->save();
            }

            return $run;
        }, 3);
    }

    /** @return array{0: CarbonInterface, 1: CarbonInterface} */
    private function period(?Subscription $subscription): array
    {
        $start = $subscription?->current_period_start?->copy()->startOfSecond() ?? now()->startOfMonth();
        $end = $subscription?->current_period_end?->copy()->startOfSecond() ?? $start->copy()->addMonth();

        return [$start, $end];
    }

    private function used(Company $company, CarbonInterface $periodStart): int
    {
        return (int) EntitlementUsage::query()
            ->where('company_id', $company->id)
            ->where('capability', self::ALLOWANCE)
            ->where('period_start', $periodStart)
            ->value('used');
    }

    private function normalizeIdentity(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?: '';

        return $digits !== '' ? $digits : strtolower(trim($value));
    }

    private function hmac(string $value): string
    {
        $key = (string) config('commercial.ai_metering.hmac_key');
        if ($key === '') {
            throw new \RuntimeException('AI metering HMAC key is unavailable.');
        }

        return hash_hmac('sha256', $value, $key);
    }
}
