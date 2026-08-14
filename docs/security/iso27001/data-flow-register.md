# Data-flow register

| Flow | Source → destination | Data | Purpose | Controls | Retention/deletion note |
|---|---|---|---|---|---|
| Registration/login | browser → App Service → MySQL/session | identity, company, auth state | account/tenant service | TLS, CSRF, validation, password hashing, TOTP, DB session | account/legal schedule pending |
| CRM operations | authenticated browser/API → Laravel → MySQL | clients, vehicles, leads, bookings, jobs, invoices | garage management | tenant scope, roles, step-up on sensitive changes, audit | legal schedule pending |
| File upload/download | browser → Laravel → private storage | documents/photos | operational evidence | MIME/content validation, size limit, tenant hash path, authorised stream | tenant deletion/migration runbook |
| Stripe checkout | Laravel ↔ Stripe Sandbox; webhook → Laravel | tenant reference, plan/price mapping, provider billing metadata | subscriptions/payments | test guard, Key Vault, TLS, signatures, idempotency | billing/legal retention |
| Meta onboarding | admin browser/Laravel ↔ Meta | approved business/phone assets, token | connect provider | 2FA/step-up, OAuth state, server IDs, allow/deny list, encrypted token | disconnect/rotation policy |
| WhatsApp inbound | Meta/Twilio → signed webhook → event/message/CRM | phone/message/provider refs | enquiry capture | signature, replay, tenant route, encrypted raw event, quota | messaging retention/legal approval |
| WhatsApp outbound | reviewed app job → provider → allowlisted recipient | template/message | operational communication | entitlement, consent/policy, staging global block, recipient allowlist | message/audit schedule |
| Quick Scan | temporary provider session → quarantine → aggregate report | bounded message history/contact evidence | consented diagnostic demo | consent, HMAC/masking, encrypted fields, no CRM creation, purge | 24h decline purge/default expiry |
| AI analysis | Laravel → configured AI provider → Laravel | bounded conversation text/context | classification/recommendation | quota, minimisation, TLS, fail closed/human handoff, no autonomous unknown action | privacy terms/provider retention review open |
| Logging | app/Azure resources → AI/LA/MySQL audit | metadata, masked refs, failures | detection/audit | redaction, request ID, RBAC, retention | 30/90-day staging configuration |
| Backup/restore | MySQL → Azure backup → isolated restore | encrypted database image | continuity | Azure access/RBAC, target guard, evidence | 7-day staging; production policy pending |

No production data flows to staging. Cross-border locations and supplier subprocessors require legal confirmation before production approval.
