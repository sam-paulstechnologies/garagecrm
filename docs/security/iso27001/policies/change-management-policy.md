# Change management policy

Every material change records purpose, risk, scope/environment, owner, reviewer, affected assets/data, tests/scans, migration/rollback, deployment SHA and verification.

- Normal: reviewed branch → green CI → staging deployment/UAT → explicit production approval.
- Emergency: incident lead authorises minimum reversible containment; evidence and retrospective review follow within two business days.
- Database changes are additive where possible, target-guarded, backed up and tested on a disposable/restored database.
- Configuration/secrets change through approved Azure/Key Vault mechanisms. Raw SSH config caching is prohibited where Key Vault/App Settings may not resolve; use the guarded runtime.
- Production slots/swaps, staging credentials, data, queues, storage, callbacks and identities never substitute for production approval.
- Failed verification triggers rollback/disablement while preserving forensic evidence.

The implementer must not be sole approver for a high-risk production change.
