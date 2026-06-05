# Scheduler

The Laravel scheduler runs cleanup tasks automatically. Currently scheduled:

- **Daily:** `conversions:cleanup-expired` — marks and deletes expired uploaded and result files based on per-plan retention policy.

---

## Why the Scheduler Is Required

Without the scheduler, expired files accumulate in storage and the database indefinitely. The cleanup command enforces the retention policy defined in `config/feature-access.php` per plan.

---

## Local Testing

Run the scheduler manually to test it:

```bash
# List all scheduled commands
php artisan schedule:list

# Run the scheduler once (executes all due commands)
php artisan schedule:run

# Run the cleanup command directly
php artisan conversions:cleanup-expired
```

---

## Production (Cron)

Add a single cron entry to your server:

```
* * * * * cd /var/www/file-converter && php artisan schedule:run >> /dev/null 2>&1
```

This runs every minute. The scheduler itself decides which commands are due based on their schedule configuration.

---

## Verify the Scheduler Runs

```bash
php artisan schedule:list
```

Expected output includes:

```
0 2 * * *  conversions:cleanup-expired
```

---

## Logging Notes

The cleanup command logs to the Laravel default log channel. Check `storage/logs/laravel.log` for output. The log includes how many files were marked expired and how many were deleted.
