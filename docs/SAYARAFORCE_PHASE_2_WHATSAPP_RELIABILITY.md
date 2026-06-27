# SayaraForce Phase 2 - WhatsApp Reliability

Status: technical readiness pass complete for safe local verification.

## Config Surfaces Reviewed

- Tenant Meta/WABA surfaces: WABA ID, phone number ID, access token, verify token, and active WhatsApp flag are referenced on the company/tenant configuration path.
- App config surfaces: Meta app secret, Twilio credentials, default WhatsApp provider, queue connection, and optional public site WhatsApp CTA URL.
- Template surfaces: WhatsApp templates, template mappings, event keys, and campaign/template controllers exist.
- Webhook surfaces: Meta WhatsApp verify/handle routes and Twilio WhatsApp inbound/status routes exist under `/api/v1/webhooks/...`.
- Queue surfaces: inbound WhatsApp processing and outbound template/message jobs are queued.

No secret values were printed or documented.

FOUNDER ACTION REQUIRED: confirm which WABA, phone number, provider mode, templates, and test numbers will be used for v1.

## Inbound Flow Status

- Meta webhook verification checks the submitted verify token against tenant/company configuration.
- Meta inbound webhook validates the signature before processing.
- Valid inbound messages resolve the company by phone number ID and dispatch the inbound processing job.
- Invalid or empty payload structures fail safely without dispatching a message job.
- Status callbacks update outbound message logs with provider status and error metadata.
- Twilio inbound/status webhook handlers also exist and validate provider signatures.
- Inbound processing has duplicate provider-message protection and creates/updates message logs and conversations.

Tests added:

- Meta webhook verification accepts the correct tenant verify token and rejects the wrong token.
- Invalid Meta WhatsApp payloads are ignored without dispatching inbound processing.
- Valid Meta WhatsApp inbound messages dispatch the inbound job with company context.
- Meta status failure callbacks update the matching message log as failed.

## Outbound Flow Status

- Outbound sending is centralized through WhatsApp service/provider classes and event/template send services.
- Event-based WhatsApp sends use mapping checks, opt-out checks, and duplicate/action locks.
- Outbound attempts can persist success or failure state.
- Inbox manual replies return clear JSON errors on provider failure.
- Tests use mocks/fakes and do not send real WhatsApp messages.

FOUNDER ACTION REQUIRED: approve one real outbound test path before any live WhatsApp send.

## Duplicate Prevention Status

- Event sends use durable/cache locks to prevent repeated sends for the same business action.
- Inbound messages protect against duplicate provider message IDs.
- Message logs carry source/event/provider status context.

Remaining risk: older or custom sending paths should be reviewed before enabling campaigns broadly.

## Queue Worker Status

- Inbound WhatsApp processing runs on the database queue/default queue.
- Failed jobs are supported by the Laravel failed jobs table.
- Local queue proof should be done with `php artisan queue:work --once` or the configured worker process after Meta test payload approval.

FOUNDER ACTION REQUIRED: confirm the production queue worker/supervisor process before launch.

## Failed Message Visibility Status

- Provider status failures are stored against message logs.
- WhatsApp message/event failure records are persisted by the event send path.
- Manager/Admin inbox send failures return a clear JSON error instead of reporting success.
- Raw tokens are not surfaced.

Recommended next improvement: a small admin-safe failed WhatsApp report filtered by company and redacted phone/message metadata.

## Manager Inbox Log Status

- Manager inbox can list same-company conversations.
- Conversation context resolves lead/client relationships.
- Inbound and outbound message logs are shown through the inbox APIs.
- Manual replies pause/handoff linked leads to human mode.
- Phone search uses normalized matching and does not cross company scope.

## Remaining Manual Steps

- FOUNDER ACTION REQUIRED: choose WABA and phone number.
- FOUNDER ACTION REQUIRED: decide whether WhatsApp Business App coexistence is required.
- FOUNDER ACTION REQUIRED: approve v1 templates.
- FOUNDER ACTION REQUIRED: provide one real inbound test number.
- FOUNDER ACTION REQUIRED: approve one real outbound test.
- FOUNDER ACTION REQUIRED: confirm queue worker setup.

## Remaining Blockers

- Live WABA/template approval is not verifiable locally.
- Live domain/webhook subscription is not verifiable locally.
- Campaign sending should remain disabled until founder approval.
