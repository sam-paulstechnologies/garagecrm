#!/usr/bin/env bash
set -euo pipefail

cd /home/site/wwwroot

echo "[$(date)] Meta import (ALL companies) starting..."
php artisan optimize || true
php artisan leads:import-meta --limit=50 -q
echo "[$(date)] Meta import completed."
