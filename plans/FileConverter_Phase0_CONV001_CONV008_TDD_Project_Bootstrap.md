# File Converter — Phase 0 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 0 — Project Bootstrap & Quality Gate**  
Диапазон задач: **CONV-001 → CONV-008**  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 0 соответствует стартовому блоку:

```txt
Phase 0 — Project Bootstrap & Quality Gate
```

Правильный диапазон Phase 0:

```txt
CONV-001 — Bootstrap Laravel Project
CONV-002 — Configure Environment Files
CONV-003 — Configure Test Database
CONV-004 — Install Livewire 3
CONV-005 — Install Tailwind CSS And Alpine.js
CONV-006 — Configure Pest Test Suite
CONV-007 — Add Code Quality Commands
CONV-008 — Add Base Dashboard Route Smoke Test
```

Эта фаза не делает бизнес-логику конвертации.  
Её задача — подготовить чистую техническую основу, на которой следующие фазы смогут развиваться через TDD.

---

# 2. Цель Phase 0

Phase 0 создаёт минимальный Laravel-проект с Livewire, Tailwind, Alpine, тестами и базовыми quality gates.

После Phase 0 должно быть готово:

```txt
- Laravel application;
- local environment setup;
- test database setup;
- Livewire 3 installed;
- Tailwind CSS installed;
- Alpine.js installed;
- Pest test suite configured;
- basic code quality commands;
- protected future path for /dashboard;
- smoke test proving the application boots.
```

Это foundation-фаза.  
Никаких конвертеров, файлов, биллинга, Cashier, API, очередей или UI-компонентов здесь быть не должно.

---

# 3. Scope Phase 0

## Входит

```txt
- Laravel project bootstrap;
- .env.example baseline;
- SQLite/PostgreSQL test database configuration;
- Livewire 3 installation;
- Tailwind CSS installation;
- Alpine.js setup;
- Pest/PHPUnit setup;
- base Feature/Unit test structure;
- composer scripts for test/lint/format;
- base /dashboard route placeholder;
- dashboard smoke test.
```

## Не входит

```txt
- auth;
- dashboard UI;
- file upload;
- converter registry;
- conversion jobs;
- image conversion;
- billing;
- credits;
- Laravel Cashier;
- API;
- OpenAPI docs;
- storage abstraction;
- queues;
- user dropdown;
- Blade design components;
- pricing page.
```

Auth будет в отдельной фазе.  
UI foundation будет в следующей фазе.  
Конвертационное ядро будет позже.

---

# 4. Critical Decisions

## 4.1. Laravel + Livewire + Blade is the default stack

Проект строится на:

```txt
Laravel
Livewire 3
Blade
Alpine.js
Tailwind CSS
Pest or PHPUnit
```

React/Vue/Inertia не добавлять.

Причина простая: текущий продукт — это формы, stepper, таблицы, dropdown, upload, polling, billing pages. Для этого Livewire достаточно.

## 4.2. TDD is mandatory from Phase 0

Даже bootstrap-фаза должна иметь smoke tests.

Минимум:

```txt
- application boots;
- dashboard placeholder route returns OK for now or redirects according to current auth decision;
- Livewire test component renders;
- test database works.
```

## 4.3. No business logic in Phase 0

Нельзя начинать с:

```txt
ConverterService
FileUploadAction
BillingService
CreditLedger
API controllers
```

Это premature. Если добавить их сейчас, появится не протестированная архитектурная каша ещё до foundation.

## 4.4. Composer scripts are part of quality gate

В проекте сразу должны быть команды:

```txt
composer test
composer lint
composer format
```

Если их нет, разработчики и агенты начнут запускать разные команды и ломать единый workflow.

## 4.5. Dashboard route is placeholder only

`/dashboard` нужен как будущая точка входа, но в Phase 0 он не должен содержать реальный UI.

Правильно:

```txt
/dashboard renders placeholder page
```

Неправильно:

```txt
/dashboard already contains upload form
/dashboard already contains converter state machine
/dashboard already contains user dropdown
```

---

# 5. Architecture Rules

## 5.1. Keep bootstrap minimal

В Phase 0 нельзя создавать доменные директории заранее “на всякий случай”, если они не используются.

Неправильно:

```txt
app/Converters
app/Billing
app/Credits
app/Api
```

Правильно:

```txt
app/Livewire
resources/views
tests/Feature
tests/Unit
```

## 5.2. No frontend framework beyond Alpine

Не добавлять:

```txt
React
Vue
Inertia
Next.js
Nuxt
```

## 5.3. No package sprawl

В Phase 0 ставятся только foundational dependencies:

```txt
Livewire
Tailwind
Alpine
Pest/PHPUnit
Pint
```

Не ставить:

```txt
Cashier
Stripe SDK manually
Imagick wrappers
FFmpeg wrappers
OpenAPI generators
Scribe/Scramble
Spatie packages
```

Иначе foundation-фаза станет мусорной корзиной.

## 5.4. Tests must run on clean checkout

После Phase 0 новый разработчик должен сделать:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan test
npm run build
```

и получить рабочий проект.

---

# 6. GitFlow для Phase 0

## Base branch

Все задачи Phase 0 создаются от:

```txt
develop
```

Если `develop` ещё нет:

```bash
git checkout -b develop
git push -u origin develop
```

## Branch format

```txt
feature/CONV-001-bootstrap-laravel-project
feature/CONV-004-install-livewire-3
feature/CONV-007-add-code-quality-commands
```

## Commit format

```txt
CONV-001: Bootstrap Laravel project
CONV-004: Install Livewire 3
CONV-007: Add code quality commands
```

## Release branch

После выполнения `CONV-001`–`CONV-008`:

```txt
release/v0.1.0-phase0-project-bootstrap
```

## Tag

После merge release branch в `main`:

```txt
v0.1.0-phase0-project-bootstrap
```

---

# 7. TDD Rules for Phase 0

## Для bootstrap

Тестировать:

```txt
- application boots;
- home or dashboard placeholder route responds correctly;
- test database is usable.
```

## Для Livewire

Тестировать:

```txt
- test Livewire component renders;
- Livewire testing helpers work.
```

## Для dashboard placeholder

Тестировать:

```txt
- /dashboard route exists;
- route returns OK or expected redirect depending on auth policy;
- page contains placeholder text.
```

## Для quality commands

Проверять вручную:

```txt
composer test
composer lint
npm run build
```

Если задача не имеет прямого теста, в ней явно пишется:

```txt
No direct test — infrastructure/package setup.
```

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Bootstrap / Infrastructure / Tests / Frontend
Type: Setup / Test / Feature / Config
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
- composer lint проходит, если уже доступен
- npm run build проходит, если frontend уже установлен
- Нет функциональности вне scope задачи
- Коммит содержит ID задачи

Files likely touched:
- path/to/file
```

---

# 9. Phase 0 Atomic Tasks

---

## CONV-001 — Bootstrap Laravel Project

**Area:** Bootstrap  
**Type:** Setup  
**Priority:** P0  
**Branch:** `feature/CONV-001-bootstrap-laravel-project`  
**Base branch:** `develop`  
**Depends on:** none

### Goal

Создать чистый Laravel-проект как основу для File Converter MVP.

### TDD step

No direct test — проект ещё создаётся.

После создания проекта должен проходить стандартный тест Laravel:

```bash
php artisan test
```

### Implementation

Создать Laravel-проект.

Если репозиторий пустой:

```bash
composer create-project laravel/laravel .
```

Если проект создаётся в новой директории:

```bash
composer create-project laravel/laravel file-converter
```

Проверить:

```bash
php artisan about
php artisan test
```

Создать базовый `.gitignore`, если его нет.

Убедиться, что в репозитории нет мусора:

```txt
/vendor
/node_modules
.env
/storage/*.key
```

### Acceptance criteria

- Laravel application создан.
- `php artisan about` работает.
- `php artisan test` проходит.
- `.env` не закоммичен.
- `.env.example` существует.
- Нет лишних пакетов сверх стандартного Laravel.
- Первый clean commit создан.

### Definition of Done

- Laravel project создан.
- Стандартные тесты проходят.
- Git status clean.
- Коммит: `CONV-001: Bootstrap Laravel project`

### Files likely touched

```txt
composer.json
composer.lock
artisan
app/*
bootstrap/*
config/*
database/*
public/*
resources/*
routes/*
tests/*
.env.example
.gitignore
```

После этого сделай MR в `develop`. Merge разрешён только если `php artisan test` проходит.

---

## CONV-002 — Configure Environment Files

**Area:** Infrastructure / Config  
**Type:** Config  
**Priority:** P0  
**Branch:** `feature/CONV-002-configure-environment-files`  
**Base branch:** `develop`  
**Depends on:** CONV-001

### Goal

Подготовить `.env.example` под будущий File Converter MVP без подключения лишних сервисов.

### TDD step

No direct test — environment template configuration.

Но после изменения должен проходить:

```bash
php artisan config:clear
php artisan test
```

### Implementation

Обновить `.env.example`.

Минимально зафиксировать:

```env
APP_NAME="File Converter"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite

QUEUE_CONNECTION=database

FILESYSTEM_DISK=local

CACHE_STORE=file
SESSION_DRIVER=file
```

Не добавлять Stripe/Cashier env в этой фазе.  
Не добавлять S3 env в этой фазе.  
Не добавлять API keys в этой фазе.

Если хочется оставить future placeholders, максимум комментариями в README позже, но не в `.env.example`.

### Acceptance criteria

- `.env.example` отражает MVP local setup.
- Default DB можно использовать локально.
- Queue connection задан как `database`, но queue tables будут добавлены позже.
- Нет Stripe/S3/API переменных.
- `php artisan config:clear` проходит.
- `php artisan test` проходит.

### Definition of Done

- `.env.example` обновлён.
- Лишние env-переменные не добавлены.
- Тесты проходят.
- Коммит: `CONV-002: Configure environment files`

### Files likely touched

```txt
.env.example
config/app.php
config/database.php
config/queue.php
config/filesystems.php
```

После этого сделай MR в `develop`. Merge разрешён только если `php artisan test` проходит.

---

## CONV-003 — Configure Test Database

**Area:** Tests / Infrastructure  
**Type:** Config  
**Priority:** P0  
**Branch:** `feature/CONV-003-configure-test-database`  
**Base branch:** `develop`  
**Depends on:** CONV-002

### Goal

Настроить тестовую базу так, чтобы все будущие feature/database tests работали стабильно и изолированно.

### TDD step

Добавить тест, который проверяет, что database layer работает.

```php
it('can use the test database', function () {
    expect(DB::connection()->getDatabaseName())->not->toBeEmpty();
});
```

Если используется SQLite in-memory, тест должен подтвердить, что connection доступен.

### Implementation

Настроить `phpunit.xml` или `Pest.php`.

Рекомендуемый MVP-вариант:

```xml
<env name="APP_ENV" value="testing"/>
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="CACHE_STORE" value="array"/>
<env name="SESSION_DRIVER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
```

Если планируется PostgreSQL с самого начала, можно использовать отдельную test database, но для MVP быстрее SQLite.

### Acceptance criteria

- Тестовая БД изолирована.
- Tests не используют local/dev database.
- `php artisan test` проходит.
- Database smoke test существует.
- Queue в тестах sync.
- Cache/session в тестах array.

### Definition of Done

- Test DB настроена.
- Smoke test добавлен.
- Тест проходит.
- Коммит: `CONV-003: Configure test database`

### Files likely touched

```txt
phpunit.xml
tests/Pest.php
tests/Feature/TestDatabaseSmokeTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `php artisan test` проходит.

---

## CONV-004 — Install Livewire 3

**Area:** Frontend / Livewire  
**Type:** Setup  
**Priority:** P0  
**Branch:** `feature/CONV-004-install-livewire-3`  
**Base branch:** `develop`  
**Depends on:** CONV-003

### Goal

Установить Livewire 3 и подтвердить, что Livewire-компоненты можно тестировать.

### TDD step

Сначала добавить ожидаемый тест на тестовый Livewire-компонент.

```php
use Livewire\Livewire;

it('renders test livewire component', function () {
    Livewire::test(\App\Livewire\TestPing::class)
        ->assertSee('Livewire is working');
});
```

Тест должен упасть до установки компонента/Livewire.

### Implementation

Установить Livewire:

```bash
composer require livewire/livewire
```

Создать временный тестовый компонент:

```bash
php artisan make:livewire TestPing
```

Компонент:

```php
final class TestPing extends Component
{
    public function render()
    {
        return view('livewire.test-ping');
    }
}
```

View:

```blade
<div>
    Livewire is working
</div>
```

Не подключать бизнес-логику.

### Acceptance criteria

- Livewire 3 установлен.
- Test component exists.
- Livewire test passes.
- Component renders Blade view.
- Нет dashboard/converter logic в компоненте.

### Definition of Done

- Тест написан первым.
- Livewire установлен.
- Test component создан.
- Тест проходит.
- Коммит: `CONV-004: Install Livewire 3`

### Files likely touched

```txt
composer.json
composer.lock
app/Livewire/TestPing.php
resources/views/livewire/test-ping.blade.php
tests/Feature/Livewire/LivewireSmokeTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `php artisan test` проходит.

---

## CONV-005 — Install Tailwind CSS And Alpine.js

**Area:** Frontend / Styling  
**Type:** Setup  
**Priority:** P0  
**Branch:** `feature/CONV-005-install-tailwind-css-and-alpine-js`  
**Base branch:** `develop`  
**Depends on:** CONV-004

### Goal

Подключить Tailwind CSS и Alpine.js как базу для будущего интерфейса.

### TDD step

No direct backend test — frontend build setup.

Но после установки должен проходить:

```bash
npm run build
php artisan test
```

### Implementation

Установить frontend dependencies, если они ещё не стоят:

```bash
npm install
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
npm install alpinejs
```

Настроить Tailwind content paths:

```js
content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './app/Livewire/**/*.php',
]
```

Подключить Alpine в `resources/js/app.js`:

```js
import Alpine from 'alpinejs'

window.Alpine = Alpine

Alpine.start()
```

Проверить `resources/css/app.css`:

```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

### Acceptance criteria

- Tailwind установлен.
- Alpine установлен.
- Vite build проходит.
- Tailwind content paths включают Blade и Livewire.
- Alpine доступен на странице.
- `npm run build` проходит.
- `php artisan test` проходит.

### Definition of Done

- Tailwind установлен.
- Alpine установлен.
- Build проходит.
- Тесты проходят.
- Коммит: `CONV-005: Install Tailwind CSS and Alpine.js`

### Files likely touched

```txt
package.json
package-lock.json
tailwind.config.js
postcss.config.js
vite.config.js
resources/css/app.css
resources/js/app.js
resources/views/layouts/app.blade.php
```

После этого сделай MR в `develop`. Merge разрешён только если `npm run build` и `php artisan test` проходят.

---

## CONV-006 — Configure Pest Test Suite

**Area:** Tests  
**Type:** Setup  
**Priority:** P0  
**Branch:** `feature/CONV-006-configure-pest-test-suite`  
**Base branch:** `develop`  
**Depends on:** CONV-005

### Goal

Зафиксировать Pest как основной test runner и подготовить структуру тестов под TDD.

### TDD step

No direct test — test framework configuration.

После настройки должен проходить:

```bash
php artisan test
```

и примерные тесты должны быть в структуре:

```txt
tests/Feature
tests/Unit
```

### Implementation

Если Pest ещё не установлен:

```bash
composer require pestphp/pest --dev --with-all-dependencies
php artisan pest:install
```

Добавить базовый `tests/Pest.php`.

Настроить uses:

```php
uses(Tests\TestCase::class)->in('Feature');
uses(Tests\TestCase::class)->in('Unit');
```

Если нужны RefreshDatabase для feature tests, не включать глобально бездумно. Лучше использовать точечно в тестах, где нужна БД.

Добавить пример unit test:

```php
it('is true', function () {
    expect(true)->toBeTrue();
});
```

Если Laravel уже создал PHPUnit tests, не обязательно удалять, но не плодить два разных стиля в новых задачах.

### Acceptance criteria

- Pest установлен.
- `php artisan test` проходит.
- Есть `tests/Pest.php`.
- Есть пример unit или feature test.
- Новые задачи будут использовать Pest-style tests.

### Definition of Done

- Pest configured.
- Tests pass.
- Коммит: `CONV-006: Configure Pest test suite`

### Files likely touched

```txt
composer.json
composer.lock
tests/Pest.php
tests/Unit/ExampleTest.php
tests/Feature/ExampleTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `php artisan test` проходит.

---

## CONV-007 — Add Code Quality Commands

**Area:** Tooling / Quality  
**Type:** Config  
**Priority:** P0  
**Branch:** `feature/CONV-007-add-code-quality-commands`  
**Base branch:** `develop`  
**Depends on:** CONV-006

### Goal

Добавить единые команды для тестов, форматирования и проверки стиля.

### TDD step

No direct test — composer scripts/tooling configuration.

Проверка:

```bash
composer test
composer lint
composer format
```

### Implementation

Убедиться, что Laravel Pint установлен. Если нет:

```bash
composer require laravel/pint --dev
```

Добавить в `composer.json` scripts:

```json
{
  "scripts": {
    "test": "php artisan test",
    "lint": "pint --test",
    "format": "pint"
  }
}
```

Опционально добавить `pint.json`:

```json
{
  "preset": "laravel"
}
```

Не добавлять PHPStan/Psalm в этой фазе, если не готов поддерживать их правила. Это отдельная задача позже.

### Acceptance criteria

- `composer test` работает.
- `composer lint` работает.
- `composer format` работает.
- Pint preset зафиксирован.
- Нет PHPStan/Psalm в этой фазе.
- `npm run build` всё ещё проходит.

### Definition of Done

- Composer scripts добавлены.
- Pint настроен.
- Команды проходят.
- Коммит: `CONV-007: Add code quality commands`

### Files likely touched

```txt
composer.json
composer.lock
pint.json
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-008 — Add Base Dashboard Route Smoke Test

**Area:** Routes / Tests  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-008-add-base-dashboard-route-smoke-test`  
**Base branch:** `develop`  
**Depends on:** CONV-007

### Goal

Добавить placeholder `/dashboard`, чтобы будущие фазы имели стабильную точку входа.

### TDD step

Feature test:

```php
it('renders dashboard placeholder page', function () {
    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('File Converter Dashboard');
});
```

На Phase 0 auth ещё не подключён, поэтому route пока публичный.  
В auth-фазе этот тест будет изменён: guest должен redirect to login.

Тест должен упасть до добавления route/view.

### Implementation

Добавить route:

```php
Route::view('/dashboard', 'dashboard')->name('dashboard');
```

Добавить view:

```blade
<x-app-layout>
    <h1>File Converter Dashboard</h1>
</x-app-layout>
```

Если layout ещё не как Blade component, использовать обычный layout:

```blade
@extends('layouts.app')

@section('content')
    <h1>File Converter Dashboard</h1>
@endsection
```

Не добавлять upload form.  
Не добавлять converter UI.  
Не добавлять auth middleware.

### Acceptance criteria

- `/dashboard` exists.
- Page returns 200.
- Page contains `File Converter Dashboard`.
- No upload UI yet.
- No business logic.
- Test passes.

### Definition of Done

- Тест написан первым.
- Route добавлен.
- View добавлена.
- Test passes.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.
- Коммит: `CONV-008: Add base dashboard route smoke test`

### Files likely touched

```txt
routes/web.php
resources/views/dashboard.blade.php
tests/Feature/DashboardRouteTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 0 Completion Criteria

Phase 0 завершена, когда:

```txt
- CONV-001–CONV-008 выполнены;
- Laravel application boots;
- .env.example configured for local MVP;
- test database configured;
- Livewire 3 installed;
- Tailwind CSS installed;
- Alpine.js installed;
- Pest test suite configured;
- composer test works;
- composer lint works;
- composer format works;
- npm run build works;
- /dashboard placeholder route exists;
- dashboard smoke test passes;
- no converter logic added;
- no billing logic added;
- no API logic added;
- no Cashier installed;
- no auth installed yet;
- no upload functionality added yet.
```

---

# 11. Что нельзя делать в Phase 0

Без отдельной задачи нельзя:

```txt
- устанавливать Laravel Breeze;
- устанавливать Laravel Cashier;
- создавать ConverterRegistry;
- создавать File model;
- создавать ConversionJob model;
- создавать BillingService;
- создавать CreditLedger;
- создавать API routes;
- создавать OpenAPI docs;
- создавать upload form;
- создавать dashboard converter component;
- добавлять Stripe env;
- добавлять S3 env;
- добавлять queues migrations;
- добавлять image processing packages;
- добавлять FFmpeg/Imagick wrappers;
- добавлять React/Vue/Inertia;
- делать полноценный dashboard UI.
```

---

# 12. Recommended Execution Order

```txt
CONV-001 Bootstrap Laravel Project
CONV-002 Configure Environment Files
CONV-003 Configure Test Database
CONV-004 Install Livewire 3
CONV-005 Install Tailwind CSS And Alpine.js
CONV-006 Configure Pest Test Suite
CONV-007 Add Code Quality Commands
CONV-008 Add Base Dashboard Route Smoke Test
```

---

# 13. Release

После завершения Phase 0:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build

git checkout -b release/v0.1.0-phase0-project-bootstrap
git push -u origin release/v0.1.0-phase0-project-bootstrap
```

После этого сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.0-phase0-project-bootstrap -m "File Converter Phase 0 project bootstrap"
git push origin v0.1.0-phase0-project-bootstrap
```
