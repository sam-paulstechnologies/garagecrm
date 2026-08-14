#!/usr/bin/env bash
set -Eeuo pipefail

cd /home/site/wwwroot

if [[ "${APP_ENV:-}" != "staging" || "${WEBSITE_SITE_NAME:-}" != "app-sayaraforce-staging" ]]; then
  echo "Refused: WhatsApp history UAT identity check failed." >&2
  exit 40
fi

php artisan staging:assert-safe --require-schema --no-interaction
php artisan staging:whatsapp-history-uat --confirm --no-interaction
