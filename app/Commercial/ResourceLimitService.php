<?php

namespace App\Commercial;

use App\Messaging\Models\MessagingPhoneNumber;
use App\Messaging\Models\MessagingNumberClaim;
use App\Models\System\Company;
use App\Models\User;
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

    public function assertCanAddPhoneClaim(Company $company): void
    {
        $this->assertBelow(
            $company,
            'limit.whatsapp_numbers',
            $this->usedWhatsAppNumbers($company),
            $this->whatsAppLimitMessage($company),
        );
    }

    public function assertCanConnectPhone(
        Company $company,
        string $provider,
        string $providerPhoneId,
        ?int $numberClaimId = null,
    ): void
    {
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

        if ($limit === null || ! $this->entitlements->decide($company, $capability)->allowed || $used >= $limit) {
            throw ValidationException::withMessages(['plan' => $message]);
        }
    }
}
