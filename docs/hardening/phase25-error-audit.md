# Phase 25 Error And Failure Handling Audit

Conducted: 2026-06-05  
Branch base: develop (bc57299)

---

## 1. Upload Validation

### Current behavior
Laravel validation in `DashboardConverter::storeUpload()` rejects files that exceed the plan's `maxFileSizeMb`. A custom message is returned via `validate()`.

### Problem
The validation error is a Livewire `ValidationException`, not a domain exception. There is no `FileTooLargeException` with structured `code` and `details`. The API upload endpoint uses `UploadFileRequest` validation which also produces plain validation errors without a stable domain code.

### Expected domain exception
`FileTooLargeException::forLimit(actualBytes, maxBytes)`

### Expected UI/API mapping
- UI: "This file is too large for your current plan."
- API: `{ "error": { "code": "file_too_large", ... } }` → 413

### Planned CONV task
CONV-401

---

## 2. File Format Detection

### Current behavior
`FileFormatDetector::detect()` throws `UnsupportedFileFormatException` (in `app/Exceptions/Files/`). `DashboardConverter` catches it and shows a hardcoded string.

### Problem
`UnsupportedFileFormatException` does not implement `DomainExceptionContract` — it has no `code()` or `details()` method. There is a separate `UnsupportedFormatException` in `app/Support/Converters/Exceptions/` used by `FileFormat::normalize()`. Two parallel classes for similar concepts, neither implements a contract.

### Expected domain exception
Consolidate under `DomainExceptionContract` with `code: unsupported_format`.

### Expected UI/API mapping
- UI: "This file type is not supported."
- API: `unsupported_format` → 422 (already mapped in `ApiExceptionMapper`)

### Planned CONV task
CONV-399 (contract), CONV-400 (exceptions)

---

## 3. Target Format Selection

### Current behavior
`DashboardConverter::selectTargetFormat()` checks the converter registry inline and sets `$targetFormatError` to a hardcoded string if no converter is found. No exception is thrown.

### Problem
The registry miss is handled silently with a string, not via a typed domain exception. The same logic in the API `ConversionController` throws `UnsupportedConversionException` but that class extends `RuntimeException` with no domain contract.

### Expected domain exception
`UnsupportedConversionException` implementing `DomainExceptionContract` with `code: unsupported_conversion`.

### Expected UI/API mapping
- UI: "This conversion is not supported yet."
- API: `unsupported_conversion` → 422 (already mapped in `ApiExceptionMapper`)

### Planned CONV task
CONV-399, CONV-400

---

## 4. Options Validation

### Current behavior
`OptionsValidator::validate()` throws `InvalidConverterOptionsException` which has `fieldErrors()` but does not implement `DomainExceptionContract`. `DashboardConverter::validateSettings()` catches it and loops `fieldErrors()` into the Livewire error bag. The API mapper returns `code: invalid_options` with field error details.

### Problem
`InvalidConverterOptionsException` only captures one field error at a time (single `optionKey`). `fieldErrors()` returns a map with one entry. Bulk validation errors are not expressible. No `DomainExceptionContract`.

### Expected domain exception
`InvalidConverterOptionsException` implementing `DomainExceptionContract` with `code: invalid_options` and `details: { errors: { field: message } }`.

### Expected UI/API mapping
- UI: Per-field validation errors (already works via `addError`)
- API: `invalid_options` → 422 with `details.errors`

### Planned CONV task
CONV-399, CONV-401

---

## 5. Conversion Job Creation

### Current behavior
`CreateConversionJobAction::handle()` throws:
- `UnsupportedConversionException` — if format normalization fails or converter not found
- `InsufficientCreditsException` — if balance is insufficient

Both are caught by `DashboardConverter::convert()` with inline messages. `InsufficientCreditsException` does not implement `DomainExceptionContract`.

### Problem
`InsufficientCreditsException::make()` carries required/available in the message string only, not in structured `details()`. No `DomainExceptionContract` interface.

### Expected domain exception
`InsufficientCreditsException` with `code: insufficient_credits`, `details: { required, available }`.

### Expected UI/API mapping
- UI: "You do not have enough credits." + billing CTA
- API: `insufficient_credits` → 402 (already mapped in `ApiExceptionMapper`)

### Planned CONV task
CONV-399, CONV-402

---

## 6. Conversion Processing

### Current behavior
`ProcessConversionJob::handle()` catches any `Throwable` and stores `error_code = Str::snake(class_basename($exception))` in the job row. It calls `report($exception)`. No typed `ConversionFailedException` is thrown.

### Problem
The `error_code` on the job record is derived from class name with `Str::snake()` — not stable across refactors. There is no `ConversionFailedException` domain type. API and UI cannot pattern-match a typed failure.

### Expected domain exception
`ConversionFailedException::forJob(jobId, reason)` with `code: conversion_failed`.

### Expected UI/API mapping
- UI: "Conversion failed. Try again or upload another file."
- API: `conversion_failed` → 500

### Planned CONV task
CONV-399, CONV-403

---

## 7. Credits Check / Spend

### Current behavior
`DatabaseCreditLedger::spend()` throws `InsufficientCreditsException::make()` if balance is low. See item 5 above.

`InvalidCreditAmountException` is thrown for non-positive amounts — this is a programmer error, not a user-facing domain error.

### Problem
Same as item 5: no `DomainExceptionContract`, no structured `details()`.

### Planned CONV task
CONV-399, CONV-402

---

## 8. Feature Access Checks

### Current behavior
`FeatureAccessService::allows()` returns a boolean. Callers check the boolean and either return 403 (middleware) or silently fail. There is no `FeatureNotAvailableException`.

`EnsureApiAccessIsAllowed` middleware returns a JSON error with `code: api_not_available` — a non-standard code not in the stable list.

### Problem
- No typed `FeatureNotAvailableException` domain type.
- Middleware uses a hardcoded non-standard error code (`api_not_available` instead of `feature_not_available`).
- UI does not have a domain message for feature access denial.

### Expected domain exception
`FeatureNotAvailableException::forFeature(feature, plan)` with `code: feature_not_available`.

### Expected UI/API mapping
- UI: "This feature is not available on your current plan."
- API: `feature_not_available` → 403

### Planned CONV task
CONV-399, CONV-402

---

## 9. Result Download

### Current behavior
`DownloadConversionResultController` uses `abort_if($file->isExpired(), 410)`. This renders as a Symfony `HttpException` with status 410. No typed `ConversionResultExpiredException`.

### Problem
No domain type for expired result. The 410 `abort_if` is correct HTTP behavior but the API mapper catches it as a generic `HttpException` with status 410 and maps it to `not_found` (404 branch) — the 410 case is missing from `ApiExceptionMapper`.

### Expected domain exception
`ConversionResultExpiredException::forConversion(jobId)` with `code: result_expired`.

### Expected UI/API mapping
- UI: "This result expired. Upload the file again to convert."
- API: `result_expired` → 410

### Planned CONV task
CONV-399, CONV-403

---

## 10. API Authentication

### Current behavior
`AuthenticateApiKey` middleware calls `abort(401)` for missing/invalid tokens. `ApiExceptionMapper` maps `AuthenticationException` → `unauthorized` and `HttpException` 401 → `unauthorized`.

### Problem
The `abort(401)` path hits the `HttpException` branch rather than `AuthenticationException`, but both map to the same `unauthorized` code so it works. No typed domain exception needed here.

### Expected mapping
`unauthorized` → 401 (already works)

### Planned CONV task
None — already correct.

---

## 11. API Conversion Endpoints

### Current behavior
`ConversionController::store()` and `EstimateConversionFlowAction` throw `UnsupportedConversionException`, `InvalidConverterOptionsException`, `InsufficientCreditsException`. All are caught by `ApiExceptionMapper` in `bootstrap/app.php`.

### Problem
Same as items above: exceptions lack `DomainExceptionContract`. `ConversionFailedException` and `ConversionResultExpiredException` are missing entirely. The `ApiExceptionMapper` does not have a mapping for `conversion_failed` or `result_expired`.

### Expected domain exception
Add `ConversionFailedException` and `ConversionResultExpiredException`, extend `ApiExceptionMapper`.

### Planned CONV task
CONV-403, CONV-406/407

---

## 12. Billing Checkout / Credit Pack Flow

### Current behavior
`BillingPaymentService` and checkout gateways throw `CannotCheckoutFreePlanException` and `UnknownBillingPlanException`. These extend `DomainException` but have no `code()` or `details()`.

### Problem
- `CannotCheckoutFreePlanException` and `UnknownBillingPlanException` are not mapped in `ApiExceptionMapper`.
- Neither implements `DomainExceptionContract`.
- UI (`BillingPage`) does not have specific error handling — billing exceptions will surface as unhandled 500s.

### Expected domain exception
These are internal/programmer errors (trying to checkout free plan, unknown plan) — they should remain as defensive exceptions rather than user-facing domain errors. However, `ApiExceptionMapper` should catch them and return a safe `internal_error` response rather than leaking stack traces.

### Planned CONV task
CONV-407 (generic catch for unexpected exceptions in API)

---

## Summary — Domain Exception Gaps (Phase 25 Audit Snapshot)

_Status as of Phase 25 completion (CONV-399–403): all gaps resolved._

| Area | Exception | Code | Status |
|---|---|---|---|
| File format | `UnsupportedFormatException` | `unsupported_format` | ✅ Implements `DomainExceptionContract` |
| File format (upload) | `UnsupportedFileFormatException` | `unsupported_format` | ✅ Mapped in `ApiExceptionMapper` |
| Conversion pair | `UnsupportedConversionException` | `unsupported_conversion` | ✅ Implements `DomainExceptionContract` |
| Options | `InvalidConverterOptionsException` | `invalid_options` | ✅ Implements `DomainExceptionContract` |
| File size | `FileTooLargeException` | `file_too_large` | ✅ Created in CONV-401 |
| Storage limit | `StorageLimitExceededException` | `storage_limit_exceeded` | ✅ Implements `DomainExceptionContract` |
| Credits | `InsufficientCreditsException` | `insufficient_credits` | ✅ Implements `DomainExceptionContract` with `details()` |
| Feature access | `FeatureNotAvailableException` | `feature_not_available` | ✅ Created in CONV-402 |
| Conversion failed | `ConversionFailedException` | `conversion_failed` | ✅ Created in CONV-403 |
| Result expired | `ConversionResultExpiredException` | `result_expired` | ✅ Created in CONV-403 |

## Summary — Infrastructure (Phase 25 completion)

| Item | Status | CONV task |
|---|---|---|
| `DomainExceptionContract` interface | ✅ Done | CONV-399 |
| Abstract `DomainException` base | ✅ Done | CONV-399 |
| UI domain error mapper (`UiDomainErrorMapper`) | ✅ Done | CONV-404/405 |
| Conversion lifecycle logging (`ConversionLogger`) | ✅ Done | CONV-408 |
| Billing/credit lifecycle logging (`BillingLogger`) | ✅ Done | CONV-409 |
| Web rate limiters registered | ✅ Done | CONV-410/411 |
| API rate limiting + `rate_limited` JSON | ✅ Done | CONV-412/413 |
| Full MVP happy-path test | ✅ Done | CONV-414 |
