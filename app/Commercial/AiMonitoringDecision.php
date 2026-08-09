<?php

namespace App\Commercial;

use App\Models\Commercial\AiCustomerUsage;
use Carbon\CarbonInterface;

final readonly class AiMonitoringDecision
{
    public function __construct(
        public bool $allowed,
        public string $status,
        public string $reason,
        public ?AiCustomerUsage $usage,
        public CarbonInterface $periodStart,
        public CarbonInterface $periodEnd,
        public ?int $limit,
        public int $used,
        public bool $newCustomer = false,
    ) {
    }
}
