<?php

namespace App\Commercial\Jobs;

interface EntitlementAwareJob
{
    public function entitlementCompanyId(): ?int;

    public function entitlementCapability(): string;
}
