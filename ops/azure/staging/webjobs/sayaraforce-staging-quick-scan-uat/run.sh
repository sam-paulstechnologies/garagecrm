#!/usr/bin/env bash
set -Eeuo pipefail

cd /home/site/wwwroot

if [[ "${APP_ENV:-}" != "staging" || "${WEBSITE_SITE_NAME:-}" != "app-sayaraforce-staging" ]]; then
  echo "Refused: Quick Scan UAT identity check failed." >&2
  exit 40
fi

php artisan staging:assert-safe --require-schema --no-interaction
php artisan staging:quick-scan-uat --confirm --no-interaction
