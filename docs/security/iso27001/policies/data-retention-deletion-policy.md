# Data retention and deletion policy (draft)

Legal/privacy approval is required before production adoption. Minimise collection and retain only for documented service, legal, security and contractual purposes.

| Category | Staging baseline | Production decision required |
|---|---|---|
| Synthetic tenant/customer data | reset after UAT/release evidence | never move to production |
| Quick Scan customer-level quarantine | purge after decline delay (24h staging) or expiry; retain aggregates/consent evidence | approve notice and duration |
| WhatsApp history review quarantine | 30 days configured | approve purpose/duration/objection |
| Application/telemetry logs | 30–90 days | approve security/legal duration |
| Security audit | retain sufficient for investigations | approve duration and immutability |
| CRM/messages/files/invoices | no complete automated schedule yet | define per contract/legal/customer-right category |
| Backups | 7 days staging | approve RPO/legal deletion propagation |

Deletion requests require identity/authority verification, tenant scope, legal-hold check, dependency map, approved purge/anonymisation, backup-expiry treatment and evidence without retaining deleted content. Provider-side deletion is separate and explicit. Normal local disconnect must not delete external Meta business assets.
