# File Converter — Phase 25 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 25 — Hardening**  
Диапазон задач: **CONV-398 → CONV-414**  
Основа нумерации: Phase 24 завершилась на `CONV-397`, поэтому Phase 25 начинается с `CONV-398`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 25 соответствует блоку:

```txt
Phase 25 — Hardening
```

Правильный диапазон Phase 25:

```txt
CONV-398 — Audit Error And Failure Handling
CONV-399 — Create Domain Exception Contract
CONV-400 — Add Format And Conversion Exceptions
CONV-401 — Add Options And File Limit Exceptions
CONV-402 — Add Credits And Feature Access Exceptions
CONV-403 — Add Conversion Result Exceptions
CONV-404 — Test UI Domain Error Mapping
CONV-405 — Implement UI Domain Error Mapping
CONV-406 — Test API Domain Error Mapping
CONV-407 — Implement API Domain Error Mapping
CONV-408 — Add Conversion Lifecycle Logging
CONV-409 — Add Billing And Credit Lifecycle Logging
CONV-410 — Test Web Rate Limiting
CONV-411 — Implement Web Rate Limiting
CONV-412 — Test API Rate Limiting
CONV-413 — Implement API Rate Limiting
CONV-414 — Add Full MVP Happy Path Test
```

Phase 25 не добавляет новые конвертеры, новые страницы или новую биллинговую модель.  
Она закрывает ошибки, предсказуемость, безопасность, rate limiting, logging и главный end-to-end тест MVP.

Главное правило:

```txt
Hardening means making the existing MVP reliable, observable and safe.
Hardening does not mean adding new product scope.
```

---

# 2. Цель Phase 25

Phase 25 доводит MVP до состояния, где критические ошибки не протекают наружу хаотично, API возвращает стабильный формат ошибок, UI показывает понятные сообщения, а дорогие операции защищены от злоупотребления.

После Phase 25 должно быть готово:

```txt
- domain exception layer;
- typed errors for unsupported formats/conversions;
- typed errors for invalid options and file limits;
- typed errors for insufficient credits and feature access;
- typed errors for failed/expired conversions;
- UI error mapper;
- API error mapper;
- conversion lifecycle logging;
- credit/billing lifecycle logging;
- web rate limiting;
- API rate limiting;
- full MVP happy-path test.
```

К концу Phase 25 основной сценарий должен быть защищён тестом:

```txt
login → upload PNG → choose JPG → configure options → estimate credits → convert → process job → download → history row → credits spent
```

---

# 3. Scope Phase 25

## Входит

```txt
- audit текущих error states;
- общий контракт domain exceptions;
- UnsupportedFormatException;
- UnsupportedConversionException;
- InvalidConverterOptionsException;
- FileTooLargeException;
- StorageLimitExceededException;
- InsufficientCreditsException;
- FeatureNotAvailableException;
- ConversionFailedException;
- ConversionResultExpiredException;
- UI mapping domain errors → readable messages;
- API mapping domain errors → stable JSON error codes;
- conversion lifecycle logging;
- billing/credit lifecycle logging;
- web upload/conversion rate limiting;
- API rate limiting;
- full MVP happy-path feature test.
```

## Не входит

```txt
- новые конвертеры;
- batch conversion;
- OCR;
- video/audio conversion;
- webhook API;
- admin panel;
- new billing model;
- Spike integration;
- advanced fraud detection;
- complex abuse scoring;
- Sentry/Bugsnag integration unless already installed;
- OpenTelemetry;
- Prometheus metrics;
- WebSocket progress;
- visual regression testing;
- Cypress/Playwright;
- React/Vue/Inertia.
```

---

# 4. Critical Decisions

## 4.1. Domain exceptions are the public boundary of business failures

Application actions/services не должны бросать случайные `RuntimeException`, `LogicException`, `ModelNotFoundException` наружу, если это ожидаемая бизнес-ошибка.

Неправильно:

```php
throw new \Exception('Bad conversion');
```

Правильно:

```php
throw UnsupportedConversionException::forPair($sourceFormat, $targetFormat);
```

## 4.2. UI and API use the same domain errors, but different mappers

Одна ошибка домена может отображаться по-разному:

```txt
UI:
This conversion is not supported yet.

API:
{
  "error": {
    "code": "unsupported_conversion",
    "message": "PNG to MP3 is not supported.",
    "details": {"source": "png", "target": "mp3"}
  }
}
```

Нельзя писать разные бизнес-проверки отдельно для UI и API.

## 4.3. API error codes are part of the contract

После Phase 25 нельзя переименовывать error codes без отдельной migration/compatibility decision.

Стабильные коды MVP:

```txt
unsupported_format
unsupported_conversion
invalid_options
file_too_large
storage_limit_exceeded
insufficient_credits
feature_not_available
conversion_failed
result_expired
rate_limited
unauthorized
forbidden
not_found
validation_failed
```

## 4.4. Logs must be structured enough to debug failed jobs

Логи должны содержать контекст:

```txt
user_id
file_id
conversion_job_id
converter_key
source_format
target_format
status
credits
error_code
```

Лог без контекста бесполезен.

Плохо:

```txt
Conversion failed.
```

Хорошо:

```txt
Conversion failed {job_id, user_id, converter_key, error_code}
```

## 4.5. Rate limiting is not billing

Rate limit не заменяет credits.  
Credits отвечают за стоимость операции.  
Rate limit отвечает за защиту системы от abuse/accidental loops.

## 4.6. Full happy-path test is the MVP safety net

Phase 25 не завершена, если нет одного теста, который проходит весь основной MVP flow от пользователя до скачивания результата и списания credits.

---

# 5. Architecture Rules

## 5.1. Domain errors live outside controllers and Livewire

Ожидаемые paths:

```txt
app/Exceptions/Domain/*
app/Exceptions/Converters/*
app/Exceptions/Files/*
app/Exceptions/Billing/*
app/Exceptions/Conversions/*
```

Можно выбрать одну директорию, например:

```txt
app/Domain/Exceptions
```

Но нельзя размазывать ошибки хаотично по controllers/components.

## 5.2. No direct JSON error construction in actions

Нельзя в action/service делать:

```php
return response()->json([...], 422);
```

Actions бросают domain exceptions.  
Controllers/API layer мапит их в JSON.

## 5.3. No direct UI message construction in domain layer

Domain exception может иметь technical message, code и details, но не должен знать про Blade/Livewire presentation.

Нельзя:

```txt
Exception returns HTML-ready message with CTA button.
```

## 5.4. Rate limits must be named

Использовать named limiters:

```txt
web-upload
web-conversion-create
api-general
api-upload
api-conversion-create
```

Не добавлять magic throttle strings в routes без централизованной фиксации.

## 5.5. Logging must not leak file contents

Можно логировать:

```txt
file name
format
size
hash/checksum
metadata
```

Нельзя логировать:

```txt
file content
OCR text
full document text
private signed download URLs
API key plain token
Stripe secret payloads beyond ids
```

---

# 6. GitFlow для Phase 25

## Base branch

Все задачи Phase 25 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-398-audit-error-and-failure-handling
feature/CONV-405-implement-ui-domain-error-mapping
feature/CONV-414-add-full-mvp-happy-path-test
```

## Commit format

```txt
CONV-398: Audit error and failure handling
CONV-405: Implement UI domain error mapping
CONV-414: Add full MVP happy path test
```

## Release branch

После выполнения `CONV-398`–`CONV-414`:

```txt
release/v0.1.25-phase25-hardening
```

## Tag

После merge release branch в `main`:

```txt
v0.1.25-phase25-hardening
```

---

# 7. TDD Rules for Phase 25

## Для domain exceptions

Тестировать:

```txt
- exception has stable code;
- exception has readable message;
- exception has details;
- exception maps to expected UI/API behavior.
```

## Для UI error mapper

Тестировать:

```txt
- unsupported conversion shows readable user message;
- insufficient credits shows credits-specific message and CTA state;
- expired result shows upload-again message;
- generic unexpected error does not leak stack trace.
```

## Для API error mapper

Тестировать:

```txt
- each domain exception returns expected HTTP status;
- JSON shape is stable;
- code/message/details are present;
- unexpected exception returns generic internal error in non-debug mode.
```

## Для logging

Тестировать через `Log::fake()` там, где возможно:

```txt
- conversion created/started/completed/failed log events;
- credits spent/granted/refunded log events;
- log context includes job_id/user_id/converter_key.
```

## Для rate limiting

Тестировать:

```txt
- repeated upload/conversion/API requests are throttled;
- normal request under limit still works;
- API returns stable JSON rate_limited error.
```

## Для full happy path

Тестировать один полный сценарий через feature/integration test, не unit mocks всего подряд.

Допустимо fake driver/storage там, где реальная image conversion делает тест хрупким, но flow должен покрыть реальные application actions.

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Hardening / Exceptions / UI / API / Logging / RateLimit / Tests
Type: Test / Feature / Infrastructure / Refactor
Priority: P0 / P1 / P2
Branch: feature/CONV-XXX-kebab-title
Base branch: develop
Depends on: CONV-...

Goal:
Что должно появиться.

TDD step:
Какой тест пишем первым. Если тест напрямую невозможен:
No direct test — причина.

Implementation:
Что именно меняем.

Acceptance criteria:
- Проверяемый результат 1
- Проверяемый результат 2
- Проверяемый результат 3

Definition of Done:
- Тест написан первым, если задача тестируемая
- Тест падает до реализации, если применимо
- Реализация минимальная
- Все связанные тесты проходят
- composer test проходит
- composer lint проходит
- npm run build проходит
- Нет функциональности вне scope задачи
- Коммит содержит ID задачи

Files likely touched:
- path/to/file
```

---

# 9. Phase 25 Atomic Tasks

---

## CONV-398 — Audit Error And Failure Handling

**Area:** Hardening / Documentation  
**Type:** Audit  
**Priority:** P0  
**Branch:** `feature/CONV-398-audit-error-and-failure-handling`  
**Base branch:** `develop`  
**Depends on:** CONV-397

### Goal

Зафиксировать текущие error/failure paths перед введением domain exceptions и mappers.

### TDD step

No direct test — audit/documentation task.

### Implementation

Создать документ:

```txt
docs/hardening/phase25-error-audit.md
```

Документ должен перечислить текущие места ошибок:

```txt
- upload validation;
- file format detection;
- target format selection;
- options validation;
- conversion job creation;
- conversion processing;
- credits check/spend;
- feature access checks;
- result download;
- API auth;
- API conversion endpoints;
- billing checkout/credit pack flow.
```

Для каждого места указать:

```txt
current behavior
problem
expected domain exception
expected UI/API mapping
planned CONV task
```

### Acceptance criteria

- Audit document создан.
- Все основные failure paths перечислены.
- Для каждого failure path указан будущий exception или mapper.
- Нет production code changes.

### Definition of Done

- Audit document добавлен.
- Scope Phase 25 подтверждён.
- `composer test` проходит.
- Коммит: `CONV-398: Audit error and failure handling`

### Files likely touched

```txt
docs/hardening/phase25-error-audit.md
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-399 — Create Domain Exception Contract

**Area:** Domain / Exceptions  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-399-create-domain-exception-contract`  
**Base branch:** `develop`  
**Depends on:** CONV-398

### Goal

Создать общий контракт для ожидаемых domain errors.

### TDD step

Unit test:

```php
it('exposes domain exception code and details', function () {
    $exception = new class('Test message.') extends \DomainException implements DomainExceptionContract {
        public function code(): string
        {
            return 'test_error';
        }

        public function details(): array
        {
            return ['foo' => 'bar'];
        }
    };

    expect($exception->code())->toBe('test_error');
    expect($exception->details())->toBe(['foo' => 'bar']);
    expect($exception->getMessage())->toBe('Test message.');
});
```

Тест должен упасть до создания contract.

### Implementation

Создать:

```txt
app/Exceptions/DomainExceptionContract.php
```

Контракт:

```php
interface DomainExceptionContract
{
    public function code(): string;

    public function details(): array;
}
```

Можно добавить abstract base class:

```txt
app/Exceptions/DomainException.php
```

Если добавляется base class, он должен:

```txt
- extend \DomainException;
- implement DomainExceptionContract;
- хранить stable code;
- хранить details array.
```

### Acceptance criteria

- DomainExceptionContract существует.
- Contract содержит `code()` и `details()`.
- Optional base class работает.
- Unit test проходит.
- Нет UI/API mapping в этой задаче.

### Definition of Done

- Тест написан первым.
- Contract/base exception добавлен.
- Тест проходит.
- Коммит: `CONV-399: Create domain exception contract`

### Files likely touched

```txt
app/Exceptions/DomainExceptionContract.php
app/Exceptions/DomainException.php
tests/Unit/Exceptions/DomainExceptionContractTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-400 — Add Format And Conversion Exceptions

**Area:** Domain / Converters / Exceptions  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-400-add-format-and-conversion-exceptions`  
**Base branch:** `develop`  
**Depends on:** CONV-399

### Goal

Добавить typed exceptions для неподдерживаемого формата и неподдерживаемой пары конвертации.

### TDD step

Unit tests:

```php
it('creates unsupported format exception with stable code and details', function () {
    $exception = UnsupportedFormatException::forFormat('heic');

    expect($exception->code())->toBe('unsupported_format');
    expect($exception->details())->toMatchArray(['format' => 'heic']);
});
```

```php
it('creates unsupported conversion exception with stable code and details', function () {
    $exception = UnsupportedConversionException::forPair('png', 'mp3');

    expect($exception->code())->toBe('unsupported_conversion');
    expect($exception->details())->toMatchArray([
        'source_format' => 'png',
        'target_format' => 'mp3',
    ]);
});
```

### Implementation

Создать:

```txt
app/Exceptions/Converters/UnsupportedFormatException.php
app/Exceptions/Converters/UnsupportedConversionException.php
```

Заменить существующие generic exceptions в:

```txt
FileFormatDetector
ConverterRegistry consumers
CreateConversionJobAction
API converter endpoints if currently throwing generic errors
```

Не делать UI/API mapping в этой задаче.

### Acceptance criteria

- `UnsupportedFormatException` существует.
- `UnsupportedConversionException` существует.
- Обе ошибки реализуют DomainExceptionContract.
- Error codes стабильные.
- Details содержат source/target/format.
- Старые generic exceptions заменены в converter-related domain flow.

### Definition of Done

- Тесты написаны.
- Exceptions добавлены.
- Generic converter errors заменены.
- Тесты проходят.
- Коммит: `CONV-400: Add format and conversion exceptions`

### Files likely touched

```txt
app/Exceptions/Converters/UnsupportedFormatException.php
app/Exceptions/Converters/UnsupportedConversionException.php
app/Services/Files/FileFormatDetector.php
app/Actions/Conversions/CreateConversionJobAction.php
tests/Unit/Exceptions/ConverterExceptionsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-401 — Add Options And File Limit Exceptions

**Area:** Domain / Files / Options / Exceptions  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-401-add-options-and-file-limit-exceptions`  
**Base branch:** `develop`  
**Depends on:** CONV-400

### Goal

Добавить typed exceptions для invalid converter options, file too large и storage limit exceeded.

### TDD step

Unit tests:

```php
it('creates invalid options exception with field errors', function () {
    $exception = InvalidConverterOptionsException::withErrors([
        'quality' => 'Invalid quality value.',
    ]);

    expect($exception->code())->toBe('invalid_options');
    expect($exception->details())->toHaveKey('errors');
});
```

```php
it('creates file too large exception with limits', function () {
    $exception = FileTooLargeException::forLimit(
        actualBytes: 50_000_000,
        maxBytes: 25_000_000,
    );

    expect($exception->code())->toBe('file_too_large');
    expect($exception->details())->toMatchArray([
        'actual_bytes' => 50_000_000,
        'max_bytes' => 25_000_000,
    ]);
});
```

### Implementation

Создать:

```txt
app/Exceptions/Converters/InvalidConverterOptionsException.php
app/Exceptions/Files/FileTooLargeException.php
app/Exceptions/Files/StorageLimitExceededException.php
```

Заменить generic validation/domain errors в:

```txt
OptionsValidator
StoreUploadedFileAction
FeatureAccess file size enforcement
storage quota enforcement
```

### Acceptance criteria

- Invalid options error имеет field-level details.
- File too large error содержит actual/max bytes.
- Storage limit error содержит used/max/attempted bytes.
- Existing tests still pass.
- Нет UI/API mapping в этой задаче.

### Definition of Done

- Тесты написаны.
- Exceptions добавлены.
- Generic option/file limit errors заменены.
- Тесты проходят.
- Коммит: `CONV-401: Add options and file limit exceptions`

### Files likely touched

```txt
app/Exceptions/Converters/InvalidConverterOptionsException.php
app/Exceptions/Files/FileTooLargeException.php
app/Exceptions/Files/StorageLimitExceededException.php
app/Services/Converters/OptionsValidator.php
app/Actions/Files/StoreUploadedFileAction.php
tests/Unit/Exceptions/OptionsAndFileExceptionsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-402 — Add Credits And Feature Access Exceptions

**Area:** Domain / Billing / Features / Exceptions  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-402-add-credits-and-feature-access-exceptions`  
**Base branch:** `develop`  
**Depends on:** CONV-401

### Goal

Добавить typed exceptions для insufficient credits и feature not available.

### TDD step

Unit tests:

```php
it('creates insufficient credits exception with required and available values', function () {
    $exception = InsufficientCreditsException::forCost(
        required: 5,
        available: 2,
    );

    expect($exception->code())->toBe('insufficient_credits');
    expect($exception->details())->toMatchArray([
        'required' => 5,
        'available' => 2,
    ]);
});
```

```php
it('creates feature not available exception with feature and plan', function () {
    $exception = FeatureNotAvailableException::forFeature('api_access', 'free');

    expect($exception->code())->toBe('feature_not_available');
    expect($exception->details())->toMatchArray([
        'feature' => 'api_access',
        'plan' => 'free',
    ]);
});
```

### Implementation

Создать:

```txt
app/Exceptions/Billing/InsufficientCreditsException.php
app/Exceptions/Features/FeatureNotAvailableException.php
```

Заменить generic errors в:

```txt
CreditLedger spend/canSpend flow
CreateConversionJobAction credit check
FeatureAccessService consumers
API access middleware
```

### Acceptance criteria

- InsufficientCreditsException содержит required/available.
- FeatureNotAvailableException содержит feature/plan.
- API access failure uses FeatureNotAvailableException or maps consistently.
- Existing billing/feature tests pass.

### Definition of Done

- Тесты написаны.
- Exceptions добавлены.
- Generic billing/feature errors заменены.
- Тесты проходят.
- Коммит: `CONV-402: Add credits and feature access exceptions`

### Files likely touched

```txt
app/Exceptions/Billing/InsufficientCreditsException.php
app/Exceptions/Features/FeatureNotAvailableException.php
app/Services/Billing/DatabaseCreditLedger.php
app/Services/Features/FeatureAccessService.php
app/Actions/Conversions/CreateConversionJobAction.php
app/Http/Middleware/EnsureApiAccess.php
tests/Unit/Exceptions/BillingAndFeatureExceptionsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-403 — Add Conversion Result Exceptions

**Area:** Domain / Conversions / Exceptions  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-403-add-conversion-result-exceptions`  
**Base branch:** `develop`  
**Depends on:** CONV-402

### Goal

Добавить typed exceptions для failed conversion и expired result.

### TDD step

Unit tests:

```php
it('creates conversion failed exception with job context', function () {
    $exception = ConversionFailedException::forJob(
        jobId: 'conv_123',
        reason: 'Driver failed.',
    );

    expect($exception->code())->toBe('conversion_failed');
    expect($exception->details())->toMatchArray([
        'conversion_job_id' => 'conv_123',
    ]);
});
```

```php
it('creates result expired exception with conversion id', function () {
    $exception = ConversionResultExpiredException::forConversion('conv_123');

    expect($exception->code())->toBe('result_expired');
    expect($exception->details())->toMatchArray([
        'conversion_job_id' => 'conv_123',
    ]);
});
```

### Implementation

Создать:

```txt
app/Exceptions/Conversions/ConversionFailedException.php
app/Exceptions/Conversions/ConversionResultExpiredException.php
```

Заменить generic errors в:

```txt
ProcessConversionJob
conversion download route/action
expired download checks
failed conversion retry path if exists
```

### Acceptance criteria

- ConversionFailedException содержит job context.
- ConversionResultExpiredException содержит conversion id.
- Expired download uses typed exception.
- Failed conversion UI/API can map this later.
- Tests pass.

### Definition of Done

- Тесты написаны.
- Exceptions добавлены.
- Generic result/conversion errors заменены.
- Тесты проходят.
- Коммит: `CONV-403: Add conversion result exceptions`

### Files likely touched

```txt
app/Exceptions/Conversions/ConversionFailedException.php
app/Exceptions/Conversions/ConversionResultExpiredException.php
app/Jobs/ProcessConversionJob.php
app/Http/Controllers/ConversionDownloadController.php
tests/Unit/Exceptions/ConversionResultExceptionsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-404 — Test UI Domain Error Mapping

**Area:** Livewire / UI / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-404-test-ui-domain-error-mapping`  
**Base branch:** `develop`  
**Depends on:** CONV-403

### Goal

Добавить падающие тесты, фиксирующие как domain exceptions отображаются в UI.

### TDD step

Livewire tests:

```php
it('shows readable message for unsupported conversion in dashboard', function () {
    Livewire::test(DashboardConverter::class)
        ->set('sourceFormat', 'png')
        ->call('selectTargetFormat', 'mp3')
        ->assertSee('This conversion is not supported yet.');
});
```

```php
it('shows insufficient credits message in dashboard', function () {
    $user = User::factory()->create();
    actingAs($user);

    // arrange user with zero credits and uploaded file state

    Livewire::test(DashboardConverter::class)
        ->call('convert')
        ->assertSee('You do not have enough credits.');
});
```

Если exact DashboardConverter state неудобен, можно тестировать mapper напрямую:

```php
$message = app(UiDomainErrorMapper::class)->message($exception);
expect($message)->toBe('You do not have enough credits.');
```

Тесты должны упасть до реализации CONV-405.

### Implementation

Только добавить тесты.

Минимальный набор ошибок:

```txt
unsupported_conversion
invalid_options
file_too_large
insufficient_credits
feature_not_available
result_expired
conversion_failed
```

### Acceptance criteria

- Тесты для UI error mapping существуют.
- Тесты проверяют user-readable messages.
- Тесты не проверяют stack traces.
- Тесты падают до реализации.

### Definition of Done

- Тесты добавлены.
- Тесты ожидаемо падают.
- Коммит: `CONV-404: Test UI domain error mapping`

### Files likely touched

```txt
tests/Unit/UI/UiDomainErrorMapperTest.php
tests/Feature/Livewire/DashboardConverterErrorTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если падение ожидаемо зафиксировано перед реализацией.

---

## CONV-405 — Implement UI Domain Error Mapping

**Area:** Livewire / UI / Error Handling  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-405-implement-ui-domain-error-mapping`  
**Base branch:** `develop`  
**Depends on:** CONV-404

### Goal

Реализовать единый mapper domain exceptions в пользовательские UI-сообщения.

### TDD step

Использовать падающие тесты из CONV-404.

### Implementation

Создать:

```txt
app/Support/UI/UiDomainErrorMapper.php
```

Mapper должен принимать `Throwable` и возвращать DTO/array:

```php
final readonly class UiErrorMessage
{
    public function __construct(
        public string $title,
        public string $message,
        public ?string $actionLabel = null,
        public ?string $actionUrl = null,
    ) {}
}
```

Минимальные mappings:

```txt
unsupported_format → Unsupported file format
unsupported_conversion → This conversion is not supported yet.
invalid_options → Some conversion settings are invalid.
file_too_large → This file is too large for your current plan.
storage_limit_exceeded → Your storage limit is reached.
insufficient_credits → You do not have enough credits.
feature_not_available → This feature is not available on your current plan.
conversion_failed → Conversion failed. Try again or upload another file.
result_expired → This result expired. Upload the file again to convert.
```

Подключить в Livewire components/actions catch blocks:

```php
try {
    ...
} catch (DomainExceptionContract $e) {
    $this->uiError = app(UiDomainErrorMapper::class)->map($e);
}
```

Не ловить всё подряд слишком широко, если это скрывает programmer errors. Для unexpected exceptions можно оставить generic message только в UI boundary.

### Acceptance criteria

- UiDomainErrorMapper существует.
- Все target domain exceptions имеют readable message.
- Dashboard показывает mapped messages.
- Insufficient credits содержит CTA на billing/credits, если route есть.
- Stack traces не показываются пользователю.
- Тесты CONV-404 проходят.

### Definition of Done

- Mapper реализован.
- Livewire error boundary подключён.
- Тесты проходят.
- `composer test` проходит.
- Коммит: `CONV-405: Implement UI domain error mapping`

### Files likely touched

```txt
app/Support/UI/UiDomainErrorMapper.php
app/Support/UI/UiErrorMessage.php
app/Livewire/DashboardConverter.php
tests/Unit/UI/UiDomainErrorMapperTest.php
tests/Feature/Livewire/DashboardConverterErrorTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-406 — Test API Domain Error Mapping

**Area:** API / Tests / Error Handling  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-406-test-api-domain-error-mapping`  
**Base branch:** `develop`  
**Depends on:** CONV-405

### Goal

Добавить падающие тесты, фиксирующие стабильный JSON формат API ошибок.

### TDD step

Feature/API tests:

```php
it('returns unsupported conversion error as stable json', function () {
    $user = User::factory()->pro()->create();
    $token = ApiKey::factory()->for($user)->createValidTokenForTest();

    $this->withToken($token)
        ->postJson('/api/v1/conversions', [
            'file_id' => 'file_test',
            'target_format' => 'mp3',
            'options' => [],
        ])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'unsupported_conversion')
        ->assertJsonStructure([
            'error' => ['code', 'message', 'details'],
        ]);
});
```

Mapper unit tests can be more stable:

```php
$response = app(ApiDomainErrorMapper::class)->map(
    UnsupportedConversionException::forPair('png', 'mp3')
);

expect($response->status)->toBe(422);
expect($response->body['error']['code'])->toBe('unsupported_conversion');
```

Тесты должны упасть до CONV-407.

### Implementation

Только добавить тесты.

Покрыть минимум:

```txt
unsupported_conversion → 422
invalid_options → 422
file_too_large → 413 or 422, выбрать и зафиксировать
insufficient_credits → 402 or 422, выбрать и зафиксировать
feature_not_available → 403
result_expired → 410
rate_limited → 429 later
```

Рекомендация:

```txt
insufficient_credits → 402 Payment Required
file_too_large → 413 Payload Too Large
result_expired → 410 Gone
```

### Acceptance criteria

- API error mapping tests существуют.
- JSON shape стабилен.
- HTTP statuses зафиксированы.
- Tests fail before implementation.

### Definition of Done

- Тесты добавлены.
- Тесты ожидаемо падают.
- Коммит: `CONV-406: Test API domain error mapping`

### Files likely touched

```txt
tests/Unit/API/ApiDomainErrorMapperTest.php
tests/Feature/API/ApiErrorMappingTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если падение ожидаемо зафиксировано перед реализацией.

---

## CONV-407 — Implement API Domain Error Mapping

**Area:** API / Error Handling  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-407-implement-api-domain-error-mapping`  
**Base branch:** `develop`  
**Depends on:** CONV-406

### Goal

Реализовать единый mapper domain exceptions в API JSON errors.

### TDD step

Использовать падающие тесты из CONV-406.

### Implementation

Создать:

```txt
app/Support/API/ApiDomainErrorMapper.php
app/Support/API/ApiErrorResponse.php
```

Формат:

```json
{
  "error": {
    "code": "unsupported_conversion",
    "message": "PNG to MP3 is not supported.",
    "details": {
      "source_format": "png",
      "target_format": "mp3"
    }
  }
}
```

Подключить в exception rendering для API routes:

```txt
bootstrap/app.php exception handler
или app/Exceptions/Handler.php, depending Laravel version
```

Mapper statuses:

```txt
unsupported_format → 422
unsupported_conversion → 422
invalid_options → 422
file_too_large → 413
storage_limit_exceeded → 422
insufficient_credits → 402
feature_not_available → 403
conversion_failed → 500 or 422 depending source; MVP: 500 for internal failure
result_expired → 410
```

Unexpected exceptions:

```txt
production/non-debug → internal_error, 500, no stack trace
debug → Laravel default acceptable only outside API tests if configured
```

### Acceptance criteria

- ApiDomainErrorMapper exists.
- API routes return stable JSON error shape.
- Domain exceptions map to expected HTTP status.
- Unexpected errors do not expose stack trace in API JSON.
- Tests CONV-406 pass.

### Definition of Done

- Mapper реализован.
- Exception rendering подключён.
- Тесты проходят.
- `composer test` проходит.
- Коммит: `CONV-407: Implement API domain error mapping`

### Files likely touched

```txt
app/Support/API/ApiDomainErrorMapper.php
app/Support/API/ApiErrorResponse.php
bootstrap/app.php
app/Exceptions/Handler.php
tests/Unit/API/ApiDomainErrorMapperTest.php
tests/Feature/API/ApiErrorMappingTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-408 — Add Conversion Lifecycle Logging

**Area:** Logging / Conversions  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-408-add-conversion-lifecycle-logging`  
**Base branch:** `develop`  
**Depends on:** CONV-407

### Goal

Добавить структурированные логи жизненного цикла conversion jobs.

### TDD step

Feature/unit tests using `Log::spy()` or `Log::fake()` pattern supported by project:

```php
it('logs conversion job creation', function () {
    Log::spy();

    // create conversion job through action

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message, array $context) =>
            $message === 'conversion.job.created'
            && isset($context['conversion_job_id'])
            && isset($context['user_id'])
            && isset($context['converter_key'])
        );
});
```

If Log facade assertions are too brittle, test via dedicated `ConversionLogger` fake.

### Implementation

Создать optional logger wrapper:

```txt
app/Support/Logging/ConversionLogger.php
```

Логировать события:

```txt
conversion.job.created
conversion.job.started
conversion.job.completed
conversion.job.failed
conversion.result.downloaded optional
```

Context:

```txt
conversion_job_id
user_id
source_file_id
result_file_id if available
converter_key
source_format
target_format
status
error_code if failed
```

Подключить в:

```txt
CreateConversionJobAction
ProcessConversionJob
Download controller/action optional
```

### Acceptance criteria

- Conversion lifecycle logs exist.
- Logs include job/user/converter context.
- Failed jobs log error_code/message without file contents.
- Tests pass or logger wrapper tested.

### Definition of Done

- Logging добавлен.
- Sensitive data не логируется.
- Тесты проходят.
- Коммит: `CONV-408: Add conversion lifecycle logging`

### Files likely touched

```txt
app/Support/Logging/ConversionLogger.php
app/Actions/Conversions/CreateConversionJobAction.php
app/Jobs/ProcessConversionJob.php
app/Http/Controllers/ConversionDownloadController.php
tests/Feature/Logging/ConversionLifecycleLoggingTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-409 — Add Billing And Credit Lifecycle Logging

**Area:** Logging / Billing / Credits  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-409-add-billing-and-credit-lifecycle-logging`  
**Base branch:** `develop`  
**Depends on:** CONV-408

### Goal

Добавить структурированные логи для credit ledger и billing events.

### TDD step

Tests:

```php
it('logs credit spend with context', function () {
    Log::spy();

    app(CreditLedger::class)->spend($user, 2, 'conversion_completed', [
        'conversion_job_id' => $job->id,
    ]);

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $message, array $context) =>
            $message === 'credits.spent'
            && $context['user_id'] === $user->id
            && $context['amount'] === 2
        );
});
```

### Implementation

Создать optional wrapper:

```txt
app/Support/Logging/BillingLogger.php
```

Логировать:

```txt
credits.granted
credits.spent
credits.refunded
billing.subscription.updated
billing.credit_pack.purchased
billing.webhook.ignored optional
```

Context:

```txt
user_id
amount
balance_after if available
reason
source_type/source_id
stripe_customer_id optional
stripe_invoice_id optional
stripe_checkout_session_id optional
```

Не логировать:

```txt
full card data
full Stripe payload
API key plain token
```

### Acceptance criteria

- Credit grants/spends/refunds log structured events.
- Billing webhook/payment handlers log relevant ids.
- Sensitive payment data not logged.
- Tests pass.

### Definition of Done

- Billing/credit logging добавлен.
- Sensitive data не логируется.
- Тесты проходят.
- Коммит: `CONV-409: Add billing and credit lifecycle logging`

### Files likely touched

```txt
app/Support/Logging/BillingLogger.php
app/Services/Billing/DatabaseCreditLedger.php
app/Services/Billing/BillingPaymentService.php
app/Http/Controllers/BillingWebhookController.php
tests/Feature/Logging/BillingCreditLoggingTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-410 — Test Web Rate Limiting

**Area:** Web / RateLimit / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-410-test-web-rate-limiting`  
**Base branch:** `develop`  
**Depends on:** CONV-409

### Goal

Добавить падающие тесты для web rate limiting на upload и conversion create.

### TDD step

Feature tests:

```php
it('rate limits repeated web conversion creation requests', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    for ($i = 0; $i < 20; $i++) {
        // call endpoint/livewire action that creates conversion
    }

    // next request/action should be blocked or return rate limit message
});
```

Если Livewire action напрямую сложно rate-limit тестировать, вынести limit в service/middleware и тестировать route/controller boundary.

Тест должен зафиксировать desired behavior, даже если конкретный лимит будет MVP-простым.

Recommended MVP limits:

```txt
web-upload: 20/min per user/IP
web-conversion-create: 30/min per user
```

### Implementation

Только добавить тесты.

### Acceptance criteria

- Тест на web upload rate limit существует.
- Тест на web conversion create rate limit существует.
- Нормальный запрос under limit не блокируется.
- Тесты падают до CONV-411.

### Definition of Done

- Тесты добавлены.
- Тесты ожидаемо падают.
- Коммит: `CONV-410: Test web rate limiting`

### Files likely touched

```txt
tests/Feature/RateLimit/WebRateLimitTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если падение ожидаемо зафиксировано перед реализацией.

---

## CONV-411 — Implement Web Rate Limiting

**Area:** Web / RateLimit  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-411-implement-web-rate-limiting`  
**Base branch:** `develop`  
**Depends on:** CONV-410

### Goal

Реализовать named web rate limiters для upload и conversion create.

### TDD step

Использовать падающие тесты из CONV-410.

### Implementation

Добавить named limiters в `AppServiceProvider` или `RouteServiceProvider`, depending Laravel version:

```php
RateLimiter::for('web-upload', function (Request $request) {
    return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('web-conversion-create', function (Request $request) {
    return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
});
```

Подключить к relevant routes/actions:

```txt
file upload endpoint if route-based
conversion create route/controller
Livewire action via manual RateLimiter hit/check if not route-based
```

Для Livewire action можно использовать:

```php
RateLimiter::tooManyAttempts($key, $maxAttempts)
RateLimiter::hit($key, decaySeconds: 60)
```

Если blocked, показать UI mapped `rate_limited` message или validation error.

### Acceptance criteria

- `web-upload` limiter exists.
- `web-conversion-create` limiter exists.
- Repeated abusive requests blocked.
- Normal requests under limit work.
- UI receives readable message.
- Tests CONV-410 pass.

### Definition of Done

- Web rate limiting реализован.
- Named limiters используются.
- Тесты проходят.
- Коммит: `CONV-411: Implement web rate limiting`

### Files likely touched

```txt
app/Providers/AppServiceProvider.php
app/Livewire/DashboardConverter.php
routes/web.php
tests/Feature/RateLimit/WebRateLimitTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-412 — Test API Rate Limiting

**Area:** API / RateLimit / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-412-test-api-rate-limiting`  
**Base branch:** `develop`  
**Depends on:** CONV-411

### Goal

Добавить падающие тесты для API rate limiting и JSON error `rate_limited`.

### TDD step

API feature test:

```php
it('rate limits api requests with stable json error', function () {
    $user = User::factory()->pro()->create();
    $token = ApiKey::factory()->for($user)->createValidTokenForTest();

    for ($i = 0; $i < 100; $i++) {
        $this->withToken($token)->getJson('/api/v1/converters');
    }

    $this->withToken($token)
        ->getJson('/api/v1/converters')
        ->assertStatus(429)
        ->assertJsonPath('error.code', 'rate_limited');
});
```

Recommended MVP limits:

```txt
api-general: per plan
free: no api access
pro: 60/min
max: 300/min
api-upload: lower/higher depending plan
api-conversion-create: lower than read endpoints
```

Тест должен упасть до CONV-413.

### Implementation

Только добавить тесты.

### Acceptance criteria

- API rate limit test существует.
- 429 JSON format фиксирован.
- `error.code = rate_limited`.
- Under-limit request works.
- Тест падает до реализации.

### Definition of Done

- Тесты добавлены.
- Тесты ожидаемо падают.
- Коммит: `CONV-412: Test API rate limiting`

### Files likely touched

```txt
tests/Feature/RateLimit/ApiRateLimitTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если падение ожидаемо зафиксировано перед реализацией.

---

## CONV-413 — Implement API Rate Limiting

**Area:** API / RateLimit  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-413-implement-api-rate-limiting`  
**Base branch:** `develop`  
**Depends on:** CONV-412

### Goal

Реализовать API rate limiting с учётом plan limits и стабильным JSON error response.

### TDD step

Использовать падающие тесты из CONV-412.

### Implementation

Добавить named limiters:

```txt
api-general
api-upload
api-conversion-create
```

Использовать `FeatureAccessService` для лимита:

```php
$limit = app(FeatureAccessService::class)
    ->limit($request->user(), 'api_rate_limit_per_minute') ?? 60;
```

Limiter key должен учитывать API key/user:

```php
$userId = $request->user()?->id;
$apiKeyId = $request->attributes->get('api_key_id');
$key = $apiKeyId ? "api-key:{$apiKeyId}" : "user:{$userId}";
```

При throttle API должен возвращать:

```json
{
  "error": {
    "code": "rate_limited",
    "message": "Too many requests. Please try again later.",
    "details": {
      "retry_after_seconds": 60
    }
  }
}
```

### Acceptance criteria

- API named limiters exist.
- Plan-specific limits used where available.
- API returns 429 JSON error with `rate_limited`.
- Free users are still blocked by feature access before rate limiting where applicable.
- Tests CONV-412 pass.

### Definition of Done

- API rate limiting реализован.
- JSON 429 стабилен.
- Тесты проходят.
- Коммит: `CONV-413: Implement API rate limiting`

### Files likely touched

```txt
app/Providers/AppServiceProvider.php
app/Http/Middleware/ApiRateLimit.php
app/Support/API/ApiDomainErrorMapper.php
routes/api.php
tests/Feature/RateLimit/ApiRateLimitTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-414 — Add Full MVP Happy Path Test

**Area:** Tests / Integration / MVP  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-414-add-full-mvp-happy-path-test`  
**Base branch:** `develop`  
**Depends on:** CONV-413

### Goal

Добавить главный end-to-end/integration тест MVP flow.

### TDD step

Feature test:

```php
it('completes the full mvp conversion happy path', function () {
    Storage::fake('local');
    Queue::fake(); // or use sync processing if preferred for this test

    $user = User::factory()->create();
    app(CreditLedger::class)->grant($user, 50, 'test_grant');

    $this->actingAs($user);

    $file = UploadedFile::fake()->image('sample.png', 800, 600);

    $component = Livewire::test(DashboardConverter::class)
        ->set('upload', $file)
        ->call('uploadFile')
        ->assertSet('step', 'format')
        ->assertSee('JPG')
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('step', 'settings')
        ->set('options.quality', 'high')
        ->call('convert')
        ->assertSet('step', 'converting');

    $job = ConversionJob::query()->latest()->firstOrFail();

    // Either dispatch real ProcessConversionJob synchronously
    // or fake driver result depending current architecture.

    app(ProcessConversionJobHandlerForTest::class)->handle($job);

    $component
        ->call('refreshConversionStatus')
        ->assertSet('step', 'completed')
        ->assertSee('Download');

    expect(app(CreditLedger::class)->balance($user))->toBe(49);

    $this->assertDatabaseHas('conversion_jobs', [
        'id' => $job->id,
        'status' => ConversionStatus::Completed,
        'source_format' => 'png',
        'target_format' => 'jpg',
    ]);
});
```

Adapt exact method names to actual DashboardConverter.

### Implementation

Добавить один полный тест, который проверяет:

```txt
- user exists;
- user has credits;
- upload PNG;
- format detected;
- target JPG selected;
- settings accepted;
- cost estimated;
- job created;
- job processed;
- result created;
- dashboard completed state visible;
- result downloadable;
- history row exists;
- credits spent.
```

Если реальный image driver делает тест нестабильным, использовать fake driver через container binding, но не mock-ать весь flow до бессмысленности.

### Acceptance criteria

- Full MVP happy-path test exists.
- Test covers UI/application integration, not only unit layer.
- Credits are spent on success.
- History row/result job exists.
- Download route works or at least completed result is downloadable in same test or companion assertion.
- Test passes consistently.

### Definition of Done

- Full MVP happy-path test добавлен.
- Тест проходит.
- `composer test` проходит.
- `composer lint` проходит.
- `npm run build` проходит.
- Коммит: `CONV-414: Add full MVP happy path test`

### Files likely touched

```txt
tests/Feature/MVP/MvpHappyPathTest.php
tests/Fakes/FakeImageConverterDriver.php
app/Livewire/DashboardConverter.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 25 Completion Criteria

Phase 25 завершена, когда:

```txt
- CONV-398–CONV-414 выполнены;
- error/failure audit exists;
- DomainExceptionContract exists;
- unsupported format/conversion errors are typed;
- invalid options/file limit errors are typed;
- credits/feature access errors are typed;
- conversion failed/expired errors are typed;
- UI domain error mapping works;
- API domain error mapping works;
- API error JSON shape is stable;
- conversion lifecycle logging exists;
- credit/billing lifecycle logging exists;
- web upload/conversion rate limits exist;
- API rate limits exist;
- rate limit API returns `rate_limited` JSON;
- full MVP happy-path test exists and passes;
- no new product modules were added;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 25

Без отдельной задачи нельзя:

```txt
- добавлять новые форматы конвертации;
- добавлять OCR;
- добавлять batch conversion;
- менять pricing model;
- менять credit ledger semantics;
- подключать Spike;
- добавлять Sentry/Bugsnag;
- добавлять OpenTelemetry;
- создавать admin dashboard;
- делать API webhooks;
- добавлять WebSockets;
- переписывать Livewire dashboard;
- добавлять React/Vue/Inertia;
- делать visual regression suite;
- менять public landing page;
- менять API docs кроме error codes, если нужно синхронизировать.
```

---

# 12. Recommended Execution Order

```txt
CONV-398 Audit Error And Failure Handling
CONV-399 Create Domain Exception Contract
CONV-400 Add Format And Conversion Exceptions
CONV-401 Add Options And File Limit Exceptions
CONV-402 Add Credits And Feature Access Exceptions
CONV-403 Add Conversion Result Exceptions
CONV-404 Test UI Domain Error Mapping
CONV-405 Implement UI Domain Error Mapping
CONV-406 Test API Domain Error Mapping
CONV-407 Implement API Domain Error Mapping
CONV-408 Add Conversion Lifecycle Logging
CONV-409 Add Billing And Credit Lifecycle Logging
CONV-410 Test Web Rate Limiting
CONV-411 Implement Web Rate Limiting
CONV-412 Test API Rate Limiting
CONV-413 Implement API Rate Limiting
CONV-414 Add Full MVP Happy Path Test
```

---

# 13. Release

После завершения Phase 25:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh --seed

git checkout -b release/v0.1.25-phase25-hardening
git push -u origin release/v0.1.25-phase25-hardening
```

После этого сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.25-phase25-hardening -m "File Converter Phase 25 hardening"
git push origin v0.1.25-phase25-hardening
```
