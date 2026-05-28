# File Converter — Phase 16 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 16 — Laravel Cashier Foundation**  
Диапазон задач: **CONV-236 → CONV-256**  
Основа нумерации: Phase 15 завершилась на `CONV-235`, поэтому Phase 16 начинается с `CONV-236`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 16 соответствует блоку:

```txt
Phase 16 — Laravel Cashier Foundation
```

Правильный диапазон Phase 16:

```txt
CONV-236 — Install Laravel Cashier Stripe
CONV-237 — Publish Cashier Migrations And Add Billable Trait
CONV-238 — Configure Stripe Environment
CONV-239 — Create BillingPlan Enum
CONV-240 — Create Billing Plans Config
CONV-241 — Create BillingPlan DTO
CONV-242 — Create BillingPlanRepository
CONV-243 — Test BillingPaymentService Creates Subscription Checkout
CONV-244 — Implement BillingPaymentService Subscription Checkout
CONV-245 — Add Subscription Checkout Route
CONV-246 — Add Billing Success And Cancel Pages
CONV-247 — Configure Cashier Webhook Route
CONV-248 — Create BillingWebhookEvents Table
CONV-249 — Create BillingWebhookEventRecorder
CONV-250 — Create SubscriptionWebhookHandler Skeleton
CONV-251 — Test Subscription Activated Updates User Plan
CONV-252 — Implement Subscription Activated Handling
CONV-253 — Test Subscription Cancelled Downgrades User
CONV-254 — Implement Subscription Cancelled Handling
CONV-255 — Test Paid Invoice Grants Monthly Credits Once
CONV-256 — Implement Monthly Subscription Credit Grant
```

Phase 16 подключает **Laravel Cashier** как payment/subscription layer.

Cashier в этой архитектуре отвечает за:

```txt
- Stripe customer/subscription integration;
- subscription checkout;
- Stripe webhook intake;
- local plan update after Stripe events;
- monthly credit grants after paid subscription invoice.
```

Cashier **не отвечает** за:

```txt
- стоимость конвертации;
- credit ledger semantics;
- feature access;
- API access;
- file size limits;
- conversion job lifecycle;
- product-level usage history.
```

Эти зоны уже принадлежат:

```txt
FeatureAccessService
CreditLedger
ConversionCostEstimator
ConversionCreditCharge
CreateConversionJobAction
```

---

# 2. Цель Phase 16

Phase 16 должна добавить основу подписочного биллинга через Laravel Cashier без разрушения уже построенной custom credit architecture.

После Phase 16 приложение должно уметь:

```txt
- иметь установленный Laravel Cashier;
- хранить Stripe customer/subscription data через Cashier migrations;
- иметь Billable User model;
- иметь локальный BillingPlan config;
- создавать Stripe subscription checkout для Pro/Max plans;
- иметь success/cancel billing pages;
- принимать Stripe/Cashier webhook events;
- идемпотентно записывать обработанные billing webhook events;
- обновлять users.plan после subscription activation/update;
- downgrade user после cancellation по MVP-политике;
- начислять monthly credits после paid subscription invoice;
- не начислять credits повторно по одному и тому же invoice/event.
```

Главный принцип: **Cashier — внешний payment adapter, а не ядро доменной логики**.

---

# 3. Scope Phase 16

## Входит

```txt
- Laravel Cashier Stripe package;
- Cashier migrations;
- Billable trait on User;
- Stripe environment variables;
- BillingPlan enum;
- Billing plans config;
- BillingPlan DTO;
- BillingPlanRepository;
- BillingPaymentService;
- subscription checkout creation;
- subscription checkout route;
- billing success/cancel pages;
- Cashier webhook route configuration;
- billing_webhook_events table;
- BillingWebhookEventRecorder;
- SubscriptionWebhookHandler skeleton;
- subscription activated/updated handling;
- subscription cancelled handling;
- monthly subscription credit grant;
- idempotency for monthly grants.
```

## Не входит

```txt
- credit packs;
- one-time purchases;
- billing page UI;
- invoice history page;
- payment method management UI;
- Stripe Customer Portal UI;
- coupons;
- trials;
- tax/VAT logic;
- teams/business accounts;
- usage-based Stripe metering;
- Stripe Entitlements;
- API billing endpoints;
- webhooks for API clients;
- reserve/capture credit model;
- Spike integration.
```

Credit packs будут отдельной фазой.  
Billing page будет отдельной фазой.  
API billing будет отдельной фазой.

---

# 4. Critical Decisions

## 4.1. Cashier is not the credit system

Нельзя заменять custom `CreditLedger` на Cashier.

Неправильно:

```php
$user->credits = $stripeBalance;
```

Правильно:

```txt
Stripe/Cashier confirms payment
        ↓
Application grants credits through CreditLedger
        ↓
CreditLedger writes local credit_transactions
```

## 4.2. No direct Cashier calls in controllers

Нельзя размазывать Cashier по routes/controllers/Livewire.

Неправильно:

```php
return auth()->user()->newSubscription('default', $priceId)->checkout();
```

Правильно:

```php
return app(BillingPaymentService::class)->createSubscriptionCheckout($user, $plan);
```

Controllers/routes должны быть тонкими.

## 4.3. BillingPlan config is the local source of product intent

Stripe price IDs — это payment IDs, а не бизнес-модель.

Локальный config должен знать:

```txt
plan key;
plan label;
Stripe price id;
monthly credits;
feature plan key;
checkout mode;
```

`FeatureAccessService` продолжает читать plan/features из локального приложения.

## 4.4. Monthly credits are granted from local plan config

Нельзя хранить monthly credit amount только в Stripe metadata и строить домен вокруг Stripe dashboard.

Правильно:

```txt
billing_plans.php defines monthly_credits
webhook says subscription paid
app grants configured monthly_credits
```

Stripe — источник факта оплаты.  
Приложение — источник продуктовой логики.

## 4.5. Webhook handling must be idempotent

Stripe/Cashier webhook events могут приходить повторно.

Любое действие с деньгами/кредитами должно быть идемпотентным.

Особенно:

```txt
invoice paid → grant monthly credits
```

Один invoice не должен начислять credits дважды.

## 4.6. MVP cancellation policy is immediate downgrade unless explicitly changed

Для MVP фиксируем простую политику:

```txt
subscription cancelled / unpaid terminal state → user.plan = free
```

Более точная политика с grace period до `ends_at` может быть добавлена позже.

Если Cashier предоставляет active/onGracePeriod helpers, их можно учесть позже, но не усложнять Phase 16.

## 4.7. No credit packs in Phase 16

Не добавлять one-time checkout:

```txt
Buy 500 credits
Buy 2000 credits
Buy 10000 credits
```

Это следующая фаза. Phase 16 — только subscription foundation.

---

# 5. Architecture Rules

## 5.1. Cashier integration layer

Рекомендуемая структура:

```txt
app/Billing/BillingPlan.php
app/Billing/BillingPlanDto.php
app/Billing/BillingPlanRepository.php
app/Billing/BillingPaymentService.php
app/Billing/Webhooks/BillingWebhookEventRecorder.php
app/Billing/Webhooks/SubscriptionWebhookHandler.php
config/billing_plans.php
```

Можно адаптировать под существующие conventions проекта, но не складывать всё в controllers.

## 5.2. Use existing Plan enum if already created

Phase 13 уже должна была создать plan model/enum для `free/pro/max`.

Если существует:

```txt
App\Enums\Plan
```

не создавать дубликат.

Если enum был назван иначе — использовать существующее имя и явно указать это в MR description.

## 5.3. Use existing CreditLedger

Phase 14 уже должна была создать:

```txt
CreditLedger interface
DatabaseCreditLedger implementation
credit_accounts
credit_transactions
```

Phase 16 обязана начислять subscription credits только через `CreditLedger`.

Нельзя делать:

```php
$user->creditAccount->increment('balance', 1000);
```

Правильно:

```php
$this->creditLedger->grant($user, $amount, 'subscription_monthly_grant', $metadata);
```

## 5.4. Use existing FeatureAccessService indirectly

Phase 16 обновляет `users.plan`.  
`FeatureAccessService` уже должен начать возвращать новые лимиты на основе `users.plan`.

Не дублировать feature config внутри BillingPaymentService.

## 5.5. No UI-heavy billing page

Phase 16 может иметь только:

```txt
billing success page
billing cancel page
```

Полноценная `/billing` page с plan cards, invoice history, credit packs — не входит.

---

# 6. GitFlow для Phase 16

## Base branch

Все задачи Phase 16 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-236-install-laravel-cashier-stripe
feature/CONV-244-implement-billing-payment-service-subscription-checkout
feature/CONV-256-implement-monthly-subscription-credit-grant
```

## Commit format

```txt
CONV-236: Install Laravel Cashier Stripe
CONV-244: Implement BillingPaymentService subscription checkout
CONV-256: Implement monthly subscription credit grant
```

## Release branch

После выполнения `CONV-236`–`CONV-256`:

```txt
release/v0.1.16-phase16-laravel-cashier-foundation
```

## Tag

После merge release branch в `main`:

```txt
v0.1.16-phase16-laravel-cashier-foundation
```

---

# 7. TDD Rules for Phase 16

## Для config/repository

Тестировать:

```txt
- Free/Pro/Max plans exist;
- paid plans have Stripe price ids;
- paid plans have monthly credits;
- invalid plan key is rejected;
- repository returns DTO, not raw array.
```

## Для BillingPaymentService

Тестировать через fake/mocked boundary:

```txt
- service rejects free checkout;
- service creates checkout for pro/max;
- service returns checkout URL;
- controllers do not call Cashier directly.
```

Если прямое тестирование Cashier checkout трудно без Stripe, тестировать service boundary и route wiring, а actual Stripe integration проверять manual/dev smoke через Stripe test mode.

## Для webhooks

Тестировать application handlers, а не реальный Stripe HTTP payload полностью.

```txt
- activated subscription updates user plan;
- cancelled subscription downgrades user;
- paid invoice grants monthly credits once;
- duplicate event/invoice does not grant twice.
```

## Для credit grants

Обязательно test-first:

```txt
- credits granted via CreditLedger;
- credit transaction metadata includes Stripe invoice id/event id;
- duplicate invoice ignored.
```

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Billing / Cashier / Webhooks / Tests / Config
Type: Setup / Test / Feature / Config / Service
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
- npm run build проходит, если затронут frontend
- Нет функциональности вне scope задачи
- Нет прямых Cashier calls вне BillingPaymentService / webhook layer
- CreditLedger используется для credits
- Коммит содержит ID задачи

Files likely touched:
- path/to/file
```

---

# 9. Phase 16 Atomic Tasks

---

## CONV-236 — Install Laravel Cashier Stripe

**Area:** Billing / Cashier  
**Type:** Setup  
**Priority:** P0  
**Branch:** `feature/CONV-236-install-laravel-cashier-stripe`  
**Base branch:** `develop`  
**Depends on:** CONV-235

### Goal

Установить Laravel Cashier Stripe package.

### TDD step

No direct test — package installation.

После установки должны проходить:

```bash
composer test
composer lint
```

### Implementation

Установить Cashier:

```bash
composer require laravel/cashier
```

Проверить, что приложение bootstraps:

```bash
php artisan about
php artisan test
```

Не публиковать migrations в этой задаче.  
Не добавлять `Billable` trait в этой задаче.  
Не добавлять Stripe routes в этой задаче.

### Acceptance criteria

- `laravel/cashier` добавлен в `composer.json`.
- `composer.lock` обновлён.
- Приложение запускается.
- `composer test` проходит.
- Нет Cashier usage в коде приложения пока.

### Definition of Done

- Cashier установлен.
- Тесты проходят.
- Линтер проходит.
- Коммит: `CONV-236: Install Laravel Cashier Stripe`

### Files likely touched

```txt
composer.json
composer.lock
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-237 — Publish Cashier Migrations And Add Billable Trait

**Area:** Billing / Cashier / Database  
**Type:** Setup  
**Priority:** P0  
**Branch:** `feature/CONV-237-publish-cashier-migrations-and-add-billable-trait`  
**Base branch:** `develop`  
**Depends on:** CONV-236

### Goal

Подготовить User model и database schema для Cashier.

### TDD step

Feature/unit test:

```php
it('uses cashier billable trait on user model', function () {
    $traits = class_uses_recursive(\App\Models\User::class);

    expect($traits)->toHaveKey(\Laravel\Cashier\Billable::class);
});
```

Тест должен упасть до добавления trait.

### Implementation

Опубликовать Cashier migrations:

```bash
php artisan vendor:publish --tag="cashier-migrations"
```

Добавить `Billable` в `User`:

```php
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use Billable;
}
```

Запустить migrations locally/test:

```bash
php artisan migrate
php artisan test
```

Не менять custom credits tables.  
Не добавлять Stripe checkout logic.

### Acceptance criteria

- Cashier migrations опубликованы или корректно подключены.
- User использует `Billable` trait.
- Existing user plan/credits logic не сломан.
- `php artisan migrate` проходит.
- Тест на Billable trait проходит.

### Definition of Done

- Тест написан первым.
- Migrations опубликованы.
- Billable trait добавлен.
- Тесты проходят.
- Коммит: `CONV-237: Publish Cashier migrations and add Billable trait`

### Files likely touched

```txt
app/Models/User.php
database/migrations/*cashier*.php
tests/Unit/Billing/CashierBillableTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint` проходят.

---

## CONV-238 — Configure Stripe Environment

**Area:** Billing / Config  
**Type:** Config  
**Priority:** P0  
**Branch:** `feature/CONV-238-configure-stripe-environment`  
**Base branch:** `develop`  
**Depends on:** CONV-237

### Goal

Добавить Stripe/Cashier environment variables без хардкода ключей.

### TDD step

Config test:

```php
it('has cashier stripe configuration keys', function () {
    expect(config('cashier.key'))->not->toBeNull();
});
```

Если config key differs by Cashier version, адаптировать тест к реальной структуре config.

### Implementation

Обновить `.env.example`:

```env
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
CASHIER_CURRENCY=eur
```

Если publish cashier config нужен:

```bash
php artisan vendor:publish --tag="cashier-config"
```

Не добавлять реальные ключи.

### Acceptance criteria

- `.env.example` содержит Stripe/Cashier placeholders.
- Реальные secrets не закоммичены.
- App boots without real Stripe keys in testing.
- Config test passes.

### Definition of Done

- Env placeholders добавлены.
- Config не ломает tests.
- Тесты проходят.
- Коммит: `CONV-238: Configure Stripe environment`

### Files likely touched

```txt
.env.example
config/cashier.php
tests/Unit/Billing/CashierConfigTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint` проходят.

---

## CONV-239 — Create BillingPlan Enum

**Area:** Billing / Domain  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-239-create-billing-plan-enum`  
**Base branch:** `develop`  
**Depends on:** CONV-238

### Goal

Создать или переиспользовать enum планов для billing layer.

### TDD step

Unit test:

```php
it('has free pro and max billing plans', function () {
    expect(BillingPlan::Free->value)->toBe('free');
    expect(BillingPlan::Pro->value)->toBe('pro');
    expect(BillingPlan::Max->value)->toBe('max');
});
```

Если Phase 13 уже создала `Plan` enum, тест должен проверять существующий enum, а задача должна не плодить дубликат.

### Implementation

Если enum ещё нет:

```txt
app/Enums/BillingPlan.php
```

```php
enum BillingPlan: string
{
    case Free = 'free';
    case Pro = 'pro';
    case Max = 'max';
}
```

Если уже есть `Plan`, использовать его и добавить alias/import в billing classes later.

### Acceptance criteria

- Есть единый enum для free/pro/max.
- Нет дубликата plan enum, если он уже был создан.
- User plan field продолжает работать.
- Unit test passes.

### Definition of Done

- Тест написан первым.
- Enum создан или переиспользован.
- Тесты проходят.
- Коммит: `CONV-239: Create BillingPlan enum`

### Files likely touched

```txt
app/Enums/BillingPlan.php
app/Enums/Plan.php
tests/Unit/Billing/BillingPlanTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint` проходят.

---

## CONV-240 — Create Billing Plans Config

**Area:** Billing / Config  
**Type:** Config  
**Priority:** P0  
**Branch:** `feature/CONV-240-create-billing-plans-config`  
**Base branch:** `develop`  
**Depends on:** CONV-239

### Goal

Создать локальный config планов, который связывает product plan с Stripe price ID и monthly credits.

### TDD step

Config test:

```php
it('defines billing plans config for free pro and max', function () {
    $plans = config('billing_plans.plans');

    expect($plans)->toHaveKeys(['free', 'pro', 'max']);
});
```

Paid plan validation:

```php
it('defines stripe price ids for paid billing plans', function () {
    expect(config('billing_plans.plans.pro.stripe_price_id'))->not->toBeEmpty();
    expect(config('billing_plans.plans.max.stripe_price_id'))->not->toBeEmpty();
});
```

В testing можно использовать placeholder price IDs.

### Implementation

Создать:

```txt
config/billing_plans.php
```

Пример:

```php
return [
    'plans' => [
        'free' => [
            'label' => 'Free',
            'stripe_price_id' => null,
            'monthly_credits' => 50,
            'is_paid' => false,
        ],
        'pro' => [
            'label' => 'Pro',
            'stripe_price_id' => env('STRIPE_PRO_PRICE_ID', 'price_test_pro'),
            'monthly_credits' => 1000,
            'is_paid' => true,
        ],
        'max' => [
            'label' => 'Max',
            'stripe_price_id' => env('STRIPE_MAX_PRICE_ID', 'price_test_max'),
            'monthly_credits' => 5000,
            'is_paid' => true,
        ],
    ],
];
```

Обновить `.env.example`:

```env
STRIPE_PRO_PRICE_ID=
STRIPE_MAX_PRICE_ID=
```

### Acceptance criteria

- `billing_plans.php` существует.
- Free/Pro/Max определены.
- Paid plans имеют Stripe price ID placeholders.
- Paid plans имеют monthly credits.
- Free не требует Stripe price ID.
- Tests pass.

### Definition of Done

- Config tests написаны.
- Config создан.
- `.env.example` обновлён.
- Тесты проходят.
- Коммит: `CONV-240: Create billing plans config`

### Files likely touched

```txt
config/billing_plans.php
.env.example
tests/Unit/Billing/BillingPlansConfigTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint` проходят.

---

## CONV-241 — Create BillingPlan DTO

**Area:** Billing / Domain  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-241-create-billing-plan-dto`  
**Base branch:** `develop`  
**Depends on:** CONV-240

### Goal

Создать DTO для billing plan, чтобы application layer не работал с raw config arrays.

### TDD step

Unit test:

```php
it('creates billing plan dto from config data', function () {
    $dto = new BillingPlanDto(
        key: 'pro',
        label: 'Pro',
        stripePriceId: 'price_test_pro',
        monthlyCredits: 1000,
        isPaid: true,
    );

    expect($dto->key)->toBe('pro');
    expect($dto->monthlyCredits)->toBe(1000);
    expect($dto->isPaid)->toBeTrue();
});
```

### Implementation

Создать:

```txt
app/Billing/BillingPlanDto.php
```

DTO:

```php
final readonly class BillingPlanDto
{
    public function __construct(
        public string $key,
        public string $label,
        public ?string $stripePriceId,
        public int $monthlyCredits,
        public bool $isPaid,
    ) {}
}
```

### Acceptance criteria

- DTO exists.
- DTO readonly/immutable.
- DTO covers key/label/stripePriceId/monthlyCredits/isPaid.
- Unit test passes.

### Definition of Done

- Тест написан первым.
- DTO создан.
- Тесты проходят.
- Коммит: `CONV-241: Create BillingPlan DTO`

### Files likely touched

```txt
app/Billing/BillingPlanDto.php
tests/Unit/Billing/BillingPlanDtoTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint` проходят.

---

## CONV-242 — Create BillingPlanRepository

**Area:** Billing / Config  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-242-create-billing-plan-repository`  
**Base branch:** `develop`  
**Depends on:** CONV-241

### Goal

Создать repository для безопасного доступа к billing plans config.

### TDD step

Unit tests:

```php
it('returns paid billing plan by key', function () {
    $plan = app(BillingPlanRepository::class)->findOrFail('pro');

    expect($plan->key)->toBe('pro');
    expect($plan->isPaid)->toBeTrue();
});
```

```php
it('rejects unknown billing plan key', function () {
    app(BillingPlanRepository::class)->findOrFail('unknown');
})->throws(UnknownBillingPlanException::class);
```

### Implementation

Создать:

```txt
app/Billing/BillingPlanRepository.php
app/Billing/Exceptions/UnknownBillingPlanException.php
```

Methods:

```php
public function all(): array;
public function paid(): array;
public function findOrFail(string $key): BillingPlanDto;
```

### Acceptance criteria

- Repository returns DTOs.
- Repository rejects unknown plan.
- `paid()` excludes Free.
- No raw config arrays leak to controllers.
- Tests pass.

### Definition of Done

- Тесты написаны.
- Repository создан.
- Exception создан.
- Тесты проходят.
- Коммит: `CONV-242: Create BillingPlanRepository`

### Files likely touched

```txt
app/Billing/BillingPlanRepository.php
app/Billing/Exceptions/UnknownBillingPlanException.php
tests/Unit/Billing/BillingPlanRepositoryTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint` проходят.

---

## CONV-243 — Test BillingPaymentService Creates Subscription Checkout

**Area:** Billing / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-243-test-billing-payment-service-creates-subscription-checkout`  
**Base branch:** `develop`  
**Depends on:** CONV-242

### Goal

Написать падающий тест на service boundary для subscription checkout.

### TDD step

Service test with fake boundary:

```php
it('creates subscription checkout for paid plan', function () {
    $user = User::factory()->create();
    $plan = app(BillingPlanRepository::class)->findOrFail('pro');

    $service = app(BillingPaymentService::class);

    $checkout = $service->createSubscriptionCheckout(
        user: $user,
        plan: $plan,
        successUrl: 'https://example.test/billing/success',
        cancelUrl: 'https://example.test/billing/cancel',
    );

    expect($checkout->url)->not->toBeEmpty();
});
```

Если реальный Cashier call нельзя безопасно выполнить в test без Stripe, ввести abstraction/fake:

```txt
SubscriptionCheckoutGateway
CashierSubscriptionCheckoutGateway
FakeSubscriptionCheckoutGateway
```

В этой задаче можно написать тест под будущий `BillingPaymentService`, который пока падает из-за отсутствия класса.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Тест проверяет paid plan checkout.
- Тест не требует реального Stripe API call.
- Тест падает до CONV-244.

### Definition of Done

- Тест написан.
- Тест ожидаемо падает.
- Коммит: `CONV-243: Test BillingPaymentService creates subscription checkout`

### Files likely touched

```txt
tests/Feature/Billing/BillingPaymentServiceTest.php
```

После этого сделай MR в `develop`. Merge разрешён с ожидаемо падающим тестом только если workflow допускает red-step MR. Если нет — объединить CONV-243 и CONV-244 в один MR, но сохранить test-first порядок в commit history.

---

## CONV-244 — Implement BillingPaymentService Subscription Checkout

**Area:** Billing / Service  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-244-implement-billing-payment-service-subscription-checkout`  
**Base branch:** `develop`  
**Depends on:** CONV-243

### Goal

Реализовать service, который создаёт subscription checkout для paid plans.

### TDD step

Использовать падающий тест из CONV-243.

Дополнительный тест:

```php
it('does not create subscription checkout for free plan', function () {
    $user = User::factory()->create();
    $plan = app(BillingPlanRepository::class)->findOrFail('free');

    app(BillingPaymentService::class)->createSubscriptionCheckout(
        user: $user,
        plan: $plan,
        successUrl: 'https://example.test/success',
        cancelUrl: 'https://example.test/cancel',
    );
})->throws(CannotCheckoutFreePlanException::class);
```

### Implementation

Создать:

```txt
app/Billing/BillingPaymentService.php
app/Billing/Exceptions/CannotCheckoutFreePlanException.php
```

Implementation direction:

```php
final class BillingPaymentService
{
    public function createSubscriptionCheckout(
        User $user,
        BillingPlanDto $plan,
        string $successUrl,
        string $cancelUrl,
    ): CheckoutSessionDto {
        if (! $plan->isPaid || $plan->stripePriceId === null) {
            throw CannotCheckoutFreePlanException::make();
        }

        $checkout = $user
            ->newSubscription('default', $plan->stripePriceId)
            ->checkout([
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ]);

        return new CheckoutSessionDto(url: $checkout->url);
    }
}
```

Если real Cashier call hard to test, wrap it behind gateway and fake the gateway in tests.

### Acceptance criteria

- BillingPaymentService exists.
- Free plan checkout rejected.
- Pro/Max checkout supported.
- Controllers/routes do not call Cashier directly.
- Tests pass without real Stripe network calls.

### Definition of Done

- Service implemented.
- Exception implemented.
- Tests pass.
- No direct Cashier calls outside service.
- Коммит: `CONV-244: Implement BillingPaymentService subscription checkout`

### Files likely touched

```txt
app/Billing/BillingPaymentService.php
app/Billing/CheckoutSessionDto.php
app/Billing/Exceptions/CannotCheckoutFreePlanException.php
tests/Feature/Billing/BillingPaymentServiceTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint` проходят.

---

## CONV-245 — Add Subscription Checkout Route

**Area:** Billing / Routes  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-245-add-subscription-checkout-route`  
**Base branch:** `develop`  
**Depends on:** CONV-244

### Goal

Добавить protected route для запуска subscription checkout.

### TDD step

Feature test:

```php
it('requires auth to start subscription checkout', function () {
    $this->post('/billing/checkout/pro')
        ->assertRedirect('/login');
});
```

Feature test with mocked service:

```php
it('starts subscription checkout for pro plan', function () {
    $user = User::factory()->create();

    $this->mock(BillingPaymentService::class, function ($mock) {
        $mock->shouldReceive('createSubscriptionCheckout')
            ->once()
            ->andReturn(new CheckoutSessionDto(url: 'https://checkout.stripe.test/session'));
    });

    $this->actingAs($user)
        ->post('/billing/checkout/pro')
        ->assertRedirect('https://checkout.stripe.test/session');
});
```

### Implementation

Add route:

```php
Route::post('/billing/checkout/{plan}', StartSubscriptionCheckoutController::class)
    ->middleware('auth')
    ->name('billing.checkout');
```

Create controller:

```txt
app/Http/Controllers/Billing/StartSubscriptionCheckoutController.php
```

Controller responsibilities:

```txt
- read plan key;
- resolve BillingPlanRepository;
- call BillingPaymentService;
- redirect to checkout URL.
```

### Acceptance criteria

- Route exists.
- Guest redirected to login.
- Auth user can start paid plan checkout.
- Invalid plan rejected.
- Controller uses BillingPaymentService.
- No direct Cashier calls in controller.

### Definition of Done

- Tests written.
- Route/controller implemented.
- Tests pass.
- Коммит: `CONV-245: Add subscription checkout route`

### Files likely touched

```txt
routes/web.php
app/Http/Controllers/Billing/StartSubscriptionCheckoutController.php
tests/Feature/Billing/SubscriptionCheckoutRouteTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint` проходят.

---

## CONV-246 — Add Billing Success And Cancel Pages

**Area:** Billing / UI  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-246-add-billing-success-and-cancel-pages`  
**Base branch:** `develop`  
**Depends on:** CONV-245

### Goal

Добавить минимальные страницы результата checkout.

### TDD step

Feature tests:

```php
it('renders billing success page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/billing/success')
        ->assertOk()
        ->assertSee('Billing successful');
});
```

```php
it('renders billing cancel page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/billing/cancel')
        ->assertOk()
        ->assertSee('Billing cancelled');
});
```

### Implementation

Routes:

```php
Route::view('/billing/success', 'billing.success')
    ->middleware('auth')
    ->name('billing.success');

Route::view('/billing/cancel', 'billing.cancel')
    ->middleware('auth')
    ->name('billing.cancel');
```

Views:

```txt
resources/views/billing/success.blade.php
resources/views/billing/cancel.blade.php
```

Content should be minimal:

```txt
Billing successful
Your subscription is being activated. This may take a moment.
```

```txt
Billing cancelled
No changes were made to your subscription.
```

### Acceptance criteria

- Success page exists.
- Cancel page exists.
- Both are auth-protected.
- Pages do not claim plan activated until webhook confirms.
- Tests pass.

### Definition of Done

- Tests written.
- Routes/views added.
- Tests pass.
- Коммит: `CONV-246: Add billing success and cancel pages`

### Files likely touched

```txt
routes/web.php
resources/views/billing/success.blade.php
resources/views/billing/cancel.blade.php
tests/Feature/Billing/BillingResultPagesTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-247 — Configure Cashier Webhook Route

**Area:** Billing / Webhooks  
**Type:** Config  
**Priority:** P0  
**Branch:** `feature/CONV-247-configure-cashier-webhook-route`  
**Base branch:** `develop`  
**Depends on:** CONV-246

### Goal

Настроить webhook endpoint для Cashier/Stripe events.

### TDD step

Feature smoke test:

```php
it('has cashier webhook endpoint registered', function () {
    $response = $this->postJson('/stripe/webhook', []);

    expect($response->status())->not->toBe(404);
});
```

Точный response может быть 400/403 из-за signature validation. Главное — route exists.

### Implementation

Использовать стандартный Cashier webhook endpoint, если пакет регистрирует его автоматически. Если route нужно добавить/настроить вручную — сделать минимально.

Убедиться, что `.env.example` содержит:

```env
STRIPE_WEBHOOK_SECRET=
```

Не писать бизнес-логику обработки events в этой задаче.

### Acceptance criteria

- Webhook endpoint registered.
- Endpoint не 404.
- Webhook secret documented in `.env.example`.
- No credit grants yet.
- Tests pass.

### Definition of Done

- Smoke test написан.
- Webhook route configured.
- Тесты проходят.
- Коммит: `CONV-247: Configure Cashier webhook route`

### Files likely touched

```txt
.env.example
routes/web.php
routes/api.php
tests/Feature/Billing/CashierWebhookRouteTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint` проходят.

---

## CONV-248 — Create BillingWebhookEvents Table

**Area:** Billing / Database / Webhooks  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-248-create-billing-webhook-events-table`  
**Base branch:** `develop`  
**Depends on:** CONV-247

### Goal

Создать таблицу для идемпотентной обработки billing webhook events.

### TDD step

Migration/model test:

```php
it('can store billing webhook event', function () {
    $event = BillingWebhookEvent::create([
        'provider' => 'stripe',
        'provider_event_id' => 'evt_test_123',
        'type' => 'invoice.paid',
        'payload' => ['id' => 'evt_test_123'],
        'processed_at' => null,
    ]);

    expect($event->exists)->toBeTrue();
});
```

### Implementation

Migration:

```txt
billing_webhook_events
```

Fields:

```txt
id
provider
provider_event_id unique
type
payload json nullable
processed_at nullable
created_at
updated_at
```

Model:

```txt
app/Models/BillingWebhookEvent.php
```

### Acceptance criteria

- Table exists.
- `provider_event_id` unique.
- Payload cast to array.
- Model/factory exists.
- Test passes.

### Definition of Done

- Тест написан.
- Migration/model/factory созданы.
- Тесты проходят.
- Коммит: `CONV-248: Create billing webhook events table`

### Files likely touched

```txt
database/migrations/*create_billing_webhook_events_table.php
app/Models/BillingWebhookEvent.php
database/factories/BillingWebhookEventFactory.php
tests/Feature/Billing/BillingWebhookEventTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint` проходят.

---

## CONV-249 — Create BillingWebhookEventRecorder

**Area:** Billing / Webhooks  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-249-create-billing-webhook-event-recorder`  
**Base branch:** `develop`  
**Depends on:** CONV-248

### Goal

Создать сервис для идемпотентной записи и проверки webhook events.

### TDD step

Unit/feature tests:

```php
it('records billing webhook event only once', function () {
    $recorder = app(BillingWebhookEventRecorder::class);

    $first = $recorder->recordIfNew('stripe', 'evt_123', 'invoice.paid', ['id' => 'evt_123']);
    $second = $recorder->recordIfNew('stripe', 'evt_123', 'invoice.paid', ['id' => 'evt_123']);

    expect($first->wasRecentlyCreated)->toBeTrue();
    expect($second->wasRecentlyCreated)->toBeFalse();
    expect(BillingWebhookEvent::query()->where('provider_event_id', 'evt_123')->count())->toBe(1);
});
```

### Implementation

Создать:

```txt
app/Billing/Webhooks/BillingWebhookEventRecorder.php
```

Methods:

```php
public function recordIfNew(string $provider, string $eventId, string $type, array $payload): BillingWebhookEvent;
public function markProcessed(BillingWebhookEvent $event): void;
public function wasProcessed(string $provider, string $eventId): bool;
```

Use DB unique constraint to protect race conditions.

### Acceptance criteria

- Recorder creates event once.
- Duplicate event does not create duplicate row.
- Processed event can be marked.
- Tests pass.

### Definition of Done

- Tests written.
- Recorder implemented.
- Тесты проходят.
- Коммит: `CONV-249: Create BillingWebhookEventRecorder`

### Files likely touched

```txt
app/Billing/Webhooks/BillingWebhookEventRecorder.php
tests/Feature/Billing/BillingWebhookEventRecorderTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint` проходят.

---

## CONV-250 — Create SubscriptionWebhookHandler Skeleton

**Area:** Billing / Webhooks  
**Type:** Service  
**Priority:** P0  
**Branch:** `feature/CONV-250-create-subscription-webhook-handler-skeleton`  
**Base branch:** `develop`  
**Depends on:** CONV-249

### Goal

Создать skeleton handler для subscription-related Stripe/Cashier events.

### TDD step

Unit test:

```php
it('resolves subscription webhook handler from container', function () {
    expect(app(SubscriptionWebhookHandler::class))->toBeInstanceOf(SubscriptionWebhookHandler::class);
});
```

### Implementation

Создать:

```txt
app/Billing/Webhooks/SubscriptionWebhookHandler.php
```

Skeleton methods:

```php
public function handleSubscriptionActivated(User $user, string $planKey, array $payload = []): void;
public function handleSubscriptionCancelled(User $user, array $payload = []): void;
public function handleInvoicePaid(User $user, string $planKey, string $invoiceId, array $payload = []): void;
```

На этом этапе методы могут бросать `LogicException('Not implemented yet')`, кроме если тест требует только container resolve.

### Acceptance criteria

- Handler exists.
- Handler resolves from container.
- Method names fixed for next tasks.
- No business logic yet.
- Test passes.

### Definition of Done

- Тест написан.
- Skeleton создан.
- Тесты проходят.
- Коммит: `CONV-250: Create SubscriptionWebhookHandler skeleton`

### Files likely touched

```txt
app/Billing/Webhooks/SubscriptionWebhookHandler.php
tests/Unit/Billing/SubscriptionWebhookHandlerSkeletonTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint` проходят.

---

## CONV-251 — Test Subscription Activated Updates User Plan

**Area:** Billing / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-251-test-subscription-activated-updates-user-plan`  
**Base branch:** `develop`  
**Depends on:** CONV-250

### Goal

Написать падающий тест: activation/update subscription переводит пользователя на paid plan.

### TDD step

Feature/unit test:

```php
it('updates user plan when subscription is activated', function () {
    $user = User::factory()->create([
        'plan' => 'free',
    ]);

    app(SubscriptionWebhookHandler::class)->handleSubscriptionActivated(
        user: $user,
        planKey: 'pro',
        payload: ['stripe_subscription_id' => 'sub_test_123'],
    );

    expect($user->fresh()->plan)->toBe('pro');
});
```

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Тест проверяет переход free → pro.
- Тест падает до CONV-252.

### Definition of Done

- Тест написан.
- Тест ожидаемо падает.
- Коммит: `CONV-251: Test subscription activated updates user plan`

### Files likely touched

```txt
tests/Feature/Billing/SubscriptionWebhookHandlerTest.php
```

После этого сделай MR в `develop`. Если red-step MR не допускается, объединить с CONV-252, но сохранить test-first порядок.

---

## CONV-252 — Implement Subscription Activated Handling

**Area:** Billing / Webhooks  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-252-implement-subscription-activated-handling`  
**Base branch:** `develop`  
**Depends on:** CONV-251

### Goal

Реализовать обновление local user plan после subscription activation/update.

### TDD step

Использовать падающий тест из CONV-251.

Дополнительный тест:

```php
it('rejects unknown plan during subscription activation', function () {
    $user = User::factory()->create(['plan' => 'free']);

    app(SubscriptionWebhookHandler::class)->handleSubscriptionActivated($user, 'unknown');
})->throws(UnknownBillingPlanException::class);
```

### Implementation

В `SubscriptionWebhookHandler`:

```php
public function handleSubscriptionActivated(User $user, string $planKey, array $payload = []): void
{
    $plan = $this->plans->findOrFail($planKey);

    if (! $plan->isPaid) {
        throw CannotActivateFreePlanFromSubscriptionException::make();
    }

    $user->forceFill([
        'plan' => $plan->key,
    ])->save();
}
```

Создать exception для free plan activation if needed.

### Acceptance criteria

- Subscription activation updates user plan.
- Unknown plan rejected.
- Free plan cannot be activated by paid subscription event.
- FeatureAccessService should now see paid features through updated user plan.
- Tests pass.

### Definition of Done

- Handler implemented.
- Tests pass.
- Коммит: `CONV-252: Implement subscription activated handling`

### Files likely touched

```txt
app/Billing/Webhooks/SubscriptionWebhookHandler.php
app/Billing/Exceptions/CannotActivateFreePlanFromSubscriptionException.php
tests/Feature/Billing/SubscriptionWebhookHandlerTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint` проходят.

---

## CONV-253 — Test Subscription Cancelled Downgrades User

**Area:** Billing / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-253-test-subscription-cancelled-downgrades-user`  
**Base branch:** `develop`  
**Depends on:** CONV-252

### Goal

Написать падающий тест: cancellation downgrades user to free по MVP-политике.

### TDD step

Feature/unit test:

```php
it('downgrades user to free when subscription is cancelled', function () {
    $user = User::factory()->create([
        'plan' => 'pro',
    ]);

    app(SubscriptionWebhookHandler::class)->handleSubscriptionCancelled(
        user: $user,
        payload: ['stripe_subscription_id' => 'sub_test_123'],
    );

    expect($user->fresh()->plan)->toBe('free');
});
```

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Тест фиксирует MVP cancellation policy.
- Тест падает до CONV-254.

### Definition of Done

- Тест написан.
- Тест ожидаемо падает.
- Коммит: `CONV-253: Test subscription cancelled downgrades user`

### Files likely touched

```txt
tests/Feature/Billing/SubscriptionWebhookHandlerTest.php
```

После этого сделай MR в `develop`. Если red-step MR не допускается, объединить с CONV-254, но сохранить test-first порядок.

---

## CONV-254 — Implement Subscription Cancelled Handling

**Area:** Billing / Webhooks  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-254-implement-subscription-cancelled-handling`  
**Base branch:** `develop`  
**Depends on:** CONV-253

### Goal

Реализовать downgrade пользователя на free после subscription cancellation.

### TDD step

Использовать падающий тест из CONV-253.

### Implementation

В `SubscriptionWebhookHandler`:

```php
public function handleSubscriptionCancelled(User $user, array $payload = []): void
{
    $user->forceFill([
        'plan' => 'free',
    ])->save();
}
```

Не удалять credit balance.  
Не удалять conversion history.  
Не отзывать API keys в этой задаче — API middleware позже сам увидит `api_access = false` через FeatureAccessService.

### Acceptance criteria

- Cancelled subscription downgrades user to free.
- Credits balance remains unchanged.
- API access will be disabled by FeatureAccessService through plan change.
- Tests pass.

### Definition of Done

- Cancellation handling implemented.
- Tests pass.
- Коммит: `CONV-254: Implement subscription cancelled handling`

### Files likely touched

```txt
app/Billing/Webhooks/SubscriptionWebhookHandler.php
tests/Feature/Billing/SubscriptionWebhookHandlerTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint` проходят.

---

## CONV-255 — Test Paid Invoice Grants Monthly Credits Once

**Area:** Billing / Tests / Credits  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-255-test-paid-invoice-grants-monthly-credits-once`  
**Base branch:** `develop`  
**Depends on:** CONV-254

### Goal

Написать падающий тест: paid invoice начисляет monthly credits один раз.

### TDD step

Feature test:

```php
it('grants monthly subscription credits once for paid invoice', function () {
    $user = User::factory()->create([
        'plan' => 'pro',
    ]);

    app(CreditLedger::class)->grant($user, 0, 'test_initial_balance');

    $handler = app(SubscriptionWebhookHandler::class);

    $handler->handleInvoicePaid(
        user: $user,
        planKey: 'pro',
        invoiceId: 'in_test_123',
        payload: ['event_id' => 'evt_test_1'],
    );

    $handler->handleInvoicePaid(
        user: $user,
        planKey: 'pro',
        invoiceId: 'in_test_123',
        payload: ['event_id' => 'evt_test_1_duplicate'],
    );

    expect(app(CreditLedger::class)->balance($user))->toBe(1000);
});
```

Use actual Pro monthly credits from `billing_plans.php`.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Первый invoice grants monthly credits.
- Duplicate invoice does not grant twice.
- Тест падает до CONV-256.

### Definition of Done

- Тест написан.
- Тест ожидаемо падает.
- Коммит: `CONV-255: Test paid invoice grants monthly credits once`

### Files likely touched

```txt
tests/Feature/Billing/SubscriptionMonthlyCreditGrantTest.php
```

После этого сделай MR в `develop`. Если red-step MR не допускается, объединить с CONV-256, но сохранить test-first порядок.

---

## CONV-256 — Implement Monthly Subscription Credit Grant

**Area:** Billing / Webhooks / Credits  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-256-implement-monthly-subscription-credit-grant`  
**Base branch:** `develop`  
**Depends on:** CONV-255

### Goal

Реализовать начисление monthly credits после paid invoice через custom CreditLedger.

### TDD step

Использовать падающий тест из CONV-255.

Дополнительные тесты:

```php
it('does not grant credits for free plan invoice', ...);
it('stores invoice id in credit transaction metadata', ...);
```

### Implementation

В `SubscriptionWebhookHandler`:

```php
public function handleInvoicePaid(
    User $user,
    string $planKey,
    string $invoiceId,
    array $payload = [],
): void {
    $plan = $this->plans->findOrFail($planKey);

    if (! $plan->isPaid) {
        return;
    }

    if ($this->alreadyGrantedForInvoice($invoiceId)) {
        return;
    }

    $this->creditLedger->grant(
        user: $user,
        amount: $plan->monthlyCredits,
        reason: 'subscription_monthly_grant',
        meta: [
            'stripe_invoice_id' => $invoiceId,
            'plan' => $plan->key,
            'payload' => $payload,
        ],
    );
}
```

Idempotency implementation options:

```txt
- query credit_transactions metadata by stripe_invoice_id;
- or create dedicated billing_grants table;
- or use billing_webhook_events if invoice event id is stable.
```

Recommended MVP:

```txt
credit_transactions.metadata_json->stripe_invoice_id unique behavior at application level
```

If DB-level JSON unique index is awkward, use dedicated table later. For Phase 16, application-level idempotency plus tests is acceptable.

### Acceptance criteria

- Paid invoice grants monthly credits.
- Duplicate invoice does not grant twice.
- Grant uses CreditLedger.
- Credit transaction reason = `subscription_monthly_grant`.
- Metadata includes Stripe invoice ID and plan key.
- Free plan invoice does not grant paid credits.
- Tests pass.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.

### Definition of Done

- Monthly credit grant implemented.
- Idempotency implemented.
- Tests pass.
- No direct balance mutation.
- CreditLedger used.
- Коммит: `CONV-256: Implement monthly subscription credit grant`

### Files likely touched

```txt
app/Billing/Webhooks/SubscriptionWebhookHandler.php
app/Credits/CreditLedger.php
app/Models/CreditTransaction.php
tests/Feature/Billing/SubscriptionMonthlyCreditGrantTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 16 Completion Criteria

Phase 16 завершена, когда:

```txt
- CONV-236–CONV-256 выполнены;
- Laravel Cashier installed;
- Cashier migrations published/applied;
- User model uses Billable;
- Stripe env placeholders exist;
- BillingPlan enum/config/repository exist;
- paid plans have Stripe price IDs;
- BillingPaymentService creates subscription checkout;
- controllers/routes do not call Cashier directly;
- /billing/checkout/{plan} route works for paid plans;
- /billing/success exists;
- /billing/cancel exists;
- Cashier webhook endpoint exists;
- billing_webhook_events table exists;
- BillingWebhookEventRecorder exists;
- SubscriptionWebhookHandler exists;
- subscription activation updates users.plan;
- subscription cancellation downgrades users.plan to free by MVP policy;
- paid invoice grants monthly credits;
- duplicate invoice does not grant credits twice;
- grants use CreditLedger;
- no credit packs were added;
- no billing page was added;
- no Spike integration was added;
- no Stripe Entitlements were added;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 16

Без отдельной задачи нельзя:

```txt
- добавлять credit packs;
- добавлять one-time checkout;
- создавать полноценную billing page;
- показывать invoice history;
- управлять payment methods;
- подключать Stripe Customer Portal UI;
- добавлять coupons/trials/tax;
- добавлять teams/business workspaces;
- добавлять usage-based Stripe metering;
- добавлять Stripe Entitlements;
- менять ConversionCostEstimator pricing rules;
- менять CreditLedger semantics;
- списывать conversion credits через Cashier;
- делать direct balance mutation;
- добавлять API billing endpoints;
- добавлять API client webhooks;
- подключать Spike;
- добавлять Paddle.
```

---

# 12. Recommended Execution Order

```txt
CONV-236 Install Laravel Cashier Stripe
CONV-237 Publish Cashier Migrations And Add Billable Trait
CONV-238 Configure Stripe Environment
CONV-239 Create BillingPlan Enum
CONV-240 Create Billing Plans Config
CONV-241 Create BillingPlan DTO
CONV-242 Create BillingPlanRepository
CONV-243 Test BillingPaymentService Creates Subscription Checkout
CONV-244 Implement BillingPaymentService Subscription Checkout
CONV-245 Add Subscription Checkout Route
CONV-246 Add Billing Success And Cancel Pages
CONV-247 Configure Cashier Webhook Route
CONV-248 Create BillingWebhookEvents Table
CONV-249 Create BillingWebhookEventRecorder
CONV-250 Create SubscriptionWebhookHandler Skeleton
CONV-251 Test Subscription Activated Updates User Plan
CONV-252 Implement Subscription Activated Handling
CONV-253 Test Subscription Cancelled Downgrades User
CONV-254 Implement Subscription Cancelled Handling
CONV-255 Test Paid Invoice Grants Monthly Credits Once
CONV-256 Implement Monthly Subscription Credit Grant
```

---

# 13. Release

После завершения Phase 16:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh --seed

git checkout -b release/v0.1.16-phase16-laravel-cashier-foundation
git push -u origin release/v0.1.16-phase16-laravel-cashier-foundation
```

После этого шага сделай MR в `main` branch и после этого остановись.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.16-phase16-laravel-cashier-foundation -m "File Converter Phase 16 Laravel Cashier Foundation"
git push origin v0.1.16-phase16-laravel-cashier-foundation
```
