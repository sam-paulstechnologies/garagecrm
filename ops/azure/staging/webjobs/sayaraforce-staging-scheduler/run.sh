#!/usr/bin/env bash
set -Eeuo pipefail

cd /home/site/wwwroot

if [[ "${APP_ENV:-}" != "staging" || "${WEBSITE_SITE_NAME:-}" != "app-sayaraforce-staging" ]]; then
  echo "Refused: staging scheduler identity check failed." >&2
  exit 40
fi

echo "SayaraForce staging scheduler starting. environment=staging"
exec php artisan schedule:work --no-interaction
