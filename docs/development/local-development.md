# Local Development Guide

---

## Initial Setup

```bash
git clone <repo-url> file-converter
cd file-converter
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan storage:link
```

---

## Running The Dev Server

```bash
php artisan serve        # backend on http://localhost:8000
npm run dev              # Vite hot-reload (optional)
php artisan queue:work   # process conversion jobs
```

---

## Test Database

Tests use SQLite in-memory (`:memory:`) via `phpunit.xml` config. No separate test database setup is needed. The test database is isolated from the development database.

---

## Queue Configuration

For local development, `QUEUE_CONNECTION=database` dispatches jobs to the `jobs` table. The queue worker must be running (`php artisan queue:work`) for conversions to complete. To skip the worker entirely, change to `QUEUE_CONNECTION=sync` in `.env` — jobs will run synchronously inside the HTTP request.

---

## Billing Without Stripe

For local development that does not involve payment flows, you can skip Stripe configuration entirely. Leave `STRIPE_KEY`, `STRIPE_SECRET`, and `STRIPE_WEBHOOK_SECRET` empty. Billing page will still render; checkout will fail gracefully.

To test Stripe locally, use the Stripe CLI:

```bash
stripe listen --forward-to http://localhost:8000/stripe/webhook
```

---

## Running Tests

```bash
# All tests
composer test

# Single test file
php artisan test --filter=DashboardConverterTest

# Single test by name
php artisan test --filter="it completes the full mvp"

# With coverage (requires Xdebug or PCOV)
php artisan test --coverage
```

---

## Linting

```bash
# Check (no fix)
composer lint

# Auto-fix
composer format
```

---

## Branch Naming

```
feature/CONV-XXX-kebab-title
```

## Commit Format

```
CONV-XXX: Short description in imperative mood
```

---

## TDD Workflow

1. Create a branch: `git checkout -b feature/CONV-XXX-title`
2. Write a failing test in `tests/Unit/` or `tests/Feature/`
3. Run: `composer test tests/path/to/NewTest.php` — confirm it fails
4. Implement the minimum code to make it pass
5. Run: `composer test` — all tests must pass
6. Run: `composer lint` — fix any style issues
7. Commit: `git commit -m "CONV-XXX: Description"`
8. Merge to `develop`

---

## Key Directories

| Directory | Contents |
|-----------|----------|
| `app/Actions/` | Single-purpose action classes |
| `app/Exceptions/` | Domain exception contracts and classes |
| `app/Jobs/` | Queue jobs (conversion processing) |
| `app/Livewire/` | Livewire components (UI) |
| `app/Services/` | Service classes (feature access, billing) |
| `app/Support/` | Utilities (converters, logging, UI mapper) |
| `tests/Feature/` | Integration/feature tests |
| `tests/Unit/` | Unit tests |
| `tests/Fakes/` | Fake implementations for testing |
