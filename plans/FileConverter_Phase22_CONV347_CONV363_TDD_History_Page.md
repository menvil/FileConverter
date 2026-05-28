# File Converter — Phase 22 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 22 — History Page**  
Диапазон задач: **CONV-347 → CONV-363**  
Основа нумерации: Phase 21 завершилась на `CONV-346`, поэтому Phase 22 начинается с `CONV-347`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 22 соответствует блоку:

```txt
Phase 22 — History Page
```

Правильный диапазон Phase 22:

```txt
CONV-347 — Create History Page Route And Shell
CONV-348 — Create ConversionHistoryTable Component Skeleton
CONV-349 — Test History Shows Current User Conversions
CONV-350 — Implement User-Scoped History Query
CONV-351 — Add History Table Columns
CONV-352 — Add History Status Badges
CONV-353 — Test History Search Filter
CONV-354 — Implement History Search Filter
CONV-355 — Test History Status Filter
CONV-356 — Implement History Status Filter
CONV-357 — Test History Date Range Filter
CONV-358 — Implement History Date Range Filter
CONV-359 — Test History Format Filters
CONV-360 — Implement History Format Filters
CONV-361 — Add History Row Actions
CONV-362 — Add Credit Cost Column
CONV-363 — Add History Page Final Smoke Tests
```

Phase 22 создаёт отдельную страницу полной истории конвертаций.

Главное правило:

```txt
Dashboard показывает короткий Recent Conversions block.
/history показывает полную историю с поиском, фильтрами, пагинацией и действиями.
```

Phase 22 не должна превращаться в файловый менеджер, биллинг-историю или админку.

---

# 2. Цель Phase 22

Phase 22 добавляет полноценную страницу `/history`, где пользователь может найти прошлые conversion jobs, посмотреть статус, скачать результат, повторить конвертацию и увидеть стоимость в credits.

После Phase 22 пользователь должен уметь:

```txt
- открыть /history;
- видеть только свои conversion jobs;
- видеть source file / result file / source format / target format;
- видеть дату создания и дату завершения;
- видеть status;
- искать по имени файла и форматам;
- фильтровать по status;
- фильтровать по date range;
- фильтровать по source/target format;
- скачать completed result, если result не expired;
- видеть disabled download для expired/failed/processing jobs;
- повторить conversion через Convert Again;
- видеть credit cost для completed jobs;
- пользоваться пагинацией.
```

History page — это рабочий инструмент, а не декоративная таблица.

---

# 3. Scope Phase 22

## Входит

```txt
- /history route;
- History page Blade shell;
- ConversionHistoryTable Livewire component;
- user-scoped conversion jobs query;
- table columns;
- status badges;
- search filter;
- status filter;
- date range filter;
- source format filter;
- target format filter;
- pagination;
- download action;
- convert again action;
- credit cost column;
- expired result handling in history UI;
- smoke tests for access, filters and actions.
```

## Не входит

```txt
- admin history for all users;
- analytics dashboard;
- charts;
- bulk actions;
- file rename;
- file move/folders;
- deleting conversion history;
- deleting physical files;
- billing invoice history;
- credit transaction ledger page;
- API usage history;
- export CSV;
- saved presets page;
- favorites page;
- webhooks;
- notifications;
- new converters;
- new billing rules;
- new frontend framework.
```

Credit transaction history уже относится к Billing Page / Credit Ledger area.  
File management относится к будущей My Files phase.  
Phase 22 делает только conversion history.

---

# 4. Critical Decisions

## 4.1. History is conversion-job-first

Страница `/history` должна отображать `ConversionJob`, а не `FileRecord`.

Правильно:

```txt
ConversionJob row:
- source_file
- result_file
- source_format
- target_format
- status
- credit charge
```

Неправильно:

```txt
File list с разрозненными uploaded/result files.
```

Файловый менеджер появится позже, если он вообще нужен.

## 4.2. User scope is mandatory

Пользователь не должен видеть чужие conversion jobs.

Query всегда должна быть scoped:

```php
ConversionJob::query()
    ->where('user_id', auth()->id())
```

Даже если UI скрывает чужие данные, backend query обязан ограничивать scope.

## 4.3. History records survive retention cleanup

Phase 21 удаляет физические expired files, но не должна удалять history rows.

Поэтому `/history` должен корректно показывать:

```txt
completed job + result expired → row visible, download disabled
failed job → row visible, error status visible
processing job → row visible, download disabled
```

Нельзя скрывать expired jobs из history, иначе пользователь не поймёт, куда исчезли результаты.

## 4.4. Reuse logic, do not duplicate conversion flow

`Convert Again` не должен вручную создавать новую конвертацию внутри History component.

Правильное поведение:

```txt
History row action redirects/dispatches to DashboardConverter with source file + target + options.
```

Если source file expired/deleted, action должен показывать clear message:

```txt
Original file expired. Upload it again to repeat this conversion.
```

## 4.5. Credit cost is not recalculated in history

History должна показывать фактически списанную стоимость, а не пересчитывать её заново.

Правильно:

```txt
conversion_credit_charges.captured_amount
```

Неправильно:

```txt
ConversionCostEstimator::estimate(...) прямо в таблице history
```

Цены могут измениться позже. История должна показывать historical cost.

## 4.6. Filters should be simple and durable

Для MVP достаточно:

```txt
search
status
source_format
target_format
date_from
date_to
```

Не нужно делать сложный query builder, saved views или analytics filters.

## 4.7. URL query string is useful but not mandatory everywhere

Для history page желательно сохранять фильтры в URL:

```txt
/history?status=completed&source=png&target=jpg
```

Но не надо тратить много времени на идеальный UX. Главное — фильтры должны работать и тестироваться.

---

# 5. Architecture Rules

## 5.1. Dedicated Livewire component

Рекомендуемый компонент:

```txt
app/Livewire/ConversionHistoryTable.php
resources/views/livewire/conversion-history-table.blade.php
```

Не надо раздувать DashboardConverter.

## 5.2. RecentConversionsTable can be reused only if clean

Если `RecentConversionsTable` уже спроектирован как reusable component, можно расширить его режимом:

```php
mode: 'recent' | 'history'
```

Но если это приведёт к условному аду, лучше создать отдельный `ConversionHistoryTable`.

Правило:

```txt
Reuse only if it reduces duplication without making component unreadable.
```

## 5.3. Eager loading required

Query должен eager-load связи:

```php
->with([
    'sourceFile',
    'resultFile',
    'creditCharge',
])
```

Иначе таблица history быстро превратится в N+1 проблему.

## 5.4. Actions must use existing routes/actions

Download action использует существующий download route из Convert UI/API phases.

Convert Again action не должен дублировать `CreateConversionJobAction` напрямую без выбора/подтверждения настроек пользователем.

## 5.5. No hard deletes in history

Никаких:

```php
$job->delete();
$file->delete();
```

в Phase 22.

Delete history может появиться позже как отдельная privacy/data-retention задача.

## 5.6. No billing mutation in history

History page читает credit charge, но не создаёт, не списывает и не возвращает credits.

---

# 6. GitFlow для Phase 22

## Base branch

Все задачи Phase 22 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-347-create-history-page-route-and-shell
feature/CONV-354-implement-history-search-filter
feature/CONV-361-add-history-row-actions
```

## Commit format

```txt
CONV-347: Create history page route and shell
CONV-354: Implement history search filter
CONV-361: Add history row actions
```

## Release branch

После выполнения `CONV-347`–`CONV-363`:

```txt
release/v0.1.22-phase22-history-page
```

## Tag

После merge release branch в `main`:

```txt
v0.1.22-phase22-history-page
```

---

# 7. TDD Rules for Phase 22

## Для доступа

Тестировать:

```txt
- guest cannot access /history;
- authenticated user can access /history;
- page renders ConversionHistoryTable.
```

## Для user scope

Тестировать:

```txt
- user sees own conversions;
- user does not see other users conversions.
```

## Для filters

Тестировать результат, а не CSS:

```txt
- search shows matching jobs only;
- status filter shows selected status only;
- date range filter limits created_at;
- source format filter works;
- target format filter works.
```

## Для actions

Тестировать:

```txt
- completed non-expired job has download action;
- expired result does not allow download;
- failed/processing jobs do not show active download;
- convert again is available only when source file is still available;
- convert again does not create job immediately without settings confirmation.
```

## Для credit cost

Тестировать:

```txt
- completed job shows captured credit amount;
- job without charge shows dash;
- failed job does not show fake cost.
```

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: History / Livewire / Table / Tests
Type: Test / Feature / Component / Filter / Action
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

# 9. Phase 22 Atomic Tasks

---

## CONV-347 — Create History Page Route And Shell

**Area:** History / Routes / Blade  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-347-create-history-page-route-and-shell`  
**Base branch:** `develop`  
**Depends on:** CONV-346

### Goal

Создать защищённую страницу `/history` с базовым shell для полной истории конвертаций.

### TDD step

Feature test:

```php
it('allows authenticated user to access history page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/history')
        ->assertOk()
        ->assertSee('Conversion History');
});
```

Guest test:

```php
it('redirects guest from history page to login', function () {
    $this->get('/history')
        ->assertRedirect('/login');
});
```

Тесты должны упасть до реализации route/view.

### Implementation

Добавить route:

```php
Route::middleware(['auth'])
    ->get('/history', HistoryPageController::class)
    ->name('history');
```

или проще:

```php
Route::view('/history', 'history.index')
    ->middleware('auth')
    ->name('history');
```

Создать view:

```txt
resources/views/history/index.blade.php
```

Минимальный content:

```blade
<x-app-layout>
    <section>
        <h1>Conversion History</h1>
        <p>Find and reuse your previous conversions.</p>
    </section>
</x-app-layout>
```

Не добавлять таблицу в этой задаче.

### Acceptance criteria

- `/history` route exists.
- Guest получает redirect to login.
- Auth user получает 200.
- Page содержит `Conversion History`.
- Нет таблицы и фильтров пока.
- Тесты проходят.

### Definition of Done

- Тесты написаны первыми.
- Route/view созданы.
- Tests pass.
- `composer test` проходит.
- `composer lint` проходит.
- Коммит: `CONV-347: Create history page route and shell`

### Files likely touched

```txt
routes/web.php
resources/views/history/index.blade.php
tests/Feature/History/HistoryPageAccessTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-348 — Create ConversionHistoryTable Component Skeleton

**Area:** History / Livewire  
**Type:** Component  
**Priority:** P0  
**Branch:** `feature/CONV-348-create-conversion-history-table-component-skeleton`  
**Base branch:** `develop`  
**Depends on:** CONV-347

### Goal

Создать Livewire component skeleton для таблицы полной истории конвертаций.

### TDD step

Livewire smoke test:

```php
use Livewire\Livewire;

it('renders conversion history table component', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->assertSee('History table');
});
```

Тест должен упасть до создания компонента.

### Implementation

Создать:

```bash
php artisan make:livewire ConversionHistoryTable
```

View skeleton:

```blade
<div>
    <div>History table</div>
</div>
```

Добавить компонент на страницу `/history`:

```blade
<livewire:conversion-history-table />
```

Пока не добавлять query.

### Acceptance criteria

- `ConversionHistoryTable` существует.
- Component renders.
- Component mounted on `/history` page.
- Нет query/business logic пока.
- Livewire test passes.

### Definition of Done

- Тест написан первым.
- Component создан.
- Component подключён к странице.
- Tests pass.
- Коммит: `CONV-348: Create conversion history table component skeleton`

### Files likely touched

```txt
app/Livewire/ConversionHistoryTable.php
resources/views/livewire/conversion-history-table.blade.php
resources/views/history/index.blade.php
tests/Feature/Livewire/ConversionHistoryTableTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-349 — Test History Shows Current User Conversions

**Area:** History / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-349-test-history-shows-current-user-conversions`  
**Base branch:** `develop`  
**Depends on:** CONV-348

### Goal

Написать падающий тест: пользователь видит свои conversion jobs и не видит чужие.

### TDD step

Livewire test:

```php
it('shows only current user conversion jobs in history', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $ownFile = FileRecord::factory()->for($user)->create([
        'original_name' => 'own-image.png',
    ]);

    $otherFile = FileRecord::factory()->for($otherUser)->create([
        'original_name' => 'other-image.png',
    ]);

    ConversionJob::factory()
        ->for($user)
        ->for($ownFile, 'sourceFile')
        ->create([
            'source_format' => 'png',
            'target_format' => 'jpg',
        ]);

    ConversionJob::factory()
        ->for($otherUser)
        ->for($otherFile, 'sourceFile')
        ->create([
            'source_format' => 'png',
            'target_format' => 'jpg',
        ]);

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->assertSee('own-image.png')
        ->assertDontSee('other-image.png');
});
```

Тест должен упасть до реализации query/render.

### Implementation

Только добавить тест.

Если factories/relations называются иначе, адаптировать под фактические модели.

### Acceptance criteria

- Тест существует.
- Тест проверяет own conversion visible.
- Тест проверяет other user conversion hidden.
- Тест падает до CONV-350.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-349: Test history shows current user conversions`

### Files likely touched

```txt
tests/Feature/Livewire/ConversionHistoryTableTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если ожидаемо падает только новый тест или команда явно запускает targeted test для проверки TDD step.

---

## CONV-350 — Implement User-Scoped History Query

**Area:** History / Livewire / Query  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-350-implement-user-scoped-history-query`  
**Base branch:** `develop`  
**Depends on:** CONV-349

### Goal

Реализовать user-scoped query для истории конвертаций.

### TDD step

Использовать падающий тест из CONV-349.

### Implementation

В `ConversionHistoryTable` использовать query:

```php
public function jobs()
{
    return ConversionJob::query()
        ->where('user_id', auth()->id())
        ->with(['sourceFile', 'resultFile', 'creditCharge'])
        ->latest()
        ->paginate(15);
}
```

В Blade вывести минимум:

```blade
@foreach ($this->jobs as $job)
    <div>{{ $job->sourceFile?->original_name }}</div>
@endforeach
```

Если Livewire computed properties используются:

```php
#[Computed]
public function jobs(): LengthAwarePaginator
```

### Acceptance criteria

- Current user sees own jobs.
- Current user does not see other users jobs.
- Query eager-loads sourceFile/resultFile/creditCharge.
- Pagination exists with default page size 15.
- CONV-349 test passes.

### Definition of Done

- Query implemented.
- Basic render implemented.
- Tests pass.
- Коммит: `CONV-350: Implement user-scoped history query`

### Files likely touched

```txt
app/Livewire/ConversionHistoryTable.php
resources/views/livewire/conversion-history-table.blade.php
tests/Feature/Livewire/ConversionHistoryTableTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-351 — Add History Table Columns

**Area:** History / Table  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-351-add-history-table-columns`  
**Base branch:** `develop`  
**Depends on:** CONV-350

### Goal

Добавить полноценные колонки таблицы history.

### TDD step

Feature/Livewire test:

```php
it('renders conversion history table columns', function () {
    $user = User::factory()->create();

    $source = FileRecord::factory()->for($user)->create([
        'original_name' => 'photo.png',
        'size_bytes' => 102400,
    ]);

    $result = FileRecord::factory()->for($user)->create([
        'original_name' => 'photo.jpg',
        'size_bytes' => 51200,
    ]);

    ConversionJob::factory()->for($user)->create([
        'source_file_id' => $source->id,
        'result_file_id' => $result->id,
        'source_format' => 'png',
        'target_format' => 'jpg',
        'status' => ConversionStatus::Completed,
    ]);

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->assertSee('photo.png')
        ->assertSee('PNG')
        ->assertSee('JPG')
        ->assertSee('Completed');
});
```

### Implementation

Добавить table layout columns:

```txt
File Name
From
To
Size
Created
Completed
Status
Actions
```

Минимальный Blade:

```blade
<table>
    <thead>...</thead>
    <tbody>
        @foreach ($this->jobs as $job)
            <tr>
                <td>{{ $job->sourceFile?->original_name ?? '—' }}</td>
                <td>{{ strtoupper($job->source_format) }}</td>
                <td>{{ strtoupper($job->target_format) }}</td>
                <td>{{ $job->sourceFile?->human_size ?? '—' }}</td>
                <td>{{ $job->created_at->format('M d, Y H:i') }}</td>
                <td>{{ $job->completed_at?->format('M d, Y H:i') ?? '—' }}</td>
                <td>{{ $job->status->value }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
```

Если нет `human_size`, временно использовать helper или простой formatting method.

### Acceptance criteria

- Table header visible.
- File name visible.
- Source format visible.
- Target format visible.
- Size visible.
- Created date visible.
- Completed date visible or dash.
- Status visible.
- Tests pass.

### Definition of Done

- Тест написан.
- Table columns implemented.
- No N+1 regression.
- Tests pass.
- Коммит: `CONV-351: Add history table columns`

### Files likely touched

```txt
app/Livewire/ConversionHistoryTable.php
resources/views/livewire/conversion-history-table.blade.php
tests/Feature/Livewire/ConversionHistoryTableTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-352 — Add History Status Badges

**Area:** History / UI  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-352-add-history-status-badges`  
**Base branch:** `develop`  
**Depends on:** CONV-351

### Goal

Сделать отображение status через badge component с понятной цветовой схемой.

### TDD step

Render test:

```php
it('renders status badges in history table', function () {
    $user = User::factory()->create();

    ConversionJob::factory()->for($user)->completed()->create();
    ConversionJob::factory()->for($user)->failed()->create();

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->assertSee('Completed')
        ->assertSee('Failed');
});
```

CSS-класс можно не тестировать, если проект не тестирует верстку на этом уровне.

### Implementation

Добавить helper:

```php
public function statusLabel(ConversionJob $job): string
public function statusBadgeVariant(ConversionJob $job): string
```

Mapping:

```txt
queued     → purple/neutral
processing → warning
completed → success
failed    → danger
cancelled → neutral
expired   → neutral/danger muted
```

В Blade:

```blade
<x-badge :variant="$this->statusBadgeVariant($job)">
    {{ $this->statusLabel($job) }}
</x-badge>
```

### Acceptance criteria

- Status displayed as badge.
- All known statuses have mapping.
- Unknown status falls back safely.
- Completed/failed states visually distinct.
- Tests pass.

### Definition of Done

- Status badge mapping added.
- Tests pass.
- Коммит: `CONV-352: Add history status badges`

### Files likely touched

```txt
app/Livewire/ConversionHistoryTable.php
resources/views/livewire/conversion-history-table.blade.php
tests/Feature/Livewire/ConversionHistoryTableTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-353 — Test History Search Filter

**Area:** History / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-353-test-history-search-filter`  
**Base branch:** `develop`  
**Depends on:** CONV-352

### Goal

Написать падающий тест: search filter ищет по имени source/result файла и форматам.

### TDD step

Livewire test:

```php
it('filters history by search query', function () {
    $user = User::factory()->create();

    $matchingFile = FileRecord::factory()->for($user)->create([
        'original_name' => 'marketing-banner.png',
    ]);

    $otherFile = FileRecord::factory()->for($user)->create([
        'original_name' => 'invoice.pdf',
    ]);

    ConversionJob::factory()->for($user)->for($matchingFile, 'sourceFile')->create([
        'source_format' => 'png',
        'target_format' => 'jpg',
    ]);

    ConversionJob::factory()->for($user)->for($otherFile, 'sourceFile')->create([
        'source_format' => 'pdf',
        'target_format' => 'jpg',
    ]);

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->set('search', 'marketing')
        ->assertSee('marketing-banner.png')
        ->assertDontSee('invoice.pdf');
});
```

Добавить format search test желательно:

```php
->set('search', 'png')
```

### Implementation

Только добавить тест.

### Acceptance criteria

- Test covers filename search.
- Test confirms non-matching rows hidden.
- Optional test covers format search.
- Test fails before implementation.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-353: Test history search filter`

### Files likely touched

```txt
tests/Feature/Livewire/ConversionHistoryTableFiltersTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если новый targeted test ожидаемо падает до CONV-354.

---

## CONV-354 — Implement History Search Filter

**Area:** History / Filter  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-354-implement-history-search-filter`  
**Base branch:** `develop`  
**Depends on:** CONV-353

### Goal

Реализовать search filter для history table.

### TDD step

Использовать падающий тест из CONV-353.

### Implementation

В `ConversionHistoryTable` добавить public property:

```php
public string $search = '';
```

При изменении search сбрасывать пагинацию:

```php
public function updatedSearch(): void
{
    $this->resetPage();
}
```

Query:

```php
->when(trim($this->search) !== '', function (Builder $query) {
    $search = '%' . trim($this->search) . '%';

    $query->where(function (Builder $query) use ($search) {
        $query
            ->where('source_format', 'like', $search)
            ->orWhere('target_format', 'like', $search)
            ->orWhereHas('sourceFile', fn (Builder $q) =>
                $q->where('original_name', 'like', $search)
            )
            ->orWhereHas('resultFile', fn (Builder $q) =>
                $q->where('original_name', 'like', $search)
            );
    });
})
```

В Blade добавить input:

```blade
<input
    type="search"
    wire:model.live.debounce.300ms="search"
    placeholder="Search files or formats..."
/>
```

### Acceptance criteria

- Search input exists.
- Search filters by source file name.
- Search filters by result file name.
- Search filters by source/target format.
- Pagination resets on search change.
- Tests pass.

### Definition of Done

- Search implemented.
- Tests pass.
- Коммит: `CONV-354: Implement history search filter`

### Files likely touched

```txt
app/Livewire/ConversionHistoryTable.php
resources/views/livewire/conversion-history-table.blade.php
tests/Feature/Livewire/ConversionHistoryTableFiltersTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-355 — Test History Status Filter

**Area:** History / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-355-test-history-status-filter`  
**Base branch:** `develop`  
**Depends on:** CONV-354

### Goal

Написать падающий тест: status filter показывает только выбранный status.

### TDD step

Livewire test:

```php
it('filters history by status', function () {
    $user = User::factory()->create();

    $completed = ConversionJob::factory()->for($user)->completed()->create();
    $failed = ConversionJob::factory()->for($user)->failed()->create();

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->set('status', 'completed')
        ->assertSee($completed->sourceFile?->original_name)
        ->assertDontSee($failed->sourceFile?->original_name);
});
```

Если factories не создают sourceFile automatically, создать явно.

### Implementation

Только добавить тест.

### Acceptance criteria

- Test covers completed filter.
- Test confirms failed hidden.
- Test fails before implementation.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-355: Test history status filter`

### Files likely touched

```txt
tests/Feature/Livewire/ConversionHistoryTableFiltersTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если новый targeted test ожидаемо падает до CONV-356.

---

## CONV-356 — Implement History Status Filter

**Area:** History / Filter  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-356-implement-history-status-filter`  
**Base branch:** `develop`  
**Depends on:** CONV-355

### Goal

Реализовать фильтр по status.

### TDD step

Использовать падающий тест из CONV-355.

### Implementation

Добавить property:

```php
public string $status = 'all';
```

Allowed statuses:

```php
public function statusOptions(): array
{
    return [
        'all' => 'All',
        'queued' => 'Queued',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
        'expired' => 'Expired',
    ];
}
```

Query:

```php
->when($this->status !== 'all', fn (Builder $query) =>
    $query->where('status', $this->status)
)
```

Blade:

```blade
<select wire:model.live="status">
    @foreach ($this->statusOptions() as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>
```

Reset page on update.

### Acceptance criteria

- Status select exists.
- All option shows all.
- Completed option shows completed only.
- Invalid status is not accepted or safely ignored.
- Pagination resets on change.
- Tests pass.

### Definition of Done

- Status filter implemented.
- Tests pass.
- Коммит: `CONV-356: Implement history status filter`

### Files likely touched

```txt
app/Livewire/ConversionHistoryTable.php
resources/views/livewire/conversion-history-table.blade.php
tests/Feature/Livewire/ConversionHistoryTableFiltersTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-357 — Test History Date Range Filter

**Area:** History / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-357-test-history-date-range-filter`  
**Base branch:** `develop`  
**Depends on:** CONV-356

### Goal

Написать падающий тест: history фильтруется по created_at date range.

### TDD step

Livewire test:

```php
it('filters history by date range', function () {
    $user = User::factory()->create();

    $insideFile = FileRecord::factory()->for($user)->create([
        'original_name' => 'inside-range.png',
    ]);

    $outsideFile = FileRecord::factory()->for($user)->create([
        'original_name' => 'outside-range.png',
    ]);

    ConversionJob::factory()->for($user)->for($insideFile, 'sourceFile')->create([
        'created_at' => now()->subDays(3),
    ]);

    ConversionJob::factory()->for($user)->for($outsideFile, 'sourceFile')->create([
        'created_at' => now()->subDays(30),
    ]);

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->set('dateFrom', now()->subDays(7)->toDateString())
        ->set('dateTo', now()->toDateString())
        ->assertSee('inside-range.png')
        ->assertDontSee('outside-range.png');
});
```

### Implementation

Только добавить тест.

### Acceptance criteria

- Test covers dateFrom/dateTo.
- Job inside range visible.
- Job outside range hidden.
- Test fails before implementation.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-357: Test history date range filter`

### Files likely touched

```txt
tests/Feature/Livewire/ConversionHistoryTableFiltersTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если targeted test ожидаемо падает до CONV-358.

---

## CONV-358 — Implement History Date Range Filter

**Area:** History / Filter  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-358-implement-history-date-range-filter`  
**Base branch:** `develop`  
**Depends on:** CONV-357

### Goal

Реализовать date range filter по `conversion_jobs.created_at`.

### TDD step

Использовать падающий тест из CONV-357.

### Implementation

Добавить properties:

```php
public ?string $dateFrom = null;
public ?string $dateTo = null;
```

Query:

```php
->when($this->dateFrom, fn (Builder $query) =>
    $query->whereDate('created_at', '>=', $this->dateFrom)
)
->when($this->dateTo, fn (Builder $query) =>
    $query->whereDate('created_at', '<=', $this->dateTo)
)
```

Blade:

```blade
<input type="date" wire:model.live="dateFrom">
<input type="date" wire:model.live="dateTo">
```

Basic validation:

```php
protected function rules(): array
{
    return [
        'dateFrom' => ['nullable', 'date'],
        'dateTo' => ['nullable', 'date', 'after_or_equal:dateFrom'],
    ];
}
```

Не блокировать страницу при invalid input; показывать validation message.

### Acceptance criteria

- Date inputs visible.
- dateFrom filters lower bound.
- dateTo filters upper bound.
- Invalid range handled cleanly.
- Pagination resets when date changes.
- Tests pass.

### Definition of Done

- Date range filter implemented.
- Tests pass.
- Коммит: `CONV-358: Implement history date range filter`

### Files likely touched

```txt
app/Livewire/ConversionHistoryTable.php
resources/views/livewire/conversion-history-table.blade.php
tests/Feature/Livewire/ConversionHistoryTableFiltersTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-359 — Test History Format Filters

**Area:** History / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-359-test-history-format-filters`  
**Base branch:** `develop`  
**Depends on:** CONV-358

### Goal

Написать падающие тесты для source format и target format filters.

### TDD step

Source format test:

```php
it('filters history by source format', function () {
    $user = User::factory()->create();

    $pngJob = ConversionJob::factory()->for($user)->create([
        'source_format' => 'png',
        'target_format' => 'jpg',
    ]);

    $jpgJob = ConversionJob::factory()->for($user)->create([
        'source_format' => 'jpg',
        'target_format' => 'webp',
    ]);

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->set('sourceFormat', 'png')
        ->assertSee($pngJob->sourceFile?->original_name)
        ->assertDontSee($jpgJob->sourceFile?->original_name);
});
```

Target format test:

```php
it('filters history by target format', function () {
    // same pattern for target_format
});
```

### Implementation

Только добавить тесты.

### Acceptance criteria

- Source format filter test exists.
- Target format filter test exists.
- Non-matching rows hidden.
- Tests fail before implementation.

### Definition of Done

- Тесты добавлены.
- Тесты ожидаемо падают.
- Коммит: `CONV-359: Test history format filters`

### Files likely touched

```txt
tests/Feature/Livewire/ConversionHistoryTableFiltersTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если targeted tests ожидаемо падают до CONV-360.

---

## CONV-360 — Implement History Format Filters

**Area:** History / Filter  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-360-implement-history-format-filters`  
**Base branch:** `develop`  
**Depends on:** CONV-359

### Goal

Реализовать filters по source format и target format.

### TDD step

Использовать падающие тесты из CONV-359.

### Implementation

Properties:

```php
public string $sourceFormat = 'all';
public string $targetFormat = 'all';
```

Options:

```php
public function formatOptions(): array
{
    return [
        'all' => 'All formats',
        'png' => 'PNG',
        'jpg' => 'JPG',
        'webp' => 'WEBP',
        'pdf' => 'PDF',
    ];
}
```

Query:

```php
->when($this->sourceFormat !== 'all', fn (Builder $query) =>
    $query->where('source_format', $this->sourceFormat)
)
->when($this->targetFormat !== 'all', fn (Builder $query) =>
    $query->where('target_format', $this->targetFormat)
)
```

Blade:

```blade
<select wire:model.live="sourceFormat">...</select>
<select wire:model.live="targetFormat">...</select>
```

Reset page on changes.

### Acceptance criteria

- Source format select exists.
- Target format select exists.
- Source filter works.
- Target filter works.
- Combined filters work reasonably.
- Pagination resets on change.
- Tests pass.

### Definition of Done

- Format filters implemented.
- Tests pass.
- Коммит: `CONV-360: Implement history format filters`

### Files likely touched

```txt
app/Livewire/ConversionHistoryTable.php
resources/views/livewire/conversion-history-table.blade.php
tests/Feature/Livewire/ConversionHistoryTableFiltersTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-361 — Add History Row Actions

**Area:** History / Actions  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-361-add-history-row-actions`  
**Base branch:** `develop`  
**Depends on:** CONV-360

### Goal

Добавить row actions: download result и convert again.

### TDD step

Download visibility test:

```php
it('shows download action only for completed non expired result', function () {
    $user = User::factory()->create();

    $result = FileRecord::factory()->for($user)->create([
        'original_name' => 'photo.jpg',
        'expires_at' => now()->addDay(),
        'status' => FileStatus::Analyzed,
    ]);

    $job = ConversionJob::factory()->for($user)->completed()->create([
        'result_file_id' => $result->id,
    ]);

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->assertSee('Download');
});
```

Expired result test:

```php
it('does not show active download action for expired result', function () {
    // completed job with expired result file
});
```

Convert again test:

```php
it('can prepare convert again for a job with available source file', function () {
    // action redirects to dashboard or emits event with job id
});
```

### Implementation

Download action:

```blade
@if ($this->canDownload($job))
    <a href="{{ route('conversions.download', $job) }}">Download</a>
@else
    <span title="Result is not available">Download unavailable</span>
@endif
```

Component helpers:

```php
public function canDownload(ConversionJob $job): bool
{
    return $job->status === ConversionStatus::Completed
        && $job->resultFile !== null
        && ! $job->resultFile->isExpired();
}
```

Convert Again action:

Option A — redirect:

```php
public function convertAgain(int $jobId): RedirectResponse
{
    $job = $this->findOwnedJob($jobId);

    if (! $this->canConvertAgain($job)) {
        $this->dispatch('toast', type: 'error', message: 'Original file expired. Upload it again.');
        return;
    }

    session()->put('convert_again_job_id', $job->id);

    return redirect()->route('dashboard');
}
```

Option B — query param:

```txt
/dashboard?repeat={jobId}
```

Preferred for MVP: session or query param, implemented only enough to connect with existing DashboardConverter behavior. Do not create conversion immediately.

### Acceptance criteria

- Download action visible for completed non-expired result.
- Download unavailable for failed/processing/expired result.
- Convert Again visible when source file exists and is not expired/deleted.
- Convert Again does not create new job immediately.
- User cannot act on another user's job.
- Tests pass.

### Definition of Done

- Tests written.
- Row actions added.
- Download route reused.
- Convert again behavior added without duplicating conversion logic.
- Tests pass.
- Коммит: `CONV-361: Add history row actions`

### Files likely touched

```txt
app/Livewire/ConversionHistoryTable.php
resources/views/livewire/conversion-history-table.blade.php
tests/Feature/Livewire/ConversionHistoryTableActionsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-362 — Add Credit Cost Column

**Area:** History / Billing / UI  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-362-add-credit-cost-column`  
**Base branch:** `develop`  
**Depends on:** CONV-361

### Goal

Показать в history фактически списанную стоимость конвертации в credits.

### TDD step

Livewire test:

```php
it('renders captured credit cost in history table', function () {
    $user = User::factory()->create();

    $job = ConversionJob::factory()->for($user)->completed()->create();

    ConversionCreditCharge::factory()->for($user)->for($job, 'conversionJob')->create([
        'captured_amount' => 2,
        'status' => 'captured',
    ]);

    Livewire::actingAs($user)
        ->test(ConversionHistoryTable::class)
        ->assertSee('2 credits');
});
```

No charge test:

```php
it('renders dash when job has no captured credit charge', function () {
    // assertSee('—') around Credits column if feasible
});
```

### Implementation

Relationship expected:

```php
ConversionJob::creditCharge()
```

If missing, add relation:

```php
public function creditCharge(): HasOne
{
    return $this->hasOne(ConversionCreditCharge::class);
}
```

Blade column:

```blade
<td>
    @if ($job->creditCharge?->captured_amount)
        {{ $job->creditCharge->captured_amount }} credits
    @else
        —
    @endif
</td>
```

Do not recalculate cost using `ConversionCostEstimator`.

### Acceptance criteria

- Credits column visible.
- Completed charged job shows captured amount.
- Failed/no charge job shows dash.
- Uses historical `captured_amount`.
- Does not call CostEstimator from table.
- Tests pass.

### Definition of Done

- Test written.
- Credits column added.
- Relation added if missing.
- Tests pass.
- Коммит: `CONV-362: Add credit cost column`

### Files likely touched

```txt
app/Models/ConversionJob.php
app/Livewire/ConversionHistoryTable.php
resources/views/livewire/conversion-history-table.blade.php
tests/Feature/Livewire/ConversionHistoryTableTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-363 — Add History Page Final Smoke Tests

**Area:** History / Tests / QA  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-363-add-history-page-final-smoke-tests`  
**Base branch:** `develop`  
**Depends on:** CONV-362

### Goal

Добавить финальные smoke tests для Phase 22 и убедиться, что history page работает как законченная фича.

### TDD step

Full page smoke test:

```php
it('renders full history page with table filters and actions', function () {
    $user = User::factory()->create();

    $source = FileRecord::factory()->for($user)->create([
        'original_name' => 'final-smoke.png',
    ]);

    $result = FileRecord::factory()->for($user)->create([
        'original_name' => 'final-smoke.jpg',
        'expires_at' => now()->addDay(),
    ]);

    $job = ConversionJob::factory()->for($user)->completed()->create([
        'source_file_id' => $source->id,
        'result_file_id' => $result->id,
        'source_format' => 'png',
        'target_format' => 'jpg',
    ]);

    ConversionCreditCharge::factory()->for($user)->for($job, 'conversionJob')->create([
        'captured_amount' => 1,
        'status' => 'captured',
    ]);

    $this->actingAs($user)
        ->get('/history')
        ->assertOk()
        ->assertSee('Conversion History')
        ->assertSee('final-smoke.png')
        ->assertSee('PNG')
        ->assertSee('JPG')
        ->assertSee('Completed')
        ->assertSee('1 credit');
});
```

Optional combined filter test:

```php
it('combines search status date and format filters without leaking other users data', ...);
```

### Implementation

Добавить smoke tests. Если обнаружены мелкие проблемы — исправить минимально в рамках Phase 22.

Также запустить:

```bash
composer test
composer lint
npm run build
```

### Acceptance criteria

- `/history` renders full page.
- Table renders own completed job.
- Filters do not break rendering.
- Credit cost visible.
- Download/convert again actions do not crash.
- Other users data not visible.
- Full test suite passes.
- Build passes.

### Definition of Done

- Final smoke tests added.
- All Phase 22 tests pass.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.
- Коммит: `CONV-363: Add history page final smoke tests`

### Files likely touched

```txt
tests/Feature/History/HistoryPageSmokeTest.php
tests/Feature/Livewire/ConversionHistoryTableTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 22 Completion Criteria

Phase 22 завершена, когда:

```txt
- CONV-347–CONV-363 выполнены;
- /history route exists;
- guest cannot access /history;
- authenticated user can access /history;
- ConversionHistoryTable exists;
- user sees only own conversion jobs;
- source/result file names render safely;
- source format and target format render;
- status badges render;
- search filter works;
- status filter works;
- date range filter works;
- source format filter works;
- target format filter works;
- pagination works;
- download action works only for completed non-expired results;
- expired results remain visible but cannot be downloaded;
- convert again is available only when source file is still available;
- credit cost column shows captured historical cost;
- no cost recalculation happens in history table;
- no other users conversion jobs leak;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 22

Без отдельной задачи нельзя:

```txt
- создавать My Files page;
- создавать folders;
- добавлять file rename/move/delete;
- добавлять admin history;
- добавлять charts/analytics;
- добавлять billing invoice history;
- добавлять credit transaction history page;
- добавлять API usage dashboard;
- добавлять CSV export;
- добавлять saved presets;
- добавлять favorites page;
- добавлять bulk actions;
- удалять ConversionJob rows;
- hard delete FileRecord rows;
- менять credit pricing;
- менять subscription plans;
- добавлять новые converters;
- добавлять webhooks;
- добавлять React/Vue/Inertia.
```

---

# 12. Recommended Execution Order

```txt
CONV-347 Create History Page Route And Shell
CONV-348 Create ConversionHistoryTable Component Skeleton
CONV-349 Test History Shows Current User Conversions
CONV-350 Implement User-Scoped History Query
CONV-351 Add History Table Columns
CONV-352 Add History Status Badges
CONV-353 Test History Search Filter
CONV-354 Implement History Search Filter
CONV-355 Test History Status Filter
CONV-356 Implement History Status Filter
CONV-357 Test History Date Range Filter
CONV-358 Implement History Date Range Filter
CONV-359 Test History Format Filters
CONV-360 Implement History Format Filters
CONV-361 Add History Row Actions
CONV-362 Add Credit Cost Column
CONV-363 Add History Page Final Smoke Tests
```

---

# 13. Release

После завершения Phase 22:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh --seed

git checkout -b release/v0.1.22-phase22-history-page
git push -u origin release/v0.1.22-phase22-history-page
```

После этого сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.22-phase22-history-page -m "File Converter Phase 22 history page"
git push origin v0.1.22-phase22-history-page
```
