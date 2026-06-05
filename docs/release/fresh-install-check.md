# Fresh Install Check

Run this after cloning the repository or before creating a release branch to verify the project bootstraps correctly from scratch.

---

## Commands

```bash
php artisan migrate:fresh --seed
php artisan test
```

---

## Expected Results

After `migrate:fresh --seed`:

- All migrations complete without errors
- `DemoUserSeeder` runs: creates `demo@example.com` with 100 credits
- `DemoConversionSeeder` runs: creates 3 sample conversion jobs for the demo user
- No migration or seeder errors

After `php artisan test`:

- All tests pass
- No critical failures

---

## Verification on 2026-06-05

| Command | Result |
|---------|--------|
| `php artisan migrate:fresh --seed` | ✅ Pass — migrations and seeders ran without errors |
| `php artisan test` | ✅ Pass — 767 tests, 766 passed, 1 skipped |
| `composer lint` | ✅ Pass |
| `npm run build` | ✅ Pass |

Git commit: post-CONV-426 (develop branch)

---

## Notes

- The fresh install uses SQLite by default (`DB_CONNECTION=sqlite`)
- `QUEUE_CONNECTION=database` is the default; queue worker not required for seeds
- Stripe keys are not required for the fresh install to complete
