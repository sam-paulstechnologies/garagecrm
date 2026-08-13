#!/usr/bin/env bash
set -Eeuo pipefail

cd /home/site/wwwroot

if [[ "${APP_ENV:-}" != "staging" || "${WEBSITE_SITE_NAME:-}" != "app-sayaraforce-staging" ]]; then
  echo "Refused: staging configuration-cache job identity check failed." >&2
  exit 40
fi

case "${TWO_FACTOR_ENFORCEMENT:-off}" in
  off|audit|required_admins) ;;
  *)
    echo "Refused: invalid two-factor enforcement mode." >&2
    exit 41
    ;;
esac

runtime_directories=(
  storage/framework/cache/data
  storage/framework/sessions
  storage/framework/views
  storage/logs
  bootstrap/cache
)
mkdir -p "${runtime_directories[@]}"
chmod -R ug+rwX storage bootstrap/cache

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart || true

echo "Staging Laravel caches rebuilt inside the guarded application runtime."
