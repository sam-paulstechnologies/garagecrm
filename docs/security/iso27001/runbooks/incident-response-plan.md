# Incident response plan

## Severity

- SEV-1: confirmed/suspected restricted-data breach, cross-tenant access, privileged takeover, production destructive action, provider/payment compromise, broad outage.
- SEV-2: contained tenant/data issue, repeated signature/abuse attack, material control failure or significant degradation.
- SEV-3/4: low-impact anomaly, blocked attempt or routine defect.

## Response

1. **Report and open record:** timestamp, reporter, correlation/event/resource IDs, environment and observed facts. Do not copy secrets/customer rows.
2. **Triage:** incident lead confirms severity, scope, data/classification, tenant/supplier/regulatory implications and safe communications channel.
3. **Contain:** disable affected feature/credential/identity/worker, block asset/route or isolate resource. Preserve availability of unaffected tenants; no destructive cleanup.
4. **Preserve evidence:** snapshot relevant logs/config/version/access history with checksum, custody record and restricted access. Use read-only provider/cloud queries where possible.
5. **Eradicate/recover:** patch, rotate, restore/reconcile from verified evidence, run tests and heightened monitoring. Production action needs incident authority.
6. **Notify:** privacy/legal decides regulator/customer/supplier/insurer/law-enforcement obligations and timing. Engineering does not make legal notification decisions.
7. **Close/learn:** root cause, timeline, impact, controls, residual risk, actions/owners/dates and effectiveness review.

Contacts and alternates must be maintained outside this repository. Conduct a tabletop at least annually and after material architecture change. Never claim absence of impact before evidence supports it.
