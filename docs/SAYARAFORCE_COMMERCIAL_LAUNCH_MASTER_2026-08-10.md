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
| 3 — billing engine and payment gateway | Complete | Commits `5a16cdbb`, `f168a558`; workflow `31343944379` passed; live verifier passed; fingerprint `ffcb44d...7697` |
| 4 — Free and Service product | Complete | Commit `ba3bfc19`; workflow `31345416690` and live verifier passed; schema fingerprint unchanged |
| 5 — Growth, Performance, AI Pro | Local gate passed; staging deployment pending | Direct-route/job tier boundaries and explicit approval for AI actions; AI Pro 10,000 remains provisional/configurable |
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
- Commit `f168a5584e7961cfe43cbe18a12fe00d3dc03869` added that deterministic OneDeploy retry. Workflow run `31343944379` then deployed the exact commit successfully. The guarded live verifier passed after the first custom-domain cold-start check timed out and subsequent custom/Azure hostname health probes returned 200. The queue worker remained running, scheduler disabled, schema unchanged, and production was read-only verified Running.

## Phase 4 decisions and validation

- Tenant web routes now resolve through one `RouteCapabilityMap` and a global fail-closed middleware. Direct URLs and write requests are denied server-side; GET requests receive a contextual 403 locked screen. Platform roles retain their separately authorized platform behavior and tenant plans never grant platform access.
- Free retains customers, vehicles, leads, opportunities, bookings, calendar, Inbox/manual reply, WhatsApp connection, one user/location/number, and 25 monitored customers. Jobs, invoices, campaign intelligence, marketing outbound, and autonomous AI remain denied.
- Service adds the purpose-built service dashboard, transactional WhatsApp, reminders, follow-up and next-service capabilities, three users, one location/number, and 300 monitored customers. Jobs/invoices and marketing intelligence remain off.
- User and WhatsApp-number allowances are checked against real tenant records. A known number may reconnect; a second distinct number is refused. Invalid tenant-role input is validated before quota enforcement so the Phase 1 role-escalation control remains authoritative.
- Every active Meta outbound call now declares a commercial purpose: manual, transactional, marketing, or AI autonomous. The service checks that capability before provider/staging safety. Marketing and AI autonomous outbound remain disabled in every launch plan. The legacy unified notifier configuration is email-only and its Twilio branch is unreachable from registered event mappings.
- Free and Service dashboards use only tenant-scoped operational counts. The Service view shows enquiries, follow-ups, qualified leads, bookings, due-service customers, response/conversion measures, and actual AI allowance usage; it contains no campaign or ROI metrics.
- `UpsellPresentationService` centralizes the product ladder, catalogue prices, and approved copy. Locked UI is presentation only; authorization is always server-side.
- Legacy route tests that previously created plan-less tenant fixtures now assign an explicit canonical plan. No testing or environment bypass was introduced.
- Focused Free/Service plus Phase 1/2 regression coverage passed 24 tests with 120 assertions. The complete suite passed 230 tests with 1,413 assertions; the only warnings remain the known clean-worktree Vite-manifest warning. No schema migration is introduced in Phase 4, so the approved commercial fingerprint remains `ffcb44d1847c7d9c75d710ad8a868b4ea55424a659ffbd62aef9597ecbab7697`.
- Commit `ba3bfc192135c993e77f642ed40e7e4a23f2f997` deployed through workflow `31345416690`. The guarded live verifier passed with the exact marker and fingerprint, isolated configuration, queue running, scheduler disabled, and real Meta/outbound guards still closed.

## Phase 5 decisions and validation

- Growth unlocks operations, management, staff/source/retention insight, basic campaign attribution, and standard AI recommendations. It does not unlock campaign intelligence, ROI, advanced reports, priority scoring, or action execution.
- Performance adds campaign intelligence/ROI, advanced reports/retention, and priority scoring. It does not receive action execution or autonomous WhatsApp.
- AI Pro adds controlled action execution/follow-up/reactivation with `approval_required` mode. Its 10,000 monitored-customer staging allowance remains catalogue configuration only and is not presented as a fixed commercial promise.
- `CommercialActionGate` enforces entitlement mode as behavior. Background middleware now refuses approval-required jobs unless the job provides an explicit, verifiable approval signal.
- AI reply generation now uses `ai_recommendations` and only persists a recommendation. It no longer creates or mutates an Opportunity from an inbound message. Approved suggestion delivery is a separate notification-queue job that rechecks the AI action entitlement, the recorded approving user, tenant linkage, and manual WhatsApp entitlement before provider execution.
- AI configuration, policy, insights, suggestion, approval, and rejection routes now have specific capabilities instead of inheriting one broad observational check. Direct Service/Growth/Performance route probes return 403 at their tier boundaries.
- Focused Phase 5/Phase 1/Phase 4 coverage passed 25 tests with 130 assertions. The full suite passed 237 tests with 1,453 assertions. Phase 5 has no schema migration; the expected fingerprint remains unchanged.

## Human dependency queue

1. Stripe: create/verify the UAE business account, complete KYC/bank setup, provide test keys/webhook secret, later approve live credentials. Engineering uses a fake/test adapter until then.
2. Meta: provide staging configuration IDs, Business Manager permissions, approved test WABA/phone, and OTP/QR participation. No live Meta change occurs automatically.
3. Push: provide Firebase/APNs/web-push project credentials if native delivery is selected. Internal notification intent and fake-provider paths do not depend on them.
4. Production: explicit GO approval remains mandatory after the completed staging rehearsal and reviewed runbook.

## Rollback posture

Each phase is an additive, separately reviewable staging commit. Application rollback redeploys the previous staging commit. Additive tables are retained during code rollback unless an explicit staging-only rollback is approved; no destructive migration is run automatically. Production promotion and rollback commands will be documented in Phase 9 but never executed in this sprint.
