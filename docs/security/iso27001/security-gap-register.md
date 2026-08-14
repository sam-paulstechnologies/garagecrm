# Security gap register

| ID | Priority | Gap | Evidence / impact | Treatment | Owner | Target |
|---|---|---|---|---|---|---|
| GAP-001 | Critical | Tenant uploads were written to a public disk | Customer documents could be URL-addressable | Private storage, magic-MIME validation, authorised download routes; migrate legacy blobs | Engineering | Implemented staging / migration pending |
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
| GAP-015 | Low | Legacy de-registered job-card controller remains in source | Confusion/dead code | Remove after feature-owner confirmation | Engineering | Backlog |

No critical gap may be accepted solely by engineering. Any exception requires accountable-owner approval, expiry date and compensating controls.
