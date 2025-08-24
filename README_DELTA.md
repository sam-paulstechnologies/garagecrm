# GarageCRM Delta Update

This drop adds:
- **Missing tables** (companies, garages, vehicles, jobsheets/job_cards, invoices/invoice_items, communication_logs, files, plans)
- **WhatsApp + Payments** service stubs (`config/whatsapp.php`, `config/payments.php`, service classes)
- **DemoSeeder** for realistic demo data
- **.env.example** with required keys
- **Deploy kit** (Dockerfile, docker-compose, nginx.conf, supervisord.conf, deploy.sh)

## Apply (no-Git)
1. Back up your project.
2. Unzip these files into your project root (allow overwrite for configs if prompted).
3. Run:
   ```bash
   composer install --no-dev --optimize-autoloader
   cp .env.example .env  # if you don't have one yet, then update keys
   php artisan key:generate
   php artisan migrate --force
   php artisan db:seed --class=Database\\Seeders\\DemoSeeder
   php artisan storage:link
   php artisan config:cache route:cache view:cache
   ```

## Apply (with Git)
```bash
git apply --reject --whitespace=fix diff.patch
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=Database\Seeders\DemoSeeder
```

Audit trails:
- Generated: 2025-08-13T15:12:47
