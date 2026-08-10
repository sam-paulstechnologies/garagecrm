# SayaraForce staging Meta/WhatsApp UAT handoff

## Current boundary

The engineering path is implemented and can be exercised with signed synthetic fixtures. No live Meta application, callback, WABA, phone number, recipient, credential, or outbound switch is changed by this repository process. Production Meta configuration remains untouched.

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
| `STAGING_META_ALLOWED_WABA_IDS` | Add only the approved test WABA IDs | Fail-closed inbound/provisioning routing |
| `STAGING_META_ALLOWED_PHONE_NUMBER_IDS` | Add only approved test phone-number IDs | Fail-closed inbound/provisioning routing |
| `STAGING_META_PRODUCTION_WABA_ID_DENYLIST` | Add the known production WABA IDs without logging them | Explicit production-asset refusal |
| `STAGING_META_PRODUCTION_PHONE_NUMBER_ID_DENYLIST` | Add the known production phone-number IDs without logging them | Explicit production-asset refusal |
| `STAGING_MESSAGE_RECIPIENT_ALLOWLIST` | Add only human-approved test recipients | Required before a separately approved outbound test |

`STAGING_ALLOW_LEGACY_COMPANY_RESOLUTION` must remain `false`, `STAGING_WHATSAPP_OUTBOUND_ENABLED` must remain `false`, and `STAGING_SMS_OUTBOUND_ENABLED` must remain `false` during configuration and inbound-only UAT.

## Additive Meta dashboard configuration

Use the existing approved app only if the following can be added without replacing a production entry:

- Allowed domain: `staging.sayaraforce.com`
- Webhook callback: `https://staging.sayaraforce.com/api/v1/webhooks/meta/whatsapp`
- Embedded Signup return page: `https://staging.sayaraforce.com/admin/whatsapp/connect`
- OAuth/redirect domain: `https://staging.sayaraforce.com`
- Webhook fields: the WhatsApp fields already approved for the application

If Meta presents only a single callback/configuration entry and saving would replace production, stop. Record the exact dashboard screen and request explicit approval; do not save.

## Controlled live UAT sequence

1. Confirm the readiness diagnostic has `live_uat_configuration_ready=true` while `outbound_test_authorized=false`.
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
