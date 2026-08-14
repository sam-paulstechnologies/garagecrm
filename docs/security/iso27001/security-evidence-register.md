# Security evidence register

| Evidence ID | Evidence | Source | Cadence | Owner | Retention |
|---|---|---|---|---|---|
| EV-001 | release commit, reviewed diff, branch target | Git | each release | Engineering | repository lifetime |
| EV-002 | full/focused test and assertion counts | CI artifact | each release | Engineering | 12 months |
| EV-003 | Composer/npm audit JSON | CI artifact | each build + weekly | Engineering | 12 months |
| EV-004 | CycloneDX SBOM | CI artifact | each release | Engineering | supported release + 2 years |
| EV-005 | secret scan report | CI artifact | each build | Security | 12 months |
| EV-006 | staging identity/health/schema verification | deployment log | each deployment | Operations | 12 months |
| EV-007 | Key Vault reference resolution and RBAC inventory | Azure diagnostic | quarterly/change | Operations | 2 years |
| EV-008 | access/role/2FA review | signed review record | quarterly | Security | 2 years |
| EV-009 | backup configuration and restore drill | Azure/runbook record | quarterly | Operations | 2 years |
| EV-010 | incident record/timeline/lessons | incident repository | per incident | Incident lead | policy/legal period |
| EV-011 | supplier/legal review | approved register/contracts | annually/change | Privacy/Legal | contract + required period |
| EV-012 | external VAPT report and remediation retest | restricted assurance store | before launch/annually | Security | 3 years |

Evidence must contain timestamps, environment, commit/resource IDs, operator/reviewer, result, exceptions and checksum where practical. It must not contain plaintext secrets, customer rows, full provider payloads or authentication material.
