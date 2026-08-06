# SayaraForce canonical MySQL schema

## Decision and safety boundary

The canonical fresh-install strategy is **Strategy B: sanitized production structure plus reviewed corrections**. A strictly read-only production audit established 103 InnoDB base tables, two invalid views, 111 foreign keys, 41 non-primary unique indexes, 356 normal indexes, and no triggers, routines, functions, or events. The base-table structure is the authoritative source because it is the structure serving the live application; repository migrations alone are incomplete and internally inconsistent.

No production application row was queried except the Laravel `migrations` ledger. The export used `--no-data`, excluded both invalid views, was sanitized outside the repository, and passed the data-safety scan before it became the baseline. Production was not migrated, repaired, restarted, or otherwise changed.

Authoritative artifacts:

- `database/schema/mysql-schema.sql`: 103 production-derived base tables, two portable views, and reviewed migration-cutoff metadata.
- `database/schema/mysql-schema.manifest.json`: object inventory, migration classifications, hashes, and canonical decisions.
- `database/schema/mysql-schema.safety.json`: machine-readable data-safety result.
- `ops/azure/staging/production-view-remediation.sql`: unexecuted production repair plan.

The sanitized production structural fingerprint is `ccc57e2ed0b89978ade9cf9ee7bca374c33b77d7a6646bd18a23a13a4bf29299`. After the pending messaging migration, the reproducible canonical fingerprint is `379628225a4c72c4e7eb236e447c90d7dd1da592dc340a5a3ea9cf99e256c21e`.

## Reconciled inventories

Production contains 103 base tables and the two named views. The complete base-table list is stored in the manifest rather than duplicated here. The major classifications are:

| Classification | Objects |
|---|---|
| Active tenant operations | `companies`, `garages`, `users`, `clients`, `vehicles`, `leads`, `opportunities`, `bookings`, `jobs`, `invoices`, `conversations`, `conversation_participants` |
| Messaging and WhatsApp already in production | `message_logs`, `whatsapp_messages`, `whatsapp_connect_sessions`, `whatsapp_connection_audits`, `whatsapp_webhook_events`, `whatsapp_synced_contacts`, `whatsapp_history_messages`, template/usage tables |
| Marketing and journeys | `campaigns`, `campaign_steps`, `campaign_enrollments`, `journeys`, `journey_steps`, `journey_enrollments`, `audiences`, rules/memberships/mappings |
| Framework/runtime | `migrations`, `cache`, `cache_locks`, `sessions`, `job_batches`, `failed_jobs`, `laravel_failed_jobs`, `queue_jobs`, `notifications`, password reset and token tables |
| Third-party package | Spatie permission/role tables and Sanctum `personal_access_tokens` |
| Retained legacy pending later cleanup | `communication_logs_backup` and the candidates listed in the manifest |
| Portable views | `vw_ai_metrics_daily`, `vw_journey_summary` |

The normal local `garagecrm` database has only 25 base tables and eight recorded migrations. It contains the Laravel skeleton/runtime tables and the seven new messaging tables, but lacks most live operational structures. Its two views are invalid. It is therefore unsuitable as a structural source and was not altered during recovery.

## Four-way reconciliation decisions

| Area | Production | Repository/code result | Canonical decision |
|---|---|---|---|
| Workshop jobs | `jobs` has operational garage columns | `App\Models\Job\Job`, controllers, and services use `jobs`; the initial Laravel migration incorrectly defines queue storage there | Retain production operational `jobs`; mark the defective initial migration represented so it can never run after the baseline |
| Database queue | `queue_jobs` exists; continuous WebJob runs `queue:work database` while web requests use `sync` | Queue configuration names `queue_jobs` | Retain `queue_jobs` separately; there is no collision |
| Failed queue records | Both `failed_jobs` and `laravel_failed_jobs` exist | Current code reads `failed_jobs`; historical migration also created `laravel_failed_jobs` | Retain both production objects pending a later cleanup decision |
| Jobsheets | `jobsheets` is absent; `job_cards` is present | Old migration creates both; only `DemoSeeder` still writes `jobsheets` | Exclude `jobsheets`; classify the seeder reference as dead/legacy until separately repaired |
| Invoice items | `invoice_items` is absent; `invoices` is live | Old migration and `DemoSeeder` reference `invoice_items`; current invoice controllers use `invoices` directly | Exclude `invoice_items`; do not invent a table from stale migration code |
| Job documents | `job_documents` exists | Production ledger contains an extra `2025_09_13_180003...` identifier while the repository contains `2025_09_13_175925...` | Include the structure and represent the repository migration; retain the production-only identifier only in reconciliation metadata |
| Phase 1 messaging | Seven `messaging_*` tables are absent from production | Migration `2026_08_05_000001_create_messaging_core_tables` owns them | Keep all seven out of the baseline and run the migration once afterward |
| WhatsApp coexistence/history | Present and used | The hardening migration is tracked and code uses the tables | Include in baseline and mark migration represented |
| Optional WhatsApp locks/opt-outs | `automation_action_locks` and `whatsapp_opt_outs` are absent | Code guards both with `Schema::hasTable` and has cache/column fallbacks | Treat as conditional features, not canonical tables |
| Public enquiry delivery | Not in the audited production structure or tracked baseline commit | A pre-existing untracked migration/test change exists in the working tree | Exclude from this commit and preserve the unrelated work untouched |
| Legacy candidates | Present in production, ownership unclear | Some have no current route/controller use | Retain temporarily because removing production structures is outside this phase; manifest records the review list |

All live application surfaces tested against the disposable MySQL installation loaded without missing-table or SQL errors. This is stronger evidence than the existing SQLite tests, which frequently build partial fixtures.

## Migration cutoff

The production ledger has 41 entries, but it is not copied blindly. Each tracked migration is classified in the manifest against the resulting structure.

- Forty tracked migrations are represented by the baseline.
- `2026_08_05_000001_create_messaging_core_tables` is the first and only pending tracked migration.
- It creates exactly seven messaging tables once, producing 110 base tables in the validated fresh database.
- The queue-shaped `0001_01_01_000002_create_jobs_table` is marked represented because running it would conflict with operational `jobs`.
- The static-data migration `2026_06_12_000001_add_vehicle_renewal_audience_segmentations` is represented structurally, but its rows are deliberately excluded by the data-free policy.
- Laravel loads the schema SQL only when the target database is empty. A populated database continues from its `migrations` ledger, and a second `migrate --force` has no pending migration or duplicate-table effect.

## View recovery

### `vw_ai_metrics_daily`

The production metadata exposes a structurally valid aggregation over `message_logs`. It outputs `report_date`, `company_id`, AI/template/human counts, average confidence, and alert count. The likely production failure is the stale SQL SECURITY DEFINER identity: all referenced tables and columns exist, but the definer differs from the application user.

The canonical view removes database qualification, uses `SQL SECURITY INVOKER`, and preserves the query semantics. Supporting production indexes include the company/created-at indexes plus indexes on `source` and `created_at`. `App\Services\Metrics\InsightsService` queries the view.

### `vw_journey_summary`

The recovered production query joins `journeys`, `journey_enrollments`, `leads`, and `opportunities`, returning journey/tenant identity and enrollment, lead, opportunity, and won totals. All dependencies exist. The stale definer is again the likely cause of MySQL error 1356.

One reviewed semantic correction is required: the recovered definition compares `opportunities.stage` with legacy value `closed_won`, but the production enum no longer permits that value and `App\Models\Client\Opportunity::STAGE_CLOSED_WON` aliases to `booking_confirmed`. The canonical view therefore counts `booking_confirmed` as `total_closed_won`. This is explicitly reconstructed SQL, not claimed as a verbatim production definition. Supporting indexes cover journey/company enrollment lookups, polymorphic enrollment targets, lead IDs, and opportunity company/lead/stage lookups. The reporting model/service/controller consume the view.

Both INVOKER views were created and queried with controlled synthetic records in each validation cycle. The AI view produced the expected source aggregation. The journey view produced one enrollment, one lead, one opportunity, and one won opportunity.

## Disposable validation

The guarded validator refuses a non-local host, a database name without `staging_validation`, or the normal development database. Each cycle dropped and recreated only `sayaraforce_staging_validation`, loaded the canonical baseline through Laravel, ran the pending messaging migration, seeded two synthetic tenants, verified 128 foreign-key constraints, created and queried both views, booted Laravel, discovered routes, and ran the representative application-surface integration test.

Cycle results:

| Check | Cycle one | Cycle two |
|---|---:|---:|
| Base tables | 110 | 110 |
| Views | 2 | 2 |
| Messaging tables | 7 exactly once | 7 exactly once |
| Synthetic companies/garages | 2 / 2 | 2 / 2 |
| Foreign-key constraints checked | 128 | 128 |
| Foreign-key violations | 0 | 0 |
| Structural fingerprint | `379628...c21e` | `379628...c21e` |

The focused WhatsApp/staging/baseline suite passed 28 tests with 165 assertions (one MySQL-only test intentionally skipped outside guarded integration mode). The full suite passed 200 tests with 1,264 assertions and one intentional integration skip. The guarded MySQL surface test passed 33 assertions in both cycles. PHP lint passed for 594 files, and the Vite production build succeeded in an operating-system temporary directory so existing generated frontend work was untouched.

## Test-harness audit

| Fixture pattern | Classification | Decision |
|---|---|---|
| `AdminResponsiveShellTest` creates minimal clients/leads/opportunities/bookings/jobs/invoices tables when absent | Isolated SQLite presentation fixture; previously capable of masking missing migrations | Retain narrowly for fast UI-shell behavior, but it is not accepted as clean-install evidence |
| `BookingLifecycleRepairTest` drops/recreates operational `jobs` and related lifecycle tables | Isolated SQLite lifecycle fixture; masks the repository jobs migration defect if treated as install evidence | Retain for lifecycle behavior only; canonical MySQL integration now proves the real `jobs` shape |
| `ManagerLifecycleInvariantTest` drops/recreates jobs/invoices and related tables | Large isolated SQLite controller fixture; masks schema mismatch if used as install evidence | Retain pending future fixture refactoring; do not use it as schema evidence |
| Other feature tests that conditionally create operational tables | Isolated compatibility fixtures | Retain unless separately refactored; the new canonical test and two-cycle validator are mandatory clean-install gates |
| `StagingSchemaBaselineTest` | Canonical static and guarded MySQL integration test | Required; it creates no operational tables and consumes the real baseline |

## Data-safety result

The baseline contains no customer/application rows, grants, credentials, hostnames, production database names, environment-specific paths, fixed definers, or nonzero AUTO_INCREMENT counters. There are no `REPLACE`, `LOAD DATA`, or `COPY` commands. The only `INSERT` target is `migrations`, containing reviewed schema-cutoff metadata. Scans found no email/phone values, password hashes, token values, Meta asset values, WABA/phone-number values, or Smart Matrix references.

The baseline preserves mixed `utf8mb4_unicode_ci` and `utf8mb4_0900_ai_ci` collations exactly because changing collation during recovery would be an unreviewed semantic migration. Normalize them later only through a dedicated migration validated in staging.

## Production view remediation

Production view repair is intentionally not part of baseline execution. The unexecuted SQL plan performs dependency checks, replaces both views idempotently with INVOKER security, provides a safe rollback definition, and validates only result metadata. Its promotion path is mandatory:

local validation → staging deployment → staging verification → explicit production approval.

No operator should run the plan until the exact release is approved and a rollback artifact from the previously deployed view release is retained.
