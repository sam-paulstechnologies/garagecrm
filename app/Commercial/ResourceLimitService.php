<?php

namespace App\Commercial;

use App\Messaging\Models\MessagingNumberClaim;
use App\Messaging\Models\MessagingPhoneNumber;
use App\Models\Garage\Garage;
use App\Models\System\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResourceLimitService
{
    public function __construct(private readonly EntitlementService $entitlements) {}

    public function assertCanCreateUser(Company $company): void
    {
        $this->assertBelow(
            $company,
            'limit.users',
            User::query()->where('company_id', $company->id)->count(),
            'Your plan user limit has been reached.'
        );
    }

    public function assertCanCreateLocation(Company $company): void
    {
        $this->assertBelow(
            $company,
            'limit.locations',
            Garage::query()->where('company_id', $company->id)->count(),
            'Your plan location limit has been reached.'
        );
    }

    public function assertCanAddPhoneClaim(Company $company): void
    {
        $this->assertBelow(
            $company,
            'limit.whatsapp_numbers',
            $this->usedWhatsAppNumbers($company),
            $this->whatsAppLimitMessage($company),
        );
    }

    public function assertCanActivateWorkflow(Company $company, ?int $ignoreRuleId = null): void
    {
        $active = DB::table('automation_rules')
            ->where('company_id', $company->id)
            ->where('active', true)
            ->when($ignoreRuleId, fn ($query) => $query->where('id', '!=', $ignoreRuleId))
            ->count();

        $this->assertBelow(
            $company,
            'limit.active_workflows',
            $active,
            'Your plan active workflow limit has been reached.'
        );
    }

    /** @return array{used:int,limit:?int,remaining:?int,mode:?string} */
    public function workflowSummary(Company $company): array
    {
        $used = DB::table('automation_rules')
            ->where('company_id', $company->id)
            ->where('active', true)
            ->count();
        $decision = $this->entitlements->decide($company, 'limit.active_workflows');

        return [
            'used' => $used,
            'limit' => $decision->limit,
            'remaining' => $decision->limit === null ? null : max(0, $decision->limit - $used),
            'mode' => $decision->mode,
        ];
    }

    public function assertCanConnectPhone(
        Company $company,
        string $provider,
        string $providerPhoneId,
        ?int $numberClaimId = null,
    ): void {
        $alreadyOwned = MessagingPhoneNumber::query()
            ->where('provider', $provider)
            ->where('phone_number_id', $providerPhoneId)
            ->whereHas('connection', fn ($query) => $query->where('company_id', $company->id))
            ->exists();

        if ($alreadyOwned) {
            return;
        }

        $used = $this->usedWhatsAppNumbers($company, $numberClaimId);

        $this->assertBelow($company, 'limit.whatsapp_numbers', $used, $this->whatsAppLimitMessage($company));
    }

    private function usedWhatsAppNumbers(Company $company, ?int $ignoreClaimId = null): int
    {
        $verified = MessagingPhoneNumber::query()
            ->whereHas('connection', fn ($query) => $query->where('company_id', $company->id))
            ->count();
        $pending = MessagingNumberClaim::query()
            ->where('company_id', $company->id)
            ->whereNull('messaging_phone_number_id')
            ->when($ignoreClaimId, fn ($query) => $query->where('id', '!=', $ignoreClaimId))
            ->count();

        return $verified + $pending;
    }

    private function whatsAppLimitMessage(Company $company): string
    {
        $identity = $this->entitlements->planIdentity($company);
        $plan = match ($identity['code']) {
            'free' => 'Free',
            'service' => 'Service',
            'growth' => 'Growth',
            'performance' => 'Performance',
            'ai_pro' => 'AI Pro',
            default => 'Your plan',
        };
        $limit = $this->entitlements->limit($company, 'limit.whatsapp_numbers');
        $allowance = $limit === 1 ? 'one WhatsApp number' : "{$limit} WhatsApp numbers";

        return "{$plan} includes {$allowance}. Upgrade if you need additional numbers.";
    }

    private function assertBelow(Company $company, string $capability, int $used, string $message): void
    {
        $limit = $this->entitlements->limit($company, $capability);

        $decision = $this->entitlements->decide($company, $capability);

        if (! $decision->allowed || ($limit !== null && $used >= $limit)) {
            throw ValidationException::withMessages(['plan' => $message]);
        }
    }
}
