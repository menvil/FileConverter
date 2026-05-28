# File Converter — Phase 1 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 1 — UI Foundation**  
Диапазон задач: **CONV-009 → CONV-020**  
Основа нумерации: Phase 0 завершила `CONV-001 → CONV-008`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 1 соответствует блоку:

```txt
Phase 1 — UI Foundation
```

Правильный диапазон Phase 1:

```txt
CONV-009 — Create Base App Layout
CONV-010 — Add Design Tokens
CONV-011 — Build Button Component
CONV-012 — Build Card Component
CONV-013 — Build Badge Component
CONV-014 — Build FileIcon Component
CONV-015 — Build Stepper Component
CONV-016 — Build Form Control Components
CONV-017 — Build User Dropdown UI Shell
CONV-018 — Build Footer Help Cards
CONV-019 — Build Table Shell Component
CONV-020 — Compose Dashboard UI Skeleton
```

Phase 1 создаёт визуальный фундамент приложения: layout, дизайн-токены, базовые Blade-компоненты и статический dashboard skeleton.

Важно: Phase 1 не создаёт upload logic, auth, conversion logic, billing, API, storage models или Livewire state machine.

---

# 2. Цель Phase 1

Phase 1 должна подготовить UI foundation, на котором следующие фазы смогут строить dashboard, upload flow, history table, billing page и API keys page без хаотичного копирования Tailwind-классов.

После Phase 1 должно быть готово:

```txt
- общий app layout;
- header shell;
- footer shell;
- CSS design tokens;
- Button Blade component;
- Card Blade component;
- Badge Blade component;
- FileIcon Blade component;
- Stepper Blade component;
- базовые form controls;
- User dropdown UI shell на Alpine;
- Footer help cards;
- Table shell component;
- dashboard UI skeleton без бизнес-логики.
```

Эта фаза отвечает за внешний каркас и повторно используемые UI primitives.

---

# 3. Scope Phase 1

## Входит

```txt
- resources/views/layouts/app.blade.php;
- header markup;
- footer markup;
- CSS variables for File Converter theme;
- reusable Blade components;
- render tests for Blade components where practical;
- Alpine-only dropdown behavior;
- static dashboard skeleton;
- Tailwind class conventions;
- light responsive pass for layout shell.
```

## Не входит

```txt
- Laravel Breeze / auth;
- real user data;
- upload form behavior;
- Livewire upload;
- ConverterRegistry;
- DynamicOptionsForm;
- RecentConversionsTable with data;
- billing;
- credits;
- Cashier;
- API;
- storage;
- image conversion;
- queues;
- database migrations except if absolutely needed for tests, which should not be needed here.
```

Dashboard skeleton может показывать статические placeholders, но не должен имитировать завершённую бизнес-логику.

---

# 4. Critical Decisions

## 4.1. UI first, but not fake product logic

Phase 1 может создать внешний вид upload card, stepper и table shell, но нельзя добавлять реальные сценарии:

```txt
upload → detect → choose format → convert
```

Это будет в следующих фазах.

Разрешено:

```txt
- static dashboard skeleton;
- placeholder upload card;
- static stepper;
- static table empty state.
```

Запрещено:

```txt
- storing files;
- detecting formats;
- creating conversion jobs;
- polling;
- download route;
- credit calculation.
```

## 4.2. Blade components are preferred over Tailwind duplication

Если один и тот же визуальный паттерн появляется больше одного раза, он должен стать Blade-компонентом.

Неправильно:

```blade
<button class="...long tailwind classes...">Convert</button>
<button class="...same long tailwind classes...">Upload</button>
```

Правильно:

```blade
<x-button variant="gradient">Convert</x-button>
<x-button variant="primary">Upload</x-button>
```

## 4.3. No frontend framework beyond Alpine

В Phase 1 нельзя добавлять:

```txt
React
Vue
Inertia
Stimulus
HTMX
```

Alpine достаточно для dropdown, small toggles, mobile nav, collapse.

## 4.4. Design tokens must be stable

Цвета, радиусы, тени и базовые spacing constants должны быть зафиксированы через CSS variables. Без этого дизайн быстро расползётся.

## 4.5. Components must be boring and composable

Компоненты не должны знать о доменной логике.

`FileIcon` может знать про формат `png`, `jpg`, `webp`, `pdf`.

Но `Button`, `Card`, `Badge`, `Stepper`, `TableShell` не должны знать про conversions, credits или users.

---

# 5. Architecture Rules

## 5.1. Component location

Blade components:

```txt
resources/views/components/button.blade.php
resources/views/components/card.blade.php
resources/views/components/badge.blade.php
resources/views/components/file-icon.blade.php
resources/views/components/stepper.blade.php
resources/views/components/form/*
resources/views/components/table/*
```

Если класс-based Blade components понадобятся позже, можно добавить их отдельно. Для MVP лучше начинать с anonymous Blade components.

## 5.2. Layout location

Layout:

```txt
resources/views/layouts/app.blade.php
```

Dashboard placeholder:

```txt
resources/views/dashboard.blade.php
```

## 5.3. Theme CSS location

```txt
resources/css/theme.css
resources/css/app.css
```

`theme.css` должен импортироваться в `app.css`.

## 5.4. Do not introduce UI libraries

Не добавлять готовые component libraries:

```txt
Filament
Flux UI
Mary UI
DaisyUI
Preline
Flowbite
```

Иначе MVP будет зависеть от чужого визуального слоя. Исключение — если позже будет осознанное решение.

## 5.5. Accessibility is not optional

Минимум:

```txt
- buttons have type;
- inputs have labels;
- dropdown can be closed;
- focus-visible styles exist;
- aria-expanded for dropdown shell;
- decorative icons use aria-hidden.
```

---

# 6. GitFlow для Phase 1

## Base branch

Все задачи Phase 1 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-009-create-base-app-layout
feature/CONV-011-build-button-component
feature/CONV-020-compose-dashboard-ui-skeleton
```

## Commit format

```txt
CONV-009: Create base app layout
CONV-011: Build Button component
CONV-020: Compose dashboard UI skeleton
```

## Release branch

После выполнения `CONV-009`–`CONV-020`:

```txt
release/v0.1.1-phase1-ui-foundation
```

## Tag

После merge release branch в `main`:

```txt
v0.1.1-phase1-ui-foundation
```

---

# 7. TDD Rules for Phase 1

## Для layout

Тестировать:

```txt
- dashboard route uses app layout;
- header text/logo visible;
- footer visible.
```

## Для Blade components

Тестировать render output там, где это даёт пользу:

```txt
- button renders variant classes/content;
- badge renders status text;
- file icon renders known format label;
- file icon has fallback for unknown format;
- stepper renders active step.
```

## Для Alpine behavior

Не нужно делать тяжёлые браузерные тесты в MVP. Для dropdown достаточно:

```txt
- markup contains x-data;
- button contains aria-expanded binding;
- dropdown menu exists.
```

Browser tests можно добавить позже.

## Для dashboard skeleton

Тестировать:

```txt
- dashboard renders upload placeholder;
- dashboard renders stepper labels;
- dashboard renders recent conversions placeholder;
- dashboard has no real data dependency.
```

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: UI / Blade / CSS / Layout / Tests
Type: Feature / Component / Test / Config
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

# 9. Phase 1 Atomic Tasks

---

## CONV-009 — Create Base App Layout

**Area:** UI / Layout  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-009-create-base-app-layout`  
**Base branch:** `develop`  
**Depends on:** CONV-008

### Goal

Создать основной application layout для будущего dashboard и публичных страниц.

### TDD step

Feature test:

```php
it('renders dashboard inside the app layout', function () {
    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('ConvertAI')
        ->assertSee('File Converter Dashboard')
        ->assertSee('Privacy Policy');
});
```

Тест должен упасть до обновления layout/dashboard view.

### Implementation

Создать или обновить:

```txt
resources/views/layouts/app.blade.php
resources/views/dashboard.blade.php
```

Layout должен содержать:

```txt
- html/head/body;
- @vite(['resources/css/app.css', 'resources/js/app.js']);
- header shell;
- main slot/content;
- footer shell.
```

Если используется Blade component layout:

```blade
<x-layouts.app>
    ...
</x-layouts.app>
```

Если обычный layout:

```blade
@extends('layouts.app')

@section('content')
    ...
@endsection
```

На этом шаге header/footer минимальные, без полноценного меню.

### Acceptance criteria

- `resources/views/layouts/app.blade.php` существует.
- `/dashboard` использует app layout.
- Header содержит brand `ConvertAI` или выбранное имя продукта.
- Footer содержит базовые ссылки.
- `composer test` проходит.
- `npm run build` проходит.

### Definition of Done

- Тест написан первым.
- Layout создан.
- Dashboard подключён к layout.
- Test passes.
- Коммит: `CONV-009: Create base app layout`

### Files likely touched

```txt
resources/views/layouts/app.blade.php
resources/views/dashboard.blade.php
tests/Feature/DashboardRouteTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-010 — Add Design Tokens

**Area:** UI / CSS  
**Type:** Config  
**Priority:** P0  
**Branch:** `feature/CONV-010-add-design-tokens`  
**Base branch:** `develop`  
**Depends on:** CONV-009

### Goal

Добавить базовые CSS design tokens для светлого SaaS-интерфейса File Converter.

### TDD step

No direct backend test — CSS tokens setup.

Проверка:

```bash
npm run build
composer test
```

### Implementation

Создать:

```txt
resources/css/theme.css
```

Добавить tokens:

```css
:root {
    --ca-bg: #f8fafc;
    --ca-surface: #ffffff;
    --ca-surface-muted: #f1f5f9;
    --ca-text: #111827;
    --ca-muted: #64748b;
    --ca-border: #e2e8f0;
    --ca-primary: #7c3aed;
    --ca-primary-strong: #6d28d9;
    --ca-accent: #f97316;
    --ca-success: #16a34a;
    --ca-warning: #f59e0b;
    --ca-danger: #dc2626;
    --ca-radius-sm: 0.5rem;
    --ca-radius-md: 0.75rem;
    --ca-radius-lg: 1rem;
    --ca-radius-xl: 1.5rem;
    --ca-shadow-card: 0 18px 45px rgba(15, 23, 42, 0.08);
}
```

Import in `resources/css/app.css`:

```css
@import './theme.css';
```

Можно добавить utility classes:

```css
.ca-gradient-primary { ... }
.ca-focus-ring { ... }
```

Не перегружать файл сотнями токенов.

### Acceptance criteria

- `theme.css` существует.
- `app.css` импортирует `theme.css`.
- Tokens используются хотя бы в layout или dashboard placeholder.
- `npm run build` проходит.
- `composer test` проходит.
- Нет больших неиспользуемых token blocks.

### Definition of Done

- Theme tokens добавлены.
- Build проходит.
- Tests pass.
- Коммит: `CONV-010: Add design tokens`

### Files likely touched

```txt
resources/css/theme.css
resources/css/app.css
resources/views/layouts/app.blade.php
resources/views/dashboard.blade.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-011 — Build Button Component

**Area:** UI / Blade Components  
**Type:** Component  
**Priority:** P0  
**Branch:** `feature/CONV-011-build-button-component`  
**Base branch:** `develop`  
**Depends on:** CONV-010

### Goal

Создать переиспользуемый Blade-компонент кнопки.

### TDD step

Render test:

```php
it('renders a primary button component', function () {
    $view = $this->blade('<x-button variant="primary">Upload</x-button>');

    $view->assertSee('Upload');
    $view->assertSee('type="button"', false);
});
```

Variant test:

```php
it('renders a gradient button variant', function () {
    $view = $this->blade('<x-button variant="gradient">Convert Now</x-button>');

    $view->assertSee('Convert Now');
    $view->assertSee('ca-gradient', false);
});
```

### Implementation

Создать:

```txt
resources/views/components/button.blade.php
```

Props:

```blade
@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'disabled' => false,
])
```

Variants:

```txt
primary
secondary
ghost
gradient
danger
```

Sizes:

```txt
sm
md
lg
```

Button must support attributes merge:

```blade
<button {{ $attributes->merge([...]) }}>
    {{ $slot }}
</button>
```

### Acceptance criteria

- `<x-button>` exists.
- Supports variant.
- Supports size.
- Supports disabled.
- Default type is `button`, not implicit submit.
- Attributes merge works.
- Render tests pass.

### Definition of Done

- Тесты написаны.
- Component создан.
- Tests pass.
- Коммит: `CONV-011: Build Button component`

### Files likely touched

```txt
resources/views/components/button.blade.php
tests/Feature/ViewComponents/ButtonComponentTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-012 — Build Card Component

**Area:** UI / Blade Components  
**Type:** Component  
**Priority:** P0  
**Branch:** `feature/CONV-012-build-card-component`  
**Base branch:** `develop`  
**Depends on:** CONV-011

### Goal

Создать универсальный `<x-card>` для dashboard sections, upload card, billing cards и help cards.

### TDD step

Render test:

```php
it('renders a card component with content', function () {
    $view = $this->blade('<x-card>Card content</x-card>');

    $view->assertSee('Card content');
});
```

Variant test:

```php
it('renders an elevated card variant', function () {
    $view = $this->blade('<x-card variant="elevated">Panel</x-card>');

    $view->assertSee('Panel');
    $view->assertSee('shadow', false);
});
```

### Implementation

Создать:

```txt
resources/views/components/card.blade.php
```

Props:

```txt
variant: default/elevated/interactive/gradient
padding: none/sm/md/lg
```

Card должен быть обычным контейнером без бизнес-логики.

### Acceptance criteria

- `<x-card>` exists.
- Supports variants.
- Supports padding options.
- Attributes merge works.
- Render tests pass.

### Definition of Done

- Тесты написаны.
- Card component создан.
- Tests pass.
- Коммит: `CONV-012: Build Card component`

### Files likely touched

```txt
resources/views/components/card.blade.php
tests/Feature/ViewComponents/CardComponentTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-013 — Build Badge Component

**Area:** UI / Blade Components  
**Type:** Component  
**Priority:** P0  
**Branch:** `feature/CONV-013-build-badge-component`  
**Base branch:** `develop`  
**Depends on:** CONV-012

### Goal

Создать `<x-badge>` для статусов, plan labels, format hints и small labels.

### TDD step

Render test:

```php
it('renders a success badge', function () {
    $view = $this->blade('<x-badge variant="success">Completed</x-badge>');

    $view->assertSee('Completed');
});
```

### Implementation

Создать:

```txt
resources/views/components/badge.blade.php
```

Variants:

```txt
neutral
success
warning
danger
purple
gradient
```

Sizes:

```txt
sm
md
```

### Acceptance criteria

- `<x-badge>` exists.
- Supports variants.
- Supports sizes.
- Slot renders correctly.
- Render tests pass.

### Definition of Done

- Тест написан.
- Badge component создан.
- Tests pass.
- Коммит: `CONV-013: Build Badge component`

### Files likely touched

```txt
resources/views/components/badge.blade.php
tests/Feature/ViewComponents/BadgeComponentTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-014 — Build FileIcon Component

**Area:** UI / Blade Components  
**Type:** Component  
**Priority:** P0  
**Branch:** `feature/CONV-014-build-file-icon-component`  
**Base branch:** `develop`  
**Depends on:** CONV-013

### Goal

Создать `<x-file-icon>` для визуального отображения форматов файлов.

### TDD step

Render test:

```php
it('renders a png file icon', function () {
    $view = $this->blade('<x-file-icon format="png" />');

    $view->assertSee('PNG');
});
```

Fallback test:

```php
it('renders an unknown file icon fallback', function () {
    $view = $this->blade('<x-file-icon format="unknown" />');

    $view->assertSee('FILE');
});
```

### Implementation

Создать:

```txt
resources/views/components/file-icon.blade.php
```

Supported MVP labels:

```txt
PNG
JPG
WEBP
PDF
```

Props:

```txt
format
size: sm/md/lg
```

Не подключать SVG library. Простого rounded square label достаточно для MVP.

### Acceptance criteria

- `<x-file-icon format="png" />` renders PNG.
- JPG/JPEG normalize visually to JPG.
- WEBP renders WEBP.
- PDF renders PDF.
- Unknown format renders FILE fallback.
- Tests pass.

### Definition of Done

- Тесты написаны.
- FileIcon component создан.
- Tests pass.
- Коммит: `CONV-014: Build FileIcon component`

### Files likely touched

```txt
resources/views/components/file-icon.blade.php
tests/Feature/ViewComponents/FileIconComponentTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-015 — Build Stepper Component

**Area:** UI / Blade Components  
**Type:** Component  
**Priority:** P0  
**Branch:** `feature/CONV-015-build-stepper-component`  
**Base branch:** `develop`  
**Depends on:** CONV-014

### Goal

Создать stepper component для будущего flow:

```txt
File → Format → Settings → Convert
```

### TDD step

Render test:

```php
it('renders converter stepper with active step', function () {
    $view = $this->blade('<x-stepper :steps="[\'File\', \'Format\', \'Settings\', \'Convert\']" active="Format" />');

    $view->assertSee('File');
    $view->assertSee('Format');
    $view->assertSee('Settings');
    $view->assertSee('Convert');
});
```

### Implementation

Создать:

```txt
resources/views/components/stepper.blade.php
```

Props:

```txt
steps: array
active: string|int
```

Stepper должен уметь:

```txt
- показывать все steps;
- выделять active;
- помечать previous как completed;
- быть статическим, без JS.
```

### Acceptance criteria

- `<x-stepper>` exists.
- Renders all provided steps.
- Active step visually highlighted.
- Previous steps visually completed.
- No business logic.
- Test passes.

### Definition of Done

- Тест написан.
- Stepper component создан.
- Tests pass.
- Коммит: `CONV-015: Build Stepper component`

### Files likely touched

```txt
resources/views/components/stepper.blade.php
tests/Feature/ViewComponents/StepperComponentTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-016 — Build Form Control Components

**Area:** UI / Blade Components / Forms  
**Type:** Component  
**Priority:** P0  
**Branch:** `feature/CONV-016-build-form-control-components`  
**Base branch:** `develop`  
**Depends on:** CONV-015

### Goal

Создать базовые form components для будущих dynamic converter options.

### TDD step

Render test for input:

```php
it('renders text input with label', function () {
    $view = $this->blade('<x-form.input name="filename" label="File name" />');

    $view->assertSee('File name');
    $view->assertSee('name="filename"', false);
});
```

Render test for select:

```php
it('renders select with label', function () {
    $view = $this->blade('<x-form.select name="quality" label="Quality"><option>High</option></x-form.select>');

    $view->assertSee('Quality');
    $view->assertSee('High');
});
```

### Implementation

Создать минимально:

```txt
resources/views/components/form/input.blade.php
resources/views/components/form/select.blade.php
resources/views/components/form/toggle.blade.php
resources/views/components/form/segmented.blade.php
resources/views/components/form/color.blade.php
```

Поддержка:

```txt
label
name
error optional
hint optional
disabled optional
```

`segmented` может быть простым group of buttons/radios.

Не делать DynamicOptionsForm здесь. Только primitives.

### Acceptance criteria

- Input component renders label/name.
- Select component renders label/options slot.
- Toggle component renders accessible control shell.
- Segmented component exists.
- Color component exists.
- Components do not depend on converter domain.
- Tests pass for at least input/select/segmented.

### Definition of Done

- Тесты написаны.
- Form control components созданы.
- Tests pass.
- Коммит: `CONV-016: Build form control components`

### Files likely touched

```txt
resources/views/components/form/input.blade.php
resources/views/components/form/select.blade.php
resources/views/components/form/toggle.blade.php
resources/views/components/form/segmented.blade.php
resources/views/components/form/color.blade.php
tests/Feature/ViewComponents/FormComponentsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-017 — Build User Dropdown UI Shell

**Area:** UI / Alpine / Header  
**Type:** Component  
**Priority:** P1  
**Branch:** `feature/CONV-017-build-user-dropdown-ui-shell`  
**Base branch:** `develop`  
**Depends on:** CONV-016

### Goal

Создать статический user dropdown shell для будущего authenticated dashboard.

### TDD step

Feature/render test:

```php
it('renders user dropdown shell in dashboard header', function () {
    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('Account')
        ->assertSee('Credits')
        ->assertSee('Billing')
        ->assertSee('Settings');
});
```

Markup test can check Alpine attribute:

```php
$this->get('/dashboard')->assertSee('x-data', false);
```

### Implementation

Создать partial/component:

```txt
resources/views/components/user-dropdown.blade.php
```

Содержимое shell:

```txt
- avatar/initials placeholder;
- Account label;
- plan placeholder: Free;
- credits placeholder: 50 credits;
- links: Dashboard, History, Billing, Settings, Log out placeholder.
```

Использовать Alpine:

```blade
<div x-data="{ open: false }" @click.outside="open = false">
```

Пока нет auth, поэтому данные статические.

### Acceptance criteria

- User dropdown shell appears in header.
- Uses Alpine `x-data`.
- Contains account summary placeholder.
- Contains credits placeholder.
- Contains Billing/Settings links placeholders.
- No real auth dependency.
- Test passes.

### Definition of Done

- Тест написан.
- User dropdown shell добавлен.
- Tests pass.
- Коммит: `CONV-017: Build user dropdown UI shell`

### Files likely touched

```txt
resources/views/components/user-dropdown.blade.php
resources/views/layouts/app.blade.php
tests/Feature/DashboardRouteTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-018 — Build Footer Help Cards

**Area:** UI / Footer  
**Type:** Component  
**Priority:** P1  
**Branch:** `feature/CONV-018-build-footer-help-cards`  
**Base branch:** `develop`  
**Depends on:** CONV-017

### Goal

Создать footer help cards для dashboard:

```txt
Help Center
Contact Support
Refer a Friend
```

### TDD step

Feature test:

```php
it('renders footer help cards on dashboard', function () {
    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('Help Center')
        ->assertSee('Contact Support')
        ->assertSee('Refer a Friend');
});
```

### Implementation

Создать:

```txt
resources/views/components/footer-help-cards.blade.php
```

Cards:

```txt
Help Center — Browse guides and FAQs
Contact Support — Submit a ticket or chat with us
Refer a Friend — Get credits for inviting friends
```

Использовать `<x-card>`.

Ссылки пока могут быть `#`, но лучше использовать named routes только когда routes появятся.

### Acceptance criteria

- Footer help cards component exists.
- Dashboard renders all three cards.
- Uses Card component.
- No broken named routes.
- Test passes.

### Definition of Done

- Тест написан.
- Footer help cards добавлены.
- Tests pass.
- Коммит: `CONV-018: Build footer help cards`

### Files likely touched

```txt
resources/views/components/footer-help-cards.blade.php
resources/views/dashboard.blade.php
tests/Feature/DashboardRouteTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-019 — Build Table Shell Component

**Area:** UI / Blade Components / Table  
**Type:** Component  
**Priority:** P1  
**Branch:** `feature/CONV-019-build-table-shell-component`  
**Base branch:** `develop`  
**Depends on:** CONV-018

### Goal

Создать table shell component для будущих Recent Conversions, History и Billing transactions.

### TDD step

Render test:

```php
it('renders table shell with header and content', function () {
    $view = $this->blade('<x-table.shell title="Recent Conversions">Empty</x-table.shell>');

    $view->assertSee('Recent Conversions');
    $view->assertSee('Empty');
});
```

### Implementation

Создать:

```txt
resources/views/components/table/shell.blade.php
resources/views/components/table/empty-state.blade.php
```

Props:

```txt
title
description optional
actionLabel optional
actionUrl optional
```

Не создавать database-backed table.

### Acceptance criteria

- `<x-table.shell>` exists.
- Accepts title.
- Renders slot.
- Empty state component exists.
- No Livewire dependency.
- Tests pass.

### Definition of Done

- Тест написан.
- Table shell создан.
- Tests pass.
- Коммит: `CONV-019: Build table shell component`

### Files likely touched

```txt
resources/views/components/table/shell.blade.php
resources/views/components/table/empty-state.blade.php
tests/Feature/ViewComponents/TableShellComponentTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-020 — Compose Dashboard UI Skeleton

**Area:** UI / Dashboard  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-020-compose-dashboard-ui-skeleton`  
**Base branch:** `develop`  
**Depends on:** CONV-019

### Goal

Собрать статический dashboard skeleton из компонентов Phase 1.

### TDD step

Feature test:

```php
it('renders dashboard UI skeleton', function () {
    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('Convert any file')
        ->assertSee('File')
        ->assertSee('Format')
        ->assertSee('Settings')
        ->assertSee('Convert')
        ->assertSee('Recent Conversions')
        ->assertSee('No conversions yet');
});
```

### Implementation

Обновить:

```txt
resources/views/dashboard.blade.php
```

Skeleton должен содержать:

```txt
- welcome/hero headline;
- static upload placeholder card;
- static stepper with File active;
- static target/settings placeholder area;
- Recent Conversions empty table shell;
- Footer help cards.
```

Использовать:

```txt
<x-card>
<x-button>
<x-badge>
<x-file-icon>
<x-stepper>
<x-table.shell>
<x-footer-help-cards>
```

Не добавлять:

```txt
wire:model
wire:click
file storage
fake conversion rows pretending to be real data
```

### Acceptance criteria

- Dashboard uses Phase 1 components.
- Upload placeholder visible.
- Stepper visible.
- Recent Conversions empty state visible.
- Footer help cards visible.
- No upload/conversion logic added.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.

### Definition of Done

- Тест написан.
- Dashboard skeleton собран.
- Нет бизнес-логики.
- Tests/build pass.
- Коммит: `CONV-020: Compose dashboard UI skeleton`

### Files likely touched

```txt
resources/views/dashboard.blade.php
tests/Feature/DashboardRouteTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 1 Completion Criteria

Phase 1 завершена, когда:

```txt
- CONV-009–CONV-020 выполнены;
- app layout exists;
- design tokens exist;
- Button component exists;
- Card component exists;
- Badge component exists;
- FileIcon component exists;
- Stepper component exists;
- Form control components exist;
- User dropdown UI shell exists;
- Footer help cards exist;
- Table shell exists;
- Dashboard UI skeleton composed;
- dashboard is still static;
- no auth was added;
- no upload behavior was added;
- no converter domain was added;
- no billing was added;
- no API was added;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 1

Без отдельной задачи нельзя:

```txt
- устанавливать Laravel Breeze;
- добавлять users auth flow;
- создавать files table;
- создавать conversion_jobs table;
- создавать ConverterRegistry;
- создавать StoreUploadedFileAction;
- добавлять Livewire upload;
- создавать DynamicOptionsForm;
- создавать RecentConversionsTable с данными;
- устанавливать Cashier;
- создавать CreditLedger;
- добавлять billing routes;
- добавлять API routes;
- добавлять OpenAPI docs;
- добавлять image processing packages;
- подключать React/Vue/Inertia;
- подключать UI libraries;
- делать Stripe/S3/queue integration.
```

---

# 12. Recommended Execution Order

```txt
CONV-009 Create Base App Layout
CONV-010 Add Design Tokens
CONV-011 Build Button Component
CONV-012 Build Card Component
CONV-013 Build Badge Component
CONV-014 Build FileIcon Component
CONV-015 Build Stepper Component
CONV-016 Build Form Control Components
CONV-017 Build User Dropdown UI Shell
CONV-018 Build Footer Help Cards
CONV-019 Build Table Shell Component
CONV-020 Compose Dashboard UI Skeleton
```

---

# 13. Release

После завершения Phase 1:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build

php artisan migrate:fresh

git checkout -b release/v0.1.1-phase1-ui-foundation
git push -u origin release/v0.1.1-phase1-ui-foundation
```

После этого сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.1-phase1-ui-foundation -m "File Converter Phase 1 UI Foundation"
git push origin v0.1.1-phase1-ui-foundation
```
