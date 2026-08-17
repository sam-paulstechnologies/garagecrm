# Security controls matrix

| Domain | Preventive | Detective | Corrective / recovery | Evidence |
|---|---|---|---|---|
| Tenant isolation | company scopes, route binding, policies | cross-tenant tests/audit logs | access revocation/incident runbook | tenant tests |
| Identity | password rules, TOTP, step-up (fail-closed in protected env — `SecurityConfigurationValidator`), throttles, session+token invalidation on credential change | failed challenge/security audit | recovery codes/admin reset | auth/security suites |
| Secrets | Key Vault, managed identity, encrypted casts | secret scan, KV ref status | rotation and legacy encrypt command | CI/security command. **Gap:** KV secrets have no expiry/lifecycle governance set (R-019); recovery codes use legacy storage pending review |
| Files | private disk (`PrivateUploadStorage`), content/magic-MIME validation, limits; dead public-disk `JobCardController` removed | rejected-upload logs/tests | delete/migrate legacy blob | upload tests/runbook |
| Web/API | CSRF, signatures, validation, rate limits; **strong CSP shipped Report-Only (staged, `SECURITY_CSP_ENFORCE`) — not yet enforcing, partial XSS mitigation** | correlation IDs, app telemetry, CSP violation reports | revoke/disable endpoint; flip CSP to enforce | route/webhook tests, `ContentSecurityPolicyTest` |
| Billing | sandbox/live guard, server price mapping | provider event/idempotency ledger | reconciliation/rollback | billing tests |
| Messaging | asset allow/deny, signatures, outbound gate | event/message audit | local disconnect/quarantine | Meta/WhatsApp tests |
| AI | entitlement/quota, observational/action separation, fail closed, prompt PII minimization (`PromptPiiMinimizer` strips name/phone/email) | privacy-minimised run telemetry | human handoff/disable provider | AI tests/config. **Residual:** no OpenAI DPA signed (LEGAL/MANAGEMENT ACTION) |
| Cloud | isolated RG/plan/DB/KV/storage, TLS/RBAC; **single subscription Owner (personal account, no PIM) — duty concentration** | Azure/AI/LA logs and health | backup/restore/DR runbooks; restore executed, decryption-after-restore evidence pending | Azure inventory. Prod deploy is CI-only — direct `azure` git push disabled locally |
| Supply chain | lockfiles/reviewed upgrades | Composer/npm audits, secret scan, SBOM | patch/rebuild/redeploy | CI artifacts |

Every control requires a named owner, test/evidence cadence and exception path. Controls described here do not replace independent effectiveness testing.
