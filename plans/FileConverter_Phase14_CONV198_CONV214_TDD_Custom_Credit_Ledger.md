# File Converter — Phase 14 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 14 — Custom Credit Ledger**  
Диапазон задач: **CONV-198 → CONV-214**  
Основа нумерации: Phase 13 завершилась на `CONV-197`, поэтому Phase 14 начинается с `CONV-198`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 14 соответствует блоку:

```txt
Phase 14 — Custom Credit Ledger
```

Правильный диапазон Phase 14:

```txt
CONV-198 — Create CreditAccount Model And Migration
CONV-199 — Create CreditTransaction Model And Migration
CONV-200 — Add CreditTransactionType Enum
CONV-201 — Create CreditLedger Contract
CONV-202 — Create DatabaseCreditLedger Skeleton
CONV-203 — Test Credit Account Auto Creation
CONV-204 — Implement Credit Account Auto Creation
CONV-205 — Test Grant Credits
CONV-206 — Implement Grant Credits
CONV-207 — Test Spend Credits
CONV-208 — Implement Spend Credits
CONV-209 — Test Insufficient Credits Are Blocked
CONV-210 — Make Credit Spending Transactional
CONV-211 — Test Refund Credits
CONV-212 — Implement Refund Credits
CONV-213 — Grant Registration Starter Credits
CONV-214 — Show Credit Balance In User Dropdown
```

Phase 14 добавляет локальный кредитный ledger для продукта.

Это **не Cashier-фаза** и **не Stripe-фаза**. Здесь нет checkout, subscriptions, invoices, credit packs и payment webhooks.

---

# 2. Цель Phase 14

Phase 14 должна создать внутреннюю систему кредитов, которая работает независимо от платежной системы.

После Phase 14 приложение должно уметь:

```txt
- хранить credit account пользователя;
- хранить immutable transaction history;
- показывать текущий credit balance;
- начислять credits;
- списывать credits;
- блокировать списание при недостаточном балансе;
- возвращать credits через refund;
- создавать credit account для нового пользователя;
- начислять стартовые credits при регистрации;
- показывать balance в user dropdown.
```

Главная идея: платежи могут появиться позже через Laravel Cashier, но **операционный баланс credits должен быть локальным и быстрым**.

---

# 3. Scope Phase 14

## Входит

```txt
- credit_accounts table;
- credit_transactions table;
- CreditAccount model;
- CreditTransaction model;
- CreditTransactionType enum;
- CreditLedger contract/interface;
- DatabaseCreditLedger implementation;
- account auto-creation for new users;
- grant credits;
- spend credits;
- insufficient balance guard;
- transactional spend with row lock;
- refund credits;
- registration starter credits;
- balance display in user dropdown;
- unit/feature tests for ledger behavior.
```

## Не входит

```txt
- Laravel Cashier;
- Stripe;
- subscriptions;
- credit packs;
- checkout pages;
- billing page;
- invoices;
- payment methods;
- monthly subscription grants;
- conversion cost estimator;
- conversion_credit_charges table;
- reserve/capture credit workflow;
- API billing;
- admin credit adjustments UI.
```

Phase 15 делает `ConversionCostEstimator`.  
Phase 16 делает Laravel Cashier foundation.  
Phase 17 делает credit packs.

---

# 4. Critical Decisions

## 4.1. Ledger is the source of truth

Нельзя хранить только одно поле `users.credits`.

Плохо:

```txt
users.credits = 123
```

Правильно:

```txt
credit_accounts.balance = current balance
credit_transactions = immutable history of all changes
```

`credit_accounts.balance` — это быстрый read model.  
`credit_transactions` — это история и audit trail.

## 4.2. Balance must never go negative

Любая попытка списать больше, чем доступно, должна падать доменной ошибкой.

Нельзя:

```txt
balance = -5
```

Правильно:

```txt
throw InsufficientCreditsException
```

## 4.3. Spend must be transactional

Credits будут списываться при конвертациях. Там возможны параллельные jobs/API requests.

Поэтому списание должно быть:

```txt
- inside DB transaction;
- with row lock on credit_accounts;
- impossible to overspend concurrently.
```

Если сделать простую проверку:

```php
if ($balance >= $amount) spend();
```

без lock, два параллельных запроса могут оба пройти проверку и загнать баланс в минус.

## 4.4. Credit transactions are append-only

Нельзя редактировать старые credit transactions после создания.

Если нужно исправить баланс, создаётся новая transaction:

```txt
adjustment
refund
expiration
```

Не делать:

```php
$transaction->update(['amount' => ...])
```

## 4.5. Phase 14 uses one universal credit type

В MVP не нужно делать разные balances:

```txt
conversion_credits
ai_credits
api_credits
```

Это усложнит pricing и UX.

Phase 14 фиксирует один универсальный баланс:

```txt
credits
```

Дорогие операции позже будут стоить больше credits.

## 4.6. Starter credits come from PlanFeatures config

Phase 13 уже ввела `monthly_credits` в plan limits.  
В Phase 14 стартовое начисление free credits должно брать значение из plan config, а не hardcode по всему коду.

Допустимо временно:

```php
FeatureAccessService::limit($user, 'monthly_credits')
```

## 4.7. Cashier must not leak into ledger

В Phase 14 нельзя добавлять:

```txt
Billable
Stripe customer id
subscription status
checkout session
```

Ledger должен работать без Stripe.

---

# 5. Architecture Rules

## 5.1. All credit operations go through CreditLedger

Нельзя в коде напрямую делать:

```php
$user->creditAccount->decrement('balance', $amount);
CreditTransaction::create([...]);
```

Правильно:

```php
app(CreditLedger::class)->spend($user, $amount, $reason, $meta);
```

## 5.2. Use contract, not concrete class

Сервисы будущих фаз должны зависеть от:

```php
CreditLedger
```

а не от:

```php
DatabaseCreditLedger
```

Это позволит позже заменить реализацию или обернуть сторонний пакет без переписывания домена.

## 5.3. Domain exceptions, not generic exceptions

Нельзя бросать:

```php
throw new \Exception('Not enough credits');
```

Нужно создать явные исключения:

```txt
InsufficientCreditsException
InvalidCreditAmountException
CreditAccountNotFoundException если понадобится
```

## 5.4. Metadata is required for auditability

Каждая transaction должна поддерживать `metadata_json`.

Примеры:

```json
{
  "source": "registration",
  "plan": "free"
}
```

Позже:

```json
{
  "conversion_job_id": 123,
  "converter_key": "png_to_pdf"
}
```

## 5.5. No billing UI except balance display

Phase 14 может показать balance в user dropdown.  
Нельзя создавать полноценную billing page в этой фазе.

---

# 6. GitFlow для Phase 14

## Base branch

Все задачи Phase 14 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-198-create-credit-account-model-and-migration
feature/CONV-206-implement-grant-credits
feature/CONV-210-make-credit-spending-transactional
```

## Commit format

```txt
CONV-198: Create credit account model and migration
CONV-206: Implement grant credits
CONV-210: Make credit spending transactional
```

## Release branch

После выполнения `CONV-198`–`CONV-214`:

```txt
release/v0.1.14-phase14-custom-credit-ledger
```

## Tag

После merge release branch в `main`:

```txt
v0.1.14-phase14-custom-credit-ledger
```

---

# 7. TDD Rules for Phase 14

## Для моделей и migrations

Тестировать:

```txt
- credit account belongs to user;
- credit transaction belongs to user;
- balance is integer;
- metadata is cast to array;
- transaction type is cast to enum.
```

## Для CreditLedger

Каждое поведение test-first:

```txt
- balance returns current account balance;
- grant increases balance;
- grant creates transaction;
- spend decreases balance;
- spend creates transaction;
- insufficient balance throws exception;
- failed spend does not create transaction;
- refund increases balance;
- refund creates transaction;
- balance_after is correct.
```

## Для concurrency

Минимум test на transactional behavior:

```txt
- spend uses database transaction;
- account row is locked during spend;
- balance cannot become negative.
```

Полноценный parallel test можно отложить, если test environment не позволяет корректно проверить concurrency, но код должен использовать `lockForUpdate()`.

## Для registration grant

Тестировать:

```txt
- new user gets credit account;
- new user gets starter credits;
- transaction reason is registration_grant;
- starter grant is not duplicated.
```

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Billing / Credits / Ledger / Tests
Type: Test / Feature / Model / Migration / Service
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
- Нет Cashier/Stripe вне scope задачи
- Нет прямых изменений balance вне CreditLedger
- Коммит содержит ID задачи

Files likely touched:
- path/to/file
```

---

# 9. Phase 14 Atomic Tasks

---

## CONV-198 — Create CreditAccount Model And Migration

**Area:** Credits / Database  
**Type:** Model / Migration  
**Priority:** P0  
**Branch:** `feature/CONV-198-create-credit-account-model-and-migration`  
**Base branch:** `develop`  
**Depends on:** CONV-197

### Goal

Создать `credit_accounts` table и модель `CreditAccount` для хранения текущего баланса пользователя.

### TDD step

Feature/model test:

```php
it('creates a credit account for a user', function () {
    $user = User::factory()->create();

    $account = CreditAccount::factory()->create([
        'user_id' => $user->id,
        'balance' => 100,
    ]);

    expect($account->user->is($user))->toBeTrue();
    expect($account->balance)->toBe(100);
});
```

Тест должен упасть до создания model/migration/factory.

### Implementation

Создать migration:

```txt
database/migrations/*_create_credit_accounts_table.php
```

Schema:

```php
Schema::create('credit_accounts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
    $table->unsignedInteger('balance')->default(0);
    $table->timestamps();
});
```

Создать model:

```txt
app/Models/CreditAccount.php
```

Relationship:

```php
public function user(): BelongsTo
```

Добавить relationship в `User`:

```php
public function creditAccount(): HasOne
```

Создать factory.

### Acceptance criteria

- `credit_accounts` table exists.
- `CreditAccount` model exists.
- `CreditAccount` belongs to user.
- User has one credit account.
- `user_id` unique.
- Balance defaults to 0.
- Test passes.

### Definition of Done

- Тест написан первым.
- Migration/model/factory созданы.
- Relationship добавлен.
- Tests pass.
- Коммит: `CONV-198: Create credit account model and migration`

### Files likely touched

```txt
app/Models/CreditAccount.php
app/Models/User.php
database/migrations/*_create_credit_accounts_table.php
database/factories/CreditAccountFactory.php
tests/Feature/Credits/CreditAccountTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-199 — Create CreditTransaction Model And Migration

**Area:** Credits / Database  
**Type:** Model / Migration  
**Priority:** P0  
**Branch:** `feature/CONV-199-create-credit-transaction-model-and-migration`  
**Base branch:** `develop`  
**Depends on:** CONV-198

### Goal

Создать `credit_transactions` table и модель `CreditTransaction` для append-only истории изменений credits.

### TDD step

Feature/model test:

```php
it('stores credit transaction metadata as array', function () {
    $user = User::factory()->create();

    $transaction = CreditTransaction::factory()->create([
        'user_id' => $user->id,
        'amount' => 50,
        'balance_after' => 50,
        'type' => 'grant',
        'reason' => 'registration_grant',
        'metadata_json' => ['plan' => 'free'],
    ]);

    expect($transaction->user->is($user))->toBeTrue();
    expect($transaction->metadata_json)->toBeArray();
    expect($transaction->metadata_json['plan'])->toBe('free');
});
```

Тест должен упасть до создания model/migration/factory.

### Implementation

Создать migration:

```txt
database/migrations/*_create_credit_transactions_table.php
```

Schema:

```php
Schema::create('credit_transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->integer('amount');
    $table->unsignedInteger('balance_after');
    $table->string('type');
    $table->string('reason');
    $table->nullableMorphs('source');
    $table->json('metadata_json')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'created_at']);
    $table->index(['user_id', 'type']);
    $table->index(['source_type', 'source_id']);
});
```

Создать model:

```txt
app/Models/CreditTransaction.php
```

Casts:

```php
'amount' => 'integer',
'balance_after' => 'integer',
'metadata_json' => 'array',
'expires_at' => 'datetime',
```

Relationships:

```php
user(): BelongsTo
source(): MorphTo
```

### Acceptance criteria

- `credit_transactions` table exists.
- `CreditTransaction` model exists.
- Transaction belongs to user.
- Metadata is cast to array.
- Source morph columns exist.
- Indexes exist for user history and source lookup.
- Test passes.

### Definition of Done

- Тест написан первым.
- Migration/model/factory созданы.
- Tests pass.
- Коммит: `CONV-199: Create credit transaction model and migration`

### Files likely touched

```txt
app/Models/CreditTransaction.php
app/Models/User.php
database/migrations/*_create_credit_transactions_table.php
database/factories/CreditTransactionFactory.php
tests/Feature/Credits/CreditTransactionTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-200 — Add CreditTransactionType Enum

**Area:** Credits / Domain  
**Type:** Enum  
**Priority:** P0  
**Branch:** `feature/CONV-200-add-credit-transaction-type-enum`  
**Base branch:** `develop`  
**Depends on:** CONV-199

### Goal

Добавить enum типов credit transactions.

### TDD step

Unit test:

```php
it('defines credit transaction types', function () {
    expect(CreditTransactionType::Grant->value)->toBe('grant');
    expect(CreditTransactionType::Purchase->value)->toBe('purchase');
    expect(CreditTransactionType::Spend->value)->toBe('spend');
    expect(CreditTransactionType::Refund->value)->toBe('refund');
    expect(CreditTransactionType::Adjustment->value)->toBe('adjustment');
    expect(CreditTransactionType::Expiration->value)->toBe('expiration');
});
```

### Implementation

Создать:

```txt
app/Enums/CreditTransactionType.php
```

Enum:

```php
enum CreditTransactionType: string
{
    case Grant = 'grant';
    case Purchase = 'purchase';
    case Spend = 'spend';
    case Refund = 'refund';
    case Adjustment = 'adjustment';
    case Expiration = 'expiration';
}
```

Добавить cast в `CreditTransaction`:

```php
'type' => CreditTransactionType::class,
```

### Acceptance criteria

- Enum exists.
- All MVP transaction types exist.
- `CreditTransaction.type` cast uses enum.
- Tests pass.

### Definition of Done

- Тест написан первым.
- Enum добавлен.
- Model cast обновлён.
- Tests pass.
- Коммит: `CONV-200: Add credit transaction type enum`

### Files likely touched

```txt
app/Enums/CreditTransactionType.php
app/Models/CreditTransaction.php
tests/Unit/Enums/CreditTransactionTypeTest.php
tests/Feature/Credits/CreditTransactionTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-201 — Create CreditLedger Contract

**Area:** Credits / Contracts  
**Type:** Contract  
**Priority:** P0  
**Branch:** `feature/CONV-201-create-credit-ledger-contract`  
**Base branch:** `develop`  
**Depends on:** CONV-200

### Goal

Создать контракт `CreditLedger`, через который весь код будет работать с кредитами.

### TDD step

Unit test:

```php
it('has credit ledger contract methods', function () {
    $reflection = new ReflectionClass(CreditLedger::class);

    expect($reflection->hasMethod('balance'))->toBeTrue();
    expect($reflection->hasMethod('grant'))->toBeTrue();
    expect($reflection->hasMethod('spend'))->toBeTrue();
    expect($reflection->hasMethod('refund'))->toBeTrue();
});
```

### Implementation

Создать:

```txt
app/Contracts/Billing/CreditLedger.php
```

Interface:

```php
interface CreditLedger
{
    public function balance(User $user): int;

    public function grant(User $user, int $amount, string $reason, array $meta = [], ?Model $source = null): CreditTransaction;

    public function spend(User $user, int $amount, string $reason, array $meta = [], ?Model $source = null): CreditTransaction;

    public function refund(User $user, int $amount, string $reason, array $meta = [], ?Model $source = null): CreditTransaction;
}
```

Если `Model $source` создаёт лишнюю строгость, можно использовать nullable `Model` from `Illuminate\Database\Eloquent\Model`.

### Acceptance criteria

- `CreditLedger` contract exists.
- Methods balance/grant/spend/refund exist.
- Contract does not depend on Cashier/Stripe.
- Tests pass.

### Definition of Done

- Тест написан первым.
- Contract создан.
- Tests pass.
- Коммит: `CONV-201: Create credit ledger contract`

### Files likely touched

```txt
app/Contracts/Billing/CreditLedger.php
tests/Unit/Contracts/CreditLedgerContractTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-202 — Create DatabaseCreditLedger Skeleton

**Area:** Credits / Service  
**Type:** Service  
**Priority:** P0  
**Branch:** `feature/CONV-202-create-database-credit-ledger-skeleton`  
**Base branch:** `develop`  
**Depends on:** CONV-201

### Goal

Создать skeleton реализации `DatabaseCreditLedger` и зарегистрировать binding в container.

### TDD step

Unit/container test:

```php
it('resolves credit ledger contract to database implementation', function () {
    $ledger = app(CreditLedger::class);

    expect($ledger)->toBeInstanceOf(DatabaseCreditLedger::class);
});
```

Тест должен упасть до binding.

### Implementation

Создать:

```txt
app/Services/Billing/DatabaseCreditLedger.php
```

Skeleton:

```php
final class DatabaseCreditLedger implements CreditLedger
{
    public function balance(User $user): int
    {
        throw new LogicException('Not implemented yet.');
    }

    public function grant(User $user, int $amount, string $reason, array $meta = [], ?Model $source = null): CreditTransaction
    {
        throw new LogicException('Not implemented yet.');
    }

    public function spend(User $user, int $amount, string $reason, array $meta = [], ?Model $source = null): CreditTransaction
    {
        throw new LogicException('Not implemented yet.');
    }

    public function refund(User $user, int $amount, string $reason, array $meta = [], ?Model $source = null): CreditTransaction
    {
        throw new LogicException('Not implemented yet.');
    }
}
```

Зарегистрировать binding в `AppServiceProvider` или отдельном provider:

```php
$this->app->bind(CreditLedger::class, DatabaseCreditLedger::class);
```

### Acceptance criteria

- `DatabaseCreditLedger` exists.
- It implements `CreditLedger`.
- Container resolves contract to implementation.
- Methods are skeleton only.
- Tests pass.

### Definition of Done

- Тест написан первым.
- Skeleton создан.
- Binding добавлен.
- Tests pass.
- Коммит: `CONV-202: Create database credit ledger skeleton`

### Files likely touched

```txt
app/Services/Billing/DatabaseCreditLedger.php
app/Providers/AppServiceProvider.php
tests/Unit/Billing/DatabaseCreditLedgerBindingTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-203 — Test Credit Account Auto Creation

**Area:** Credits / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-203-test-credit-account-auto-creation`  
**Base branch:** `develop`  
**Depends on:** CONV-202

### Goal

Написать падающий тест: новый пользователь получает credit account автоматически.

### TDD step

Feature test:

```php
it('creates credit account for new user automatically', function () {
    $user = User::factory()->create();

    expect($user->fresh()->creditAccount)->not->toBeNull();
    expect($user->fresh()->creditAccount->balance)->toBe(0);
});
```

Тест должен упасть до реализации auto creation.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Тест проверяет auto-created account.
- Initial balance ожидается 0.
- Тест падает до CONV-204.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-203: Test credit account auto creation`

### Files likely touched

```txt
tests/Feature/Credits/CreditAccountAutoCreationTest.php
```

После этого сделай MR в `develop`. Merge разрешён после подтверждения, что новый тест падает ожидаемо до реализации.

---

## CONV-204 — Implement Credit Account Auto Creation

**Area:** Credits / User Lifecycle  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-204-implement-credit-account-auto-creation`  
**Base branch:** `develop`  
**Depends on:** CONV-203

### Goal

Автоматически создавать `CreditAccount` при создании пользователя.

### TDD step

Использовать падающий тест из CONV-203.

### Implementation

Варианты:

```txt
- User model booted callback;
- UserCreated listener;
- observer.
```

Рекомендация для MVP: listener/observer, чтобы не раздувать `User` model.

Создать observer или listener:

```txt
app/Observers/UserObserver.php
```

Logic:

```php
public function created(User $user): void
{
    $user->creditAccount()->firstOrCreate([], [
        'balance' => 0,
    ]);
}
```

Зарегистрировать observer.

### Acceptance criteria

- New user gets credit account automatically.
- Initial balance is 0.
- Existing user creation tests still pass.
- Auto creation is idempotent.
- Tests pass.

### Definition of Done

- Auto creation реализован.
- Падающий тест проходит.
- Related tests pass.
- Коммит: `CONV-204: Implement credit account auto creation`

### Files likely touched

```txt
app/Observers/UserObserver.php
app/Providers/AppServiceProvider.php
app/Models/User.php
tests/Feature/Credits/CreditAccountAutoCreationTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-205 — Test Grant Credits

**Area:** Credits / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-205-test-grant-credits`  
**Base branch:** `develop`  
**Depends on:** CONV-204

### Goal

Написать падающий тест: `CreditLedger::grant()` увеличивает баланс и создаёт transaction.

### TDD step

Feature/unit test:

```php
it('grants credits and records transaction', function () {
    $user = User::factory()->create();

    $transaction = app(CreditLedger::class)->grant(
        user: $user,
        amount: 50,
        reason: 'test_grant',
        meta: ['source' => 'test']
    );

    expect(app(CreditLedger::class)->balance($user))->toBe(50);
    expect($transaction->amount)->toBe(50);
    expect($transaction->balance_after)->toBe(50);
    expect($transaction->type)->toBe(CreditTransactionType::Grant);
    expect($transaction->reason)->toBe('test_grant');
});
```

Тест должен упасть до CONV-206.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Проверяет balance increase.
- Проверяет transaction amount/type/reason/balance_after.
- Тест падает до реализации.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-205: Test grant credits`

### Files likely touched

```txt
tests/Feature/Credits/CreditLedgerTest.php
```

После этого сделай MR в `develop`. Merge разрешён после подтверждения, что новый тест падает ожидаемо до реализации.

---

## CONV-206 — Implement Grant Credits

**Area:** Credits / Ledger  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-206-implement-grant-credits`  
**Base branch:** `develop`  
**Depends on:** CONV-205

### Goal

Реализовать `DatabaseCreditLedger::grant()` и `balance()`.

### TDD step

Использовать падающий тест из CONV-205.

### Implementation

Создать exception для invalid amount:

```txt
app/Exceptions/Billing/InvalidCreditAmountException.php
```

Правило:

```txt
amount must be > 0
```

`balance()`:

```php
public function balance(User $user): int
{
    return (int) $user->creditAccount()->firstOrCreate()->balance;
}
```

`grant()` через transaction:

```php
return DB::transaction(function () use ($user, $amount, $reason, $meta, $source) {
    if ($amount <= 0) {
        throw InvalidCreditAmountException::becauseAmountMustBePositive();
    }

    $account = CreditAccount::query()
        ->where('user_id', $user->id)
        ->lockForUpdate()
        ->firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

    $account->increment('balance', $amount);
    $account->refresh();

    return CreditTransaction::create([
        'user_id' => $user->id,
        'amount' => $amount,
        'balance_after' => $account->balance,
        'type' => CreditTransactionType::Grant,
        'reason' => $reason,
        'metadata_json' => $meta,
        'source_type' => $source?->getMorphClass(),
        'source_id' => $source?->getKey(),
    ]);
});
```

Если `nullableMorphs` не удобно заполнять вручную, использовать `$transaction->source()->associate($source)`.

### Acceptance criteria

- `balance()` returns current balance.
- `grant()` increases balance.
- `grant()` creates transaction.
- `balance_after` correct.
- Invalid amount rejected.
- Tests pass.

### Definition of Done

- Grant реализован.
- Balance реализован.
- Invalid amount exception добавлен.
- Tests pass.
- Коммит: `CONV-206: Implement grant credits`

### Files likely touched

```txt
app/Services/Billing/DatabaseCreditLedger.php
app/Exceptions/Billing/InvalidCreditAmountException.php
tests/Feature/Credits/CreditLedgerTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-207 — Test Spend Credits

**Area:** Credits / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-207-test-spend-credits`  
**Base branch:** `develop`  
**Depends on:** CONV-206

### Goal

Написать падающий тест: `CreditLedger::spend()` уменьшает баланс и создаёт transaction.

### TDD step

Feature test:

```php
it('spends credits and records transaction', function () {
    $user = User::factory()->create();
    $ledger = app(CreditLedger::class);

    $ledger->grant($user, 100, 'test_grant');

    $transaction = $ledger->spend($user, 30, 'test_spend', [
        'operation' => 'png_to_jpg',
    ]);

    expect($ledger->balance($user))->toBe(70);
    expect($transaction->amount)->toBe(-30);
    expect($transaction->balance_after)->toBe(70);
    expect($transaction->type)->toBe(CreditTransactionType::Spend);
    expect($transaction->reason)->toBe('test_spend');
});
```

Тест должен упасть до CONV-208.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Проверяет balance decrease.
- Проверяет negative transaction amount.
- Проверяет transaction type/reason/balance_after.
- Тест падает до реализации.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-207: Test spend credits`

### Files likely touched

```txt
tests/Feature/Credits/CreditLedgerTest.php
```

После этого сделай MR в `develop`. Merge разрешён после подтверждения, что новый тест падает ожидаемо до реализации.

---

## CONV-208 — Implement Spend Credits

**Area:** Credits / Ledger  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-208-implement-spend-credits`  
**Base branch:** `develop`  
**Depends on:** CONV-207

### Goal

Реализовать `DatabaseCreditLedger::spend()` для успешного списания credits.

### TDD step

Использовать падающий тест из CONV-207.

### Implementation

В `DatabaseCreditLedger::spend()`:

```php
return DB::transaction(function () use ($user, $amount, $reason, $meta, $source) {
    if ($amount <= 0) {
        throw InvalidCreditAmountException::becauseAmountMustBePositive();
    }

    $account = CreditAccount::query()
        ->where('user_id', $user->id)
        ->lockForUpdate()
        ->firstOrFail();

    $account->decrement('balance', $amount);
    $account->refresh();

    return CreditTransaction::create([
        'user_id' => $user->id,
        'amount' => -$amount,
        'balance_after' => $account->balance,
        'type' => CreditTransactionType::Spend,
        'reason' => $reason,
        'metadata_json' => $meta,
        'source_type' => $source?->getMorphClass(),
        'source_id' => $source?->getKey(),
    ]);
});
```

Проверка недостаточного баланса будет добавлена в CONV-209/CONV-210.  
Но если уже очевидно, можно добавить сразу, не ломая TDD, если тесты есть в следующей задаче.

### Acceptance criteria

- `spend()` decreases balance.
- Transaction amount is negative.
- Transaction type is `spend`.
- `balance_after` correct.
- Invalid amount rejected.
- Tests pass.

### Definition of Done

- Spend реализован.
- Тесты проходят.
- Коммит: `CONV-208: Implement spend credits`

### Files likely touched

```txt
app/Services/Billing/DatabaseCreditLedger.php
tests/Feature/Credits/CreditLedgerTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-209 — Test Insufficient Credits Are Blocked

**Area:** Credits / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-209-test-insufficient-credits-are-blocked`  
**Base branch:** `develop`  
**Depends on:** CONV-208

### Goal

Написать падающий тест: нельзя списать больше credits, чем есть на балансе.

### TDD step

Feature test:

```php
it('does not allow spending more credits than available', function () {
    $user = User::factory()->create();
    $ledger = app(CreditLedger::class);

    $ledger->grant($user, 10, 'test_grant');

    expect(fn () => $ledger->spend($user, 20, 'too_expensive'))
        ->toThrow(InsufficientCreditsException::class);

    expect($ledger->balance($user))->toBe(10);
    expect(CreditTransaction::query()->where('type', CreditTransactionType::Spend)->count())->toBe(0);
});
```

Тест должен упасть до CONV-210.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Overspend throws explicit exception.
- Balance remains unchanged.
- Spend transaction is not created.
- Тест падает до реализации.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-209: Test insufficient credits are blocked`

### Files likely touched

```txt
tests/Feature/Credits/CreditLedgerTest.php
```

После этого сделай MR в `develop`. Merge разрешён после подтверждения, что новый тест падает ожидаемо до реализации.

---

## CONV-210 — Make Credit Spending Transactional

**Area:** Credits / Ledger  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-210-make-credit-spending-transactional`  
**Base branch:** `develop`  
**Depends on:** CONV-209

### Goal

Добавить insufficient balance guard и гарантировать transactional spend с `lockForUpdate()`.

### TDD step

Использовать падающий тест из CONV-209.

Дополнительно можно добавить структурный test через mock не стоит. Лучше проверить observable behavior:

```txt
- failed spend keeps balance unchanged;
- failed spend creates no transaction.
```

### Implementation

Создать exception:

```txt
app/Exceptions/Billing/InsufficientCreditsException.php
```

Пример:

```php
final class InsufficientCreditsException extends DomainException
{
    public static function make(int $required, int $available): self
    {
        return new self("Not enough credits. Required: {$required}, available: {$available}.");
    }
}
```

В `spend()` внутри DB transaction после `lockForUpdate()`:

```php
if ($account->balance < $amount) {
    throw InsufficientCreditsException::make($amount, $account->balance);
}
```

Убедиться, что transaction не создаётся до проверки.

### Acceptance criteria

- Overspend blocked.
- Explicit `InsufficientCreditsException` thrown.
- Balance unchanged after failed spend.
- No spend transaction after failed spend.
- Spend uses DB transaction.
- Spend locks account row with `lockForUpdate()`.
- Tests pass.

### Definition of Done

- InsufficientCreditsException добавлен.
- Spend guard добавлен.
- Transactional row lock используется.
- Тесты проходят.
- Коммит: `CONV-210: Make credit spending transactional`

### Files likely touched

```txt
app/Services/Billing/DatabaseCreditLedger.php
app/Exceptions/Billing/InsufficientCreditsException.php
tests/Feature/Credits/CreditLedgerTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-211 — Test Refund Credits

**Area:** Credits / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-211-test-refund-credits`  
**Base branch:** `develop`  
**Depends on:** CONV-210

### Goal

Написать падающий тест: `CreditLedger::refund()` возвращает credits и пишет transaction.

### TDD step

Feature test:

```php
it('refunds credits and records transaction', function () {
    $user = User::factory()->create();
    $ledger = app(CreditLedger::class);

    $ledger->grant($user, 100, 'test_grant');
    $ledger->spend($user, 30, 'test_spend');

    $transaction = $ledger->refund($user, 30, 'conversion_failed_refund');

    expect($ledger->balance($user))->toBe(100);
    expect($transaction->amount)->toBe(30);
    expect($transaction->balance_after)->toBe(100);
    expect($transaction->type)->toBe(CreditTransactionType::Refund);
    expect($transaction->reason)->toBe('conversion_failed_refund');
});
```

Тест должен упасть до CONV-212.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Refund increases balance.
- Refund creates transaction.
- `balance_after` correct.
- Тест падает до реализации.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-211: Test refund credits`

### Files likely touched

```txt
tests/Feature/Credits/CreditLedgerTest.php
```

После этого сделай MR в `develop`. Merge разрешён после подтверждения, что новый тест падает ожидаемо до реализации.

---

## CONV-212 — Implement Refund Credits

**Area:** Credits / Ledger  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-212-implement-refund-credits`  
**Base branch:** `develop`  
**Depends on:** CONV-211

### Goal

Реализовать `DatabaseCreditLedger::refund()`.

### TDD step

Использовать падающий тест из CONV-211.

### Implementation

`refund()` похож на `grant()`, но transaction type должен быть `Refund`.

```php
return DB::transaction(function () use ($user, $amount, $reason, $meta, $source) {
    if ($amount <= 0) {
        throw InvalidCreditAmountException::becauseAmountMustBePositive();
    }

    $account = CreditAccount::query()
        ->where('user_id', $user->id)
        ->lockForUpdate()
        ->firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

    $account->increment('balance', $amount);
    $account->refresh();

    return CreditTransaction::create([
        'user_id' => $user->id,
        'amount' => $amount,
        'balance_after' => $account->balance,
        'type' => CreditTransactionType::Refund,
        'reason' => $reason,
        'metadata_json' => $meta,
        'source_type' => $source?->getMorphClass(),
        'source_id' => $source?->getKey(),
    ]);
});
```

Не связывать refund с конкретным spend transaction в MVP.  
Это появится позже через `conversion_credit_charges`.

### Acceptance criteria

- Refund increases balance.
- Refund creates transaction.
- Transaction type is `refund`.
- `balance_after` correct.
- Invalid refund amount rejected.
- Tests pass.

### Definition of Done

- Refund реализован.
- Тесты проходят.
- Коммит: `CONV-212: Implement refund credits`

### Files likely touched

```txt
app/Services/Billing/DatabaseCreditLedger.php
tests/Feature/Credits/CreditLedgerTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-213 — Grant Registration Starter Credits

**Area:** Credits / User Lifecycle  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-213-grant-registration-starter-credits`  
**Base branch:** `develop`  
**Depends on:** CONV-212

### Goal

Начислять стартовые credits при создании нового пользователя.

### TDD step

Feature test:

```php
it('grants starter credits to newly registered user', function () {
    $user = User::factory()->create();

    expect(app(CreditLedger::class)->balance($user))->toBe(50);

    $this->assertDatabaseHas('credit_transactions', [
        'user_id' => $user->id,
        'amount' => 50,
        'balance_after' => 50,
        'reason' => 'registration_grant',
    ]);
});
```

Если Phase 13 config уже содержит другое значение для free `monthly_credits`, использовать его в тесте:

```php
$expected = app(FeatureAccessService::class)->limit($user, 'monthly_credits');
```

### Implementation

В User observer/listener после создания account вызвать:

```php
$amount = (int) app(FeatureAccessService::class)->limit($user, 'monthly_credits');

app(CreditLedger::class)->grant(
    user: $user,
    amount: $amount,
    reason: 'registration_grant',
    meta: [
        'plan' => $user->plan->value,
    ],
);
```

Важное правило: не дублировать starter grant при повторном сохранении user.

Если observer уже создаёт account, расширить его аккуратно.

### Acceptance criteria

- New user gets starter credits.
- Starter amount comes from FeatureAccessService/plan config.
- Transaction reason is `registration_grant`.
- Starter grant is not duplicated on user update.
- Tests pass.

### Definition of Done

- Тест написан первым.
- Starter grant реализован.
- Используется CreditLedger, не direct balance update.
- Tests pass.
- Коммит: `CONV-213: Grant registration starter credits`

### Files likely touched

```txt
app/Observers/UserObserver.php
app/Services/Billing/DatabaseCreditLedger.php
tests/Feature/Credits/RegistrationCreditsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-214 — Show Credit Balance In User Dropdown

**Area:** UI / Credits  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-214-show-credit-balance-in-user-dropdown`  
**Base branch:** `develop`  
**Depends on:** CONV-213

### Goal

Показать текущий credit balance в user dropdown.

### TDD step

Feature/Livewire/Blade test, адаптировать к текущей реализации dropdown:

```php
it('shows user credit balance in dropdown', function () {
    $user = User::factory()->create();
    app(CreditLedger::class)->grant($user, 25, 'test_extra_grant');

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Credits')
        ->assertSee((string) app(CreditLedger::class)->balance($user));
});
```

Если dropdown рендерится отдельным Blade component, тестировать component render.

### Implementation

В user dropdown добавить компактный блок:

```txt
Credits
75 available
```

Получать balance через `CreditLedger` или view model, но не через прямой доступ к account в Blade, если уже есть service.

Правильно:

```php
$creditsBalance = app(CreditLedger::class)->balance(auth()->user());
```

Лучше — подготовить в Livewire/layout view composer, если dropdown не Livewire.

Не добавлять buy credits CTA в этой фазе, если нет checkout.

### Acceptance criteria

- User dropdown shows current credits balance.
- Balance uses CreditLedger.
- No direct balance mutation in UI.
- No buy credits checkout link yet.
- Tests pass.

### Definition of Done

- Тест написан первым.
- Dropdown обновлён.
- Balance отображается корректно.
- Tests pass.
- `npm run build` passes.
- Коммит: `CONV-214: Show credit balance in user dropdown`

### Files likely touched

```txt
resources/views/components/user-dropdown.blade.php
resources/views/layouts/app.blade.php
app/View/Components/UserDropdown.php
app/Livewire/UserDropdown.php
tests/Feature/Credits/UserDropdownCreditsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 14 Completion Criteria

Phase 14 завершена, когда:

```txt
- CONV-198–CONV-214 выполнены;
- credit_accounts table exists;
- credit_transactions table exists;
- CreditAccount model exists;
- CreditTransaction model exists;
- CreditTransactionType enum exists;
- CreditLedger contract exists;
- DatabaseCreditLedger implements CreditLedger;
- CreditLedger is bound in the container;
- new user gets credit account;
- new user gets starter credits;
- grant credits works;
- spend credits works;
- overspend is blocked;
- spend is transactional;
- spend uses row lock;
- refund credits works;
- every balance change creates transaction;
- transaction balance_after is correct;
- credit balance is visible in user dropdown;
- no Cashier installed;
- no Stripe logic added;
- no checkout logic added;
- no conversion cost estimator added;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 14

Без отдельной задачи нельзя:

```txt
- устанавливать Laravel Cashier;
- добавлять Stripe env;
- создавать checkout routes;
- создавать billing page;
- создавать credit pack checkout;
- создавать subscriptions;
- создавать invoices UI;
- создавать conversion cost estimator;
- создавать conversion_credit_charges table;
- внедрять credits в CreateConversionJobAction;
- списывать credits за конвертацию;
- добавлять API billing;
- добавлять admin adjustment UI;
- добавлять несколько типов credit balances;
- добавлять reserve/capture/refund workflow;
- добавлять Spike;
- использовать сторонний credits package.
```

---

# 12. Recommended Execution Order

```txt
CONV-198 Create CreditAccount Model And Migration
CONV-199 Create CreditTransaction Model And Migration
CONV-200 Add CreditTransactionType Enum
CONV-201 Create CreditLedger Contract
CONV-202 Create DatabaseCreditLedger Skeleton
CONV-203 Test Credit Account Auto Creation
CONV-204 Implement Credit Account Auto Creation
CONV-205 Test Grant Credits
CONV-206 Implement Grant Credits
CONV-207 Test Spend Credits
CONV-208 Implement Spend Credits
CONV-209 Test Insufficient Credits Are Blocked
CONV-210 Make Credit Spending Transactional
CONV-211 Test Refund Credits
CONV-212 Implement Refund Credits
CONV-213 Grant Registration Starter Credits
CONV-214 Show Credit Balance In User Dropdown
```

---

# 13. Release

После завершения Phase 14:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh --seed

git checkout -b release/v0.1.14-phase14-custom-credit-ledger
git push -u origin release/v0.1.14-phase14-custom-credit-ledger
```

После этого сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.14-phase14-custom-credit-ledger -m "File Converter Phase 14 custom credit ledger"
git push origin v0.1.14-phase14-custom-credit-ledger
```
