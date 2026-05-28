# File Converter — Phase 13 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 13 — Feature Access Service**  
Диапазон задач: **CONV-181 → CONV-197**  
Основа нумерации: Phase 12 завершилась на `CONV-180`, поэтому Phase 13 начинается с `CONV-181`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 13 соответствует блоку:

```txt
Phase 13 — Feature Access Service
```

Правильный диапазон Phase 13:

```txt
CONV-181 — Create PlanFeatures Config
CONV-182 — Create FeatureAccessService Skeleton
CONV-183 — Test Free Plan Feature Access
CONV-184 — Implement Free Plan Feature Access
CONV-185 — Test Pro And Max Feature Access
CONV-186 — Implement Pro And Max Feature Access
CONV-187 — Add PlanLimit DTO
CONV-188 — Test Max File Size Limit Resolution
CONV-189 — Enforce Max File Size Limit In Upload
CONV-190 — Test Storage Usage Calculation
CONV-191 — Implement StorageUsageService
CONV-192 — Test Storage Limit Blocks Upload
CONV-193 — Enforce Storage Limit In Upload
CONV-194 — Test Retention Days Resolution
CONV-195 — Apply Retention Days To Files
CONV-196 — Show Plan Limits In User Dropdown
CONV-197 — Add Feature Access Integration Test
```

Phase 13 добавляет application-level слой доступа к возможностям продукта:

```txt
- доступен ли API;
- доступна ли batch conversion;
- максимальный размер файла;
- storage quota;
- retention days;
- monthly credits value;
- future feature flags для OCR/video/priority queue.
```

Это **не billing-фаза**. Здесь нет Cashier, Stripe, checkout, invoices и списаний credits.

---

# 2. Цель Phase 13

Phase 13 должна отделить продуктовые права и лимиты от платежей.

После Phase 13 приложение должно уметь отвечать на вопросы:

```txt
- какой plan у пользователя;
- разрешён ли пользователю API access;
- разрешена ли batch conversion;
- какой max file size у пользователя;
- сколько storage доступно;
- сколько дней хранить файлы пользователя;
- сколько monthly credits должен получать plan в будущем;
- можно ли загрузить файл с учётом plan limits;
- какой expires_at назначить uploaded/result file.
```

Главное: `FeatureAccessService` должен быть единой точкой правды для feature flags и limits.

---

# 3. Scope Phase 13

## Входит

```txt
- config/features.php или config/plans.php;
- FeatureAccessService;
- PlanLimit DTO;
- tests for free/pro/max feature access;
- max file size limit resolution;
- upload max file size enforcement;
- StorageUsageService;
- storage quota enforcement;
- retention days resolution;
- applying expires_at to uploaded/result files;
- user dropdown display for plan limits;
- integration test covering upload + limits + retention.
```

## Не входит

```txt
- CreditLedger;
- credit_accounts;
- credit_transactions;
- ConversionCostEstimator;
- Laravel Cashier;
- Stripe checkout;
- subscription webhooks;
- credit packs;
- API key management;
- API middleware;
- pricing page;
- billing page;
- admin plan management UI;
- team/workspace permissions.
```

Credit ledger будет в Phase 14.  
Conversion cost estimator будет в Phase 15.  
Cashier будет в Phase 16.

---

# 4. Critical Decisions

## 4.1. Feature access is local application logic

Нельзя спрашивать Stripe/Cashier в hot path upload/conversion.

Неправильно:

```php
$user->subscription()->active();
Stripe::retrieveCustomer(...);
```

внутри upload или conversion flow.

Правильно:

```php
$featureAccess->allows($user, 'api_access');
$featureAccess->limit($user, 'max_file_size_mb');
```

Cashier позже будет только обновлять локальное состояние пользователя/плана.

## 4.2. User plan already exists from Auth Foundation

Phase 02 уже добавила:

```txt
users.plan
Plan enum/cast
```

Phase 13 не должна заново создавать `users.plan`, если оно уже есть.  
Если в реальном коде Phase 02 была реализована иначе, задача должна адаптироваться к фактическому enum/string cast, но не дублировать поле.

## 4.3. Config first, database later

Для MVP plans лучше хранить в config, а не в таблицах.

Правильно для MVP:

```txt
config/feature-access.php
```

Неправильно для MVP:

```txt
plans table
plan_features table
admin UI for plan editing
```

DB-планы нужны позже, когда pricing часто меняется из админки. Сейчас это лишняя сложность.

## 4.4. Plan limits are not billing

`monthly_credits` в config — это параметр plan, но не ledger.

Phase 13 может знать:

```txt
free gives 50 monthly credits
pro gives 1000 monthly credits
max gives 5000 monthly credits
```

Но Phase 13 не должна начислять credits. Это будет Phase 14/16.

## 4.5. Upload must use FeatureAccessService

Нельзя держать max file size только в Livewire validation string.

Плохо:

```php
'file' => 'max:25600'
```

захардкожено в компоненте.

Правильно:

```php
$maxMb = $featureAccess->limit($user, 'max_file_size_mb');
```

и уже из него строится validation/error message.

## 4.6. Storage quota must count active files only

Storage usage должен считать файлы пользователя, которые ещё занимают место.

Правило Phase 13:

```txt
include files where status is not deleted/expired
include uploaded source files
include result files
exclude expired/deleted files
```

Если physical file удалён, он не должен продолжать занимать quota.

## 4.7. Retention applies to uploaded and result files

`expires_at` должен назначаться:

```txt
- source uploaded files;
- result converted files.
```

Retention зависит от plan:

```txt
free: 1 day
pro: 30 days
max: 90 days
```

Cleanup job будет позже. Phase 13 только правильно выставляет `expires_at`.

---

# 5. Architecture Rules

## 5.1. Single access point

Весь код должен использовать:

```php
FeatureAccessService
```

Нельзя размазывать проверки по проекту:

```php
if ($user->plan === 'pro') ...
if ($user->plan === 'max') ...
```

Такие проверки быстро станут неуправляемыми.

## 5.2. Feature keys must be constants or enum-like

Не плодить magic strings по всему коду.

Допустимо в MVP:

```php
final class Features
{
    public const API_ACCESS = 'api_access';
    public const BATCH_CONVERSION = 'batch_conversion';
}
```

или enum, если проект уже использует enum-style.

## 5.3. Limit keys must be constants or enum-like

То же для limits:

```php
final class PlanLimits
{
    public const MAX_FILE_SIZE_MB = 'max_file_size_mb';
    public const STORAGE_MB = 'storage_mb';
    public const RETENTION_DAYS = 'retention_days';
    public const MONTHLY_CREDITS = 'monthly_credits';
}
```

## 5.4. No direct billing coupling

FeatureAccessService не должен зависеть от:

```txt
Cashier
Stripe
CreditLedger
ConversionCostEstimator
```

Он зависит только от:

```txt
User
Plan enum/string
config
```

## 5.5. Errors must be domain-specific

Для limit failures использовать явные exceptions:

```txt
FeatureNotAvailableException
PlanLimitExceededException
FileTooLargeForPlanException
StorageLimitExceededException
```

Если exception classes уже созданы в hardening phase позже, в Phase 13 можно создать минимальные exceptions для использования сейчас.

---

# 6. GitFlow для Phase 13

## Base branch

Все задачи Phase 13 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-181-create-plan-features-config
feature/CONV-189-enforce-max-file-size-limit-in-upload
feature/CONV-196-show-plan-limits-in-user-dropdown
```

## Commit format

```txt
CONV-181: Create PlanFeatures config
CONV-189: Enforce max file size limit in upload
CONV-196: Show plan limits in user dropdown
```

## Release branch

После выполнения `CONV-181`–`CONV-197`:

```txt
release/v0.1.13-phase13-feature-access-service
```

## Tag

После merge release branch в `main`:

```txt
v0.1.13-phase13-feature-access-service
```

---

# 7. TDD Rules for Phase 13

## Для FeatureAccessService

Test-first:

```txt
- free plan has no api_access;
- free plan has no batch_conversion;
- pro plan has api_access;
- max plan has api_access;
- unknown feature returns false or throws explicit exception;
- unknown limit throws explicit exception.
```

## Для limits

Test-first:

```txt
- max_file_size_mb resolves per plan;
- storage_mb resolves per plan;
- retention_days resolves per plan;
- monthly_credits resolves per plan.
```

## Для upload enforcement

Test-first:

```txt
- free user cannot upload file above free max_file_size_mb;
- pro user can upload larger file according to pro limit;
- error message includes plan limit and actual file size.
```

## Для storage quota

Test-first:

```txt
- storage usage sums active files;
- expired/deleted files excluded;
- upload blocked when quota exceeded.
```

## Для retention

Test-first:

```txt
- uploaded file gets expires_at based on current plan;
- result file gets expires_at based on current plan.
```

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Feature Access / Limits / Upload / UI / Tests
Type: Test / Feature / Config / Service / UI
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
- Нет billing/Cashier/credits вне scope задачи
- Нет прямых `$user->plan === ...` checks вне FeatureAccessService/tests
- Коммит содержит ID задачи

Files likely touched:
- path/to/file
```

---

# 9. Phase 13 Atomic Tasks

---

## CONV-181 — Create PlanFeatures Config

**Area:** Feature Access / Config  
**Type:** Config  
**Priority:** P0  
**Branch:** `feature/CONV-181-create-plan-features-config`  
**Base branch:** `develop`  
**Depends on:** CONV-180

### Goal

Создать config, описывающий feature flags и limits для `free`, `pro`, `max`.

### TDD step

Config shape test:

```php
it('defines feature access config for all plans', function () {
    $config = config('feature-access.plans');

    expect($config)->toHaveKeys(['free', 'pro', 'max']);

    foreach (['free', 'pro', 'max'] as $plan) {
        expect($config[$plan])->toHaveKeys([
            'features',
            'limits',
        ]);
    }
});
```

Тест должен упасть до создания config.

### Implementation

Создать:

```txt
config/feature-access.php
```

Пример структуры:

```php
return [
    'plans' => [
        'free' => [
            'features' => [
                'api_access' => false,
                'batch_conversion' => false,
                'priority_queue' => false,
                'ocr' => false,
                'video_conversion' => false,
            ],
            'limits' => [
                'max_file_size_mb' => 25,
                'storage_mb' => 250,
                'retention_days' => 1,
                'monthly_credits' => 50,
            ],
        ],
        'pro' => [
            'features' => [
                'api_access' => true,
                'batch_conversion' => true,
                'priority_queue' => false,
                'ocr' => true,
                'video_conversion' => false,
            ],
            'limits' => [
                'max_file_size_mb' => 250,
                'storage_mb' => 10000,
                'retention_days' => 30,
                'monthly_credits' => 1000,
            ],
        ],
        'max' => [
            'features' => [
                'api_access' => true,
                'batch_conversion' => true,
                'priority_queue' => true,
                'ocr' => true,
                'video_conversion' => true,
            ],
            'limits' => [
                'max_file_size_mb' => 2000,
                'storage_mb' => 100000,
                'retention_days' => 90,
                'monthly_credits' => 5000,
            ],
        ],
    ],
];
```

### Acceptance criteria

- Config file exists.
- Plans `free`, `pro`, `max` exist.
- Each plan has `features` and `limits`.
- Free plan has API disabled.
- Pro/Max have API enabled.
- Test passes.

### Definition of Done

- Config shape test written first.
- Config implemented.
- `composer test` passes.
- Коммит: `CONV-181: Create PlanFeatures config`

### Files likely touched

```txt
config/feature-access.php
tests/Unit/FeatureAccess/FeatureAccessConfigTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-182 — Create FeatureAccessService Skeleton

**Area:** Feature Access / Service  
**Type:** Service  
**Priority:** P0  
**Branch:** `feature/CONV-182-create-feature-access-service-skeleton`  
**Base branch:** `develop`  
**Depends on:** CONV-181

### Goal

Создать skeleton `FeatureAccessService` как единую точку проверки features и limits.

### TDD step

Unit test:

```php
it('has feature access service with allows and limit methods', function () {
    $service = app(FeatureAccessService::class);

    expect(method_exists($service, 'allows'))->toBeTrue();
    expect(method_exists($service, 'limit'))->toBeTrue();
});
```

Тест должен упасть до создания service.

### Implementation

Создать:

```txt
app/Services/FeatureAccess/FeatureAccessService.php
```

Skeleton:

```php
namespace App\Services\FeatureAccess;

use App\Models\User;

final class FeatureAccessService
{
    public function allows(User $user, string $feature): bool
    {
        throw new \LogicException('Not implemented yet.');
    }

    public function limit(User $user, string $limit): int|string|null
    {
        throw new \LogicException('Not implemented yet.');
    }
}
```

Не реализовывать бизнес-логику в этой задаче.

### Acceptance criteria

- `FeatureAccessService` exists.
- Service resolves from container.
- Methods `allows` and `limit` exist.
- No feature logic yet.
- Skeleton test passes.

### Definition of Done

- Тест написан первым.
- Skeleton создан.
- Test passes.
- Коммит: `CONV-182: Create FeatureAccessService skeleton`

### Files likely touched

```txt
app/Services/FeatureAccess/FeatureAccessService.php
tests/Unit/FeatureAccess/FeatureAccessServiceSkeletonTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-183 — Test Free Plan Feature Access

**Area:** Feature Access / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-183-test-free-plan-feature-access`  
**Base branch:** `develop`  
**Depends on:** CONV-182

### Goal

Написать падающие тесты для feature access на free plan.

### TDD step

Unit tests:

```php
it('does not allow api access for free plan', function () {
    $user = User::factory()->create(['plan' => Plan::Free]);

    expect(app(FeatureAccessService::class)->allows($user, 'api_access'))->toBeFalse();
});
```

```php
it('does not allow batch conversion for free plan', function () {
    $user = User::factory()->create(['plan' => Plan::Free]);

    expect(app(FeatureAccessService::class)->allows($user, 'batch_conversion'))->toBeFalse();
});
```

Тесты должны упасть до CONV-184.

### Implementation

Только добавить тесты.

Если `Plan::Free` enum value называется иначе, адаптировать тест к существующему enum из Phase 02.

### Acceptance criteria

- Tests for free plan exist.
- API access expected false.
- Batch conversion expected false.
- Tests fail before implementation.

### Definition of Done

- Тесты добавлены.
- Тесты ожидаемо падают.
- Коммит: `CONV-183: Test free plan feature access`

### Files likely touched

```txt
tests/Unit/FeatureAccess/FeatureAccessServiceTest.php
```

После этого сделай MR в `develop`. Если проект требует always-green mainline, объединить CONV-183 и CONV-184 в один MR с двумя коммитами.

---

## CONV-184 — Implement Free Plan Feature Access

**Area:** Feature Access / Service  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-184-implement-free-plan-feature-access`  
**Base branch:** `develop`  
**Depends on:** CONV-183

### Goal

Реализовать чтение feature flags для free plan через config.

### TDD step

Использовать падающие тесты из CONV-183.

### Implementation

В `FeatureAccessService::allows()`:

```php
public function allows(User $user, string $feature): bool
{
    $plan = $this->planKey($user);

    return (bool) config("feature-access.plans.$plan.features.$feature", false);
}
```

Добавить private helper:

```php
private function planKey(User $user): string
{
    $plan = $user->plan;

    return $plan instanceof \BackedEnum ? $plan->value : (string) $plan;
}
```

Не добавлять limit logic, если она ещё не нужна для тестов.

### Acceptance criteria

- Free API access returns false.
- Free batch conversion returns false.
- Unknown feature returns false.
- Service reads config.
- Tests pass.

### Definition of Done

- Minimal implementation added.
- Tests from CONV-183 pass.
- No hardcoded `$user->plan === 'free'` outside helper.
- Коммит: `CONV-184: Implement free plan feature access`

### Files likely touched

```txt
app/Services/FeatureAccess/FeatureAccessService.php
tests/Unit/FeatureAccess/FeatureAccessServiceTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-185 — Test Pro And Max Feature Access

**Area:** Feature Access / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-185-test-pro-and-max-feature-access`  
**Base branch:** `develop`  
**Depends on:** CONV-184

### Goal

Написать тесты для `pro` и `max` feature flags.

### TDD step

Tests:

```php
it('allows api access for pro plan', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);

    expect(app(FeatureAccessService::class)->allows($user, 'api_access'))->toBeTrue();
});
```

```php
it('allows priority queue only for max plan', function () {
    $pro = User::factory()->create(['plan' => Plan::Pro]);
    $max = User::factory()->create(['plan' => Plan::Max]);

    $service = app(FeatureAccessService::class);

    expect($service->allows($pro, 'priority_queue'))->toBeFalse();
    expect($service->allows($max, 'priority_queue'))->toBeTrue();
});
```

Если `Plan::Pro`/`Plan::Max` ещё не существуют, это значит Phase 02 была неполной. Тогда задача должна добавить enum cases в рамках implementation, но не создавать `users.plan` заново.

### Implementation

Только добавить тесты.

### Acceptance criteria

- Pro API access expected true.
- Max API access expected true.
- Priority queue expected true only for max.
- Tests expose missing enum cases/config mistakes.

### Definition of Done

- Тесты добавлены.
- Тесты ожидаемо падают, если implementation/config incomplete.
- Коммит: `CONV-185: Test pro and max feature access`

### Files likely touched

```txt
tests/Unit/FeatureAccess/FeatureAccessServiceTest.php
```

После этого сделай MR в `develop`. Если проект требует always-green mainline, объединить CONV-185 и CONV-186 в один MR с двумя коммитами.

---

## CONV-186 — Implement Pro And Max Feature Access

**Area:** Feature Access / Service  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-186-implement-pro-and-max-feature-access`  
**Base branch:** `develop`  
**Depends on:** CONV-185

### Goal

Довести feature access для `pro` и `max` до рабочего состояния.

### TDD step

Использовать падающие тесты из CONV-185.

### Implementation

Проверить:

```txt
- Plan enum has Free/Pro/Max;
- config contains free/pro/max;
- FeatureAccessService correctly resolves enum-backed plan keys.
```

Если enum values в проекте lower-case:

```php
enum Plan: string
{
    case Free = 'free';
    case Pro = 'pro';
    case Max = 'max';
}
```

FeatureAccessService должен работать с enum и string.

### Acceptance criteria

- Pro API access returns true.
- Max API access returns true.
- Pro priority queue returns false.
- Max priority queue returns true.
- Tests pass.

### Definition of Done

- Pro/Max access works.
- Tests pass.
- No direct feature checks outside service.
- Коммит: `CONV-186: Implement pro and max feature access`

### Files likely touched

```txt
app/Enums/Plan.php
app/Services/FeatureAccess/FeatureAccessService.php
config/feature-access.php
tests/Unit/FeatureAccess/FeatureAccessServiceTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-187 — Add PlanLimit DTO

**Area:** Feature Access / Limits  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-187-add-plan-limit-dto`  
**Base branch:** `develop`  
**Depends on:** CONV-186

### Goal

Добавить typed DTO для возвращения лимитов plan, чтобы не передавать raw arrays по приложению.

### TDD step

Unit test:

```php
it('creates plan limit dto from array', function () {
    $dto = PlanLimit::fromArray([
        'max_file_size_mb' => 25,
        'storage_mb' => 250,
        'retention_days' => 1,
        'monthly_credits' => 50,
    ]);

    expect($dto->maxFileSizeMb)->toBe(25);
    expect($dto->storageMb)->toBe(250);
    expect($dto->retentionDays)->toBe(1);
    expect($dto->monthlyCredits)->toBe(50);
});
```

Тест должен упасть до создания DTO.

### Implementation

Создать:

```txt
app/Services/FeatureAccess/PlanLimit.php
```

Пример:

```php
final readonly class PlanLimit
{
    public function __construct(
        public int $maxFileSizeMb,
        public int $storageMb,
        public int $retentionDays,
        public int $monthlyCredits,
    ) {}

    public static function fromArray(array $limits): self
    {
        return new self(
            maxFileSizeMb: (int) $limits['max_file_size_mb'],
            storageMb: (int) $limits['storage_mb'],
            retentionDays: (int) $limits['retention_days'],
            monthlyCredits: (int) $limits['monthly_credits'],
        );
    }
}
```

### Acceptance criteria

- `PlanLimit` exists.
- DTO is readonly/immutable.
- DTO maps config keys to typed properties.
- Test passes.

### Definition of Done

- DTO test written first.
- DTO implemented.
- Test passes.
- Коммит: `CONV-187: Add PlanLimit DTO`

### Files likely touched

```txt
app/Services/FeatureAccess/PlanLimit.php
tests/Unit/FeatureAccess/PlanLimitTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-188 — Test Max File Size Limit Resolution

**Area:** Feature Access / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-188-test-max-file-size-limit-resolution`  
**Base branch:** `develop`  
**Depends on:** CONV-187

### Goal

Написать тесты: FeatureAccessService корректно возвращает лимиты по plan.

### TDD step

Tests:

```php
it('resolves max file size limit for free plan', function () {
    $user = User::factory()->create(['plan' => Plan::Free]);

    expect(app(FeatureAccessService::class)->limit($user, 'max_file_size_mb'))->toBe(25);
});
```

```php
it('resolves all plan limits as dto', function () {
    $user = User::factory()->create(['plan' => Plan::Pro]);

    $limits = app(FeatureAccessService::class)->limits($user);

    expect($limits)->toBeInstanceOf(PlanLimit::class);
    expect($limits->maxFileSizeMb)->toBe(250);
});
```

Тесты должны упасть до реализации `limit()`/`limits()`.

### Implementation

Только добавить тесты.

### Acceptance criteria

- Tests for `limit()` exist.
- Tests for `limits()` DTO exist.
- Free max file size expected 25 MB.
- Pro max file size expected 250 MB.
- Tests fail before implementation.

### Definition of Done

- Тесты добавлены.
- Тесты ожидаемо падают.
- Коммит: `CONV-188: Test max file size limit resolution`

### Files likely touched

```txt
tests/Unit/FeatureAccess/FeatureAccessServiceLimitsTest.php
```

После этого сделай MR в `develop`. Если проект требует always-green mainline, объединить CONV-188 и CONV-189 в один MR с двумя коммитами.

---

## CONV-189 — Enforce Max File Size Limit In Upload

**Area:** Feature Access / Upload  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-189-enforce-max-file-size-limit-in-upload`  
**Base branch:** `develop`  
**Depends on:** CONV-188

### Goal

Реализовать limits в FeatureAccessService и использовать max file size limit в upload flow.

### TDD step

Использовать падающие tests из CONV-188.

Добавить feature test для upload:

```php
it('rejects upload above plan max file size', function () {
    $user = User::factory()->create(['plan' => Plan::Free]);

    $file = UploadedFile::fake()->create('large.png', 26 * 1024, 'image/png');

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', $file)
        ->call('storeUpload')
        ->assertHasErrors(['upload']);
});
```

Если Livewire method names differ, адаптировать под текущий компонент Phase 06.

### Implementation

В `FeatureAccessService`:

```php
public function limit(User $user, string $limit): int|string|null
{
    $plan = $this->planKey($user);

    return config("feature-access.plans.$plan.limits.$limit");
}

public function limits(User $user): PlanLimit
{
    $plan = $this->planKey($user);

    return PlanLimit::fromArray(config("feature-access.plans.$plan.limits"));
}
```

В upload validation/action добавить plan-aware max size:

```php
$maxMb = app(FeatureAccessService::class)->limits($user)->maxFileSizeMb;
```

Laravel validation `max` для files принимает KB:

```php
'upload' => ['required', 'file', 'max:' . ($maxMb * 1024)]
```

Ошибка должна быть понятной:

```txt
This file is too large for your current plan. Max size: 25 MB.
```

### Acceptance criteria

- `FeatureAccessService::limit()` works.
- `FeatureAccessService::limits()` returns PlanLimit DTO.
- Free max file size is enforced in upload flow.
- Error message includes plan max size.
- Valid files still upload.
- Tests pass.

### Definition of Done

- Limits implemented.
- Upload validation uses FeatureAccessService.
- No hardcoded max file size in component.
- Tests pass.
- Коммит: `CONV-189: Enforce max file size limit in upload`

### Files likely touched

```txt
app/Services/FeatureAccess/FeatureAccessService.php
app/Livewire/DashboardConverter.php
app/Actions/Files/StoreUploadedFileAction.php
tests/Unit/FeatureAccess/FeatureAccessServiceLimitsTest.php
tests/Feature/Livewire/DashboardUploadFlowTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-190 — Test Storage Usage Calculation

**Area:** Feature Access / Storage / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-190-test-storage-usage-calculation`  
**Base branch:** `develop`  
**Depends on:** CONV-189

### Goal

Написать тесты для расчёта storage usage пользователя.

### TDD step

Unit/feature tests:

```php
it('calculates active storage usage for user files', function () {
    $user = User::factory()->create();

    FileRecord::factory()->for($user)->create([
        'size_bytes' => 1000,
        'status' => FileStatus::Analyzed,
    ]);

    FileRecord::factory()->for($user)->create([
        'size_bytes' => 2000,
        'status' => FileStatus::Analyzed,
    ]);

    expect(app(StorageUsageService::class)->usedBytes($user))->toBe(3000);
});
```

```php
it('excludes expired and deleted files from storage usage', function () {
    $user = User::factory()->create();

    FileRecord::factory()->for($user)->create(['size_bytes' => 1000, 'status' => FileStatus::Analyzed]);
    FileRecord::factory()->for($user)->create(['size_bytes' => 5000, 'status' => FileStatus::Expired]);
    FileRecord::factory()->for($user)->create(['size_bytes' => 7000, 'status' => FileStatus::Deleted]);

    expect(app(StorageUsageService::class)->usedBytes($user))->toBe(1000);
});
```

Тесты должны упасть до CONV-191.

### Implementation

Только добавить тесты.

### Acceptance criteria

- Tests for active usage exist.
- Expired/deleted files expected excluded.
- Usage is scoped by user.
- Tests fail before service exists.

### Definition of Done

- Тесты добавлены.
- Тесты ожидаемо падают.
- Коммит: `CONV-190: Test storage usage calculation`

### Files likely touched

```txt
tests/Unit/Storage/StorageUsageServiceTest.php
```

После этого сделай MR в `develop`. Если проект требует always-green mainline, объединить CONV-190 и CONV-191 в один MR с двумя коммитами.

---

## CONV-191 — Implement StorageUsageService

**Area:** Feature Access / Storage  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-191-implement-storage-usage-service`  
**Base branch:** `develop`  
**Depends on:** CONV-190

### Goal

Реализовать `StorageUsageService`, считающий активное storage usage пользователя.

### TDD step

Использовать падающие тесты из CONV-190.

### Implementation

Создать:

```txt
app/Services/Storage/StorageUsageService.php
```

Пример:

```php
final class StorageUsageService
{
    public function usedBytes(User $user): int
    {
        return (int) FileRecord::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', [
                FileStatus::Expired,
                FileStatus::Deleted,
            ])
            ->sum('size_bytes');
    }

    public function usedMegabytes(User $user): float
    {
        return round($this->usedBytes($user) / 1024 / 1024, 2);
    }
}
```

Если `FileStatus` backed enum используется в DB, убедиться, что query correctly serializes enum values.

### Acceptance criteria

- Service exists.
- Sums only current user's files.
- Excludes expired/deleted.
- Returns bytes and MB helper.
- Tests pass.

### Definition of Done

- StorageUsageService implemented.
- Tests pass.
- Коммит: `CONV-191: Implement StorageUsageService`

### Files likely touched

```txt
app/Services/Storage/StorageUsageService.php
tests/Unit/Storage/StorageUsageServiceTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-192 — Test Storage Limit Blocks Upload

**Area:** Feature Access / Upload / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-192-test-storage-limit-blocks-upload`  
**Base branch:** `develop`  
**Depends on:** CONV-191

### Goal

Написать тест: upload блокируется, если файл превышает storage quota пользователя.

### TDD step

Feature test:

```php
it('rejects upload when storage quota would be exceeded', function () {
    $user = User::factory()->create(['plan' => Plan::Free]);

    FileRecord::factory()->for($user)->create([
        'size_bytes' => 249 * 1024 * 1024,
        'status' => FileStatus::Analyzed,
    ]);

    $file = UploadedFile::fake()->create('image.png', 2 * 1024, 'image/png');

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', $file)
        ->call('storeUpload')
        ->assertHasErrors(['upload']);
});
```

Free storage limit в config = 250 MB, поэтому 249 MB + 2 MB должно быть rejected.

### Implementation

Только добавить тест.

### Acceptance criteria

- Test exists.
- Current usage + upload size compared to plan storage limit.
- Upload expected rejected.
- Error field is upload/file field.
- Test fails before enforcement.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-192: Test storage limit blocks upload`

### Files likely touched

```txt
tests/Feature/Livewire/DashboardUploadFlowTest.php
tests/Feature/Files/StoreUploadedFileActionTest.php
```

После этого сделай MR в `develop`. Если проект требует always-green mainline, объединить CONV-192 и CONV-193 в один MR с двумя коммитами.

---

## CONV-193 — Enforce Storage Limit In Upload

**Area:** Feature Access / Upload  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-193-enforce-storage-limit-in-upload`  
**Base branch:** `develop`  
**Depends on:** CONV-192

### Goal

Блокировать upload, если `current storage usage + new file size` превышает plan storage limit.

### TDD step

Использовать падающий тест из CONV-192.

### Implementation

Добавить проверку в upload flow до сохранения файла или сразу перед финальным сохранением.

Рекомендуемый уровень: `StoreUploadedFileAction`, потому что API upload позже должен использовать тот же action.

Пример:

```php
$limits = $featureAccess->limits($user);
$limitBytes = $limits->storageMb * 1024 * 1024;
$usedBytes = $storageUsage->usedBytes($user);
$newFileBytes = $uploadedFile->getSize();

if ($usedBytes + $newFileBytes > $limitBytes) {
    throw StorageLimitExceededException::make($limits->storageMb, $usedBytes, $newFileBytes);
}
```

Создать exception:

```txt
app/Exceptions/Storage/StorageLimitExceededException.php
```

UI должен маппить exception в readable validation error.

### Acceptance criteria

- Upload blocked when quota exceeded.
- Upload allowed when quota not exceeded.
- Check lives in application action, not only Livewire UI.
- Exception is explicit.
- Tests pass.

### Definition of Done

- Storage quota enforcement added.
- Exception added.
- UI maps error cleanly.
- Tests pass.
- Коммит: `CONV-193: Enforce storage limit in upload`

### Files likely touched

```txt
app/Actions/Files/StoreUploadedFileAction.php
app/Exceptions/Storage/StorageLimitExceededException.php
app/Livewire/DashboardConverter.php
app/Services/Storage/StorageUsageService.php
tests/Feature/Files/StoreUploadedFileActionTest.php
tests/Feature/Livewire/DashboardUploadFlowTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-194 — Test Retention Days Resolution

**Area:** Feature Access / Retention / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-194-test-retention-days-resolution`  
**Base branch:** `develop`  
**Depends on:** CONV-193

### Goal

Написать тесты: retention days корректно определяется по plan.

### TDD step

Tests:

```php
it('resolves retention days for free plan', function () {
    $user = User::factory()->create(['plan' => Plan::Free]);

    expect(app(FeatureAccessService::class)->limits($user)->retentionDays)->toBe(1);
});
```

```php
it('resolves retention days for pro and max plans', function () {
    $pro = User::factory()->create(['plan' => Plan::Pro]);
    $max = User::factory()->create(['plan' => Plan::Max]);

    $service = app(FeatureAccessService::class);

    expect($service->limits($pro)->retentionDays)->toBe(30);
    expect($service->limits($max)->retentionDays)->toBe(90);
});
```

Если эти tests уже проходят после CONV-189, это нормально. Добавить их как explicit coverage.

### Implementation

Только добавить тесты.

### Acceptance criteria

- Free retention = 1 day.
- Pro retention = 30 days.
- Max retention = 90 days.
- Tests exist.

### Definition of Done

- Тесты добавлены.
- Tests pass or expose config bug.
- Коммит: `CONV-194: Test retention days resolution`

### Files likely touched

```txt
tests/Unit/FeatureAccess/FeatureAccessServiceLimitsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-195 — Apply Retention Days To Files

**Area:** Feature Access / Retention / Files  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-195-apply-retention-days-to-files`  
**Base branch:** `develop`  
**Depends on:** CONV-194

### Goal

Назначать `expires_at` для uploaded files и result files на основе plan retention days.

### TDD step

Feature tests:

```php
it('sets uploaded file expiration based on user plan', function () {
    Carbon::setTestNow('2026-01-01 10:00:00');

    $user = User::factory()->create(['plan' => Plan::Free]);
    $file = UploadedFile::fake()->image('image.png');

    $record = app(StoreUploadedFileAction::class)->handle($user, $file);

    expect($record->expires_at->toDateTimeString())->toBe('2026-01-02 10:00:00');
});
```

Result file test using fake completed conversion:

```php
it('sets result file expiration based on user plan', function () {
    Carbon::setTestNow('2026-01-01 10:00:00');

    $user = User::factory()->create(['plan' => Plan::Pro]);

    // create/process job with fake driver

    expect($resultFile->expires_at->toDateString())->toBe('2026-01-31');
});
```

### Implementation

В `StoreUploadedFileAction`:

```php
'expires_at' => now()->addDays($featureAccess->limits($user)->retentionDays),
```

В `ProcessConversionJob` при создании result FileRecord использовать owner user и тот же retention.

Если `result_file_id` создаётся через отдельный action, retention logic лучше вынести туда.

### Acceptance criteria

- Uploaded source file gets expires_at.
- Result converted file gets expires_at.
- Retention uses FeatureAccessService.
- Free/pro/max behave according to config.
- Tests pass.

### Definition of Done

- Retention applied to source files.
- Retention applied to result files.
- Tests pass.
- Коммит: `CONV-195: Apply retention days to files`

### Files likely touched

```txt
app/Actions/Files/StoreUploadedFileAction.php
app/Jobs/ProcessConversionJob.php
app/Services/FeatureAccess/FeatureAccessService.php
tests/Feature/Files/StoreUploadedFileActionTest.php
tests/Feature/Conversion/ProcessConversionJobTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-196 — Show Plan Limits In User Dropdown

**Area:** UI / Feature Access  
**Type:** UI  
**Priority:** P1  
**Branch:** `feature/CONV-196-show-plan-limits-in-user-dropdown`  
**Base branch:** `develop`  
**Depends on:** CONV-195

### Goal

Показать пользователю текущий plan и основные лимиты в dropdown.

### TDD step

Livewire/Blade render test:

```php
it('shows user plan limits in user dropdown', function () {
    $user = User::factory()->create(['plan' => Plan::Free]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSee('Free')
        ->assertSee('25 MB')
        ->assertSee('1 day');
});
```

Если dropdown скрыт через Alpine и content всё равно присутствует в DOM, test с `assertSee` работает. Если content lazy-rendered, использовать component-level test.

### Implementation

В user dropdown добавить compact block:

```txt
Plan: Free
Max file: 25 MB
Storage: 250 MB
Retention: 1 day
API: Not included
```

Использовать `FeatureAccessService`, а не прямой config access в Blade.

Если dropdown сейчас Blade-only, можно передавать данные из layout composer/component.

### Acceptance criteria

- Current plan visible.
- Max file size visible.
- Storage limit visible.
- Retention visible.
- API included/not included visible.
- Data comes from FeatureAccessService.
- Test passes.

### Definition of Done

- UI updated.
- No direct config lookups in Blade.
- Test passes.
- Коммит: `CONV-196: Show plan limits in user dropdown`

### Files likely touched

```txt
resources/views/components/user-dropdown.blade.php
app/View/Components/UserDropdown.php
app/Livewire/UserDropdown.php
tests/Feature/DashboardRouteTest.php
tests/Feature/Livewire/UserDropdownTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-197 — Add Feature Access Integration Test

**Area:** Feature Access / Integration / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-197-add-feature-access-integration-test`  
**Base branch:** `develop`  
**Depends on:** CONV-196

### Goal

Добавить один интеграционный тест, который проверяет, что feature access реально влияет на upload flow и user-visible UI.

### TDD step

Integration test:

```php
it('applies free plan limits across dashboard and upload flow', function () {
    $user = User::factory()->create(['plan' => Plan::Free]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Free')
        ->assertSee('25 MB');

    $validFile = UploadedFile::fake()->image('valid.png');

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', $validFile)
        ->call('storeUpload')
        ->assertSet('step', 'format');

    $tooLargeFile = UploadedFile::fake()->create('too-large.png', 26 * 1024, 'image/png');

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', $tooLargeFile)
        ->call('storeUpload')
        ->assertHasErrors(['upload']);
});
```

### Implementation

Только добавить integration test и исправить мелкие wiring issues, если тест обнаружит расхождения.

Не добавлять новую бизнес-логику, если она не связана с провалившимся интеграционным тестом.

### Acceptance criteria

- Dashboard displays plan information.
- Valid file within free limit uploads.
- Too-large file is rejected.
- FeatureAccessService participates in flow.
- Test passes.

### Definition of Done

- Integration test added.
- Any wiring bugs fixed minimally.
- All Phase 13 tests pass.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.
- Коммит: `CONV-197: Add feature access integration test`

### Files likely touched

```txt
tests/Feature/FeatureAccess/FeatureAccessIntegrationTest.php
app/Livewire/DashboardConverter.php
resources/views/components/user-dropdown.blade.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 13 Completion Criteria

Phase 13 завершена, когда:

```txt
- CONV-181–CONV-197 выполнены;
- feature-access config exists;
- free/pro/max plans have features and limits;
- FeatureAccessService exists;
- FeatureAccessService::allows() works;
- FeatureAccessService::limit()/limits() works;
- PlanLimit DTO exists;
- max file size is enforced in upload flow;
- StorageUsageService exists;
- storage usage excludes expired/deleted files;
- upload is blocked when storage quota would be exceeded;
- uploaded files get expires_at by plan;
- result files get expires_at by plan;
- user dropdown shows current plan and limits;
- no Cashier installed;
- no CreditLedger added;
- no Stripe code added;
- no API middleware added;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 13

Без отдельной задачи нельзя:

```txt
- устанавливать Laravel Cashier;
- добавлять Stripe env;
- создавать checkout routes;
- создавать billing page;
- создавать credit_accounts;
- создавать credit_transactions;
- списывать credits;
- начислять monthly credits;
- покупать credit packs;
- создавать API keys;
- добавлять API access middleware;
- добавлять pricing page logic;
- добавлять admin plan editor;
- добавлять teams/workspaces;
- добавлять usage analytics dashboards;
- добавлять cleanup job для expired files;
- добавлять Redis/cache layer для limits.
```

---

# 12. Recommended Execution Order

```txt
CONV-181 Create PlanFeatures Config
CONV-182 Create FeatureAccessService Skeleton
CONV-183 Test Free Plan Feature Access
CONV-184 Implement Free Plan Feature Access
CONV-185 Test Pro And Max Feature Access
CONV-186 Implement Pro And Max Feature Access
CONV-187 Add PlanLimit DTO
CONV-188 Test Max File Size Limit Resolution
CONV-189 Enforce Max File Size Limit In Upload
CONV-190 Test Storage Usage Calculation
CONV-191 Implement StorageUsageService
CONV-192 Test Storage Limit Blocks Upload
CONV-193 Enforce Storage Limit In Upload
CONV-194 Test Retention Days Resolution
CONV-195 Apply Retention Days To Files
CONV-196 Show Plan Limits In User Dropdown
CONV-197 Add Feature Access Integration Test
```

---

# 13. Release

После завершения Phase 13:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh --seed

git checkout -b release/v0.1.13-phase13-feature-access-service
git push -u origin release/v0.1.13-phase13-feature-access-service
```

После этого сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.13-phase13-feature-access-service -m "File Converter Phase 13 feature access service"
git push origin v0.1.13-phase13-feature-access-service
```
