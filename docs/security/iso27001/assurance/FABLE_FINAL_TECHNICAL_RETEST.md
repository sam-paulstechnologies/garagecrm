# Fable final technical re-test — handoff

**Scope: ONLY the ten targeted technical items below.** This is not a request to
re-run the full ISO/adversarial audit. Confirm each item independently against the
deployed staging code and runtime; do not trust this document.

Base: branched from the verified deployed staging SHA
`9c840c9c5af290dc8502364949d5872f8616f5c6`. This is an internal engineering
self-assessment; no external VAPT or ISO certification has been performed.

## Items to re-test

1. **M2 — Quick Scan stuck/failed retention.** A scan may ingest customer history
   then stall/fail before `report_ready`, leaving `report_expires_at` and
   `purge_scheduled_at` NULL. Fix (no schema change): the reconciliation sweep
   (`QuickScanRetentionEnforcer`) derives a deterministic purge deadline from the
   retention anchor — `history_sync_started_at` (set at ingestion), else
   `created_at` — plus `quick_scan.customer_data_retention_hours`, and a `stalled`
   bucket purges any data-bearing scan past that deadline. Verify stuck/failed
   scans are physically purged and accepted/converted scans are never swept.
   Tests: `tests/Feature/QuickScan/QuickScanRetentionEnforcerTest.php`.

2. **M11 — WhatsApp disconnect zero-import orphans.** With zero imported
   candidates, the previous cleanup left message bodies attached to pending
   candidates. Fix in both handlers (`DisconnectService`,
   `MetaEmbeddedSignupService::purgeUnreviewedHistoryQuarantine`): an empty import
   set purges all connection-scoped unreviewed history; imported CRM history,
   Don't-Track and operational records are preserved.
   Tests: `tests/Feature/WhatsAppDisconnectRetentionTest.php`.

3. **M31 — environment fail-closed.** Staging email allowlist + provider-asset
   save-guards now register on `StagingSafety::outboundGuardActive()` (multi-signal)
   not `app()->environment('staging')`. `STAGING_SAFETY_ENFORCED=true` provisioned
   in staging infra. Verify an APP_ENV drift cannot open outbound/email.
   Tests: `tests/Feature/StagingSafetyTest.php`.

4. **M6 — hashed recovery codes + atomicity.** `RecoveryCodeService` stores only
   hashes; plaintext shown once via session; legacy plaintext accepted and migrated
   to hashes on use; consumption stays atomic (row-locked transaction in
   `TwoFactorChallengeController`). Verify hashed-at-rest, one-time use, legacy
   compatibility, and that concurrent same-code use yields exactly one success.
   Tests: `tests/Feature/Security/RecoveryCodeHashingTest.php`,
   `tests/Feature/TwoFactorSecurityTest.php`.

5. **Quick Scan legacy URL token.** The `?quick_scan=<token>` fallback is removed;
   handoff is session-only. Verify a crafted query token binds no scan.
   Tests: `tests/Feature/Security/QuickScanTokenHandoffTest.php`.

6. **Security-maintenance automatic execution.** Converted to a CONTINUOUS webjob
   (`ops/azure/staging/webjobs/sayaraforce-staging-security-maintenance`) that loops
   only `security:run-maintenance`; deploy places it under `jobs/continuous`,
   verifies Running, and guards it never runs the outbound scheduler.
   `WEBJOBS_DISABLE_SCHEDULE=1` stays. Verify recurring execution and zero outbound.
   Tests: `tests/Feature/Security/SecurityMaintenanceTest.php`. Runtime confirmation
   requires reading the deployed continuous webjob status (operator/Azure).

7. **Meta production denylist readiness.** Still fails closed; operator must
   populate `STAGING_META_PRODUCTION_WABA_ID_DENYLIST` and
   `STAGING_META_PRODUCTION_PHONE_NUMBER_ID_DENYLIST` (see
   `ops/azure/staging/meta-production-denylist-handoff.md`). Production identifiers
   were NOT guessed. Verify readiness surfaces via `staging:meta-readiness`.

8. **Encrypted-field restore evidence.** Read-only `staging:verify-encrypted-recovery`
   command + evidence template (`runbooks/backup-restore-runbook.md`). Verify it
   reports decryption pass/fail without printing plaintext and refuses a production
   host. The witnessed restore drill remains operator-pending.
   Tests: `tests/Feature/Security/EncryptedFieldRecoveryTest.php`.

9. **Secret scanner LOW hardening.** Build pass now includes a bare Meta `EAA`
   rule; Stripe skip keys on an explicit fixture marker in the matched value only
   (never a broad line word, never live keys). Verify a real live secret on a line
   containing "example" still fails, and fixtures still pass.
   Tests: `tests/Feature/Security/SecretScannerTest.php`.

10. **Secure session cookie default.** `SESSION_SECURE_COOKIE` fails closed to
    secure in protected environments (and when APP_ENV omitted); local/testing HTTP
    dev unaffected. Tests: `tests/Feature/Security/SessionCookieSecurityTest.php`.

## Explicitly unchanged / still open (do not re-open here)

- **M7 tenant isolation** — residual architectural risk (partial backstop); external
  VAPT target. No live IDOR. Not expanded this sprint by design.
- **CSP** — remains Report-Only until Meta/Stripe UAT.
- **Azure Owner/PIM** — MANAGEMENT ACTION REQUIRED.
- **Geo-redundant backups / RPO/RTO** — MANAGEMENT/DR DECISION.
- **OpenAI DPA** — LEGAL/MANAGEMENT ACTION REQUIRED (technical minimization is
  compensating only, not closed).
- **UAE PDPL** — LEGAL REVIEW REQUIRED.
- **ISO 27001 certification / external VAPT** — NOT DONE.
