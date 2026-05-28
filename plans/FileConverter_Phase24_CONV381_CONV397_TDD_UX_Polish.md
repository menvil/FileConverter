# File Converter — Phase 24 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 24 — UX Polish**  
Диапазон задач: **CONV-381 → CONV-397**  
Основа нумерации: Phase 23 завершилась на `CONV-380`, поэтому Phase 24 начинается с `CONV-381`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 24 соответствует блоку:

```txt
Phase 24 — UX Polish
```

Правильный диапазон Phase 24:

```txt
CONV-381 — Audit Current Dashboard UX States
CONV-382 — Test Upload Loading State
CONV-383 — Implement Upload Loading State
CONV-384 — Test Target Selection Loading State
CONV-385 — Implement Target Selection Loading State
CONV-386 — Test Convert Double Submit Guard
CONV-387 — Implement Convert Loading And Double Submit Guard
CONV-388 — Create Toast Notification Infrastructure
CONV-389 — Test Toast Events For Conversion Flow
CONV-390 — Implement Toast Events For Conversion Flow
CONV-391 — Improve Dashboard Empty States
CONV-392 — Improve History And Billing Empty States
CONV-393 — Add Accessibility Labels And Focus States
CONV-394 — Add Keyboard Interaction For Dropdowns And Steppers
CONV-395 — Add Responsive Dashboard Layout Pass
CONV-396 — Add Responsive Tables And Action Menus
CONV-397 — Add UX Polish Final Smoke Tests
```

Phase 24 не добавляет новую продуктовую функциональность.  
Она доводит уже реализованный MVP flow до состояния, которое не выглядит как сырой прототип.

Главное правило:

```txt
Polish means improving clarity, feedback, accessibility and responsiveness.
Polish does not mean adding new product modules.
```

---

# 2. Цель Phase 24

Phase 24 улучшает пользовательский опыт вокруг уже существующих сценариев:

```txt
- upload file;
- choose target format;
- configure options;
- convert;
- download result;
- view recent conversions;
- view history;
- view billing/credits;
- use user dropdown;
- navigate on desktop/tablet/mobile.
```

После Phase 24 пользователь должен понимать:

```txt
- что сейчас происходит;
- что можно нажать;
- что временно заблокировано;
- почему действие не сработало;
- куда двигаться дальше;
- как пользоваться интерфейсом клавиатурой;
- как интерфейс ведёт себя на небольшом экране.
```

Phase 24 не должна переписывать архитектуру, менять pricing model, добавлять новые конвертеры или подключать новые frontend frameworks.

---

# 3. Scope Phase 24

## Входит

```txt
- audit current dashboard UX states;
- upload loading state;
- target selection loading state;
- convert button loading state;
- double-submit guard;
- toast notification infrastructure;
- toast events for upload/conversion/download/billing-relevant states;
- dashboard empty states;
- history empty states;
- billing empty states;
- accessibility labels;
- visible focus states;
- keyboard interaction for dropdowns;
- keyboard interaction for stepper/format cards where practical;
- responsive dashboard layout;
- responsive tables;
- action menu improvements for small screens;
- final UX smoke tests.
```

## Не входит

```txt
- redesign of the whole product;
- new landing page;
- new converters;
- batch conversion;
- OCR page;
- tools hub;
- desktop app page;
- new billing model;
- Spike integration;
- advanced analytics;
- admin panel;
- API webhooks;
- real-time WebSocket updates;
- React/Vue/Inertia;
- Cypress/Playwright unless already installed;
- visual regression testing unless already adopted.
```

---

# 4. Critical Decisions

## 4.1. UX polish must be behavior-driven

Нельзя просто «добавить красивостей».

Каждое изменение должно улучшать одно из состояний:

```txt
idle
loading
success
error
empty
disabled
focused
mobile
```

Если изменение не улучшает состояние, оно не входит в Phase 24.

## 4.2. Loading states must prevent duplicate actions

Кнопки, которые запускают дорогие операции, должны блокироваться во время выполнения:

```txt
- upload;
- target selection if it triggers server state;
- convert;
- checkout redirect;
- buy credits redirect;
- save settings if touched by empty state links.
```

Особенно важно для `Convert Now`: двойной клик не должен создавать два `conversion_jobs`.

## 4.3. Toasts are feedback, not business logic

Toast — это UI notification layer.

Нельзя в toast-компоненте:

```txt
- создавать conversion job;
- списывать credits;
- менять status;
- вызывать billing actions;
- делать redirect logic кроме простого link CTA.
```

Правильно:

```txt
Action/Livewire component emits event → Toast UI displays message.
```

## 4.4. Empty states must contain next action

Плохой empty state:

```txt
No conversions found.
```

Хороший empty state:

```txt
No conversions yet.
Upload your first PNG or JPG file to start converting.
[Start conversion]
```

Каждый empty state должен иметь:

```txt
- короткий заголовок;
- объяснение;
- primary action или clear next step.
```

## 4.5. Accessibility is not optional

Phase 24 должна закрыть базовые проблемы:

```txt
- buttons have accessible names;
- form fields have labels;
- dropdowns are keyboard-accessible;
- focus states are visible;
- icons used as buttons have aria-label;
- loading states announce enough text visually;
- color is not the only status indicator.
```

Это не WCAG-аудит уровня enterprise, но базовая доступность должна быть.

## 4.6. Responsive means usable, not pixel-perfect

MVP не обязан быть идеальным на телефоне. Но он не должен разваливаться.

Минимум:

```txt
- dashboard can be used on laptop/tablet;
- mobile can upload and start a conversion;
- tables do not overflow destructively;
- action buttons remain reachable;
- header/user dropdown remains usable.
```

---

# 5. Architecture Rules

## 5.1. No new frontend framework

Запрещено добавлять:

```txt
React
Vue
Inertia
Next.js
Nuxt
```

Использовать:

```txt
Blade
Livewire
Alpine.js
Tailwind CSS
```

## 5.2. Keep UI state inside Livewire/Alpine boundaries

Правильно:

```txt
- server state: Livewire;
- tiny local interactions: Alpine;
- styling: Blade/Tailwind.
```

Неправильно:

```txt
- random inline scripts across Blade;
- duplicate state in Livewire and Alpine without sync;
- global JavaScript store for simple dropdowns;
- hidden business logic in Alpine.
```

## 5.3. No duplicate conversion prevention only in frontend

Frontend disabled button нужен, но недостаточен.

Для `Convert Now` guard должен быть и на backend/application side:

```txt
- Livewire blocks duplicate click;
- action/service avoids duplicate job creation for same in-flight state where applicable.
```

Если backend duplicate guard уже сделан раньше — Phase 24 только проверяет UI.  
Если его нет — CONV-387 должен добавить минимальную защиту в компоненте и не трогать глубокую доменную архитектуру без необходимости.

## 5.4. Components must stay reusable

Toast, empty state, loading button, responsive action menu — это reusable UI pieces.

Нельзя размазывать одноразовый HTML по всем страницам, если элемент повторяется.

## 5.5. Tests first where behavior is testable

Для loading/disabled states, empty states, accessibility text, duplicate guard — tests first.

Для чистого responsive CSS direct backend test невозможен. В таких задачах явно указывать:

```txt
No direct test — responsive CSS/manual UI pass.
```

---

# 6. GitFlow для Phase 24

## Base branch

Все задачи Phase 24 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-381-audit-current-dashboard-ux-states
feature/CONV-383-implement-upload-loading-state
feature/CONV-390-implement-toast-events-for-conversion-flow
```

## Commit format

```txt
CONV-381: Audit current dashboard UX states
CONV-383: Implement upload loading state
CONV-390: Implement toast events for conversion flow
```

## Release branch

После выполнения `CONV-381`–`CONV-397`:

```txt
release/v0.1.24-phase24-ux-polish
```

## Tag

После merge release branch в `main`:

```txt
v0.1.24-phase24-ux-polish
```

---

# 7. TDD Rules for Phase 24

## Для loading states

Тестировать:

```txt
- upload action exposes loading UI text/disabled state;
- convert button cannot create duplicate jobs;
- loading text appears during action where Livewire can assert it;
- buttons use wire:loading.attr="disabled" or equivalent.
```

## Для toast events

Тестировать:

```txt
- upload success dispatches toast event;
- conversion started dispatches toast event;
- conversion completed dispatches toast event where component observes status change;
- conversion failed dispatches error toast event;
- insufficient credits dispatches clear error toast/event.
```

## Для empty states

Тестировать:

```txt
- recent conversions empty state visible when no jobs;
- history empty state visible when no jobs;
- billing transaction empty state visible when no credit transactions;
- each empty state contains a useful CTA or next-step copy.
```

## Для accessibility

Тестировать там, где возможно:

```txt
- important buttons include accessible labels/text;
- icon-only actions include aria-label;
- form fields have labels;
- dropdown trigger has aria attributes if practical.
```

## Для responsive

Direct test обычно невозможен без browser testing.

Проверять вручную:

```txt
- desktop width;
- tablet width;
- mobile width;
- table overflow behavior;
- header/dropdown behavior.
```

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: UX / Livewire / Blade / Alpine / Tests / Accessibility
Type: Test / Feature / Polish / Refactor / Config
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

# 9. Phase 24 Atomic Tasks

---

## CONV-381 — Audit Current Dashboard UX States

**Area:** UX / Documentation  
**Type:** Polish / Audit  
**Priority:** P0  
**Branch:** `feature/CONV-381-audit-current-dashboard-ux-states`  
**Base branch:** `develop`  
**Depends on:** CONV-380

### Goal

Зафиксировать текущие UX-состояния dashboard/history/billing перед polish-задачами.

### TDD step

No direct test — audit/documentation task.

### Implementation

Создать короткий audit document:

```txt
docs/ux/phase24-ux-audit.md
```

Документ должен перечислить состояния:

```txt
Dashboard:
- upload empty;
- upload loading;
- file uploaded;
- target selection;
- settings;
- converting;
- completed;
- failed;
- insufficient credits.

Tables:
- recent conversions with rows;
- recent conversions empty;
- history empty;
- billing transactions empty.

Global:
- user dropdown;
- footer/help cards;
- mobile/tablet layout.
```

Для каждого состояния указать:

```txt
current behavior
problem
planned Phase 24 task
```

### Acceptance criteria

- Audit document created.
- Dashboard states listed.
- Table empty states listed.
- Accessibility/responsive risks listed.
- No production code changed unless needed for docs path.

### Definition of Done

- Audit document добавлен.
- Scope Phase 24 подтверждён.
- `composer test` проходит.
- Коммит: `CONV-381: Audit current dashboard UX states`

### Files likely touched

```txt
docs/ux/phase24-ux-audit.md
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-382 — Test Upload Loading State

**Area:** Livewire / Tests / UX  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-382-test-upload-loading-state`  
**Base branch:** `develop`  
**Depends on:** CONV-381

### Goal

Добавить падающий тест или render assertion, фиксирующий наличие upload loading UI.

### TDD step

Livewire/component render test:

```php
it('renders upload loading state hooks', function () {
    Livewire::test(DashboardConverter::class)
        ->assertSee('Uploading')
        ->assertSeeHtml('wire:loading')
        ->assertSeeHtml('wire:target="upload"');
});
```

Если точный HTML отличается, тест должен проверять стабильный пользовательский текст:

```txt
Uploading file...
```

Тест должен упасть до реализации CONV-383.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Тест проверяет наличие upload loading state text/hook.
- Тест падает до реализации.
- Нет production changes.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-382: Test upload loading state`

### Files likely touched

```txt
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён после подтверждения, что тест падает до CONV-383.

---

## CONV-383 — Implement Upload Loading State

**Area:** Livewire / Blade / UX  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-383-implement-upload-loading-state`  
**Base branch:** `develop`  
**Depends on:** CONV-382

### Goal

Добавить явное состояние загрузки файла в dashboard upload card.

### TDD step

Использовать падающий тест из CONV-382.

### Implementation

В upload card добавить:

```blade
<div wire:loading wire:target="uploadedFile">
    Uploading file...
</div>
```

или, если upload method называется иначе:

```blade
<div wire:loading wire:target="upload">
    Uploading file...
</div>
```

Добавить disabled state для upload-related buttons:

```blade
<button wire:loading.attr="disabled" wire:target="uploadedFile">
```

Добавить визуальный progress hint, если Livewire upload progress уже используется.

Не добавлять chunk upload.  
Не добавлять direct-to-S3 upload.

### Acceptance criteria

- During upload user sees `Uploading file...` or equivalent.
- Upload controls are disabled during upload.
- Existing upload success flow still works.
- Upload errors still render correctly.
- Test CONV-382 passes.

### Definition of Done

- Upload loading UI добавлен.
- Тест проходит.
- `composer test` проходит.
- `composer lint` проходит.
- `npm run build` проходит.
- Коммит: `CONV-383: Implement upload loading state`

### Files likely touched

```txt
resources/views/livewire/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-384 — Test Target Selection Loading State

**Area:** Livewire / Tests / UX  
**Type:** Test  
**Priority:** P1  
**Branch:** `feature/CONV-384-test-target-selection-loading-state`  
**Base branch:** `develop`  
**Depends on:** CONV-383

### Goal

Добавить тест, что при выборе target format есть loading/disabled feedback.

### TDD step

Livewire/render test:

```php
it('renders target selection loading state hooks', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->png()->for($user)->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('fileRecordId', $file->id)
        ->set('step', 'format')
        ->assertSee('Choose output format')
        ->assertSeeHtml('wire:target="selectTargetFormat"');
});
```

Если реализация использует другое имя метода, тест адаптировать к реальному имени.

Тест должен упасть до CONV-385.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест проверяет loading hook для target cards.
- Тест проверяет отсутствие silent click состояния.
- Тест падает до реализации.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-384: Test target selection loading state`

### Files likely touched

```txt
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён после подтверждения, что тест падает до CONV-385.

---

## CONV-385 — Implement Target Selection Loading State

**Area:** Livewire / Blade / UX  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-385-implement-target-selection-loading-state`  
**Base branch:** `develop`  
**Depends on:** CONV-384

### Goal

Добавить feedback при выборе целевого формата.

### TDD step

Использовать падающий тест из CONV-384.

### Implementation

Для target format cards добавить:

```blade
<button
    wire:click="selectTargetFormat('{{ $target }}')"
    wire:loading.attr="disabled"
    wire:target="selectTargetFormat"
>
```

Добавить небольшой loading text:

```blade
<span wire:loading wire:target="selectTargetFormat">
    Loading settings...
</span>
```

Не блокировать всю страницу, только область выбора формата.

### Acceptance criteria

- Target cards disabled while selection is processing.
- User sees `Loading settings...` or equivalent.
- Correct settings still load after selection.
- Back navigation still works.
- Test CONV-384 passes.

### Definition of Done

- Target selection loading UI добавлен.
- Тест проходит.
- `composer test` проходит.
- `composer lint` проходит.
- `npm run build` проходит.
- Коммит: `CONV-385: Implement target selection loading state`

### Files likely touched

```txt
resources/views/livewire/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-386 — Test Convert Double Submit Guard

**Area:** Livewire / Tests / Conversion  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-386-test-convert-double-submit-guard`  
**Base branch:** `develop`  
**Depends on:** CONV-385

### Goal

Написать тест, что повторный запуск `Convert Now` не создаёт duplicate conversion jobs.

### TDD step

Livewire/action test:

```php
it('does not create duplicate conversion jobs on repeated convert call', function () {
    Queue::fake();

    $user = User::factory()->create();
    $file = FileRecord::factory()->png()->for($user)->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->call('loadFile', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->set('options.quality', 'high')
        ->call('convert')
        ->call('convert');

    expect(ConversionJob::query()->where('user_id', $user->id)->count())->toBe(1);
});
```

Адаптировать к реальным методам компонента.

Тест должен упасть, если duplicate guard отсутствует.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Повторный convert call проверяется.
- Ожидается только один job.
- Тест падает до CONV-387, если guard отсутствует.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает или подтверждает уже существующий guard.
- Коммит: `CONV-386: Test convert double submit guard`

### Files likely touched

```txt
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён после проверки результата теста.

---

## CONV-387 — Implement Convert Loading And Double Submit Guard

**Area:** Livewire / UX / Conversion  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-387-implement-convert-loading-and-double-submit-guard`  
**Base branch:** `develop`  
**Depends on:** CONV-386

### Goal

Добавить loading state для Convert Now и минимальную защиту от duplicate submit.

### TDD step

Использовать тест из CONV-386.

### Implementation

В Livewire component добавить guard:

```php
public bool $isSubmittingConversion = false;

public function convert(): void
{
    if ($this->isSubmittingConversion || $this->currentJobId !== null) {
        return;
    }

    $this->isSubmittingConversion = true;

    try {
        // existing create conversion flow
    } finally {
        $this->isSubmittingConversion = false;
    }
}
```

Если `currentJobId` уже есть и step = converting, использовать существующий state.

В Blade:

```blade
<button
    wire:click="convert"
    wire:loading.attr="disabled"
    wire:target="convert"
>
    <span wire:loading.remove wire:target="convert">Convert Now</span>
    <span wire:loading wire:target="convert">Starting conversion...</span>
</button>
```

Не создавать новую application action.  
Не менять conversion job domain без необходимости.

### Acceptance criteria

- Double click does not create duplicate jobs.
- Convert button disabled while conversion starts.
- User sees `Starting conversion...` or equivalent.
- Existing conversion flow still works.
- Test CONV-386 passes.

### Definition of Done

- Convert loading UI добавлен.
- Duplicate guard добавлен.
- Тест проходит.
- `composer test` проходит.
- `composer lint` проходит.
- `npm run build` проходит.
- Коммит: `CONV-387: Implement convert loading and double submit guard`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
resources/views/livewire/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-388 — Create Toast Notification Infrastructure

**Area:** Blade / Alpine / UX  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-388-create-toast-notification-infrastructure`  
**Base branch:** `develop`  
**Depends on:** CONV-387

### Goal

Создать reusable toast notification layer для Livewire/Alpine событий.

### TDD step

Render test:

```php
it('renders toast notification container in app layout', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('toast-region', false);
});
```

Если route требует другие preconditions, использовать любую страницу с app layout.

### Implementation

Создать Blade partial/component:

```txt
resources/views/components/toast-region.blade.php
```

Пример поведения:

```blade
<div
    x-data="toastRegion()"
    x-on:toast.window="add($event.detail)"
    id="toast-region"
    aria-live="polite"
    aria-atomic="true"
>
    <!-- toasts -->
</div>
```

Добавить Alpine helper в `resources/js/app.js` или отдельный файл:

```js
window.toastRegion = function () {
    return {
        toasts: [],
        add(toast) { /* push + timeout */ },
        remove(id) { /* remove */ },
    }
}
```

Включить в app layout.

### Acceptance criteria

- Toast region exists in app layout.
- Toast region listens for `toast` window event.
- Supports success/error/info variants.
- Has `aria-live="polite"`.
- No business logic in toast component.
- Render test passes.

### Definition of Done

- Toast infrastructure создана.
- Тест проходит.
- `composer test` проходит.
- `composer lint` проходит.
- `npm run build` проходит.
- Коммит: `CONV-388: Create toast notification infrastructure`

### Files likely touched

```txt
resources/views/layouts/app.blade.php
resources/views/components/toast-region.blade.php
resources/js/app.js
tests/Feature/LayoutSmokeTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-389 — Test Toast Events For Conversion Flow

**Area:** Livewire / Tests / UX  
**Type:** Test  
**Priority:** P1  
**Branch:** `feature/CONV-389-test-toast-events-for-conversion-flow`  
**Base branch:** `develop`  
**Depends on:** CONV-388

### Goal

Добавить тесты, что DashboardConverter dispatches toast events для ключевых состояний.

### TDD step

Livewire tests:

```php
it('dispatches toast after successful upload', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(DashboardConverter::class)
        ->set('uploadedFile', UploadedFile::fake()->image('image.png'))
        ->call('upload')
        ->assertDispatched('toast');
});
```

```php
it('dispatches toast when conversion starts', function () {
    // arrange file + target + options

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->call('convert')
        ->assertDispatched('toast');
});
```

Если Livewire version uses browser events differently, adapt assertion to installed version.

### Implementation

Только добавить тесты.

### Acceptance criteria

- Upload success toast test exists.
- Conversion started toast test exists.
- Conversion failed/insufficient credits test added if current component can trigger it cleanly.
- Tests fail before implementation if events missing.

### Definition of Done

- Тесты добавлены.
- Тесты ожидаемо падают или фиксируют уже существующее событие.
- Коммит: `CONV-389: Test toast events for conversion flow`

### Files likely touched

```txt
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён после проверки тестов.

---

## CONV-390 — Implement Toast Events For Conversion Flow

**Area:** Livewire / UX  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-390-implement-toast-events-for-conversion-flow`  
**Base branch:** `develop`  
**Depends on:** CONV-389

### Goal

Подключить toast events к основным пользовательским событиям конвертации.

### TDD step

Использовать тесты из CONV-389.

### Implementation

В Livewire component dispatch browser events:

```php
$this->dispatch('toast', [
    'type' => 'success',
    'title' => 'File uploaded',
    'message' => 'Choose the output format to continue.',
]);
```

События:

```txt
upload success → success/info toast
conversion started → info toast
conversion completed → success toast
conversion failed → error toast
insufficient credits → error/warning toast
```

Не дублировать длинные ошибки, если они уже видны inline. Toast должен быть коротким.

### Acceptance criteria

- Upload success dispatches toast.
- Conversion started dispatches toast.
- Conversion failed dispatches toast.
- Insufficient credits dispatches readable toast if applicable.
- Inline errors still remain.
- Tests CONV-389 pass.

### Definition of Done

- Toast events добавлены.
- Тесты проходят.
- `composer test` проходит.
- `composer lint` проходит.
- `npm run build` проходит.
- Коммит: `CONV-390: Implement toast events for conversion flow`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-391 — Improve Dashboard Empty States

**Area:** UX / Blade / Livewire  
**Type:** Polish  
**Priority:** P1  
**Branch:** `feature/CONV-391-improve-dashboard-empty-states`  
**Base branch:** `develop`  
**Depends on:** CONV-390

### Goal

Улучшить empty states на dashboard: upload empty state и recent conversions empty state.

### TDD step

Feature/Livewire tests:

```php
it('shows helpful recent conversions empty state on dashboard', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(RecentConversionsTable::class)
        ->assertSee('No conversions yet')
        ->assertSee('Upload your first file');
});
```

Если RecentConversionsTable не тестируется отдельно, использовать dashboard page/component.

### Implementation

Для dashboard upload empty state:

```txt
Drop your file here
PNG, JPG, WEBP and PDF are supported in beta.
```

Для recent conversions empty state:

```txt
No conversions yet
Upload your first PNG or JPG file to start converting.
[Start conversion]
```

CTA должен scroll/focus к upload area или вести на dashboard, если empty state находится в другом компоненте.

### Acceptance criteria

- Dashboard upload empty state clear and honest.
- Recent conversions empty state has title, explanation, CTA.
- No fake “100+ formats” copy if MVP supports fewer formats.
- Test passes.

### Definition of Done

- Empty states улучшены.
- Тест проходит.
- `composer test` проходит.
- `composer lint` проходит.
- `npm run build` проходит.
- Коммит: `CONV-391: Improve dashboard empty states`

### Files likely touched

```txt
resources/views/livewire/dashboard-converter.blade.php
resources/views/livewire/recent-conversions-table.blade.php
tests/Feature/Livewire/RecentConversionsTableTest.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-392 — Improve History And Billing Empty States

**Area:** UX / Blade / Livewire  
**Type:** Polish  
**Priority:** P1  
**Branch:** `feature/CONV-392-improve-history-and-billing-empty-states`  
**Base branch:** `develop`  
**Depends on:** CONV-391

### Goal

Добавить полезные empty states для `/history` и `/billing`.

### TDD step

Feature tests:

```php
it('shows helpful empty state on history page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/history')
        ->assertOk()
        ->assertSee('No conversion history yet')
        ->assertSee('Start your first conversion');
});
```

```php
it('shows helpful empty state for credit transactions', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/billing')
        ->assertOk()
        ->assertSee('No credit transactions yet');
});
```

### Implementation

History empty state:

```txt
No conversion history yet
Once you convert files, every result will appear here.
[Start your first conversion]
```

Billing credit transactions empty state:

```txt
No credit transactions yet
Your credit grants, purchases and conversion charges will appear here.
```

Do not create fake sample transactions.

### Acceptance criteria

- `/history` empty state has useful next action.
- `/billing` transactions empty state explains future records.
- No fake data.
- Tests pass.

### Definition of Done

- Empty states добавлены.
- Тесты проходят.
- `composer test` проходит.
- `composer lint` проходит.
- `npm run build` проходит.
- Коммит: `CONV-392: Improve history and billing empty states`

### Files likely touched

```txt
resources/views/history/index.blade.php
resources/views/billing/index.blade.php
resources/views/livewire/history-table.blade.php
resources/views/livewire/credit-transactions-table.blade.php
tests/Feature/HistoryPageTest.php
tests/Feature/BillingPageTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-393 — Add Accessibility Labels And Focus States

**Area:** Accessibility / Blade / CSS  
**Type:** Polish  
**Priority:** P0  
**Branch:** `feature/CONV-393-add-accessibility-labels-and-focus-states`  
**Base branch:** `develop`  
**Depends on:** CONV-392

### Goal

Добавить базовую доступность для форм, action buttons, icon-only buttons и focus states.

### TDD step

Feature/render tests where practical:

```php
it('renders accessible labels for dashboard file input', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Upload file')
        ->assertSeeHtml('aria-label');
});
```

Не надо делать хрупкие тесты на каждый class. Фиксировать только важные accessible labels/text.

### Implementation

Проверить и добавить:

```txt
- label for file input;
- aria-label for icon-only download/open/star/delete buttons;
- aria-expanded/aria-controls for dropdown trigger where practical;
- visible focus classes on buttons/links/inputs;
- status badges include text, not only color;
- loading buttons contain text, not only spinner.
```

Добавить reusable focus utility classes через Blade components или Tailwind classes.

### Acceptance criteria

- File input has accessible label.
- Icon-only actions have aria-label.
- Buttons/links have visible focus state.
- Dropdown trigger has useful aria attributes.
- Status remains understandable without color.
- Tests pass where added.

### Definition of Done

- Accessibility labels добавлены.
- Focus states добавлены.
- Тесты проходят.
- `composer test` проходит.
- `composer lint` проходит.
- `npm run build` проходит.
- Коммит: `CONV-393: Add accessibility labels and focus states`

### Files likely touched

```txt
resources/views/components/button.blade.php
resources/views/components/file-icon.blade.php
resources/views/livewire/dashboard-converter.blade.php
resources/views/livewire/recent-conversions-table.blade.php
resources/views/components/user-dropdown.blade.php
resources/css/app.css
tests/Feature/AccessibilitySmokeTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-394 — Add Keyboard Interaction For Dropdowns And Steppers

**Area:** Accessibility / Alpine / UX  
**Type:** Polish  
**Priority:** P1  
**Branch:** `feature/CONV-394-add-keyboard-interaction-for-dropdowns-and-steppers`  
**Base branch:** `develop`  
**Depends on:** CONV-393

### Goal

Сделать user dropdown и основные интерактивные блоки пригодными для клавиатуры.

### TDD step

No direct test for keyboard behavior unless browser tests exist.

Добавить минимальный render test:

```php
it('renders keyboard accessible user dropdown trigger', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertSeeHtml('aria-expanded')
        ->assertSeeHtml('x-on:keydown.escape');
});
```

### Implementation

User dropdown:

```txt
- Enter/Space toggles dropdown if using button semantics;
- Escape closes dropdown;
- click outside closes dropdown;
- focus is not trapped incorrectly;
- menu links are reachable by Tab.
```

Target format cards:

```txt
- use <button>, not clickable <div>;
- focus state visible;
- selected state has text/badge, not only color.
```

Stepper:

```txt
- if steps are clickable, use buttons/links;
- if not clickable, mark as progress indicator without fake click behavior.
```

### Acceptance criteria

- User dropdown closes on Escape.
- User dropdown trigger is a real button.
- Target format cards are keyboard-focusable.
- Clickable divs are replaced with buttons/links where needed.
- Render test passes.

### Definition of Done

- Keyboard interaction improved.
- Тест проходит where applicable.
- `composer test` проходит.
- `composer lint` проходит.
- `npm run build` проходит.
- Коммит: `CONV-394: Add keyboard interaction for dropdowns and steppers`

### Files likely touched

```txt
resources/views/components/user-dropdown.blade.php
resources/views/livewire/dashboard-converter.blade.php
resources/views/components/stepper.blade.php
tests/Feature/AccessibilitySmokeTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-395 — Add Responsive Dashboard Layout Pass

**Area:** Responsive / Blade / CSS  
**Type:** Polish  
**Priority:** P0  
**Branch:** `feature/CONV-395-add-responsive-dashboard-layout-pass`  
**Base branch:** `develop`  
**Depends on:** CONV-394

### Goal

Сделать dashboard usable на desktop/tablet/mobile без полного редизайна.

### TDD step

No direct test — responsive CSS/manual UI pass.

Добавить только smoke test, если dashboard route не покрыт:

```php
it('renders dashboard page after responsive layout changes', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk();
});
```

### Implementation

Проверить и улучшить:

```txt
- header wraps correctly;
- upload/settings card stacks on smaller screens;
- target format cards become single-column on mobile;
- settings form controls do not overflow;
- footer help cards stack cleanly;
- spacing remains readable.
```

Использовать Tailwind breakpoints:

```txt
sm
md
lg
xl
```

Не вводить отдельную mobile-only страницу.

### Acceptance criteria

- Dashboard usable at desktop width.
- Dashboard usable at tablet width.
- Dashboard minimally usable at mobile width.
- Upload card and settings card stack correctly.
- No destructive horizontal overflow in main layout.
- `npm run build` passes.

### Definition of Done

- Responsive dashboard pass completed.
- Manual viewport check done.
- `composer test` проходит.
- `composer lint` проходит.
- `npm run build` проходит.
- Коммит: `CONV-395: Add responsive dashboard layout pass`

### Files likely touched

```txt
resources/views/layouts/app.blade.php
resources/views/livewire/dashboard-converter.blade.php
resources/views/components/footer-help-cards.blade.php
resources/css/app.css
tests/Feature/DashboardRouteTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-396 — Add Responsive Tables And Action Menus

**Area:** Responsive / Tables / UX  
**Type:** Polish  
**Priority:** P1  
**Branch:** `feature/CONV-396-add-responsive-tables-and-action-menus`  
**Base branch:** `develop`  
**Depends on:** CONV-395

### Goal

Сделать таблицы Recent Conversions, History, Credit Transactions usable на небольших экранах.

### TDD step

Feature smoke tests:

```php
it('renders recent conversions table after responsive changes', function () {
    $user = User::factory()->create();
    ConversionJob::factory()->for($user)->completed()->create();

    Livewire::actingAs($user)
        ->test(RecentConversionsTable::class)
        ->assertSee('File Name')
        ->assertSee('Actions');
});
```

### Implementation

Варианты допустимые для MVP:

```txt
Option A: horizontal scroll container on mobile;
Option B: card-style rows on mobile;
Option C: hide low-priority columns and keep actions menu.
```

Рекомендация для MVP:

```txt
- desktop: normal table;
- mobile: horizontal scroll + sticky/visible action menu;
- icon-only actions get aria-labels from CONV-393.
```

Для actions на mobile можно сделать dropdown:

```txt
More → Download / Convert again / Delete if available
```

Не переписывать таблицы на JavaScript grid.

### Acceptance criteria

- Recent conversions table does not break mobile layout.
- History table does not break mobile layout.
- Credit transactions table does not break mobile layout.
- Actions remain reachable.
- Smoke tests pass.

### Definition of Done

- Responsive table behavior improved.
- Manual viewport check done.
- `composer test` проходит.
- `composer lint` проходит.
- `npm run build` проходит.
- Коммит: `CONV-396: Add responsive tables and action menus`

### Files likely touched

```txt
resources/views/livewire/recent-conversions-table.blade.php
resources/views/livewire/history-table.blade.php
resources/views/livewire/credit-transactions-table.blade.php
resources/views/components/table-action-menu.blade.php
resources/css/app.css
tests/Feature/Livewire/RecentConversionsTableTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-397 — Add UX Polish Final Smoke Tests

**Area:** Tests / QA  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-397-add-ux-polish-final-smoke-tests`  
**Base branch:** `develop`  
**Depends on:** CONV-396

### Goal

Добавить финальные smoke tests, подтверждающие, что Phase 24 не сломала основные MVP-страницы и UI states.

### TDD step

Feature tests:

```php
it('renders main mvp pages after ux polish', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();
    $this->actingAs($user)->get('/history')->assertOk();
    $this->actingAs($user)->get('/billing')->assertOk();
    $this->actingAs($user)->get('/settings')->assertOk();
});
```

Livewire smoke:

```php
it('renders dashboard converter with polish UI elements', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(DashboardConverter::class)
        ->assertSee('Drop your file')
        ->assertSee('Uploading', false)
        ->assertSee('Convert');
});
```

### Implementation

Добавить/обновить smoke tests.

Запустить полный gate:

```bash
composer test
composer lint
npm run build
```

Обновить audit document CONV-381, добавив final checklist:

```txt
Phase 24 final check: done
```

### Acceptance criteria

- Main MVP pages render.
- DashboardConverter renders expected polish elements.
- Existing conversion happy path still passes.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.
- UX audit document updated with final result.

### Definition of Done

- Final smoke tests added.
- Full quality gate passes.
- Audit document updated.
- Коммит: `CONV-397: Add UX polish final smoke tests`

### Files likely touched

```txt
tests/Feature/UxPolishSmokeTest.php
tests/Feature/Livewire/DashboardConverterTest.php
docs/ux/phase24-ux-audit.md
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 24 Completion Criteria

Phase 24 завершена, когда:

```txt
- CONV-381–CONV-397 выполнены;
- UX audit document exists;
- upload loading state visible;
- target selection loading state visible;
- Convert Now has loading state;
- Convert Now double submit does not create duplicate jobs;
- toast infrastructure exists;
- upload/conversion events dispatch toasts;
- dashboard empty states are useful;
- history empty state is useful;
- billing empty states are useful;
- important icon-only actions have aria-labels;
- file input has accessible label;
- focus states are visible;
- user dropdown is keyboard usable;
- dashboard is usable on desktop/tablet/mobile;
- tables do not destructively overflow on small screens;
- final smoke tests pass;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 24

Без отдельной задачи нельзя:

```txt
- добавлять новые конвертеры;
- добавлять batch conversion;
- добавлять OCR;
- добавлять tools page;
- добавлять desktop app page;
- менять billing model;
- менять credit pricing;
- подключать Spike;
- добавлять Stripe features;
- менять API contract;
- добавлять webhooks;
- добавлять admin panel;
- добавлять React/Vue/Inertia;
- переписывать dashboard architecture;
- делать полный redesign;
- добавлять browser testing framework без отдельного решения.
```

---

# 12. Recommended Execution Order

```txt
CONV-381 Audit Current Dashboard UX States
CONV-382 Test Upload Loading State
CONV-383 Implement Upload Loading State
CONV-384 Test Target Selection Loading State
CONV-385 Implement Target Selection Loading State
CONV-386 Test Convert Double Submit Guard
CONV-387 Implement Convert Loading And Double Submit Guard
CONV-388 Create Toast Notification Infrastructure
CONV-389 Test Toast Events For Conversion Flow
CONV-390 Implement Toast Events For Conversion Flow
CONV-391 Improve Dashboard Empty States
CONV-392 Improve History And Billing Empty States
CONV-393 Add Accessibility Labels And Focus States
CONV-394 Add Keyboard Interaction For Dropdowns And Steppers
CONV-395 Add Responsive Dashboard Layout Pass
CONV-396 Add Responsive Tables And Action Menus
CONV-397 Add UX Polish Final Smoke Tests
```

---

# 13. Release

После завершения Phase 24:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build

php artisan migrate:fresh --seed

composer test
npm run build

git checkout -b release/v0.1.24-phase24-ux-polish
git push -u origin release/v0.1.24-phase24-ux-polish
```

После этого шага сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.24-phase24-ux-polish -m "File Converter Phase 24 UX polish"
git push origin v0.1.24-phase24-ux-polish
```
