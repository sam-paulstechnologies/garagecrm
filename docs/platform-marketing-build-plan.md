# Platform Marketing Build Plan

## Implemented Foundation

- Super Admin-only marketing route group.
- Isolated platform marketing tables and models.
- Prospect manager with duplicate-safe phone normalization.
- Segments for controlled recipient groups.
- Campaign lifecycle with prepare, approve, queue launch, pause, and stop actions.
- Consent and suppression enforcement before recipient preparation.
- Platform WhatsApp channel setup with hidden credentials.
- Platform webhook router with garage fallback.
- Platform AI sales agent with deterministic fallback and usage logging.
- Platform demo appointments and reporting pages.
- Azure queue WebJob update for platform queues.

## External Configuration Needed

- Meta platform channel access token.
- Verify token if Meta webhook verification is configured for the platform channel.
- Approved WhatsApp campaign templates for the PaulsTechnologies WABA.
- A safe UAT recipient before launching live sends.

## Next Hardening Steps

- Add full CSV/XLSX mapping preview UI.
- Add live Meta template sync.
- Add public signed demo booking route.
- Add richer follow-up rule builder.
- Expand audit logs around every campaign state transition.
