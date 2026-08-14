# Information-security risk register

Scoring: likelihood (L) and impact (I), 1–5; inherent/residual score = L × I. 15–25 critical/high, 8–14 medium, 1–7 low. Acceptance authority: executive for score ≥15, security owner for 8–14, system owner for ≤7.

| Risk | Asset/process | Threat / vulnerability | Inherent | Existing/treatment controls | Residual | Decision / owner |
|---|---|---|---:|---|---:|---|
| R-001 | Tenant CRM data | Cross-tenant IDOR/query omission | 5×5=25 | scoped binding, policies, company filters, adversarial tests | 2×5=10 | Treat; Engineering/Security |
| R-002 | Documents/photos | Public or malicious uploads | 4×5=20 | private disk, magic MIME, hash paths, authorised streaming, limits | 2×4=8 | Treat; Engineering |
| R-003 | Provider/payment secrets | plaintext/log/repository exposure | 4×5=20 | Key Vault refs, encrypted casts, log redaction, secret scan, hidden fields | 2×5=10 | Treat; Security/Operations |
| R-004 | Authentication | account takeover/admin bypass | 4×5=20 | Fortify, mandatory admin TOTP, step-up, throttles, audit, recovery controls | 2×5=10 | Treat; Security |
| R-005 | Stripe billing | forged/replayed event or browser activation | 4×5=20 | signed webhook, idempotency, test/live guard, provider reconciliation | 1×5=5 | Monitor; Commercial owner |
| R-006 | Meta/WhatsApp | unknown/production asset accepted in staging | 4×5=20 | signature, asset allow/deny lists, tenant number match, fail closed | 2×5=10 | Treat; Integration owner |
| R-007 | AI automation | hallucinated/unsafe autonomous action | 4×4=16 | observational separation, entitlements, quota, outbound guard, human handoff | 2×4=8 | Treat; AI owner |
| R-008 | Webhooks/uploads | SSRF, injection, resource exhaustion | 4×4=16 | allowlists, public-DNS validation, limits, signatures, throttles | 2×4=8 | Treat; Engineering |
| R-009 | Azure availability | regional/database failure | 3×5=15 | backups, health, isolated resources; no staging HA/geo backup | 3×4=12 | Treat; Operations |
| R-010 | Supply chain | vulnerable/compromised dependency | 4×4=16 | lockfiles, audits, SBOM, CI gate, reviewed upgrades | 2×4=8 | Treat; Engineering |
| R-011 | Logging/telemetry | PII/secrets retained or overexposed | 4×4=16 | minimisation, redactor, RBAC, retention; public telemetry endpoints | 2×4=8 | Treat; Security/Operations |
| R-012 | Insider/admin | excessive privilege/impersonation misuse | 3×5=15 | role separation, 2FA/step-up, audit, scoped impersonation | 2×5=10 | Treat; Security |
| R-013 | Privacy | unlawful/excessive processing or retention | 3×5=15 | consent records, Quick Scan purge, data minimisation; legal review open | 3×4=12 | Treat; Privacy owner |
| R-014 | Recovery | backup unusable when needed | 3×5=15 | automated backup; restore drill not yet witnessed | 3×5=15 | Immediate treatment; Operations |
| R-015 | Network | public data-plane endpoint exploitation | 3×5=15 | TLS/RBAC/credentials; private endpoint gap | 2×5=10 | Treat; Operations |

Risk owners must record treatment evidence, review date and acceptance decision. Residual ratings are engineering assessments, not independent assurance.
