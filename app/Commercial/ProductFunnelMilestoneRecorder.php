<?php

namespace App\Commercial;

use App\Models\System\Company;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

final class ProductFunnelMilestoneRecorder
{
    public function __construct(private readonly ProductEventRecorder $events) {}

    public function firstInbound(int $companyId): void
    {
        $this->record(ProductEvents::FIRST_INBOUND, $companyId, ['channel' => 'whatsapp']);
    }

    public function firstLead(int $companyId): void
    {
        $this->record(ProductEvents::FIRST_LEAD, $companyId, ['source' => 'application']);
    }

    public function firstOpportunity(int $companyId): void
    {
        $this->record(ProductEvents::FIRST_OPPORTUNITY, $companyId, ['source' => 'application']);
    }

    public function firstBooking(int $companyId): void
    {
        $this->record(ProductEvents::FIRST_BOOKING, $companyId, ['source' => 'application']);
    }

    public function whatsappOnboardingStarted(int $companyId, string $connectionMode, string $sessionKey): void
    {
        $this->record(
            ProductEvents::WHATSAPP_ONBOARDING_STARTED,
            $companyId,
            ['connection_mode' => $connectionMode],
            'whatsapp-onboarding-started:'.$sessionKey,
        );
    }

    public function whatsappConnected(int $companyId, string $connectionMode, int $connectionId): void
    {
        $this->record(
            ProductEvents::WHATSAPP_CONNECTED,
            $companyId,
            ['connection_mode' => $connectionMode],
            'whatsapp-connected:'.$connectionId,
        );
    }

    public function aiUsageThresholds(
        int $companyId,
        int $used,
        int $limit,
        CarbonInterface $periodStart,
    ): void {
        if ($limit <= 0) {
            return;
        }

        foreach ([
            50 => ProductEvents::AI_USAGE_50,
            80 => ProductEvents::AI_USAGE_80,
            100 => ProductEvents::AI_USAGE_100,
        ] as $threshold => $eventType) {
            if ($used < (int) ceil($limit * ($threshold / 100))) {
                continue;
            }

            $this->record(
                $eventType,
                $companyId,
                ['threshold' => $threshold, 'used' => $used, 'limit' => $limit],
                implode(':', ['ai-usage', $companyId, $periodStart->toDateString(), $threshold]),
            );
        }
    }

    /** @param array<string, scalar|null> $properties */
    private function record(
        string $eventType,
        int $companyId,
        array $properties,
        ?string $dedupeKey = null,
    ): void {
        if ($companyId <= 0 || ! Schema::hasTable('product_events')) {
            return;
        }

        try {
            $company = Company::query()->find($companyId);
            if (! $company) {
                return;
            }

            $this->events->record(
                $eventType,
                $company,
                properties: $properties,
                dedupeKey: $dedupeKey ?? $eventType.':'.$companyId,
            );
        } catch (\Throwable $exception) {
            Log::warning('Product funnel milestone could not be recorded.', [
                'event_type' => $eventType,
                'company_id' => $companyId,
                'exception' => $exception::class,
            ]);
        }
    }
}
