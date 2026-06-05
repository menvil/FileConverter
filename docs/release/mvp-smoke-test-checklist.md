# MVP Smoke Test Checklist

Use before each release to manually verify the core MVP flows.  
No knowledge of internal architecture required.

---

## Setup

1. Run `php artisan migrate:fresh --seed`
2. Start the server: `php artisan serve`
3. Start the queue worker: `php artisan queue:work`
4. Open `http://localhost:8000`

---

## Authentication

| # | Step | Expected Result | Pass/Fail | Notes |
|---|------|-----------------|-----------|-------|
| 1 | Register a new account at `/register` | Account created, redirected to dashboard | | |
| 2 | Log out | Redirected to login page | | |
| 3 | Log in as `demo@example.com` / `password` | Redirected to dashboard | | |
| 4 | Credit balance visible in user dropdown | Shows non-zero balance (100 demo credits) | | |

---

## Upload and Convert

| # | Step | Expected Result | Pass/Fail | Notes |
|---|------|-----------------|-----------|-------|
| 5 | Upload a PNG file | File accepted, format step shown with available targets | | |
| 6 | Select JPG as target format | Settings step shown with quality option | | |
| 7 | Set quality to "high" | Setting updates | | |
| 8 | Click Convert | Converting step shown, spinner visible | | |
| 9 | Wait for conversion to complete | Completed step shown with Download button | | |
| 10 | Click Download | File downloads with `.jpg` extension | | |
| 11 | Credit balance decremented after download | Balance reduced by 1 credit | | |
| 12 | History row appears on `/history` page | Shows completed PNG → JPG conversion | | |

---

## Error Handling

| # | Step | Expected Result | Pass/Fail | Notes |
|---|------|-----------------|-----------|-------|
| 13 | Try to upload a file larger than plan limit | Shows "This file is too large" message | | |
| 14 | Drain credits to zero, attempt conversion | Shows "not enough credits" message with billing CTA | | |
| 15 | Access a download for an expired result | Returns 410 / expired message | | |

---

## Billing (Stripe Test Mode Required)

| # | Step | Expected Result | Pass/Fail | Notes |
|---|------|-----------------|-----------|-------|
| 16 | Open `/billing` | Current plan, pricing cards, credit balance visible | | |
| 17 | Click "Upgrade to Pro" | Redirects to Stripe Checkout (test mode) | | Requires Stripe keys |
| 18 | Click "Buy 500 Credits" | Redirects to Stripe Checkout (test mode) | | Requires Stripe keys |

---

## API

| # | Step | Expected Result | Pass/Fail | Notes |
|---|------|-----------------|-----------|-------|
| 19 | Create an API key on billing page (Pro user) | API key shown once | | Requires Pro plan |
| 20 | GET `/api/v1/converters` with Bearer token | Returns JSON list of converters | | |
| 21 | POST `/api/v1/files/upload` with PNG file | Returns file resource with ID | | |
| 22 | POST `/api/v1/conversions` with file_id and target_format | Returns conversion job resource | | |
| 23 | GET `/api/v1/conversions/{id}` | Returns job status (completed) | | |
| 24 | GET `/api/v1/conversions/{id}/download` | Downloads result file | | |
| 25 | GET `/api/v1/credits` | Returns current balance | | |

---

## API Docs

| # | Step | Expected Result | Pass/Fail | Notes |
|---|------|-----------------|-----------|-------|
| 26 | Open `/api/docs` | Redoc UI loads with MVP endpoints listed | | |

---

## Cleanup

| # | Step | Expected Result | Pass/Fail | Notes |
|---|------|-----------------|-----------|-------|
| 27 | Run `php artisan conversions:cleanup-expired` | Expired files cleaned up, no errors | | |
| 28 | Run `php artisan schedule:list` | Shows cleanup command scheduled | | |

---

## Notes

_Add any observations or known issues discovered during this smoke test run here._
