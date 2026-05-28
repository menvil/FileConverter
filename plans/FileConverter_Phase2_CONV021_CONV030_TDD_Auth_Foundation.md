# File Converter — Phase 02 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 02 — Auth Foundation**  
Диапазон задач: **CONV-021 → CONV-030**  
Основа нумерации: Phase 00 содержит `CONV-001 → CONV-008`, Phase 01 содержит `CONV-009 → CONV-020`, поэтому Phase 02 начинается с `CONV-021`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 02 соответствует блоку:

```txt
Phase 02 — Auth Foundation
```

Правильный диапазон Phase 02:

```txt
CONV-021 — Install Laravel Breeze Blade
CONV-022 — Configure Auth Views Baseline
CONV-023 — Test Guest Cannot Access Dashboard
CONV-024 — Protect Dashboard Route
CONV-025 — Test Authenticated User Can Access Dashboard
CONV-026 — Add User Plan Field
CONV-027 — Add User Settings Field
CONV-028 — Add User Display Helpers
CONV-029 — Wire User Dropdown To Auth User
CONV-030 — Add Logout Action To User Dropdown
```

Phase 02 добавляет authentication foundation, но **не добавляет billing**, **не добавляет credits**, **не добавляет file upload**, **не добавляет converter logic**.

Цель фазы — чтобы будущий dashboard, история конвертаций, credits, billing и API keys всегда имели владельца: authenticated user.

---

# 2. Цель Phase 02

Phase 02 должна подготовить полноценную базовую авторизацию для MVP.

После Phase 02 должно быть готово:

```txt
- Laravel Breeze Blade installed;
- login/register/logout flow works;
- dashboard route is protected;
- guest redirects to login;
- authenticated user can access dashboard;
- user has local plan field;
- new users default to free plan;
- user has settings JSON field;
- user dropdown displays real auth user data;
- logout works from user dropdown.
```

Это всё ещё инфраструктурно-продуктовая фаза.  
Настоящий dashboard converter flow появится позже.

---

# 3. Scope Phase 02

## Входит

```txt
- Laravel Breeze Blade installation;
- auth routes;
- login page;
- register page;
- logout flow;
- dashboard auth middleware;
- tests for guest/auth dashboard behavior;
- users.plan field;
- Plan enum/value object if appropriate;
- users.settings JSON field;
- user display helper methods;
- real user data inside header dropdown;
- logout action from dropdown.
```

## Не входит

```txt
- Laravel Cashier;
- Stripe;
- billing page;
- credits;
- CreditLedger;
- FeatureAccessService;
- ConversionCostEstimator;
- file upload;
- converter registry;
- conversion jobs;
- API keys;
- social login;
- email verification requirement;
- password reset customization;
- user profile edit page;
- teams/workspaces;
- admin roles;
- two-factor authentication.
```

Billing будет отдельной фазой.  
FeatureAccessService будет отдельной фазой.  
Credits будут отдельной фазой.  
API keys будут отдельной фазой.

---

# 4. Critical Decisions

## 4.1. Breeze Blade, not Jetstream

Для MVP нужен лёгкий authentication scaffold.

Правильно:

```txt
Laravel Breeze Blade
```

Неправильно для MVP:

```txt
Jetstream
Fortify customization from scratch
Socialite
Teams
Inertia auth stack
React/Vue auth stack
```

Причина: проект строится на Blade + Livewire + Alpine. Breeze Blade даёт достаточно: login, register, logout, password reset baseline.

## 4.2. Dashboard becomes protected in this phase

В Phase 00 `/dashboard` был placeholder без auth.  
В Phase 02 это нужно изменить.

Правильное поведение после Phase 02:

```txt
Guest GET /dashboard → redirect to /login
Auth user GET /dashboard → 200 OK
```

Тест из Phase 00 нужно обновить, а не оставить конфликтующим.

## 4.3. Local plan field is required before billing

Даже если Cashier появится позже, приложению нужен локальный план:

```txt
free
pro
max
```

Stripe/Cashier — это источник платежного состояния.  
Локальный `users.plan` — быстрый application-level state для feature gates.

В Phase 02 мы только добавляем поле и default `free`.  
Никаких payment/webhook rules ещё нет.

## 4.4. User settings must be JSON from the start

Нужны будущие настройки:

```txt
- default image quality;
- remove metadata by default;
- default output behavior;
- notification preferences;
- auto-delete preferences.
```

Но в Phase 02 мы не реализуем эти настройки в UI.  
Мы только добавляем техническое поле `users.settings` с безопасным cast.

## 4.5. User dropdown should use real auth data

Phase 01 создала UI-shell для dropdown.  
Phase 02 должна подключить реальные данные:

```txt
name
email
plan
initials/avatar placeholder
logout
```

Но не надо добавлять туда:

```txt
credits balance
storage usage
billing CTAs
API links
```

Они появятся позже, когда будут реальные сервисы.

---

# 5. Architecture Rules

## 5.1. Auth is framework-level, product access is application-level

Breeze/Cashier/Auth отвечают за identity.

Feature access позже должен быть отдельным сервисом:

```php
FeatureAccessService::allows($user, 'api_access');
FeatureAccessService::limit($user, 'max_file_size_mb');
```

Нельзя в Phase 02 начать размазывать доступы по views:

```blade
@if ($user->plan === 'pro')
```

План можно показывать, но бизнес-решения по доступам позже должны идти через сервис.

## 5.2. No billing logic in User model

В Phase 02 допустимы helpers:

```php
$user->isFreePlan();
$user->displayName();
$user->initials();
```

Недопустимо:

```php
$user->hasCredits();
$user->canUseApi();
$user->canConvertVideo();
$user->chargeForConversion();
```

Это не auth foundation.

## 5.3. Keep Breeze views minimally adapted

Не надо тратить фазу на pixel-perfect auth pages.

Допустимо:

```txt
- brand name;
- app layout consistency;
- basic Tailwind cleanup.
```

Недопустимо:

```txt
- full marketing redesign of login/register;
- pricing CTAs inside login;
- OAuth buttons without implementation;
- animated auth pages.
```

## 5.4. Tests before route behavior changes

Перед изменением `/dashboard` auth behavior должен быть тест:

```txt
guest cannot access dashboard
```

Потом реализация.

---

# 6. GitFlow для Phase 02

## Base branch

Все задачи Phase 02 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-021-install-laravel-breeze-blade
feature/CONV-024-protect-dashboard-route
feature/CONV-029-wire-user-dropdown-to-auth-user
```

## Commit format

```txt
CONV-021: Install Laravel Breeze Blade
CONV-024: Protect dashboard route
CONV-029: Wire user dropdown to auth user
```

## Release branch

После выполнения `CONV-021`–`CONV-030`:

```txt
release/v0.1.2-phase02-auth-foundation
```

## Tag

После merge release branch в `main`:

```txt
v0.1.2-phase02-auth-foundation
```

---

# 7. TDD Rules for Phase 02

## Для auth routes

Тестировать:

```txt
- login page renders;
- register page renders;
- user can register;
- user can login;
- user can logout.
```

Breeze может уже добавить часть тестов. Если они есть — не дублировать без смысла, но убедиться, что они проходят.

## Для dashboard protection

Test-first:

```txt
- guest is redirected from dashboard to login;
- authenticated user can access dashboard.
```

## Для user plan

Тестировать:

```txt
- users table has plan field;
- new user defaults to free;
- invalid plan should not be silently accepted if enum/cast is used.
```

## Для user settings

Тестировать:

```txt
- settings field defaults to empty array;
- settings casts to array;
- settings can store simple preferences.
```

## Для user dropdown

Тестировать render-level:

```txt
- authenticated dashboard page shows user name/email;
- logout form/action exists.
```

Если dropdown behavior uses Alpine, не надо browser-test на открытие в Phase 02. Это UI foundation concern. Достаточно render/access smoke test.

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Auth / User / Dashboard / Tests / UI
Type: Setup / Test / Feature / Migration / UI
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

# 9. Phase 02 Atomic Tasks

---

## CONV-021 — Install Laravel Breeze Blade

**Area:** Auth / Bootstrap  
**Type:** Setup  
**Priority:** P0  
**Branch:** `feature/CONV-021-install-laravel-breeze-blade`  
**Base branch:** `develop`  
**Depends on:** CONV-020

### Goal

Установить Laravel Breeze в Blade-режиме как минимальный auth scaffold.

### TDD step

No direct test — package/scaffold installation.

После установки должны проходить:

```bash
php artisan test
npm run build
```

Если Breeze добавил свои auth tests, они должны проходить без изменений.

### Implementation

Установить Breeze:

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install
npm run build
php artisan migrate
```

Проверить, что не выбран Inertia/React/Vue stack.

Не кастомизировать auth UI глубоко в этой задаче.

### Acceptance criteria

- Laravel Breeze установлен.
- Используется Blade stack.
- Login/register routes существуют.
- `npm run build` проходит.
- `php artisan test` проходит.
- React/Vue/Inertia не добавлены.

### Definition of Done

- Breeze Blade установлен.
- Auth routes появились.
- Tests/build проходят.
- Коммит: `CONV-021: Install Laravel Breeze Blade`

### Files likely touched

```txt
composer.json
composer.lock
package.json
package-lock.json
routes/auth.php
routes/web.php
app/Http/Controllers/Auth/*
app/Http/Requests/Auth/*
resources/views/auth/*
resources/views/layouts/*
resources/js/app.js
resources/css/app.css
tests/Feature/Auth/*
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-022 — Configure Auth Views Baseline

**Area:** Auth / UI  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-022-configure-auth-views-baseline`  
**Base branch:** `develop`  
**Depends on:** CONV-021

### Goal

Минимально привести Breeze auth views к проекту File Converter: бренд, title, базовая совместимость с layout.

### TDD step

Feature tests:

```php
it('renders the login page', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('File Converter');
});

it('renders the register page', function () {
    $this->get('/register')
        ->assertOk()
        ->assertSee('File Converter');
});
```

Тесты могут упасть до добавления бренда в views.

### Implementation

Обновить auth views минимально:

```txt
- login page shows File Converter;
- register page shows File Converter;
- app logo/wordmark uses project name;
- no marketing sections;
- no pricing CTAs.
```

Не делать pixel-perfect дизайн.

### Acceptance criteria

- `/login` returns 200.
- `/register` returns 200.
- Login/register pages show `File Converter`.
- Breeze auth functionality не сломана.
- Tests pass.

### Definition of Done

- Тесты написаны.
- Auth views минимально адаптированы.
- Tests/build pass.
- Коммит: `CONV-022: Configure auth views baseline`

### Files likely touched

```txt
resources/views/auth/login.blade.php
resources/views/auth/register.blade.php
resources/views/components/application-logo.blade.php
resources/views/layouts/guest.blade.php
tests/Feature/Auth/AuthPagesTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-023 — Test Guest Cannot Access Dashboard

**Area:** Dashboard / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-023-test-guest-cannot-access-dashboard`  
**Base branch:** `develop`  
**Depends on:** CONV-022

### Goal

Написать падающий тест: guest не может открыть `/dashboard`.

### TDD step

Feature test:

```php
it('redirects guest from dashboard to login', function () {
    $this->get('/dashboard')
        ->assertRedirect('/login');
});
```

Если старый тест из Phase 00 проверяет `assertOk()`, его нужно изменить или заменить.  
Нельзя оставить конфликтующие тесты.

### Implementation

Только добавить/изменить тест.

Не добавлять middleware в этой задаче.  
Это задача CONV-024.

### Acceptance criteria

- Тест существует.
- Тест ожидает redirect guest → login.
- Старый dashboard public smoke test не конфликтует.
- Тест падает до CONV-024.

### Definition of Done

- Тест написан первым.
- Тест ожидаемо падает.
- Нет реализации middleware.
- Коммит: `CONV-023: Test guest cannot access dashboard`

### Files likely touched

```txt
tests/Feature/DashboardRouteTest.php
```

После этого сделай MR в `develop`. Merge разрешён даже с ожидаемо падающим targeted test только если такой workflow принят. Если проект требует always-green mainline, объединить CONV-023 и CONV-024 в один MR с двумя коммитами.

---

## CONV-024 — Protect Dashboard Route

**Area:** Dashboard / Auth  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-024-protect-dashboard-route`  
**Base branch:** `develop`  
**Depends on:** CONV-023

### Goal

Защитить `/dashboard` auth middleware.

### TDD step

Использовать падающий тест из CONV-023.

### Implementation

Обновить route:

```php
Route::view('/dashboard', 'dashboard')
    ->middleware(['auth'])
    ->name('dashboard');
```

Если Breeze уже создал dashboard route, не дублировать.  
Привести к одному canonical route.

### Acceptance criteria

- Guest `/dashboard` redirects to `/login`.
- Route named `dashboard` remains available.
- No duplicate dashboard routes.
- Test from CONV-023 passes.

### Definition of Done

- Dashboard protected.
- Tests pass.
- Коммит: `CONV-024: Protect dashboard route`

### Files likely touched

```txt
routes/web.php
tests/Feature/DashboardRouteTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-025 — Test Authenticated User Can Access Dashboard

**Area:** Dashboard / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-025-test-authenticated-user-can-access-dashboard`  
**Base branch:** `develop`  
**Depends on:** CONV-024

### Goal

Добавить тест: authenticated user может открыть `/dashboard`.

### TDD step

Feature test:

```php
it('allows authenticated user to access dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('File Converter Dashboard');
});
```

Тест может уже проходить после CONV-024. Это нормально, если route/view корректны.  
Главная цель — зафиксировать ожидаемое поведение.

### Implementation

Если тест падает из-за view/layout — исправить минимально.

Не добавлять настоящий dashboard UI.

### Acceptance criteria

- Auth user gets 200 from `/dashboard`.
- Page contains dashboard placeholder.
- Guest test still passes.
- No upload/converter UI added.

### Definition of Done

- Тест добавлен.
- Tests pass.
- Коммит: `CONV-025: Test authenticated user can access dashboard`

### Files likely touched

```txt
tests/Feature/DashboardRouteTest.php
resources/views/dashboard.blade.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-026 — Add User Plan Field

**Area:** User / Database  
**Type:** Migration / Feature  
**Priority:** P0  
**Branch:** `feature/CONV-026-add-user-plan-field`  
**Base branch:** `develop`  
**Depends on:** CONV-025

### Goal

Добавить локальное поле `plan` пользователю с default `free`.

### TDD step

Feature/model test:

```php
it('assigns free plan to new users by default', function () {
    $user = User::factory()->create();

    expect($user->fresh()->plan)->toBe('free');
});
```

Если используется enum cast:

```php
expect($user->fresh()->plan)->toBe(Plan::Free);
```

Тест должен упасть до migration/cast.

### Implementation

Создать enum:

```txt
app/Enums/Plan.php
```

Значения:

```php
enum Plan: string
{
    case Free = 'free';
    case Pro = 'pro';
    case Max = 'max';
}
```

Migration:

```php
$table->string('plan')->default('free')->after('password');
```

User model cast:

```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'plan' => Plan::class,
    ];
}
```

Если проект использует старый `$casts`, адаптировать к текущей версии Laravel.

### Acceptance criteria

- `users.plan` exists.
- Default value is `free`.
- `Plan` enum exists.
- User model casts plan to enum.
- New user defaults to Free plan.
- Tests pass.

### Definition of Done

- Тест написан.
- Migration добавлена.
- Enum добавлен.
- Cast добавлен.
- Tests pass.
- Коммит: `CONV-026: Add user plan field`

### Files likely touched

```txt
app/Enums/Plan.php
app/Models/User.php
database/migrations/*add_plan_to_users_table.php
tests/Feature/User/UserPlanTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-027 — Add User Settings Field

**Area:** User / Database  
**Type:** Migration / Feature  
**Priority:** P0  
**Branch:** `feature/CONV-027-add-user-settings-field`  
**Base branch:** `develop`  
**Depends on:** CONV-026

### Goal

Добавить `users.settings` JSON field для будущих пользовательских настроек.

### TDD step

Feature/model test:

```php
it('casts user settings to array', function () {
    $user = User::factory()->create([
        'settings' => [
            'default_image_quality' => 'high',
        ],
    ]);

    expect($user->fresh()->settings)->toBeArray();
    expect($user->fresh()->settings['default_image_quality'])->toBe('high');
});
```

Default test:

```php
it('defaults user settings to an empty array', function () {
    $user = User::factory()->create();

    expect($user->fresh()->settings)->toBeArray();
});
```

### Implementation

Migration:

```php
$table->json('settings')->nullable()->after('plan');
```

User cast:

```php
'settings' => 'array',
```

Добавить accessor, если нужно гарантировать empty array:

```php
public function getSettingsAttribute($value): array
{
    return $value ? json_decode($value, true) : [];
}
```

Но не усложнять, если Laravel cast + default работает через factory.

Лучше migration default не ставить для JSON, чтобы не ловить несовместимость разных DB.

### Acceptance criteria

- `users.settings` exists.
- Settings cast to array.
- New user settings are safely handled as array.
- Can store simple preferences.
- Tests pass.

### Definition of Done

- Тесты написаны.
- Migration добавлена.
- Cast добавлен.
- Tests pass.
- Коммит: `CONV-027: Add user settings field`

### Files likely touched

```txt
app/Models/User.php
database/migrations/*add_settings_to_users_table.php
tests/Feature/User/UserSettingsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-028 — Add User Display Helpers

**Area:** User / UI Support  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-028-add-user-display-helpers`  
**Base branch:** `develop`  
**Depends on:** CONV-027

### Goal

Добавить безопасные helpers для отображения пользователя в header/user dropdown.

### TDD step

Unit tests:

```php
it('returns user display name', function () {
    $user = User::factory()->make([
        'name' => 'Alex Johnson',
        'email' => 'alex@example.com',
    ]);

    expect($user->displayName())->toBe('Alex Johnson');
});

it('returns user initials', function () {
    $user = User::factory()->make([
        'name' => 'Alex Johnson',
    ]);

    expect($user->initials())->toBe('AJ');
});
```

Email fallback test:

```php
it('uses email as display fallback when name is missing', function () {
    $user = User::factory()->make([
        'name' => '',
        'email' => 'alex@example.com',
    ]);

    expect($user->displayName())->toBe('alex@example.com');
});
```

### Implementation

В `User` model добавить:

```php
public function displayName(): string
{
    return trim((string) $this->name) !== ''
        ? $this->name
        : $this->email;
}

public function initials(): string
{
    // simple robust implementation
}
```

`initials()` должен быть устойчив к:

```txt
single-word name
empty name
non-latin name
email fallback
```

Не добавлять avatar upload.

### Acceptance criteria

- `displayName()` exists.
- `initials()` exists.
- Name used when available.
- Email fallback works.
- Initials generated safely.
- Tests pass.

### Definition of Done

- Тесты написаны.
- Helpers добавлены.
- Tests pass.
- Коммит: `CONV-028: Add user display helpers`

### Files likely touched

```txt
app/Models/User.php
tests/Unit/User/UserDisplayHelpersTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-029 — Wire User Dropdown To Auth User

**Area:** Header / UI / Auth  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-029-wire-user-dropdown-to-auth-user`  
**Base branch:** `develop`  
**Depends on:** CONV-028

### Goal

Подключить user dropdown к реальному authenticated user.

### TDD step

Feature render test:

```php
it('shows authenticated user data in dashboard header', function () {
    $user = User::factory()->create([
        'name' => 'Alex Johnson',
        'email' => 'alex@example.com',
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Alex Johnson')
        ->assertSee('alex@example.com');
});
```

Plan badge test:

```php
it('shows user plan in dashboard header dropdown', function () {
    $user = User::factory()->create([
        'plan' => Plan::Free,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Free');
});
```

### Implementation

Обновить header/user dropdown component/view:

```txt
- use auth()->user();
- show displayName();
- show email;
- show initials/avatar placeholder;
- show plan badge;
- show menu links placeholders: Dashboard, Settings, Billing.
```

Не показывать credits balance ещё.  
Не показывать storage usage ещё, если нет сервиса.

Если Phase 01 создала статический dropdown, заменить mock data на real data.

### Acceptance criteria

- Dashboard header shows auth user name.
- Dashboard header shows auth user email.
- Plan badge shows Free/Pro/Max label.
- Guest never sees user dropdown because dashboard is protected.
- No credits/storage fake numbers.
- Tests pass.

### Definition of Done

- Тесты написаны.
- Dropdown uses auth user.
- Mock user data removed.
- Tests/build pass.
- Коммит: `CONV-029: Wire user dropdown to auth user`

### Files likely touched

```txt
resources/views/layouts/app.blade.php
resources/views/components/user-dropdown.blade.php
resources/views/dashboard.blade.php
tests/Feature/Auth/UserDropdownTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-030 — Add Logout Action To User Dropdown

**Area:** Header / Auth / UI  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-030-add-logout-action-to-user-dropdown`  
**Base branch:** `develop`  
**Depends on:** CONV-029

### Goal

Добавить logout action в user dropdown.

### TDD step

Feature test:

```php
it('allows authenticated user to logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});
```

Render test:

```php
it('renders logout control in user dropdown', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Log out');
});
```

### Implementation

В dropdown добавить logout form:

```blade
<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit">Log out</button>
</form>
```

Не делать logout через GET.

### Acceptance criteria

- Logout visible in user dropdown.
- Logout uses POST.
- CSRF token included.
- User becomes guest after logout.
- Redirect behavior matches Breeze default.
- Tests pass.

### Definition of Done

- Тесты написаны.
- Logout form добавлен.
- GET logout не используется.
- Tests/build pass.
- Коммит: `CONV-030: Add logout action to user dropdown`

### Files likely touched

```txt
resources/views/components/user-dropdown.blade.php
resources/views/layouts/navigation.blade.php
tests/Feature/Auth/LogoutTest.php
tests/Feature/Auth/UserDropdownTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 02 Completion Criteria

Phase 02 завершена, когда:

```txt
- CONV-021–CONV-030 выполнены;
- Laravel Breeze Blade installed;
- login page renders;
- register page renders;
- user can register;
- user can login;
- user can logout;
- /dashboard is auth-protected;
- guest redirects from /dashboard to /login;
- authenticated user can access /dashboard;
- users.plan exists;
- new users default to free plan;
- Plan enum exists;
- users.settings exists;
- settings casts to array;
- user display helpers exist;
- user dropdown shows real auth user data;
- user dropdown does not show fake credits/storage;
- logout exists in user dropdown;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 02

Без отдельной задачи нельзя:

```txt
- устанавливать Laravel Cashier;
- добавлять Stripe keys;
- добавлять BillingPaymentService;
- создавать CreditLedger;
- создавать FeatureAccessService;
- создавать ConversionCostEstimator;
- добавлять credits balance;
- добавлять storage usage calculation;
- создавать upload form;
- создавать FileRecord model;
- создавать ConversionJob model;
- создавать ConverterRegistry;
- создавать API keys;
- добавлять /api/v1 routes;
- добавлять OAuth/social login;
- добавлять teams/workspaces;
- добавлять admin roles;
- добавлять 2FA;
- делать profile edit page;
- делать полноценный billing page.
```

---

# 12. Recommended Execution Order

```txt
CONV-021 Install Laravel Breeze Blade
CONV-022 Configure Auth Views Baseline
CONV-023 Test Guest Cannot Access Dashboard
CONV-024 Protect Dashboard Route
CONV-025 Test Authenticated User Can Access Dashboard
CONV-026 Add User Plan Field
CONV-027 Add User Settings Field
CONV-028 Add User Display Helpers
CONV-029 Wire User Dropdown To Auth User
CONV-030 Add Logout Action To User Dropdown
```

---

# 13. Release

После завершения Phase 02:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh

git checkout -b release/v0.1.2-phase02-auth-foundation
git push -u origin release/v0.1.2-phase02-auth-foundation
```

После этого сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.2-phase02-auth-foundation -m "File Converter Phase 02 Auth Foundation"
git push origin v0.1.2-phase02-auth-foundation
```
