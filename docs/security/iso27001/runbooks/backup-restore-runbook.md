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

## Encrypted-field decryption evidence (GAP-021)

Database restore success is not application recovery success. Application-encrypted
fields (provider tokens, WhatsApp/history content, Quick Scan PII) are only usable
if the restored application can decrypt them with the restored `APP_KEY` / Key Vault
material. Capture this as explicit, witnessed evidence during every restore drill.

Run the read-only verification on the authorized staging network, connected to the
isolated restored staging database (never production). It prints only pass/fail
counts and byte lengths — never any decrypted value — and refuses to run against a
production-denylisted database host:

```bash
php artisan staging:verify-encrypted-recovery --sample=5 --json=storage/logs/restore-decryption-evidence.json
```

A `result: pass` requires `decrypt_failed = 0` and `ordinary_records_readable = true`.

### Evidence template (fill in and file as EV-009)

| Field | Value |
|---|---|
| Restore point (UTC) | |
| Backup timestamp (UTC) | |
| Restore duration (observed RTO) | |
| Restored DB identity (sanitised resource ID / db name) | |
| Schema fingerprint verified (`staging:schema-fingerprint --verify`) | pass / fail |
| Application boot / health check | pass / fail |
| Encrypted-field decryption (`staging:verify-encrypted-recovery`) | pass / fail (decrypted_ok / decrypt_failed) |
| APP_KEY fingerprint (first 12 hex of sha256) | |
| Ordinary CRM records readable | yes / no |
| Cleanup / isolation confirmed (temporary restore deleted) | yes / no |
| Operator / reviewer | |

Do not claim a contractual RTO/RPO from a single staging drill; record the observed
values only. Management must approve production RPO/RTO separately.
