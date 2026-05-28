# File Converter — Phase 18 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 18 — Billing Page**  
Диапазон задач: **CONV-274 → CONV-290**  
Основа нумерации: Phase 17 завершилась на `CONV-273`, поэтому Phase 18 начинается с `CONV-274`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 18 соответствует блоку:

```txt
Phase 18 — Billing Page
```

Правильный диапазон Phase 18:

```txt
CONV-274 — Create Billing Page Route
CONV-275 — Create Billing Page Livewire Component
CONV-276 — Add Current Plan Summary Card
CONV-277 — Add Credits Balance Card
CONV-278 — Add Plan Limits Summary Card
CONV-279 — Add Available Plans Section
CONV-280 — Highlight Current Plan
CONV-281 — Add Subscription Checkout Actions
CONV-282 — Add Checkout Result Notices
CONV-283 — Add Credit Packs Section
CONV-284 — Add Buy Credit Pack Actions
CONV-285 — Add Credit Transaction History Table
CONV-286 — Add Credit Transaction Details
CONV-287 — Add Customer Portal And Invoice Links
CONV-288 — Add Billing Empty And Error States
CONV-289 — Add Billing Responsive Layout
CONV-290 — Add Billing Page Final Smoke Tests
```

Phase 18 создаёт пользовательскую страницу `/billing`, где пользователь видит свой тариф, credits, лимиты, доступные планы, credit packs и историю credit ledger.

Billing page **не является** Stripe dashboard. Это продуктовая страница приложения, которая использует уже созданные сервисы:

```txt
FeatureAccessService
CreditLedger
ConversionCostEstimator
BillingPaymentService
CreditPackRepository
CreditPackCheckoutAction / service
```

---

# 2. Цель Phase 18

Phase 18 добавляет минимальную, но полноценную страницу управления биллингом.

После Phase 18 пользователь должен уметь:

```txt
- открыть /billing;
- увидеть текущий plan;
- увидеть credits balance;
- увидеть лимиты текущего plan;
- увидеть доступные планы Free / Pro / Max;
- начать subscription checkout для Pro/Max;
- увидеть доступные credit packs;
- начать one-time checkout для credit pack;
- увидеть credit transaction history;
- открыть Stripe Customer Portal или понятный placeholder;
- видеть понятные empty/error states.
```

Это UI-фаза поверх уже существующего billing backend.  
Она не должна придумывать новую billing-логику.

---

# 3. Scope Phase 18

## Входит

```txt
- /billing route;
- BillingPage Livewire component;
- current plan card;
- credits balance card;
- plan limits card;
- available plans section;
- subscription checkout buttons;
- credit packs section;
- credit pack checkout buttons;
- credit transaction history;
- transaction type badges;
- customer portal / invoice area;
- billing empty states;
- billing error states;
- responsive layout pass;
- feature/livewire tests for billing page.
```

## Не входит

```txt
- установка Laravel Cashier;
- Stripe webhook implementation;
- создание subscriptions backend;
- создание credit packs backend;
- создание CreditLedger;
- создание FeatureAccessService;
- изменение pricing model;
- ручное начисление credits из UI;
- ручная смена users.plan из UI;
- admin billing panel;
- refunds UI;
- team billing;
- VAT/tax settings;
- invoice PDF generation outside Cashier/Stripe;
- full Stripe Customer Portal customization;
- coupons/promo codes UI;
- usage graphs;
- payment method management form inside app.
```

Эти зоны либо уже сделаны в Phase 13–17, либо должны быть отдельными фазами.

---

# 4. Critical Decisions

## 4.1. Billing page must not mutate plan directly

Нельзя делать:

```php
$user->update(['plan' => Plan::Pro]);
```

из BillingPage.

Правильно:

```txt
BillingPage → BillingPaymentService → Cashier checkout → Stripe webhook → local plan update
```

Plan меняется только через verified payment/subscription flow.

## 4.2. Billing page must not grant credits directly

Нельзя делать:

```php
$creditLedger->grant($user, 2000, 'manual_from_billing_page');
```

из UI.

Правильно:

```txt
BillingPage → CreditPack checkout → Stripe webhook → CreditLedger grant
```

Единственное исключение — seed/dev tooling, но не пользовательская страница.

## 4.3. Use services, not raw Cashier calls in Livewire

Livewire-компонент не должен напрямую работать с Cashier:

```php
$user->newSubscription(...)->checkout(...)
```

Правильно:

```php
app(BillingPaymentService::class)->createSubscriptionCheckout($user, $plan);
```

Это сохраняет архитектуру:

```txt
UI / API / Console
        ↓
Application services
        ↓
Cashier / Stripe / CreditLedger
```

## 4.4. Billing page is not Pricing page

`/pricing` — публичная маркетинговая страница.

`/billing` — приватная страница аккаунта.

Billing page должна показывать:

```txt
- current user state;
- current credits;
- current plan;
- current limits;
- purchase/upgrade actions.
```

Pricing page может быть красивой витриной. Billing page должна быть рабочей и проверяемой.

## 4.5. Current plan CTA must be disabled

Если пользователь уже на Pro, карточка Pro не должна показывать обычную кнопку `Upgrade`.

Правильно:

```txt
Current plan
```

или:

```txt
Manage plan
```

Неправильно:

```txt
Upgrade to Pro
```

для пользователя, который уже на Pro.

## 4.6. Credit transaction history is product history

История credits должна читаться из `credit_transactions`, а не из Stripe invoices.

Stripe invoice отвечает на вопрос:

```txt
что было оплачено деньгами?
```

Credit transaction отвечает на вопрос:

```txt
что произошло с credits внутри продукта?
```

Они связаны, но это не одно и то же.

## 4.7. Customer Portal is optional in MVP

Если Stripe Customer Portal настроен, можно дать ссылку.

Если не настроен, лучше честный placeholder:

```txt
Billing portal will be available after payment provider setup.
```

Плохой вариант — сломанная кнопка.

---

# 5. Architecture Rules

## 5.1. BillingPage uses read models / services

BillingPage может читать:

```txt
Auth user
BillingPlanRepository / config
CreditPackRepository
FeatureAccessService
CreditLedger
credit_transactions query
```

BillingPage может запускать:

```txt
create subscription checkout
create credit pack checkout
```

BillingPage не может:

```txt
directly update users.plan;
directly grant credits;
directly spend/refund credits;
directly process Stripe events.
```

## 5.2. Billing actions must be idempotent downstream

Кнопки покупки могут быть нажаты повторно. UI должен блокировать double submit, но backend всё равно должен быть безопасным.

Phase 18 не реализует идемпотентность webhook — она уже должна быть в Phase 16/17. Но Phase 18 должна не ломать эту модель.

## 5.3. Billing page must be auth-only

`/billing` доступен только authenticated users.

Guest должен получить redirect to login.

## 5.4. No new billing package in Phase 18

Нельзя ставить:

```txt
Spike
Climactic/laravel-credits
Laravel Spark
новые Stripe wrappers
```

MVP billing stack уже зафиксирован:

```txt
Laravel Cashier
custom CreditLedger
custom FeatureAccessService
custom ConversionCostEstimator
```

## 5.5. Keep UI consistent with dashboard

Billing page должна использовать существующие Blade components:

```txt
<x-card>
<x-button>
<x-badge>
<x-file-icon> if needed
```

Не писать отдельный визуальный стиль.

---

# 6. GitFlow для Phase 18

## Base branch

Все задачи Phase 18 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-274-create-billing-page-route
feature/CONV-281-add-subscription-checkout-actions
feature/CONV-285-add-credit-transaction-history-table
```

## Commit format

```txt
CONV-274: Create Billing page route
CONV-281: Add subscription checkout actions
CONV-285: Add credit transaction history table
```

## Release branch

После выполнения `CONV-274`–`CONV-290`:

```txt
release/v0.1.18-phase18-billing-page
```

## Tag

После merge release branch в `main`:

```txt
v0.1.18-phase18-billing-page
```

---

# 7. TDD Rules for Phase 18

## Для route/page

Тестировать:

```txt
- guest cannot access /billing;
- authenticated user can access /billing;
- page renders current plan;
- page renders credits balance.
```

## Для plans

Тестировать:

```txt
- available plans are rendered;
- current plan is highlighted;
- current plan upgrade button is disabled/hidden;
- Pro/Max checkout actions call BillingPaymentService.
```

## Для credit packs

Тестировать:

```txt
- credit packs are rendered from CreditPackRepository;
- buy button calls credit pack checkout service/action;
- invalid pack cannot be purchased.
```

## Для transaction history

Тестировать:

```txt
- grants are visible;
- spends are visible;
- refunds are visible;
- transaction amount sign/color is correct enough;
- pagination works if implemented.
```

## Для no-direct-mutation rules

Критично проверять через код-ревью:

```txt
- BillingPage does not update users.plan directly;
- BillingPage does not grant/spend credits directly;
- BillingPage does not process Stripe webhook logic;
- BillingPage does not call raw Cashier APIs directly if BillingPaymentService exists.
```

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Billing / UI / Livewire / Tests
Type: Test / Feature / Component / Route / Table / Integration
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
- Нет direct users.plan mutation из UI
- Нет direct credit grant/spend из UI
- Checkout создаётся через application service
- Коммит содержит ID задачи

Files likely touched:
- path/to/file
```

---

# 9. Phase 18 Atomic Tasks

---

## CONV-274 — Create Billing Page Route

**Area:** Billing / Routes  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-274-create-billing-page-route`  
**Base branch:** `develop`  
**Depends on:** CONV-273

### Goal

Создать protected route `/billing` для страницы биллинга.

### TDD step

Feature tests:

```php
it('redirects guest from billing page to login', function () {
    $this->get('/billing')
        ->assertRedirect('/login');
});
```

```php
it('allows authenticated user to access billing page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/billing')
        ->assertOk()
        ->assertSee('Billing');
});
```

Тест должен упасть до добавления route/page.

### Implementation

Добавить route:

```php
Route::middleware(['auth'])->group(function () {
    Route::get('/billing', BillingPage::class)->name('billing');
});
```

Если Livewire component ещё не создан, можно временно использовать placeholder view, но лучше сразу создать skeleton component в CONV-275.  
В этой задаче допустим минимальный placeholder, если компонент создаётся следующей задачей.

### Acceptance criteria

- `/billing` route exists.
- Guest redirected to login.
- Auth user can access page.
- Page contains `Billing` heading or placeholder.
- No billing business logic yet.
- Tests pass.

### Definition of Done

- Тесты написаны.
- Route добавлен.
- Guest/auth behavior работает.
- Коммит: `CONV-274: Create Billing page route`

### Files likely touched

```txt
routes/web.php
resources/views/billing.blade.php
app/Livewire/Billing/BillingPage.php
tests/Feature/Billing/BillingPageRouteTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-275 — Create Billing Page Livewire Component

**Area:** Billing / Livewire  
**Type:** Component  
**Priority:** P0  
**Branch:** `feature/CONV-275-create-billing-page-livewire-component`  
**Base branch:** `develop`  
**Depends on:** CONV-274

### Goal

Создать `BillingPage` Livewire component как основу страницы `/billing`.

### TDD step

Livewire test:

```php
use Livewire\Livewire;

it('renders billing page livewire component', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(\App\Livewire\Billing\BillingPage::class)
        ->assertSee('Billing');
});
```

Тест должен упасть до создания компонента.

### Implementation

Создать компонент:

```bash
php artisan make:livewire Billing/BillingPage
```

Минимальный render:

```blade
<div>
    <h1>Billing</h1>
</div>
```

Подключить компонент к route, если в CONV-274 был placeholder.

### Acceptance criteria

- `BillingPage` component exists.
- Component renders `Billing` heading.
- `/billing` uses Livewire component.
- No plan/credits UI yet.
- Livewire test passes.

### Definition of Done

- Тест написан первым.
- Component создан.
- Route использует component.
- Tests pass.
- Коммит: `CONV-275: Create Billing page Livewire component`

### Files likely touched

```txt
app/Livewire/Billing/BillingPage.php
resources/views/livewire/billing/billing-page.blade.php
routes/web.php
tests/Feature/Livewire/Billing/BillingPageTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-276 — Add Current Plan Summary Card

**Area:** Billing / UI  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-276-add-current-plan-summary-card`  
**Base branch:** `develop`  
**Depends on:** CONV-275

### Goal

Показать текущий план пользователя на billing page.

### TDD step

Livewire test:

```php
it('shows current user plan on billing page', function () {
    $user = User::factory()->create([
        'plan' => Plan::Pro,
    ]);

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Current plan')
        ->assertSee('Pro');
});
```

Adapt enum/class names to actual implementation.

### Implementation

В `BillingPage` получить current user:

```php
public function getUserProperty(): User
{
    return auth()->user();
}
```

В Blade добавить card:

```txt
Current plan
Pro / Free / Max
```

Использовать `<x-card>` и `<x-badge>`, если они уже существуют.

Не добавлять checkout logic в этой задаче.

### Acceptance criteria

- Current plan card visible.
- Shows actual user plan.
- Uses plan label, not raw enum value if labels exist.
- Free user shows Free.
- Pro user shows Pro.
- Test passes.

### Definition of Done

- Тест написан.
- Current plan card добавлена.
- No direct plan mutation.
- Test passes.
- Коммит: `CONV-276: Add current plan summary card`

### Files likely touched

```txt
app/Livewire/Billing/BillingPage.php
resources/views/livewire/billing/billing-page.blade.php
tests/Feature/Livewire/Billing/BillingPageTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-277 — Add Credits Balance Card

**Area:** Billing / Credits / UI  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-277-add-credits-balance-card`  
**Base branch:** `develop`  
**Depends on:** CONV-276

### Goal

Показать текущий credits balance пользователя через `CreditLedger`.

### TDD step

Livewire test:

```php
it('shows current credits balance on billing page', function () {
    $user = User::factory()->create();

    app(CreditLedger::class)->grant(
        user: $user,
        amount: 500,
        reason: 'test_grant',
    );

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Credits balance')
        ->assertSee('500');
});
```

### Implementation

В `BillingPage` внедрить `CreditLedger` через container/service call.

Вариант:

```php
public function getCreditsBalanceProperty(): int
{
    return app(CreditLedger::class)->balance(auth()->user());
}
```

В Blade добавить card:

```txt
Credits balance
500 credits
```

Не читать напрямую `credit_accounts.balance`, если уже есть `CreditLedger` interface.

### Acceptance criteria

- Credits balance visible.
- Value comes from CreditLedger.
- Zero balance handled.
- No direct DB balance query in Blade.
- Test passes.

### Definition of Done

- Тест написан.
- Credits balance card добавлена.
- CreditLedger используется.
- Test passes.
- Коммит: `CONV-277: Add credits balance card`

### Files likely touched

```txt
app/Livewire/Billing/BillingPage.php
resources/views/livewire/billing/billing-page.blade.php
tests/Feature/Livewire/Billing/BillingPageTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-278 — Add Plan Limits Summary Card

**Area:** Billing / Feature Access / UI  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-278-add-plan-limits-summary-card`  
**Base branch:** `develop`  
**Depends on:** CONV-277

### Goal

Показать лимиты текущего плана: max file size, storage, retention, API access, batch conversion.

### TDD step

Livewire test:

```php
it('shows current plan limits on billing page', function () {
    $user = User::factory()->create([
        'plan' => Plan::Free,
    ]);

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Plan limits')
        ->assertSee('Max file size')
        ->assertSee('API access');
});
```

Если конкретные значения уже зафиксированы:

```php
->assertSee('25 MB')
->assertSee('Disabled')
```

### Implementation

Использовать `FeatureAccessService`:

```php
$featureAccess->limit($user, 'max_file_size_mb');
$featureAccess->limit($user, 'storage_mb');
$featureAccess->limit($user, 'retention_days');
$featureAccess->allows($user, 'api_access');
$featureAccess->allows($user, 'batch_conversion');
```

В UI показать компактный блок:

```txt
Max file size: 25 MB
Storage: 250 MB
Retention: 1 day
API access: Disabled
Batch conversion: Disabled
```

### Acceptance criteria

- Plan limits card visible.
- Values come from FeatureAccessService.
- Boolean features render as Enabled/Disabled.
- No duplicated hardcoded limits in Blade.
- Test passes.

### Definition of Done

- Тест написан.
- Plan limits card добавлена.
- FeatureAccessService используется.
- Test passes.
- Коммит: `CONV-278: Add plan limits summary card`

### Files likely touched

```txt
app/Livewire/Billing/BillingPage.php
resources/views/livewire/billing/billing-page.blade.php
tests/Feature/Livewire/Billing/BillingPageTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-279 — Add Available Plans Section

**Area:** Billing / Plans / UI  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-279-add-available-plans-section`  
**Base branch:** `develop`  
**Depends on:** CONV-278

### Goal

Показать доступные планы Free / Pro / Max на billing page.

### TDD step

Livewire test:

```php
it('shows available billing plans', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Free')
        ->assertSee('Pro')
        ->assertSee('Max');
});
```

### Implementation

Использовать existing BillingPlan config/repository из Phase 16.

Если repository уже есть:

```php
public function getPlansProperty(): array
{
    return app(BillingPlanRepository::class)->all();
}
```

Если repository нет, использовать `config('billing.plans')`, но не дублировать данные в Blade.

Показать cards:

```txt
Free
Pro
Max
```

Каждая card должна иметь:

```txt
name
price
monthly credits
key limits
CTA placeholder/button
```

### Acceptance criteria

- Available plans section visible.
- Free/Pro/Max rendered from config/repository.
- Monthly credits visible.
- Main limits visible.
- No hardcoded duplicate plan data in Blade.
- Test passes.

### Definition of Done

- Тест написан.
- Plans section добавлена.
- Plans come from config/repository.
- Test passes.
- Коммит: `CONV-279: Add available plans section`

### Files likely touched

```txt
app/Livewire/Billing/BillingPage.php
resources/views/livewire/billing/billing-page.blade.php
tests/Feature/Livewire/Billing/BillingPageTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-280 — Highlight Current Plan

**Area:** Billing / Plans / UI  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-280-highlight-current-plan`  
**Base branch:** `develop`  
**Depends on:** CONV-279

### Goal

Выделить текущий план и не показывать пользователю бессмысленную кнопку upgrade на уже активный план.

### TDD step

Livewire test:

```php
it('highlights the current plan', function () {
    $user = User::factory()->create([
        'plan' => Plan::Pro,
    ]);

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Current plan')
        ->assertSee('Pro');
});
```

Дополнительно, если текст кнопки проверяем:

```php
->assertDontSee('Upgrade to Pro')
```

### Implementation

В plan card:

```blade
@if ($plan->key === $currentPlan)
    <x-badge>Current plan</x-badge>
    <x-button disabled>Current plan</x-button>
@else
    <x-button wire:click="startSubscriptionCheckout('{{ $plan->key }}')">
        Upgrade to {{ $plan->label }}
    </x-button>
@endif
```

Для Free можно показывать `Current plan`, если user на Free.

### Acceptance criteria

- Current plan visually highlighted.
- Current plan CTA disabled or replaced with `Current plan`.
- Other paid plans show upgrade/change CTA.
- Test passes.

### Definition of Done

- Тест написан.
- Current plan highlight добавлен.
- No misleading CTA.
- Test passes.
- Коммит: `CONV-280: Highlight current plan`

### Files likely touched

```txt
resources/views/livewire/billing/billing-page.blade.php
app/Livewire/Billing/BillingPage.php
tests/Feature/Livewire/Billing/BillingPageTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-281 — Add Subscription Checkout Actions

**Area:** Billing / Cashier / Livewire  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-281-add-subscription-checkout-actions`  
**Base branch:** `develop`  
**Depends on:** CONV-280

### Goal

Добавить action для запуска subscription checkout для paid plans через `BillingPaymentService`.

### TDD step

Livewire test with mock:

```php
it('starts subscription checkout for paid plan', function () {
    $user = User::factory()->create([
        'plan' => Plan::Free,
    ]);

    $service = Mockery::mock(BillingPaymentService::class);
    $service->shouldReceive('createSubscriptionCheckout')
        ->once()
        ->withArgs(fn ($actualUser, $plan) =>
            $actualUser->is($user) && $plan === Plan::Pro
        )
        ->andReturn('https://checkout.stripe.test/session');

    app()->instance(BillingPaymentService::class, $service);

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->call('startSubscriptionCheckout', 'pro')
        ->assertRedirect('https://checkout.stripe.test/session');
});
```

Adapt enum/string according to implementation.

### Implementation

В `BillingPage`:

```php
public function startSubscriptionCheckout(string $plan): RedirectResponse
{
    $targetPlan = Plan::from($plan);

    if ($targetPlan === Plan::Free) {
        throw ValidationException::withMessages([
            'plan' => 'Free plan does not require checkout.',
        ]);
    }

    if (auth()->user()->plan === $targetPlan) {
        throw ValidationException::withMessages([
            'plan' => 'You are already on this plan.',
        ]);
    }

    $url = app(BillingPaymentService::class)
        ->createSubscriptionCheckout(auth()->user(), $targetPlan);

    return redirect()->away($url);
}
```

Не вызывать Cashier напрямую в component.

### Acceptance criteria

- Paid plan button triggers checkout.
- Free plan checkout rejected.
- Current plan checkout rejected.
- Uses BillingPaymentService.
- Redirects to checkout URL.
- Test passes.

### Definition of Done

- Тест написан.
- Checkout action добавлен.
- No direct Cashier calls in component.
- Test passes.
- Коммит: `CONV-281: Add subscription checkout actions`

### Files likely touched

```txt
app/Livewire/Billing/BillingPage.php
resources/views/livewire/billing/billing-page.blade.php
tests/Feature/Livewire/Billing/BillingPageCheckoutTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-282 — Add Checkout Result Notices

**Area:** Billing / UX  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-282-add-checkout-result-notices`  
**Base branch:** `develop`  
**Depends on:** CONV-281

### Goal

Показать понятные сообщения после возврата из Stripe checkout: success/cancel.

### TDD step

Feature tests:

```php
it('shows billing success notice', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/billing?checkout=success')
        ->assertOk()
        ->assertSee('Payment received');
});
```

```php
it('shows billing cancelled notice', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/billing?checkout=cancelled')
        ->assertOk()
        ->assertSee('Checkout was cancelled');
});
```

### Implementation

В `BillingPage` прочитать query parameter:

```php
request()->query('checkout')
```

Разрешённые значения:

```txt
success
cancelled
```

UI:

```txt
Payment received. Your plan or credits will update after payment confirmation.
Checkout was cancelled. No changes were made.
```

Не обещать мгновенное начисление, если webhook асинхронный.

### Acceptance criteria

- Success notice visible for `?checkout=success`.
- Cancel notice visible for `?checkout=cancelled`.
- Unknown value ignored.
- Message does not falsely claim plan/credits already updated before webhook.
- Tests pass.

### Definition of Done

- Тесты написаны.
- Notices добавлены.
- Copy честный.
- Tests pass.
- Коммит: `CONV-282: Add checkout result notices`

### Files likely touched

```txt
app/Livewire/Billing/BillingPage.php
resources/views/livewire/billing/billing-page.blade.php
tests/Feature/Billing/BillingCheckoutNoticeTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-283 — Add Credit Packs Section

**Area:** Billing / Credits / UI  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-283-add-credit-packs-section`  
**Base branch:** `develop`  
**Depends on:** CONV-282

### Goal

Показать доступные credit packs на billing page.

### TDD step

Livewire test:

```php
it('shows available credit packs on billing page', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Buy credits')
        ->assertSee('500 credits')
        ->assertSee('2000 credits');
});
```

Adapt amounts to actual `CreditPack` config.

### Implementation

Использовать `CreditPackRepository` из Phase 17:

```php
public function getCreditPacksProperty(): array
{
    return app(CreditPackRepository::class)->all();
}
```

UI section:

```txt
Buy credits
500 credits
2000 credits
10000 credits
```

Показать:

```txt
credits amount
price label
short description
Buy button
```

### Acceptance criteria

- Credit packs section visible.
- Packs come from CreditPackRepository/config.
- Amount and price label visible.
- Buy button visible for each pack.
- Test passes.

### Definition of Done

- Тест написан.
- Credit packs section добавлена.
- No hardcoded duplicate pack list in Blade.
- Test passes.
- Коммит: `CONV-283: Add credit packs section`

### Files likely touched

```txt
app/Livewire/Billing/BillingPage.php
resources/views/livewire/billing/billing-page.blade.php
tests/Feature/Livewire/Billing/BillingPageCreditPacksTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-284 — Add Buy Credit Pack Actions

**Area:** Billing / Credit Packs / Livewire  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-284-add-buy-credit-pack-actions`  
**Base branch:** `develop`  
**Depends on:** CONV-283

### Goal

Добавить action покупки credit pack через существующий checkout service/action.

### TDD step

Livewire test with mock:

```php
it('starts credit pack checkout', function () {
    $user = User::factory()->create();

    $service = Mockery::mock(CreditPackCheckoutService::class);
    $service->shouldReceive('createCheckout')
        ->once()
        ->withArgs(fn ($actualUser, $packKey) =>
            $actualUser->is($user) && $packKey === 'credits_500'
        )
        ->andReturn('https://checkout.stripe.test/credit-pack');

    app()->instance(CreditPackCheckoutService::class, $service);

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->call('buyCreditPack', 'credits_500')
        ->assertRedirect('https://checkout.stripe.test/credit-pack');
});
```

Adapt service name to Phase 17 implementation.

### Implementation

В `BillingPage`:

```php
public function buyCreditPack(string $packKey): RedirectResponse
{
    $url = app(CreditPackCheckoutService::class)
        ->createCheckout(auth()->user(), $packKey);

    return redirect()->away($url);
}
```

Если Phase 17 использует action name instead:

```php
CreateCreditPackCheckoutAction
```

использовать его.

Invalid pack должен обрабатываться service/action и возвращаться как readable validation/domain error.

### Acceptance criteria

- Buy button calls checkout action/service.
- Redirects to checkout URL.
- Invalid pack rejected.
- No direct Cashier calls in component.
- No credits granted from UI.
- Test passes.

### Definition of Done

- Тест написан.
- Buy action добавлен.
- Uses credit pack checkout service/action.
- Test passes.
- Коммит: `CONV-284: Add buy credit pack actions`

### Files likely touched

```txt
app/Livewire/Billing/BillingPage.php
resources/views/livewire/billing/billing-page.blade.php
tests/Feature/Livewire/Billing/BillingPageCreditPacksTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-285 — Add Credit Transaction History Table

**Area:** Billing / Credits / Table  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-285-add-credit-transaction-history-table`  
**Base branch:** `develop`  
**Depends on:** CONV-284

### Goal

Добавить таблицу истории credit transactions на billing page.

### TDD step

Livewire test:

```php
it('shows credit transaction history', function () {
    $user = User::factory()->create();

    app(CreditLedger::class)->grant($user, 50, 'registration_grant');
    app(CreditLedger::class)->spend($user, 2, 'conversion_completed');

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Credit history')
        ->assertSee('registration_grant')
        ->assertSee('conversion_completed')
        ->assertSee('+50')
        ->assertSee('-2');
});
```

### Implementation

В `BillingPage` загрузить последние transactions текущего пользователя:

```php
CreditTransaction::query()
    ->where('user_id', auth()->id())
    ->latest()
    ->paginate(10)
```

Если есть relationship:

```php
$user->creditTransactions()->latest()->paginate(10)
```

UI columns:

```txt
Date
Type
Amount
Reason
Balance after
```

### Acceptance criteria

- Credit history section visible.
- Shows only current user transactions.
- Shows positive and negative amounts.
- Shows reason.
- Shows balance_after.
- Test passes.

### Definition of Done

- Тест написан.
- Credit transaction table добавлена.
- Owner scoping enforced.
- Test passes.
- Коммит: `CONV-285: Add credit transaction history table`

### Files likely touched

```txt
app/Livewire/Billing/BillingPage.php
resources/views/livewire/billing/billing-page.blade.php
tests/Feature/Livewire/Billing/BillingPageCreditHistoryTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-286 — Add Credit Transaction Details

**Area:** Billing / Credits / UI  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-286-add-credit-transaction-details`  
**Base branch:** `develop`  
**Depends on:** CONV-285

### Goal

Улучшить таблицу credit history: добавить badges, readable labels и metadata/details для важных transactions.

### TDD step

Livewire test:

```php
it('renders readable credit transaction labels', function () {
    $user = User::factory()->create();

    app(CreditLedger::class)->grant($user, 500, 'credit_pack_purchase', [
        'pack_key' => 'credits_500',
    ]);

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Credit pack purchase')
        ->assertSee('+500');
});
```

### Implementation

Добавить mapping для reasons/types:

```txt
registration_grant → Registration grant
subscription_monthly_grant → Monthly subscription credits
credit_pack_purchase → Credit pack purchase
conversion_completed → Conversion completed
conversion_refund → Conversion refund
```

Amount format:

```txt
+500
-2
```

Badge colors:

```txt
grant/purchase/refund → success
spend/expiration → warning/danger/neutral
```

Если metadata содержит `conversion_job_id`, можно показать короткую ссылку на history/conversion later. В MVP можно просто показать metadata summary.

### Acceptance criteria

- Transaction types have readable labels.
- Amount signs are clear.
- Badges/colors distinguish grant/spend/refund.
- Metadata is displayed safely and minimally.
- Raw JSON is not dumped into UI.
- Test passes.

### Definition of Done

- Тест написан.
- Labels/badges добавлены.
- UI не показывает raw JSON.
- Test passes.
- Коммит: `CONV-286: Add credit transaction details`

### Files likely touched

```txt
app/Livewire/Billing/BillingPage.php
resources/views/livewire/billing/billing-page.blade.php
app/View/Components/* optional
tests/Feature/Livewire/Billing/BillingPageCreditHistoryTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-287 — Add Customer Portal And Invoice Links

**Area:** Billing / Cashier / UI  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-287-add-customer-portal-and-invoice-links`  
**Base branch:** `develop`  
**Depends on:** CONV-286

### Goal

Добавить блок для управления оплатой: customer portal / invoices / payment method. Если портал не настроен, показать честный placeholder.

### TDD step

Feature/Livewire test:

```php
it('shows billing management section', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Manage billing')
        ->assertSee('Invoices');
});
```

Если `BillingPaymentService` умеет создавать portal URL:

```php
it('starts customer portal when available', function () {
    // mock BillingPaymentService::createCustomerPortalUrl(...)
});
```

### Implementation

UI section:

```txt
Manage billing
Payment method, invoices, and subscription details are handled securely by Stripe.
[Open billing portal]
```

В `BillingPage` action:

```php
public function openCustomerPortal(): RedirectResponse
{
    $url = app(BillingPaymentService::class)
        ->createCustomerPortalUrl(auth()->user());

    return redirect()->away($url);
}
```

Если service пока не поддерживает portal:

```txt
Show disabled button / Coming soon message.
```

Не строить собственную payment method form.

### Acceptance criteria

- Manage billing section visible.
- Invoices/payment method copy visible.
- If portal service exists, button redirects through service.
- If portal not configured, no broken button.
- No raw Cashier portal logic in Blade.
- Test passes.

### Definition of Done

- Тест написан.
- Manage billing section добавлена.
- Portal behavior safe.
- Test passes.
- Коммит: `CONV-287: Add customer portal and invoice links`

### Files likely touched

```txt
app/Livewire/Billing/BillingPage.php
resources/views/livewire/billing/billing-page.blade.php
app/Services/Billing/BillingPaymentService.php optional
tests/Feature/Livewire/Billing/BillingPageTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-288 — Add Billing Empty And Error States

**Area:** Billing / UX / Errors  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-288-add-billing-empty-and-error-states`  
**Base branch:** `develop`  
**Depends on:** CONV-287

### Goal

Добавить понятные empty/error states для billing page.

### TDD step

Livewire tests:

```php
it('shows empty state when user has no credit transactions', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('No credit transactions yet');
});
```

Checkout error test:

```php
it('shows validation error for invalid subscription checkout plan', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->call('startSubscriptionCheckout', 'invalid-plan')
        ->assertHasErrors();
});
```

### Implementation

Empty states:

```txt
No credit transactions yet
Your credit grants and conversion charges will appear here.
```

Error states:

```txt
Invalid plan selected.
Unable to start checkout. Try again later.
This credit pack is no longer available.
```

Catch domain exceptions in Livewire actions and map to validation errors or toast events.

Do not swallow errors silently.

### Acceptance criteria

- Empty credit history state exists.
- Invalid plan/pack errors are visible.
- Checkout failure shows readable message.
- No stack trace in UI.
- Tests pass.

### Definition of Done

- Тесты написаны.
- Empty/error states добавлены.
- Errors readable.
- Tests pass.
- Коммит: `CONV-288: Add billing empty and error states`

### Files likely touched

```txt
app/Livewire/Billing/BillingPage.php
resources/views/livewire/billing/billing-page.blade.php
tests/Feature/Livewire/Billing/BillingPageErrorStateTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-289 — Add Billing Responsive Layout

**Area:** Billing / Frontend  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-289-add-billing-responsive-layout`  
**Base branch:** `develop`  
**Depends on:** CONV-288

### Goal

Привести billing page к аккуратному responsive layout в стиле dashboard.

### TDD step

No direct backend test — visual/responsive layout task.

Но после изменений должны проходить:

```bash
composer test
composer lint
npm run build
```

### Implementation

Layout direction:

```txt
Top summary row:
- Current plan
- Credits balance
- Plan limits

Middle:
- Available plans
- Credit packs

Bottom:
- Credit history
- Manage billing
```

Responsive behavior:

```txt
desktop: grid cards
laptop: 2 columns where sensible
tablet/mobile: single column
```

Use existing components and tokens.

No new design system in this task.

### Acceptance criteria

- Billing page usable on desktop.
- Billing page does not break on tablet width.
- Mobile falls back to single column.
- Tables do not overflow badly; if needed, horizontal scroll wrapper.
- `npm run build` passes.

### Definition of Done

- Layout cleaned.
- Responsive classes added.
- No duplicated styles outside component conventions.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.
- Коммит: `CONV-289: Add billing responsive layout`

### Files likely touched

```txt
resources/views/livewire/billing/billing-page.blade.php
resources/css/app.css optional
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-290 — Add Billing Page Final Smoke Tests

**Area:** Billing / Tests / QA  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-290-add-billing-page-final-smoke-tests`  
**Base branch:** `develop`  
**Depends on:** CONV-289

### Goal

Добавить финальные smoke tests для Phase 18 и проверить, что billing page собрана как законченная MVP-страница.

### TDD step

Feature/Livewire test:

```php
it('renders complete billing page for authenticated user', function () {
    $user = User::factory()->create([
        'plan' => Plan::Free,
    ]);

    app(CreditLedger::class)->grant($user, 50, 'registration_grant');

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertSee('Billing')
        ->assertSee('Current plan')
        ->assertSee('Credits balance')
        ->assertSee('Plan limits')
        ->assertSee('Available plans')
        ->assertSee('Buy credits')
        ->assertSee('Credit history')
        ->assertSee('Manage billing');
});
```

Security smoke:

```php
it('does not show another users credit transactions', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    app(CreditLedger::class)->grant($other, 999, 'other_user_grant');

    Livewire::actingAs($user)
        ->test(BillingPage::class)
        ->assertDontSee('other_user_grant')
        ->assertDontSee('999');
});
```

### Implementation

Добавить недостающие финальные тесты.

Запустить полный набор:

```bash
composer test
composer lint
npm run build
```

Исправить только проблемы Phase 18.  
Не начинать новые фичи.

### Acceptance criteria

- Complete billing page smoke test exists.
- Guest protection covered.
- User data scoping covered.
- Checkout action tests pass.
- Credit packs render test passes.
- Credit history tests pass.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.

### Definition of Done

- Final smoke tests added.
- No out-of-scope features added.
- Full quality gate passes.
- Коммит: `CONV-290: Add billing page final smoke tests`

### Files likely touched

```txt
tests/Feature/Livewire/Billing/BillingPageTest.php
tests/Feature/Livewire/Billing/BillingPageCreditHistoryTest.php
tests/Feature/Livewire/Billing/BillingPageCheckoutTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 18 Completion Criteria

Phase 18 завершена, когда:

```txt
- CONV-274–CONV-290 выполнены;
- /billing route exists;
- guest cannot access /billing;
- authenticated user can access /billing;
- BillingPage Livewire component exists;
- current plan card visible;
- credits balance card visible;
- plan limits card visible;
- available plans section visible;
- current plan highlighted;
- subscription checkout actions use BillingPaymentService;
- checkout result notices exist;
- credit packs section visible;
- buy credit pack actions use existing checkout service/action;
- credit transaction history visible;
- transaction labels/badges are readable;
- customer portal/invoice section exists;
- empty/error states exist;
- responsive layout pass done;
- billing page does not mutate users.plan directly;
- billing page does not grant/spend credits directly;
- billing page does not process Stripe webhooks;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 18

Без отдельной задачи нельзя:

```txt
- менять pricing model;
- менять Plan enum semantics;
- менять FeatureAccessService rules без отдельной задачи;
- менять ConversionCostEstimator;
- добавлять Stripe webhook logic;
- начислять credits напрямую из BillingPage;
- списывать credits из BillingPage;
- напрямую менять users.plan из BillingPage;
- ставить Spike;
- ставить сторонний credits package;
- строить admin billing panel;
- строить refunds UI;
- строить coupons UI;
- строить team billing;
- строить payment method form внутри приложения;
- строить usage graphs;
- строить public pricing redesign;
- добавлять API changes;
- добавлять Vue/React/Inertia.
```

---

# 12. Recommended Execution Order

```txt
CONV-274 Create Billing Page Route
CONV-275 Create Billing Page Livewire Component
CONV-276 Add Current Plan Summary Card
CONV-277 Add Credits Balance Card
CONV-278 Add Plan Limits Summary Card
CONV-279 Add Available Plans Section
CONV-280 Highlight Current Plan
CONV-281 Add Subscription Checkout Actions
CONV-282 Add Checkout Result Notices
CONV-283 Add Credit Packs Section
CONV-284 Add Buy Credit Pack Actions
CONV-285 Add Credit Transaction History Table
CONV-286 Add Credit Transaction Details
CONV-287 Add Customer Portal And Invoice Links
CONV-288 Add Billing Empty And Error States
CONV-289 Add Billing Responsive Layout
CONV-290 Add Billing Page Final Smoke Tests
```

---

# 13. Release

После завершения Phase 18:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh --seed

git checkout -b release/v0.1.18-phase18-billing-page
git push -u origin release/v0.1.18-phase18-billing-page
```

После этого шага сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.18-phase18-billing-page -m "File Converter Phase 18 billing page"
git push origin v0.1.18-phase18-billing-page
```
