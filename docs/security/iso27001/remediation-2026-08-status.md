# Fable remediation status — August 2026

**This is an internal self-assessment. No external VAPT, penetration test or ISO
certification audit has been performed.** The items below record what the August 2026
remediation sprint changed following the Fable audit handoff
(`assurance/FABLE_SECURITY_AUDIT_HANDOFF.md`), with the code that evidences each
change. Statuses are engineering assessments, not independent assurance.

## What changed, linked to code

| Area | Change | Evidence (code) | Status |
|---|---|---|---|
| Content-Security-Policy | Strong policy with `default-src`/`script-src` added, shipped **Report-Only** (staged rollout via `SECURITY_CSP_ENFORCE`). Previously-enforced header had no `script-src`/`default-src`. `X-Powered-By` dropped. | `app/Security/ContentSecurityPolicy.php`, `config/security.php` (`csp.*`), `app/Http/Middleware/ApplySecurityHeaders.php`, `tests/Feature/Security/ContentSecurityPolicyTest.php` | Report-Only; enforce pending Meta/Stripe UAT |
| Uploads | All live uploads use a private disk with magic-MIME validation and authorized streamed downloads; dead public-disk `JobCardController` (used `Storage::disk('public')`) removed. | `app/Security/Uploads/PrivateUploadStorage.php`; removed `app/Http/Controllers/Tenant/JobCardController.php` | Live paths remediated; legacy blob migration pending |
| MFA fail-closed | Mandatory privileged MFA/step-up fails **closed** in protected environments — unset/invalid `TWO_FACTOR_ENFORCEMENT` no longer silently disables MFA. | `app/Security/SecurityConfigurationValidator.php`, `app/Security/TwoFactorPolicy.php`, `tests/Feature/Security/SecurityConfigurationValidatorTest.php` | Implemented |
| Session invalidation | Sessions and API tokens invalidated on password/2FA/credential change. | `app/Security/SecuritySessionInvalidator.php`, `tests/Feature/Security/SecuritySessionInvalidatorTest.php` | Implemented |
| AI PII minimization | Name/phone/email stripped from prompts before send to OpenAI; neutral lead descriptor only. Compensating control — **no OpenAI DPA signed**. | `app/Services/Ai/PromptPiiMinimizer.php`, `app/Services/Ai/NlpService.php`, `tests/Unit/PromptPiiMinimizerTest.php` | Technical control implemented; DPA legal action open |
| Log redaction | Broadened redaction of sensitive values in logs. | `app/Logging/RedactSensitiveLogs.php`, `tests/Unit/RedactSensitiveLogsTest.php` | Implemented |
| Deployment integrity | Direct production git push disabled locally (`azure` remote push URL = `DISABLED_DIRECT_PROD_PUSH_USE_CI_ONLY`); hardened OIDC least-privilege CI workflow prepared (not yet on `main`). | `.github/workflows/main_app-sayaraforce.yml`, `ops/azure/production/production-deploy-hardening.md`, `ops/security/scan-secrets.php` | Local push disabled; CI adoption pending review |
| Dead-code removal | Removed several de-registered controllers reducing attack/confusion surface. | commit `78613a6e` | Implemented |

## Explicitly NOT resolved (management/legal or evidence pending)

- **Azure subscription Owner concentration** — one personal account owns prod+staging,
  no PIM. MANAGEMENT ACCEPTANCE REQUIRED (R-016 / GAP-017).
- **OpenAI DPA** — unsigned; PII minimization is compensating only. LEGAL/MANAGEMENT
  ACCEPTANCE REQUIRED (R-018 / GAP-020).
- **CSP enforcement** — still Report-Only; XSS mitigation partial until enforced
  (R-017 / GAP-016).
- **Decryption-after-restore** — restore executed, but decrypting application-encrypted
  fields with the restored `APP_KEY`/Key Vault is not yet evidenced (R-014 / GAP-021).
- **Key Vault secret expiry/lifecycle** — no expiries set (R-019 / GAP-018).
- **Deployment source drift** — local checkout was 62 commits behind staging; single
  authoritative CI source pending (R-020 / GAP-019).
- **Quick Scan purge reconciliation** — not independently evidenced (R-021 / GAP-022).
- **Tenant-isolation backstop** — automated global scope/guard being added alongside
  manual checks and adversarial tests (R-022 / GAP-023).
- **Recovery-code legacy storage** — review pending (R-023 / GAP-024).

## Standing constraints on claims

Do not describe the product as ISO certified, penetration tested, fully secure,
PDPL/DPDP compliant, or end-to-end encrypted. Application-level encryption protects
selected high-risk fields; platform TLS/at-rest encryption is relied upon. Independent
VAPT, legal/DPA decisions, named-role approvals and witnessed restore/decryption
evidence remain open (see `security-gap-register.md`, `risk-register.md`,
`assurance/external-vapt-handoff.md`).
