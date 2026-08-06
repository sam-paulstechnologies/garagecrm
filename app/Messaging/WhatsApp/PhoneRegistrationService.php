<?php

namespace App\Messaging\WhatsApp;

use App\Messaging\Enums\ConnectionMode;

class PhoneRegistrationService
{
    public function __construct(private readonly MetaApiClient $meta)
    {
    }

    public function verify(string $phoneNumberId, string $token, string $mode): array
    {
        $phone = $this->meta->getPhone($phoneNumberId, $token);
        $status = strtoupper((string) ($phone['status'] ?? $phone['code_verification_status'] ?? 'UNKNOWN'));
        $isOnBusinessApp = filter_var($phone['is_on_biz_app'] ?? false, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($mode === ConnectionMode::BusinessApp->value && $isOnBusinessApp !== true) {
            return ['ready' => false, 'reason' => 'coexistence_not_confirmed', 'phone' => $phone];
        }

        return [
            'ready' => in_array($status, ['CONNECTED', 'VERIFIED', 'APPROVED'], true),
            'reason' => in_array($status, ['CONNECTED', 'VERIFIED', 'APPROVED'], true) ? null : 'phone_registration_pending',
            'phone' => $phone,
        ];
    }
}
