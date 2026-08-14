# Backup and restore runbook

Current staging MySQL Flexible Server backup retention is seven days; geo-redundancy and HA are disabled. Confirm with read-only Azure inspection before each exercise.

## Guardrails

- Target subscription/tenant must match the approved environment.
- Restore target name and resource group must contain `staging` and must not equal production server/database/app/RG.
- Never expose connection values or use production data for staging tests.
- A restore is created as a new isolated target; never overwrite the source.

## Drill

1. Record source resource ID (sanitised), backup/restore point, operator, approval and expected RPO/RTO.
2. Confirm source health and latest restorable time read-only.
3. Create a new staging-only point-in-time restore server using approved Azure command/IaC and equivalent private-network settings.
4. Validate target resource ID before any database connection.
5. Connect with a restricted staging identity; verify MySQL version, migrations/schema fingerprint, FK/view integrity and synthetic-only classification. Do not sample operational rows unnecessarily.
6. Boot an isolated validation application or run safe schema/health checks. Record actual restore/recovery time and errors.
7. Delete the temporary restore only after reviewer approval and evidence capture, using the exact verified staging resource ID.
8. Record pass/fail, observed RPO/RTO, checksum/fingerprint and treatment actions.

## Production preparation

Choose production retention, geo-redundancy/HA and RPO/RTO based on business impact and legal needs. A staging drill does not prove production recovery. Production restore requires explicit approval, traffic/data-integrity plan and rollback.
