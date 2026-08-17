# Risk treatment plan

| Treatment | Risks | Action / measurable exit | Evidence | Status |
|---|---|---|---|---|
| RTP-01 Tenant boundary | R-001, R-012 | Every tenant-owned route and background job has company scope; adversarial suite passes | route/policy tests, `SecurityHardeningTest`, tenant suite | Ongoing |
| RTP-02 Confidential files | R-002 | New uploads use private disk; legacy inventory migrated; public URLs return no customer content | migration, storage tests, staging UAT | Code complete; data migration pending |
| RTP-03 Secrets | R-003, R-011 | No committed secret; KV refs resolved; legacy plaintext count zero; logs redact fixtures | scan report, command evidence, KV diagnostic | Code complete; staging run pending |
| RTP-04 Identity | R-004, R-012 | Admin/super-admin 2FA and step-up tests pass; quarterly access review | auth suite, access-review record | Implemented / recurring |
| RTP-05 Provider integrity | R-005, R-006, R-008 | Signatures/replay/asset guards and throttles pass; production IDs denied | Stripe/Meta test suites | Implemented |
| RTP-06 AI safety | R-007 | Unknown/error decision never acts; outbound remains independently guarded | AI policy test, staging settings | Implemented |
| RTP-07 Recovery | R-009, R-014 | Restore backup to isolated target (done); additionally decrypt application-encrypted fields with restored `APP_KEY`/KV and verify schema/app integrity; record observed RPO/RTO | restore evidence; decryption-after-restore proof | Restore executed; decryption-after-restore evidence pending |
| RTP-08 Supply chain | R-010 | High/critical dependency audit count zero at release; SBOM retained | CI artifacts | Implemented |
| RTP-09 Network hardening | R-011, R-015 | Private endpoints/VNet and identity-only storage evaluated, deployed and regression-tested | Azure change record | Planned |
| RTP-10 Privacy/legal | R-013, R-018, R-021 | Counsel approves purposes, notices, retention, DSAR, cross-border/supplier terms; sign OpenAI DPA (prompt PII minimization is compensating only); evidence Quick Scan purge reconciliation | legal register/sign-off; DPA; purge reconciliation record | External dependency; **LEGAL/MANAGEMENT ACCEPTANCE REQUIRED [privacy owner/counsel: TBD]** |
| RTP-11 Cloud governance | R-016 | Separate prod/staging subscription ownership, least-privilege RBAC, enable PIM/JIT, define break-glass; remove standing personal Owner | Azure RBAC/PIM change record | **MANAGEMENT ACCEPTANCE REQUIRED [accountable executive: TBD]** — not started |
| RTP-12 Browser XSS hardening | R-017 | Validate strong CSP under Report-Only against Meta/Stripe UAT, add nonces/hashes to inline scripts, then set `SECURITY_CSP_ENFORCE=true` | CSP report review, `ContentSecurityPolicyTest`, enforce diff | Report-Only staged; enforce pending |
| RTP-13 Secret lifecycle | R-019 | Set Key Vault secret expiries, document rotation cadence/ownership, alert near expiry | KV configuration evidence | Planned |
| RTP-14 Deployment integrity | R-020 | Single authoritative CI deployment source; direct prod git push disabled locally (done); adopt hardened OIDC least-privilege workflow on `main` after review | remote config, `ops/azure/production/production-deploy-hardening.md`, workflow | Local push disabled; CI adoption pending |
| RTP-15 Tenant isolation backstop | R-022 | Add automated global scope/guard backstop in addition to adversarial tests so a missed manual scope cannot leak cross-tenant data | backstop code + tests | In progress |
| RTP-16 Session hygiene | R-024 | Invalidate sessions and tokens on credential/2FA change | `SecuritySessionInvalidator`, `SecuritySessionInvalidatorTest` | Implemented |

Treatments must not be closed without reproducible evidence. Overdue high risks are escalated to the accountable executive. Treatments marked MANAGEMENT/LEGAL ACCEPTANCE REQUIRED remain open until a named authority signs off.
