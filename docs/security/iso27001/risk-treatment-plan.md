# Risk treatment plan

| Treatment | Risks | Action / measurable exit | Evidence | Status |
|---|---|---|---|---|
| RTP-01 Tenant boundary | R-001, R-012 | Every tenant-owned route and background job has company scope; adversarial suite passes | route/policy tests, `SecurityHardeningTest`, tenant suite | Ongoing |
| RTP-02 Confidential files | R-002 | New uploads use private disk; legacy inventory migrated; public URLs return no customer content | migration, storage tests, staging UAT | Code complete; data migration pending |
| RTP-03 Secrets | R-003, R-011 | No committed secret; KV refs resolved; legacy plaintext count zero; logs redact fixtures | scan report, command evidence, KV diagnostic | Code complete; staging run pending |
| RTP-04 Identity | R-004, R-012 | Admin/super-admin 2FA and step-up tests pass; quarterly access review | auth suite, access-review record | Implemented / recurring |
| RTP-05 Provider integrity | R-005, R-006, R-008 | Signatures/replay/asset guards and throttles pass; production IDs denied | Stripe/Meta test suites | Implemented |
| RTP-06 AI safety | R-007 | Unknown/error decision never acts; outbound remains independently guarded | AI policy test, staging settings | Implemented |
| RTP-07 Recovery | R-009, R-014 | Restore staging backup to isolated target; verify schema/app integrity; record observed RPO/RTO | restore evidence | Required before launch |
| RTP-08 Supply chain | R-010 | High/critical dependency audit count zero at release; SBOM retained | CI artifacts | Implemented |
| RTP-09 Network hardening | R-011, R-015 | Private endpoints/VNet and identity-only storage evaluated, deployed and regression-tested | Azure change record | Planned |
| RTP-10 Privacy/legal | R-013 | Counsel approves purposes, notices, retention, DSAR, cross-border/supplier terms | legal register/sign-off | External dependency |

Treatments must not be closed without reproducible evidence. Overdue high risks are escalated to the accountable executive.
