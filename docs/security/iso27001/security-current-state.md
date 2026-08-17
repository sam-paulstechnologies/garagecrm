# Current-state security assessment

Assessment date: 2026-08-15. Evidence was gathered from the current `origin/staging` source tree, local automated tests, dependency manifests, and read-only Azure staging inspection.

## Established controls

- Company-scoped route binding, queries, policies and adversarial tenant-isolation tests cover critical CRM, billing, messaging and security paths.
- Fortify password, mandatory admin TOTP, one-time recovery codes, recent step-up, rate limiting, session regeneration and security audit events are implemented.
- Commercial entitlements fail closed and use stable plan/capability identities.
- Stripe Sandbox uses signature validation, test-mode guards, idempotent provider events and mapped internal prices; browser returns do not activate entitlements.
- Meta/WhatsApp webhook verification, asset allow/deny controls, replay/idempotency and provider-controlled identifiers exist.
- App Service is staging-isolated, HTTPS only, TLS 1.2 minimum, FTPS disabled, system managed identity enabled, health checked, with a separate MySQL server, Key Vault, storage and observability.
- Staging outbound WhatsApp and SMS are disabled, mail is logged, the scheduler is disabled, and the queue worker is isolated.

## Remediation implemented in this release

- Private, tenant-scoped upload storage (`App\Security\Uploads\PrivateUploadStorage`) with content/magic-MIME validation and authenticated streaming. All live upload paths use the private disk; a dead public-disk `Tenant\JobCardController` (wrote to `Storage::disk('public')`) was removed.
- Security headers, request correlation, cache prevention on sensitive pages and log-context redaction. A strong Content-Security-Policy with `default-src`/`script-src` was introduced but ships **Report-Only** by default (staged rollout; enforce via `SECURITY_CSP_ENFORCE`, `App\Security\ContentSecurityPolicy`). The header previously enforced had no `script-src`/`default-src` and provided little XSS mitigation; XSS mitigation from CSP remains **partial/staged** until the enforce flag is switched on after Meta/Stripe compatibility UAT.
- Mandatory privileged MFA now fails **closed** in protected environments: an unset/invalid `TWO_FACTOR_ENFORCEMENT` forces privileged enrolment and step-up rather than silently disabling MFA (`App\Security\SecurityConfigurationValidator`, `App\Security\TwoFactorPolicy`).
- Sessions and API tokens are invalidated on password/2FA/credential change (`App\Security\SecuritySessionInvalidator`).
- AI prompts sent to the third-party model (OpenAI) are PII-minimized — name, phone and email are stripped before send (`App\Services\Ai\PromptPiiMinimizer`, applied in `NlpService`). This reduces but does not remove supplier-processing exposure; no OpenAI DPA is signed (legal action open).
- Direct production deployment by git push is disabled locally (the `azure` remote push URL is set to `DISABLED_DIRECT_PROD_PUSH_USE_CI_ONLY`); a hardened OIDC least-privilege CI workflow is prepared but not yet adopted on `main` (`ops/azure/production/production-deploy-hardening.md`).
- Encryption at rest for provider tokens, a safe legacy encryption command, and removal of tenant-submitted provider secrets/assets.
- OAuth state validation, provider-host restrictions, stricter webhook fail-closed behaviour and endpoint throttling.
- SSRF-resistant remote attachment ingestion and strict size/host/DNS/redirect controls.
- Stored-XSS and unsafe URL sink corrections.
- AI policy failures/unknown decisions route to human handoff rather than autonomous execution.
- Dependency upgrades, zero-known-vulnerability lockfile audits, secret scanning and SBOM generation in CI.

## Residual architecture facts

- Azure platform encryption at rest and TLS in transit are relied upon; application-level encryption is applied to high-risk tokens and selected quarantined content. The product must not be described as end-to-end encrypted.
- Staging MySQL has seven-day backups but no geo-redundant backup or HA. The production design needs an approved RPO/RTO and independently witnessed restore evidence. An isolated restore target exists and a restore has been executed; however, **decryption of application-encrypted fields after restore** (correct `APP_KEY` and Key Vault availability against restored data) has not yet been separately evidenced and must not be assumed working.
- A single personal account currently holds Azure subscription **Owner** across both production and staging, with no PIM/just-in-time elevation. This is a segregation-of-duties concentration requiring management action (see risk register R-016).
- Key Vault secrets have no expiry set and no documented rotation/lifecycle governance (R-019).
- Deployment source drift was observed during the audit: the local working checkout was 62 commits behind the staging branch. Direct production git push has since been disabled locally (CI-only), but the drift underscores the need for a single authoritative deployment source (R-020).
- Key Vault, storage and telemetry public network endpoints remain enabled in staging. Access is credential/RBAC controlled, but private endpoints and network segmentation are a recommended production treatment.
- App Service client affinity is enabled in the current staging resource. Database sessions make it unnecessary and it should be disabled in an approved infrastructure change.
- Supplier legal terms, UAE privacy assessment, data-processing agreements and retention schedules require counsel/owner approval.
- No independent penetration test or ISO certification audit has been performed.
