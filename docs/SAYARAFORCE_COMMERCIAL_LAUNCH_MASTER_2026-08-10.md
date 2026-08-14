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
| 5 — Growth, Performance, AI Pro | Complete | Commit `0a24daca`; workflow `31346356662` and live verifier passed after transient queue initialization |
| 6 — mobile/service-manager experience | Complete | Commit `5412c5b5`; workflow `31347576717` and live verifier passed; fake push only |
| 7 — Meta/WhatsApp UAT readiness | Complete | Commit `bd63053f`; workflow `31348427916` and live verifier passed; live Meta remains parked |
| 8 — full staging launch rehearsal | Complete | Commit `b23ce862`; workflow `31349240822` and guarded live verifier passed |
| 9 — production migration preparation | Complete | Commit `a50e8258`; workflow `31349953947` and guarded live verifier passed; production untouched |
| 10 — commercial launch experience | Complete | Commits `42bfa7c3`, `00065967`; workflow `31351005730`, live verifier and public-page smoke passed |
| 11 — measurement foundation | Complete | Commit `af0d55ef`; workflow `31351719337`, live verifier and public smoke passed; fingerprint `6c5799c4...a39480` |

## Phase 1 completion record

Phase 1 introduced stable plan codes (`free`, `service`, `growth`, `performance`, `ai_pro`), immutable plan versions and explicit AED price records, subscription lifecycle foundations, canonical fail-closed capabilities, tenant overrides, usage/audit records, centralized company status enforcement, explicit Free registration assignment, and server-side role/entitlement boundaries. Database IDs have no commercial meaning. Launch and standard prices are explicit records. The original 12-cycle policy was superseded on 2026-08-12 by the approved three-successful-paid-cycle policy described below. Marketing and autonomous WhatsApp remain disabled.

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
- Commit `0a24dacac20a88cdf97da3df2df622de33c81662` deployed through workflow `31346356662`. The first live verifier reached its final queue check while the WebJob was still initializing; the worker recovered to Running without intervention and the complete verifier then passed.

## Phase 6 decisions and validation

- Repository evidence shows responsive Laravel/Inertia web surfaces and an installable web manifest, but no service worker, native client, FCM/APNs integration, or existing push-token domain. Phase 6 therefore extends the responsive product rather than inventing a separate native application.
- `notification_intents` persists tenant/user-scoped intent, lifecycle, idempotency, delivery timestamps, and privacy-minimized payload metadata. Supported types cover new enquiry, high intent, follow-up due/missed, booking upcoming, next service, and billing attention.
- `push_devices` stores only a tenant-scoped HMAC token identity plus encrypted device token; API responses hide both. Registration/revocation is authenticated, user/tenant scoped, and idempotent.
- Push delivery is provider-neutral. `fake` performs no network operation and is the only enabled staging driver. External drivers fail closed unless the environment explicitly enables delivery; Firebase/APNs/web-push credentials remain a human dependency.
- Background delivery rechecks company status and user/tenant linkage, suppresses disabled tenants or missing devices, and never falls back to WhatsApp/SMS/email.
- A responsive notification centre and 44-pixel mobile actions were added for admin/manager users. The shared Service dashboard now generates role-correct links and exposes the notification centre.
- Focused notification/mobile plus manager and Free/Service regression coverage passed 42 tests with 336 assertions. The full suite passed 243 tests with 1,473 assertions and the frontend production build passed. Two guarded disposable MySQL cycles passed with 125 base tables, two views, 45 migrations, 157 foreign keys, 458 routes, two synthetic tenants, and identical fingerprint `62e255336ee6f481ed2178673defcb9d264a8199971349771dcdb380631c1e5b`.
- Commit `5412c5b54bd0a02fb30c5c9b013cc5b7075d9f14` deployed through workflow `31347576717`. The guarded live verifier passed with the exact deployment marker and fingerprint, notification tables present, fake push/external delivery settings enforced, queue running, scheduler disabled, and production read-only verified Running.

## Phase 7 decisions and validation

- `staging:meta-readiness` produces a restricted presence/safety report without printing IDs, credentials, tokens, WABA/phone values, or recipient values and without making a Meta request.
- Engineering readiness and human-owned live-UAT configuration readiness are separate. Missing Meta application/config IDs and test assets remain visible dependencies but do not weaken or block synthetic engineering verification.
- Staging remains fail closed: production WABA/phone denylists and staging test-asset allowlists are required before an asset can resolve; legacy company resolution, WhatsApp outbound, and SMS outbound remain disabled.
- The handoff documents the additive domain, callback and Embedded Signup URLs plus the exact Key Vault/App Service settings the human must supply. It explicitly stops if a Meta dashboard action would replace a production callback.
- Existing signed synthetic coverage continues to prove signature rejection, unknown/denied assets, replay/idempotency, raw capture before enrichment, clean lead progression, quota exhaustion, coexistence history/echo isolation, and no unintended outbound. The new readiness suite adds configuration-presence and secret-nondisclosure assertions.
- Phase 7 focused readiness/staging/webhook/Embedded Signup coverage passed 39 tests with 207 assertions. The complete regression suite passed 246 tests with 1,495 assertions. No migration or frontend change was introduced; the approved fingerprint remains `62e255336ee6f481ed2178673defcb9d264a8199971349771dcdb380631c1e5b`.
- Commit `bd63053f6fee3dcf9514820bf1b0ee1253a6c2dd` deployed through workflow `31348427916`. The guarded live verifier passed with the exact marker and fingerprint, queue running, scheduler disabled, outbound guards closed, and no production write. It correctly reported live Meta guard configuration incomplete; this remains the documented human-owned dependency.

### Phase 7 controlled live-UAT follow-up (2026-08-14)

- The approved Meta app can coexist safely only through Meta's per-WABA callback override. The canonical onboarding client now posts the exact staging callback and staging verification token while subscribing the test WABA, then requires the same `override_callback_uri` in the provider read-back before the connection can become ready. The feature is disabled by default and fails closed on a missing, non-HTTPS, non-canonical, or credential-bearing URL.
- The canonical readiness report now uses the same `messaging.providers.meta_whatsapp` configuration consumed by provisioning, reports the canonical owner/completion/webhook endpoints, and distinguishes inbound-only readiness from the separately authorized outbound recipient allowlist.
- Coexistence health now requires `messages`, `history`, `smb_app_state_sync`, and `smb_message_echoes`; dedicated Cloud API health requires `messages`. No Meta request was made and no app, WABA, phone, callback, or production configuration was changed.
- Focused Meta/WhatsApp/security/billing regression coverage passed 166 tests with 1,090 assertions. The complete suite passed 346 tests with 2,304 assertions (one intentional skip and one upstream PHPUnit deprecation); PHP lint, Pint, Bicep compilation, and the frontend production build passed.

## Phase 8 decisions and validation

- The composite launch rehearsal starts at the real public-registration endpoint, asserts exactly one tenant, garage, admin, active Free subscription, zero messaging connections, WhatsApp-ready onboarding access, and the Free AI allowance.
- It upgrades the same tenant through Service, Growth, Performance and AI Pro using only the fake/test checkout plus a signed, normalized provider event. Redirects cannot unlock access; verified events reuse one local/provider subscription and create one idempotent provider receipt per transition.
- The rehearsal asserts each commercial boundary at the point of upgrade, including Service reminders without jobs, Growth operations without campaign intelligence, Performance measurement without action execution, and AI Pro approval-required action AI without autonomous WhatsApp.
- A safe notification intent is persisted without external delivery. Mail remains log-only; WhatsApp and SMS outbound remain disabled; no Meta connection or provider asset is created.
- The focused rehearsal plus registration, ingress, metering, billing lifecycle, limits, tenant roles, inbox isolation, notification and tier-boundary suites passed 78 tests with 463 assertions. The complete suite passed 247 tests with 1,541 assertions.
- Separate existing cases in the same gate cover duplicate webhook, raw capture before enrichment, quota exhaustion, concurrent/customer-period metering, failed payment/grace, retry-safe provider events, cancel-at-period-end, introductory-price transition, user/number limits, expired overrides, suspended companies, locked APIs, and queued-job denial.
- No migration or frontend change is introduced in Phase 8. The canonical fingerprint remains unchanged; the live staging run will retain synthetic-only data and make no card, Meta, push, SMS, email or WhatsApp network call.
- Commit `b23ce86273570c41b8f5197074a76fb3b0f90271` deployed through workflow `31349240822`. The guarded live verifier passed with isolated staging configuration, queue worker Running, scheduler disabled, Meta UAT guard incomplete by design, and no secret output.

## Phase 9 decisions and validation

- `commercial:plan-legacy-migration` is a read-only planning command. It reports aggregate classifications by default and exposes only optional numeric company IDs when an authorized operator deliberately requests them. It does not load or print tenant names, emails, contracts, credentials, or customer records and performs no mutations.
- The planner distinguishes explicit canonical assignments, grandfathered subscriptions, legacy contracts needing a reviewed entitlement snapshot, canonical-plan references needing approval, subscription records needing manual review, and plan-less tenants. No database ID is treated as commercial meaning.
- The production promotion runbook fixes the later order as backup and identity verification, additive schema migration, catalogue/legacy shadow evaluation, explicit human approval, application release, cache rebuild, queue restart, and production verification. It includes a rollback posture and a hard stop before any grandfathering or enforcement change.
- Synthetic tests prove legacy classifications, aggregate-only output, optional ID output, and zero database mutation. Focused Phase 9 validation passed two tests with 14 assertions.
- This phase prepares production migration only. No production database, app, Azure setting, queue, tenant, or entitlement was queried or changed.
- Commit `a50e82580ae3d572b8a35bdab473d3f232c6b462` deployed through workflow `31349953947`. The guarded live verifier passed with the exact deployment marker, unchanged schema fingerprint, queue worker Running, scheduler disabled, and Meta/outbound controls still closed.

## Phase 10 decisions and validation

- The public catalogue now comes from active immutable plan-version and price records. Marketing configuration contains positioning, benefits and calls to action only; it contains no duplicate price amounts.
- The page presents Free, Service, Growth, Performance and AI Pro with the approved launch and standard AED amounts. AI Pro is explicitly “from” pricing. The current page follows the audited Launch Offer state: three verified paid launch months when ON, standard-only pricing for new subscriptions when OFF, without claiming a percentage discount.
- The primary proposition is “Connect your WhatsApp. Stop losing bookings.” Free and Service remain self-service entry points; higher plans can use upgrade/contact positioning without fake testimonials or fabricated outcomes.
- The public legal page points to the versioned catalogue and verified checkout terms instead of hard-coded legacy prices.
- Locked-feature upsells use tenant-scoped counts already present in the application. They render no usage claim when no real usage exists, and never manufacture a metric.
- Focused public-site, launch-experience and Free/Service validation passed 14 tests with 112 assertions. The frontend production build passed; its only notices were existing Browserslist, dynamic/static chunk and font-resolution warnings.
- Phase 10 introduces no migration. Its exact commit will receive the complete CI regression suite and live staging verification before the phase is marked complete.
- Initial workflow `31350607386` stopped in the full suite before deployment because the legacy root-page smoke test deliberately boots without migrations and the catalogue reader queried `prices` unconditionally. The reader now returns an empty catalogue only when the commercial schema is unavailable, so bootstrap/maintenance states do not return HTTP 500; it never substitutes hard-coded prices. The reproduced failing smoke plus public catalogue regression then passed seven tests with 72 assertions.
- Commits `42bfa7c3782e8ebe86bc2b078e384f4c7f5f12a3` and `000659674d3464bbfd21dec97c7e0e94b7a6f157` passed corrected workflow `31351005730`. The guarded live verifier passed after the ordinary post-deploy cold start, with the queue Running, scheduler disabled and outbound/Meta guards closed. Public staging returned 200 and displayed all five catalogue amounts plus the split-line “Connect your WhatsApp. Stop losing bookings.” proposition.

## Phase 11 decisions and validation

- `product_events` is a tenant-aware, privacy-minimized ledger. Event names and property keys are whitelisted, arbitrary arrays are refused, obvious email/phone-like values and oversized strings are rejected, and only a SHA-256 idempotency key is persisted. Optional telemetry fails safely if its additive table is not yet available during deployment and cannot block registration, billing, inbound capture, or another primary operation.
- Instrumented milestones include registration start/completion; WhatsApp onboarding start/connection; first inbound, lead, opportunity and booking; 50/80/100 percent AI allowance thresholds; locked-feature upgrade views; checkout start; subscription activation/failure/cancellation; and verified plan upgrades/downgrades. First-event and threshold keys are tenant/period scoped and idempotent.
- AI thresholds derive from the configured tenant allowance. No raw customer identifier is copied to product events, and raw inbound capture still precedes all measurement work.
- The platform-only commercial dashboard reports entitled plan assignments, verified Free-to-Service and Service-to-Growth transitions, cancellations, failed payments, incomplete checkout count, AI runs/tokens/recorded cost units, monitored-customer averages, allowance pressure, provider-reported WhatsApp usage/cost, and verified AED invoice revenue. Payment fees remain explicitly unavailable rather than guessed.
- A Phase 11 review found and fixed a latent P1 billing defect: the verified paid-invoice branch used an introductory-cycle variable that had been assigned only in the cancellation branch. A new regression proves a paid invoice increments exactly one cycle and records AED 199.00 once.
- Focused observability, billing, registration, lifecycle/metering and Meta-onboarding coverage passed 47 tests with 300 assertions across the two reported groups. The complete suite passed 256 tests with 1,595 assertions; warning classification remains the known absent clean-worktree Vite manifest, plus one pre-existing PHPUnit doc-comment deprecation. PHP lint and Pint passed, and the frontend production build passed with only the existing Browserslist/font/chunk notices.
- Two guarded disposable MySQL cycles passed with 126 base tables, two valid views, 46 migration records, 159 foreign keys, 459 routes and identical fingerprint `6c5799c461f3935342817ce3cb17649b400b59b97cdf8d801de7de7a55a39480`.
- Phase 11 adds only `2026_08_10_000005_create_product_events`. It is additive, has nullable tenant/user foreign keys with null-on-delete behavior, event/time indexes and a unique dedupe key.
- Commit `af0d55ef48638c54fbcef432577ce966f3965408` passed workflow `31351719337`, including the exact full suite, frontend build, staging-only package deployment, additive migration, cache rebuild, marker and health checks. The independent live verifier accepted 126 base tables, two views and fingerprint `6c5799c461f3935342817ce3cb17649b400b59b97cdf8d801de7de7a55a39480`; queue Running, scheduler disabled, outbound guards closed and production isolated. Public staging returned 200 with all five catalogue tiers, while unauthenticated platform commercial metrics correctly redirected to login.

## Post-launch staging UAT correction — plan identity and number claims

- The global and manager shells no longer default to a hard-coded Growth label or probe obsolete company package attributes. A shared badge component now calls `EntitlementService::planIdentity`, resolves only stable canonical codes, fails closed as `UNASSIGNED`, and performs no application-level label cache. Free, Service, Growth, Performance, AI Pro, subscription switches and grandfathered canonical versions are covered.
- `messaging_number_claims` represents a tenant-owned E.164 number before Meta verification. It is explicitly not a provider phone record and cannot carry WABA IDs, provider phone IDs, tokens or connected state. The existing `messaging_phone_numbers` table remains provider authority after Meta confirms the exact saved number.
- Free and Service number limits are enforced by `ResourceLimitService` from `limit.whatsapp_numbers`; pending claims and verified phones share the same allowance. Duplicate, second-number, cross-tenant update/delete, provider-field spoofing, replacement and verified-connection regression cases are covered.
- The WhatsApp page now keeps number entry available when Meta configuration is absent, defaults its UAE country-code selector to +971 while accepting international E.164 numbers, separates number/mode entry from Meta connection, and presents Number added → Meta verification → Connection → Webhook → Ready truthfully.
- Focused WhatsApp/commercial/tenant-isolation coverage passed 125 tests with 738 assertions. The complete suite passed 270 tests with 1,689 assertions; PHP lint passed 696 files and the frontend production build passed with only the existing Browserslist/font/chunk notices.
- Two guarded disposable MySQL cycles passed with 127 base tables, two valid views, 47 migration records, 163 foreign keys, 462 routes, two synthetic tenants, and identical fingerprint `9bd58c304af5a43125c69066b458436c0f2ea0f701866c9bb992302ab8304dd3`.
- Meta configuration remains parked, outbound WhatsApp/SMS remain disabled, mail remains log-only, and no production resource or provider asset is part of this correction.
- Initial staging workflow `31427555348` passed source, test, build, deployment, additive migration and exact fingerprint gates, then correctly stopped before marker/cache completion because the original live verifier treated one legitimate public-registration UAT tenant as non-synthetic. The verifier now requires the original two seeded tenants and four seeded users, explicit subscriptions, isolated schema, synthetic operational fixtures and zero provider credentials, while allowing additional self-service staging tenants without inspecting or reporting their identities.

## Post-launch staging UAT correction — verified fake billing lifecycle

- Root cause: the staging fake checkout emitted one checkout/subscription event carrying `payment_status=paid`; the shared processor immediately activated the requested plan while no invoice event existed. The success redirect itself was non-entitling, but the fake provider collapsed provider confirmation and payment settlement into one event.
- Checkout and subscription confirmation now persist provider identity on the checkout as `payment_pending`. Only a verified, idempotent `invoice.paid` or `invoice.payment_succeeded` event may activate the requested price, set payment to paid, complete checkout, and unlock entitlements.
- Fake checkout emits the same normalized two-event lifecycle expected from Stripe: provider checkout/subscription confirmation followed by a synthetic paid invoice. Failed and cancelled checkouts leave the previous subscription unchanged; repeated provider events remain idempotent.
- Billing history now preserves checkout, immutable price and test-mode attribution and labels fake invoices as TEST or STAGING / TEST. A guarded staging/test-only reconciliation command converts legacy fake paid checkouts without history into signed, normalized invoice events; it does not run in production.
- Stripe normalization now reads subscription metadata carried by invoice parents and records test/live mode in the same normalized contract as the fake adapter. Real Stripe entitlement changes remain webhook-driven; browser return URLs never activate access.
- Focused billing/commercial/staging validation passed 41 tests with 311 assertions. The complete suite passed 276 tests with 1,741 assertions; modified PHP lint and the frontend production build passed.
- Two guarded disposable MySQL cycles passed with 127 base tables, two views, 48 migration records, 165 foreign keys, 463 routes and identical fingerprint `b763b44beb37674d819e3bee0c67f84c33a1832944ac6a706cb983f7e22b38fd`. The disposable database was removed after validation.

## Stripe sandbox handoff safety correction

- The handoff audit confirmed that Stripe API requests already required `BILLING_MODE=test` and an `sk_test_` secret, but webhook verification could not distinguish a test and live `whsec_` value. The Stripe event payload's authoritative `livemode` field was not rejected, so a correctly signed live event was a staging safety gap.
- The Stripe adapter now validates test billing mode before API calls and webhook verification, rejects `sk_live_` and optional `pk_live_` credentials, restricts the staging API base to the official Stripe HTTPS endpoint, and rejects live-mode or mode-unclassified Stripe events before provider-event persistence.
- The provider remains `fake`; no Stripe or Azure value was configured. Tests prove a valid signature cannot bypass the live-event guard and that invalid test/live configuration records no provider event.
- The same audit exposed a timing-sensitive fake-event replay: retrying a checkout completion after the wall clock advanced reused an event ID with regenerated timestamps. Fake checkout timestamps now derive from the persisted checkout creation time, and a forced delayed replay proves the payload and two-event receipt set remain idempotent.
- Focused billing coverage passed 21 tests with 141 assertions. The complete suite passed 280 tests with 1,749 assertions; modified-file PHP lint and the frontend production build passed. Existing clean-worktree Vite-manifest warnings and one PHPUnit doc-comment deprecation remain unchanged.

## Stripe Sandbox readiness — price mappings and webhook contract

- `billing:map-stripe-sandbox-price` maps exactly one stable paid plan code and `launch|standard` phase to one external `price_...` identifier. It is restricted to staging/testing, requires exactly one of `--dry-run` or `--confirm`, validates the canonical AED/month catalogue and positive phase amount, refuses slot/external-ID conflicts, treats an exact active rerun as a no-op, and writes an audit record only when it creates a mapping. It never queries Stripe, stores a secret, deletes history, or treats a database plan ID as commercial identity.
- The eight required invocations are `service|growth|performance|ai_pro` multiplied by `launch|standard`. Operators must dry-run each value before confirming it. Stripe Product/Price currency, interval, and amount must also be reviewed in the Stripe Sandbox dashboard before provider cutover because the import command intentionally has no credential or network dependency.
- Stripe's current official API version on 2026-08-11 is `2026-07-29.dahlia`. `STRIPE_API_VERSION` now pins outbound requests and signed event normalization to that exact reviewed contract. A missing or different event `api_version`, live-mode event, malformed envelope, wrong object type, absent required field, invalid Price identifier, or non-AED invoice fails before partial application.
- Synthetic fixtures cover `checkout.session.completed`, `customer.subscription.created`, `customer.subscription.updated`, `customer.subscription.deleted`, `invoice.paid`, and `invoice.payment_failed`. The reviewed Dahlia paths include subscription-item period fields, invoice `parent.subscription_details`, and invoice-line `pricing.price_details.price`; retained legacy fallbacks do not weaken exact API-version enforcement.
- `checkout.session.expired` is additionally normalized. It moves an open local checkout to the terminal `expired` state, records a non-activation audit, does not change the subscription, and blocks later checkout/subscription/payment events from confirming or activating that terminal checkout. The Sandbox event destination must subscribe to this seventh event as well.
- This readiness change has no migration and creates no live mapping. Staging remains on the fake provider until eight real Sandbox Price IDs, Key Vault references, and the exact-version event destination are supplied and separately authorized.
- Focused Stripe readiness and billing coverage passed 31 tests with 231 assertions. The complete suite passed 290 tests with 1,839 assertions; modified PHP lint, Pint, JSON fixture parsing and the frontend production build passed. Existing warning classification remains limited to the known test-harness notices and one PHPUnit doc-comment deprecation.

## Stripe Sandbox price mapping and three-month Launch Offer

- The eight externally created Sandbox Prices remain provider mappings only. `billing:map-stripe-sandbox-price` validates stable plan code, `launch|standard` phase, the canonical configured amount, AED currency, monthly interval, identifier format, slot uniqueness, and exact-rerun idempotency without a Stripe API call or secret.
- One audited `commercial_settings.launch_offer_enabled` value controls eligibility for future paid subscriptions. Only platform administration can change it. The warning and price table are available independently of Stripe configuration; tenant administrators cannot reach the route.
- Each checkout snapshots its server-selected `launch|standard` phase and whether it represents a new qualification. Verified paid activation persists qualification on the tenant subscription. Cancelling or expiring checkout, browser return, provider checkout confirmation, failed invoice, and unpaid invoice never enroll or consume the offer.
- A qualified subscription keeps its remaining introductory cycles when the global offer is later disabled. Re-enabling cannot reset or regrant an offer because qualification and consumption history remain on the same tenant subscription. Existing paid standard subscribers remain standard.
- Only a newly recorded, verified paid provider invoice increments the introductory counter. Both provider event IDs and provider invoice IDs are idempotency boundaries, so a replay or a second paid-event notification for the same invoice cannot count twice.
- After the third successful paid monthly invoice, the controlled transition job resolves the same plan's standard mapping, requests an idempotent provider update, and only after provider success records the standard Price ID, transition timestamp, consumption timestamp and audit entry. A provider exception leaves those fields unset for queue retry.
- Public, tenant billing and contextual locked-feature pricing derive from the canonical catalogue plus Launch Offer policy. ON shows the launch amount for three successful paid months and the standard renewal amount; OFF shows standard pricing only for newly eligible customers. Historical invoices remain immutable.
- The additive migration creates one settings table, checkout snapshots and subscription history, and applies the approved 12-to-3 catalogue correction. Two clean MySQL cycles produced 128 base tables, two views, 49 migration records, 166 foreign keys, 464 routes and identical fingerprint `30898ba38ad4b1d9031e8cdd26d99eb0d09f99695ffeccb7fa2a9c0fe6d91ce8`.
- Local focused coverage passed 51 tests with 409 assertions before the final de-duplication extension; the complete suite passed 296 tests (285 warning-classified view-manifest cases plus 11 ordinary passes) with 1,905 assertions. Changed-file Pint, repository PHP lint and the frontend production build passed.
- Staging release evidence, exact commit, workflow, live mapping import, ON/OFF verification and final provider state are recorded after deployment below/with the release handoff. `BILLING_PROVIDER` remains `fake`; Stripe credentials, webhooks, card charges, annual pricing, Meta and production remain unchanged.

## WhatsApp coexistence history intelligence and package limits (2026-08-14)

- The existing coexistence history webhook now terminates in an encrypted, tenant-scoped review quarantine. Discovery and analysis create no Client, Lead, Opportunity, Booking, Job, campaign, reminder, automation or outbound message. Only administrator-approved Track contacts can be imported into the existing Client/Conversation/MessageLog structure.
- Don't Track becomes a tenant-scoped future-ingress policy, purges unnecessary staged bodies/media and is reversible only through a deliberate resync. Pending candidates quarantine subsequent live messages; genuinely new live senders retain the ordinary CRM lifecycle.
- The canonical launch matrix now consistently enforces users, locations, WhatsApp numbers, billing-period AI customers, cumulative history contacts, billing-period campaign creation, per-campaign recipients and concurrent active workflows. AI Pro remains custom/fair-use rather than a hard-coded public allowance.
- Bounded observational intelligence suggests customer/personal/colleague/unknown and high/medium/low/none retention evidence. It never emits or executes a CRM action, and deterministic staff matching precedes semantic analysis.
- The additive migration creates six review/metering tables and historical-message attribution. A following fail-closed, mode-only repair handles dotted AI Pro limit keys without changing prices, subscriptions, invoices or provider mappings. Two clean MySQL cycles passed with 135 base tables, two valid views, 53 migrations, 191 foreign keys, 484 routes and identical fingerprint `1edb26c7d59c3687c8e3f74f073eebb3f3a0a9496dbe016ef3a3066096178d63`.
- The full architecture, privacy semantics, exact limits and controlled real-Meta stopping point are documented in `docs/SAYARAFORCE_WHATSAPP_HISTORY_INTELLIGENCE.md`.

## WhatsApp Quick Scan and history quota correction (2026-08-14)

- Historical contact allowance now belongs to the first successful deep analysis of a unique tenant-scoped identity. Track consumes nothing further, Don't Track never refunds, deterministic staff exclusion does not consume, and reconnect/resync/new batch/billing rollover cannot reset cumulative use. Import refuses candidates that have no canonical analysis usage, closing direct route/job bypass.
- Quick Scan is an explicitly enabled staging acquisition feature, not a tenant or plan. A platform administrator creates an encrypted temporary garage prospect workspace and a random, expiring/revocable 64-character link/QR. The garage provides versioned consent, the claimed WhatsApp number, connection mode and optional staff exclusions before personally completing the existing Meta Embedded Signup contract.
- Provider assets remain server-controlled and must pass token inspection, claimed-number match, staging allowlists, production denylists and cross-workspace/tenant uniqueness. Normal tenant webhook routing has priority; unresolved scan history may enter the isolated quarantine, while live messages/statuses/echoes are server-side ignored and cannot create CRM or outbound activity.
- The shared `HistorySignalAnalyzer` powers bounded customer/noise, retention, missed-enquiry, quote and service suggestions. Quick Scan has its own configurable 500-contact staging ceiling and usage counters and never consumes paid tenant history/AI meters. The aggregate report masks examples and makes no revenue or lost-customer claim.
- Start SayaraForce enters normal registration, explicit Free subscription and admin security, then rebinds customer-level history only into canonical Track / Don't Track quarantine. It creates no Client. Not Now schedules a physical purge of messages, candidates, identifiers, provider payloads/tokens and identifiable evidence while retaining only encrypted garage-level B2B prospect fields, aggregate metrics, outcome, follow-up and deletion evidence.
- The initial MySQL cycle correctly stopped on an auto-generated 65-character foreign-key name; the migration now uses a reviewed short constraint. Two restarted clean cycles passed with 141 base tables, two views, 54 migrations, 202 foreign keys, 501 routes and identical fingerprint `995333181942e36ee3b141d996aa71d12fc502133447f75eda7ebec817d51005`.
- Focused quota/Quick Scan coverage passed 22 tests with 236 assertions. Cross-cutting Meta, coexistence, webhook, tenant, 2FA, billing and entitlement coverage passed 160 tests with 1,158 assertions. The complete suite passed 367 tests with 2,542 assertions and one intentional integration skip; changed-file lint, Pint and the frontend production build passed.
- Synthetic-only staging deployment evidence, exact commit/workflow, live 500-contact report metrics, responsive screenshots and the production-isolation check are appended after the staging release. No real garage, Meta login, customer history or outbound action is authorized by this sprint.

## Human dependency queue

1. Stripe: create/verify the UAE business account, complete KYC/bank setup, provide test keys/webhook secret, later approve live credentials. Engineering uses a fake/test adapter until then.
2. Meta: provide staging configuration IDs, Business Manager permissions, approved test WABA/phone, and OTP/QR participation. No live Meta change occurs automatically.
3. Push: provide Firebase/APNs/web-push project credentials if native delivery is selected. Internal notification intent and fake-provider paths do not depend on them.
4. Production: explicit GO approval remains mandatory after the completed staging rehearsal and reviewed runbook.

## Rollback posture

Each phase is an additive, separately reviewable staging commit. Application rollback redeploys the previous staging commit. Additive tables are retained during code rollback unless an explicit staging-only rollback is approved; no destructive migration is run automatically. Production promotion and rollback commands will be documented in Phase 9 but never executed in this sprint.
