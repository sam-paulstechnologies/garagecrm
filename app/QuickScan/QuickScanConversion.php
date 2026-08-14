<?php

namespace App\QuickScan;

use App\Models\QuickScan\QuickScanWorkspace;
use App\Models\System\Company;
use App\Models\WhatsApp\WhatsAppHistoryCandidate;
use App\Models\WhatsApp\WhatsAppHistoryImportBatch;
use App\Models\WhatsApp\WhatsAppHistoryMessage;
use App\Services\WhatsApp\History\HistoryIdentity;
use Illuminate\Support\Facades\DB;

final class QuickScanConversion
{
    public function __construct(
        private readonly QuickScanAudit $audit,
        private readonly HistoryIdentity $identity,
    ) {}

    public function accept(QuickScanWorkspace $scan): QuickScanWorkspace
    {
        if ($scan->status === 'report_ready') {
            $scan->forceFill(['status' => 'accepted', 'accepted_at' => now(), 'outcome' => 'start_sayaraforce'])->save();
            $this->audit->record($scan, 'quick_scan.start_clicked', context: [
                'status' => 'accepted', 'outcome' => 'start_sayaraforce',
            ]);
        }

        return $scan->fresh();
    }

    public function bindToCompany(QuickScanWorkspace $scan, Company $company): QuickScanWorkspace
    {
        return DB::transaction(function () use ($scan, $company): QuickScanWorkspace {
            $scan = QuickScanWorkspace::query()->lockForUpdate()->findOrFail($scan->id);
            if ($scan->converted_company_id) {
                abort_unless((int) $scan->converted_company_id === (int) $company->id, 409);

                return $scan;
            }
            abort_unless($scan->status === 'accepted' && ! $scan->purged_at, 422);
            $batch = WhatsAppHistoryImportBatch::query()->create([
                'company_id' => $company->id,
                'messaging_connection_id' => null,
                'connection_scope_hash' => hash_hmac('sha256', 'quick-scan|'.$scan->public_id, (string) config('messaging.history.hmac_key')),
                'status' => 'awaiting_review',
                'contacts_discovered' => $scan->candidates()->count(),
                'contacts_eligible' => $scan->candidates()->whereNotIn('classification', ['possible_personal', 'possible_colleague'])->count(),
                'contacts_analysed' => 0,
                'pending_review' => $scan->candidates()->count(),
                'sync_started_at' => $scan->history_sync_started_at,
                'sync_completed_at' => $scan->history_sync_completed_at,
                'review_expires_at' => now()->addDays(max(1, (int) config('messaging.history.review_retention_days', 30))),
            ]);

            foreach ($scan->candidates()->with('messages')->orderBy('id')->get() as $source) {
                $phone = (string) $source->customer_identifier;
                $candidate = WhatsAppHistoryCandidate::query()->create([
                    'company_id' => $company->id,
                    'whatsapp_history_import_batch_id' => $batch->id,
                    'external_identity_hash' => $this->identity->hash($phone),
                    'phone_e164' => '+'.preg_replace('/\D+/', '', $phone),
                    'display_name' => $source->display_name,
                    'message_count' => $source->message_count,
                    'inbound_count' => $source->inbound_count,
                    'outbound_count' => $source->outbound_count,
                    'first_message_at' => $source->first_message_at,
                    'last_message_at' => $source->last_message_at,
                    // Scan findings remain suggestions. Tenant deep analysis
                    // must consume the tenant's own canonical allowance.
                    'intelligence_status' => 'unselected',
                    'classification' => $source->classification,
                    'retention_level' => $source->retention_level,
                    'review_decision' => 'pending',
                ]);
                foreach ($source->messages as $message) {
                    WhatsAppHistoryMessage::query()->create([
                        'company_id' => $company->id,
                        'whatsapp_history_import_batch_id' => $batch->id,
                        'whatsapp_history_candidate_id' => $candidate->id,
                        'phone_number_id' => 'quick-scan-handoff',
                        'external_identity_hash' => $candidate->external_identity_hash,
                        'source_fingerprint' => hash('sha256', 'quick-scan-handoff|'.$company->id.'|'.$message->source_fingerprint),
                        'provider_message_id' => null,
                        'direction' => $message->direction,
                        'message_type' => $message->message_type,
                        'source' => 'quick_scan_handoff',
                        'customer_identifier' => $phone,
                        'body' => $message->body,
                        'metadata' => ['source' => 'quick_scan_handoff', 'provider_identifiers_transferred' => false],
                        'message_timestamp' => $message->message_timestamp,
                    ]);
                }
            }
            $scan->forceFill(['converted_company_id' => $company->id, 'outcome' => 'converted'])->save();
            $this->audit->record($scan, 'quick_scan.converted', context: [
                'status' => 'accepted', 'outcome' => 'converted', 'converted_company_id' => $company->id,
                'contacts_discovered' => $batch->contacts_discovered,
            ]);

            return $scan->fresh();
        }, 3);
    }
}
