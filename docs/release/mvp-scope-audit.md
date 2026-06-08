# MVP Scope Audit

Date: 2026-06-05  
Branch: develop  
Commit base: post-Phase-25 (CONV-398–414 complete)

---

## Authentication

**Implemented:** yes  
**Related tasks:** Phase 1–3 (Breeze scaffold)  
**Verification:** Register at `/register`, login at `/login`, logout works.  
**Known limitations:** Email verification not enforced in MVP.

---

## Upload Flow

**Implemented:** yes  
**Related tasks:** Phase 5–6 (file upload, storage)  
**Verification:** Upload PNG/JPG/WEBP/PDF on dashboard. File is stored in `uploads/{user_id}/`. Format is detected. Metadata (dimensions for images) is extracted.  
**Known limitations:** Supported formats limited to PNG, JPG, WEBP, PDF. No video/audio/office formats.

---

## Target Format Selection

**Implemented:** yes  
**Related tasks:** Phase 7–8 (converter registry, format step)  
**Verification:** After upload, available target formats appear. Unsupported pairs show "This conversion is not supported yet." message.  
**Known limitations:** Only image-to-image and image-to-PDF conversions are supported.

---

## Dynamic Settings

**Implemented:** yes  
**Related tasks:** Phase 9 (options schema, settings step)  
**Verification:** Quality option appears for PNG/JPG conversions. Settings are persisted per target format while navigating.  
**Known limitations:** Settings are simple per-pair options (quality only for image conversions).

---

## Image Conversion Drivers

**Implemented:** yes  
**Related tasks:** Phase 10–11 (Imagick drivers)  
**Verification:** PNG→JPG, PNG→WEBP, PNG→PDF, JPG→PNG, JPG→WEBP, JPG→PDF all work via Imagick.  
**Known limitations:** Imagick must be installed. No fallback driver. See `docs/release/supported-conversions.md`.

---

## Download

**Implemented:** yes  
**Related tasks:** Phase 12 (download controller)  
**Verification:** After conversion completes, Download button appears. File downloads with correct MIME type. Expired results return 410.  
**Known limitations:** Results expire after retention period (configurable via `CONVERTER_DEFAULT_RETENTION_DAYS`).

---

## Recent Conversions

**Implemented:** yes  
**Related tasks:** Phase 7 (history table)  
**Verification:** Dashboard shows recent conversion jobs. `/history` shows full history with status badges.  
**Known limitations:** No pagination on dashboard widget (shows most recent only).

---

## Credits

**Implemented:** yes  
**Related tasks:** Phase 14 (credit ledger), Phase 13 (feature access)  
**Verification:** Credits are granted on registration. Credits are spent on successful conversion. Balance shown in user dropdown. Credit history shown on billing page.  
**Known limitations:** Credit costs are configured in `config/conversion_costs.php`. No dynamic pricing.

---

## Cashier Subscriptions

**Implemented:** yes  
**Related tasks:** Phase 18 (billing page), Cashier integration  
**Verification:** Billing page shows current plan, pricing cards. Checkout redirects to Stripe. Webhooks update plan on subscription events.  
**Known limitations:** Requires Stripe keys and webhook setup. Test mode only unless production keys configured.

---

## Credit Packs

**Implemented:** yes  
**Related tasks:** Phase 18 (billing page), credit pack checkout  
**Verification:** Credit packs shown on billing page. Checkout works via Stripe. Credits granted on `checkout.session.completed` webhook.  
**Known limitations:** Same Stripe requirements as subscriptions.

---

## API

**Implemented:** yes  
**Related tasks:** Phase 19 (API foundation)  
**Verification:** API key creation on billing page. Authentication via Bearer token. Endpoints: converters list, file upload, conversion create/estimate/status/download, credit balance.  
**Known limitations:** API access requires Pro or Max plan. Free users are blocked at middleware.

---

## API Documentation

**Implemented:** yes  
**Related tasks:** Phase 20 (OpenAPI + Redoc)  
**Verification:** `/docs/api` shows Redoc UI. `/docs/api/openapi.yaml` returns OpenAPI 3.1 spec.  
**Known limitations:** Docs are static (generated from spec file). Not auto-generated from code.

---

## Cleanup / Retention

**Implemented:** yes  
**Related tasks:** Phase 13 (retention policy), Phase 16 (cleanup job)  
**Verification:** `php artisan files:cleanup-expired` marks and deletes expired files. Scheduler runs it hourly.  
**Known limitations:** Retention period is per-plan. Cleanup is not real-time.

---

## Rate Limiting

**Implemented:** yes  
**Related tasks:** Phase 19 (API rate limiting), Phase 25 CONV-410–413 (web + API rate limiting)  
**Verification:** `api-v1` limiter enforces per-plan limits. `web-upload` limiter: 20/min. `web-conversion-create` limiter: 30/min. API returns `rate_limited` JSON on 429.  
**Known limitations:** Web rate limit is registered but not enforced at Livewire action level in MVP (limiter is available but not wired to DashboardConverter actions). Rate limit enforcement on web is via named limiter keys, not middleware, and would need explicit `RateLimiter::hit()` calls in Livewire to be fully active.

---

## Error Handling

**Implemented:** yes  
**Related tasks:** Phase 25 CONV-398–407  
**Verification:** All domain errors have typed exceptions implementing `DomainExceptionContract`. API returns stable JSON error codes. UI shows readable messages via `UiDomainErrorMapper`.  
**Known limitations:** None in scope.

---

## Logging

**Implemented:** yes  
**Related tasks:** Phase 25 CONV-408–409  
**Verification:** Conversion lifecycle events logged (created, started, completed, failed). Credit events logged (granted, spent, refunded). No sensitive data in logs.  
**Known limitations:** Logs go to Laravel default log channel. No external log aggregation configured.

---

## Known Gaps

```txt
1. Web rate limiting not wired to Livewire action level (registered but not enforced inline).
2. Email verification not required on registration.
3. No admin dashboard.
4. No batch conversion.
5. No video/audio/office document conversion.
6. No OCR.
7. No WebSocket progress — polling only.
8. No Sentry/Bugsnag error monitoring.
9. No OpenTelemetry/Prometheus metrics.
10. API webhooks not implemented.
11. No Docker production setup.
```
