<?php

namespace App\Messaging\WhatsApp;

use App\Messaging\Enums\ConnectionStatus;
use App\Messaging\Models\MessagingConnection;
use App\Messaging\Models\MessagingConsent;
use App\Messaging\Services\MessagingAuditService;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppHistoryCandidate;
use App\Models\WhatsApp\WhatsAppHistoryMessage;
use App\Models\WhatsApp\WhatsAppSyncedContact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DisconnectService
{
    public function __construct(private readonly MessagingAuditService $audit) {}

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

            $purged = $this->purgeUnreviewedHistoryQuarantine(
                (int) $company->id,
                (int) $locked->id,
                $phone?->phone_number_id !== null ? (string) $phone->phone_number_id : null,
            );

            $this->audit->record($company->id, $locked->id, $user->id, $locked->product_key,
                'connection_disconnected_locally', 'success', [
                    'external_assets_deleted' => false,
                    'history_quarantine_purged' => $purged,
                ]);
        });
    }

    /**
     * Enforce explicit retention on WhatsApp disconnect (M11).
     *
     * Physically PURGES unreviewed quarantine / history-sync material that was
     * never promoted to the CRM:
     *   - whatsapp_synced_contacts (raw contact sync staging)
     *   - whatsapp_history_messages not attached to an imported candidate
     *   - pending-review whatsapp_history_candidates that were never imported
     *
     * PRESERVES everything the tenant explicitly kept: imported CRM records
     * (Client/Conversation/MessageLog created via an approved Track) and the
     * candidate rows behind them, plus Don't-Track suppression preferences
     * (whatsapp_tracking_preferences). Never touches invoices/jobs/bookings/leads.
     *
     * @return array<string,int> counts of physically deleted quarantine rows
     */
    private function purgeUnreviewedHistoryQuarantine(int $companyId, ?int $connectionId, ?string $phoneNumberId): array
    {
        $deleted = ['candidates' => 0, 'history_messages' => 0, 'synced_contacts' => 0];

        // Candidates promoted to the CRM (an explicit Track import) are kept, and
        // so is any staged history physically attached to them.
        $importedCandidateIds = Schema::hasTable('whatsapp_history_candidates')
            ? WhatsAppHistoryCandidate::query()
                ->where('company_id', $companyId)
                ->whereNotNull('imported_client_id')
                ->pluck('id')
                ->all()
            : [];

        if (Schema::hasTable('whatsapp_history_messages')) {
            $deleted['history_messages'] = (int) WhatsAppHistoryMessage::query()
                ->where('company_id', $companyId)
                ->when($phoneNumberId !== null && $phoneNumberId !== '', fn ($query) => $query->where('phone_number_id', $phoneNumberId))
                ->where(function ($query) use ($importedCandidateIds): void {
                    // When nothing was imported to the CRM, every staged history
                    // message for this connection is unreviewed quarantine and
                    // must be purged (empty nested clause = no extra constraint,
                    // so the outer company/phone scope purges all). Previously an
                    // empty import set only deleted NULL-candidate rows, orphaning
                    // message bodies attached to pending candidates (M11).
                    if ($importedCandidateIds === []) {
                        return;
                    }

                    // Otherwise keep only history physically attached to an
                    // imported candidate; purge NULL-candidate and non-imported.
                    $query->whereNull('whatsapp_history_candidate_id')
                        ->orWhereNotIn('whatsapp_history_candidate_id', $importedCandidateIds);
                })
                ->delete();
        }

        if (Schema::hasTable('whatsapp_synced_contacts')) {
            $deleted['synced_contacts'] = (int) WhatsAppSyncedContact::query()
                ->where('company_id', $companyId)
                ->when($phoneNumberId !== null && $phoneNumberId !== '', fn ($query) => $query->where('phone_number_id', $phoneNumberId))
                ->delete();
        }

        if (Schema::hasTable('whatsapp_history_candidates')) {
            $deleted['candidates'] = (int) WhatsAppHistoryCandidate::query()
                ->where('company_id', $companyId)
                ->where('review_decision', 'pending')
                ->whereNull('imported_client_id')
                ->when($connectionId !== null, fn ($query) => $query->where(function ($scoped) use ($connectionId): void {
                    $scoped->whereNull('messaging_connection_id')->orWhere('messaging_connection_id', $connectionId);
                }))
                ->delete();
        }

        return $deleted;
    }
}
