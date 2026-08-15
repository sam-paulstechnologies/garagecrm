#!/usr/bin/env bash
set -Eeuo pipefail

cd /home/site/wwwroot

# Fable M1/M2: isolated security/retention maintenance. Refuses to run outside
# the staging App Service identity, and invokes ONLY security:run-maintenance
# (billing grace enforcement + Quick Scan retention purge) — never the general
# outbound-capable scheduler.
if [[ "${APP_ENV:-}" != "staging" || "${WEBSITE_SITE_NAME:-}" != "app-sayaraforce-staging" ]]; then
  echo "Refused: security maintenance identity check failed." >&2
  exit 40
fi

php artisan security:run-maintenance --no-interaction
