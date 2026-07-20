#!/usr/bin/env bash
set -euo pipefail

cd /home/site/wwwroot

echo "SayaraForce queue worker starting at $(date)"
echo "Connection: database"
echo "Queues: default,notifications"

php artisan queue:restart || true

exec php artisan queue:work database \
  --queue=default,notifications \
  --sleep=3 \
  --tries=3 \
  --timeout=90 \
  --memory=256
