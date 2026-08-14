# Internal security review checklist

- [ ] Scope/assets/data flows/owners and change are current.
- [ ] Risk register/treatments/exceptions reviewed and overdue high risks escalated.
- [ ] Privileged, tenant admin, repository, Azure, supplier and service identities reviewed.
- [ ] Mandatory 2FA/step-up and recovery/emergency paths tested.
- [ ] Tenant-isolation, webhook/signature/replay, billing and outbound-safety suites pass.
- [ ] Secret scan, Composer/npm audits and SBOM pass; unsupported dependencies reviewed.
- [ ] Key Vault refs/RBAC/rotation and app configuration reviewed without exposing values.
- [ ] Logging redaction, retention, alerts and incident contacts tested.
- [ ] Backup success and restore drill evidence current; RPO/RTO accepted.
- [ ] Supplier/legal/privacy registers reviewed by accountable owners.
- [ ] Independent VAPT/certification findings tracked and retested.
- [ ] Release/deployment SHA, migration, rollback, health and production isolation evidenced.

Reviewer records date, scope, evidence IDs, exceptions/actions and signature/approval outside source if personal signatures are sensitive.
