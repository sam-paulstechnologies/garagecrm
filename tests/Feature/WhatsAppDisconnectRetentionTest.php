<?php

namespace Tests\Feature;

use App\Messaging\Enums\ConnectionStatus;
use App\Messaging\Models\MessagingConnection;
use App\Messaging\Models\MessagingPhoneNumber;
use App\Messaging\WhatsApp\DisconnectService;
use App\Models\Client\Client;
use App\Models\Conversation;
use App\Models\MessageLog;
use App\Models\System\Company;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppHistoryCandidate;
use App\Models\WhatsApp\WhatsAppHistoryImportBatch;
use App\Models\WhatsApp\WhatsAppHistoryMessage;
use App\Models\WhatsApp\WhatsAppSyncedContact;
use App\Models\WhatsApp\WhatsAppTrackingPreference;
use App\Services\WhatsApp\MetaEmbeddedSignupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WhatsAppDisconnectRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_disconnect_purges_unreviewed_quarantine_and_preserves_crm_and_preferences(): void
    {
        [$company, $phoneNumberId] = $this->tenantWithConnection('PN-100');
        $connection = MessagingConnection::query()->where('company_id', $company->id)->firstOrFail();
        $admin = $this->admin($company);

        $seed = $this->seedRetentionScenario($company, $phoneNumberId, $connection->id);
        $neighbour = $this->seedNeighbourTenant();

        app(DisconnectService::class)->disconnect($connection, $admin);

        $this->assertRetentionOutcome($company, $seed);
        $this->assertNeighbourUntouched($neighbour);

        // Connection state is revoked as part of the same operation.
        $this->assertSame(ConnectionStatus::Disconnected, $connection->fresh()->status);
    }

    public function test_company_level_disconnect_purges_unreviewed_quarantine_and_preserves_crm_and_preferences(): void
    {
        [$company, $phoneNumberId] = $this->tenantWithConnection('PN-200');
        $company->forceFill(['meta_access_token' => 'tok'])->save();

        $seed = $this->seedRetentionScenario($company, $phoneNumberId, null);
        $neighbour = $this->seedNeighbourTenant();

        app(MetaEmbeddedSignupService::class)->disconnectCompany($company->fresh());

        $this->assertRetentionOutcome($company, $seed);
        $this->assertNeighbourUntouched($neighbour);

        // Provider credentials are cleared on disconnect.
        $this->assertNull($company->fresh()->meta_access_token);
    }

    /**
     * @return array{0:Company,1:string}
     */
    private function tenantWithConnection(string $phoneNumberId): array
    {
        $company = Company::query()->create(['name' => 'Retention Garage '.$phoneNumberId, 'status' => 'active']);
        $company->forceFill([
            'meta_phone_number_id' => $phoneNumberId,
            'is_whatsapp_active' => true,
            'whatsapp_connection_mode' => 'business_app_onboarding',
            'whatsapp_coexistence_enabled' => true,
        ])->save();

        $connection = MessagingConnection::query()->create([
            'company_id' => $company->id,
            'product_key' => 'whatsapp',
            'provider' => 'meta_whatsapp',
            'status' => ConnectionStatus::Connected,
            'connection_mode' => 'business_app_onboarding',
        ]);
        MessagingPhoneNumber::query()->create([
            'messaging_connection_id' => $connection->id,
            'provider' => 'meta_whatsapp',
            'phone_number_id' => $phoneNumberId,
            'is_primary' => true,
        ]);

        return [$company->fresh(), $phoneNumberId];
    }

    private function admin(Company $company): User
    {
        return User::query()->create([
            'company_id' => $company->id,
            'name' => 'Retention Admin',
            'email' => 'retention-admin-'.$company->id.'@example.test',
            'password' => 'Strong-Password-2026!',
            'role' => 'admin',
            'status' => true,
            'must_change_password' => false,
        ]);
    }

    /**
     * Seeds: (a) unreviewed quarantine that MUST be purged, (b) an imported CRM
     * record + its candidate that MUST survive, (c) a Don't-Track preference that
     * MUST survive, and (d) an invoice proving operational records are untouched.
     *
     * @return array<string,mixed>
     */
    private function seedRetentionScenario(Company $company, string $phoneNumberId, ?int $connectionId): array
    {
        $batch = WhatsAppHistoryImportBatch::query()->create([
            'company_id' => $company->id,
            'messaging_connection_id' => $connectionId,
            'connection_scope_hash' => str_repeat('a', 64),
            'status' => 'awaiting_review',
        ]);

        // (a) Unreviewed, never-imported quarantine candidate + staged messages.
        $pending = WhatsAppHistoryCandidate::query()->create([
            'company_id' => $company->id,
            'whatsapp_history_import_batch_id' => $batch->id,
            'messaging_connection_id' => $connectionId,
            'external_identity_hash' => hash('sha256', 'pending-'.$company->id),
            'review_decision' => 'pending',
            'intelligence_status' => 'unselected',
        ]);
        foreach (['pending-a', 'pending-b'] as $fingerprint) {
            WhatsAppHistoryMessage::query()->create([
                'company_id' => $company->id,
                'whatsapp_history_import_batch_id' => $batch->id,
                'whatsapp_history_candidate_id' => $pending->id,
                'phone_number_id' => $phoneNumberId,
                'source_fingerprint' => $fingerprint.'-'.$company->id,
                'direction' => 'in',
                'message_type' => 'text',
                'body' => 'Quarantined body',
                'message_timestamp' => now()->subDays(30),
            ]);
        }

        // Raw synced-contact quarantine.
        foreach (['sync-a', 'sync-b'] as $hash) {
            WhatsAppSyncedContact::query()->create([
                'company_id' => $company->id,
                'phone_number_id' => $phoneNumberId,
                'contact_hash' => hash('sha256', $hash.'-'.$company->id),
                'sync_action' => 'add',
                'status' => 'active',
                'last_synced_at' => now(),
            ]);
        }

        // (b) Legitimate imported CRM record (created via an explicit Track).
        $client = Client::query()->create([
            'company_id' => $company->id,
            'name' => 'Imported Customer',
            'phone' => '+971500000900',
            'source' => 'whatsapp_history',
            'status' => 'active',
        ]);
        $conversation = Conversation::query()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'is_whatsapp_linked' => true,
            'customer_name' => $client->name,
            'customer_phone' => '+971500000900',
            'subject' => 'Imported WhatsApp history',
        ]);
        $importedCandidate = WhatsAppHistoryCandidate::query()->create([
            'company_id' => $company->id,
            'whatsapp_history_import_batch_id' => $batch->id,
            'messaging_connection_id' => $connectionId,
            'external_identity_hash' => hash('sha256', 'imported-'.$company->id),
            'review_decision' => 'track',
            'intelligence_status' => 'analysed',
            'imported_client_id' => $client->id,
            'imported_conversation_id' => $conversation->id,
            'import_status' => 'created_client',
            'imported_at' => now(),
        ]);
        $importedHistoryMessage = WhatsAppHistoryMessage::query()->create([
            'company_id' => $company->id,
            'whatsapp_history_import_batch_id' => $batch->id,
            'whatsapp_history_candidate_id' => $importedCandidate->id,
            'phone_number_id' => $phoneNumberId,
            'source_fingerprint' => 'imported-'.$company->id,
            'direction' => 'in',
            'message_type' => 'text',
            'body' => 'Imported context',
            'message_timestamp' => now()->subDays(20),
        ]);
        $crmMessage = MessageLog::query()->create([
            'company_id' => $company->id,
            'conversation_id' => $conversation->id,
            'direction' => 'in',
            'channel' => 'whatsapp',
            'source' => 'whatsapp_coexistence_history',
            'is_historical' => true,
            'whatsapp_history_candidate_id' => $importedCandidate->id,
            'body' => 'Imported message',
            'provider_message_id' => 'history-import-'.$company->id,
            'provider_status' => 'historical',
        ]);

        // (c) Don't-Track suppression preference.
        $preference = WhatsAppTrackingPreference::query()->create([
            'company_id' => $company->id,
            'external_identity_hash' => hash('sha256', 'dont-track-'.$company->id),
            'decision' => 'dont_track',
            'decided_at' => now(),
        ]);

        // (d) Operational record that must never be touched by a disconnect.
        $invoiceId = DB::table('invoices')->insertGetId([
            'company_id' => $company->id,
            'number' => 'INV-'.$company->id.'-001',
            'subtotal' => 100,
            'tax' => 5,
            'total' => 105,
            'status' => 'sent',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'pending_candidate_id' => $pending->id,
            'imported_candidate_id' => $importedCandidate->id,
            'imported_history_message_id' => $importedHistoryMessage->id,
            'client_id' => $client->id,
            'conversation_id' => $conversation->id,
            'crm_message_id' => $crmMessage->id,
            'preference_id' => $preference->id,
            'invoice_id' => $invoiceId,
            'phone_number_id' => $phoneNumberId,
        ];
    }

    /**
     * @param  array<string,mixed>  $seed
     */
    private function assertRetentionOutcome(Company $company, array $seed): void
    {
        // (a) Unreviewed quarantine is physically gone.
        $this->assertDatabaseMissing('whatsapp_history_candidates', ['id' => $seed['pending_candidate_id']]);
        $this->assertSame(0, WhatsAppHistoryMessage::query()
            ->where('whatsapp_history_candidate_id', $seed['pending_candidate_id'])->count());
        $this->assertSame(0, WhatsAppSyncedContact::query()->where('company_id', $company->id)->count());

        // (b) Imported CRM records and their candidate survive.
        $this->assertDatabaseHas('whatsapp_history_candidates', ['id' => $seed['imported_candidate_id']]);
        $this->assertDatabaseHas('whatsapp_history_messages', ['id' => $seed['imported_history_message_id']]);
        $this->assertDatabaseHas('clients', ['id' => $seed['client_id']]);
        $this->assertDatabaseHas('conversations', ['id' => $seed['conversation_id']]);
        $this->assertDatabaseHas('message_logs', ['id' => $seed['crm_message_id'], 'is_historical' => true]);

        // (c) Don't-Track preference survives.
        $this->assertDatabaseHas('whatsapp_tracking_preferences', [
            'id' => $seed['preference_id'],
            'decision' => 'dont_track',
        ]);

        // (d) Operational record untouched.
        $this->assertDatabaseHas('invoices', ['id' => $seed['invoice_id'], 'company_id' => $company->id]);
    }

    /**
     * @return array<string,mixed>
     */
    private function seedNeighbourTenant(): array
    {
        [$company, $phoneNumberId] = $this->tenantWithConnection('PN-NEIGHBOUR-'.uniqid());
        $batch = WhatsAppHistoryImportBatch::query()->create([
            'company_id' => $company->id,
            'connection_scope_hash' => str_repeat('b', 64),
            'status' => 'awaiting_review',
        ]);
        $candidate = WhatsAppHistoryCandidate::query()->create([
            'company_id' => $company->id,
            'whatsapp_history_import_batch_id' => $batch->id,
            'external_identity_hash' => hash('sha256', 'neighbour-'.$company->id),
            'review_decision' => 'pending',
        ]);
        WhatsAppSyncedContact::query()->create([
            'company_id' => $company->id,
            'phone_number_id' => $phoneNumberId,
            'contact_hash' => hash('sha256', 'neighbour-sync-'.$company->id),
            'sync_action' => 'add',
            'status' => 'active',
            'last_synced_at' => now(),
        ]);

        return ['company_id' => $company->id, 'candidate_id' => $candidate->id];
    }

    /**
     * @param  array<string,mixed>  $neighbour
     */
    private function assertNeighbourUntouched(array $neighbour): void
    {
        $this->assertDatabaseHas('whatsapp_history_candidates', ['id' => $neighbour['candidate_id']]);
        $this->assertSame(1, WhatsAppSyncedContact::query()->where('company_id', $neighbour['company_id'])->count());
    }
}
