# Fable security audit handoff

Fable may review this repository/staging baseline only within explicit written authority. This handoff does not assert Fable certification, compliance status or prior testing.

Review starting points:

- architecture/current state/gaps/risks/SoA/evidence in `docs/security/iso27001/`;
- Laravel middleware, auth/2FA, tenant scopes, provider gateways/webhooks, uploads and AI policy;
- `ops/security/` gates, staging Bicep/deploy/verify scripts and CI workflow;
- automated security, tenant, billing, Meta/WhatsApp, Quick Scan and AI tests;
- Azure staging resource/config/RBAC inventory and restore evidence supplied separately without secrets.

Fable should validate control design and operating effectiveness independently, sample evidence provenance, challenge residual risk, test management-system ownership/cadence and distinguish technical staging evidence from production/people/physical/legal controls.

Open dependencies to call out explicitly: independent VAPT, legal/privacy/DPA decisions, named-role approvals, access review, restore drill, network private-endpoint treatment and production rollout approval.

Share findings through the restricted incident/assurance channel. Do not put exploit details, customer data, provider payloads or secrets in normal tickets/chat. Each finding needs severity, evidence, affected control/risk, owner, due date and retest result.
