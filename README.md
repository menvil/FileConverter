# File Converter

A Laravel-based MVP application for converting image files between formats via a web UI and REST API.

---

## MVP Scope

File Converter v0.1.0 supports:

- User registration, login, and credit-based billing
- Upload PNG or JPG files via the dashboard
- Convert to JPG, PNG, WEBP, or PDF
- Download converted results
- View conversion history
- Subscription plans (Free, Pro, Max) via Stripe/Cashier
- Credit packs purchasable via Stripe
- REST API for programmatic access (Pro/Max plans)

**Not included in MVP:** video/audio, office documents, OCR, batch conversion, WebSockets.

See `docs/release/supported-conversions.md` for the full conversion matrix.

---

## Requirements

- PHP 8.3+
- `ext-imagick` (ImageMagick)
- Node.js 18+
- Composer 2+
- SQLite (default) or MySQL/PostgreSQL

---

## Installation

```bash
# 1. Clone and install dependencies
git clone <repo-url> file-converter
cd file-converter
composer install
npm install

# 2. Configure environment
cp .env.example .env
php artisan key:generate

# 3. Run migrations and seed demo data
php artisan migrate --seed

# 4. Build frontend assets
npm run build

# 5. Create storage symlink
php artisan storage:link
```

---

## Running Locally

```bash
# Start the dev server
php artisan serve

# In a separate terminal — process queued conversion jobs
php artisan queue:work

# Frontend hot-reload (optional)
npm run dev
```

Visit `http://localhost:8000` and log in with the demo account:

```text
Email:    demo@example.com
Password: password
```

---

## Running Tests

```bash
composer test
```

---

## Linting

```bash
composer lint

# Auto-fix
composer format
```

---

## Queue Worker

Conversion jobs run asynchronously. A queue worker must be running:

```bash
php artisan queue:work
```

For production, use Supervisor. See `docs/deployment/queue-worker.md`.

---

## Scheduler

File cleanup runs daily via the Laravel scheduler:

```bash
# Add to crontab
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

See `docs/deployment/scheduler.md`.

---

## Billing / Stripe

Billing requires Stripe keys in `.env`:

```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

For local testing with webhooks:

```bash
stripe listen --forward-to http://localhost:8000/stripe/webhook
```

See `docs/billing/stripe-webhooks.md`.

---

## API

API access is available on Pro and Max plans. Authenticate with a Bearer token.

API docs are available at `/docs/api` after starting the server.

See `docs/release/supported-conversions.md` for supported conversion pairs.

---

## Documentation

| Document | Description |
|----------|-------------|
| `docs/release/mvp-scope-audit.md` | Full MVP feature checklist |
| `docs/release/supported-conversions.md` | Supported conversion matrix |
| `docs/deployment/production-checklist.md` | Production deployment checklist |
| `docs/deployment/queue-worker.md` | Queue worker setup |
| `docs/deployment/scheduler.md` | Scheduler / cron setup |
| `docs/deployment/storage-and-permissions.md` | Storage and file permissions |
| `docs/billing/stripe-webhooks.md` | Stripe webhook setup |
| `docs/development/local-development.md` | Local development guide |

---

## License

MIT
