# Production Configuration Checklist

Use this checklist before deploying or after any significant configuration change.

---

## PHP and Extensions

- [ ] PHP 8.2 or higher
- [ ] `ext-pdo`, `ext-sqlite3` or MySQL/PostgreSQL driver
- [ ] `ext-imagick` (ImageMagick — required for image conversions)
- [ ] `ext-gd` (optional fallback)
- [ ] `ext-mbstring`, `ext-ctype`, `ext-tokenizer`

---

## Web Server

- [ ] Document root points to `/public`
- [ ] `.htaccess` or nginx rewrite rules configured to route all requests to `index.php`
- [ ] HTTPS configured
- [ ] Upload size limits in web server match application limits:
  - Nginx: `client_max_body_size`
  - Apache: `LimitRequestBody`

---

## Application

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` is set (run `php artisan key:generate` if empty)
- [ ] `APP_URL` matches the actual domain with scheme

---

## Database

- [ ] Database is created and accessible
- [ ] `DB_*` variables configured
- [ ] Migrations run: `php artisan migrate --force`

---

## Cache / Session / Queue

- [ ] `QUEUE_CONNECTION=database` (or Redis for production scale)
- [ ] `SESSION_DRIVER=file` or `database`
- [ ] `CACHE_STORE=file` or `database` or `redis`

---

## Queue Worker

- [ ] Queue worker process running (Supervisor or equivalent)
- [ ] See `docs/deployment/queue-worker.md`

---

## Scheduler

- [ ] Cron entry added for `php artisan schedule:run`
- [ ] See `docs/deployment/scheduler.md`

---

## Storage

- [ ] `storage/` and `bootstrap/cache/` are writable by the web server user
- [ ] Storage symlink created: `php artisan storage:link`
- [ ] Max PHP upload size configured: `upload_max_filesize`, `post_max_size`
- [ ] See `docs/deployment/storage-and-permissions.md`

---

## Stripe / Cashier

- [ ] `STRIPE_KEY` (publishable key) set
- [ ] `STRIPE_SECRET` (secret key) set
- [ ] `STRIPE_WEBHOOK_SECRET` set from Stripe dashboard webhook settings
- [ ] `CASHIER_CURRENCY` set (e.g., `eur` or `usd`)
- [ ] Stripe product/price IDs set for Pro, Max plans and credit packs
- [ ] Webhook endpoint registered in Stripe: `https://yourdomain.com/stripe/webhook`
- [ ] See `docs/billing/stripe-webhooks.md`

---

## File Converter Settings

- [ ] `CONVERTER_DEFAULT_RETENTION_DAYS` set (default: 1)
- [ ] Verify ImageMagick can convert PNG/JPG: `php artisan tinker` → `new \Imagick()`

---

## Security

- [ ] HTTPS enforced (redirect HTTP → HTTPS)
- [ ] `.env` file not publicly accessible
- [ ] `storage/` not directly accessible unless intentionally public
- [ ] Default `demo@example.com` credentials changed or seeder not run in production

---

## Verification Commands

```bash
php artisan config:clear
php artisan route:list
php artisan schedule:list
php artisan migrate:status
php artisan queue:failed
```
