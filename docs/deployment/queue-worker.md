# Queue Worker

Image conversions are processed asynchronously by queue jobs. A queue worker **must be running** for conversions to complete. Without a worker, jobs will sit in the `jobs` table and no conversions will happen.

---

## Local

```bash
php artisan queue:work
```

This processes jobs one by one, blocking the terminal. Stop with `Ctrl+C`.

For local development without a persistent worker, you can use the sync driver:

```env
QUEUE_CONNECTION=sync
```

Jobs will run inline during the HTTP request (not recommended for production).

---

## Production

Run the queue worker as a persistent background process managed by Supervisor.

### Supervisor Example

`/etc/supervisor/conf.d/file-converter-worker.conf`:

```ini
[program:file-converter-worker]
command=/usr/bin/php /var/www/file-converter/artisan queue:work --sleep=3 --tries=3 --timeout=90
directory=/var/www/file-converter
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/file-converter-worker.log
```

After creating the config:

```bash
supervisorctl reread
supervisorctl update
supervisorctl start file-converter-worker
```

---

## Restart After Deploy

After deploying new code, restart the worker so it picks up the changes:

```bash
php artisan queue:restart
```

Supervisor will automatically start a new worker process.

---

## Failed Jobs

```bash
# List failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# Retry a specific failed job by ID
php artisan queue:retry <id>

# Flush all failed jobs
php artisan queue:flush
```

---

## Connection Notes

- Default: `QUEUE_CONNECTION=database` — jobs stored in the `jobs` table
- For production scale, consider Redis: `QUEUE_CONNECTION=redis`
- Never use `sync` in production
