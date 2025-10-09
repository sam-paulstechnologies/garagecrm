#!/usr/bin/env bash
set -euo pipefail

# 1) Go to the Laravel app root (where artisan lives)
cd /home/site/wwwroot

# 2) Quick diagnostics (go to WebJob Logs to see these)
echo "[scheduler] PWD=$(pwd)"
which php || true
php -v || true

echo "[scheduler] Artisan version:"
php artisan --version || true

# 3) Warm up caches (safe even if already cached)
echo "[scheduler] Running optimize (config/routes/views caches)"
php artisan optimize || true

# 4) Show the defined schedule once (sanity check)
echo "[scheduler] schedule:list:"
php artisan schedule:list || true

# 5) Start the long-running scheduler loop (verbose)
echo "[scheduler] Starting schedule:work (verbose)…"
exec php artisan schedule:work -v
