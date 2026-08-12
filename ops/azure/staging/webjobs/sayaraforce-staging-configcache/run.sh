#!/usr/bin/env bash
set -Eeuo pipefail

cd /home/site/wwwroot

if [[ "${APP_ENV:-}" != "staging" || "${WEBSITE_SITE_NAME:-}" != "app-sayaraforce-staging" ]]; then
  echo "Refused: staging config-cache identity check failed." >&2
  exit 40
fi

php artisan config:clear
php artisan config:cache

echo "Staging Laravel configuration cached for deployment ${DEPLOYED_COMMIT:-unknown}."
