#!/usr/bin/env bash
set -Eeuo pipefail

cd /home/site/wwwroot

if [[ "${APP_ENV:-}" != "staging" || "${WEBSITE_SITE_NAME:-}" != "app-sayaraforce-staging" ]]; then
  echo "Refused: staging queue worker identity check failed." >&2
  exit 40
fi

echo "SayaraForce staging queue worker starting. environment=staging"
php artisan queue:restart || true
exec php artisan queue:work database \
  --queue=default,notifications \
  --sleep=3 \
  --tries=3 \
  --timeout=90 \
  --memory=256
