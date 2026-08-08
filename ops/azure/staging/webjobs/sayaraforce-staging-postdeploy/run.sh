#!/usr/bin/env bash
set -Eeuo pipefail

cd /home/site/wwwroot

if [[ "${APP_ENV:-}" != "staging" || "${WEBSITE_SITE_NAME:-}" != "app-sayaraforce-staging" ]]; then
  echo "Refused: staging post-deployment job identity check failed." >&2
  exit 40
fi

exec bash ops/azure/staging/post-deploy.sh
