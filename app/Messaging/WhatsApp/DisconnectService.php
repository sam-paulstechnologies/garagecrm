<?php

namespace App\Messaging\WhatsApp;

use App\Messaging\Enums\ConnectionStatus;
use App\Messaging\Models\MessagingConnection;
use App\Messaging\Models\MessagingConsent;
use App\Messaging\Services\MessagingAuditService;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DisconnectService
{
    public function __construct(private readonly MessagingAuditService $audit)
    {
    }

    public function disconnect(MessagingConnection $connection, User $user): void
    {
        abort_unless((int) $connection->company_id === (int) $user->company_id && $user->role === 'admin', 403);

        DB::transaction(function () use ($connection, $user): void {
            $locked = MessagingConnection::query()->lockForUpdate()->findOrFail($connection->id);
            $phone = $locked->phoneNumbers()->where('is_primary', true)->first();
            $company = $locked->company()->lockForUpdate()->firstOrFail();

            $locked->forceFill([
                'status' => ConnectionStatus::Disconnected,
                'disconnected_at' => now(),
                'updated_by' => $user->id,
            ])->save();
            MessagingConsent::query()->where('messaging_connection_id', $locked->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);

            if ($phone && (string) $company->meta_phone_number_id === (string) $phone->phone_number_id) {
                $company->forceFill([
                    'is_whatsapp_active' => false,
                    'whatsapp_coexistence_status' => 'disconnected',
                ])->save();
            }

            $this->audit->record($company->id, $locked->id, $user->id, $locked->product_key,
                'connection_disconnected_locally', 'success', ['external_assets_deleted' => false]);
        });
    }
}
