# File Converter — Phase 15 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 15 — Conversion Cost Estimator**  
Диапазон задач: **CONV-215 → CONV-235**  
Основа нумерации: Phase 14 завершилась на `CONV-214`, поэтому Phase 15 начинается с `CONV-215`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 15 соответствует блоку:

```txt
Phase 15 — Conversion Cost Estimator
```

Правильный диапазон Phase 15:

```txt
CONV-215 — Create CreditCost DTO
CONV-216 — Create CreditCostBreakdown DTO
CONV-217 — Create ConversionCostEstimator Contract
CONV-218 — Create ConfigDrivenConversionCostEstimator Skeleton
CONV-219 — Add Conversion Costs Config
CONV-220 — Test Image To Image Cost
CONV-221 — Implement Image To Image Cost
CONV-222 — Test Image To PDF Cost
CONV-223 — Implement Image To PDF Cost
CONV-224 — Test Cost Breakdown Shape
CONV-225 — Implement Cost Breakdown
CONV-226 — Test Unsupported Cost Estimation Is Rejected
CONV-227 — Create EstimateConversionCostAction
CONV-228 — Create ConversionCreditCharge Model And Migration
CONV-229 — Test Conversion Job Checks Credits Before Queue
CONV-230 — Enforce Credits In CreateConversionJobAction
CONV-231 — Test Credits Captured After Successful Conversion
CONV-232 — Capture Credits On Successful Conversion
CONV-233 — Test Failed Conversion Does Not Spend Credits
CONV-234 — Show Real Estimated Cost In Settings Step
CONV-235 — Add Insufficient Credits UI State
```

Phase 15 связывает конвертационное ядро с кредитной системой.

Это **не Cashier-фаза** и **не Stripe-фаза**. Здесь нет checkout, subscriptions, invoices, credit packs, webhooks и платежных экранов.

---

# 2. Цель Phase 15

Phase 15 должна добавить расчёт стоимости конвертации в credits и начать применять этот расчёт перед запуском conversion job.

После Phase 15 приложение должно уметь:

```txt
- считать стоимость конвертации до запуска job;
- возвращать cost breakdown;
- показывать estimated credits в UI;
- блокировать конвертацию при недостаточном балансе;
- создавать product-level charge record для conversion job;
- списывать credits только после успешной конвертации;
- не списывать credits при failed conversion;
- показывать пользователю понятное сообщение о нехватке credits.
```

Главная идея: `CreditLedger` из Phase 14 хранит баланс и транзакции, а Phase 15 решает, **сколько стоит конкретная операция и когда списывать credits**.

---

# 3. Scope Phase 15

## Входит

```txt
- CreditCost DTO;
- CreditCostBreakdown DTO;
- ConversionCostEstimator contract;
- ConfigDrivenConversionCostEstimator implementation;
- conversion_costs config;
- MVP pricing rules;
- EstimateConversionCostAction;
- conversion_credit_charges table;
- ConversionCreditCharge model;
- credit check before creating conversion job;
- capture credits after successful conversion;
- no spend on failed conversion;
- real estimated cost in settings step;
- insufficient credits UI state.
```

## Не входит

```txt
- Laravel Cashier;
- Stripe checkout;
- subscriptions;
- monthly credit grants from paid plans;
- credit packs;
- reserve/capture ledger model;
- expiring credits by batch;
- enterprise pricing;
- OCR pricing;
- video pricing;
- API billing endpoints;
- invoice history;
- payment method management;
- billing page.
```

Cashier будет отдельной фазой.  
API будет отдельной фазой.  
Reserve/capture можно добавить позже, если MVP-упрощение окажется недостаточным.

---

# 4. Critical Decisions

## 4.1. MVP uses simple spend-on-success

Идеальный production-flow:

```txt
estimate → reserve → capture/refund
```

Но для MVP вводим упрощённую модель:

```txt
estimate → check balance before queue → spend after success
```

Это не идеально для массового параллельного запуска job, но достаточно для MVP, если добавить базовый check перед queue.

Reserve/capture не делать в Phase 15, чтобы не раздуть фазу.

## 4.2. Cost estimation is not a UI concern

Нельзя считать стоимость в Livewire-компоненте:

```php
if ($target === 'pdf') {
    $cost = 2;
}
```

Правильно:

```php
$cost = app(EstimateConversionCostAction::class)->handle($user, $file, $converter, $options);
```

UI только отображает результат.

## 4.3. Cost estimation is not a converter driver concern

Driver отвечает за выполнение конвертации.

Неправильно:

```php
PngToJpgDriver::cost()
```

Правильно:

```txt
ConversionCostEstimator calculates cost using converter key/source/target/file/options.
ConverterDriver converts file.
```

Стоимость — application/billing concern, а не infrastructure driver concern.

## 4.4. MVP pricing must be simple and explicit

Для MVP фиксируем:

```txt
Image → Image = 1 credit
Image → PDF   = 2 credits
```

Примеры:

```txt
PNG → JPG  = 1 credit
JPG → PNG  = 1 credit
PNG → WEBP = 1 credit
JPG → WEBP = 1 credit
PNG → PDF  = 2 credits
JPG → PDF  = 2 credits
```

Не добавлять size-based billing в Phase 15. Можно добавить placeholder breakdown field `size = 0`, но не менять цену от размера файла.

## 4.5. Product charge record is separate from credit ledger

`credit_transactions` отвечает за ledger.

`conversion_credit_charges` отвечает за продуктовую связь:

```txt
this conversion cost 2 credits because target was PDF
```

Даже если ledger transaction существует, charge record всё равно нужен для history/debug/API.

## 4.6. Failed conversion must not spend credits

Если job упала по ошибке системы или driver exception:

```txt
credits are not spent
charge status = failed
```

Если файл пользователя битый и ошибка обнаружена только во время conversion, в MVP тоже не списывать. Спорные политики можно решить позже.

---

# 5. Architecture Rules

## 5.1. CreateConversionJobAction owns credit check

Нельзя проверять credits только в Livewire.

Правильно:

```txt
Dashboard Livewire → CreateConversionJobAction → EstimateCost → CreditLedger balance check → create job
API later       → CreateConversionJobAction → same path
```

## 5.2. ProcessConversionJob owns credit capture after success

Списание должно происходить после успешного driver result.

Правильно:

```txt
driver success → result file saved → job completed → credits spent → charge captured
```

Если spend failed after conversion success, job нельзя тихо считать completed без следа. Нужно логировать и mark charge failed или retryable. В MVP достаточно domain exception + failed job handling.

## 5.3. Estimator must return DTO, not raw array

Неправильно:

```php
return ['amount' => 2];
```

Правильно:

```php
return new CreditCost(amount: 2, breakdown: ...);
```

## 5.4. Cost config must be testable

Стоимость не должна быть размазана по коду.

Правильно:

```txt
config/conversion_costs.php
```

Сервисы читают config, тесты проверяют правила.

## 5.5. No Cashier in Phase 15

Не устанавливать:

```txt
laravel/cashier
stripe/stripe-php manually
```

Phase 15 работает полностью локально на custom ledger.

---

# 6. GitFlow для Phase 15

## Base branch

Все задачи Phase 15 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-215-create-credit-cost-dto
feature/CONV-227-create-estimate-conversion-cost-action
feature/CONV-230-enforce-credits-in-create-conversion-job-action
```

## Commit format

```txt
CONV-215: Create CreditCost DTO
CONV-227: Create EstimateConversionCostAction
CONV-230: Enforce credits in CreateConversionJobAction
```

## Release branch

После выполнения `CONV-215`–`CONV-235`:

```txt
release/v0.1.15-phase15-conversion-cost-estimator
```

## Tag

После merge release branch в `main`:

```txt
v0.1.15-phase15-conversion-cost-estimator
```

---

# 7. TDD Rules for Phase 15

## Для estimator

Test-first:

```txt
- image-to-image conversion costs 1 credit;
- image-to-pdf conversion costs 2 credits;
- cost breakdown has stable shape;
- unsupported converter cannot be estimated.
```

## Для job creation

Test-first:

```txt
- conversion job is created when credits are enough;
- conversion job is not created when credits are insufficient;
- queue job is not dispatched when credits are insufficient.
```

## Для capture

Test-first:

```txt
- successful conversion spends credits;
- failed conversion does not spend credits;
- charge record is updated.
```

## Для UI

Livewire tests:

```txt
- settings step shows estimated cost;
- insufficient credits shows clear error;
- convert button does not create job when credits are insufficient.
```

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Billing / Credits / Conversion / UI / Tests
Type: Test / Feature / Action / DTO / Config / Migration
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
- npm run build проходит, если UI затронут
- Нет Cashier/Stripe вне scope задачи
- Коммит содержит ID задачи

Files likely touched:
- path/to/file
```

---

# 9. Phase 15 Atomic Tasks

---

## CONV-215 — Create CreditCost DTO

**Area:** Billing / Credits  
**Type:** DTO  
**Priority:** P0  
**Branch:** `feature/CONV-215-create-credit-cost-dto`  
**Base branch:** develop  
**Depends on:** CONV-214

### Goal

Создать immutable DTO для результата оценки стоимости конвертации.

### TDD step

Unit test:

```php
it('creates credit cost dto', function () {
    $cost = new CreditCost(amount: 2, breakdown: []);

    expect($cost->amount)->toBe(2);
    expect($cost->breakdown)->toBe([]);
});
```

Тест должен упасть до создания DTO.

### Implementation

Создать:

```txt
app/Data/Credits/CreditCost.php
```

Пример:

```php
namespace App\Data\Credits;

final readonly class CreditCost
{
    public function __construct(
        public int $amount,
        public array $breakdown,
    ) {
        if ($this->amount < 0) {
            throw new \InvalidArgumentException('Credit cost amount cannot be negative.');
        }
    }
}
```

### Acceptance criteria

- `CreditCost` exists.
- `amount` is public readonly int.
- `breakdown` exists.
- Negative amount rejected.
- Unit test passes.

### Definition of Done

- Тест написан первым.
- DTO создан.
- Тест проходит.
- Коммит: `CONV-215: Create CreditCost DTO`

### Files likely touched

```txt
app/Data/Credits/CreditCost.php
tests/Unit/Data/CreditCostTest.php
```

После этого сделай MR в бранч develop как только сделается MR сразу мержи его на гитхабе и обновляй бранч develop локально

---

## CONV-216 — Create CreditCostBreakdown DTO

**Area:** Billing / Credits  
**Type:** DTO  
**Priority:** P0  
**Branch:** `feature/CONV-216-create-credit-cost-breakdown-dto`  
**Base branch:** develop  
**Depends on:** CONV-215

### Goal

Создать DTO для breakdown стоимости, чтобы не хранить произвольный массив без структуры.

### TDD step

Unit test:

```php
it('creates credit cost breakdown dto', function () {
    $breakdown = new CreditCostBreakdown(
        base: 1,
        size: 0,
        features: 1,
        total: 2,
        details: ['target' => 'pdf'],
    );

    expect($breakdown->total)->toBe(2);
    expect($breakdown->toArray()['base'])->toBe(1);
});
```

### Implementation

Создать:

```txt
app/Data/Credits/CreditCostBreakdown.php
```

Поля:

```php
public int $base
public int $size
public int $features
public int $total
public array $details
```

Добавить `toArray()`.

### Acceptance criteria

- DTO существует.
- Есть поля `base`, `size`, `features`, `total`, `details`.
- Есть `toArray()`.
- Negative values rejected.
- Unit test passes.

### Definition of Done

- Тест написан первым.
- DTO создан.
- Test passes.
- Коммит: `CONV-216: Create CreditCostBreakdown DTO`

### Files likely touched

```txt
app/Data/Credits/CreditCostBreakdown.php
tests/Unit/Data/CreditCostBreakdownTest.php
```

После этого сделай MR в бранч develop как только сделается MR сразу мержи его на гитхабе и обновляй бранч develop локально

---

## CONV-217 — Create ConversionCostEstimator Contract

**Area:** Billing / Conversion  
**Type:** Contract  
**Priority:** P0  
**Branch:** `feature/CONV-217-create-conversion-cost-estimator-contract`  
**Base branch:** develop  
**Depends on:** CONV-216

### Goal

Создать contract для оценки стоимости конвертации.

### TDD step

Unit/skeleton test:

```php
it('can resolve conversion cost estimator contract', function () {
    expect(interface_exists(ConversionCostEstimator::class))->toBeTrue();
});
```

Тест должен упасть до создания interface.

### Implementation

Создать:

```txt
app/Contracts/Billing/ConversionCostEstimator.php
```

Интерфейс:

```php
namespace App\Contracts\Billing;

use App\Data\Credits\CreditCost;
use App\Models\FileRecord;
use App\Support\Conversion\Converter;

interface ConversionCostEstimator
{
    public function estimate(FileRecord $file, Converter $converter, array $options = []): CreditCost;
}
```

Адаптировать namespaces под фактическую структуру проекта.

### Acceptance criteria

- Interface exists.
- Method accepts file, converter, options.
- Method returns CreditCost.
- No implementation logic here.
- Test passes.

### Definition of Done

- Тест написан первым.
- Contract создан.
- Test passes.
- Коммит: `CONV-217: Create ConversionCostEstimator contract`

### Files likely touched

```txt
app/Contracts/Billing/ConversionCostEstimator.php
tests/Unit/Contracts/ConversionCostEstimatorContractTest.php
```

После этого сделай MR в бранч develop как только сделается MR сразу мержи его на гитхабе и обновляй бранч develop локально

---

## CONV-218 — Create ConfigDrivenConversionCostEstimator Skeleton

**Area:** Billing / Conversion  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-218-create-config-driven-conversion-cost-estimator-skeleton`  
**Base branch:** develop  
**Depends on:** CONV-217

### Goal

Создать skeleton implementation estimator, который позже будет читать pricing rules из config.

### TDD step

Unit test:

```php
it('resolves conversion cost estimator implementation from container', function () {
    $estimator = app(ConversionCostEstimator::class);

    expect($estimator)->toBeInstanceOf(ConfigDrivenConversionCostEstimator::class);
});
```

Тест должен упасть до binding implementation.

### Implementation

Создать:

```txt
app/Services/Billing/ConfigDrivenConversionCostEstimator.php
```

Skeleton:

```php
final class ConfigDrivenConversionCostEstimator implements ConversionCostEstimator
{
    public function estimate(FileRecord $file, Converter $converter, array $options = []): CreditCost
    {
        throw new \LogicException('Not implemented yet.');
    }
}
```

Зарегистрировать binding в `AppServiceProvider` или dedicated provider:

```php
$this->app->bind(ConversionCostEstimator::class, ConfigDrivenConversionCostEstimator::class);
```

### Acceptance criteria

- Implementation exists.
- Contract resolves to implementation.
- No pricing logic yet.
- Test passes.

### Definition of Done

- Тест написан первым.
- Skeleton создан.
- Binding добавлен.
- Test passes.
- Коммит: `CONV-218: Create ConfigDrivenConversionCostEstimator skeleton`

### Files likely touched

```txt
app/Services/Billing/ConfigDrivenConversionCostEstimator.php
app/Providers/AppServiceProvider.php
tests/Unit/Services/ConversionCostEstimatorResolutionTest.php
```

После этого сделай MR в бранч develop как только сделается MR сразу мержи его на гитхабе и обновляй бранч develop локально

---

## CONV-219 — Add Conversion Costs Config

**Area:** Billing / Config  
**Type:** Config  
**Priority:** P0  
**Branch:** `feature/CONV-219-add-conversion-costs-config`  
**Base branch:** develop  
**Depends on:** CONV-218

### Goal

Добавить config-файл с MVP pricing rules.

### TDD step

Config test:

```php
it('has conversion costs config for mvp converters', function () {
    expect(config('conversion_costs.rules.image_to_image.base'))->toBe(1);
    expect(config('conversion_costs.rules.image_to_pdf.base'))->toBe(2);
});
```

### Implementation

Создать:

```txt
config/conversion_costs.php
```

Пример:

```php
return [
    'rules' => [
        'image_to_image' => [
            'base' => 1,
            'size' => 0,
            'features' => [],
        ],
        'image_to_pdf' => [
            'base' => 2,
            'size' => 0,
            'features' => [],
        ],
    ],

    'groups' => [
        'image' => ['png', 'jpg', 'jpeg', 'webp'],
        'pdf' => ['pdf'],
    ],
];
```

### Acceptance criteria

- `config/conversion_costs.php` exists.
- Image-to-image base = 1.
- Image-to-pdf base = 2.
- Config test passes.
- No dynamic pricing yet.

### Definition of Done

- Тест написан первым.
- Config добавлен.
- Test passes.
- Коммит: `CONV-219: Add conversion costs config`

### Files likely touched

```txt
config/conversion_costs.php
tests/Unit/Config/ConversionCostsConfigTest.php
```

После этого сделай MR в бранч develop как только сделается MR сразу мержи его на гитхабе и обновляй бранч develop локально

---

## CONV-220 — Test Image To Image Cost

**Area:** Billing / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-220-test-image-to-image-cost`  
**Base branch:** develop  
**Depends on:** CONV-219

### Goal

Написать падающий тест: image-to-image конвертация стоит 1 credit.

### TDD step

Unit test:

```php
it('estimates image to image conversion as one credit', function () {
    $file = FileRecord::factory()->png()->create();
    $converter = app(ConverterRegistry::class)->find('png', 'jpg');

    $cost = app(ConversionCostEstimator::class)->estimate($file, $converter, []);

    expect($cost->amount)->toBe(1);
});
```

Адаптировать factories и registry под фактические имена.

### Implementation

Только добавить тест.

### Acceptance criteria

- Test exists.
- PNG→JPG expected cost = 1.
- JPG→WEBP can be added as second case.
- Test fails before implementation.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-220: Test image to image cost`

### Files likely touched

```txt
tests/Unit/Services/ConversionCostEstimatorTest.php
```

После этого сделай MR в бранч develop как только сделается MR сразу мержи его на гитхабе и обновляй бранч develop локально

---

## CONV-221 — Implement Image To Image Cost

**Area:** Billing / Conversion  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-221-implement-image-to-image-cost`  
**Base branch:** develop  
**Depends on:** CONV-220

### Goal

Реализовать расчёт стоимости image-to-image conversion.

### TDD step

Использовать падающий тест из CONV-220.

### Implementation

В `ConfigDrivenConversionCostEstimator`:

```php
if ($this->isImage($converter->sourceFormat()) && $this->isImage($converter->targetFormat())) {
    return $this->makeCost('image_to_image', $file, $converter, $options);
}
```

На этом шаге `makeCost()` может вернуть `CreditCost(amount: 1, breakdown: [])`, breakdown будет стабилизирован позже.

### Acceptance criteria

- PNG→JPG = 1 credit.
- JPG→PNG = 1 credit.
- PNG→WEBP = 1 credit.
- JPG→WEBP = 1 credit.
- Test passes.

### Definition of Done

- Implementation минимальная.
- Tests pass.
- Коммит: `CONV-221: Implement image to image cost`

### Files likely touched

```txt
app/Services/Billing/ConfigDrivenConversionCostEstimator.php
tests/Unit/Services/ConversionCostEstimatorTest.php
```

После этого сделай MR в бранч develop как только сделается MR сразу мержи его на гитхабе и обновляй бранч develop локально

---

## CONV-222 — Test Image To PDF Cost

**Area:** Billing / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-222-test-image-to-pdf-cost`  
**Base branch:** develop  
**Depends on:** CONV-221

### Goal

Написать падающий тест: image-to-pdf конвертация стоит 2 credits.

### TDD step

Unit test:

```php
it('estimates image to pdf conversion as two credits', function () {
    $file = FileRecord::factory()->png()->create();
    $converter = app(ConverterRegistry::class)->find('png', 'pdf');

    $cost = app(ConversionCostEstimator::class)->estimate($file, $converter, []);

    expect($cost->amount)->toBe(2);
});
```

Добавить case для JPG→PDF.

### Implementation

Только добавить тест.

### Acceptance criteria

- PNG→PDF expected cost = 2.
- JPG→PDF expected cost = 2.
- Test fails before implementation.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-222: Test image to PDF cost`

### Files likely touched

```txt
tests/Unit/Services/ConversionCostEstimatorTest.php
```

После этого сделай MR в бранч develop как только сделается MR сразу мержи его на гитхабе и обновляй бранч develop локально

---

## CONV-223 — Implement Image To PDF Cost

**Area:** Billing / Conversion  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-223-implement-image-to-pdf-cost`  
**Base branch:** develop  
**Depends on:** CONV-222

### Goal

Реализовать расчёт стоимости image-to-pdf conversion.

### TDD step

Использовать падающий тест из CONV-222.

### Implementation

В `ConfigDrivenConversionCostEstimator`:

```php
if ($this->isImage($converter->sourceFormat()) && $converter->targetFormat() === 'pdf') {
    return $this->makeCost('image_to_pdf', $file, $converter, $options);
}
```

### Acceptance criteria

- PNG→PDF = 2 credits.
- JPG→PDF = 2 credits.
- Existing image-to-image costs still pass.
- Tests pass.

### Definition of Done

- Implementation минимальная.
- Tests pass.
- Коммит: `CONV-223: Implement image to PDF cost`

### Files likely touched

```txt
app/Services/Billing/ConfigDrivenConversionCostEstimator.php
tests/Unit/Services/ConversionCostEstimatorTest.php
```

После этого сделай MR в бранч develop как только сделается MR сразу мержи его на гитхабе и обновляй бранч develop локально

---

## CONV-224 — Test Cost Breakdown Shape

**Area:** Billing / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-224-test-cost-breakdown-shape`  
**Base branch:** develop  
**Depends on:** CONV-223

### Goal

Написать тест, фиксирующий стабильную структуру breakdown.

### TDD step

Unit test:

```php
it('returns stable cost breakdown', function () {
    $file = FileRecord::factory()->png()->create();
    $converter = app(ConverterRegistry::class)->find('png', 'pdf');

    $cost = app(ConversionCostEstimator::class)->estimate($file, $converter, []);

    expect($cost->breakdown)->toHaveKeys([
        'base',
        'size',
        'features',
        'total',
        'details',
    ]);

    expect($cost->breakdown['total'])->toBe($cost->amount);
});
```

### Implementation

Только добавить тест.

### Acceptance criteria

- Test fixes breakdown shape.
- `total` equals `amount`.
- Test fails before implementation.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-224: Test cost breakdown shape`

### Files likely touched

```txt
tests/Unit/Services/ConversionCostEstimatorTest.php
```

После этого сделай MR в бранч develop как только сделается MR сразу мержи его на гитхабе и обновляй бранч develop локально

---

## CONV-225 — Implement Cost Breakdown

**Area:** Billing / Conversion  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-225-implement-cost-breakdown`  
**Base branch:** develop  
**Depends on:** CONV-224

### Goal

Возвращать стабильный breakdown для всех MVP conversion costs.

### TDD step

Использовать падающий тест из CONV-224.

### Implementation

В `makeCost()` создать `CreditCostBreakdown`:

```php
$breakdown = new CreditCostBreakdown(
    base: $base,
    size: 0,
    features: 0,
    total: $base,
    details: [
        'rule' => $rule,
        'source_format' => $converter->sourceFormat(),
        'target_format' => $converter->targetFormat(),
        'converter_key' => $converter->key(),
        'file_size_bytes' => $file->size_bytes,
    ],
);

return new CreditCost(
    amount: $breakdown->total,
    breakdown: $breakdown->toArray(),
);
```

### Acceptance criteria

- Breakdown has base/size/features/total/details.
- Total equals amount.
- Details include converter key and formats.
- Tests pass.

### Definition of Done

- Breakdown implemented.
- Tests pass.
- Коммит: `CONV-225: Implement cost breakdown`

### Files likely touched

```txt
app/Services/Billing/ConfigDrivenConversionCostEstimator.php
app/Data/Credits/CreditCostBreakdown.php
tests/Unit/Services/ConversionCostEstimatorTest.php
```

После этого сделай MR в бранч develop как только сделается MR сразу мержи его на гитхабе и обновляй бранч develop локально

---

## CONV-226 — Test Unsupported Cost Estimation Is Rejected

**Area:** Billing / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-226-test-unsupported-cost-estimation-is-rejected`  
**Base branch:** develop  
**Depends on:** CONV-225

### Goal

Написать тест: estimator не должен молча возвращать 0 или 1 credit для неизвестной пары.

### TDD step

Unit test:

```php
it('rejects unsupported cost estimation', function () {
    $file = FileRecord::factory()->png()->create();
    $converter = new FakeUnsupportedConverter('png', 'mp3');

    app(ConversionCostEstimator::class)->estimate($file, $converter, []);
})->throws(UnsupportedConversionCostException::class);
```

### Implementation

Создать exception позже в этой же задаче или следующей?  
Для этой задачи — только тест, если exception ещё не существует, можно добавить import placeholder и позволить тесту упасть.

### Acceptance criteria

- Test exists.
- Unsupported pair expected to throw explicit exception.
- No silent zero cost allowed.
- Test fails before implementation if exception/logic missing.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-226: Test unsupported cost estimation is rejected`

### Files likely touched

```txt
tests/Unit/Services/ConversionCostEstimatorTest.php
tests/Fakes/FakeUnsupportedConverter.php
```

После этого сделай MR в бранч develop как только сделается MR сразу мержи его на гитхабе и обновляй бранч develop локально

---

## CONV-227 — Create EstimateConversionCostAction

**Area:** Billing / Application  
**Type:** Action  
**Priority:** P0  
**Branch:** `feature/CONV-227-create-estimate-conversion-cost-action`  
**Base branch:** develop  
**Depends on:** CONV-226

### Goal

Создать application action для оценки стоимости, чтобы UI/API не вызывали estimator напрямую.

### TDD step

Feature/unit test:

```php
it('estimates conversion cost through application action', function () {
    $file = FileRecord::factory()->png()->create();
    $converter = app(ConverterRegistry::class)->find('png', 'jpg');

    $cost = app(EstimateConversionCostAction::class)->handle($file, $converter, []);

    expect($cost->amount)->toBe(1);
});
```

### Implementation

Создать:

```txt
app/Actions/Conversions/EstimateConversionCostAction.php
```

Action:

```php
final class EstimateConversionCostAction
{
    public function __construct(
        private readonly ConversionCostEstimator $estimator,
    ) {}

    public function handle(FileRecord $file, Converter $converter, array $options = []): CreditCost
    {
        return $this->estimator->estimate($file, $converter, $options);
    }
}
```

Также реализовать exception из CONV-226, если ещё не сделано:

```txt
app/Exceptions/Billing/UnsupportedConversionCostException.php
```

### Acceptance criteria

- Action exists.
- Action delegates to estimator.
- Unsupported conversion throws explicit exception.
- Tests pass.

### Definition of Done

- Тест написан.
- Action создан.
- Unsupported exception добавлен.
- Tests pass.
- Коммит: `CONV-227: Create EstimateConversionCostAction`

### Files likely touched

```txt
app/Actions/Conversions/EstimateConversionCostAction.php
app/Exceptions/Billing/UnsupportedConversionCostException.php
app/Services/Billing/ConfigDrivenConversionCostEstimator.php
tests/Feature/Actions/EstimateConversionCostActionTest.php
tests/Unit/Services/ConversionCostEstimatorTest.php
```

После этого сделай MR в бранч develop как только сделается MR сразу мержи его на гитхабе и обновляй бранч develop локально

---

## CONV-228 — Create ConversionCreditCharge Model And Migration

**Area:** Billing / Database  
**Type:** Migration  
**Priority:** P0  
**Branch:** `feature/CONV-228-create-conversion-credit-charge-model-and-migration`  
**Base branch:** develop  
**Depends on:** CONV-227

### Goal

Создать таблицу product-level charges для связи conversion job и credit cost.

### TDD step

Feature/model test:

```php
it('creates conversion credit charge record', function () {
    $charge = ConversionCreditCharge::factory()->create([
        'estimated_amount' => 2,
        'captured_amount' => 0,
        'status' => ConversionCreditChargeStatus::Estimated,
    ]);

    expect($charge->exists)->toBeTrue();
    expect($charge->estimated_amount)->toBe(2);
});
```

Тест должен упасть до migration/model.

### Implementation

Создать migration:

```txt
conversion_credit_charges
```

Поля:

```txt
id
user_id
conversion_job_id nullable
estimated_amount
captured_amount default 0
refunded_amount default 0
status
breakdown_json nullable
created_at
updated_at
```

Создать:

```txt
app/Models/ConversionCreditCharge.php
app/Enums/ConversionCreditChargeStatus.php
```

Statuses:

```txt
estimated
captured
refunded
failed
```

### Acceptance criteria

- Migration exists.
- Model exists.
- Enum exists.
- Factory exists.
- Charge can link to user and conversion job.
- Test passes.

### Definition of Done

- Тест написан первым.
- Migration/model/enum/factory добавлены.
- Tests pass.
- Коммит: `CONV-228: Create ConversionCreditCharge model and migration`

### Files likely touched

```txt
database/migrations/*create_conversion_credit_charges_table.php
app/Models/ConversionCreditCharge.php
app/Enums/ConversionCreditChargeStatus.php
database/factories/ConversionCreditChargeFactory.php
tests/Feature/Models/ConversionCreditChargeTest.php
```

После этого сделай MR в бранч develop как только сделается MR сразу мержи его на гитхабе и обновляй бранч develop локально

---

## CONV-229 — Test Conversion Job Checks Credits Before Queue

**Area:** Conversion / Credits / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-229-test-conversion-job-checks-credits-before-queue`  
**Base branch:** develop  
**Depends on:** CONV-228

### Goal

Написать тесты: conversion job создаётся только если credits хватает.

### TDD step

Feature test:

```php
it('does not create conversion job when user has insufficient credits', function () {
    Queue::fake();

    $user = User::factory()->create();
    app(CreditLedger::class)->grant($user, 0, 'test');

    $file = FileRecord::factory()->for($user)->png()->create();

    app(CreateConversionJobAction::class)->handle(
        user: $user,
        file: $file,
        targetFormat: 'pdf',
        options: []
    );
})->throws(InsufficientCreditsException::class);
```

Side effects:

```php
expect(ConversionJob::query()->count())->toBe(0);
Queue::assertNothingPushed();
```

Positive test:

```php
it('creates conversion job when user has enough credits', ...);
```

### Implementation

Только добавить тесты.

### Acceptance criteria

- Insufficient credits test exists.
- Enough credits test exists.
- No job created when insufficient.
- No queue dispatch when insufficient.
- Tests fail before implementation.

### Definition of Done

- Тесты добавлены.
- Тесты ожидаемо падают.
- Коммит: `CONV-229: Test conversion job checks credits before queue`

### Files likely touched

```txt
tests/Feature/Actions/CreateConversionJobActionCreditTest.php
```

После этого сделай MR в бранч develop как только сделается MR сразу мержи его на гитхабе и обновляй бранч develop локально

---

## CONV-230 — Enforce Credits In CreateConversionJobAction

**Area:** Conversion / Credits  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-230-enforce-credits-in-create-conversion-job-action`  
**Base branch:** develop  
**Depends on:** CONV-229

### Goal

Добавить проверку credits в `CreateConversionJobAction` перед созданием job и dispatch.

### TDD step

Использовать падающие тесты из CONV-229.

### Implementation

Создать exception:

```txt
app/Exceptions/Billing/InsufficientCreditsException.php
```

В `CreateConversionJobAction`:

```php
$cost = $this->estimateCost->handle($file, $converter, $normalizedOptions);

if ($this->creditLedger->balance($user) < $cost->amount) {
    throw InsufficientCreditsException::make(
        required: $cost->amount,
        available: $this->creditLedger->balance($user),
    );
}
```

Создать `ConversionCreditCharge` with status `estimated` linked to job after job creation.

В MVP можно:

```txt
- estimate before job;
- create job;
- create charge linked to job;
- dispatch process job.
```

### Acceptance criteria

- Not enough credits blocks conversion.
- Job is not created when blocked.
- Queue is not dispatched when blocked.
- Enough credits creates job.
- Charge record created with estimated amount.
- Tests pass.

### Definition of Done

- Exception добавлен.
- CreateConversionJobAction checks credits.
- Charge record created.
- Tests pass.
- Коммит: `CONV-230: Enforce credits in CreateConversionJobAction`

### Files likely touched

```txt
app/Actions/Conversions/CreateConversionJobAction.php
app/Exceptions/Billing/InsufficientCreditsException.php
app/Models/ConversionCreditCharge.php
tests/Feature/Actions/CreateConversionJobActionCreditTest.php
```

После этого сделай MR в бранч develop как только сделается MR сразу мержи его на гитхабе и обновляй бранч develop локально

---

## CONV-231 — Test Credits Captured After Successful Conversion

**Area:** Conversion / Credits / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-231-test-credits-captured-after-successful-conversion`  
**Base branch:** develop  
**Depends on:** CONV-230

### Goal

Написать тест: успешная conversion списывает credits и обновляет charge.

### TDD step

Feature test:

```php
it('captures credits after successful conversion', function () {
    $user = User::factory()->create();
    app(CreditLedger::class)->grant($user, 10, 'test');

    $job = ConversionJob::factory()
        ->for($user)
        ->pngToJpg()
        ->queued()
        ->create();

    ConversionCreditCharge::factory()
        ->for($user)
        ->for($job, 'conversionJob')
        ->create([
            'estimated_amount' => 1,
            'captured_amount' => 0,
            'status' => ConversionCreditChargeStatus::Estimated,
        ]);

    ProcessConversionJob::dispatchSync($job);

    expect(app(CreditLedger::class)->balance($user))->toBe(9);
    expect($job->creditCharge->fresh()->status)->toBe(ConversionCreditChargeStatus::Captured);
});
```

Адаптировать factories/job dispatch под текущую реализацию.

### Implementation

Только добавить тест.

### Acceptance criteria

- Test exists.
- Successful conversion should spend credits.
- Charge status should become captured.
- Test fails before implementation.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-231: Test credits captured after successful conversion`

### Files likely touched

```txt
tests/Feature/Jobs/ProcessConversionJobCreditTest.php
```

После этого сделай MR в бранч develop как только сделается MR сразу мержи его на гитхабе и обновляй бранч develop локально

---

## CONV-232 — Capture Credits On Successful Conversion

**Area:** Conversion / Credits  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-232-capture-credits-on-successful-conversion`  
**Base branch:** develop  
**Depends on:** CONV-231

### Goal

Списывать credits после успешной конвертации.

### TDD step

Использовать падающий тест из CONV-231.

### Implementation

После успешного driver result и сохранения result file:

```php
$charge = $job->creditCharge;

$this->creditLedger->spend(
    user: $job->user,
    amount: $charge->estimated_amount,
    reason: 'conversion_completed',
    meta: [
        'conversion_job_id' => $job->id,
        'converter_key' => $job->converter_key,
    ],
);

$charge->forceFill([
    'captured_amount' => $charge->estimated_amount,
    'status' => ConversionCreditChargeStatus::Captured,
])->save();
```

Делать это в transaction, если возможно.

### Acceptance criteria

- Successful conversion spends credits.
- Charge status becomes captured.
- Captured amount equals estimated amount.
- Ledger transaction reason is clear.
- Test passes.

### Definition of Done

- Capture logic added.
- Tests pass.
- Коммит: `CONV-232: Capture credits on successful conversion`

### Files likely touched

```txt
app/Jobs/ProcessConversionJob.php
app/Models/ConversionJob.php
app/Models/ConversionCreditCharge.php
tests/Feature/Jobs/ProcessConversionJobCreditTest.php
```

После этого сделай MR в бранч develop как только сделается MR сразу мержи его на гитхабе и обновляй бранч develop локально

---

## CONV-233 — Test Failed Conversion Does Not Spend Credits

**Area:** Conversion / Credits / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-233-test-failed-conversion-does-not-spend-credits`  
**Base branch:** develop  
**Depends on:** CONV-232

### Goal

Проверить, что failed conversion не списывает credits и charge помечается failed.

### TDD step

Feature test:

```php
it('does not spend credits when conversion fails', function () {
    $user = User::factory()->create();
    app(CreditLedger::class)->grant($user, 10, 'test');

    $job = ConversionJob::factory()->for($user)->queued()->create();

    ConversionCreditCharge::factory()
        ->for($user)
        ->for($job, 'conversionJob')
        ->create([
            'estimated_amount' => 2,
            'captured_amount' => 0,
            'status' => ConversionCreditChargeStatus::Estimated,
        ]);

    app(FakeFailingDriverRegistry::class)->forceFailure();

    ProcessConversionJob::dispatchSync($job);

    expect(app(CreditLedger::class)->balance($user))->toBe(10);
    expect($job->creditCharge->fresh()->status)->toBe(ConversionCreditChargeStatus::Failed);
});
```

Адаптировать fake failure mechanism под текущую driver architecture.

### Implementation

Добавить тест и минимальный fake failing driver, если его ещё нет.

### Acceptance criteria

- Failed conversion does not spend credits.
- Charge status becomes failed.
- Captured amount remains 0.
- Test passes or fails until implementation adjusted.

### Definition of Done

- Тест написан.
- Failed path проверен.
- Реализация поправлена, если нужно.
- Tests pass.
- Коммит: `CONV-233: Test failed conversion does not spend credits`

### Files likely touched

```txt
tests/Feature/Jobs/ProcessConversionJobCreditTest.php
tests/Fakes/FakeFailingConverterDriver.php
app/Jobs/ProcessConversionJob.php
```

После этого сделай MR в бранч develop как только сделается MR сразу мержи его на гитхабе и обновляй бранч develop локально

---

## CONV-234 — Show Real Estimated Cost In Settings Step

**Area:** Dashboard / Livewire / Credits  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-234-show-real-estimated-cost-in-settings-step`  
**Base branch:** develop  
**Depends on:** CONV-233

### Goal

Показывать пользователю реальную estimated cost в credits на settings step.

### TDD step

Livewire test:

```php
it('shows estimated conversion cost in settings step', function () {
    $user = User::factory()->create();
    app(CreditLedger::class)->grant($user, 10, 'test');

    $file = FileRecord::factory()->for($user)->png()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'pdf')
        ->assertSee('2 credits');
});
```

### Implementation

В `DashboardConverter` после выбора target/options:

```php
$this->estimatedCost = app(EstimateConversionCostAction::class)->handle(
    $this->currentFile,
    $this->currentConverter,
    $this->options,
);
```

В UI:

```txt
This conversion will use 2 credits.
Your balance: 10 credits.
```

Обновлять cost при изменении options, если options влияют на cost. В MVP options не влияют, но метод должен быть готов.

### Acceptance criteria

- Settings step shows estimated credits.
- PNG→JPG shows 1 credit.
- PNG→PDF shows 2 credits.
- User balance visible nearby or in dropdown.
- Livewire test passes.

### Definition of Done

- Тест написан.
- UI показывает реальную стоимость.
- No hardcoded target checks in Blade.
- Tests pass.
- `npm run build` passes.
- Коммит: `CONV-234: Show real estimated cost in settings step`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
resources/views/livewire/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterCostTest.php
```

После этого сделай MR в бранч develop как только сделается MR сразу мержи его на гитхабе и обновляй бранч develop локально

---

## CONV-235 — Add Insufficient Credits UI State

**Area:** Dashboard / Livewire / Credits  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-235-add-insufficient-credits-ui-state`  
**Base branch:** develop  
**Depends on:** CONV-234

### Goal

Добавить понятное UI-состояние, когда пользователю не хватает credits для запуска conversion.

### TDD step

Livewire test:

```php
it('shows insufficient credits message and does not create job', function () {
    Queue::fake();

    $user = User::factory()->create();
    app(CreditLedger::class)->grant($user, 0, 'test');

    $file = FileRecord::factory()->for($user)->png()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'pdf')
        ->call('convert')
        ->assertSee('Not enough credits');

    expect(ConversionJob::query()->count())->toBe(0);
    Queue::assertNothingPushed();
});
```

### Implementation

В `DashboardConverter::convert()` ловить `InsufficientCreditsException`:

```php
catch (InsufficientCreditsException $e) {
    $this->errorMessage = 'Not enough credits. This conversion requires ...';
    return;
}
```

UI:

```txt
Not enough credits
This conversion requires 2 credits. Your balance is 0 credits.
[Buy credits] [View pricing]
```

CTA может быть placeholder до Billing/Cashier фазы:

```txt
Buy credits — disabled/coming soon
Pricing — link to pricing if page exists
```

### Acceptance criteria

- Insufficient credits message visible.
- Required and available credits shown.
- Conversion job is not created.
- Queue is not dispatched.
- CTA exists but no broken route.
- Livewire test passes.

### Definition of Done

- Тест написан.
- UI state added.
- No broken links.
- Tests pass.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.
- Коммит: `CONV-235: Add insufficient credits UI state`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
resources/views/livewire/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterCostTest.php
```

После этого сделай MR в бранч develop как только сделается MR сразу мержи его на гитхабе и обновляй бранч develop локально

---

# 10. Phase 15 Completion Criteria

Phase 15 завершена, когда:

```txt
- CONV-215–CONV-235 выполнены;
- CreditCost DTO exists;
- CreditCostBreakdown DTO exists;
- ConversionCostEstimator contract exists;
- ConfigDrivenConversionCostEstimator exists;
- conversion_costs config exists;
- image-to-image conversions cost 1 credit;
- image-to-pdf conversions cost 2 credits;
- cost breakdown shape is stable;
- unsupported cost estimation is rejected;
- EstimateConversionCostAction exists;
- conversion_credit_charges table exists;
- CreateConversionJobAction checks credits before creating/dispatching job;
- insufficient credits blocks conversion;
- successful conversion spends credits;
- failed conversion does not spend credits;
- settings step shows real estimated cost;
- insufficient credits UI state exists;
- no Cashier/Stripe was added;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 15

Без отдельной задачи нельзя:

```txt
- устанавливать Laravel Cashier;
- подключать Stripe;
- создавать checkout;
- создавать subscriptions;
- создавать credit packs;
- создавать billing page;
- добавлять API endpoints;
- делать reserve/capture ledger;
- добавлять expiring-credit spend order;
- добавлять OCR pricing;
- добавлять video pricing;
- добавлять PDF per-page pricing;
- добавлять dynamic size multipliers;
- менять тарифные планы;
- делать payment webhooks;
- добавлять Stripe Entitlements.
```

---

# 12. Recommended Execution Order

```txt
CONV-215 Create CreditCost DTO
CONV-216 Create CreditCostBreakdown DTO
CONV-217 Create ConversionCostEstimator Contract
CONV-218 Create ConfigDrivenConversionCostEstimator Skeleton
CONV-219 Add Conversion Costs Config
CONV-220 Test Image To Image Cost
CONV-221 Implement Image To Image Cost
CONV-222 Test Image To PDF Cost
CONV-223 Implement Image To PDF Cost
CONV-224 Test Cost Breakdown Shape
CONV-225 Implement Cost Breakdown
CONV-226 Test Unsupported Cost Estimation Is Rejected
CONV-227 Create EstimateConversionCostAction
CONV-228 Create ConversionCreditCharge Model And Migration
CONV-229 Test Conversion Job Checks Credits Before Queue
CONV-230 Enforce Credits In CreateConversionJobAction
CONV-231 Test Credits Captured After Successful Conversion
CONV-232 Capture Credits On Successful Conversion
CONV-233 Test Failed Conversion Does Not Spend Credits
CONV-234 Show Real Estimated Cost In Settings Step
CONV-235 Add Insufficient Credits UI State
```

---

# 13. Release

После завершения Phase 15:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh --seed

git checkout -b release/v0.1.15-phase15-conversion-cost-estimator
git push -u origin release/v0.1.15-phase15-conversion-cost-estimator
```

После этого шага сделай MR в `main` branch и после этого остановись.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.15-phase15-conversion-cost-estimator -m "File Converter Phase 15 Conversion Cost Estimator"
git push origin v0.1.15-phase15-conversion-cost-estimator
```
