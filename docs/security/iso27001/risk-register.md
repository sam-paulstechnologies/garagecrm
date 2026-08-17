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
| R-014 | Recovery | backup unusable when needed; encrypted fields undecryptable after restore | 3×5=15 | automated backup; isolated restore executed, but application-field decryption after restore not yet evidenced | 3×5=15 | Immediate treatment; Operations |
| R-015 | Network | public data-plane endpoint exploitation | 3×5=15 | TLS/RBAC/credentials; private endpoint gap | 2×5=10 | Treat; Operations |
| R-016 | Cloud tenancy/governance | single personal account holds Azure subscription Owner over prod+staging, no PIM | 4×5=20 | account credential holds MFA; no duty separation, no JIT elevation, no break-glass | 4×5=20 | **MANAGEMENT ACCEPTANCE REQUIRED — [accountable executive: TBD]**; do not accept without treatment plan |
| R-017 | Web/XSS | weak/partial CSP — enforced policy lacked `script-src`/`default-src`; strong policy Report-Only | 4×4=16 | output escaping, framework defaults; strong CSP staged Report-Only (`SECURITY_CSP_ENFORCE`) | 3×4=12 | Treat; Engineering — enforce after Meta/Stripe UAT |
| R-018 | AI supplier/privacy | prompt content processed by OpenAI without signed DPA | 4×4=16 | prompt PII minimization strips name/phone/email (`PromptPiiMinimizer`); observational/fail-closed AI | 3×4=12 | **LEGAL/MANAGEMENT ACCEPTANCE REQUIRED — [privacy owner / counsel: TBD]** |
| R-019 | Secrets lifecycle | Key Vault secrets have no expiry / no rotation governance | 3×4=12 | KV RBAC, managed identity, secret scan | 3×4=12 | Treat; Operations — set expiries and rotation cadence |
| R-020 | Deployment integrity | source drift (local checkout 62 commits behind staging); direct prod git push | 4×4=16 | direct `azure` remote push disabled locally (CI-only); hardened OIDC workflow prepared | 2×4=8 | Treat; Operations/Engineering |
| R-021 | Privacy/deletion | Quick Scan purge not independently reconciled | 3×4=12 | `PurgeQuickScan` job, retention intent | 3×4=12 | Treat; Privacy owner — evidence reconciliation |
| R-022 | Tenant isolation | manual scope checks fragile; a missed scope leaks cross-tenant data | 4×5=20 | company scopes, policies, adversarial tests; automated backstop being added | 3×5=15 | Treat; Engineering — land global scope/guard backstop |
| R-023 | Identity | recovery codes stored in legacy format | 2×4=8 | codes are one-time, hashed per legacy scheme | 2×4=8 | Treat; Engineering |
| R-024 | Session hygiene | stale session/token after credential change | 3×4=12 | **Remediated:** sessions + tokens invalidated on credential/2FA change (`SecuritySessionInvalidator`) | 1×4=4 | Monitor; Engineering |

Risk owners must record treatment evidence, review date and acceptance decision. Residual ratings are engineering assessments, not independent assurance. Risks marked **MANAGEMENT ACCEPTANCE REQUIRED** are unresolved and must not be treated as accepted until a named accountable authority signs off with a treatment plan and expiry.
