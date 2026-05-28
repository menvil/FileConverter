# File Converter — Phase 26 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 26 — Release Preparation**  
Диапазон задач: **CONV-415 → CONV-431**  
Основа нумерации: Phase 25 завершилась на `CONV-414`, поэтому Phase 26 начинается с `CONV-415`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 26 соответствует финальному MVP-блоку:

```txt
Phase 26 — Release Preparation
```

Правильный диапазон Phase 26:

```txt
CONV-415 — Audit MVP Scope Completion
CONV-416 — Freeze MVP Supported Conversions Matrix
CONV-417 — Update Environment Example For Release
CONV-418 — Add Production Configuration Checklist
CONV-419 — Add README Installation Guide
CONV-420 — Add Local Development Guide
CONV-421 — Add Queue Worker Documentation
CONV-422 — Add Scheduler Documentation
CONV-423 — Add Storage And Permissions Documentation
CONV-424 — Add Stripe Webhook Setup Documentation
CONV-425 — Add Demo User Seeder
CONV-426 — Add Demo Conversion Fixtures
CONV-427 — Add Fresh Migration Seed Check
CONV-428 — Add Release Smoke Test Checklist
CONV-429 — Run Full Release Quality Gate
CONV-430 — Create MVP Release Branch
CONV-431 — Tag MVP Release
```

Phase 26 не добавляет новые продуктовые возможности.  
Она подготавливает MVP к первому стабильному релизу: документация, env, seed data, deployment checklist, финальная проверка, release branch и tag.

Главное правило:

```txt
Release preparation means making the existing MVP reproducible, deployable and verifiable.
Release preparation does not mean adding new product scope.
```

---

# 2. Цель Phase 26

Phase 26 завершает MVP и делает его пригодным для передачи разработчику, агенту, тестировщику или деплоя.

После Phase 26 должно быть готово:

```txt
- MVP scope checklist;
- frozen supported conversions matrix;
- release-ready .env.example;
- production configuration checklist;
- README setup guide;
- local development guide;
- queue worker documentation;
- scheduler documentation;
- storage/permissions documentation;
- Stripe webhook setup documentation;
- demo user seeder;
- demo conversion fixtures;
- fresh migration + seed check;
- release smoke test checklist;
- full release quality gate execution;
- release branch;
- MVP tag.
```

Фаза закрывает не кодовую разработку продукта, а операционную готовность.

---

# 3. Scope Phase 26

## Входит

```txt
- audit текущего MVP scope;
- фиксация поддерживаемых конверсий;
- актуализация .env.example;
- README для установки;
- local development guide;
- deployment checklist;
- queue worker instructions;
- scheduler instructions;
- storage permissions instructions;
- Stripe webhook instructions;
- demo seed data;
- demo fixtures для ручной проверки;
- migrate:fresh --seed check;
- release smoke checklist;
- финальный composer test / composer lint / npm run build;
- release branch;
- git tag.
```

## Не входит

```txt
- новые конвертеры;
- OCR;
- batch conversion;
- video/audio conversion;
- WebSockets;
- API webhooks;
- admin dashboard;
- billing model changes;
- Spike integration;
- refactor credit ledger;
- refactor converter drivers;
- redesign dashboard;
- публичный landing page rewrite;
- mobile redesign;
- Sentry/Bugsnag/OpenTelemetry;
- Docker production setup, если не было отдельного решения;
- Kubernetes/Forge/Vapor-specific deployment automation.
```

Если в Phase 26 обнаруживается критический баг, он исправляется отдельной bugfix-задачей или возвращается в соответствующую фазу.  
Нельзя тихо добавлять новый scope внутри release preparation.

---

# 4. Critical Decisions

## 4.1. MVP scope is frozen

К началу Phase 26 MVP уже должен уметь:

```txt
- register/login;
- grant starter credits;
- upload PNG/JPG;
- choose target JPG/PNG/WEBP/PDF;
- render pair-specific settings;
- estimate credit cost;
- create conversion job;
- process image conversion;
- download result;
- show recent conversions;
- spend credits on success;
- buy subscription through Cashier;
- buy credit packs;
- access API on allowed plans;
- read API docs.
```

Phase 26 только проверяет и документирует это.

## 4.2. Documentation must match implemented behavior

Нельзя писать в README:

```txt
Supports 100+ formats
Supports OCR
Supports MP4 conversion
Supports Desktop App
```

если этого нет в MVP.

Правильно:

```txt
MVP supports PNG/JPG image conversions to JPG/PNG/WEBP/PDF.
```

## 4.3. .env.example must be realistic

`.env.example` должен содержать только реально используемые переменные.

Нельзя оставлять мусорные placeholder-переменные, если код их не использует.

## 4.4. Demo seed data must not be production data

Demo user и demo fixtures нужны только для локальной проверки.

Нельзя:

```txt
- использовать реальные email/password;
- использовать реальные Stripe keys;
- использовать приватные файлы;
- добавлять большие бинарные файлы в git.
```

## 4.5. Release branch is created only after quality gate

Нельзя создавать release branch, если не проходят:

```txt
composer test
composer lint
npm run build
php artisan migrate:fresh --seed
```

---

# 5. Architecture Rules

## 5.1. No new architecture in release phase

В Phase 26 нельзя создавать новые слои:

```txt
app/Domain
app/Modules
app/Admin
app/Observers
```

если они не были нужны раньше.

## 5.2. Docs must be executable

README и guides должны содержать команды, которые реально можно выполнить:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan queue:work
```

Нельзя писать абстрактно:

```txt
Configure everything and run the project.
```

## 5.3. Release checks must be repeatable

Release checklist должен быть повторяемым другим человеком без знания переписки.

## 5.4. Seeds must be deterministic

Demo seed должен создавать одинаковые сущности:

```txt
- demo user;
- known password;
- credits balance;
- optional sample conversion jobs;
- optional sample API key disabled или generated only locally.
```

## 5.5. Stripe setup must be separated from local-only setup

README должен разделять:

```txt
- local development without Stripe webhooks;
- local Stripe testing with Stripe CLI;
- production webhook setup.
```

---

# 6. GitFlow для Phase 26

## Base branch

Все задачи Phase 26 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-415-audit-mvp-scope-completion
feature/CONV-419-add-readme-installation-guide
feature/CONV-429-run-full-release-quality-gate
```

## Commit format

```txt
CONV-415: Audit MVP scope completion
CONV-419: Add README installation guide
CONV-429: Run full release quality gate
```

## Release branch

После выполнения `CONV-415`–`CONV-429`:

```txt
release/v0.1.0-mvp
```

## Tag

После merge release branch в `main`:

```txt
v0.1.0-mvp
```

---

# 7. TDD Rules for Phase 26

Phase 26 содержит много документационных задач, поэтому не каждая задача имеет прямой unit test.

## Для docs/config

Проверять:

```txt
- файл существует;
- команды из документации актуальны;
- переменные .env.example соответствуют коду;
- нет заявленных несуществующих возможностей.
```

## Для seed data

Тестировать:

```txt
- demo user создаётся;
- demo user получает credits;
- seed можно запускать повторно;
- migrate:fresh --seed проходит.
```

## Для release quality gate

Проверять командами:

```bash
composer test
composer lint
npm run build
php artisan migrate:fresh --seed
```

## Для release branch/tag

No direct test — Git release operation.  
Но перед задачами `CONV-430` и `CONV-431` должен быть выполнен `CONV-429`.

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Release / Docs / Config / Seed / QA / GitFlow
Type: Documentation / Config / Test / Release
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
- Документация соответствует реальному коду
- composer test проходит
- composer lint проходит
- npm run build проходит
- Нет функциональности вне scope задачи
- Коммит содержит ID задачи

Files likely touched:
- path/to/file
```

---

# 9. Phase 26 Atomic Tasks

---

## CONV-415 — Audit MVP Scope Completion

**Area:** Release / QA  
**Type:** Documentation / Audit  
**Priority:** P0  
**Branch:** `feature/CONV-415-audit-mvp-scope-completion`  
**Base branch:** `develop`  
**Depends on:** CONV-414

### Goal

Создать документ с проверкой, что фактический MVP соответствует заявленному scope.

### TDD step

No direct test — release audit documentation.

Проверка выполняется вручную через список acceptance criteria и финальные команды.

### Implementation

Создать документ:

```txt
docs/release/mvp-scope-audit.md
```

Зафиксировать sections:

```txt
- Authentication
- Upload flow
- Target format selection
- Dynamic settings
- Image conversion drivers
- Download
- Recent conversions
- Credits
- Cashier subscriptions
- Credit packs
- API
- API documentation
- Cleanup/retention
- Rate limiting
- Known gaps
```

Для каждого пункта указать:

```txt
Implemented: yes/no
Related task IDs
Manual verification steps
Known limitations
```

### Acceptance criteria

- Документ `docs/release/mvp-scope-audit.md` существует.
- В документе перечислен весь MVP scope.
- У каждого пункта есть статус implemented yes/no.
- Known gaps явно перечислены.
- Документ не заявляет несуществующие функции.

### Definition of Done

- Audit document создан.
- Known gaps не скрыты.
- `composer test` проходит.
- Коммит: `CONV-415: Audit MVP scope completion`

### Files likely touched

```txt
docs/release/mvp-scope-audit.md
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-416 — Freeze MVP Supported Conversions Matrix

**Area:** Release / Documentation / Converters  
**Type:** Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-416-freeze-mvp-supported-conversions-matrix`  
**Base branch:** `develop`  
**Depends on:** CONV-415

### Goal

Зафиксировать официальную матрицу поддерживаемых MVP-конверсий.

### TDD step

No direct test — documentation based on implemented `ConverterRegistry`.

Если есть простой способ, добавить test/assertion, что documented conversions совпадают с registry snapshot. Если это создаёт лишнюю хрупкость, ограничиться ручной проверкой.

### Implementation

Создать:

```txt
docs/release/supported-conversions.md
```

Содержимое:

```txt
PNG → JPG
PNG → WEBP
PNG → PDF
JPG → PNG
JPG → WEBP
JPG → PDF
```

Для каждой пары указать:

```txt
- source format;
- target format;
- converter key;
- available options;
- credit cost;
- limitations.
```

Не писать `100+ formats`.

### Acceptance criteria

- Матрица существует.
- Матрица совпадает с MVP `ConverterRegistry`.
- Для каждой пары указаны настройки.
- Для каждой пары указан credit cost.
- Неподдерживаемые форматы не перечислены.

### Definition of Done

- Supported conversions matrix создана.
- Документ честно отражает MVP.
- Тесты проходят.
- Коммит: `CONV-416: Freeze MVP supported conversions matrix`

### Files likely touched

```txt
docs/release/supported-conversions.md
app/Converters/* optional only if typo fixes are needed
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-417 — Update Environment Example For Release

**Area:** Config / Release  
**Type:** Config  
**Priority:** P0  
**Branch:** `feature/CONV-417-update-environment-example-for-release`  
**Base branch:** `develop`  
**Depends on:** CONV-416

### Goal

Обновить `.env.example` под фактический MVP с Cashier, queue, scheduler, storage и app settings.

### TDD step

No direct test — environment template configuration.

Проверка:

```bash
php artisan config:clear
php artisan test
```

### Implementation

Обновить `.env.example`.

Должны быть секции:

```txt
Application
Database
Cache/Session
Queue
Storage
Mail optional
Stripe/Cashier
File Converter limits
```

Пример обязательных переменных:

```env
APP_NAME="File Converter"
APP_URL=http://localhost

DB_CONNECTION=sqlite

QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
CASHIER_CURRENCY=eur

CONVERTER_DEFAULT_RETENTION_DAYS=1
```

Не добавлять переменные, которые код не читает.

### Acceptance criteria

- `.env.example` содержит все реально нужные MVP переменные.
- Stripe/Cashier переменные добавлены.
- Queue/storage переменные добавлены.
- Нет неиспользуемых fantasy variables.
- `php artisan config:clear` проходит.
- `composer test` проходит.

### Definition of Done

- `.env.example` обновлён.
- Переменные соответствуют коду.
- Тесты проходят.
- Коммит: `CONV-417: Update environment example for release`

### Files likely touched

```txt
.env.example
config/cashier.php
config/services.php
config/queue.php
config/filesystems.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-418 — Add Production Configuration Checklist

**Area:** Release / Documentation / Deployment  
**Type:** Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-418-add-production-configuration-checklist`  
**Base branch:** `develop`  
**Depends on:** CONV-417

### Goal

Создать production checklist для настройки окружения перед деплоем.

### TDD step

No direct test — deployment documentation.

### Implementation

Создать:

```txt
docs/deployment/production-checklist.md
```

Включить разделы:

```txt
- PHP version and extensions;
- web server document root;
- APP_ENV/APP_DEBUG;
- database;
- queue worker;
- scheduler;
- storage symlink;
- file permissions;
- max upload size;
- Stripe keys;
- Stripe webhook secret;
- HTTPS;
- cleanup job;
- backup notes.
```

### Acceptance criteria

- Production checklist создан.
- Checklist содержит env/security пункты.
- Checklist содержит queue/scheduler пункты.
- Checklist содержит upload/storage пункты.
- Checklist не привязан к конкретному хостингу без необходимости.

### Definition of Done

- Deployment checklist создан.
- Документ можно использовать для ручного деплоя.
- Коммит: `CONV-418: Add production configuration checklist`

### Files likely touched

```txt
docs/deployment/production-checklist.md
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-419 — Add README Installation Guide

**Area:** Documentation  
**Type:** Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-419-add-readme-installation-guide`  
**Base branch:** `develop`  
**Depends on:** CONV-418

### Goal

Обновить `README.md`, чтобы новый разработчик мог поднять проект локально.

### TDD step

No direct test — README documentation.

Команды из README должны быть проверены вручную.

### Implementation

Добавить в README:

```txt
- project overview;
- MVP scope;
- requirements;
- installation;
- environment setup;
- migrations/seeding;
- running dev server;
- running queue worker;
- running tests;
- supported conversions;
- billing notes;
- API docs link.
```

Команды:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan queue:work
php artisan serve
```

### Acceptance criteria

- README содержит clear installation steps.
- README указывает реальные MVP supported conversions.
- README не заявляет несуществующие фичи.
- README содержит команды test/lint/build.
- README содержит ссылку на API docs.

### Definition of Done

- README обновлён.
- Команды актуальны.
- `composer test` проходит.
- Коммит: `CONV-419: Add README installation guide`

### Files likely touched

```txt
README.md
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-420 — Add Local Development Guide

**Area:** Documentation / Developer Experience  
**Type:** Documentation  
**Priority:** P1  
**Branch:** `feature/CONV-420-add-local-development-guide`  
**Base branch:** `develop`  
**Depends on:** CONV-419

### Goal

Создать отдельный guide для локальной разработки и TDD workflow.

### TDD step

No direct test — documentation.

### Implementation

Создать:

```txt
docs/development/local-development.md
```

Включить:

```txt
- local setup;
- test database behavior;
- queue sync vs database queue;
- fake billing notes;
- Stripe local testing optional;
- how to run one test file;
- how to create a new task branch;
- TDD workflow.
```

Примеры команд:

```bash
php artisan test --filter=DashboardConverterTest
composer lint
composer format
npm run build
```

### Acceptance criteria

- Local development guide создан.
- Есть команды для TDD.
- Есть правила branch/commit naming.
- Есть описание queue и Stripe local mode.
- Документ не дублирует README полностью.

### Definition of Done

- Guide создан.
- Коммит: `CONV-420: Add local development guide`

### Files likely touched

```txt
docs/development/local-development.md
README.md optional
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-421 — Add Queue Worker Documentation

**Area:** Documentation / Queue  
**Type:** Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-421-add-queue-worker-documentation`  
**Base branch:** `develop`  
**Depends on:** CONV-420

### Goal

Документировать queue worker, потому что conversion jobs не должны выполняться в HTTP-запросе.

### TDD step

No direct test — operational documentation.

### Implementation

Создать или обновить:

```txt
docs/deployment/queue-worker.md
```

Включить:

```txt
- why queue worker is required;
- local command;
- production command;
- Supervisor example;
- restart command;
- failed jobs command;
- retry failed job;
- queue connection notes.
```

Команды:

```bash
php artisan queue:work
php artisan queue:restart
php artisan queue:failed
php artisan queue:retry all
```

### Acceptance criteria

- Queue worker documentation exists.
- Есть local и production instructions.
- Есть Supervisor пример или generic process manager пример.
- Есть failed job commands.
- README ссылается на этот документ.

### Definition of Done

- Queue documentation создана.
- README link добавлен, если нужен.
- Коммит: `CONV-421: Add queue worker documentation`

### Files likely touched

```txt
docs/deployment/queue-worker.md
README.md
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-422 — Add Scheduler Documentation

**Area:** Documentation / Scheduler  
**Type:** Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-422-add-scheduler-documentation`  
**Base branch:** `develop`  
**Depends on:** CONV-421

### Goal

Документировать Laravel scheduler для cleanup/retention jobs.

### TDD step

No direct test — operational documentation.

### Implementation

Создать:

```txt
docs/deployment/scheduler.md
```

Включить:

```txt
- why scheduler is required;
- local manual command;
- production cron entry;
- cleanup expired files job;
- how to verify scheduler runs;
- logging notes.
```

Команды:

```bash
php artisan schedule:list
php artisan schedule:run
```

Cron:

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

### Acceptance criteria

- Scheduler documentation exists.
- Есть production cron example.
- Есть schedule:list verification.
- README/deployment checklist ссылается на scheduler doc.

### Definition of Done

- Scheduler documentation создана.
- Коммит: `CONV-422: Add scheduler documentation`

### Files likely touched

```txt
docs/deployment/scheduler.md
docs/deployment/production-checklist.md
README.md optional
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-423 — Add Storage And Permissions Documentation

**Area:** Documentation / Storage / Deployment  
**Type:** Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-423-add-storage-and-permissions-documentation`  
**Base branch:** `develop`  
**Depends on:** CONV-422

### Goal

Документировать storage, public symlink, upload limits и permissions.

### TDD step

No direct test — deployment documentation.

### Implementation

Создать:

```txt
docs/deployment/storage-and-permissions.md
```

Включить:

```txt
- local disk setup;
- storage link;
- writable directories;
- upload temp directories;
- max upload size PHP/nginx/apache;
- cleanup job relation;
- backup notes;
- production warning about private source files.
```

Команды:

```bash
php artisan storage:link
chmod -R ug+rwx storage bootstrap/cache
```

Не рекомендовать делать `chmod -R 777`.

### Acceptance criteria

- Storage documentation exists.
- Есть `storage:link` instruction.
- Есть upload size notes.
- Есть permissions notes без `777`.
- Есть предупреждение про private source/result files.

### Definition of Done

- Storage guide создан.
- Production checklist обновлён, если нужно.
- Коммит: `CONV-423: Add storage and permissions documentation`

### Files likely touched

```txt
docs/deployment/storage-and-permissions.md
docs/deployment/production-checklist.md
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-424 — Add Stripe Webhook Setup Documentation

**Area:** Documentation / Billing / Stripe  
**Type:** Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-424-add-stripe-webhook-setup-documentation`  
**Base branch:** `develop`  
**Depends on:** CONV-423

### Goal

Документировать Stripe webhook setup для Cashier subscriptions и credit packs.

### TDD step

No direct test — billing operations documentation.

### Implementation

Создать:

```txt
docs/billing/stripe-webhooks.md
```

Включить:

```txt
- required Stripe env variables;
- local testing with Stripe CLI;
- webhook endpoint path;
- required events;
- idempotency note;
- subscription events;
- checkout session completed for credit packs;
- how to inspect failed webhook;
- security warning about webhook secret.
```

Пример:

```bash
stripe listen --forward-to http://localhost:8000/stripe/webhook
```

Не указывать реальные ключи.

### Acceptance criteria

- Stripe webhook guide создан.
- Есть local Stripe CLI example.
- Есть required events list.
- Есть security warning.
- README или billing page docs ссылаются на guide.

### Definition of Done

- Stripe webhook documentation создана.
- Коммит: `CONV-424: Add Stripe webhook setup documentation`

### Files likely touched

```txt
docs/billing/stripe-webhooks.md
README.md optional
docs/deployment/production-checklist.md
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-425 — Add Demo User Seeder

**Area:** Seed / Developer Experience  
**Type:** Feature / Test  
**Priority:** P0  
**Branch:** `feature/CONV-425-add-demo-user-seeder`  
**Base branch:** `develop`  
**Depends on:** CONV-424

### Goal

Добавить demo user для локальной проверки MVP.

### TDD step

Feature test:

```php
it('seeds demo user with credits', function () {
    $this->seed();

    $user = User::where('email', 'demo@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->creditAccount->balance)->toBeGreaterThan(0);
});
```

Тест должен упасть до реализации demo seed.

### Implementation

Обновить `DatabaseSeeder` или создать отдельный seeder:

```txt
database/seeders/DemoUserSeeder.php
```

Demo credentials:

```txt
email: demo@example.com
password: password
plan: free or pro depending desired demo
credits: starter balance
```

Пароль должен быть явно указан только для local/demo.

### Acceptance criteria

- Demo user создаётся.
- Demo user имеет credit account.
- Demo user имеет credits balance.
- Seeder idempotent: повторный запуск не создаёт дубль.
- Test passes.

### Definition of Done

- Тест написан первым.
- Demo seeder создан.
- Test passes.
- Коммит: `CONV-425: Add demo user seeder`

### Files likely touched

```txt
database/seeders/DemoUserSeeder.php
database/seeders/DatabaseSeeder.php
tests/Feature/Seeders/DemoUserSeederTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-426 — Add Demo Conversion Fixtures

**Area:** Seed / Fixtures / Developer Experience  
**Type:** Feature / Test  
**Priority:** P1  
**Branch:** `feature/CONV-426-add-demo-conversion-fixtures`  
**Base branch:** `develop`  
**Depends on:** CONV-425

### Goal

Добавить безопасные demo fixtures для проверки history/recent conversions без реальных пользовательских файлов.

### TDD step

Feature test:

```php
it('seeds demo conversion history records', function () {
    $this->seed();

    $user = User::where('email', 'demo@example.com')->firstOrFail();

    expect($user->conversionJobs()->count())->toBeGreaterThan(0);
});
```

### Implementation

Создать:

```txt
database/seeders/DemoConversionSeeder.php
```

Варианты:

```txt
- создать fake FileRecord rows без больших бинарников;
- создать completed/failed/processing sample jobs;
- сохранить маленькие fixture images в tests/Fixtures или database/fixtures only if acceptable.
```

Не добавлять тяжёлые изображения в git.

Рекомендуемый вариант для MVP:

```txt
- tiny 1x1 png fixture;
- sample completed job;
- sample failed job;
- sample queued job.
```

### Acceptance criteria

- Demo conversion history создаётся.
- Fixtures маленькие.
- Seeder idempotent.
- History page/recent conversions можно проверить вручную.
- Test passes.

### Definition of Done

- Тест написан.
- Demo conversion seeder создан.
- Test passes.
- Коммит: `CONV-426: Add demo conversion fixtures`

### Files likely touched

```txt
database/seeders/DemoConversionSeeder.php
database/seeders/DatabaseSeeder.php
tests/Fixtures/tiny.png optional
tests/Feature/Seeders/DemoConversionSeederTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-427 — Add Fresh Migration Seed Check

**Area:** QA / Database / Seed  
**Type:** Test / Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-427-add-fresh-migration-seed-check`  
**Base branch:** `develop`  
**Depends on:** CONV-426

### Goal

Зафиксировать, что проект поднимается с нуля через `migrate:fresh --seed`.

### TDD step

No direct unit test — release command verification.

Но можно добавить smoke test для seeders, если его нет.

### Implementation

Создать документ:

```txt
docs/release/fresh-install-check.md
```

Команды:

```bash
php artisan migrate:fresh --seed
php artisan test
```

В документе описать ожидаемый результат:

```txt
- migrations complete;
- demo user exists;
- demo credits exist;
- dashboard accessible after login;
- no migration errors.
```

Запустить команду локально и зафиксировать результат в checklist.

### Acceptance criteria

- `php artisan migrate:fresh --seed` проходит.
- Demo user создаётся.
- Credits создаются.
- Документ fresh install check создан.
- README ссылается на fresh install check или содержит краткое указание.

### Definition of Done

- Fresh install check создан.
- Команда вручную проверена.
- `composer test` проходит.
- Коммит: `CONV-427: Add fresh migration seed check`

### Files likely touched

```txt
docs/release/fresh-install-check.md
README.md optional
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-428 — Add Release Smoke Test Checklist

**Area:** QA / Release  
**Type:** Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-428-add-release-smoke-test-checklist`  
**Base branch:** `develop`  
**Depends on:** CONV-427

### Goal

Создать ручной smoke checklist для проверки MVP перед релизом.

### TDD step

No direct test — manual release checklist.

### Implementation

Создать:

```txt
docs/release/mvp-smoke-test-checklist.md
```

Checklist должен проверять:

```txt
- register/login;
- demo login;
- credits visible;
- upload PNG;
- choose JPG;
- settings render;
- cost visible;
- convert;
- result download;
- history row;
- credits spent;
- insufficient credits behavior;
- billing page opens;
- pricing/subscription CTA opens checkout in test mode;
- credit pack CTA opens checkout in test mode;
- API key creation;
- API upload/conversion/status/download flow;
- API docs page opens;
- cleanup expired files command/job.
```

Каждый пункт должен иметь:

```txt
Expected result
Pass/Fail checkbox
Notes
```

### Acceptance criteria

- Smoke checklist создан.
- Checklist покрывает web, billing, credits, API, docs.
- Checklist не требует знания внутренней архитектуры.
- Checklist можно использовать перед каждым релизом.

### Definition of Done

- Smoke checklist создан.
- Коммит: `CONV-428: Add release smoke test checklist`

### Files likely touched

```txt
docs/release/mvp-smoke-test-checklist.md
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-429 — Run Full Release Quality Gate

**Area:** QA / Release  
**Type:** Release Check  
**Priority:** P0  
**Branch:** `feature/CONV-429-run-full-release-quality-gate`  
**Base branch:** `develop`  
**Depends on:** CONV-428

### Goal

Выполнить финальный quality gate перед release branch.

### TDD step

No direct test — release command execution.

### Implementation

Запустить:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh --seed
php artisan route:list
php artisan schedule:list
```

Создать документ с результатом:

```txt
docs/release/quality-gate-result.md
```

Зафиксировать:

```txt
- date;
- git commit hash;
- commands executed;
- pass/fail result;
- known warnings;
- blocking issues if any.
```

Если команда падает, не делать release branch.

### Acceptance criteria

- Quality gate commands выполнены.
- Результат записан в документ.
- Все blocking commands проходят.
- Если есть warning, он явно описан.
- Release branch ещё не создан в этой задаче.

### Definition of Done

- Full quality gate выполнен.
- Документ результата создан.
- Все команды проходят.
- Коммит: `CONV-429: Run full release quality gate`

### Files likely touched

```txt
docs/release/quality-gate-result.md
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build`, `php artisan migrate:fresh --seed` проходят.

---

## CONV-430 — Create MVP Release Branch

**Area:** GitFlow / Release  
**Type:** Release  
**Priority:** P0  
**Branch:** `release/v0.1.0-mvp`  
**Base branch:** `develop`  
**Depends on:** CONV-429

### Goal

Создать release branch для MVP после успешного quality gate.

### TDD step

No direct test — Git release operation.

Перед выполнением повторить minimum gate:

```bash
composer test
composer lint
npm run build
```

### Implementation

Команды:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh --seed

git checkout -b release/v0.1.0-mvp
git push -u origin release/v0.1.0-mvp
```

Создать MR/PR:

```txt
release/v0.1.0-mvp → main
```

Описание MR должно содержать:

```txt
- MVP scope summary;
- supported conversions;
- quality gate result;
- known limitations;
- deployment checklist link.
```

### Acceptance criteria

- Release branch created from latest develop.
- Release branch pushed.
- MR to main created.
- MR description includes scope, checks and limitations.
- No new code changes added directly to release branch except emergency fixes.

### Definition of Done

- Release branch создан.
- MR в main создан.
- Quality gate result linked.
- После MR остановиться до review.

### Files likely touched

```txt
No files expected unless release notes are added.
```

После этого сделать MR в `main` branch и остановиться.

---

## CONV-431 — Tag MVP Release

**Area:** GitFlow / Release  
**Type:** Release  
**Priority:** P0  
**Branch:** `main`  
**Base branch:** `main`  
**Depends on:** CONV-430 and approved merge to main

### Goal

Создать annotated git tag для MVP после merge release branch в `main`.

### TDD step

No direct test — Git release tagging.

Перед tag проверить:

```bash
git checkout main
git pull origin main
composer test
composer lint
npm run build
```

### Implementation

Команды:

```bash
git checkout main
git pull origin main

composer test
composer lint
npm run build

git tag -a v0.1.0-mvp -m "File Converter v0.1.0 MVP"
git push origin v0.1.0-mvp
```

Optional release notes:

```txt
docs/release/v0.1.0-mvp.md
```

Если release notes создаются, они должны быть добавлены до tag или через отдельный docs commit до релиза.

### Acceptance criteria

- `main` содержит merged release branch.
- Final test/lint/build проходят.
- Annotated tag `v0.1.0-mvp` создан.
- Tag pushed to origin.
- Release notes exist or MR description contains enough release notes.

### Definition of Done

- Tag создан.
- Tag pushed.
- MVP release зафиксирован.
- Коммит не требуется, если только не добавляются release notes.

### Files likely touched

```txt
docs/release/v0.1.0-mvp.md optional
```

После этого релиз Phase 26 завершён.

---

# 10. Phase 26 Completion Criteria

Phase 26 завершена, когда:

```txt
- CONV-415–CONV-431 выполнены;
- MVP scope audit exists;
- supported conversions matrix is frozen;
- .env.example is release-ready;
- production configuration checklist exists;
- README installation guide is updated;
- local development guide exists;
- queue worker docs exist;
- scheduler docs exist;
- storage/permissions docs exist;
- Stripe webhook docs exist;
- demo user seeder exists;
- demo conversion fixtures exist or documented as intentionally skipped;
- migrate:fresh --seed passes;
- release smoke test checklist exists;
- full quality gate result is recorded;
- release branch `release/v0.1.0-mvp` exists;
- release branch MR to main is created;
- tag `v0.1.0-mvp` is created after merge;
- no new product scope was added;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 26

Без отдельной задачи нельзя:

```txt
- добавлять новые converter drivers;
- добавлять OCR;
- добавлять batch conversion;
- добавлять video/audio converters;
- менять credit pricing model;
- менять plan features;
- менять API contracts;
- добавлять API webhooks;
- подключать Spike;
- добавлять Sentry/Bugsnag/OpenTelemetry;
- добавлять Docker production setup;
- переписывать README под несуществующий cloud deployment;
- добавлять React/Vue/Inertia;
- менять dashboard UI;
- менять billing flow;
- менять Cashier integration;
- делать refactor ради красоты;
- скрывать known limitations.
```

---

# 12. Recommended Execution Order

```txt
CONV-415 Audit MVP Scope Completion
CONV-416 Freeze MVP Supported Conversions Matrix
CONV-417 Update Environment Example For Release
CONV-418 Add Production Configuration Checklist
CONV-419 Add README Installation Guide
CONV-420 Add Local Development Guide
CONV-421 Add Queue Worker Documentation
CONV-422 Add Scheduler Documentation
CONV-423 Add Storage And Permissions Documentation
CONV-424 Add Stripe Webhook Setup Documentation
CONV-425 Add Demo User Seeder
CONV-426 Add Demo Conversion Fixtures
CONV-427 Add Fresh Migration Seed Check
CONV-428 Add Release Smoke Test Checklist
CONV-429 Run Full Release Quality Gate
CONV-430 Create MVP Release Branch
CONV-431 Tag MVP Release
```

---

# 13. Release

После завершения `CONV-415`–`CONV-429`:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh --seed
php artisan route:list
php artisan schedule:list

git checkout -b release/v0.1.0-mvp
git push -u origin release/v0.1.0-mvp
```

После этого сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

composer test
composer lint
npm run build

git tag -a v0.1.0-mvp -m "File Converter v0.1.0 MVP"
git push origin v0.1.0-mvp
```
