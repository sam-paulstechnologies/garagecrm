# SayaraForce WhatsApp Business App onboarding audit

Audit date: 4 August 2026
Repository: `C:\laragon\www\garagecrm`
Branch at audit: `main`
HEAD at audit: `2955231a6ec2f10aeb2f1054cbe2148fdfeca58b`
Relevant prior change: `dc27f1d9938a8d1eba1ba66d5ffb56c0815f114e` (`Deploy UAT fixes and WhatsApp coexistence onboarding`)

This document records code evidence only. No live Meta request, production deployment, phone-number onboarding, or destructive database command was performed.

## Baseline

| Check | Result before this hardening pass |
|---|---|
| PHP | 8.3.16 |
| Laravel | 12.38.1 |
| Node | 20.19.6 |
| Browser routes | 423 |
| Full tests | 141 passed, 915 assertions |
| Production frontend build | Passed with the existing Browserslist/font/chunk warnings |
| Local migration state | The new hardening migration is intentionally pending; it was exercised by `RefreshDatabase` tests only |

## Specification basis

The implementation uses Meta Embedded Signup v4 configuration, `response_type: code`, `override_default_response_type: true`, session information version 3, and the Business App onboarding feature selector `whatsapp_business_app_onboarding`. The WABA subscription call is `POST /{WABA-ID}/subscribed_apps`. Sources reviewed:

- [Meta WhatsApp Business Platform — Embedded Signup collection](https://www.postman.com/meta/whatsapp-business-platform/documentation/du6gzjv/embedded-signup)
- [Meta WhatsApp Cloud API collection](https://www.postman.com/meta/whatsapp-business-platform/documentation/wlk6lh4/whatsapp-cloud-api)
- [Meta Embedded Signup v3 documentation](https://developers.facebook.com/documentation/business-messaging/whatsapp/embedded-signup/version-3)
- [Meta Business App onboarding documentation](https://developers.facebook.com/documentation/business-messaging/whatsapp/embedded-signup/onboarding-business-app-users)

The two developer-documentation URLs rate-limited automated inspection during this audit. Their live contents and the exact configured product variation must be rechecked in the Meta dashboard immediately before live UAT.

## Exact implementation found before changes

### Embedded Signup

- `GET /admin/whatsapp/connect` created a state and loaded the Facebook JavaScript SDK.
- The UI offered a legacy mode named `coexistence` and a standard Cloud API mode.
- Both choices used a shared configuration abstraction, but the existing-number browser flow launched `featureType: whatsapp_embedded_signup`; this did not prove Business App onboarding.
- `response_type: code` and `override_default_response_type: true` were present.
- Session information was accepted, but cancellation/error/timeout handling was incomplete and browser diagnostics could display raw session values.
- The callback accepted browser-supplied connection mode, WABA ID and phone-number ID without completing server-side asset ownership checks.

### Server completion

- Authorization-code exchange and encrypted token storage existed.
- A failed encryption attempt could fall back to plaintext token persistence.
- State was optional and not bound strongly to tenant, user, expiry and one-time processing.
- Token app/scope inspection, WABA grant validation, phone membership validation and WABA app subscription were missing.
- There was no migration in the repository for the coexistence columns/session table already present in the local database.

### Webhooks

- Signature validation, first inbound message dispatch, status updates and `smb_message_echoes` handling existed.
- The controller logged the complete raw webhook body, URL and request metadata.
- Only `entry.0.changes.0` and the first message were handled.
- `smb_app_state_sync` and `history` were not processed.
- Echo payloads were copied wholesale to message metadata.
- Tenant resolution selected the first company with a phone-number ID and did not reject ambiguous claims or validate a supplied WABA.
- Historical messages had no isolated store, so production-safe history synchronization was not available.

## Runtime path after hardening

### Existing WhatsApp Business app number

1. `WhatsAppEmbeddedSignupController@index` resolves the authenticated user's company.
2. `MetaEmbeddedSignupService::signupConfiguration()` requires the dedicated Business App configuration ID; it does not fall back to the standard Cloud API configuration.
3. `MetaEmbeddedSignupService::createState()` stores a 64-character, tenant/user/mode-bound, expiring state.
4. `connect.blade.php` launches v4 with `featureType: whatsapp_business_app_onboarding` and waits for both the one-time code and `FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING` session event.
5. `CompleteWhatsAppEmbeddedSignupRequest` validates the callback shape.
6. `MetaEmbeddedSignupService::complete()` atomically consumes the state, exchanges the code, inspects app/scopes, validates granted WABA and phone membership, confirms `is_on_biz_app`, rejects another tenant's phone, subscribes the app to the WABA, encrypts the token, and persists the connection plus a redacted audit record.
7. Contact synchronization is requested after connection. A sync-request failure is reported as a retryable warning and does not invalidate the already verified connection.

### Dedicated Cloud API number

The same server validation is used, but the separate Cloud API configuration and standard `FINISH`/`FINISH_ONLY_WABA` events are required. A number reported as still on the Business app is rejected from this path.

### Webhooks

1. `MetaWhatsAppWebhookController@handle` validates `X-Hub-Signature-256` before parsing.
2. Every entry/change is iterated.
3. Tenant resolution requires a unique active phone claim and validates a persisted WABA where one exists.
4. Ordinary inbound messages create a deduplicated receipt and dispatch `ProcessInboundWhatsApp` on the database/default queue.
5. Statuses are deduplicated and monotonic; stale out-of-order updates cannot regress `read` to `delivered`.
6. Business App echoes are logged once as outbound source `whatsapp_business_app`, may attach to an existing same-company lead/conversation, and never dispatch inbound processing.
7. `smb_app_state_sync` and `history` payloads are encrypted at rest and dispatched by ID to `ProcessWhatsAppCoexistenceWebhook`.
8. Contacts are stored in `whatsapp_synced_contacts` using a tenant-scoped HMAC lookup key plus encrypted PII.
9. Historical messages are stored in `whatsapp_history_messages` and never enter lead, conversation, reply or automation flows.

## Requirement classification

| Requirement | Status | Evidence |
|---|---|---|
| Separate existing-number and dedicated-number choices | Fully implemented and tested | connect Blade, service mode constants, `WhatsAppEmbeddedSignupTest` |
| Current Business App feature selector | Fully implemented and tested | config/service/Blade and deprecated-mode assertion |
| Config-driven v4/session information | Fully implemented and tested | `config/services.php`, `.env.example`, page test |
| Cancellation, denial, popup timeout, missing code/session | Fully implemented and tested in server path; browser branches statically verified | connect Blade and callback validation tests |
| Tenant/user-bound expiring state | Fully implemented and tested | connect session migration/service tests |
| Authorization code exchange | Fully implemented and tested | success/failure HTTP fakes |
| App and required-scope validation | Fully implemented and tested | `debug_token` validation test path |
| WABA grant and phone membership validation | Fully implemented and tested | service and tests |
| Confirm existing number remains on Business app | Fully implemented and tested | `is_on_biz_app` guard |
| WABA app subscription | Fully implemented and tested | `/{waba}/subscribed_apps` assertion |
| Encrypted credential persistence | Fully implemented and tested | `Crypt` assertion; no plaintext fallback in new flow |
| Idempotent callback/reconnect | Fully implemented and tested | completed-session test; WABA subscription is safe to repeat |
| Connection audit without secrets | Fully implemented and tested | `whatsapp_connection_audits` |
| Ordinary inbound messages | Fully implemented and tested | reliability and coexistence webhook tests |
| Delivery/read/failed status handling | Fully implemented and tested | existing failure test plus out-of-order test |
| Business App outbound echoes | Fully implemented and tested | echo-loop/duplicate test |
| Contacts synchronization | Fully implemented and tested | encrypted tenant-scoped contact test |
| Optional approved history synchronization | Fully implemented and tested | isolated history test |
| Historical automation suppression | Fully implemented and tested | no inbound job/message/lead/conversation assertions |
| Supported media metadata | Fully implemented and tested | image fixture mutation test; image/document/audio/video/sticker recognized |
| Duplicate webhook delivery | Fully implemented and tested | receipt uniqueness and one-job assertions |
| Strict cross-company isolation | Fully implemented and tested | ambiguous claim and asset-claim tests |
| Raw token/code/webhook redaction | Fully implemented and tested | no raw logging; encrypted raw-storage assertions |
| Webhook fields enabled in Meta dashboard | External Meta configuration required | cannot be proven locally |
| Business App number eligibility and app retention | External Meta configuration required | must be proven in controlled live UAT |
| Production queue worker and failed-job monitoring | External infrastructure confirmation required | code uses database/default queue with retries |

## Persistence changes

Migration `2026_08_04_000001_harden_whatsapp_business_app_onboarding.php` adds:

- explicit company connection mode, onboarding source, connection/subscription/sync health timestamps and statuses;
- durable, expiring `whatsapp_connect_sessions` fields;
- `whatsapp_connection_audits` (no secrets);
- `whatsapp_webhook_events` (encrypted payload, unique receipt key, retry status);
- `whatsapp_synced_contacts` (tenant-scoped hash and encrypted PII);
- `whatsapp_history_messages` (encrypted history isolated from operational messages);
- missing `message_logs.conversation_id`/`source` compatibility and a longer provider message ID.

No migration was executed against the developer database during this task.

## Security controls

- Tokens are encrypted with Laravel `Crypt`; the Business App onboarding path rejects legacy/plaintext values.
- Authorization codes and provider response bodies are never logged or persisted.
- Webhook bodies are no longer written to logs.
- Sync payloads are encrypted at rest; phone/name/history fields use encrypted casts.
- Jobs carry only an internal event ID for sync processing.
- Company ownership is derived from authenticated user/state on completion and from verified Meta assets on webhook delivery.
- Ambiguous asset claims are rejected, rather than selecting the first company.
- Browser callback IDs are hints only; the server re-fetches WABA phone assets and verifies membership.
- The UI returns masked phone details and never returns access/verify tokens.

## External Meta/dashboard requirements

Before live UAT, an authorised Meta administrator must confirm:

1. the app remains Live and the approved permissions remain active;
2. a dedicated Embedded Signup v4 configuration exists for WhatsApp Business App onboarding;
3. `META_WHATSAPP_BUSINESS_APP_CONFIG_ID` points to that exact configuration;
4. the standard Cloud API configuration remains separate;
5. the HTTPS OAuth domain/redirect and allowed domains are correct;
6. the callback URL is subscribed for `messages`, `smb_message_echoes`, `smb_app_state_sync` and approved history events;
7. the production app secret and webhook verify token are available only through the deployment environment;
8. the production database migration, queue worker, retry/failed-job monitoring and application logs are healthy.

## Audit verdict

The repository is technically prepared for a guarded live UAT after the quality gate. It is **not yet authorised for the founder's number** because the dedicated Meta configuration, production deployment/migration, webhook field subscriptions, queue worker, number eligibility, backup and live browser flow still require manual confirmation. See `WHATSAPP_COEXISTENCE_LIVE_UAT.md`.

## Final quality gate

- Changed PHP syntax: 41 files checked, no failures.
- Cache/view clear: passed.
- Route inventory: 426 routes; WhatsApp routes resolved.
- `--filter=EmbeddedSignup`: 10 tests, 45 assertions, passed.
- `--filter=Coexistence`: 7 tests, 47 assertions, passed.
- `--filter=WhatsApp`: 26 tests, 152 assertions, passed.
- Full suite: 158 tests, 1,007 assertions, passed.
- Vite production build: passed (1,055 modules).
- `git diff --check`: passed; only existing line-ending notices were printed.
- Existing non-blocking warnings: PHPUnit doc-comment metadata deprecation, stale Browserslist data, two runtime-resolved local font URLs, three large preview images, and the existing 591.30 kB application chunk.
