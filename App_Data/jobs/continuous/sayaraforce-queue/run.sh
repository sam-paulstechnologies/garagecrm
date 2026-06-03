#!/bin/bash
cd /home/site/wwwroot

echo "SayaraForce queue worker started at $(date)"

php artisan queue:work database \
  --queue=default \
  --sleep=3 \
  --tries=3 \
  --timeout=90 \
  --memory=256