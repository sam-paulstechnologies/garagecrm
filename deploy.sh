#!/usr/bin/env bash
set -euo pipefail
echo ">>> GarageCRM deploy starting"
if [ -f composer.lock ]; then composer install --no-dev --optimize-autoloader; fi
if ! grep -q "APP_KEY=" .env 2>/dev/null; then php artisan key:generate; fi
php artisan storage:link || true
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart || true
echo ">>> Done."
