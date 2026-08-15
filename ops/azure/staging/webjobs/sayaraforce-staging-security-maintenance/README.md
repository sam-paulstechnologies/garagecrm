# Isolated security / retention maintenance (Fable M1/M2)

This triggered webjob runs `php artisan security:run-maintenance`, which executes
ONLY:

- `billing:enforce-grace-periods` — suspends subscriptions whose failed-payment
  grace window has expired (M1).
- `quick-scan:enforce-retention` — physically purges Quick Scans past their
  permitted customer-data retention: due-but-unpurged (lost delayed job) and
  abandoned/expired scans that were never accepted (M2).

## Why a dedicated webjob (not the general scheduler)

The general Laravel scheduler is intentionally NOT deployed to staging: its tasks
(`bookings:send-reminders`, `journeys:tick`) can send outbound WhatsApp, and the
staging deploy pipeline explicitly refuses to package the scheduler webjob for
outbound safety. `security:run-maintenance` performs **no outbound messaging**,
so it is safe to run on its own cadence via this isolated job.

`run.sh` additionally refuses to run unless `APP_ENV=staging` AND
`WEBSITE_SITE_NAME=app-sayaraforce-staging`.

## OPERATIONAL ACTION REQUIRED

Staging currently sets `WEBJOBS_DISABLE_SCHEDULE=1`, which disables ALL scheduled
webjobs site-wide. To run this maintenance on the `settings.job` schedule
(every 2 hours), the operator must set `WEBJOBS_DISABLE_SCHEDULE=0` on the
**staging** App Service only.

This is safe because **no outbound scheduler webjob is packaged** — enabling
scheduled webjobs enables only this isolated, no-outbound maintenance job.

Alternatives if site-wide scheduled webjobs must stay disabled:
- Trigger the triggered webjob on demand / from CI post-deploy, or
- Invoke `security:run-maintenance` from an external scheduler (e.g. an Azure
  Logic App or a secured cron) — still no outbound.

Do NOT re-enable the general outbound scheduler in staging.
