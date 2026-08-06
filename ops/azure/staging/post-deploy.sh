#!/usr/bin/env bash
set -Eeuo pipefail

cd /home/site/wwwroot

if [[ "${APP_ENV:-}" != "staging" ]]; then
  echo "Refused: APP_ENV is not staging." >&2
  exit 40
fi

if [[ "${WEBSITE_SITE_NAME:-}" != "app-sayaraforce-staging" ]]; then
  echo "Refused: WEBSITE_SITE_NAME is not app-sayaraforce-staging." >&2
  exit 41
fi

php artisan staging:assert-safe --require-schema --no-interaction
php artisan migrate --force --no-interaction
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link || true
php artisan queue:restart || true

echo "Staging migration and Laravel cache rebuild completed."
