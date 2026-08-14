# Supplier security register

| Supplier/service | Data/role | Technical controls observed | Due diligence / open action | Owner |
|---|---|---|---|---|
| Microsoft Azure | hosting, DB, storage, KV, telemetry, backup | RBAC, managed identity, TLS, encryption, logs | verify contract/DPA, region, shared-responsibility, private endpoints, assurance reports | Operations/Legal |
| Stripe | payment/subscription processor | hosted checkout, signed webhooks, idempotency, sandbox/live segregation | contract/DPA, retention, incident contacts, production key governance | Commercial/Legal |
| Meta/WhatsApp | message/business asset provider | signed webhooks, OAuth/provider IDs, asset guards | business terms, app review, data use/retention, production/staging callback effects | Integration/Legal |
| Twilio (legacy/optional) | messaging provider | signed webhook support, platform-managed credentials | decide retirement vs approved use; DPA/security review | Integration/Legal |
| OpenAI/configured AI supplier | AI analysis | TLS/API credential, bounded inputs, deterministic fallback | approve DPA/data-use/retention/residency/model terms before production customer text | AI/Privacy |
| Git hosting / CI runner | source/build artifacts | repository auth, workflow gates, artifacts | verify MFA, branch protection, runner trust, admin/access inventory | Engineering/Security |
| DNS/registrar | domain routing | managed TLS binding | identify owner, MFA, lock/recovery process | Operations |
| Mail provider (future) | transactional email | staging log-only | DPA, sender domain, allowlist and production approval | Operations/Legal |

Before onboarding a new processor: document purpose/data/classification/location/subprocessors, review security/privacy terms, approve least-privilege access, define incident notification and exit/deletion, and record reassessment cadence. No supplier is deemed certified solely from marketing claims.
