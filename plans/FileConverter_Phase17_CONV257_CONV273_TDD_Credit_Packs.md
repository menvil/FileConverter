# File Converter — Phase 17 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 17 — Credit Packs**  
Диапазон задач: **CONV-257 → CONV-273**  
Основа нумерации: Phase 16 завершилась на `CONV-256`, поэтому Phase 17 начинается с `CONV-257`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 17 соответствует блоку:

```txt
Phase 17 — Credit Packs
```

Правильный диапазон Phase 17:

```txt
CONV-257 — Create CreditPack Enum
CONV-258 — Create Credit Packs Config
CONV-259 — Create CreditPack DTO
CONV-260 — Create CreditPackRepository
CONV-261 — Test Credit Pack Checkout Creation
CONV-262 — Implement Credit Pack Checkout Creation
CONV-263 — Add Credit Pack Checkout Route
CONV-264 — Add Credit Pack Checkout Redirect Pages
CONV-265 — Create CreditPackWebhookHandler Skeleton
CONV-266 — Test Credit Pack Purchase Grants Credits
CONV-267 — Implement Credit Pack Purchase Grant
CONV-268 — Test Duplicate Credit Pack Purchase Is Ignored
CONV-269 — Implement Credit Pack Purchase Idempotency
CONV-270 — Add Credit Pack Transaction Metadata
CONV-271 — Add Buy Credits CTA Component
CONV-272 — Show Buy Credits CTA In User Dropdown
CONV-273 — Show Buy Credits CTA On Insufficient Credits
```

Phase 17 добавляет **one-time credit packs** поверх уже созданной Cashier/CreditLedger архитектуры.

Credit packs в этой архитектуре отвечают за:

```txt
- покупку фиксированного количества credits;
- Stripe one-time checkout;
- обработку успешной оплаты;
- начисление credits через CreditLedger;
- идемпотентность начисления;
- понятные CTA для покупки credits.
```

Credit packs **не отвечают** за:

```txt
- подписки;
- monthly subscription grants;
- смену users.plan;
- стоимость конвертаций;
- API access;
- file size limits;
- billing page UI;
- invoice history;
- Stripe Customer Portal.
```

Эти зоны остаются в других фазах:

```txt
Phase 13 — Feature Access Service
Phase 14 — Custom Credit Ledger
Phase 15 — Conversion Cost Estimator
Phase 16 — Laravel Cashier Foundation
Phase 18 — Billing Page
```

---

# 2. Цель Phase 17

Phase 17 должна добавить возможность купить credits отдельно от подписки.

После Phase 17 пользователь должен уметь:

```txt
- видеть CTA на покупку credits;
- выбрать credit pack через route/action;
- попасть в Stripe one-time checkout;
- после успешной оплаты получить credits в local CreditLedger;
- не получить credits повторно при duplicate webhook;
- видеть credit transaction с корректной metadata;
- использовать купленные credits в уже существующем conversion flow.
```

Главный принцип: **Stripe подтверждает оплату, но credits начисляются только через локальный CreditLedger**.

---

# 3. Scope Phase 17

## Входит

```txt
- CreditPack enum;
- credit_packs config;
- CreditPack DTO;
- CreditPackRepository;
- extension of BillingPaymentService for one-time credit pack checkout;
- POST /billing/credits/{pack} route;
- success/cancel redirect pages or reuse existing pages safely;
- CreditPackWebhookHandler;
- checkout.session.completed handling for credit packs;
- idempotent credit pack grants;
- CreditLedger grant transaction for purchase;
- transaction metadata with Stripe checkout/payment identifiers;
- reusable Buy Credits CTA component;
- Buy Credits CTA in user dropdown;
- Buy Credits CTA in insufficient credits UI state;
- tests for checkout, grant, duplicate webhook, metadata, CTA rendering.
```

## Не входит

```txt
- full Billing page;
- invoice history;
- payment method management;
- Stripe Customer Portal;
- coupons/discounts;
- tax/VAT logic;
- subscription plan changes;
- subscription monthly grants;
- reserve/capture credits;
- multiple credit currencies;
- expiring purchased credits;
- team/shared credit balances;
- admin credit adjustment UI;
- refund UI;
- API endpoints for credit pack checkout;
- API client webhooks;
- Spike integration;
- Paddle integration.
```

Billing page будет отдельной фазой.  
Credit pack checkout route может существовать без полноценной `/billing` страницы.

---

# 4. Critical Decisions

## 4.1. Credit packs are one-time purchases, not subscriptions

Credit pack покупается отдельно и не меняет `users.plan`.

Неправильно:

```php
$user->update(['plan' => Plan::Pro]);
```

Правильно:

```php
$creditLedger->grant(
    user: $user,
    amount: $pack->credits,
    reason: 'credit_pack_purchase',
    meta: [...]
);
```

## 4.2. Free users may buy credits, but features are still plan-gated

Пользователь на `free` может купить credits, но это не должно автоматически открывать:

```txt
api_access
batch_conversion
larger_file_size
longer_retention
priority_queue
```

Credits отвечают за баланс.  
FeatureAccessService отвечает за доступы.

## 4.3. Stripe price IDs are not product logic

Stripe price id — это payment identifier.  
Локальная модель credit packs должна жить в config/repository.

Неправильно:

```php
if ($stripePriceId === 'price_123') {
    $credits = 2000;
}
```

Правильно:

```php
$pack = $creditPackRepository->findByStripePriceId($priceId);
$creditLedger->grant($user, $pack->credits, ...);
```

## 4.4. Grants must be idempotent

Stripe webhook может прийти несколько раз.  
Один checkout/payment не должен начислить credits дважды.

Идемпотентность должна опираться на:

```txt
Stripe event id
Stripe checkout session id
Stripe payment intent id, if available
```

В Phase 16 уже есть `billing_webhook_events` / `BillingWebhookEventRecorder`.  
Phase 17 должна использовать этот механизм, а не создавать второй параллельный event-log без причины.

## 4.5. CreditLedger remains the only balance mutation path

Нельзя делать:

```php
$user->creditAccount->increment('balance', $pack->credits);
```

Правильно:

```php
app(CreditLedger::class)->grant(...);
```

Иначе ledger и balance разойдутся.

## 4.6. Credit pack metadata is mandatory

Каждая purchase transaction должна иметь metadata:

```txt
pack_key
pack_credits
stripe_event_id
stripe_checkout_session_id
stripe_payment_intent_id nullable
stripe_customer_id nullable
stripe_price_id
```

Без metadata невозможно нормально расследовать billing disputes и duplicate events.

## 4.7. No credit expiration in Phase 17

Purchased credits в MVP не истекают.

Не добавлять:

```txt
expires_at for purchased credits
FIFO expiration model
credit lots
expiration notification
```

Это отдельная будущая фаза, если бизнес-модель потребует.

---

# 5. Architecture Rules

## 5.1. Controllers/routes must be thin

Route/controller не должен знать деталей Stripe checkout.

Неправильно:

```php
Route::post('/billing/credits/{pack}', function ($pack) {
    return auth()->user()->checkout([...]);
});
```

Правильно:

```php
return app(BillingPaymentService::class)
    ->createCreditPackCheckout(auth()->user(), $pack);
```

## 5.2. CreditPackRepository owns pack lookup

Нельзя искать pack через raw config в разных местах.

Правильно:

```php
$pack = $creditPackRepository->findOrFail($packKey);
```

## 5.3. Webhook handler owns grant logic

Нельзя начислять credits в route closure webhook endpoint.

Правильно:

```php
app(CreditPackWebhookHandler::class)->handleCheckoutSessionCompleted($event);
```

## 5.4. Existing subscription handling must not regress

Phase 17 не должна ломать Phase 16:

```txt
subscription activation;
subscription cancellation;
monthly subscription credit grant;
BillingWebhookEventRecorder behavior.
```

## 5.5. No billing page in this phase

Можно добавить CTA/components и checkout route.  
Нельзя делать полноценную `/billing` page с планами, pack cards, transaction history. Это Phase 18.

---

# 6. GitFlow для Phase 17

## Base branch

Все задачи Phase 17 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-257-create-credit-pack-enum
feature/CONV-263-add-credit-pack-checkout-route
feature/CONV-267-implement-credit-pack-purchase-grant
```

## Commit format

```txt
CONV-257: Create CreditPack enum
CONV-263: Add credit pack checkout route
CONV-267: Implement credit pack purchase grant
```

## Release branch

После выполнения `CONV-257`–`CONV-273`:

```txt
release/v0.1.17-phase17-credit-packs
```

## Tag

После merge release branch в `main`:

```txt
v0.1.17-phase17-credit-packs
```

---

# 7. TDD Rules for Phase 17

## Для CreditPackRepository

Тестировать:

```txt
- returns all configured packs;
- finds pack by key;
- rejects unknown pack;
- finds pack by Stripe price id;
- config shape is valid.
```

## Для checkout

Тестировать:

```txt
- paid credit pack creates one-time checkout;
- invalid pack is rejected;
- checkout is created through BillingPaymentService;
- controller/route does not call Cashier directly.
```

## Для webhook grant

Тестировать:

```txt
- successful checkout.session.completed grants credits;
- credit transaction is written;
- metadata includes Stripe identifiers;
- duplicate event/session does not grant credits twice;
- unknown price id is ignored or fails safely.
```

## Для UI CTA

Тестировать:

```txt
- Buy Credits CTA component renders;
- user dropdown shows CTA;
- insufficient credits state shows CTA;
- CTA points to valid checkout route or future billing route depending implementation.
```

Если direct test невозможен, задача должна явно написать:

```txt
No direct test — infrastructure integration boundary.
```

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Billing / Credits / Cashier / Webhook / UI / Tests
Type: Test / Feature / Config / Service / Route / Component
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
- npm run build проходит, если frontend touched
- Нет direct balance mutation
- CreditLedger используется для начисления credits
- Cashier используется только через BillingPaymentService
- Коммит содержит ID задачи

Files likely touched:
- path/to/file
```

---

# 9. Phase 17 Atomic Tasks

---

## CONV-257 — Create CreditPack Enum

**Area:** Billing / Credits  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-257-create-credit-pack-enum`  
**Base branch:** `develop`  
**Depends on:** CONV-256

### Goal

Создать enum для ключей credit packs.

### TDD step

Unit test:

```php
it('defines supported credit pack keys', function () {
    expect(CreditPack::Small->value)->toBe('small');
    expect(CreditPack::Medium->value)->toBe('medium');
    expect(CreditPack::Large->value)->toBe('large');
});
```

Тест должен упасть до создания enum.

### Implementation

Создать enum:

```txt
app/Enums/Billing/CreditPack.php
```

Пример:

```php
namespace App\Enums\Billing;

enum CreditPack: string
{
    case Small = 'small';
    case Medium = 'medium';
    case Large = 'large';
}
```

Не добавлять Stripe price IDs в enum.

### Acceptance criteria

- `CreditPack` enum существует.
- Есть `small`, `medium`, `large`.
- Enum не содержит Stripe-specific logic.
- Unit test passes.

### Definition of Done

- Тест написан первым.
- Enum создан.
- Тест проходит.
- Коммит: `CONV-257: Create CreditPack enum`

### Files likely touched

```txt
app/Enums/Billing/CreditPack.php
tests/Unit/Billing/CreditPackEnumTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-258 — Create Credit Packs Config

**Area:** Billing / Config  
**Type:** Config  
**Priority:** P0  
**Branch:** `feature/CONV-258-create-credit-packs-config`  
**Base branch:** `develop`  
**Depends on:** CONV-257

### Goal

Добавить локальный config для credit packs.

### TDD step

Config test:

```php
it('has configured credit packs', function () {
    $packs = config('billing.credit_packs');

    expect($packs)->toHaveKeys(['small', 'medium', 'large']);
    expect($packs['small']['credits'])->toBeGreaterThan(0);
    expect($packs['small'])->toHaveKeys([
        'label',
        'credits',
        'stripe_price_id',
        'description',
    ]);
});
```

Тест должен упасть до добавления config.

### Implementation

Добавить или расширить:

```txt
config/billing.php
```

Пример:

```php
'credit_packs' => [
    'small' => [
        'label' => '500 Credits',
        'credits' => 500,
        'stripe_price_id' => env('STRIPE_CREDIT_PACK_SMALL_PRICE_ID'),
        'description' => 'Good for occasional conversions.',
    ],
    'medium' => [
        'label' => '2,000 Credits',
        'credits' => 2000,
        'stripe_price_id' => env('STRIPE_CREDIT_PACK_MEDIUM_PRICE_ID'),
        'description' => 'Best for regular usage.',
    ],
    'large' => [
        'label' => '10,000 Credits',
        'credits' => 10000,
        'stripe_price_id' => env('STRIPE_CREDIT_PACK_LARGE_PRICE_ID'),
        'description' => 'For heavy users and API workloads.',
    ],
],
```

Обновить `.env.example`:

```env
STRIPE_CREDIT_PACK_SMALL_PRICE_ID=
STRIPE_CREDIT_PACK_MEDIUM_PRICE_ID=
STRIPE_CREDIT_PACK_LARGE_PRICE_ID=
```

### Acceptance criteria

- Config содержит small/medium/large packs.
- У каждого pack есть label/credits/stripe_price_id/description.
- Credits are positive integers.
- Stripe price IDs берутся из env.
- `.env.example` содержит placeholders.
- Tests pass.

### Definition of Done

- Config test written.
- Config добавлен.
- `.env.example` обновлён.
- Тесты проходят.
- Коммит: `CONV-258: Create credit packs config`

### Files likely touched

```txt
config/billing.php
.env.example
tests/Feature/Billing/CreditPacksConfigTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-259 — Create CreditPack DTO

**Area:** Billing / Credits  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-259-create-credit-pack-dto`  
**Base branch:** `develop`  
**Depends on:** CONV-258

### Goal

Создать immutable DTO для credit pack.

### TDD step

Unit test:

```php
it('creates credit pack dto from config array', function () {
    $dto = CreditPackDto::fromConfig('small', [
        'label' => '500 Credits',
        'credits' => 500,
        'stripe_price_id' => 'price_small',
        'description' => 'Good for occasional conversions.',
    ]);

    expect($dto->key)->toBe('small');
    expect($dto->label)->toBe('500 Credits');
    expect($dto->credits)->toBe(500);
    expect($dto->stripePriceId)->toBe('price_small');
});
```

Тест должен упасть до создания DTO.

### Implementation

Создать:

```txt
app/Data/Billing/CreditPackDto.php
```

DTO:

```php
final readonly class CreditPackDto
{
    public function __construct(
        public string $key,
        public string $label,
        public int $credits,
        public string $stripePriceId,
        public string $description,
    ) {}

    public static function fromConfig(string $key, array $config): self
    {
        // validate required keys minimally
    }
}
```

### Acceptance criteria

- DTO immutable/readonly.
- DTO maps key/label/credits/stripePriceId/description.
- Missing required config throws explicit exception or InvalidArgumentException.
- Tests pass.

### Definition of Done

- Тест написан.
- DTO создан.
- Invalid config handled.
- Тесты проходят.
- Коммит: `CONV-259: Create CreditPack DTO`

### Files likely touched

```txt
app/Data/Billing/CreditPackDto.php
tests/Unit/Billing/CreditPackDtoTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-260 — Create CreditPackRepository

**Area:** Billing / Credits  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-260-create-credit-pack-repository`  
**Base branch:** `develop`  
**Depends on:** CONV-259

### Goal

Создать repository для получения credit packs из config.

### TDD step

Unit tests:

```php
it('returns all credit packs', function () {
    $packs = app(CreditPackRepository::class)->all();

    expect($packs)->not->toBeEmpty();
    expect($packs->first())->toBeInstanceOf(CreditPackDto::class);
});
```

```php
it('finds credit pack by key', function () {
    $pack = app(CreditPackRepository::class)->findOrFail('small');

    expect($pack->key)->toBe('small');
});
```

```php
it('finds credit pack by stripe price id', function () {
    config()->set('billing.credit_packs.small.stripe_price_id', 'price_small');

    $pack = app(CreditPackRepository::class)->findByStripePriceId('price_small');

    expect($pack?->key)->toBe('small');
});
```

### Implementation

Создать:

```txt
app/Services/Billing/CreditPackRepository.php
```

Методы:

```php
public function all(): Collection;
public function find(string $key): ?CreditPackDto;
public function findOrFail(string $key): CreditPackDto;
public function findByStripePriceId(string $stripePriceId): ?CreditPackDto;
```

### Acceptance criteria

- Repository читает `config('billing.credit_packs')`.
- `all()` возвращает collection DTO.
- `findOrFail()` бросает exception для unknown key.
- `findByStripePriceId()` работает.
- Tests pass.

### Definition of Done

- Tests written.
- Repository создан.
- Тесты проходят.
- Коммит: `CONV-260: Create CreditPackRepository`

### Files likely touched

```txt
app/Services/Billing/CreditPackRepository.php
tests/Unit/Billing/CreditPackRepositoryTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-261 — Test Credit Pack Checkout Creation

**Area:** Billing / Cashier / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-261-test-credit-pack-checkout-creation`  
**Base branch:** `develop`  
**Depends on:** CONV-260

### Goal

Написать падающий тест: `BillingPaymentService` умеет создавать one-time checkout для credit pack.

### TDD step

Service test:

```php
it('creates credit pack checkout for user', function () {
    $user = User::factory()->create();
    $pack = app(CreditPackRepository::class)->findOrFail('small');

    $checkout = app(BillingPaymentService::class)
        ->createCreditPackCheckout($user, $pack);

    expect($checkout)->not->toBeNull();
});
```

Если прямое тестирование Cashier checkout сложно без Stripe calls, использовать fake/boundary:

```txt
- mock Cashier boundary;
- assert BillingPaymentService receives pack and creates one-time checkout request;
- do not call real Stripe in tests.
```

Тест должен упасть до реализации метода.

### Implementation

Только добавить тест.  
Реализация будет в CONV-262.

### Acceptance criteria

- Тест существует.
- Тест проверяет именно credit pack checkout.
- Тест не делает реальный Stripe HTTP call.
- Тест падает до CONV-262.

### Definition of Done

- Тест написан.
- Тест ожидаемо падает.
- Коммит: `CONV-261: Test credit pack checkout creation`

### Files likely touched

```txt
tests/Feature/Billing/BillingPaymentServiceCreditPackTest.php
```

После этого сделай MR в `develop`. Merge разрешён после проверки, что новый тест падает по ожидаемой причине в feature branch.

---

## CONV-262 — Implement Credit Pack Checkout Creation

**Area:** Billing / Cashier  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-262-implement-credit-pack-checkout-creation`  
**Base branch:** `develop`  
**Depends on:** CONV-261

### Goal

Реализовать создание Stripe one-time checkout для credit pack через `BillingPaymentService`.

### TDD step

Использовать падающий тест из CONV-261.

### Implementation

Расширить:

```txt
app/Services/Billing/BillingPaymentService.php
```

Добавить метод:

```php
public function createCreditPackCheckout(User $user, CreditPackDto $pack): mixed
{
    // create one-time checkout through Cashier boundary
}
```

Обязательные параметры checkout:

```txt
mode/payment one-time
price id from CreditPackDto
quantity = 1
success_url
cancel_url
metadata: user_id, pack_key, credits
```

Не делать direct credits grant в этом методе.  
Credits начисляются только после webhook подтверждения оплаты.

### Acceptance criteria

- `BillingPaymentService::createCreditPackCheckout()` exists.
- Uses pack Stripe price id.
- Sets checkout metadata.
- Does not grant credits immediately.
- Does not mutate user plan.
- Test from CONV-261 passes.

### Definition of Done

- Реализация минимальная.
- Тест проходит.
- No direct CreditLedger grant in checkout creation.
- Коммит: `CONV-262: Implement credit pack checkout creation`

### Files likely touched

```txt
app/Services/Billing/BillingPaymentService.php
tests/Feature/Billing/BillingPaymentServiceCreditPackTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-263 — Add Credit Pack Checkout Route

**Area:** Billing / Routes  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-263-add-credit-pack-checkout-route`  
**Base branch:** `develop`  
**Depends on:** CONV-262

### Goal

Добавить route для запуска checkout credit pack.

### TDD step

Feature test:

```php
it('starts credit pack checkout for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('billing.credits.checkout', ['pack' => 'small']))
        ->assertRedirect();
});
```

Invalid pack test:

```php
it('rejects unknown credit pack checkout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('billing.credits.checkout', ['pack' => 'unknown']))
        ->assertNotFound();
});
```

Guest test:

```php
it('requires auth for credit pack checkout', function () {
    $this->post(route('billing.credits.checkout', ['pack' => 'small']))
        ->assertRedirect(route('login'));
});
```

### Implementation

Добавить route:

```php
Route::post('/billing/credits/{pack}', CreditPackCheckoutController::class)
    ->middleware('auth')
    ->name('billing.credits.checkout');
```

Создать controller:

```txt
app/Http/Controllers/Billing/CreditPackCheckoutController.php
```

Controller должен:

```txt
- resolve pack через CreditPackRepository;
- call BillingPaymentService;
- return checkout redirect/response;
- not call Cashier directly.
```

### Acceptance criteria

- Auth user can start checkout.
- Guest redirected to login.
- Unknown pack returns 404.
- Controller does not call Cashier directly.
- Tests pass.

### Definition of Done

- Tests written.
- Route added.
- Controller added.
- BillingPaymentService used.
- Тесты проходят.
- Коммит: `CONV-263: Add credit pack checkout route`

### Files likely touched

```txt
routes/web.php
app/Http/Controllers/Billing/CreditPackCheckoutController.php
tests/Feature/Billing/CreditPackCheckoutRouteTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-264 — Add Credit Pack Checkout Redirect Pages

**Area:** Billing / UI  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-264-add-credit-pack-checkout-redirect-pages`  
**Base branch:** `develop`  
**Depends on:** CONV-263

### Goal

Добавить или переиспользовать success/cancel pages для credit pack checkout.

### TDD step

Feature tests:

```php
it('renders credit pack checkout success page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('billing.credits.success'))
        ->assertOk()
        ->assertSee('Credits purchase successful');
});
```

```php
it('renders credit pack checkout cancel page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('billing.credits.cancel'))
        ->assertOk()
        ->assertSee('Credits purchase cancelled');
});
```

### Implementation

Вариант A — отдельные routes:

```txt
GET /billing/credits/success
GET /billing/credits/cancel
```

Вариант B — переиспользовать общие billing success/cancel pages из Phase 16, но текст должен не врать.

Рекомендация для MVP: отдельные lightweight pages, чтобы user feedback был точным.

### Acceptance criteria

- Success page exists.
- Cancel page exists.
- Both require auth.
- Pages do not claim credits are already available before webhook if confirmation is async.
- Tests pass.

### Definition of Done

- Tests written.
- Routes/views added.
- Copy is honest about async webhook if needed.
- Коммит: `CONV-264: Add credit pack checkout redirect pages`

### Files likely touched

```txt
routes/web.php
resources/views/billing/credits-success.blade.php
resources/views/billing/credits-cancel.blade.php
tests/Feature/Billing/CreditPackRedirectPagesTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-265 — Create CreditPackWebhookHandler Skeleton

**Area:** Billing / Webhook  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-265-create-credit-pack-webhook-handler-skeleton`  
**Base branch:** `develop`  
**Depends on:** CONV-264

### Goal

Создать handler для обработки successful credit pack checkout webhook.

### TDD step

Unit test:

```php
it('has credit pack webhook handler with checkout completed method', function () {
    $handler = app(CreditPackWebhookHandler::class);

    expect(method_exists($handler, 'handleCheckoutSessionCompleted'))->toBeTrue();
});
```

Тест должен упасть до создания handler.

### Implementation

Создать:

```txt
app/Services/Billing/CreditPackWebhookHandler.php
```

Skeleton:

```php
final class CreditPackWebhookHandler
{
    public function handleCheckoutSessionCompleted(array|object $event): void
    {
        throw new \LogicException('Not implemented yet.');
    }
}
```

Не подключать пока к webhook route, если основной dispatcher ещё не готов различать events.  
Если Phase 16 уже имеет `SubscriptionWebhookHandler`, добавить общий dispatcher позже в CONV-267.

### Acceptance criteria

- Handler exists.
- Method exists.
- Handler resolvable from container.
- No grant logic yet.
- Test passes.

### Definition of Done

- Тест написан.
- Skeleton created.
- Test passes.
- Коммит: `CONV-265: Create CreditPackWebhookHandler skeleton`

### Files likely touched

```txt
app/Services/Billing/CreditPackWebhookHandler.php
tests/Unit/Billing/CreditPackWebhookHandlerSkeletonTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-266 — Test Credit Pack Purchase Grants Credits

**Area:** Billing / Credits / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-266-test-credit-pack-purchase-grants-credits`  
**Base branch:** `develop`  
**Depends on:** CONV-265

### Goal

Написать падающий тест: successful checkout for credit pack grants credits through CreditLedger.

### TDD step

Feature/service test:

```php
it('grants credits after successful credit pack checkout', function () {
    config()->set('billing.credit_packs.small.stripe_price_id', 'price_small');

    $user = User::factory()->create();
    app(CreditLedger::class)->grant($user, 0, 'test_initial');

    $event = fakeStripeCheckoutCompletedEvent([
        'event_id' => 'evt_credit_pack_1',
        'checkout_session_id' => 'cs_test_1',
        'customer_id' => $user->stripe_id,
        'user_id' => $user->id,
        'price_id' => 'price_small',
        'payment_intent_id' => 'pi_test_1',
    ]);

    app(CreditPackWebhookHandler::class)
        ->handleCheckoutSessionCompleted($event);

    expect(app(CreditLedger::class)->balance($user))->toBe(500);
});
```

Тест должен упасть до CONV-267.

### Implementation

Только добавить тест и fake helper, если нужен.

### Acceptance criteria

- Тест существует.
- Тест имитирует Stripe checkout.session.completed.
- Тест проверяет CreditLedger balance.
- Тест не вызывает реальный Stripe.
- Тест падает до реализации.

### Definition of Done

- Тест написан.
- Fake event helper добавлен, если нужен.
- Тест ожидаемо падает.
- Коммит: `CONV-266: Test credit pack purchase grants credits`

### Files likely touched

```txt
tests/Feature/Billing/CreditPackWebhookHandlerTest.php
tests/Support/FakeStripeEvents.php
```

После этого сделай MR в `develop`. Merge разрешён после проверки ожидаемого failing test.

---

## CONV-267 — Implement Credit Pack Purchase Grant

**Area:** Billing / Credits / Webhook  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-267-implement-credit-pack-purchase-grant`  
**Base branch:** `develop`  
**Depends on:** CONV-266

### Goal

Реализовать начисление credits после успешного credit pack checkout.

### TDD step

Использовать падающий тест из CONV-266.

### Implementation

В `CreditPackWebhookHandler`:

```txt
- extract checkout session;
- resolve user by metadata user_id or customer id;
- resolve pack by price id or metadata pack_key;
- grant credits through CreditLedger;
- write reason credit_pack_purchase;
- attach metadata.
```

Пример логики:

```php
$pack = $this->creditPackRepository->findByStripePriceId($priceId);

$this->creditLedger->grant(
    user: $user,
    amount: $pack->credits,
    reason: 'credit_pack_purchase',
    meta: [
        'pack_key' => $pack->key,
        'stripe_checkout_session_id' => $sessionId,
        'stripe_payment_intent_id' => $paymentIntentId,
        'stripe_price_id' => $pack->stripePriceId,
    ],
);
```

Подключить handler к webhook dispatcher/route из Phase 16 для event type:

```txt
checkout.session.completed
```

Но не ломать subscription events.

### Acceptance criteria

- Successful credit pack checkout grants credits.
- Credits granted through CreditLedger only.
- User plan is not changed.
- Subscription monthly grant logic still works.
- Test from CONV-266 passes.

### Definition of Done

- Grant logic implemented.
- Handler wired into webhook dispatcher.
- Tests pass.
- Коммит: `CONV-267: Implement credit pack purchase grant`

### Files likely touched

```txt
app/Services/Billing/CreditPackWebhookHandler.php
app/Services/Billing/SubscriptionWebhookHandler.php
app/Http/Controllers/Billing/CashierWebhookController.php
tests/Feature/Billing/CreditPackWebhookHandlerTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-268 — Test Duplicate Credit Pack Purchase Is Ignored

**Area:** Billing / Webhook / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-268-test-duplicate-credit-pack-purchase-is-ignored`  
**Base branch:** `develop`  
**Depends on:** CONV-267

### Goal

Написать тест: duplicate webhook/event/session не начисляет credits повторно.

### TDD step

Feature test:

```php
it('does not grant credits twice for duplicate credit pack checkout event', function () {
    config()->set('billing.credit_packs.small.stripe_price_id', 'price_small');

    $user = User::factory()->create();

    $event = fakeStripeCheckoutCompletedEvent([
        'event_id' => 'evt_duplicate_1',
        'checkout_session_id' => 'cs_duplicate_1',
        'user_id' => $user->id,
        'price_id' => 'price_small',
    ]);

    app(CreditPackWebhookHandler::class)->handleCheckoutSessionCompleted($event);
    app(CreditPackWebhookHandler::class)->handleCheckoutSessionCompleted($event);

    expect(app(CreditLedger::class)->balance($user))->toBe(500);
});
```

Дополнительный тест, если implementation использует session id:

```php
it('does not grant credits twice for same checkout session with different event ids', ...);
```

### Implementation

Только добавить тесты.

### Acceptance criteria

- Duplicate same event ignored.
- Duplicate same checkout session ignored.
- Balance increases once.
- Тесты падают до CONV-269, если idempotency ещё неполная.

### Definition of Done

- Tests written.
- Tests fail before implementation if applicable.
- Коммит: `CONV-268: Test duplicate credit pack purchase is ignored`

### Files likely touched

```txt
tests/Feature/Billing/CreditPackWebhookHandlerTest.php
```

После этого сделай MR в `develop`. Merge разрешён после проверки ожидаемого failing test.

---

## CONV-269 — Implement Credit Pack Purchase Idempotency

**Area:** Billing / Webhook / Credits  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-269-implement-credit-pack-purchase-idempotency`  
**Base branch:** `develop`  
**Depends on:** CONV-268

### Goal

Сделать credit pack grants идемпотентными.

### TDD step

Использовать падающие тесты из CONV-268.

### Implementation

Использовать механизм Phase 16:

```txt
billing_webhook_events
BillingWebhookEventRecorder
```

Но event id alone may be insufficient. Для credit packs дополнительно защититься по checkout session id:

```txt
credit_transactions metadata contains stripe_checkout_session_id
handler checks whether a credit_pack_purchase transaction already exists for session id
```

Рекомендация:

```php
if ($this->hasAlreadyGrantedForCheckoutSession($sessionId)) {
    return;
}
```

Или отдельная таблица `credit_pack_purchases`, но в Phase 17 лучше не плодить таблицы, если metadata query достаточно надёжный.

Если metadata query становится грязным — создать отдельную таблицу:

```txt
credit_pack_purchases
- id
- user_id
- pack_key
- credits
- stripe_event_id
- stripe_checkout_session_id unique
- stripe_payment_intent_id nullable
- created_at
- updated_at
```

Решение: предпочтительно metadata + unique event recorder в MVP. Таблица — только если тесты показывают слабость подхода.

### Acceptance criteria

- Duplicate same event does not grant twice.
- Duplicate same checkout session does not grant twice.
- CreditLedger balance correct.
- Idempotency uses existing webhook recorder where applicable.
- Tests pass.

### Definition of Done

- Idempotency implemented.
- Tests from CONV-268 pass.
- No double grant possible in tested scenarios.
- Коммит: `CONV-269: Implement credit pack purchase idempotency`

### Files likely touched

```txt
app/Services/Billing/CreditPackWebhookHandler.php
app/Services/Billing/BillingWebhookEventRecorder.php
app/Models/CreditTransaction.php
tests/Feature/Billing/CreditPackWebhookHandlerTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-270 — Add Credit Pack Transaction Metadata

**Area:** Billing / Credits / Tests  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-270-add-credit-pack-transaction-metadata`  
**Base branch:** `develop`  
**Depends on:** CONV-269

### Goal

Гарантировать, что credit transaction после покупки pack содержит достаточную metadata.

### TDD step

Feature test:

```php
it('stores stripe metadata on credit pack purchase transaction', function () {
    config()->set('billing.credit_packs.small.stripe_price_id', 'price_small');

    $user = User::factory()->create();

    $event = fakeStripeCheckoutCompletedEvent([
        'event_id' => 'evt_meta_1',
        'checkout_session_id' => 'cs_meta_1',
        'payment_intent_id' => 'pi_meta_1',
        'user_id' => $user->id,
        'price_id' => 'price_small',
    ]);

    app(CreditPackWebhookHandler::class)->handleCheckoutSessionCompleted($event);

    $transaction = CreditTransaction::query()
        ->where('user_id', $user->id)
        ->where('reason', 'credit_pack_purchase')
        ->firstOrFail();

    expect($transaction->metadata_json)->toMatchArray([
        'pack_key' => 'small',
        'pack_credits' => 500,
        'stripe_event_id' => 'evt_meta_1',
        'stripe_checkout_session_id' => 'cs_meta_1',
        'stripe_payment_intent_id' => 'pi_meta_1',
        'stripe_price_id' => 'price_small',
    ]);
});
```

### Implementation

Обновить metadata в grant call.

Обязательная metadata:

```txt
pack_key
pack_credits
stripe_event_id
stripe_checkout_session_id
stripe_payment_intent_id nullable
stripe_customer_id nullable
stripe_price_id
```

### Acceptance criteria

- Credit transaction metadata is complete.
- Metadata includes pack and Stripe identifiers.
- No sensitive card/payment method details stored.
- Test passes.

### Definition of Done

- Test written.
- Metadata added.
- Test passes.
- Коммит: `CONV-270: Add credit pack transaction metadata`

### Files likely touched

```txt
app/Services/Billing/CreditPackWebhookHandler.php
tests/Feature/Billing/CreditPackWebhookHandlerTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-271 — Add Buy Credits CTA Component

**Area:** UI / Billing  
**Type:** Component  
**Priority:** P1  
**Branch:** `feature/CONV-271-add-buy-credits-cta-component`  
**Base branch:** `develop`  
**Depends on:** CONV-270

### Goal

Создать переиспользуемый Blade component для покупки credits.

### TDD step

Render test:

```php
it('renders buy credits cta component', function () {
    $this->blade('<x-billing.buy-credits-cta />')
        ->assertSee('Buy credits')
        ->assertSee('Get more conversion credits');
});
```

Если Blade component testing setup отличается, адаптировать.

### Implementation

Создать component:

```txt
resources/views/components/billing/buy-credits-cta.blade.php
```

Компонент должен:

```txt
- показывать короткий текст;
- иметь CTA button/link;
- не содержать hardcoded Stripe links;
- вести на будущую billing page или открывать pack checkout dropdown, если уже есть;
- принимать optional variant: compact/full.
```

MVP route target:

```txt
/billing
```

Если `/billing` ещё не существует, CTA может вести на `route('pricing')` или показывать disabled state. Но лучше сделать URL configurable prop.

### Acceptance criteria

- Component renders.
- Text clear.
- No direct Stripe price IDs in Blade.
- Supports compact/full variant or at least can be reused.
- Render test passes.

### Definition of Done

- Component created.
- Test written.
- Test passes.
- `npm run build` passes.
- Коммит: `CONV-271: Add buy credits CTA component`

### Files likely touched

```txt
resources/views/components/billing/buy-credits-cta.blade.php
tests/Feature/ViewComponents/BuyCreditsCtaTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-272 — Show Buy Credits CTA In User Dropdown

**Area:** UI / Billing  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-272-show-buy-credits-cta-in-user-dropdown`  
**Base branch:** `develop`  
**Depends on:** CONV-271

### Goal

Добавить buy credits CTA в user dropdown рядом с текущим credits balance.

### TDD step

Feature/render test:

```php
it('shows buy credits cta in user dropdown', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Buy credits');
});
```

Если dropdown скрыт Alpine и текст не в DOM до открытия, тестировать component-level render.

### Implementation

В user dropdown:

```txt
- show credits balance;
- add compact Buy Credits CTA;
- keep dropdown compact;
- do not render full credit pack pricing table here.
```

Пример copy:

```txt
Credits: 420
Need more? Buy credits
```

### Acceptance criteria

- Dropdown shows current credits.
- Dropdown shows Buy Credits CTA.
- CTA does not overcrowd dropdown.
- No billing page implementation here.
- Tests pass.

### Definition of Done

- Test written.
- CTA added to dropdown.
- Test passes.
- `npm run build` passes.
- Коммит: `CONV-272: Show buy credits CTA in user dropdown`

### Files likely touched

```txt
resources/views/components/user-dropdown.blade.php
resources/views/components/billing/buy-credits-cta.blade.php
tests/Feature/Dashboard/UserDropdownTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-273 — Show Buy Credits CTA On Insufficient Credits

**Area:** UI / Credits / Conversion  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-273-show-buy-credits-cta-on-insufficient-credits`  
**Base branch:** `develop`  
**Depends on:** CONV-272

### Goal

Показывать buy credits CTA в conversion flow, когда пользователю не хватает credits.

### TDD step

Livewire test:

```php
it('shows buy credits cta when conversion cannot start due to insufficient credits', function () {
    $user = User::factory()->create();
    app(CreditLedger::class)->spendAllForTest($user); // adapt to test helper

    $file = FileRecord::factory()->for($user)->png()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->set('targetFormat', 'pdf')
        ->call('startConversion')
        ->assertSee('Not enough credits')
        ->assertSee('Buy credits');
});
```

Адаптировать к реальной структуре `DashboardConverter`.

### Implementation

В insufficient credits handler/UI:

```txt
- catch InsufficientCreditsException;
- show readable message;
- render Buy Credits CTA component;
- do not create conversion job;
- do not dispatch queue job.
```

Copy:

```txt
Not enough credits.
This conversion requires 2 credits. You have 0 credits.
Buy credits to continue.
```

### Acceptance criteria

- Insufficient credits state shows clear message.
- Shows Buy Credits CTA.
- No conversion job created.
- No queue job dispatched.
- Existing sufficient credits flow still works.
- Tests pass.

### Definition of Done

- Livewire test written.
- UI state implemented.
- Buy Credits CTA rendered.
- Tests pass.
- `npm run build` passes.
- Коммит: `CONV-273: Show buy credits CTA on insufficient credits`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
resources/views/livewire/dashboard-converter.blade.php
resources/views/components/billing/buy-credits-cta.blade.php
tests/Feature/Livewire/DashboardConverterCreditsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 17 Completion Criteria

Phase 17 завершена, когда:

```txt
- CONV-257–CONV-273 выполнены;
- CreditPack enum exists;
- credit_packs config exists;
- .env.example contains credit pack Stripe price placeholders;
- CreditPackDto exists;
- CreditPackRepository can find packs by key;
- CreditPackRepository can find packs by Stripe price id;
- BillingPaymentService can create one-time credit pack checkout;
- /billing/credits/{pack} checkout route exists;
- guest cannot start credit pack checkout;
- unknown pack is rejected;
- credit pack success/cancel pages exist or safe redirects exist;
- CreditPackWebhookHandler exists;
- checkout.session.completed for credit pack grants credits;
- credits are granted through CreditLedger only;
- user plan is not changed after credit pack purchase;
- duplicate event/session does not grant credits twice;
- credit transaction metadata contains pack and Stripe identifiers;
- Buy Credits CTA component exists;
- user dropdown shows Buy Credits CTA;
- insufficient credits state shows Buy Credits CTA;
- no full billing page was created;
- no invoice history was created;
- no Spike integration was added;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 17

Без отдельной задачи нельзя:

```txt
- создавать полноценную /billing page;
- показывать полный pricing/credit packs catalog page;
- добавлять invoice history;
- управлять payment methods;
- подключать Stripe Customer Portal UI;
- добавлять coupons/discounts;
- добавлять tax/VAT logic;
- менять subscription checkout logic без необходимости;
- менять monthly subscription credit grant logic;
- менять users.plan после credit pack purchase;
- добавлять multiple credit types;
- добавлять credit expiration;
- добавлять reserve/capture model;
- добавлять teams/shared balances;
- добавлять admin credit adjustment UI;
- делать refund UI;
- добавлять API endpoints для покупки credits;
- подключать Spike;
- подключать Paddle;
- делать direct balance increment/decrement.
```

---

# 12. Recommended Execution Order

```txt
CONV-257 Create CreditPack Enum
CONV-258 Create Credit Packs Config
CONV-259 Create CreditPack DTO
CONV-260 Create CreditPackRepository
CONV-261 Test Credit Pack Checkout Creation
CONV-262 Implement Credit Pack Checkout Creation
CONV-263 Add Credit Pack Checkout Route
CONV-264 Add Credit Pack Checkout Redirect Pages
CONV-265 Create CreditPackWebhookHandler Skeleton
CONV-266 Test Credit Pack Purchase Grants Credits
CONV-267 Implement Credit Pack Purchase Grant
CONV-268 Test Duplicate Credit Pack Purchase Is Ignored
CONV-269 Implement Credit Pack Purchase Idempotency
CONV-270 Add Credit Pack Transaction Metadata
CONV-271 Add Buy Credits CTA Component
CONV-272 Show Buy Credits CTA In User Dropdown
CONV-273 Show Buy Credits CTA On Insufficient Credits
```

---

# 13. Release

После завершения Phase 17:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh --seed

git checkout -b release/v0.1.17-phase17-credit-packs
git push -u origin release/v0.1.17-phase17-credit-packs
```

После этого шага сделай MR в `main` branch и после этого остановись.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.17-phase17-credit-packs -m "File Converter Phase 17 Credit Packs"
git push origin v0.1.17-phase17-credit-packs
```
