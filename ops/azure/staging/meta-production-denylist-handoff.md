# Meta production asset denylist — operator configuration handoff (M8)

**Status: OPERATOR CONFIGURATION REQUIRED — not completed by code.**

The staging safety guard fails closed: while the production Meta denylists are
empty, every WhatsApp/Meta provider-asset validation and outbound path is hard
blocked (`StagingSafety::assertProviderAssetsAllowed` throws). This is safe, but
real Meta Coexistence staging UAT cannot proceed until the operator populates the
real production identifiers so staging can positively reject them.

The identifiers are production business configuration and are **not** stored in
this repository. Codex/Claude must not guess or fabricate them, and must not read
production to discover them. The approved operator must provide the exact values.

## Values the operator must supply

Set these staging App Service settings (and the matching `.env` keys) with the
REAL production identifiers, comma-separated if more than one:

| Setting | Meaning | Current state |
|---|---|---|
| `STAGING_META_PRODUCTION_WABA_ID_DENYLIST` | Production WhatsApp Business Account (WABA) ID(s) that staging must refuse | EMPTY — REQUIRED |
| `STAGING_META_PRODUCTION_PHONE_NUMBER_ID_DENYLIST` | Production Meta phone-number-ID(s) that staging must refuse | EMPTY — REQUIRED |
| `STAGING_PRODUCTION_DB_HOST_DENYLIST` | Production DB host(s) | POPULATED (verify still current) |

Separately, the approved staging TEST assets must be allowlisted before UAT:

| Setting | Meaning |
|---|---|
| `STAGING_META_ALLOWED_WABA_IDS` | Approved staging test WABA ID(s) |
| `STAGING_META_ALLOWED_PHONE_NUMBER_IDS` | Approved staging test phone-number-ID(s) |

## Where to find them (operator, not the agent)

- Meta Business Manager → WhatsApp Accounts → the production WABA (WABA ID), and
  the production phone number's phone-number-ID (WhatsApp Manager → Phone numbers).
- These are the production values that staging must never touch.

## Verify readiness after configuration

```bash
php artisan staging:meta-readiness
```

Expected after configuration:

- production asset denylist: **READY** (WABA + phone-number-ID populated)
- staging asset allowlist: separately controlled and populated
- unknown/production assets: **fail closed**

Complete this BEFORE real Coexistence staging UAT. Until then, the technical
control is implemented and fails closed; operational readiness is OPEN.
