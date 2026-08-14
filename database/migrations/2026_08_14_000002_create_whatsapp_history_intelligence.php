<?php

use App\Commercial\Capabilities;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->recoverLegacyClientsShape();

        // Production's canonical baseline contains this operational table, but
        // the legacy repository migration chain never created it. A guarded
        // definition makes post-baseline and SQLite clean installs converge
        // without touching an existing canonical table.
        if (! Schema::hasTable('conversations')) {
            Schema::create('conversations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id');
                $table->foreignId('client_id')->nullable();
                $table->string('subject')->nullable();
                $table->timestamp('latest_message_at')->nullable();
                $table->boolean('is_whatsapp_linked')->default(true);
                $table->foreignId('lead_id')->nullable();
                $table->string('customer_name')->nullable();
                $table->string('customer_phone', 40)->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->string('last_message_preview')->nullable();
                $table->unsignedInteger('unread_count')->default(0);
                $table->timestamps();
                $table->index('company_id', 'conversations_company_id');
                $table->index('client_id', 'conversations_client_id');
                $table->index('latest_message_at', 'conversations_latest_message_at');
            });
        }

        Schema::create('whatsapp_history_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('messaging_connection_id')->nullable()
                ->constrained('messaging_connections', 'id', 'wa_hist_batch_connection_fk')->nullOnDelete();
            $table->char('connection_scope_hash', 64);
            $table->string('status', 32)->default('pending_sync');
            $table->unsignedInteger('contacts_discovered')->default(0);
            $table->unsignedInteger('contacts_eligible')->default(0);
            $table->unsignedInteger('contacts_analysed')->default(0);
            $table->unsignedInteger('pending_review')->default(0);
            $table->unsignedInteger('track_selected')->default(0);
            $table->unsignedInteger('dont_track_selected')->default(0);
            $table->unsignedInteger('new_clients')->default(0);
            $table->unsignedInteger('matched_clients')->default(0);
            $table->unsignedInteger('messages_imported')->default(0);
            $table->unsignedInteger('excluded_contacts')->default(0);
            $table->unsignedInteger('failed_records')->default(0);
            $table->string('last_error_code', 80)->nullable();
            $table->timestamp('sync_started_at')->nullable();
            $table->timestamp('sync_completed_at')->nullable();
            $table->timestamp('review_expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status'], 'wa_history_batches_company_status_idx');
            $table->index(['company_id', 'connection_scope_hash'], 'wa_history_batches_scope_idx');
        });

        Schema::create('whatsapp_history_candidates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('whatsapp_history_import_batch_id')
                ->constrained('whatsapp_history_import_batches', 'id', 'wa_hist_candidate_batch_fk')->cascadeOnDelete();
            $table->foreignId('messaging_connection_id')->nullable()
                ->constrained('messaging_connections', 'id', 'wa_hist_candidate_connection_fk')->nullOnDelete();
            $table->char('external_identity_hash', 64);
            $table->longText('phone_e164')->nullable();
            $table->longText('display_name')->nullable();
            $table->unsignedInteger('message_count')->default(0);
            $table->unsignedInteger('inbound_count')->default(0);
            $table->unsignedInteger('outbound_count')->default(0);
            $table->timestamp('first_message_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_live_message_at')->nullable();
            $table->char('analysis_fingerprint', 64)->nullable();
            $table->string('intelligence_status', 24)->default('unselected');
            $table->string('classification', 32)->default('unknown');
            $table->decimal('classification_confidence', 5, 4)->nullable();
            $table->longText('classification_reason')->nullable();
            $table->string('retention_level', 24)->default('none');
            $table->decimal('retention_confidence', 5, 4)->nullable();
            $table->longText('retention_reason')->nullable();
            $table->string('review_decision', 24)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('analysis_requested_at')->nullable();
            $table->timestamp('analysed_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('imported_client_id')->nullable()->constrained('clients')->nullOnDelete();
            // The canonical MySQL baseline contains conversations, but the
            // repository's legacy SQLite migration path still does not. Keep
            // this additive reference portable and enforce company ownership
            // in HistoryImporter until that legacy migration debt is removed.
            $table->unsignedBigInteger('imported_conversation_id')->nullable()->index();
            $table->string('import_status', 24)->default('not_requested');
            $table->timestamp('imported_at')->nullable();
            $table->timestamp('staged_content_purged_at')->nullable();
            $table->boolean('unsupported')->default(false);
            $table->timestamps();
            $table->unique(
                ['whatsapp_history_import_batch_id', 'external_identity_hash'],
                'wa_history_candidate_batch_identity_unique'
            );
            $table->index(['company_id', 'review_decision'], 'wa_history_candidates_company_review_idx');
            $table->index(['company_id', 'intelligence_status'], 'wa_history_candidates_company_intel_idx');
            $table->index(['company_id', 'retention_level'], 'wa_history_candidates_company_retention_idx');
        });

        Schema::create('whatsapp_history_contact_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->char('external_identity_hash', 64);
            $table->foreignId('first_batch_id')->nullable()->constrained('whatsapp_history_import_batches')->nullOnDelete();
            $table->foreignId('first_candidate_id')->nullable()->constrained('whatsapp_history_candidates')->nullOnDelete();
            $table->unsignedInteger('allowance_snapshot')->nullable();
            $table->string('entitlement_source', 32)->nullable();
            $table->timestamp('analysed_at')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'external_identity_hash'], 'wa_history_usage_company_identity_unique');
        });

        Schema::create('whatsapp_tracking_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->char('external_identity_hash', 64);
            $table->longText('phone_e164')->nullable();
            $table->string('decision', 24)->default('pending');
            $table->foreignId('source_batch_id')->nullable()
                ->constrained('whatsapp_history_import_batches', 'id', 'wa_tracking_batch_fk')->nullOnDelete();
            $table->foreignId('source_candidate_id')->nullable()
                ->constrained('whatsapp_history_candidates', 'id', 'wa_tracking_candidate_fk')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('history_removed_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'external_identity_hash'], 'wa_tracking_company_identity_unique');
            $table->index(['company_id', 'decision'], 'wa_tracking_company_decision_idx');
        });

        Schema::create('whatsapp_history_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('whatsapp_history_import_batches')->nullOnDelete();
            $table->foreignId('candidate_id')->nullable()->constrained('whatsapp_history_candidates')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 80);
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['company_id', 'event', 'created_at'], 'wa_history_audit_company_event_idx');
        });

        Schema::create('entitlement_usage_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('capability', 120);
            $table->string('resource_type', 80);
            $table->string('resource_key', 191);
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->unsignedInteger('units')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(
                ['company_id', 'capability', 'resource_type', 'resource_key', 'period_start'],
                'entitlement_usage_event_unique'
            );
            $table->index(['company_id', 'capability', 'period_start'], 'entitlement_usage_event_period_idx');
        });

        Schema::table('whatsapp_history_messages', function (Blueprint $table): void {
            $table->foreignId('whatsapp_history_import_batch_id')->nullable()->after('company_id')
                ->constrained('whatsapp_history_import_batches', 'id', 'wa_history_message_batch_fk')->nullOnDelete();
            $table->foreignId('whatsapp_history_candidate_id')->nullable()->after('whatsapp_history_import_batch_id')
                ->constrained('whatsapp_history_candidates', 'id', 'wa_history_message_candidate_fk')->nullOnDelete();
            $table->char('external_identity_hash', 64)->nullable()->after('phone_number_id');
            $table->string('source', 50)->default('whatsapp_coexistence_history')->after('message_type');
            $table->timestamp('purged_at')->nullable()->after('message_timestamp');
            $table->index(
                ['company_id', 'external_identity_hash', 'message_timestamp'],
                'wa_history_company_identity_time_idx'
            );
        });

        Schema::table('message_logs', function (Blueprint $table): void {
            $table->boolean('is_historical')->default(false)->after('source');
            $table->foreignId('whatsapp_history_candidate_id')->nullable()->after('is_historical')
                ->constrained('whatsapp_history_candidates')->nullOnDelete();
            $table->timestamp('historical_message_timestamp')->nullable()->after('whatsapp_history_candidate_id');
            $table->index(['company_id', 'is_historical'], 'message_logs_company_historical_idx');
        });

        $this->reconcilePrelaunchCommercialEntitlements();
    }

    public function down(): void
    {
        Schema::table('message_logs', function (Blueprint $table): void {
            $table->dropIndex('message_logs_company_historical_idx');
            $table->dropForeign('message_logs_whatsapp_history_candidate_id_foreign');
            $table->dropColumn('whatsapp_history_candidate_id');
            $table->dropColumn(['is_historical', 'historical_message_timestamp']);
        });
        Schema::table('whatsapp_history_messages', function (Blueprint $table): void {
            $table->dropIndex('wa_history_company_identity_time_idx');
            $table->dropForeign('wa_history_message_candidate_fk');
            $table->dropForeign('wa_history_message_batch_fk');
            $table->dropColumn(['whatsapp_history_candidate_id', 'whatsapp_history_import_batch_id']);
            $table->dropColumn(['external_identity_hash', 'source', 'purged_at']);
        });

        Schema::dropIfExists('entitlement_usage_events');
        Schema::dropIfExists('whatsapp_history_audit_logs');
        Schema::dropIfExists('whatsapp_tracking_preferences');
        Schema::dropIfExists('whatsapp_history_contact_usages');
        Schema::dropIfExists('whatsapp_history_candidates');
        Schema::dropIfExists('whatsapp_history_import_batches');
    }

    private function reconcilePrelaunchCommercialEntitlements(): void
    {
        if (! Schema::hasTable('plan_versions') || ! Schema::hasTable('plan_entitlements')) {
            return;
        }

        foreach ((array) config('commercial.plans') as $planCode => $definition) {
            $versionId = DB::table('plan_versions')
                ->where('code', $planCode.':'.config('commercial.catalogue_version'))
                ->value('id');
            if (! $versionId) {
                continue;
            }

            foreach (Capabilities::tenant() as $capability) {
                $isLimit = Capabilities::isLimit($capability);
                $limits = (array) ($definition['limits'] ?? []);
                $allowance = $isLimit ? ($limits[$capability] ?? null) : null;
                $enabled = $isLimit
                    ? array_key_exists($capability, $limits)
                    : in_array($capability, (array) ($definition['capabilities'] ?? []), true);
                $mode = data_get($definition, 'modes.'.$capability, $enabled ? 'enabled' : 'disabled');

                DB::table('plan_entitlements')->updateOrInsert(
                    ['plan_version_id' => $versionId, 'capability' => $capability],
                    [
                        'value_type' => $isLimit ? 'integer' : 'boolean',
                        'value' => json_encode($isLimit ? ['allowance' => $allowance] : ['enabled' => $enabled]),
                        'allowance' => $allowance,
                        'enabled' => $enabled,
                        'mode' => $mode,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }

        if (Schema::hasTable('entitlement_audit_logs')) {
            DB::table('subscriptions')->orderBy('id')->get(['id', 'company_id'])->each(function (object $subscription): void {
                DB::table('entitlement_audit_logs')->insert([
                    'company_id' => $subscription->company_id,
                    'subscription_id' => $subscription->id,
                    'event' => 'commercial.prelaunch_limits_reconciled',
                    'source' => 'migration',
                    'context' => json_encode(['catalogue' => config('commercial.catalogue_version')]),
                    'created_at' => now(),
                ]);
            });
        }
    }

    private function recoverLegacyClientsShape(): void
    {
        if (! Schema::hasTable('clients') || Schema::hasColumn('clients', 'company_id')) {
            return;
        }

        // The production-derived baseline already has this canonical shape.
        // These guarded additions only repair databases built from the older,
        // incomplete repository migration so history import can stay tenant-scoped.
        Schema::table('clients', function (Blueprint $table): void {
            $table->unsignedBigInteger('company_id')->nullable()->after('id')->index();
            $table->string('phone_norm', 30)->nullable()->after('phone')->index();
            $table->string('whatsapp', 20)->nullable()->after('phone_norm');
            $table->string('email_norm')->nullable()->after('email')->index();
            $table->string('status', 50)->default('active');
            $table->string('preferred_channel', 20)->default('phone');
            $table->boolean('is_vip')->default(false);
            $table->boolean('is_archived')->default(false);
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->string('email')->nullable()->change();
            if (Schema::hasColumn('clients', 'vehicle')) {
                $table->string('vehicle')->nullable()->change();
            }
        });
    }
};
