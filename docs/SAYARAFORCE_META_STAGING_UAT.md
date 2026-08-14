# SayaraForce staging Meta/WhatsApp UAT handoff

## Current boundary

The engineering path is implemented and can be exercised with signed synthetic fixtures. No live Meta application, WABA, phone number, recipient, credential, or outbound switch is changed by this repository process. Production Meta configuration remains untouched.

The approved Meta app may be shared only through Meta's **per-WABA callback override**. SayaraForce posts the staging callback and its staging verify token when subscribing the approved test WABA, then reads the subscription back and refuses readiness unless the returned `override_callback_uri` is exactly the canonical staging webhook. This prevents the staging WABA from falling back to the app-level production callback and avoids replacing that callback in the Meta dashboard.

Run the secret-free diagnostic in the staging App Service:

```console
php artisan staging:meta-readiness --json
```

The command reports presence and safety booleans only. It never prints configured values and never calls Meta.

## Staging-only values a human must supply

Store secret values in the staging Key Vault/App Service configuration. Do not commit them.

| Setting | Action / value to retrieve | Why it is needed |
|---|---|---|
| `META_APP_ID` | Reuse the approved SayaraForce app ID if Meta supports additive staging configuration | Embedded Signup SDK identity |
| `META_APP_SECRET` | Retrieve in Meta App Settings and store only as a staging secret | Webhook signature verification and code exchange |
| `META_WHATSAPP_BUSINESS_APP_CONFIG_ID` | Create/select the coexistence Business App onboarding configuration | WhatsApp Business App coexistence flow |
| `META_WHATSAPP_CLOUD_API_CONFIG_ID` | Create/select the dedicated Cloud API configuration | Dedicated test-number flow |
| `META_WHATSAPP_VERIFY_TOKEN` | Generate a new staging-only random token | Initial webhook callback verification |
| `META_GRAPH_API_VERSION` | Keep the reviewed supported value (`v25.0` in this release) | Graph API request version |
| `META_WHATSAPP_WABA_CALLBACK_OVERRIDE_ENABLED` | Set `true` on staging only | Requires per-WABA callback isolation |
| `META_WHATSAPP_WEBHOOK_CALLBACK_URL` | Set exactly `https://staging.sayaraforce.com/api/v1/webhooks/meta/whatsapp` | Callback applied and verified for the staging WABA |
| `STAGING_META_ALLOWED_WABA_IDS` | Add only the approved test WABA IDs | Fail-closed inbound/provisioning routing |
| `STAGING_META_ALLOWED_PHONE_NUMBER_IDS` | Add only approved test phone-number IDs | Fail-closed inbound/provisioning routing |
| `STAGING_META_PRODUCTION_WABA_ID_DENYLIST` | Add the known production WABA IDs without logging them | Explicit production-asset refusal |
| `STAGING_META_PRODUCTION_PHONE_NUMBER_ID_DENYLIST` | Add the known production phone-number IDs without logging them | Explicit production-asset refusal |
| `STAGING_MESSAGE_RECIPIENT_ALLOWLIST` | Add only human-approved test recipients | Required before a separately approved outbound test |

`STAGING_ALLOW_LEGACY_COMPANY_RESOLUTION` must remain `false`, `STAGING_WHATSAPP_OUTBOUND_ENABLED` must remain `false`, and `STAGING_SMS_OUTBOUND_ENABLED` must remain `false` during configuration and inbound-only UAT.

Suggested staging Key Vault secret names and consumers:

| Key Vault secret | App Service setting |
|---|---|
| `meta-app-id` | `META_APP_ID` |
| `meta-app-secret` | `META_APP_SECRET` |
| `meta-whatsapp-business-app-config-id` | `META_WHATSAPP_BUSINESS_APP_CONFIG_ID` |
| `meta-whatsapp-cloud-api-config-id` | `META_WHATSAPP_CLOUD_API_CONFIG_ID` |
| `meta-webhook-verification-token` (already provisioned) | `META_WHATSAPP_VERIFY_TOKEN` and compatibility alias `META_VERIFY_TOKEN` |
| `meta-staging-allowed-waba-ids` | `STAGING_META_ALLOWED_WABA_IDS` |
| `meta-staging-allowed-phone-number-ids` | `STAGING_META_ALLOWED_PHONE_NUMBER_IDS` |
| `meta-production-waba-id-denylist` | `STAGING_META_PRODUCTION_WABA_ID_DENYLIST` |
| `meta-production-phone-number-id-denylist` | `STAGING_META_PRODUCTION_PHONE_NUMBER_ID_DENYLIST` |

App/config IDs and provider IDs are not authorization credentials, but Key Vault references keep provider identifiers out of ordinary configuration listings. `META_WHATSAPP_SYSTEM_USER_ID` and `META_WHATSAPP_SYSTEM_USER_ACCESS_TOKEN` are optional and must be added only if the reviewed Tech Provider setup requires configured system-user assignment.

## Exact staging endpoints

| Purpose | Method | URL | Route | Protection |
|---|---|---|---|---|
| Owner onboarding | GET | `https://staging.sayaraforce.com/admin/messaging/whatsapp` | `admin.messaging.whatsapp.index` | authenticated active admin; `whatsapp_connect` entitlement |
| Add pending number | POST | `https://staging.sayaraforce.com/admin/messaging/whatsapp/number` | `admin.messaging.whatsapp.number.store` | web CSRF, recent 2FA/password step-up, rate limit |
| Start Embedded Signup | POST | `https://staging.sayaraforce.com/admin/messaging/whatsapp/onboarding/session` | `admin.messaging.whatsapp.start` | web CSRF, recent step-up, rate limit |
| Complete Embedded Signup | POST | `https://staging.sayaraforce.com/admin/messaging/whatsapp/onboarding/complete` | `admin.messaging.whatsapp.complete` | web CSRF, recent step-up, signed state/nonce, rate limit |
| Verify webhook | GET | `https://staging.sayaraforce.com/api/v1/webhooks/meta/whatsapp` | `api.webhooks.meta.whatsapp.verify` | public challenge; exact staging verify token |
| Receive webhook | POST | `https://staging.sayaraforce.com/api/v1/webhooks/meta/whatsapp` | `api.webhooks.meta.whatsapp.handle` | stateless/no CSRF; mandatory `X-Hub-Signature-256` HMAC |

The completion endpoint is an application POST performed after the popup SDK supplies the authorization code and session event. It is not an OAuth GET redirect target.

## Additive Meta dashboard configuration

Use the existing approved app only if the following can be added without replacing a production entry:

- Allowed domain: `staging.sayaraforce.com`
- Add `staging.sayaraforce.com` as an allowed app/JavaScript SDK domain where the existing app allows additive domains.
- Create a dedicated Embedded Signup configuration for **WhatsApp Business App onboarding/coexistence** and record its configuration ID.
- Create a separate Embedded Signup configuration for **dedicated Cloud API** onboarding and record its configuration ID.
- Do **not** replace the app-level production webhook callback. SayaraForce applies `https://staging.sayaraforce.com/api/v1/webhooks/meta/whatsapp` as a per-WABA callback override when subscribing the approved test WABA.
- Ensure the app's `whatsapp_business_account` webhook fields include `messages`; coexistence additionally requires `history`, `smb_app_state_sync`, and `smb_message_echoes`. Adding an app-level field may change which event types existing subscribed production WABAs emit, so review that effect and obtain explicit production approval before saving if the field is not already enabled.
- OAuth/redirect domain: `https://staging.sayaraforce.com`

If a domain, configuration, or webhook-field change would replace a production value rather than add a separate entry, stop. Record the exact dashboard screen and request explicit approval; do not save.

## Controlled live UAT sequence

1. Confirm the readiness diagnostic has `live_uat_configuration_ready=true`, the WABA callback override checks are true, and `outbound_test_authorized=false`.
2. Confirm the test WABA, test phone, and test recipient do not belong to production or Smart Matrix.
3. Complete coexistence Embedded Signup with the test garage and human-owned OTP/QR action.
4. Verify the signed inbound callback creates an idempotent webhook event and raw message before enrichment.
5. Verify conversation, customer, and basic lead creation; a greeting must not create an opportunity or booking.
6. Qualify the synthetic service need and verify one opportunity is created.
7. Confirm a date/time and verify one booking plus follow-up intent.
8. Verify replay and duplicate delivery do not duplicate CRM records or AI usage.
9. Keep outbound disabled. A separate explicit authorization is required before allowing one approved-recipient reply.

## Synthetic coverage already required

The staging suite covers valid and invalid signatures, unknown/denied assets, idempotent webhook replay, raw persistence, conversation/customer/lead resolution, AI quota behavior, qualification boundaries, and no unintended outbound. These tests use synthetic identifiers and HTTP fakes only.
