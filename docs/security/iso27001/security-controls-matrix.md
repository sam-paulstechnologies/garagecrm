# Security controls matrix

| Domain | Preventive | Detective | Corrective / recovery | Evidence |
|---|---|---|---|---|
| Tenant isolation | company scopes, route binding, policies | cross-tenant tests/audit logs | access revocation/incident runbook | tenant tests |
| Identity | password rules, TOTP, step-up, throttles | failed challenge/security audit | recovery codes/admin reset | auth/security suites |
| Secrets | Key Vault, managed identity, encrypted casts | secret scan, KV ref status | rotation and legacy encrypt command | CI/security command |
| Files | private disk, content validation, limits | rejected-upload logs/tests | delete/migrate legacy blob | upload tests/runbook |
| Web/API | CSRF, signatures, validation, rate limits, CSP | correlation IDs, app telemetry | revoke/disable endpoint | route/webhook tests |
| Billing | sandbox/live guard, server price mapping | provider event/idempotency ledger | reconciliation/rollback | billing tests |
| Messaging | asset allow/deny, signatures, outbound gate | event/message audit | local disconnect/quarantine | Meta/WhatsApp tests |
| AI | entitlement/quota, observational/action separation, fail closed | privacy-minimised run telemetry | human handoff/disable provider | AI tests/config |
| Cloud | isolated RG/plan/DB/KV/storage, TLS/RBAC | Azure/AI/LA logs and health | backup/restore/DR runbooks | Azure inventory |
| Supply chain | lockfiles/reviewed upgrades | Composer/npm audits, secret scan, SBOM | patch/rebuild/redeploy | CI artifacts |

Every control requires a named owner, test/evidence cadence and exception path. Controls described here do not replace independent effectiveness testing.
