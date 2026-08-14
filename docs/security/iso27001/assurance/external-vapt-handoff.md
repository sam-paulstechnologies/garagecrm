# External VAPT handoff

This application has not been penetration tested by this programme. Engage an independent tester with written rules of engagement.

## Scope

- staging web/API, Laravel auth/Fortify/TOTP/recovery/step-up and session security;
- multi-tenant IDOR/mass assignment/role/impersonation/entitlement bypass;
- uploads/downloads, SSRF, XSS, CSRF, injection, rate limits and business logic;
- Stripe signed/idempotent billing using Sandbox only;
- Meta/WhatsApp signed webhook, asset controls and onboarding using approved test assets only;
- Quick Scan token/consent/purge and AI quota/action safety;
- Azure staging App Service, identity/RBAC, KV/storage/MySQL/network/telemetry configuration.

## Exclusions

Production, denial-of-service/load beyond limits, real customer data/messages, live payments, social engineering, destructive provider/cloud actions and supplier infrastructure unless separately authorised.

Provide synthetic tenants in at least two roles and one platform role, architecture/data flows, test windows, contacts and evidence channel. Require CVSS/context severity, reproducible safe evidence, affected tenant/data/control, remediation advice, confirmation no sensitive data retained, and retest. Critical findings trigger immediate stop/incident process.
