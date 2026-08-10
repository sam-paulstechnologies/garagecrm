# SayaraForce commercial production promotion runbook

## Status and authority

This is preparation only. Nothing in this runbook was executed against production. Production promotion requires a reviewed staging rehearsal, database backup evidence, an approved commit, explicit human GO, and a separately authorized maintenance window.

## Preflight GO/NO-GO

GO requires all of the following:

- exact staging commit, schema fingerprint, CI run and staging verifier are green;
- Phase 1–11 master report and migration list are reviewed;
- every existing tenant contract has an approved canonical plan or immutable grandfathered entitlement snapshot;
- payment provider live account/KYC, price IDs and webhook endpoint are separately approved;
- Meta and outbound messaging changes are excluded unless separately approved;
- production database backup/restore point is verified and rollback owner is named;
- production deployment identity is distinct from staging and the approved package is immutable.

Any missing item is NO-GO.

## Legacy contract shadow evaluation

The inventory command is read-only and aggregate-only by default:

```console
php artisan commercial:plan-legacy-migration --json
```

It performs no assignment. Numeric company IDs may be included only in an explicitly approved, access-controlled review using `--include-company-ids`; names, email addresses, phone numbers, credentials and provider identifiers are never output.

For each legacy tenant, a human must:

1. record the signed/current commercial contract and active modules;
2. compare current server-side behavior with the proposed canonical plan in shadow mode;
3. choose an explicit canonical plan only when it preserves approved functionality;
4. otherwise approve an immutable `legacy_grandfathered` capability snapshot;
5. record approver, reason and effective date outside application secrets;
6. validate tenant, background-job and API decisions before enforcement.

Never infer commercial meaning from a database ID. Old Starter/Growth/Pro labels are not automatically equivalent to the new catalogue.

## Approved future execution order

The following commands are examples for the later authorized window; do not run them now.

1. Disable the production deployment pipeline except for the approved identity and record current app/database health.
2. Create and verify the Azure MySQL point-in-time restore/backup marker.
3. Capture the current production commit, migration ledger and schema fingerprint without application rows.
4. Deploy the exact staging-approved immutable package without a slot swap.
5. Put Laravel into maintenance mode only if the reviewed migrations require it.
6. Run `php artisan migrate --force` once from the approved package.
7. Run the separately reviewed tenant migration approval import; never use an automatic name/ID mapping.
8. Run `php artisan optimize:clear`, then `php artisan config:cache`, `php artisan route:cache`, and `php artisan view:cache`.
9. Restart only the production queue worker after schema/application verification; enable no new scheduler task by default.
10. Bring the application out of maintenance and run health, login, tenant-role, entitlement, queue and billing-read-only checks.

## Rollback decision

- Application defect before a write migration: redeploy the prior package and rebuild caches.
- Additive-schema defect: redeploy prior code and retain additive tables unless the reviewed rollback migration is explicitly approved.
- Incorrect tenant entitlements: stop enforcement, restore the captured entitlement snapshot/history, and keep provider billing events immutable.
- Destructive/data defect: stop traffic and perform the pre-approved point-in-time restore; do not improvise a reverse migration.

Rollback never uses a slot swap and never copies staging data into production.

## Verification record required after any future promotion

Record the deployed commit, migration status, schema fingerprint, five canonical catalogue versions/prices, tenant mapping count, unmatched tenant count, subscription state totals, failed provider event count, queue health, scheduler state, health/login results, and confirmation that Meta/outbound settings were unchanged unless separately authorized.
