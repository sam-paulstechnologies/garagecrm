# Disaster recovery plan

## Objectives (provisional)

Staging engineering target: RPO ≤24 hours, RTO ≤8 hours. These are planning targets, not proven service commitments. Production objectives require business approval and restore evidence.

## Scenarios

- App/package failure: disable traffic/checkout/outbound as needed, deploy last verified SHA, guarded cache rebuild, health/smoke/schema check.
- Database corruption/loss: freeze writes, identify clean restore point, restore to new server, verify schema/data integrity, reconfigure only after approval.
- Secret compromise: disable dependent feature, revoke/rotate in provider/Key Vault, update reference, guarded cache, verify, investigate.
- Regional Azure outage: assess provider status; production failover architecture is not yet implemented. Invoke business continuity and communicate.
- Supplier outage: fail closed for writes/actions, preserve inbound evidence where safe, retry idempotently, avoid duplicate payments/messages.
- Tenant/security breach: isolate tenant/credential without deleting evidence; follow incident plan.

## Recovery order

Identity/Key Vault/network → database/storage → application → queue → read-only health → controlled write tests → provider webhooks → outbound/scheduler only with separate approval.

Every recovery records decision authority, exact resource/SHA/config, integrity proof, monitoring and customer/legal communications decision. Exercise semi-annually and after major architecture changes.
