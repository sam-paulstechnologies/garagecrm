#!/usr/bin/env bash
set -Eeuo pipefail

cd /home/site/wwwroot

# Fable M1/M2 (final): dedicated CONTINUOUS security/retention maintenance.
#
# Runs isolated security and retention maintenance only (billing grace
# enforcement + Quick Scan retention purge) on a fixed interval. A CONTINUOUS
# WebJob is NOT gated by WEBJOBS_DISABLE_SCHEDULE, so this maintenance runs
# automatically without enabling the general outbound-capable task runner, which
# stays unpackaged. It never sends any outbound customer communication.
if [[ "${APP_ENV:-}" != "staging" || "${WEBSITE_SITE_NAME:-}" != "app-sayaraforce-staging" ]]; then
  echo "Refused: security maintenance identity check failed." >&2
  exit 40
fi

interval="${SECURITY_MAINTENANCE_INTERVAL_SECONDS:-3600}"
case "$interval" in
  ''|*[!0-9]*) interval=3600 ;;
esac
if [[ "$interval" -lt 300 ]]; then
  interval=300
fi

echo "SayaraForce staging security-maintenance starting. environment=staging interval=${interval}s"

while true; do
  if ! php artisan security:run-maintenance --no-interaction; then
    echo "security:run-maintenance failed; will retry after the interval." >&2
  fi
  sleep "$interval"
done
