# File Converter — Phase 19 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 19 — API Foundation**  
Диапазон задач: **CONV-291 → CONV-312**  
Основа нумерации: Phase 18 завершилась на `CONV-290`, поэтому Phase 19 начинается с `CONV-291`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 19 соответствует блоку:

```txt
Phase 19 — API Foundation
```

Правильный диапазон Phase 19:

```txt
CONV-291 — Create API v1 Route Group
CONV-292 — Add Standard API Error Contract
CONV-293 — Add API Domain Exception Mapping
CONV-294 — Create API Keys Table
CONV-295 — Create ApiKey Model And Relations
CONV-296 — Create API Key Generation Service
CONV-297 — Create API Authentication Middleware
CONV-298 — Enforce API Access Feature Gate
CONV-299 — Add API Rate Limiting Baseline
CONV-300 — Add Converters Index Endpoint
CONV-301 — Add Converter Schema Endpoint
CONV-302 — Add API File Upload Endpoint
CONV-303 — Add File Target Formats Endpoint
CONV-304 — Add Conversion Cost Estimate Endpoint
CONV-305 — Add Create Conversion Endpoint
CONV-306 — Add Conversion Status Endpoint
CONV-307 — Add Conversion Result Download Endpoint
CONV-308 — Add Credits Balance Endpoint
CONV-309 — Add API Resource Response DTOs
CONV-310 — Add API Ownership Guards
CONV-311 — Add API Happy Path Feature Test
CONV-312 — Add API Foundation Final Smoke Tests
```

Phase 19 добавляет первый рабочий REST API поверх уже существующей application-логики.

Главное правило:

```txt
API не создаёт отдельную бизнес-логику.
API вызывает те же actions/services, что Livewire UI.
```

---

# 2. Цель Phase 19

Phase 19 добавляет API-слой для программного использования File Converter.

После Phase 19 API-клиент должен уметь:

```txt
- аутентифицироваться через API key;
- получить список доступных конвертеров;
- получить schema настроек для конкретной пары source → target;
- загрузить файл;
- получить доступные target formats для файла;
- оценить стоимость конвертации в credits;
- создать conversion job;
- проверить status conversion job;
- скачать результат;
- получить текущий credits balance.
```

API должен использовать существующие слои:

```txt
StoreUploadedFileAction
ConverterRegistry
OptionsValidator
ConversionCostEstimator
CreateConversionJobAction
CreditLedger
FeatureAccessService
ConversionJob / FileRecord policies or ownership checks
```

Phase 19 не делает API documentation portal. Это Phase 20.

---

# 3. Scope Phase 19

## Входит

```txt
- versioned /api/v1 route group;
- standard JSON error format;
- domain exception → API error mapping;
- api_keys table;
- ApiKey model;
- API key generation service;
- API authentication middleware;
- api_access feature gate;
- baseline API rate limiting;
- converters endpoint;
- converter schema endpoint;
- file upload endpoint;
- file target formats endpoint;
- conversion cost estimate endpoint;
- create conversion endpoint;
- conversion status endpoint;
- conversion download endpoint;
- credits balance endpoint;
- API response resources/DTOs;
- ownership guards;
- API happy path test.
```

## Не входит

```txt
- OpenAPI documentation page;
- Swagger UI / Redoc;
- Postman collection;
- public developer portal;
- API SDK generation;
- webhooks;
- OAuth;
- team API keys;
- scoped API permissions beyond baseline;
- API key UI page;
- API usage analytics dashboard;
- advanced rate limits by plan beyond basic limits;
- async callback URL support;
- direct-to-S3 upload;
- chunked uploads;
- batch conversion API;
- changing converter pricing;
- changing billing model;
- changing Livewire conversion flow.
```

Phase 20 делает API documentation.  
API key UI может быть отдельной фазой или частью будущей Settings/API Keys page.

---

# 4. Critical Decisions

## 4.1. API controllers are thin adapters

Нельзя делать бизнес-логику в API controllers:

```php
// wrong
public function store(Request $request)
{
    $file = FileRecord::create([...]);
    $job = ConversionJob::create([...]);
    $user->credits()->decrement(...);
}
```

Правильно:

```txt
Controller → Request validation → Application Action → API Resource
```

Например:

```php
$file = app(StoreUploadedFileAction::class)->handle($user, $request->file('file'));
```

## 4.2. API must use the same conversion actions as UI

`POST /api/v1/conversions` обязан использовать:

```txt
CreateConversionJobAction
```

а не отдельный `ApiCreateConversionJobAction`, если там будет та же логика.

Иначе через месяц web UI и API начнут вести себя по-разному:

```txt
UI проверяет credits, API не проверяет;
UI валидирует options, API пропускает мусор;
UI учитывает FeatureAccessService, API обходит его.
```

Это недопустимо.

## 4.3. API keys are not Laravel Sanctum tokens in this MVP

Можно было бы использовать Sanctum, но для File Converter API лучше свой минимальный `api_keys` слой:

```txt
- token показывается один раз;
- в базе хранится hash;
- можно revoke;
- можно хранить last_used_at;
- можно позже добавить scopes;
- можно связать с usage/rate limits.
```

Sanctum можно добавить позже, если появится SPA/mobile use case. Сейчас нужен server-to-server API key.

## 4.4. Free users cannot use API unless plan allows it

Credits сами по себе не дают API-доступ.

Неправильно:

```txt
if user has credits → API allowed
```

Правильно:

```php
FeatureAccessService::allows($user, 'api_access')
```

Пример:

```txt
Free: credits есть, API disabled
Pro: API enabled
Max: API enabled with higher rate limit
```

## 4.5. API must return stable error codes

API не должен отдавать сырые exception messages.

Правильно:

```json
{
  "error": {
    "code": "insufficient_credits",
    "message": "You need 2 credits to run this conversion.",
    "details": {
      "required": 2,
      "available": 1
    }
  }
}
```

Коды ошибок должны быть стабильны, потому что API-клиенты будут на них завязываться.

## 4.6. Upload endpoint must enforce the same limits as UI

`POST /api/v1/files` должен использовать те же правила:

```txt
- supported MIME;
- max file size by plan;
- storage limit;
- file metadata extraction;
- retention policy.
```

Нельзя делать API upload “в обход”, потому что так API станет дырой в лимитах.

## 4.7. Conversion estimate endpoint must not create jobs

`POST /api/v1/conversions/estimate` только считает:

```txt
- converter exists;
- options valid;
- cost amount;
- breakdown;
- whether user has enough credits.
```

Он не должен:

```txt
- создавать ConversionJob;
- списывать credits;
- dispatch queue job;
- сохранять result file.
```

## 4.8. Download endpoint must not expose storage paths

API не должен возвращать внутренние пути:

```txt
storage/app/private/conversions/...
```

Скачивание только через контролируемый endpoint:

```txt
GET /api/v1/conversions/{conversion}/download
```

С проверками:

```txt
- authenticated API key;
- owner only;
- completed only;
- not expired;
- result file exists.
```

---

# 5. Architecture Rules

## 5.1. API route location

Использовать стандартный Laravel API route file:

```txt
routes/api.php
```

Route prefix:

```txt
/api/v1
```

Names:

```txt
api.v1.converters.index
api.v1.conversions.store
```

## 5.2. Controller location

Рекомендуемые пути:

```txt
app/Http/Controllers/Api/V1/ConverterController.php
app/Http/Controllers/Api/V1/FileController.php
app/Http/Controllers/Api/V1/ConversionController.php
app/Http/Controllers/Api/V1/CreditController.php
```

Не складывать всё в один `ApiController`.

## 5.3. Request validation location

Для endpoints с payload использовать Form Requests:

```txt
app/Http/Requests/Api/V1/UploadFileRequest.php
app/Http/Requests/Api/V1/EstimateConversionRequest.php
app/Http/Requests/Api/V1/CreateConversionRequest.php
```

Но FormRequest не должен содержать бизнес-валидацию converter options.  
Он валидирует форму payload. Converter options валидирует `OptionsValidator` / converter.

## 5.4. API responses must use resources/DTOs

Нельзя разбрасывать raw arrays в каждом controller.

Правильно:

```txt
FileResource
ConverterResource
ConversionResource
CreditBalanceResource or simple DTO response
```

## 5.5. No OpenAPI implementation in Phase 19

Phase 19 должна оставить API достаточно стабильным, чтобы Phase 20 описала его в OpenAPI.

Но в Phase 19 нельзя тратить время на:

```txt
Swagger UI
Redoc
Scribe
Scramble
OpenAPI YAML
```

Это следующая фаза.

---

# 6. GitFlow для Phase 19

## Base branch

Все задачи Phase 19 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-291-create-api-v1-route-group
feature/CONV-297-create-api-authentication-middleware
feature/CONV-305-add-create-conversion-endpoint
```

## Commit format

```txt
CONV-291: Create API v1 route group
CONV-297: Create API authentication middleware
CONV-305: Add create conversion endpoint
```

## Release branch

После выполнения `CONV-291`–`CONV-312`:

```txt
release/v0.1.19-phase19-api-foundation
```

## Tag

После merge release branch в `main`:

```txt
v0.1.19-phase19-api-foundation
```

---

# 7. TDD Rules for Phase 19

## Для API auth

Test-first:

```txt
- request without API key returns 401;
- request with invalid key returns 401;
- request with revoked key returns 401;
- request with valid key authenticates user;
- last_used_at updates.
```

## Для feature gate

Test-first:

```txt
- free user with valid API key gets api_not_available;
- pro/max user with valid API key can call API;
- feature gate uses FeatureAccessService.
```

## Для endpoints

Каждый endpoint должен иметь feature tests:

```txt
- happy path;
- unauthorized path;
- ownership path where relevant;
- invalid input path;
- domain error mapping path.
```

## Для controllers

Не тестировать implementation details controllers.  
Тестировать observable API behavior:

```txt
status code
JSON shape
error code
database side effects
queue dispatch where relevant
```

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: API / Auth / Controllers / Tests / Resources
Type: Test / Feature / Controller / Middleware / Migration / Resource
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
- API response format стабилен
- Нет business logic в controller
- Используются application services/actions
- Коммит содержит ID задачи

Files likely touched:
- path/to/file
```

---

# 9. Phase 19 Atomic Tasks

---

## CONV-291 — Create API v1 Route Group

**Area:** API / Routing  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-291-create-api-v1-route-group`  
**Base branch:** `develop`  
**Depends on:** CONV-290

### Goal

Создать versioned API route group `/api/v1` и минимальный health endpoint для проверки, что API layer подключён.

### TDD step

Feature test:

```php
it('responds from api v1 health endpoint', function () {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertJson([
            'status' => 'ok',
            'version' => 'v1',
        ]);
});
```

Тест должен упасть до создания route.

### Implementation

В `routes/api.php` добавить:

```php
Route::prefix('v1')
    ->name('api.v1.')
    ->group(function () {
        Route::get('/health', fn () => response()->json([
            'status' => 'ok',
            'version' => 'v1',
        ]))->name('health');
    });
```

Не добавлять auth middleware на health endpoint.

### Acceptance criteria

- `/api/v1/health` возвращает 200.
- Response JSON содержит `status=ok` и `version=v1`.
- Route name имеет префикс `api.v1`.
- Нет бизнес-логики.
- Test passes.

### Definition of Done

- Тест написан первым.
- Route group добавлен.
- Test passes.
- Коммит: `CONV-291: Create API v1 route group`

### Files likely touched

```txt
routes/api.php
tests/Feature/Api/V1/ApiHealthTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-292 — Add Standard API Error Contract

**Area:** API / Errors  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-292-add-standard-api-error-contract`  
**Base branch:** `develop`  
**Depends on:** CONV-291

### Goal

Зафиксировать единый JSON contract для API errors.

### TDD step

Feature test на temporary route или helper:

```php
it('returns standard api error response shape', function () {
    $response = api_error(
        code: 'test_error',
        message: 'Test error message.',
        status: 422,
        details: ['field' => 'value'],
    );

    expect($response->getStatusCode())->toBe(422);
    expect($response->getData(true))->toBe([
        'error' => [
            'code' => 'test_error',
            'message' => 'Test error message.',
            'details' => ['field' => 'value'],
        ],
    ]);
});
```

Если global helper нежелателен, сделать `ApiErrorResponseFactory` и тестировать его.

### Implementation

Рекомендуемый вариант: создать factory.

```txt
app/Support/Api/ApiErrorResponseFactory.php
```

Пример:

```php
final class ApiErrorResponseFactory
{
    public function make(string $code, string $message, int $status = 400, array $details = []): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $status);
    }
}
```

### Acceptance criteria

- Есть единая factory/helper для API errors.
- JSON shape стабилен.
- `details` всегда объект/array, не `null`.
- Status code задаётся явно.
- Unit test passes.

### Definition of Done

- Тест написан.
- Error response factory добавлена.
- Test passes.
- Коммит: `CONV-292: Add standard API error contract`

### Files likely touched

```txt
app/Support/Api/ApiErrorResponseFactory.php
tests/Unit/Support/Api/ApiErrorResponseFactoryTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-293 — Add API Domain Exception Mapping

**Area:** API / Errors  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-293-add-api-domain-exception-mapping`  
**Base branch:** `develop`  
**Depends on:** CONV-292

### Goal

Сопоставить доменные exceptions с API error codes/statuses.

### TDD step

Feature test через test route или прямой mapper test:

```php
it('maps insufficient credits exception to api error', function () {
    $mapper = app(ApiExceptionMapper::class);

    $mapped = $mapper->map(new InsufficientCreditsException(
        required: 2,
        available: 1,
    ));

    expect($mapped->code)->toBe('insufficient_credits');
    expect($mapped->status)->toBe(402);
});
```

Адаптировать к фактическим exception classes из предыдущих фаз.

### Implementation

Создать:

```txt
app/Support/Api/ApiExceptionMapper.php
app/Support/Api/MappedApiError.php
```

Минимальные mappings:

```txt
UnsupportedFormatException       → unsupported_format        422
UnsupportedConversionException   → unsupported_conversion    422
InvalidConverterOptionsException → invalid_options           422
InsufficientCreditsException     → insufficient_credits      402
FeatureNotAvailableException     → feature_not_available     403
FileTooLargeException            → file_too_large            413
ConversionFailedException        → conversion_failed         500
ModelNotFoundException           → not_found                 404
AuthenticationException          → unauthorized              401
AuthorizationException           → forbidden                 403
ThrottleRequestsException        → rate_limited              429
```

Подключить в exception rendering для API requests.  
В Laravel 11/12 это может быть `bootstrap/app.php`; в старых версиях — `app/Exceptions/Handler.php`.

### Acceptance criteria

- Domain exceptions не протекают сырыми messages.
- API получает стабильный `error.code`.
- HTTP status соответствует ошибке.
- Web UI exception rendering не сломан.
- Tests pass.

### Definition of Done

- Mapper добавлен.
- API exception rendering подключён.
- Tests pass.
- Коммит: `CONV-293: Add API domain exception mapping`

### Files likely touched

```txt
app/Support/Api/ApiExceptionMapper.php
app/Support/Api/MappedApiError.php
bootstrap/app.php
app/Exceptions/Handler.php
tests/Feature/Api/V1/ApiErrorMappingTest.php
tests/Unit/Support/Api/ApiExceptionMapperTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-294 — Create API Keys Table

**Area:** API / Auth / Database  
**Type:** Migration  
**Priority:** P0  
**Branch:** `feature/CONV-294-create-api-keys-table`  
**Base branch:** `develop`  
**Depends on:** CONV-293

### Goal

Создать таблицу `api_keys` для server-to-server API authentication.

### TDD step

Migration/schema test:

```php
it('has api keys table', function () {
    expect(Schema::hasTable('api_keys'))->toBeTrue();

    foreach ([
        'id',
        'user_id',
        'name',
        'token_hash',
        'last_used_at',
        'revoked_at',
        'created_at',
        'updated_at',
    ] as $column) {
        expect(Schema::hasColumn('api_keys', $column))->toBeTrue();
    }
});
```

Тест должен упасть до migration.

### Implementation

Migration:

```php
Schema::create('api_keys', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('token_hash', 64)->unique();
    $table->timestamp('last_used_at')->nullable();
    $table->timestamp('revoked_at')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'revoked_at']);
});
```

Не хранить plain token.

### Acceptance criteria

- `api_keys` table exists.
- `token_hash` unique.
- `user_id` indexed/referenced.
- `revoked_at` nullable.
- Plain token не хранится.
- Test passes.

### Definition of Done

- Schema test написан.
- Migration добавлена.
- Test passes.
- Коммит: `CONV-294: Create API keys table`

### Files likely touched

```txt
database/migrations/*_create_api_keys_table.php
tests/Feature/Database/ApiKeysSchemaTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-295 — Create ApiKey Model And Relations

**Area:** API / Auth / Model  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-295-create-api-key-model-and-relations`  
**Base branch:** `develop`  
**Depends on:** CONV-294

### Goal

Создать `ApiKey` model и связи с `User`.

### TDD step

Model test:

```php
it('belongs api key to user', function () {
    $user = User::factory()->create();

    $apiKey = ApiKey::factory()->for($user)->create();

    expect($apiKey->user->is($user))->toBeTrue();
    expect($user->apiKeys()->first()->is($apiKey))->toBeTrue();
});
```

### Implementation

Создать:

```txt
app/Models/ApiKey.php
database/factories/ApiKeyFactory.php
```

Model:

```php
final class ApiKey extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'token_hash',
        'last_used_at',
        'revoked_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}
```

В `User`:

```php
public function apiKeys(): HasMany
{
    return $this->hasMany(ApiKey::class);
}
```

### Acceptance criteria

- ApiKey model exists.
- ApiKey belongs to User.
- User has many api keys.
- `isRevoked()` works.
- Factory exists.
- Tests pass.

### Definition of Done

- Model tests написаны.
- Model/factory/relations добавлены.
- Tests pass.
- Коммит: `CONV-295: Create ApiKey model and relations`

### Files likely touched

```txt
app/Models/ApiKey.php
app/Models/User.php
database/factories/ApiKeyFactory.php
tests/Feature/Models/ApiKeyTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-296 — Create API Key Generation Service

**Area:** API / Auth / Service  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-296-create-api-key-generation-service`  
**Base branch:** `develop`  
**Depends on:** CONV-295

### Goal

Создать сервис, который генерирует API key, показывает plain token один раз и сохраняет только hash.

### TDD step

Service test:

```php
it('creates api key and returns plain token once', function () {
    $user = User::factory()->create();

    $result = app(ApiKeyGenerator::class)->create($user, 'Test key');

    expect($result->plainToken)->toStartWith('fc_');
    expect($result->apiKey->name)->toBe('Test key');
    expect($result->apiKey->token_hash)->not->toBe($result->plainToken);
    expect(hash('sha256', $result->plainToken))->toBe($result->apiKey->token_hash);
});
```

### Implementation

Создать:

```txt
app/Services/Api/ApiKeyGenerator.php
app/Services/Api/GeneratedApiKey.php
```

Пример:

```php
final readonly class GeneratedApiKey
{
    public function __construct(
        public ApiKey $apiKey,
        public string $plainToken,
    ) {}
}
```

Generator:

```php
$plainToken = 'fc_' . Str::random(64);
$hash = hash('sha256', $plainToken);
```

### Acceptance criteria

- Plain token starts with stable prefix, e.g. `fc_`.
- Plain token не хранится в базе.
- Hash stored via SHA-256.
- Name required.
- Result DTO содержит model и plain token.
- Tests pass.

### Definition of Done

- Тест написан.
- Generator создан.
- Tests pass.
- Коммит: `CONV-296: Create API key generation service`

### Files likely touched

```txt
app/Services/Api/ApiKeyGenerator.php
app/Services/Api/GeneratedApiKey.php
tests/Feature/Services/Api/ApiKeyGeneratorTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-297 — Create API Authentication Middleware

**Area:** API / Auth / Middleware  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-297-create-api-authentication-middleware`  
**Base branch:** `develop`  
**Depends on:** CONV-296

### Goal

Создать middleware для Bearer API key authentication.

### TDD step

Feature tests:

```php
it('rejects api request without bearer token', function () {
    $this->getJson('/api/v1/protected-test')
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'unauthorized');
});
```

```php
it('authenticates api request with valid api key', function () {
    $user = User::factory()->create();
    $generated = app(ApiKeyGenerator::class)->create($user, 'Test');

    $this->withToken($generated->plainToken)
        ->getJson('/api/v1/protected-test')
        ->assertOk();
});
```

Временно можно добавить test-only route внутри теста или routes file guarded by environment. Лучше использовать реальный future endpoint после CONV-300, но для middleware удобен test route.

### Implementation

Создать middleware:

```txt
app/Http/Middleware/AuthenticateApiKey.php
```

Логика:

```txt
- прочитать Bearer token;
- hash('sha256', token);
- найти ApiKey by token_hash;
- reject if missing;
- reject if revoked;
- auth()->setUser($apiKey->user) или request()->attributes->set('api_key', $apiKey);
- update last_used_at;
- continue.
```

Не использовать raw token в logs.

### Acceptance criteria

- Missing token → 401 `unauthorized`.
- Invalid token → 401 `unauthorized`.
- Revoked token → 401 `unauthorized`.
- Valid token authenticates user.
- `last_used_at` updates.
- Plain token не логируется.
- Tests pass.

### Definition of Done

- Middleware tests написаны.
- Middleware реализован.
- Registered alias, e.g. `api.key`.
- Tests pass.
- Коммит: `CONV-297: Create API authentication middleware`

### Files likely touched

```txt
app/Http/Middleware/AuthenticateApiKey.php
bootstrap/app.php
app/Http/Kernel.php
routes/api.php
tests/Feature/Api/V1/ApiAuthenticationTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-298 — Enforce API Access Feature Gate

**Area:** API / Feature Access  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-298-enforce-api-access-feature-gate`  
**Base branch:** `develop`  
**Depends on:** CONV-297

### Goal

Запретить API-доступ пользователям, чей plan не включает `api_access`.

### TDD step

Feature tests:

```php
it('rejects free user from protected api endpoints even with valid api key', function () {
    $user = User::factory()->create(['plan' => Plan::Free]);
    $generated = app(ApiKeyGenerator::class)->create($user, 'Free key');

    $this->withToken($generated->plainToken)
        ->getJson('/api/v1/converters')
        ->assertForbidden()
        ->assertJsonPath('error.code', 'api_not_available');
});
```

```php
it('allows pro user to access protected api endpoints', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $generated = app(ApiKeyGenerator::class)->create($user, 'Pro key');

    $this->withToken($generated->plainToken)
        ->getJson('/api/v1/converters')
        ->assertOk();
});
```

Endpoint `/api/v1/converters` может быть stubbed until CONV-300.

### Implementation

Создать middleware:

```txt
app/Http/Middleware/EnsureApiAccessIsAllowed.php
```

Логика:

```php
if (! app(FeatureAccessService::class)->allows($request->user(), 'api_access')) {
    throw FeatureNotAvailableException::forFeature('api_access');
}
```

Или сразу отдавать API error `api_not_available`, если текущая exception mapping не поддерживает feature-specific code.

Protected API routes должны использовать:

```txt
api.key
api.access
```

### Acceptance criteria

- Free user with valid key blocked.
- Pro/Max user allowed.
- Uses FeatureAccessService.
- Error code is `api_not_available` or stable mapped feature error.
- Tests pass.

### Definition of Done

- Feature gate tests написаны.
- Middleware добавлен.
- Protected API routes используют middleware.
- Tests pass.
- Коммит: `CONV-298: Enforce API access feature gate`

### Files likely touched

```txt
app/Http/Middleware/EnsureApiAccessIsAllowed.php
bootstrap/app.php
app/Http/Kernel.php
routes/api.php
tests/Feature/Api/V1/ApiAccessFeatureGateTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-299 — Add API Rate Limiting Baseline

**Area:** API / Rate Limit  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-299-add-api-rate-limiting-baseline`  
**Base branch:** `develop`  
**Depends on:** CONV-298

### Goal

Добавить базовый rate limit для API endpoints.

### TDD step

Feature test:

```php
it('rate limits api requests', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);
    $generated = app(ApiKeyGenerator::class)->create($user, 'Pro key');

    for ($i = 0; $i < 61; $i++) {
        $this->withToken($generated->plainToken)->getJson('/api/v1/health');
    }

    $this->withToken($generated->plainToken)
        ->getJson('/api/v1/health')
        ->assertStatus(429);
});
```

Адаптировать лимит к фактическому config. Для теста лучше сделать route с small test limiter или использовать time travel.

### Implementation

В `RouteServiceProvider` / `bootstrap/app.php` настроить limiter:

```php
RateLimiter::for('api-v1', function (Request $request) {
    $user = $request->user();

    $limit = $user
        ? app(FeatureAccessService::class)->limit($user, 'api_rate_limit_per_minute') ?? 60
        : 30;

    return Limit::perMinute((int) $limit)->by(
        $user?->id ?: $request->ip()
    );
});
```

Protected API routes use:

```txt
throttle:api-v1
```

### Acceptance criteria

- API routes have rate limiting.
- Limit can depend on FeatureAccessService.
- 429 errors return standard API error if possible.
- Health endpoint can remain public but still limited if desired.
- Tests pass or minimal smoke test proves limiter registered.

### Definition of Done

- Rate limit configured.
- Tests/smoke test added.
- Tests pass.
- Коммит: `CONV-299: Add API rate limiting baseline`

### Files likely touched

```txt
app/Providers/RouteServiceProvider.php
bootstrap/app.php
routes/api.php
tests/Feature/Api/V1/ApiRateLimitTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-300 — Add Converters Index Endpoint

**Area:** API / Converters  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-300-add-converters-index-endpoint`  
**Base branch:** `develop`  
**Depends on:** CONV-299

### Goal

Добавить endpoint для списка доступных конвертеров.

### TDD step

Feature test:

```php
it('returns available converters for api user', function () {
    $user = User::factory()->pro()->create();
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $this->withToken($token)
        ->getJson('/api/v1/converters')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'key',
                    'source_format',
                    'target_format',
                    'label',
                    'description',
                ],
            ],
        ]);
});
```

### Implementation

Route:

```php
Route::get('/converters', [ConverterController::class, 'index'])
    ->name('converters.index');
```

Controller uses:

```php
app(ConverterRegistry::class)->all();
```

Response example:

```json
{
  "data": [
    {
      "key": "png_to_jpg",
      "source_format": "png",
      "target_format": "jpg",
      "label": "PNG to JPG",
      "description": "Convert PNG images to JPG."
    }
  ]
}
```

### Acceptance criteria

- Endpoint protected by API key + api_access.
- Uses ConverterRegistry.
- Returns stable JSON shape.
- Does not expose PHP class names.
- Tests pass.

### Definition of Done

- Feature test написан.
- Controller/route добавлены.
- Tests pass.
- Коммит: `CONV-300: Add converters index endpoint`

### Files likely touched

```txt
routes/api.php
app/Http/Controllers/Api/V1/ConverterController.php
app/Http/Resources/Api/V1/ConverterResource.php
tests/Feature/Api/V1/ConvertersIndexEndpointTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-301 — Add Converter Schema Endpoint

**Area:** API / Converters  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-301-add-converter-schema-endpoint`  
**Base branch:** `develop`  
**Depends on:** CONV-300

### Goal

Добавить endpoint для получения options schema конкретного converter pair.

### TDD step

Feature test:

```php
it('returns converter options schema', function () {
    $user = User::factory()->pro()->create();
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $this->withToken($token)
        ->getJson('/api/v1/converters/png/jpg/schema')
        ->assertOk()
        ->assertJsonPath('data.source_format', 'png')
        ->assertJsonPath('data.target_format', 'jpg')
        ->assertJsonStructure([
            'data' => [
                'source_format',
                'target_format',
                'options',
            ],
        ]);
});
```

Unsupported pair test:

```php
$this->withToken($token)
    ->getJson('/api/v1/converters/png/mp3/schema')
    ->assertStatus(422)
    ->assertJsonPath('error.code', 'unsupported_conversion');
```

### Implementation

Route:

```php
Route::get('/converters/{source}/{target}/schema', [ConverterController::class, 'schema'])
    ->name('converters.schema');
```

Controller:

```php
$converter = $registry->find($source, $target);

if (! $converter) {
    throw UnsupportedConversionException::forPair($source, $target);
}

return response()->json([
    'data' => [
        'source_format' => $converter->sourceFormat(),
        'target_format' => $converter->targetFormat(),
        'options' => $converter->optionsSchema(),
    ],
]);
```

### Acceptance criteria

- Valid pair returns schema.
- Unsupported pair returns standard error.
- Uses ConverterRegistry.
- Does not duplicate schema manually in controller.
- Tests pass.

### Definition of Done

- Tests written.
- Endpoint implemented.
- Tests pass.
- Коммит: `CONV-301: Add converter schema endpoint`

### Files likely touched

```txt
routes/api.php
app/Http/Controllers/Api/V1/ConverterController.php
tests/Feature/Api/V1/ConverterSchemaEndpointTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-302 — Add API File Upload Endpoint

**Area:** API / Files  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-302-add-api-file-upload-endpoint`  
**Base branch:** `develop`  
**Depends on:** CONV-301

### Goal

Добавить API endpoint для загрузки файла через multipart/form-data.

### TDD step

Feature test:

```php
it('uploads file through api', function () {
    Storage::fake('local');

    $user = User::factory()->pro()->create();
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $file = UploadedFile::fake()->image('image.png', 100, 100);

    $this->withToken($token)
        ->postJson('/api/v1/files', [
            'file' => $file,
        ])
        ->assertCreated()
        ->assertJsonPath('data.original_name', 'image.png')
        ->assertJsonPath('data.extension', 'png');

    $this->assertDatabaseHas('files', [
        'user_id' => $user->id,
        'original_name' => 'image.png',
        'extension' => 'png',
    ]);
});
```

### Implementation

Request:

```txt
app/Http/Requests/Api/V1/UploadFileRequest.php
```

Route:

```php
Route::post('/files', [FileController::class, 'store'])
    ->name('files.store');
```

Controller must call:

```php
$fileRecord = app(StoreUploadedFileAction::class)->handle(
    user: $request->user(),
    file: $request->file('file'),
);
```

Response status:

```txt
201 Created
```

### Acceptance criteria

- API upload uses StoreUploadedFileAction.
- Plan file size limits enforced through existing action/validation.
- Response contains file id/name/format/size/metadata.
- Owner set to authenticated API user.
- Unsupported file returns standard error.
- Tests pass.

### Definition of Done

- Feature tests written.
- Request/controller/resource added.
- No duplicate upload logic in controller.
- Tests pass.
- Коммит: `CONV-302: Add API file upload endpoint`

### Files likely touched

```txt
routes/api.php
app/Http/Controllers/Api/V1/FileController.php
app/Http/Requests/Api/V1/UploadFileRequest.php
app/Http/Resources/Api/V1/FileResource.php
tests/Feature/Api/V1/FileUploadEndpointTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-303 — Add File Target Formats Endpoint

**Area:** API / Files / Converters  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-303-add-file-target-formats-endpoint`  
**Base branch:** `develop`  
**Depends on:** CONV-302

### Goal

Добавить endpoint, который возвращает доступные target formats для уже загруженного файла.

### TDD step

Feature test:

```php
it('returns target formats for uploaded file', function () {
    $user = User::factory()->pro()->create();
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $file = FileRecord::factory()->for($user)->create([
        'extension' => 'png',
        'mime_type' => 'image/png',
    ]);

    $this->withToken($token)
        ->getJson("/api/v1/files/{$file->id}/targets")
        ->assertOk()
        ->assertJsonFragment(['target_format' => 'jpg'])
        ->assertJsonFragment(['target_format' => 'webp'])
        ->assertJsonFragment(['target_format' => 'pdf']);
});
```

Ownership test:

```php
it('does not return targets for another users file', ...);
```

### Implementation

Route:

```php
Route::get('/files/{file}/targets', [FileController::class, 'targets'])
    ->name('files.targets');
```

Controller:

```php
$this->authorizeFileOwner($request->user(), $file);
$targets = app(ConverterRegistry::class)->targetsFor($file->extension);
```

Response:

```json
{
  "data": [
    {
      "target_format": "jpg",
      "label": "JPG",
      "description": "Best for sharing photos.",
      "recommended": true
    }
  ]
}
```

### Acceptance criteria

- Returns targets based on file source format.
- Owner-only.
- Unsupported source returns empty list or `unsupported_format` depending existing policy.
- Uses ConverterRegistry.
- Tests pass.

### Definition of Done

- Tests written.
- Endpoint implemented.
- Ownership enforced.
- Tests pass.
- Коммит: `CONV-303: Add file target formats endpoint`

### Files likely touched

```txt
routes/api.php
app/Http/Controllers/Api/V1/FileController.php
app/Http/Resources/Api/V1/TargetFormatResource.php
tests/Feature/Api/V1/FileTargetsEndpointTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-304 — Add Conversion Cost Estimate Endpoint

**Area:** API / Conversions / Billing  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-304-add-conversion-cost-estimate-endpoint`  
**Base branch:** `develop`  
**Depends on:** CONV-303

### Goal

Добавить endpoint для оценки стоимости конвертации без создания job.

### TDD step

Feature test:

```php
it('estimates conversion cost through api', function () {
    $user = User::factory()->pro()->create();
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    $this->withToken($token)
        ->postJson('/api/v1/conversions/estimate', [
            'file_id' => $file->id,
            'target_format' => 'jpg',
            'options' => [
                'quality' => 'high',
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.amount', 1)
        ->assertJsonStructure([
            'data' => [
                'amount',
                'breakdown',
                'has_enough_credits',
            ],
        ]);

    $this->assertDatabaseCount('conversion_jobs', 0);
});
```

### Implementation

Request:

```txt
EstimateConversionRequest
```

Route:

```php
Route::post('/conversions/estimate', [ConversionController::class, 'estimate'])
    ->name('conversions.estimate');
```

Controller flow:

```txt
- find FileRecord by file_id;
- enforce owner;
- find converter;
- validate options;
- estimate cost;
- read CreditLedger balance;
- return amount/breakdown/has_enough_credits.
```

Do not create `ConversionJob`.

### Acceptance criteria

- Estimate returns amount and breakdown.
- Does not create conversion job.
- Does not spend credits.
- Validates converter/options.
- Owner-only.
- Tests pass.

### Definition of Done

- Tests written.
- Endpoint implemented.
- No side effects beyond read operations.
- Tests pass.
- Коммит: `CONV-304: Add conversion cost estimate endpoint`

### Files likely touched

```txt
routes/api.php
app/Http/Controllers/Api/V1/ConversionController.php
app/Http/Requests/Api/V1/EstimateConversionRequest.php
tests/Feature/Api/V1/ConversionEstimateEndpointTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-305 — Add Create Conversion Endpoint

**Area:** API / Conversions  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-305-add-create-conversion-endpoint`  
**Base branch:** `develop`  
**Depends on:** CONV-304

### Goal

Добавить endpoint для создания conversion job через API.

### TDD step

Feature test:

```php
it('creates conversion job through api', function () {
    Queue::fake();

    $user = User::factory()->pro()->create();
    app(CreditLedger::class)->grant($user, 10, 'test_grant');

    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    $this->withToken($token)
        ->postJson('/api/v1/conversions', [
            'file_id' => $file->id,
            'target_format' => 'jpg',
            'options' => [
                'quality' => 'high',
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'queued')
        ->assertJsonPath('data.source_format', 'png')
        ->assertJsonPath('data.target_format', 'jpg');

    $this->assertDatabaseHas('conversion_jobs', [
        'user_id' => $user->id,
        'source_file_id' => $file->id,
        'target_format' => 'jpg',
        'status' => 'queued',
    ]);
});
```

Insufficient credits test:

```php
$this->withToken($token)
    ->postJson('/api/v1/conversions', [...])
    ->assertStatus(402)
    ->assertJsonPath('error.code', 'insufficient_credits');
```

### Implementation

Request:

```txt
CreateConversionRequest
```

Route:

```php
Route::post('/conversions', [ConversionController::class, 'store'])
    ->name('conversions.store');
```

Controller must call:

```php
$job = app(CreateConversionJobAction::class)->handle(
    user: $request->user(),
    file: $file,
    targetFormat: $request->string('target_format'),
    options: $request->array('options', []),
);
```

No direct job creation in controller.

### Acceptance criteria

- Creates conversion job via CreateConversionJobAction.
- Checks credits through existing action.
- Dispatches processing job through existing action behavior.
- Returns `201 Created`.
- Standard errors for unsupported/invalid/insufficient credits.
- Tests pass.

### Definition of Done

- Tests written.
- Endpoint implemented.
- Controller contains no duplicated business logic.
- Tests pass.
- Коммит: `CONV-305: Add create conversion endpoint`

### Files likely touched

```txt
routes/api.php
app/Http/Controllers/Api/V1/ConversionController.php
app/Http/Requests/Api/V1/CreateConversionRequest.php
app/Http/Resources/Api/V1/ConversionResource.php
tests/Feature/Api/V1/CreateConversionEndpointTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-306 — Add Conversion Status Endpoint

**Area:** API / Conversions  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-306-add-conversion-status-endpoint`  
**Base branch:** `develop`  
**Depends on:** CONV-305

### Goal

Добавить endpoint для получения статуса conversion job.

### TDD step

Feature test:

```php
it('returns conversion status through api', function () {
    $user = User::factory()->pro()->create();
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $job = ConversionJob::factory()->for($user)->queued()->create([
        'source_format' => 'png',
        'target_format' => 'jpg',
    ]);

    $this->withToken($token)
        ->getJson("/api/v1/conversions/{$job->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $job->id)
        ->assertJsonPath('data.status', 'queued');
});
```

Ownership test:

```php
it('does not expose another users conversion status', ...);
```

### Implementation

Route:

```php
Route::get('/conversions/{conversion}', [ConversionController::class, 'show'])
    ->name('conversions.show');
```

Response should include:

```txt
id
status
progress
source_format
target_format
created_at
started_at
completed_at
result_file nullable
error nullable
```

### Acceptance criteria

- Owner can see conversion status.
- Other user cannot.
- Response includes status/progress/result metadata.
- Failed job includes safe error code/message.
- Tests pass.

### Definition of Done

- Tests written.
- Endpoint implemented.
- Ownership enforced.
- Tests pass.
- Коммит: `CONV-306: Add conversion status endpoint`

### Files likely touched

```txt
routes/api.php
app/Http/Controllers/Api/V1/ConversionController.php
app/Http/Resources/Api/V1/ConversionResource.php
tests/Feature/Api/V1/ConversionStatusEndpointTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-307 — Add Conversion Result Download Endpoint

**Area:** API / Conversions / Download  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-307-add-conversion-result-download-endpoint`  
**Base branch:** `develop`  
**Depends on:** CONV-306

### Goal

Добавить endpoint для скачивания результата completed conversion.

### TDD step

Feature test:

```php
it('downloads completed conversion result through api', function () {
    Storage::fake('local');

    $user = User::factory()->pro()->create();
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    Storage::disk('local')->put('results/result.jpg', 'fake-image');

    $resultFile = FileRecord::factory()->for($user)->create([
        'stored_path' => 'results/result.jpg',
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
    ]);

    $job = ConversionJob::factory()->for($user)->completed()->create([
        'result_file_id' => $resultFile->id,
    ]);

    $this->withToken($token)
        ->getJson("/api/v1/conversions/{$job->id}/download")
        ->assertOk();
});
```

Failed/incomplete test:

```php
queued conversion download returns 422 result_not_ready
```

### Implementation

Route:

```php
Route::get('/conversions/{conversion}/download', [ConversionController::class, 'download'])
    ->name('conversions.download');
```

Checks:

```txt
- owner;
- status completed;
- result_file_id exists;
- result file not expired;
- physical file exists.
```

Return file response, not storage path.

### Acceptance criteria

- Completed conversion can be downloaded.
- Queued/processing/failed cannot.
- Expired result cannot.
- Other user cannot download.
- Internal storage path not exposed in JSON.
- Tests pass.

### Definition of Done

- Tests written.
- Endpoint implemented.
- Security checks added.
- Tests pass.
- Коммит: `CONV-307: Add conversion result download endpoint`

### Files likely touched

```txt
routes/api.php
app/Http/Controllers/Api/V1/ConversionController.php
tests/Feature/Api/V1/ConversionDownloadEndpointTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-308 — Add Credits Balance Endpoint

**Area:** API / Credits  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-308-add-credits-balance-endpoint`  
**Base branch:** `develop`  
**Depends on:** CONV-307

### Goal

Добавить endpoint для получения текущего credits balance.

### TDD step

Feature test:

```php
it('returns current credits balance through api', function () {
    $user = User::factory()->pro()->create();
    app(CreditLedger::class)->grant($user, 100, 'test_grant');

    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $this->withToken($token)
        ->getJson('/api/v1/credits/balance')
        ->assertOk()
        ->assertJsonPath('data.balance', 100)
        ->assertJsonPath('data.unit', 'credits');
});
```

### Implementation

Route:

```php
Route::get('/credits/balance', [CreditController::class, 'balance'])
    ->name('credits.balance');
```

Controller:

```php
$balance = app(CreditLedger::class)->balance($request->user());
```

Response:

```json
{
  "data": {
    "balance": 100,
    "unit": "credits"
  }
}
```

### Acceptance criteria

- Endpoint protected by API key + api_access.
- Uses CreditLedger.
- Returns integer balance.
- Does not expose transaction history in this endpoint.
- Tests pass.

### Definition of Done

- Test written.
- Endpoint implemented.
- Tests pass.
- Коммит: `CONV-308: Add credits balance endpoint`

### Files likely touched

```txt
routes/api.php
app/Http/Controllers/Api/V1/CreditController.php
tests/Feature/Api/V1/CreditBalanceEndpointTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-309 — Add API Resource Response DTOs

**Area:** API / Resources  
**Type:** Refactor / Feature  
**Priority:** P1  
**Branch:** `feature/CONV-309-add-api-resource-response-dtos`  
**Base branch:** `develop`  
**Depends on:** CONV-308

### Goal

Убрать raw response arrays из controllers и привести API responses к единым resources/DTO.

### TDD step

Snapshot-style structure tests on existing endpoints:

```php
it('returns stable file resource shape', function () {
    // call upload or show response and assert structure
});
```

```php
it('returns stable conversion resource shape', function () {
    // call conversion status and assert structure
});
```

### Implementation

Создать или доработать:

```txt
app/Http/Resources/Api/V1/FileResource.php
app/Http/Resources/Api/V1/ConverterResource.php
app/Http/Resources/Api/V1/ConversionResource.php
app/Http/Resources/Api/V1/CreditBalanceResource.php optional
```

Standard response shape:

```json
{
  "data": {}
}
```

For collections:

```json
{
  "data": []
}
```

Do not include internal fields:

```txt
stored_path
token_hash
internal error traces
```

### Acceptance criteria

- Controllers use resources/DTOs.
- JSON shapes are stable.
- Internal fields are hidden.
- Existing endpoint tests still pass.
- Tests pass.

### Definition of Done

- Resource tests written/updated.
- Raw arrays removed where practical.
- Tests pass.
- Коммит: `CONV-309: Add API resource response DTOs`

### Files likely touched

```txt
app/Http/Resources/Api/V1/FileResource.php
app/Http/Resources/Api/V1/ConverterResource.php
app/Http/Resources/Api/V1/ConversionResource.php
app/Http/Resources/Api/V1/TargetFormatResource.php
app/Http/Controllers/Api/V1/*
tests/Feature/Api/V1/*
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-310 — Add API Ownership Guards

**Area:** API / Security  
**Type:** Feature / Hardening  
**Priority:** P0  
**Branch:** `feature/CONV-310-add-api-ownership-guards`  
**Base branch:** `develop`  
**Depends on:** CONV-309

### Goal

Централизовать ownership checks для API resources.

### TDD step

Security tests:

```php
it('does not allow api user to access another users file targets', function () {
    $owner = User::factory()->pro()->create();
    $other = User::factory()->pro()->create();

    $file = FileRecord::factory()->for($owner)->create(['extension' => 'png']);
    $token = app(ApiKeyGenerator::class)->create($other, 'Other')->plainToken;

    $this->withToken($token)
        ->getJson("/api/v1/files/{$file->id}/targets")
        ->assertForbidden()
        ->assertJsonPath('error.code', 'forbidden');
});
```

Add similar tests for conversions.

### Implementation

Варианты:

```txt
- Policies for FileRecord and ConversionJob;
- small trait/helper in controllers;
- dedicated OwnershipGuard service.
```

Рекомендуемый минимальный вариант:

```txt
app/Support/Api/ApiOwnershipGuard.php
```

Methods:

```php
public function ensureFileOwner(User $user, FileRecord $file): void
public function ensureConversionOwner(User $user, ConversionJob $conversion): void
```

Throw AuthorizationException / domain Forbidden exception.

### Acceptance criteria

- File endpoints owner-only.
- Conversion endpoints owner-only.
- Download endpoint owner-only.
- Error shape stable.
- Tests cover cross-user access.
- Tests pass.

### Definition of Done

- Security tests written.
- Ownership guard implemented.
- All relevant endpoints use guard.
- Tests pass.
- Коммит: `CONV-310: Add API ownership guards`

### Files likely touched

```txt
app/Support/Api/ApiOwnershipGuard.php
app/Http/Controllers/Api/V1/FileController.php
app/Http/Controllers/Api/V1/ConversionController.php
tests/Feature/Api/V1/ApiOwnershipGuardTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-311 — Add API Happy Path Feature Test

**Area:** API / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-311-add-api-happy-path-feature-test`  
**Base branch:** `develop`  
**Depends on:** CONV-310

### Goal

Добавить один полный API happy-path тест от upload до download.

### TDD step

Feature test:

```php
it('runs full api conversion happy path', function () {
    Storage::fake('local');
    Queue::fake();

    $user = User::factory()->pro()->create();
    app(CreditLedger::class)->grant($user, 10, 'test_grant');
    $token = app(ApiKeyGenerator::class)->create($user, 'API')->plainToken;

    $uploadResponse = $this->withToken($token)
        ->postJson('/api/v1/files', [
            'file' => UploadedFile::fake()->image('image.png', 100, 100),
        ])
        ->assertCreated();

    $fileId = $uploadResponse->json('data.id');

    $this->withToken($token)
        ->getJson("/api/v1/files/{$fileId}/targets")
        ->assertOk()
        ->assertJsonFragment(['target_format' => 'jpg']);

    $this->withToken($token)
        ->postJson('/api/v1/conversions/estimate', [
            'file_id' => $fileId,
            'target_format' => 'jpg',
            'options' => ['quality' => 'high'],
        ])
        ->assertOk()
        ->assertJsonPath('data.amount', 1);

    $conversionResponse = $this->withToken($token)
        ->postJson('/api/v1/conversions', [
            'file_id' => $fileId,
            'target_format' => 'jpg',
            'options' => ['quality' => 'high'],
        ])
        ->assertCreated();

    $conversionId = $conversionResponse->json('data.id');

    $this->withToken($token)
        ->getJson("/api/v1/conversions/{$conversionId}")
        ->assertOk()
        ->assertJsonPath('data.status', 'queued');
});
```

Если real queue processing в тесте уже стабилен, можно выполнить job и скачать result. Если нет — happy path ограничить create/status, а download отдельно покрыт CONV-307.

### Implementation

Только добавить/исправить тесты.  
Если тест выявляет проблемы в предыдущих endpoints — исправить минимально.

### Acceptance criteria

- Полный API flow проходит.
- Используются реальные endpoints.
- API key auth работает.
- Credits check работает.
- Queue dispatch работает.
- No controller-only shortcuts.
- Test passes.

### Definition of Done

- Happy-path test added.
- All endpoint tests pass.
- Коммит: `CONV-311: Add API happy path feature test`

### Files likely touched

```txt
tests/Feature/Api/V1/ApiHappyPathTest.php
app/Http/Controllers/Api/V1/* optional fixes
app/Http/Resources/Api/V1/* optional fixes
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-312 — Add API Foundation Final Smoke Tests

**Area:** API / Tests / Release  
**Type:** Test / Hardening  
**Priority:** P0  
**Branch:** `feature/CONV-312-add-api-foundation-final-smoke-tests`  
**Base branch:** `develop`  
**Depends on:** CONV-311

### Goal

Финально проверить, что Phase 19 API foundation закрыта и не сломала web UI.

### TDD step

Добавить smoke tests:

```php
it('protects all non-health api v1 endpoints with api key authentication', function () {
    $this->getJson('/api/v1/converters')
        ->assertUnauthorized();

    $this->postJson('/api/v1/files')
        ->assertUnauthorized();

    $this->postJson('/api/v1/conversions')
        ->assertUnauthorized();

    $this->getJson('/api/v1/credits/balance')
        ->assertUnauthorized();
});
```

Добавить smoke на web route:

```php
it('keeps dashboard route available after api foundation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk();
});
```

### Implementation

- Добавить final smoke tests.
- Проверить route list.
- Проверить отсутствие accidental public endpoints.
- Проверить, что health endpoint остаётся публичным.

Команды:

```bash
composer test
composer lint
npm run build
php artisan route:list --path=api/v1
```

### Acceptance criteria

- All protected API endpoints require API key.
- API health remains public.
- Free user blocked by api_access gate.
- Pro/Max user allowed.
- Web dashboard still works.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.

### Definition of Done

- Final smoke tests added.
- All Phase 19 tests pass.
- Route list reviewed.
- No API docs added in this phase.
- Коммит: `CONV-312: Add API foundation final smoke tests`

### Files likely touched

```txt
tests/Feature/Api/V1/ApiFoundationSmokeTest.php
tests/Feature/DashboardRouteTest.php optional
routes/api.php optional fixes
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 19 Completion Criteria

Phase 19 завершена, когда:

```txt
- CONV-291–CONV-312 выполнены;
- /api/v1 route group exists;
- /api/v1/health works;
- standard API error contract exists;
- domain exceptions map to stable API error codes;
- api_keys table exists;
- ApiKey model exists;
- API key generation service stores only token hash;
- API auth middleware works;
- revoked keys are rejected;
- last_used_at updates;
- api_access feature gate is enforced;
- basic API rate limiting exists;
- GET /api/v1/converters works;
- GET /api/v1/converters/{source}/{target}/schema works;
- POST /api/v1/files works;
- GET /api/v1/files/{file}/targets works;
- POST /api/v1/conversions/estimate works;
- POST /api/v1/conversions works;
- GET /api/v1/conversions/{conversion} works;
- GET /api/v1/conversions/{conversion}/download works;
- GET /api/v1/credits/balance works;
- API uses existing application actions/services;
- API does not duplicate conversion logic;
- API ownership guards are enforced;
- API happy path test passes;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 19

Без отдельной задачи нельзя:

```txt
- создавать OpenAPI YAML;
- подключать Swagger UI / Redoc;
- ставить Scribe/Scramble;
- делать API developer portal;
- делать API SDK;
- делать webhooks;
- делать OAuth;
- делать team API keys;
- делать API usage dashboard;
- делать API keys UI page;
- добавлять batch conversion API;
- добавлять direct-to-storage upload;
- добавлять chunked uploads;
- менять billing/pricing model;
- менять converter options schemas без причины;
- добавлять новые converters;
- обходить CreateConversionJobAction;
- обходить CreditLedger;
- обходить FeatureAccessService;
- отдавать internal storage paths;
- логировать plain API tokens.
```

---

# 12. Recommended Execution Order

```txt
CONV-291 Create API v1 Route Group
CONV-292 Add Standard API Error Contract
CONV-293 Add API Domain Exception Mapping
CONV-294 Create API Keys Table
CONV-295 Create ApiKey Model And Relations
CONV-296 Create API Key Generation Service
CONV-297 Create API Authentication Middleware
CONV-298 Enforce API Access Feature Gate
CONV-299 Add API Rate Limiting Baseline
CONV-300 Add Converters Index Endpoint
CONV-301 Add Converter Schema Endpoint
CONV-302 Add API File Upload Endpoint
CONV-303 Add File Target Formats Endpoint
CONV-304 Add Conversion Cost Estimate Endpoint
CONV-305 Add Create Conversion Endpoint
CONV-306 Add Conversion Status Endpoint
CONV-307 Add Conversion Result Download Endpoint
CONV-308 Add Credits Balance Endpoint
CONV-309 Add API Resource Response DTOs
CONV-310 Add API Ownership Guards
CONV-311 Add API Happy Path Feature Test
CONV-312 Add API Foundation Final Smoke Tests
```

---

# 13. Release

После завершения Phase 19:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan route:list --path=api/v1

php artisan migrate:fresh --seed
composer test

git checkout -b release/v0.1.19-phase19-api-foundation
git push -u origin release/v0.1.19-phase19-api-foundation
```

После этого шага сделай MR в `main` branch и после этого остановись.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.19-phase19-api-foundation -m "File Converter Phase 19 API foundation"
git push origin v0.1.19-phase19-api-foundation
```
