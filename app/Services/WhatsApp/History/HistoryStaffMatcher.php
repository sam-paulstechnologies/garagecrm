<?php

namespace App\Services\WhatsApp\History;

use App\Models\System\Company;
use App\Models\User;

class HistoryStaffMatcher
{
    public function __construct(private readonly HistoryIdentity $identity) {}

    public function matches(int $companyId, string $phone): bool
    {
        $normalized = $this->identity->normalize($phone);
        if ($normalized === '') {
            return false;
        }

        $userMatch = User::query()->where('company_id', $companyId)->whereNotNull('phone')->get(['phone'])
            ->contains(fn (User $user): bool => $this->identity->normalize((string) $user->phone) === $normalized);
        if ($userMatch) {
            return true;
        }

        $company = Company::query()->find($companyId);

        return collect([$company?->manager_phone])
            ->filter()
            ->contains(fn (string $value): bool => $this->identity->normalize($value) === $normalized);
    }
}
