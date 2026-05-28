# File Converter — Phase 12 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 12 — Recent Conversions Table**  
Диапазон задач: **CONV-164 → CONV-180**  
Основа нумерации: Phase 11 завершилась на `CONV-163`, поэтому Phase 12 начинается с `CONV-164`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 12 соответствует блоку:

```txt
Phase 12 — Recent Conversions Table
```

Правильный диапазон Phase 12:

```txt
CONV-164 — Create RecentConversionsTable Skeleton
CONV-165 — Add Empty State
CONV-166 — Add File Name Column
CONV-167 — Add Source And Target Format Columns
CONV-168 — Add File Size Column
CONV-169 — Add Date Column
CONV-170 — Add Status Badge Column
CONV-171 — Add Download Row Action
CONV-172 — Add Search Input
CONV-173 — Add Status Filter
CONV-174 — Add Pagination
CONV-175 — Add Convert Again Test
CONV-176 — Implement Convert Again Action
CONV-177 — Add Star Conversion Test
CONV-178 — Implement Star Conversion Action
CONV-179 — Add Table Authorization Tests
CONV-180 — Add Recent Conversions Integration Test
```

Phase 12 делает таблицу последних конвертаций на dashboard.

Она использует результат предыдущих фаз:

```txt
Phase 05 — File Storage & Metadata
Phase 09 — Conversion Job Core
Phase 11 — Convert UI Flow
```

После Phase 12 пользователь должен видеть историю последних конвертаций прямо на dashboard и иметь быстрые действия:

```txt
- download completed result;
- search conversions;
- filter by status;
- paginate;
- convert again;
- star/unstar conversion.
```

Важно: Phase 12 не делает отдельную `/history` страницу. Это будет отдельная фаза.

---

# 2. Цель Phase 12

Phase 12 добавляет dashboard-блок `Recent Conversions`.

После Phase 12 authenticated user должен уметь:

```txt
- видеть последние conversion jobs;
- видеть original filename;
- видеть source format и target format;
- видеть размер результата или исходного файла;
- видеть дату создания;
- видеть статус conversion job;
- скачать completed result;
- искать по имени файла и форматам;
- фильтровать по статусу;
- листать pagination;
- запустить convert again;
- отметить conversion как starred.
```

Это dashboard table фаза. Она не должна создавать billing, credits, API, full history page или admin tools.

---

# 3. Scope Phase 12

## Входит

```txt
- RecentConversionsTable Livewire component;
- empty state;
- file name column;
- source/target format columns;
- file size column;
- date column;
- status badge column;
- download action;
- search input;
- status filter;
- pagination;
- convert again action;
- starred flag/action;
- table authorization tests;
- dashboard integration test.
```

## Не входит

```txt
- full /history page;
- date range filters;
- source/target advanced filters;
- credit cost column;
- billing/credits;
- API endpoints;
- batch download;
- delete conversion action;
- public share links;
- cloud save actions;
- admin all-users conversions table;
- export history CSV;
- analytics charts;
- websocket/live progress table;
- retry failed conversion with queue retry semantics.
```

Full history page будет отдельной фазой.  
Credits/cost estimator будет отдельной фазой.  
Billing будет отдельной фазой.

---

# 4. Critical Decisions

## 4.1. Recent Conversions is user-owned only

Таблица должна показывать только conversion jobs текущего пользователя.

Неправильно:

```php
ConversionJob::latest()->paginate(10)
```

Правильно:

```php
ConversionJob::query()
    ->where('user_id', auth()->id())
    ->latest()
    ->paginate(10)
```

Даже если UI скрыт за auth middleware, query-level ownership обязателен.

## 4.2. Dashboard table is not full history

На dashboard нужно показать последние записи и базовые фильтры.

Правильно:

```txt
Recent Conversions
- search
- status filter
- pagination
- row actions
```

Неправильно:

```txt
advanced date ranges
export CSV
all-time analytics
credit statements
```

Это перегрузит MVP.

## 4.3. Download action must reuse existing download route

Phase 12 не должна создавать второй download mechanism.

Правильно:

```txt
row action links to existing /conversions/{conversion}/download
```

Неправильно:

```txt
new table-specific download controller
new direct Storage::download without ownership checks
```

## 4.4. Convert again loads previous source/target/options

`Convert again` не должен сразу создавать новую job.

Правильное поведение:

```txt
click Convert Again
→ load source file id
→ load target format
→ load previous options
→ move DashboardConverter to settings step
→ user confirms Convert Now
```

Так пользователь может поменять настройки перед повторной конвертацией.

## 4.5. Starred is a lightweight MVP feature

Starred conversion в Phase 12 — это просто флаг на conversion job.

Правильно:

```txt
conversion_jobs.is_starred boolean default false
```

Неправильно:

```txt
separate favorites table
conversion presets
saved templates
folders
```

Favorites/presets можно сделать позже.

## 4.6. Status badges must use domain status

Не вычислять статус по наличию result file.

Неправильно:

```php
$record->result_file_id ? 'completed' : 'processing'
```

Правильно:

```php
$record->status
```

`ConversionStatus` уже должен быть источником правды.

---

# 5. Architecture Rules

## 5.1. RecentConversionsTable is a separate Livewire component

Не встраивать таблицу прямо внутрь `DashboardConverter`.

Правильно:

```txt
app/Livewire/RecentConversionsTable.php
resources/views/livewire/recent-conversions-table.blade.php
```

Неправильно:

```txt
DashboardConverter содержит upload state, settings state, polling state и table filters одновременно.
```

`DashboardConverter` и `RecentConversionsTable` могут общаться через Livewire events.

## 5.2. Use eager loading

Таблица должна eager-load source/result files.

Пример:

```php
ConversionJob::query()
    ->with(['sourceFile', 'resultFile'])
```

Цель — не получить N+1 на каждой строке.

## 5.3. No direct conversion creation in table except through dashboard event

`Convert again` не должен сам создавать job.

Он должен отправить событие:

```php
$this->dispatch('conversion-repeat-requested', conversionJobId: $job->id);
```

А `DashboardConverter` решает, как загрузить состояние.

## 5.4. Actions must respect status

Download visible only when:

```txt
status = completed
result_file_id exists
result not expired
```

Convert again visible for:

```txt
completed
failed
```

Star visible for all user-owned records.

## 5.5. Keep table UI simple

Phase 12 table is not a data grid library integration.

Не добавлять:

```txt
Filament table
Datatables
AG Grid
custom JS table framework
```

Livewire pagination and simple Blade table достаточно.

---

# 6. GitFlow для Phase 12

## Base branch

Все задачи Phase 12 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-164-create-recent-conversions-table-skeleton
feature/CONV-171-add-download-row-action
feature/CONV-178-implement-star-conversion-action
```

## Commit format

```txt
CONV-164: Create RecentConversionsTable skeleton
CONV-171: Add download row action
CONV-178: Implement star conversion action
```

## Release branch

После выполнения `CONV-164`–`CONV-180`:

```txt
release/v0.1.12-phase12-recent-conversions-table
```

## Tag

После merge release branch в `main`:

```txt
v0.1.12-phase12-recent-conversions-table
```

---

# 7. TDD Rules for Phase 12

## Для таблицы

Тестировать:

```txt
- authenticated user sees own conversion jobs;
- authenticated user does not see other users' conversion jobs;
- empty state is visible when no jobs exist;
- columns render expected data;
- statuses render expected badges/text;
- pagination works.
```

## Для search/filter

Тестировать:

```txt
- search by filename;
- search by source format;
- search by target format;
- status filter: completed/processing/failed/all.
```

## Для actions

Тестировать:

```txt
- completed conversion has download action;
- processing/failed conversion does not have download action;
- convert again dispatches event or calls dashboard handler;
- star toggles is_starred;
- actions cannot target another user's conversion.
```

Если действие сложно протестировать через DOM, тестировать Livewire method напрямую.

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Dashboard / Livewire / Table / Tests
Type: Test / Feature / UI / Action / Migration
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

# 9. Phase 12 Atomic Tasks

---

## CONV-164 — Create RecentConversionsTable Skeleton

**Area:** Dashboard / Livewire  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-164-create-recent-conversions-table-skeleton`  
**Base branch:** `develop`  
**Depends on:** CONV-163

### Goal

Создать Livewire-компонент `RecentConversionsTable` и подключить его к dashboard под основным converter UI.

### TDD step

Livewire smoke test:

```php
use Livewire\Livewire;

it('renders recent conversions table component', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('Recent Conversions');
});
```

Dashboard integration smoke test:

```php
it('renders recent conversions section on dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Recent Conversions');
});
```

Тест должен упасть до создания компонента/подключения.

### Implementation

Создать Livewire component:

```bash
php artisan make:livewire RecentConversionsTable
```

View должен содержать минимальный skeleton:

```blade
<section>
    <h2>Recent Conversions</h2>
</section>
```

Подключить на dashboard:

```blade
<livewire:recent-conversions-table />
```

### Acceptance criteria

- `RecentConversionsTable` существует.
- Компонент рендерит заголовок `Recent Conversions`.
- Компонент подключён на dashboard.
- Authenticated user видит section.
- Нет query/table logic пока.

### Definition of Done

- Тест написан первым.
- Компонент создан.
- Dashboard integration добавлен.
- Tests pass.
- Коммит: `CONV-164: Create RecentConversionsTable skeleton`

### Files likely touched

```txt
app/Livewire/RecentConversionsTable.php
resources/views/livewire/recent-conversions-table.blade.php
resources/views/dashboard.blade.php
tests/Feature/Livewire/RecentConversionsTableTest.php
tests/Feature/DashboardRouteTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-165 — Add Empty State

**Area:** Dashboard / Livewire / Table  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-165-add-empty-state`  
**Base branch:** `develop`  
**Depends on:** CONV-164

### Goal

Показать понятное empty state, если у пользователя ещё нет conversion jobs.

### TDD step

```php
it('shows empty state when user has no conversions', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('No conversions yet')
        ->assertSee('Upload a file to start converting');
});
```

### Implementation

В компоненте получить последние conversions текущего пользователя.

Если коллекция пустая, показать:

```txt
No conversions yet
Upload a file to start converting.
```

Пока можно использовать простую query без pagination; pagination будет добавлена позже.

### Acceptance criteria

- Empty state виден при отсутствии jobs.
- Empty state не виден, если jobs есть.
- Текст не обещает неподдерживаемые форматы.
- Query scoped to current user.

### Definition of Done

- Тест написан первым.
- Empty state реализован.
- Tests pass.
- Коммит: `CONV-165: Add empty state`

### Files likely touched

```txt
app/Livewire/RecentConversionsTable.php
resources/views/livewire/recent-conversions-table.blade.php
tests/Feature/Livewire/RecentConversionsTableTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-166 — Add File Name Column

**Area:** Dashboard / Livewire / Table  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-166-add-file-name-column`  
**Base branch:** `develop`  
**Depends on:** CONV-165

### Goal

Добавить колонку file name, показывающую имя исходного файла.

### TDD step

```php
it('renders source file name in recent conversions table', function () {
    $user = User::factory()->create();

    $file = FileRecord::factory()->for($user)->create([
        'original_name' => 'product-photo.png',
    ]);

    ConversionJob::factory()
        ->for($user)
        ->for($file, 'sourceFile')
        ->create();

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('product-photo.png');
});
```

### Implementation

Обновить query:

```php
ConversionJob::query()
    ->where('user_id', auth()->id())
    ->with(['sourceFile', 'resultFile'])
    ->latest()
```

В таблице добавить column:

```txt
File Name
```

Показывать:

```txt
sourceFile.original_name
```

Если source file отсутствует, fallback:

```txt
—
```

### Acceptance criteria

- File name visible.
- Missing source file handled safely.
- Query uses eager loading.
- User sees only own jobs.

### Definition of Done

- Тест написан первым.
- Column добавлена.
- Tests pass.
- Коммит: `CONV-166: Add file name column`

### Files likely touched

```txt
app/Livewire/RecentConversionsTable.php
resources/views/livewire/recent-conversions-table.blade.php
tests/Feature/Livewire/RecentConversionsTableTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-167 — Add Source And Target Format Columns

**Area:** Dashboard / Livewire / Table  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-167-add-source-and-target-format-columns`  
**Base branch:** `develop`  
**Depends on:** CONV-166

### Goal

Добавить колонки `From` и `To`, показывающие source format и target format.

### TDD step

```php
it('renders source and target formats in recent conversions table', function () {
    $user = User::factory()->create();

    ConversionJob::factory()->for($user)->create([
        'source_format' => 'png',
        'target_format' => 'jpg',
    ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('PNG')
        ->assertSee('JPG');
});
```

### Implementation

Добавить table headers:

```txt
From
To
```

Форматы показывать uppercase:

```php
strtoupper($job->source_format)
strtoupper($job->target_format)
```

Можно использовать `FileIcon` component рядом с format label, если он уже есть из UI foundation.

### Acceptance criteria

- Source format visible.
- Target format visible.
- Format rendered uppercase.
- Unknown/null format handled safely.

### Definition of Done

- Тест написан первым.
- Columns добавлены.
- Tests pass.
- Коммит: `CONV-167: Add source and target format columns`

### Files likely touched

```txt
resources/views/livewire/recent-conversions-table.blade.php
tests/Feature/Livewire/RecentConversionsTableTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-168 — Add File Size Column

**Area:** Dashboard / Livewire / Table  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-168-add-file-size-column`  
**Base branch:** `develop`  
**Depends on:** CONV-167

### Goal

Добавить колонку size.

Правило MVP:

```txt
If result file exists → show result file size.
Else → show source file size.
```

### TDD step

```php
it('renders result file size when result file exists', function () {
    $user = User::factory()->create();

    $source = FileRecord::factory()->for($user)->create([
        'size_bytes' => 900_000,
    ]);

    $result = FileRecord::factory()->for($user)->create([
        'size_bytes' => 420_000,
    ]);

    ConversionJob::factory()
        ->for($user)
        ->for($source, 'sourceFile')
        ->for($result, 'resultFile')
        ->create();

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('420 KB');
});
```

### Implementation

Добавить helper для форматирования bytes.

Варианты:

```php
app(FormatsBytes::class)->format($bytes)
```

или private method внутри component для MVP.

Не тащить отдельный package.

### Acceptance criteria

- Result file size shown when available.
- Source file size shown when result missing.
- Bytes formatted human-readably.
- Null size handled as `—`.

### Definition of Done

- Тест написан первым.
- Size column добавлена.
- Tests pass.
- Коммит: `CONV-168: Add file size column`

### Files likely touched

```txt
app/Livewire/RecentConversionsTable.php
resources/views/livewire/recent-conversions-table.blade.php
tests/Feature/Livewire/RecentConversionsTableTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-169 — Add Date Column

**Area:** Dashboard / Livewire / Table  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-169-add-date-column`  
**Base branch:** `develop`  
**Depends on:** CONV-168

### Goal

Добавить колонку date, показывающую дату создания conversion job.

### TDD step

```php
it('renders conversion creation date', function () {
    $user = User::factory()->create();

    ConversionJob::factory()->for($user)->create([
        'created_at' => now()->setDate(2026, 1, 15)->setTime(10, 30),
    ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('Jan 15, 2026');
});
```

### Implementation

Добавить column:

```txt
Date
```

Формат:

```php
$job->created_at?->format('M j, Y H:i')
```

Если проект использует user timezone позже — не реализовывать сейчас. MVP может использовать app timezone.

### Acceptance criteria

- Date visible.
- Format readable.
- Null date handled safely.
- Sorting remains latest first by default.

### Definition of Done

- Тест написан первым.
- Date column добавлена.
- Tests pass.
- Коммит: `CONV-169: Add date column`

### Files likely touched

```txt
resources/views/livewire/recent-conversions-table.blade.php
tests/Feature/Livewire/RecentConversionsTableTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-170 — Add Status Badge Column

**Area:** Dashboard / Livewire / Table  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-170-add-status-badge-column`  
**Base branch:** `develop`  
**Depends on:** CONV-169

### Goal

Добавить status badge column.

### TDD step

```php
it('renders conversion status badge', function () {
    $user = User::factory()->create();

    ConversionJob::factory()->for($user)->create([
        'status' => ConversionStatus::Completed,
    ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('Completed');
});
```

Можно добавить data provider для статусов:

```txt
queued
processing
completed
failed
cancelled
expired
```

### Implementation

Добавить column:

```txt
Status
```

Badge colors:

```txt
queued     → purple/blue
processing → warning
completed  → success
failed     → danger
cancelled  → neutral
expired    → neutral
```

Использовать `<x-badge>` из UI foundation, если он уже есть.

### Acceptance criteria

- Status visible.
- Status rendered as badge.
- Each main status has color mapping.
- Uses ConversionStatus enum/value consistently.

### Definition of Done

- Тест написан первым.
- Status badge column добавлена.
- Tests pass.
- Коммит: `CONV-170: Add status badge column`

### Files likely touched

```txt
resources/views/livewire/recent-conversions-table.blade.php
app/Livewire/RecentConversionsTable.php
tests/Feature/Livewire/RecentConversionsTableTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-171 — Add Download Row Action

**Area:** Dashboard / Livewire / Table / Action  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-171-add-download-row-action`  
**Base branch:** `develop`  
**Depends on:** CONV-170

### Goal

Добавить row action для скачивания completed conversion result.

### TDD step

```php
it('shows download action for completed conversion', function () {
    $user = User::factory()->create();

    $result = FileRecord::factory()->for($user)->create();

    ConversionJob::factory()->for($user)->create([
        'status' => ConversionStatus::Completed,
        'result_file_id' => $result->id,
    ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('Download');
});
```

Negative test:

```php
it('does not show download action for failed conversion', function () {
    $user = User::factory()->create();

    ConversionJob::factory()->for($user)->create([
        'status' => ConversionStatus::Failed,
    ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertDontSee('Download');
});
```

### Implementation

В row actions добавить ссылку:

```blade
@if ($job->isCompleted() && $job->result_file_id)
    <a href="{{ route('conversions.download', $job) }}">Download</a>
@endif
```

Если `isCompleted()` helper ещё нет, добавить на `ConversionJob` model.

Не создавать новый download route. Использовать route из Phase 11.

### Acceptance criteria

- Download visible only for completed jobs with result file.
- Failed/processing/queued jobs do not show download.
- Link points to existing download route.
- Ownership still enforced by route.

### Definition of Done

- Тесты написаны первыми.
- Download row action добавлен.
- Tests pass.
- Коммит: `CONV-171: Add download row action`

### Files likely touched

```txt
app/Models/ConversionJob.php
resources/views/livewire/recent-conversions-table.blade.php
tests/Feature/Livewire/RecentConversionsTableTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-172 — Add Search Input

**Area:** Dashboard / Livewire / Table / Search  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-172-add-search-input`  
**Base branch:** `develop`  
**Depends on:** CONV-171

### Goal

Добавить поиск по recent conversions.

Поиск должен находить:

```txt
- original filename;
- source format;
- target format.
```

### TDD step

```php
it('searches conversions by source file name', function () {
    $user = User::factory()->create();

    $matchFile = FileRecord::factory()->for($user)->create([
        'original_name' => 'marketing-report.png',
    ]);

    $otherFile = FileRecord::factory()->for($user)->create([
        'original_name' => 'product-photo.jpg',
    ]);

    $match = ConversionJob::factory()->for($user)->for($matchFile, 'sourceFile')->create();
    $other = ConversionJob::factory()->for($user)->for($otherFile, 'sourceFile')->create();

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->set('search', 'marketing')
        ->assertSee('marketing-report.png')
        ->assertDontSee('product-photo.jpg');
});
```

### Implementation

Добавить public property:

```php
public string $search = '';
```

Query:

```php
->when($this->search !== '', function (Builder $query) {
    $search = trim($this->search);

    $query->where(function (Builder $query) use ($search) {
        $query->where('source_format', 'like', "%{$search}%")
            ->orWhere('target_format', 'like', "%{$search}%")
            ->orWhereHas('sourceFile', fn (Builder $q) =>
                $q->where('original_name', 'like', "%{$search}%")
            );
    });
})
```

UI:

```txt
Search files, formats...
```

### Acceptance criteria

- Search input visible.
- Search by filename works.
- Search by source format works.
- Search by target format works.
- Empty search shows all rows.

### Definition of Done

- Тест написан первым.
- Search implemented.
- Tests pass.
- Коммит: `CONV-172: Add search input`

### Files likely touched

```txt
app/Livewire/RecentConversionsTable.php
resources/views/livewire/recent-conversions-table.blade.php
tests/Feature/Livewire/RecentConversionsTableTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-173 — Add Status Filter

**Area:** Dashboard / Livewire / Table / Filter  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-173-add-status-filter`  
**Base branch:** `develop`  
**Depends on:** CONV-172

### Goal

Добавить фильтр по статусу.

Filters:

```txt
All
Completed
Processing
Failed
```

### TDD step

```php
it('filters conversions by status', function () {
    $user = User::factory()->create();

    $completed = ConversionJob::factory()->for($user)->create([
        'status' => ConversionStatus::Completed,
    ]);

    $failed = ConversionJob::factory()->for($user)->create([
        'status' => ConversionStatus::Failed,
    ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->set('statusFilter', 'completed')
        ->assertSee('Completed')
        ->assertDontSee('Failed');
});
```

### Implementation

Добавить:

```php
public string $statusFilter = 'all';
```

Query:

```php
->when($this->statusFilter !== 'all', fn (Builder $query) =>
    $query->where('status', $this->statusFilter)
)
```

UI можно сделать как segmented controls или select.

### Acceptance criteria

- All filter shows all statuses.
- Completed filter shows completed only.
- Processing filter shows processing only.
- Failed filter shows failed only.
- Invalid filter ignored or normalized to all.

### Definition of Done

- Тест написан первым.
- Status filter implemented.
- Tests pass.
- Коммит: `CONV-173: Add status filter`

### Files likely touched

```txt
app/Livewire/RecentConversionsTable.php
resources/views/livewire/recent-conversions-table.blade.php
tests/Feature/Livewire/RecentConversionsTableTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-174 — Add Pagination

**Area:** Dashboard / Livewire / Table  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-174-add-pagination`  
**Base branch:** `develop`  
**Depends on:** CONV-173

### Goal

Добавить pagination для recent conversions.

### TDD step

```php
it('paginates recent conversions', function () {
    $user = User::factory()->create();

    ConversionJob::factory()->count(16)->for($user)->create();

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('Next');
});
```

Если pagination text отличается в Tailwind pagination views, проверять количество records или page state.

### Implementation

В компонент:

```php
use Livewire\WithPagination;

public int $perPage = 10;
```

Query:

```php
return $query->paginate($this->perPage);
```

При изменении search/statusFilter сбрасывать страницу:

```php
public function updatedSearch(): void
{
    $this->resetPage();
}

public function updatedStatusFilter(): void
{
    $this->resetPage();
}
```

### Acceptance criteria

- Table paginated.
- Default per page = 10.
- Pagination links visible when needed.
- Search resets page.
- Status filter resets page.

### Definition of Done

- Тест написан первым.
- Pagination implemented.
- Tests pass.
- Коммит: `CONV-174: Add pagination`

### Files likely touched

```txt
app/Livewire/RecentConversionsTable.php
resources/views/livewire/recent-conversions-table.blade.php
tests/Feature/Livewire/RecentConversionsTableTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-175 — Add Convert Again Test

**Area:** Dashboard / Livewire / Action / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-175-add-convert-again-test`  
**Base branch:** `develop`  
**Depends on:** CONV-174

### Goal

Написать падающий тест: пользователь может нажать `Convert Again` на своей conversion job.

### TDD step

```php
it('dispatches convert again event for own conversion', function () {
    $user = User::factory()->create();

    $job = ConversionJob::factory()->for($user)->create([
        'status' => ConversionStatus::Completed,
        'target_format' => 'jpg',
        'options_json' => [
            'quality' => 'high',
        ],
    ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->call('convertAgain', $job->id)
        ->assertDispatched('conversion-repeat-requested');
});
```

Security test:

```php
it('does not dispatch convert again event for another users conversion', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $job = ConversionJob::factory()->for($other)->create();

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->call('convertAgain', $job->id)
        ->assertNotDispatched('conversion-repeat-requested');
});
```

### Implementation

Только добавить тесты. Реализация будет в CONV-176.

### Acceptance criteria

- Test for own conversion exists.
- Test for another user's conversion exists.
- Tests fail before implementation.

### Definition of Done

- Тесты написаны.
- Тесты ожидаемо падают.
- Коммит: `CONV-175: Add convert again test`

### Files likely touched

```txt
tests/Feature/Livewire/RecentConversionsTableActionsTest.php
```

После этого сделай MR в `develop`. Merge разрешён после подтверждения, что тест падает ожидаемо до реализации.

---

## CONV-176 — Implement Convert Again Action

**Area:** Dashboard / Livewire / Action  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-176-implement-convert-again-action`  
**Base branch:** `develop`  
**Depends on:** CONV-175

### Goal

Реализовать `Convert Again` action.

### TDD step

Использовать падающие тесты из CONV-175.

### Implementation

В `RecentConversionsTable`:

```php
public function convertAgain(int $conversionJobId): void
{
    $job = ConversionJob::query()
        ->where('user_id', auth()->id())
        ->find($conversionJobId);

    if (! $job) {
        return;
    }

    $this->dispatch('conversion-repeat-requested', conversionJobId: $job->id);
}
```

В row actions добавить button:

```blade
<button wire:click="convertAgain({{ $job->id }})">
    Convert again
</button>
```

Не создавать новую conversion job здесь.

### Acceptance criteria

- Own job dispatches `conversion-repeat-requested`.
- Another user's job does not dispatch.
- Button visible in row actions.
- No new ConversionJob is created by this action.

### Definition of Done

- Реализация минимальная.
- Тесты из CONV-175 проходят.
- Коммит: `CONV-176: Implement convert again action`

### Files likely touched

```txt
app/Livewire/RecentConversionsTable.php
resources/views/livewire/recent-conversions-table.blade.php
tests/Feature/Livewire/RecentConversionsTableActionsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-177 — Add Star Conversion Test

**Area:** Dashboard / Livewire / Action / Tests  
**Type:** Test  
**Priority:** P1  
**Branch:** `feature/CONV-177-add-star-conversion-test`  
**Base branch:** `develop`  
**Depends on:** CONV-176

### Goal

Написать падающий тест для star/unstar conversion.

### TDD step

Migration expectation test:

```php
it('can toggle starred state for own conversion', function () {
    $user = User::factory()->create();

    $job = ConversionJob::factory()->for($user)->create([
        'is_starred' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->call('toggleStar', $job->id);

    expect($job->fresh()->is_starred)->toBeTrue();
});
```

Security test:

```php
it('cannot toggle starred state for another users conversion', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $job = ConversionJob::factory()->for($other)->create([
        'is_starred' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->call('toggleStar', $job->id);

    expect($job->fresh()->is_starred)->toBeFalse();
});
```

### Implementation

Только добавить тесты. Реализация будет в CONV-178.

### Acceptance criteria

- Own toggle test exists.
- Other-user security test exists.
- Tests fail before implementation.

### Definition of Done

- Тесты написаны.
- Тесты ожидаемо падают.
- Коммит: `CONV-177: Add star conversion test`

### Files likely touched

```txt
tests/Feature/Livewire/RecentConversionsTableActionsTest.php
```

После этого сделай MR в `develop`. Merge разрешён после подтверждения, что тест падает ожидаемо до реализации.

---

## CONV-178 — Implement Star Conversion Action

**Area:** Dashboard / Livewire / Action / Migration  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-178-implement-star-conversion-action`  
**Base branch:** `develop`  
**Depends on:** CONV-177

### Goal

Реализовать star/unstar conversion action.

### TDD step

Использовать падающие тесты из CONV-177.

### Implementation

Добавить migration:

```php
Schema::table('conversion_jobs', function (Blueprint $table) {
    $table->boolean('is_starred')->default(false)->after('status');
});
```

Добавить cast:

```php
'is_starred' => 'boolean',
```

В `RecentConversionsTable`:

```php
public function toggleStar(int $conversionJobId): void
{
    $job = ConversionJob::query()
        ->where('user_id', auth()->id())
        ->find($conversionJobId);

    if (! $job) {
        return;
    }

    $job->forceFill([
        'is_starred' => ! $job->is_starred,
    ])->save();
}
```

UI:

```blade
<button wire:click="toggleStar({{ $job->id }})">
    {{ $job->is_starred ? 'Starred' : 'Star' }}
</button>
```

### Acceptance criteria

- `conversion_jobs.is_starred` exists.
- Default false.
- Own conversion can be starred/unstarred.
- Another user's conversion cannot be modified.
- UI reflects starred state.

### Definition of Done

- Migration added.
- Cast added.
- Action implemented.
- Tests pass.
- Коммит: `CONV-178: Implement star conversion action`

### Files likely touched

```txt
database/migrations/*add_is_starred_to_conversion_jobs_table.php
app/Models/ConversionJob.php
app/Livewire/RecentConversionsTable.php
resources/views/livewire/recent-conversions-table.blade.php
tests/Feature/Livewire/RecentConversionsTableActionsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-179 — Add Table Authorization Tests

**Area:** Dashboard / Livewire / Security / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-179-add-table-authorization-tests`  
**Base branch:** `develop`  
**Depends on:** CONV-178

### Goal

Зафиксировать, что RecentConversionsTable не показывает и не изменяет чужие conversion jobs.

### TDD step

```php
it('does not render another users conversions', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $ownFile = FileRecord::factory()->for($user)->create([
        'original_name' => 'own-file.png',
    ]);

    $otherFile = FileRecord::factory()->for($other)->create([
        'original_name' => 'other-file.png',
    ]);

    ConversionJob::factory()->for($user)->for($ownFile, 'sourceFile')->create();
    ConversionJob::factory()->for($other)->for($otherFile, 'sourceFile')->create();

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('own-file.png')
        ->assertDontSee('other-file.png');
});
```

Action security tests already exist for convertAgain/toggleStar. Add missing ones if not covered.

### Implementation

Если тест падает, исправить query/action scoping.

Правильный query:

```php
->where('user_id', auth()->id())
```

Правильный action lookup:

```php
ConversionJob::query()
    ->where('user_id', auth()->id())
    ->find($id)
```

### Acceptance criteria

- Own jobs visible.
- Other users' jobs hidden.
- Actions cannot affect other users' jobs.
- All table queries scoped to current user.

### Definition of Done

- Authorization tests added.
- Query/action scoping verified.
- Tests pass.
- Коммит: `CONV-179: Add table authorization tests`

### Files likely touched

```txt
app/Livewire/RecentConversionsTable.php
tests/Feature/Livewire/RecentConversionsTableAuthorizationTest.php
tests/Feature/Livewire/RecentConversionsTableActionsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-180 — Add Recent Conversions Integration Test

**Area:** Dashboard / Livewire / Integration / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-180-add-recent-conversions-integration-test`  
**Base branch:** `develop`  
**Depends on:** CONV-179

### Goal

Добавить интеграционный тест: после успешной web-конвертации запись появляется в Recent Conversions table.

### TDD step

```php
it('shows completed conversion in recent conversions after successful conversion flow', function () {
    $user = User::factory()->create();

    $source = FileRecord::factory()->for($user)->create([
        'original_name' => 'product-photo.png',
        'extension' => 'png',
    ]);

    $result = FileRecord::factory()->for($user)->create([
        'original_name' => 'product-photo.jpg',
        'extension' => 'jpg',
        'size_bytes' => 420_000,
    ]);

    ConversionJob::factory()
        ->for($user)
        ->for($source, 'sourceFile')
        ->for($result, 'resultFile')
        ->create([
            'source_format' => 'png',
            'target_format' => 'jpg',
            'status' => ConversionStatus::Completed,
        ]);

    $this->actingAs($user);

    Livewire::test(RecentConversionsTable::class)
        ->assertSee('product-photo.png')
        ->assertSee('PNG')
        ->assertSee('JPG')
        ->assertSee('Completed')
        ->assertSee('Download');
});
```

Если Phase 11 имеет full conversion flow test, добавить assertion туда или оставить отдельный table integration test.

### Implementation

Если все предыдущие задачи сделаны правильно, реализация может не потребоваться.

Если тест падает, исправить:

```txt
- eager loading;
- status rendering;
- download visibility;
- file size fallback;
- dashboard integration.
```

### Acceptance criteria

- Completed conversion appears in table.
- File name visible.
- Source/target visible.
- Status visible.
- Download action visible.
- Test passes with realistic data.

### Definition of Done

- Integration test added.
- No unnecessary implementation if already passes.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.
- Коммит: `CONV-180: Add recent conversions integration test`

### Files likely touched

```txt
tests/Feature/Livewire/RecentConversionsTableIntegrationTest.php
app/Livewire/RecentConversionsTable.php
resources/views/livewire/recent-conversions-table.blade.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 12 Completion Criteria

Phase 12 завершена, когда:

```txt
- CONV-164–CONV-180 выполнены;
- RecentConversionsTable component exists;
- dashboard renders Recent Conversions section;
- empty state works;
- file name column works;
- source/target format columns work;
- file size column works;
- date column works;
- status badge column works;
- download action is visible only for completed results;
- search works by filename/source/target;
- status filter works;
- pagination works;
- convert again dispatches event and does not create job directly;
- star/unstar action works;
- table never shows another user's jobs;
- table actions cannot mutate another user's jobs;
- no full history page was created;
- no billing/credits/API logic was added;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 12

Без отдельной задачи нельзя:

```txt
- создавать /history page;
- добавлять date range filters;
- добавлять credit cost column;
- добавлять CreditLedger;
- добавлять ConversionCostEstimator;
- добавлять billing CTA;
- добавлять Cashier;
- добавлять API endpoints;
- добавлять batch download;
- добавлять delete conversion action;
- добавлять public share links;
- добавлять cloud save actions;
- добавлять admin conversions table;
- добавлять analytics charts;
- добавлять websocket progress;
- подключать table/grid JS libraries;
- добавлять React/Vue/Inertia.
```

---

# 12. Recommended Execution Order

```txt
CONV-164 Create RecentConversionsTable Skeleton
CONV-165 Add Empty State
CONV-166 Add File Name Column
CONV-167 Add Source And Target Format Columns
CONV-168 Add File Size Column
CONV-169 Add Date Column
CONV-170 Add Status Badge Column
CONV-171 Add Download Row Action
CONV-172 Add Search Input
CONV-173 Add Status Filter
CONV-174 Add Pagination
CONV-175 Add Convert Again Test
CONV-176 Implement Convert Again Action
CONV-177 Add Star Conversion Test
CONV-178 Implement Star Conversion Action
CONV-179 Add Table Authorization Tests
CONV-180 Add Recent Conversions Integration Test
```

---

# 13. Release

После завершения Phase 12:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh --seed

git checkout -b release/v0.1.12-phase12-recent-conversions-table
git push -u origin release/v0.1.12-phase12-recent-conversions-table
```

После этого сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.12-phase12-recent-conversions-table -m "File Converter Phase 12 recent conversions table"
git push origin v0.1.12-phase12-recent-conversions-table
```
