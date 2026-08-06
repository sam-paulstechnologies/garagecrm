<?php

namespace App\Messaging\WhatsApp;

use App\Messaging\Enums\ConnectionMode;
use App\Messaging\Exceptions\MessagingProvisioningException;
use App\Messaging\Models\MessagingConnection;
use App\Messaging\Models\MessagingPhoneNumber;
use App\Messaging\Services\TokenService;
use App\Models\System\Company;

class LegacyCompanyConnectionSynchronizer
{
    public function __construct(private readonly TokenService $tokens)
    {
    }

    public function synchronize(MessagingConnection $connection, MessagingPhoneNumber $phone): Company
    {
        $company = Company::query()->lockForUpdate()->findOrFail($connection->company_id);

        if (filled($company->meta_phone_number_id)
            && (string) $company->meta_phone_number_id !== (string) $phone->phone_number_id) {
            throw new MessagingProvisioningException(
                'existing_connection_conflict',
                'This garage already has a different WhatsApp number. Disconnect it before connecting another number.',
            );
        }
        if (filled($company->meta_waba_id) && (string) $company->meta_waba_id !== (string) $connection->waba_id) {
            throw new MessagingProvisioningException('existing_waba_conflict', 'This garage already has a different WhatsApp account.');
        }

        $company->forceFill([
            'meta_phone_number_id' => $phone->phone_number_id,
            'meta_access_token' => \Illuminate\Support\Facades\Crypt::encryptString($this->tokens->retrieve($connection)),
            'meta_waba_id' => $connection->waba_id,
            'meta_business_id' => $connection->meta_business_id,
            'meta_display_phone_number' => $phone->display_phone_number,
            'meta_token_expires_at' => $connection->token_expires_at,
            'is_whatsapp_active' => true,
            'whatsapp_connection_mode' => $connection->connection_mode,
            'whatsapp_coexistence_enabled' => $connection->connection_mode === ConnectionMode::BusinessApp->value,
            'whatsapp_coexistence_status' => $phone->coexistence_status,
            'whatsapp_onboarding_source' => 'self_service_embedded_signup',
            'whatsapp_connected_at' => now(),
            'whatsapp_webhook_subscription_status' => 'subscribed',
            'whatsapp_webhook_subscription_checked_at' => now(),
        ])->save();

        return $company;
    }
}
