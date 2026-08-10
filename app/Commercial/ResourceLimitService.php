<?php

namespace App\Commercial;

use App\Messaging\Models\MessagingPhoneNumber;
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

    public function assertCanConnectPhone(Company $company, string $provider, string $providerPhoneId): void
    {
        $alreadyOwned = MessagingPhoneNumber::query()
            ->where('provider', $provider)
            ->where('phone_number_id', $providerPhoneId)
            ->whereHas('connection', fn ($query) => $query->where('company_id', $company->id))
            ->exists();

        if ($alreadyOwned) {
            return;
        }

        $used = MessagingPhoneNumber::query()
            ->whereHas('connection', fn ($query) => $query->where('company_id', $company->id))
            ->count();

        $this->assertBelow($company, 'limit.whatsapp_numbers', $used, 'Your plan WhatsApp number limit has been reached.');
    }

    private function assertBelow(Company $company, string $capability, int $used, string $message): void
    {
        $limit = $this->entitlements->limit($company, $capability);

        if ($limit === null || ! $this->entitlements->decide($company, $capability)->allowed || $used >= $limit) {
            throw ValidationException::withMessages(['plan' => $message]);
        }
    }
}
