# SayaraForce commercial launch master execution

## Safety boundary and authoritative baseline

All implementation follows build, local test, staging deployment, staging verification, human approval, then production. This execution stops before production: no production deployment, migration, restart, configuration, DNS, Meta asset, customer data, payment credential, or customer message is authorized.

The clean release worktree started from staging commit `e8f2aecbf9571c7b43d577702c7d4ddd24f346fd`. The original workspace's 74 unrelated dirty/untracked entries remain outside this worktree and outside every commercial commit.

## Phase status

| Phase | Status | Evidence / dependency |
|---|---|---|
| 0 — staging foundation | Complete | Isolated Azure staging, TLS, queue, canonical schema, synthetic data, outbound disabled |
| 1 — security and entitlement kernel | Complete | Commits `c5ac6624`, `910abb68`, `e8f2aecb`; 204 tests, 1,260 assertions; deployed staging; P0 role escalation closed |
| 2 — WhatsApp lifecycle and AI metering | Complete | Commits `95db5d3e`, `ca45683d`; CI/deploy passed; live verifier passed; queue running; fingerprint `163f55f...259fb` |
| 3 — billing engine and payment gateway | Local gate passed; staging deployment pending | Provider-neutral lifecycle, signed fake/Stripe test adapters, webhook idempotency, grace policy, billing UI, and introductory-price transition implemented |
| 4 — Free and Service product | Pending | Depends on Phase 2/3 services, not live Meta |
| 5 — Growth, Performance, AI Pro | Pending | AI Pro 10,000 allowance remains provisional/configurable, not a promise |
| 6 — mobile/service-manager experience | Pending | External push credentials may remain unresolved behind a fake provider |
| 7 — Meta/WhatsApp UAT readiness | Pending | Synthetic signed fixtures only; live Meta remains parked |
| 8 — full staging launch rehearsal | Pending | No real cards, customer messages, or production assets |
| 9 — production migration preparation | Pending | Tooling/runbooks only; no production execution |
| 10 — commercial launch experience | Pending | Catalogue-driven pricing and real-metric upsells |
| 11 — measurement foundation | Pending | Privacy-minimized product/commercial events |

## Phase 1 completion record

Phase 1 introduced stable plan codes (`free`, `service`, `growth`, `performance`, `ai_pro`), immutable plan versions and explicit AED price records, subscription lifecycle foundations, canonical fail-closed capabilities, tenant overrides, usage/audit records, centralized company status enforcement, explicit Free registration assignment, and server-side role/entitlement boundaries. Database IDs have no commercial meaning. Launch and standard prices are explicit records; paid promotional pricing lasts a configurable 12 billing cycles. Marketing and autonomous WhatsApp remain disabled.

## Phase 2 decisions and validation

- Signed Meta/Twilio ingress persists a raw `message_logs` row before queue dispatch. Provider message identifiers make ingress idempotent.
- A WhatsApp greeting creates/reuses a Client and a `new` Lead. It does not create an Opportunity. Existing qualified conversation flows remain responsible for Opportunity creation; Booking remains a confirmed date/time outcome.
- AI observation is claimed only after raw persistence and CRM resolution. Quota, NLP, lead, conversation, or automation failure cannot delete the inbound record.
- One billable usage unit is one unique external customer, scoped by tenant plus messaging connection, during the subscription period. The ledger stores only HMAC-SHA256 identities, never a raw phone number for metering.
- `entitlement_usages` is the aggregate allowance counter. `ai_customer_usages` is the unique customer-period ledger. `ai_analysis_runs` records provider/model, run status, token fields, duration, estimated cost, and skip/error reason without prompts or credentials.
- A configurable per-customer analysis-run guard protects against unlimited spend after the unique-customer claim.
- Free has no automatic transactional WhatsApp reply. Service and above require the `whatsapp_transactional` entitlement, while the staging environment-wide outbound switch remains disabled.
- `2026_08_10_000002_create_ai_monitoring_metering` is additive and remains a post-baseline migration. The data-free canonical SQL file and its SHA-256 are unchanged.

Validation evidence:

- Focused Phase 2 + webhook reliability: 15 tests, 63 assertions, no failures.
- Full suite: 211 tests executed, 1,290 assertions, no failures. Two hundred are warning-classified solely because the clean release worktree has no prebuilt Vite manifest; 11 are ordinary passes. One unrelated PHPUnit doc-comment deprecation remains.
- Two disposable local MySQL cycles: 120 base tables, two views, 43 migration records, 147 foreign keys checked, 442 routes, two synthetic tenants, matching fingerprint `163f55f3252e9b6aa6b4426d1124622d34de71ae9f03636cd5fdb2b1742259fb`.
- First staging workflow run deployed the package and migration successfully and matched the exact fingerprint, then correctly stopped because `staging:verify-live` still expected the Phase 1 table count of 118. The verifier was corrected to require 120 tables and both Phase 2 metering tables; no rollback, reseed, or schema mutation was used to mask the regression.
- The corrected workflow run `31341365787` passed package deployment, no-op migration handling, exact fingerprint verification, deployment marker, staging-only restart, and health checks at commit `ca45683d577f0e110ef00b6b92527b6ad160b8b5`.
- The guarded live verifier passed on retry after one transient Azure-host cold-start timeout: staging HTTP returned 200, the queue WebJob was running, the scheduler remained disabled, and no secret was printed. Live Meta asset allowlists remain intentionally unconfigured, so real WhatsApp UAT stays parked.

## Phase 3 decisions and validation

- `BillingGateway` is provider-neutral: customer, checkout, subscription, plan change, cancellation, retrieval, portal, signature verification, and event normalization are isolated from `EntitlementService`.
- The Stripe adapter is test-mode only in this release. It refuses live mode or `sk_live_` credentials before any HTTP request. Missing KYC, account, bank, test keys, and webhook secret remain external dependencies rather than engineering blockers.
- Internal immutable `prices` remain commercial authority. `price_provider_mappings` holds launch/standard provider IDs as integration metadata; fake mappings are deterministic and refused in production.
- Checkout creates a tenant-scoped pending request. Browser success never changes entitlements. Only a signed, normalized, idempotently persisted provider event can activate or change the local subscription.
- Provider event IDs are unique per provider; payload hashes detect event-ID tampering; older state events are recorded and ignored. Persisted payloads are normalized commercial metadata, not raw card or provider request bodies.
- Payment failure starts a configurable grace period. A separate idempotent command suspends only after grace expiry. Cancellation requests leave access unchanged until a verified provider event confirms the lifecycle state.
- Introductory pricing is counted by verified paid invoice cycles. At the configured duration, an idempotent queued request transitions the provider to the standard mapping; local authority changes only on the follow-up verified webhook.
- The tenant-admin billing UI exposes current plan/state, versioned launch and standard prices, renewal/grace information, cancellation/portal actions, and verified invoice history. It never handles or stores card details.
- Local focused billing validation: 11 scenarios and 82 assertions passed, including signature spoofing, duplicate and tampered replay, out-of-order delivery, cross-tenant idempotency, redirect distrust, verified plan change without duplicate subscriptions, grace/suspension, cancellation, Stripe test adapter coverage, and deterministic introductory-price transition.
- Full suite after Phase 3: 222 tests executed, 1,372 assertions, no failures. The warning classification remains limited to the known absent prebuilt Vite manifest; frontend production build and compiled Blade views passed.
- Two guarded disposable MySQL cycles passed with 123 base tables, two views, 44 migration records, 153 foreign keys, 450 routes, and identical fingerprint `ffcb44d1847c7d9c75d710ad8a868b4ea55424a659ffbd62aef9597ecbab7697`.
- Staging workflow run `31343570973` passed every source, schema, PHP, test, frontend, production-dependency, packaging, OIDC, and Azure target check. Azure OneDeploy then left only an incomplete `Receiving changes / Fetching changes` receipt before any post-deploy migration, marker, or restart step ran. The deployment step now retries the same immutable ZIP up to three times; every attempt remains hard-bound to the already verified staging resource.

## Human dependency queue

1. Stripe: create/verify the UAE business account, complete KYC/bank setup, provide test keys/webhook secret, later approve live credentials. Engineering uses a fake/test adapter until then.
2. Meta: provide staging configuration IDs, Business Manager permissions, approved test WABA/phone, and OTP/QR participation. No live Meta change occurs automatically.
3. Push: provide Firebase/APNs/web-push project credentials if native delivery is selected. Internal notification intent and fake-provider paths do not depend on them.
4. Production: explicit GO approval remains mandatory after the completed staging rehearsal and reviewed runbook.

## Rollback posture

Each phase is an additive, separately reviewable staging commit. Application rollback redeploys the previous staging commit. Additive tables are retained during code rollback unless an explicit staging-only rollback is approved; no destructive migration is run automatically. Production promotion and rollback commands will be documented in Phase 9 but never executed in this sprint.
