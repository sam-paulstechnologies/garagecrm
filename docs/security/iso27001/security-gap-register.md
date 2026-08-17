# Security gap register

| ID | Priority | Gap | Evidence / impact | Treatment | Owner | Target |
|---|---|---|---|---|---|---|
| GAP-001 | Critical | Tenant uploads were written to a public disk | Customer documents could be URL-addressable | All live uploads use `PrivateUploadStorage` (private disk, magic-MIME validation, authorised streamed download routes); dead public-disk `JobCardController` removed; legacy blob migration pending | Engineering | Live paths remediated staging / legacy migration pending |
| GAP-002 | Critical | Legacy provider values could be plaintext and editable by tenant admins | Credential disclosure/asset spoofing | Encrypted casts, controlled encrypt command, server-managed UI/request rules | Engineering | Implemented staging / data command pending |
| GAP-003 | High | Known stored-XSS/unsafe URL sinks | Session/action compromise | Context escaping and relative/HTTPS URL allow rules | Engineering | Implemented staging |
| GAP-004 | High | Attachment URL ingestion allowed overly broad remote fetch | SSRF and resource exhaustion | Explicit allowlist, HTTPS, public DNS, no redirects, cap/stream | Engineering | Implemented staging |
| GAP-005 | High | AI policy evaluation failed open | Unreviewed automated actions | Fail closed to human handoff | AI owner | Implemented staging |
| GAP-006 | High | Sensitive exception/payload logging | PII/token leakage | Global redaction and targeted log minimisation | Engineering | Implemented staging; recurring review |
| GAP-007 | High | Public Azure data-plane endpoints | Larger network attack surface | Private endpoints/VNet integration design and approved rollout | Operations | Before production security release |
| GAP-008 | High | No independent VAPT | Unknown exploitable defects | External authenticated multi-tenant/API/cloud VAPT | Security owner | Before launch approval |
| GAP-009 | High | Restore evidence not yet independently witnessed | Unproven recovery objective | Staging restore drill, hash/integrity evidence and sign-off | Operations | Before launch approval |
| GAP-010 | Medium | Retention/deletion schedule incomplete outside Quick Scan/history | Excess retention and DSAR difficulty | Legal-approved schedule and tenant data export/delete workflow | Privacy owner | 30 days |
| GAP-011 | Medium | Client affinity enabled | Unnecessary platform state | Disable in staging IaC after regression test | Operations | Next infra change |
| GAP-012 | Medium | Storage shared-key access enabled | Alternative credential path | Move to identity-only and disable shared key after compatibility test | Operations | 30 days |
| GAP-013 | Medium | Telemetry workspaces allow public ingestion/query endpoints | Monitoring surface exposure | Private link or restricted network plus RBAC review | Operations | 60 days |
| GAP-014 | Medium | Physical/personnel controls lack evidence | Incomplete ISMS scope | HR/office control inventory and owner evidence | Executive/HR | Before certification audit |
| GAP-015 | Low | Legacy de-registered job-card controller remains in source | Confusion/dead code; wrote to public disk | Removed (`Tenant\JobCardController` deleted; used `Storage::disk('public')`) | Engineering | Closed — removed staging |
| GAP-016 | High | Enforced CSP had no `script-src`/`default-src`; strong CSP ships Report-Only | Limited XSS mitigation until enforced | Strong policy staged Report-Only (`ContentSecurityPolicy`); validate Meta/Stripe UAT then set `SECURITY_CSP_ENFORCE=true` | Engineering | Report-Only staged / enforce pending |
| GAP-017 | High | Single personal account holds Azure subscription Owner over prod+staging, no PIM | Duty concentration; takeover = full estate | **MANAGEMENT ACTION REQUIRED:** split ownership, least-privilege RBAC, PIM/JIT elevation, break-glass account | Executive/Operations | Management decision required |
| GAP-018 | Medium | Key Vault secrets have no expiry set / no lifecycle governance | Stale/never-rotated secrets | Set expiries, document rotation cadence and ownership, alert on near-expiry | Operations | 60 days |
| GAP-019 | Medium | Deployment source drift; local checkout was 62 commits behind staging; direct prod git push possible | Wrong/unreviewed code shipped | Direct `azure` remote push disabled locally (CI-only); adopt single authoritative CI source and hardened OIDC workflow | Operations/Engineering | Local push disabled / CI adoption pending |
| GAP-020 | High | No signed OpenAI data-processing agreement | Third-party processing of prompt content | **LEGAL/MANAGEMENT ACTION REQUIRED:** DPA/contract review; prompt PII minimization (`PromptPiiMinimizer`) is a compensating control, not a substitute | Legal/Privacy owner | Legal decision required |
| GAP-021 | High | Application-encrypted-field decryption after restore not evidenced | Restore may not yield usable data | Restore drill that decrypts encrypted fields with restored `APP_KEY`/KV and records integrity evidence | Operations | Before launch approval |
| GAP-022 | Medium | Quick Scan purge reconciliation not independently verified | Retained PII beyond purpose | Evidence that `PurgeQuickScan` job reconciles all Quick Scan stores against retention policy | Privacy owner | 30 days |
| GAP-023 | Medium | Tenant-isolation relies on manual scope checks | Missed scope = cross-tenant exposure | Automated backstop (global scope/guard) being added in addition to adversarial tests | Engineering | In progress |
| GAP-024 | Low | Recovery codes use legacy storage format | Weaker at-rest handling | Review and align recovery-code storage with current crypto standard | Engineering | Backlog |

No critical gap may be accepted solely by engineering. Any exception requires accountable-owner approval, expiry date and compensating controls.
