# Asset register

Classification labels are defined in the information-classification register.

| Asset | Owner | Classification | Location / processor | Criticality | Primary controls |
|---|---|---|---|---|---|
| Laravel source and migrations | Engineering | Internal | Git repository | Critical | access control, review, scans, lockfiles |
| Deployment workflow/IaC | Operations | Restricted | repository/Azure | Critical | branch/release review, target guards |
| Tenant/company records | Product/Privacy | Confidential | staging/production MySQL | Critical | tenant scope, RBAC, TLS, backup |
| Users/auth/2FA/recovery | Security | Restricted | MySQL/session store | Critical | Fortify, encryption, hashing, step-up |
| CRM clients/leads/vehicles/jobs/invoices | Tenant/data owner | Confidential | MySQL/private storage | Critical | tenant scope, private downloads, audit |
| WhatsApp messages/history/Quick Scan | Tenant/Privacy | Confidential/Restricted | MySQL | Critical | encrypted fields, consent, quotas, purge |
| Provider tokens/webhook secrets/API keys | Security/Integration | Restricted | Key Vault/encrypted DB fields | Critical | managed identity, KV RBAC, rotation, redaction |
| Stripe customers/subscriptions/invoices/events | Commercial | Confidential | Stripe Sandbox + MySQL | High | signature, idempotency, test guard, scoped metadata |
| AI prompts/inputs/results/telemetry | AI/Privacy | Confidential | app/OpenAI where enabled | High | minimisation, quota, no hidden reasoning, human handoff |
| Uploaded documents/photos | Tenant/data owner | Confidential | private staging storage | High | magic MIME, private disk, authenticated stream |
| Application/security/audit logs | Security/Operations | Confidential | App Insights/Log Analytics/MySQL | High | redaction, RBAC, retention, correlation ID |
| Backups | Operations | Restricted | Azure MySQL backup | Critical | provider encryption, access control, restore tests |
| DNS/certificates/domains | Operations | Internal/Critical config | DNS/Azure | High | scoped changes, managed TLS |
| Developer/operator endpoints | Security | Internal | managed workstations | High | acceptable use, patching, MFA (evidence external) |

Owners review accuracy quarterly and on architecture change. Unregistered assets cannot process production data without risk/classification review.
