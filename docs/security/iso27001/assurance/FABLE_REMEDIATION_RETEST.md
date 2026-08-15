# Fable remediation — targeted re-test package (2026-08)

**Do not re-run the whole audit.** This scopes the next Fable pass to independently
verify the remediation of the findings below. Baseline: the deployed staging
source `b3a96425` (`origin/staging`, matches the live `DEPLOYED_COMMIT`). All
changes live on branch `security/fable-remediation-2026-08` and are proposed to
`staging` via PR (production untouched).

Full automated suite after remediation: **439 passed, 1 skipped, 0 failed.**

## Remediation evidence

| ID | Sev | Reproduced on b3a96425? | Change | Test / verification | Residual |
|----|-----|--------------------------|--------|---------------------|----------|
| H1 | HIGH | Yes (prod workflow: no perms, publish-profile, no gates, ships tests/docs) | Hardened `main_app-sayaraforce.yml` (least-priv, OIDC fail-closed, full gate suite, artifact hygiene, deploy marker, rollback marker); build-output secret scan; prod-OIDC provisioning plan | Workflow authored (not adopted); scanner smoke-tested (1698 files) | Prod OIDC identity + SHA-pinning are human ops (see hardening doc). Prod deploy still MANUAL/reviewed — out of sprint scope |
| H2 | HIGH | Yes (enforcement `off` by default disables MFA+step-up silently) | `SecurityConfigurationValidator` fails closed in protected envs; `/healthz?check=security` (503 on critical); boot log | 8 tests (unset/off/unknown/audit/required_admins × env); existing 2FA suite green | Confirm `TWO_FACTOR_ENFORCEMENT=required_admins` at runtime (it is on staging) |
| M1 | MED | Yes (`billing:enforce-grace-periods` unscheduled) | `security:run-maintenance` (isolated, no-outbound) schedules grace enforcement + retention; dedicated staging webjob | BillingEngineTest green | Operator must enable scheduled webjobs for the isolated job (WEBJOBS_DISABLE_SCHEDULE=1) — see webjob README |
| M2 | MED | Yes (no purge reconciliation; abandoned scans retain PII) | `QuickScanRetentionEnforcer` + `quick-scan:enforce-retention` — physical purge of due-but-lost + abandoned/expired; never purges accepted | 5 tests (abandoned purged, accepted preserved, dry-run, fresh kept) | Same webjob enablement note |
| M3 | MED | Yes (CSP had no script-src/default-src) | Strong CSP built from config, shipped **Report-Only** (`SECURITY_CSP_ENFORCE` to enforce); baseline protections kept | 3 tests (report-only default, enforce mode, X-Powered-By gone) | Enforce after Meta-UAT confirms inline-script compatibility (nonces) |
| M4 | MED | Yes (name/phone/message sent to OpenAI) | `PromptPiiMinimizer` strips name/phone/email; redacts bodies; preserves service semantics | 10 tests | OpenAI DPA still open — LEGAL/MANAGEMENT |
| M5 | MED | Yes (cred change didn't invalidate sessions/tokens) | `SecuritySessionInvalidator` on reset/change/2FA-disable/admin-reset | 3 tests | — |
| M6 | MED | Yes (recovery-code double-use race; encrypted-not-hashed) | Atomic consume (row lock + transaction) | TwoFactorSecurityTest 30 green | **Hashed-at-rest deferred** — needs Fortify generation/display refactor + migration (see "Deferred") |
| M8 | MED | Yes (empty production denylists, invisible) | `denylistReadiness()` surfaces empty required denylists; provider-asset assertion fails closed | StagingSafetyTest extended | Operator must populate `STAGING_PRODUCTION_DB_HOST_DENYLIST`, `STAGING_META_PRODUCTION_WABA_ID_DENYLIST`, `STAGING_META_PRODUCTION_PHONE_NUMBER_ID_DENYLIST` with real prod IDs |
| M10 | MED | Yes (phones/emails/bodies not redacted) | Expanded key + value redaction with depth guard | 14 tests | — |
| M11 | MED | Yes (disconnect left history quarantine) | Disconnect purges UNREVIEWED history quarantine; preserves imported CRM/Don't-Track/invoices | WhatsAppDisconnectRetentionTest | — |
| M31 | MED | Yes (guards rest on one env string) | Ambiguous/unknown env fails closed for outbound + provider assets; explicit `STAGING_SAFETY_ENFORCED` | StagingSafetyTest extended | — |
| M7 | MED | Yes (global scope on 4/75 models; manual-check fragility) | `TenantContext` + `TenantOwned`; active backstop on Client/Vehicle/Campaign; marker on Invoice/Conversation/MessageLog; explicit `runAsPlatform` bypass | Adversarial Garage A/B test (3) | Active scope on join-heavy models (Invoice/Conversation/MessageLog) deferred — needs qualified-column review per relation |
| LOW: fake-billing secret | LOW | Yes (fell back to APP_KEY) | Dedicated secret only; fails closed if unset | FakeBillingWebhookSecretTest | — |
| LOW: Meta Lead-Ads sig | LOW | Yes (skipped outside prod) | HMAC required in all envs, fail closed | MetaLeadWebhookSignatureTest | — |
| LOW: QS URL token | LOW | Yes (token in register URL) | Server-side session handoff; clean URL | QuickScanTokenHandoffTest + updated QuickScanTest | — |
| LOW: dead controllers | LOW | Yes (6 unrouted weak controllers) | Deleted after proving unreferenced | Full suite green | — |
| LOW: direct prod git remote | LOW | Yes | Local `azure` push URL disabled (fetch untouched, Azure never contacted); prod is CI-only | `git push azure` fails locally | Workstation-level; documented |
| LOW: X-Powered-By | LOW | Yes | Removed in ApplySecurityHeaders | CSP test | Duplicate headers are Azure-platform layer (cosmetic) |

## Deferred (documented, not silently dropped)

- **M6 hashed recovery codes at rest** — the atomic race is fixed; hashing requires
  refactoring Fortify's generation/display flow (plaintext shown once, hashes
  stored) plus a backward-compatible migration and rework of ~4 tests that read
  plaintext via `recoveryCodes()`. Proposed as its own reviewed change.
- **M7 active scope on join-intermediate models** (Invoice/Conversation/MessageLog)
  — deferred because a bare-column scope is ambiguous under `hasManyThrough`
  joins and a qualified one regressed a pre-existing model; needs per-relation
  qualified-column review. Marker interface + manual checks retain isolation.
- **User::$fillable role/company_id** (Phase 38) — NOT removed: incompatible.
  `User::factory()->create(['company_id'=>…,'role'=>…])` is used throughout the
  suite; removal breaks factory-based test data. No live exploit exists
  (registration hardcodes `admin`; controllers set `company_id` server-side).

## Requires operator / external action (not code)

- Enable scheduled webjobs for the isolated maintenance job (or trigger externally).
- Populate the three production denylists (M8) with real production identifiers.
- Provision production OIDC + SHA-pin actions before adopting the hardened prod workflow (H1).
- Restore encrypted-field verification (Phase 42): the staging restore server exists,
  but decryption-after-restore could NOT be verified from this environment (private
  DB, no VNet path). Procedure documented; run from within the staging network.
- Management: single subscription Owner / PIM (M9); backup geo-redundancy.
- Legal: OpenAI DPA; UAE PDPL applicability.

## Targeted re-test scope for Fable

Independently verify (against the deployed remediation SHA, not this branch's local state):
`H1, H2, M1, M2, M3, M4, M5, M6 (atomic), M7 (backstop + bypass), M8, M10, M11, M31`
plus: source-of-truth/deploy-marker alignment, Quick Scan URL-token handoff, build-output
secret scan, Meta Lead-Ads signature, fake-billing secret separation, dead-code removal,
local prod-remote guard. Confirm the **deferred** items are honestly represented and the
operator/management/legal actions remain open.

**Claim ceiling unchanged:** "SayaraForce has implemented an ISO/IEC 27001:2022-aligned
security baseline on staging and has undergone an internal independent adversarial
security review." NOT certified / pen-tested / fully secure / PDPL-compliant / E2E-encrypted.
