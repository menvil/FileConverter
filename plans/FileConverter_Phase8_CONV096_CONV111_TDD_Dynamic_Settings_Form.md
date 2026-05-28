# File Converter — Phase 8 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 8 — Dynamic Settings Form**  
Диапазон задач: **CONV-096 → CONV-111**  
Основа нумерации: Phase 7 завершилась на `CONV-095`, поэтому Phase 8 начинается с `CONV-096`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 8 соответствует блоку:

```txt
Phase 8 — Dynamic Settings Form
```

Правильный диапазон Phase 8:

```txt
CONV-096 — Add Settings Step Guard
CONV-097 — Create Dynamic Options Form Partial
CONV-098 — Add Segmented Field Renderer
CONV-099 — Add Select Field Renderer
CONV-100 — Add Toggle Field Renderer
CONV-101 — Add Color Field Renderer
CONV-102 — Add Number And Range Field Renderer
CONV-103 — Load Converter Options Schema On Target Selection
CONV-104 — Initialize Default Converter Options
CONV-105 — Render PNG To JPG Settings
CONV-106 — Render PNG To PDF Settings
CONV-107 — Add Settings Field Validation Errors
CONV-108 — Validate Settings Before Convert Step
CONV-109 — Preserve Settings State During Navigation
CONV-110 — Add Estimated Cost Placeholder
CONV-111 — Add Dynamic Settings Smoke Tests
```

Phase 8 добавляет третий шаг основного flow:

```txt
File uploaded → Choose target format → Configure settings
```

Важно:

```txt
Phase 6 = upload flow
Phase 7 = target format selection
Phase 8 = dynamic settings form
Phase 9 = conversion jobs
Phase 10 = real conversion drivers
```

В Phase 8 пользователь уже видит настоящую форму настроек для выбранной пары `source_format → target_format`, но ещё **не создаёт ConversionJob**, **не запускает конвертацию**, **не списывает credits** и **не скачивает результат**.

---

# 2. Цель Phase 8

Phase 8 должна превратить settings placeholder из Phase 7 в рабочую динамическую форму, построенную на `optionsSchema()` выбранного converter.

После Phase 8 authenticated user должен уметь:

```txt
- загрузить supported file;
- выбрать target format;
- попасть на settings step;
- увидеть настройки, соответствующие выбранной паре source → target;
- увидеть разные настройки для PNG → JPG и PNG → PDF;
- изменить значения настроек;
- получить field-level validation errors;
- вернуться назад к выбору формата без потери файла;
- вернуться со settings обратно и сохранить введённые values;
- пройти validation перед будущим convert step;
- увидеть placeholder estimated cost.
```

Главная архитектурная цель:

```txt
UI не знает вручную про PNG → JPG или PNG → PDF.
UI рендерит форму по schema.
```

Неправильно:

```php
@if ($source === 'png' && $target === 'jpg')
    ... ручная форма JPG ...
@endif

@if ($source === 'png' && $target === 'pdf')
    ... ручная форма PDF ...
@endif
```

Правильно:

```txt
DashboardConverter → selected converter → optionsSchema → DynamicOptionsForm → Livewire options state
```

---

# 3. Scope Phase 8

## Входит

```txt
- settings step guard;
- dynamic options form partial/component;
- field renderers for segmented/select/toggle/color/number/range;
- loading options schema from selected converter;
- default options initialization;
- rendering PNG → JPG settings;
- rendering PNG → PDF settings;
- Livewire binding to options state;
- settings validation through existing OptionsValidator;
- field-level validation messages;
- navigation back/forward preserving settings state;
- estimated cost placeholder.
```

## Не входит

```txt
- ConversionJob model;
- CreateConversionJobAction;
- queue jobs;
- real image conversion;
- download route;
- credits ledger;
- real cost estimator;
- Cashier;
- API endpoints;
- OpenAPI docs;
- batch conversion;
- saving user presets;
- advanced conditional visibility engine beyond simple visible_when placeholder.
```

---

# 4. Critical Decisions

## 4.1. Settings are converter-pair-specific

Настройки принадлежат не формату исходного файла, а конкретному converter:

```txt
PNG → JPG has image compression/transparency settings.
PNG → PDF has page layout/margins/fit settings.
```

Нельзя делать одну “PNG settings form” для всех target formats.

## 4.2. Schema is the source of truth

Форма должна строиться из:

```php
$converter->optionsSchema()
```

Livewire component не должен содержать список полей каждого converter.

## 4.3. Options are stored as associative array

В `DashboardConverter` использовать единое состояние:

```php
public array $options = [];
public array $optionsSchema = [];
```

Не создавать отдельные public properties:

```php
public string $quality;
public string $pageSize;
public bool $removeMetadata;
```

Это сломает динамичность.

## 4.4. Validation is schema-based

Validation должен вызываться через `OptionsValidator`, созданный ранее.

Нельзя валидировать так:

```php
if ($this->selectedTargetFormat === 'jpg') {
    $this->validate([...]);
}
```

Правильно:

```php
$normalized = $this->optionsValidator->validate($this->optionsSchema, $this->options);
```

## 4.5. No conversion in Phase 8

Кнопка может называться:

```txt
Continue
Review conversion
Convert Now placeholder
```

Но она не должна создавать job.

Лучше в Phase 8 делать:

```txt
Settings → Convert placeholder step
```

А настоящая логика `CreateConversionJobAction` появится в Phase 9.

## 4.6. Estimated cost is placeholder only

Настоящий `ConversionCostEstimator` появится в billing/cost phase.

В Phase 8 допустимо:

```txt
Estimated cost will be calculated before conversion.
```

Нельзя сейчас захардкодить:

```txt
PNG → JPG = 1 credit
PNG → PDF = 2 credits
```

Это будет отдельная доменная задача, чтобы не размазать billing раньше времени.

---

# 5. Architecture Rules

## 5.1. DynamicOptionsForm is view-level renderer

`DynamicOptionsForm` не должен знать про converter registry, files, billing, jobs.

Он принимает:

```txt
schema
options
errors
```

и рендерит поля.

## 5.2. DashboardConverter owns step state

`DashboardConverter` отвечает за:

```txt
current file
selected target format
selected converter
options schema
options state
step transitions
```

Но не отвечает за реальное выполнение conversion.

## 5.3. Field renderers must be small

Не создавать один огромный Blade-файл на 500 строк.

Допустимо:

```txt
resources/views/livewire/dashboard-converter/partials/dynamic-options-form.blade.php
resources/views/livewire/dashboard-converter/fields/segmented.blade.php
resources/views/livewire/dashboard-converter/fields/select.blade.php
resources/views/livewire/dashboard-converter/fields/toggle.blade.php
resources/views/livewire/dashboard-converter/fields/color.blade.php
resources/views/livewire/dashboard-converter/fields/number.blade.php
resources/views/livewire/dashboard-converter/fields/range.blade.php
```

Если проект предпочитает меньше partials, можно оставить один partial, но не смешивать в нём business logic.

## 5.4. Options keys must be stable

Schema field key должен совпадать с ключом в `$options`:

```php
'quality' => [
    'type' => 'segmented',
    'default' => 'high',
]
```

Livewire binding:

```blade
wire:model.live="options.quality"
```

## 5.5. Unknown fields should fail visibly

Если schema содержит unsupported field type, UI должен показывать controlled fallback в dev/test, а не молча пропускать поле.

Минимально:

```txt
Unsupported field type: slider2
```

В production можно заменить на generic fallback позже.

---

# 6. GitFlow для Phase 8

## Base branch

Все задачи Phase 8 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-096-add-settings-step-guard
feature/CONV-097-create-dynamic-options-form-partial
feature/CONV-105-render-png-to-jpg-settings
```

## Commit format

```txt
CONV-096: Add settings step guard
CONV-097: Create dynamic options form partial
CONV-105: Render PNG to JPG settings
```

## Release branch

После выполнения `CONV-096`–`CONV-111`:

```txt
release/v0.1.8-phase08-dynamic-settings-form
```

## Tag

После merge release branch в `main`:

```txt
v0.1.8-phase08-dynamic-settings-form
```

---

# 7. TDD Rules for Phase 8

## Для settings step

Тестировать:

```txt
- settings step недоступен без uploaded file;
- settings step недоступен без selected target format;
- selecting target format loads schema;
- default options are initialized;
- settings form renders schema fields.
```

## Для field renderers

Тестировать:

```txt
- segmented field visible;
- select field visible;
- toggle field visible;
- color field visible;
- number/range fields visible;
- unsupported field type handled.
```

## Для validation

Тестировать:

```txt
- invalid option value shows error;
- valid default options pass;
- unknown option is rejected;
- validation does not create ConversionJob.
```

## Для navigation

Тестировать:

```txt
- settings → format сохраняет current file;
- settings values persist after back/forward;
- choosing a different target resets incompatible options.
```

Если тест напрямую невозможен, в задаче писать:

```txt
No direct test — view-only renderer wiring, covered by Livewire smoke test.
```

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Dashboard / Livewire / Options / Tests
Type: Test / Feature / View / Validation / State
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
- Нет ConversionJob/queue/billing/API вне scope
- Коммит содержит ID задачи

Files likely touched:
- path/to/file
```

---

# 9. Phase 8 Atomic Tasks

---

## CONV-096 — Add Settings Step Guard

**Area:** Dashboard / Livewire / State  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-096-add-settings-step-guard`  
**Base branch:** `develop`  
**Depends on:** CONV-095

### Goal

Добавить guard для settings step: пользователь не может попасть на настройки без загруженного файла и выбранного target format.

### TDD step

Livewire test:

```php
use App\Livewire\DashboardConverter;
use Livewire\Livewire;

it('does not allow settings step without uploaded file', function () {
    Livewire::test(DashboardConverter::class)
        ->set('step', 'settings')
        ->call('ensureValidStep')
        ->assertSet('step', 'upload');
});
```

Second test:

```php
it('does not allow settings step without selected target format', function () {
    $file = FileRecord::factory()->create([
        'extension' => 'png',
    ]);

    Livewire::test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->set('selectedTargetFormat', null)
        ->call('goToSettingsStep')
        ->assertSet('step', 'format');
});
```

Тест должен упасть до реализации guard.

### Implementation

В `DashboardConverter` добавить:

```php
public function goToSettingsStep(): void
{
    if ($this->currentFile === null) {
        $this->resetConverterState();
        $this->step = 'upload';
        return;
    }

    if ($this->selectedTargetFormat === null) {
        $this->step = 'format';
        return;
    }

    $this->step = 'settings';
}
```

Если уже есть centralized `ensureValidStep()`, расширить его.

Не загружать schema в этой задаче.

### Acceptance criteria

- Settings step требует current file.
- Settings step требует selected target format.
- Missing file возвращает на upload.
- Missing target возвращает на format.
- Stale file id handled safely.
- Тесты проходят.

### Definition of Done

- Тесты написаны первыми.
- Guard добавлен.
- Нет создания ConversionJob.
- `composer test` проходит.
- `composer lint` проходит.
- Коммит: `CONV-096: Add settings step guard`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
tests/Feature/Livewire/DashboardConverterSettingsStepTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-097 — Create Dynamic Options Form Partial

**Area:** Dashboard / Livewire / View  
**Type:** View  
**Priority:** P0  
**Branch:** `feature/CONV-097-create-dynamic-options-form-partial`  
**Base branch:** `develop`  
**Depends on:** CONV-096

### Goal

Создать базовый partial для динамической формы настроек, который принимает `optionsSchema` и рендерит поля по типам.

### TDD step

Livewire/view smoke test:

```php
it('renders dynamic options form container on settings step', function () {
    Livewire::test(DashboardConverter::class)
        ->set('step', 'settings')
        ->set('optionsSchema', [
            'quality' => [
                'type' => 'segmented',
                'label' => 'Quality',
                'default' => 'high',
                'options' => [
                    'medium' => 'Medium',
                    'high' => 'High',
                ],
            ],
        ])
        ->assertSee('Quality');
});
```

Если прямое выставление settings step ломается guard, использовать helper/factory для подготовки file + target.

### Implementation

Создать partial:

```txt
resources/views/livewire/dashboard-converter/partials/dynamic-options-form.blade.php
```

Минимальная структура:

```blade
<div class="space-y-5" data-testid="dynamic-options-form">
    @foreach ($optionsSchema as $key => $field)
        <div data-testid="option-field-{{ $key }}">
            @includeIf('livewire.dashboard-converter.fields.' . $field['type'], [
                'key' => $key,
                'field' => $field,
            ])
        </div>
    @endforeach
</div>
```

Добавить fallback для неизвестного типа:

```blade
@if (! view()->exists('livewire.dashboard-converter.fields.' . $field['type']))
    <div class="text-sm text-red-600">
        Unsupported field type: {{ $field['type'] ?? 'unknown' }}
    </div>
@endif
```

### Acceptance criteria

- Dynamic options form partial существует.
- Partial рендерит поля из schema.
- Unknown field type не ломает страницу.
- Нет converter-specific if в Blade.
- Тест проходит.

### Definition of Done

- Тест написан первым.
- Partial создан.
- Fallback добавлен.
- Нет ручных PNG/JPG/PDF условий.
- `composer test` проходит.
- `composer lint` проходит.
- Коммит: `CONV-097: Create dynamic options form partial`

### Files likely touched

```txt
resources/views/livewire/dashboard-converter.blade.php
resources/views/livewire/dashboard-converter/partials/dynamic-options-form.blade.php
tests/Feature/Livewire/DashboardConverterSettingsStepTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-098 — Add Segmented Field Renderer

**Area:** Dashboard / Livewire / Options  
**Type:** View  
**Priority:** P0  
**Branch:** `feature/CONV-098-add-segmented-field-renderer`  
**Base branch:** `develop`  
**Depends on:** CONV-097

### Goal

Добавить renderer для `segmented` field type.

### TDD step

Livewire test:

```php
it('renders segmented option field', function () {
    Livewire::test(DashboardConverter::class)
        ->set('step', 'settings')
        ->set('optionsSchema', [
            'quality' => [
                'type' => 'segmented',
                'label' => 'Quality',
                'default' => 'high',
                'options' => [
                    'low' => 'Low',
                    'medium' => 'Medium',
                    'high' => 'High',
                ],
            ],
        ])
        ->set('options', ['quality' => 'high'])
        ->assertSee('Quality')
        ->assertSee('Low')
        ->assertSee('Medium')
        ->assertSee('High');
});
```

### Implementation

Создать:

```txt
resources/views/livewire/dashboard-converter/fields/segmented.blade.php
```

Renderer должен:

```txt
- показать label;
- показать все options;
- bind на options.{key};
- выделить выбранное значение;
- поддержать help text, если field.help задан.
```

Пример binding:

```blade
<button
    type="button"
    wire:click="$set('options.{{ $key }}', '{{ $value }}')"
>
    {{ $label }}
</button>
```

### Acceptance criteria

- Segmented field рендерит label.
- Все варианты видны.
- Клик меняет `options.{key}`.
- Active state визуально отличается.
- Help text отображается, если есть.
- Тест проходит.

### Definition of Done

- Тест написан.
- Segmented renderer создан.
- Binding работает.
- `composer test` проходит.
- `npm run build` проходит.
- Коммит: `CONV-098: Add segmented field renderer`

### Files likely touched

```txt
resources/views/livewire/dashboard-converter/fields/segmented.blade.php
tests/Feature/Livewire/DashboardConverterSettingsFieldsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-099 — Add Select Field Renderer

**Area:** Dashboard / Livewire / Options  
**Type:** View  
**Priority:** P0  
**Branch:** `feature/CONV-099-add-select-field-renderer`  
**Base branch:** `develop`  
**Depends on:** CONV-098

### Goal

Добавить renderer для `select` field type.

### TDD step

Livewire test:

```php
it('renders select option field', function () {
    Livewire::test(DashboardConverter::class)
        ->set('step', 'settings')
        ->set('optionsSchema', [
            'page_size' => [
                'type' => 'select',
                'label' => 'Page size',
                'default' => 'auto',
                'options' => [
                    'auto' => 'Auto',
                    'a4' => 'A4',
                    'letter' => 'Letter',
                ],
            ],
        ])
        ->set('options', ['page_size' => 'auto'])
        ->assertSee('Page size')
        ->assertSee('A4')
        ->assertSee('Letter');
});
```

### Implementation

Создать:

```txt
resources/views/livewire/dashboard-converter/fields/select.blade.php
```

Renderer:

```blade
<label for="option-{{ $key }}">{{ $field['label'] }}</label>
<select id="option-{{ $key }}" wire:model.live="options.{{ $key }}">
    @foreach ($field['options'] as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>
```

### Acceptance criteria

- Select field рендерит label.
- Options visible.
- Value bound to `options.{key}`.
- Placeholder поддерживается, если задан.
- Help text поддерживается.
- Тест проходит.

### Definition of Done

- Тест написан.
- Select renderer создан.
- Binding работает.
- `composer test` проходит.
- `npm run build` проходит.
- Коммит: `CONV-099: Add select field renderer`

### Files likely touched

```txt
resources/views/livewire/dashboard-converter/fields/select.blade.php
tests/Feature/Livewire/DashboardConverterSettingsFieldsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-100 — Add Toggle Field Renderer

**Area:** Dashboard / Livewire / Options  
**Type:** View  
**Priority:** P0  
**Branch:** `feature/CONV-100-add-toggle-field-renderer`  
**Base branch:** `develop`  
**Depends on:** CONV-099

### Goal

Добавить renderer для boolean `toggle` field type.

### TDD step

Livewire test:

```php
it('renders toggle option field', function () {
    Livewire::test(DashboardConverter::class)
        ->set('step', 'settings')
        ->set('optionsSchema', [
            'remove_metadata' => [
                'type' => 'toggle',
                'label' => 'Remove metadata',
                'default' => true,
                'help' => 'Strip EXIF and private metadata.',
            ],
        ])
        ->set('options', ['remove_metadata' => true])
        ->assertSee('Remove metadata')
        ->assertSee('Strip EXIF');
});
```

### Implementation

Создать:

```txt
resources/views/livewire/dashboard-converter/fields/toggle.blade.php
```

Renderer должен:

```txt
- bind to options.{key};
- support true/false;
- render label/help;
- be keyboard-accessible enough for MVP.
```

Можно использовать checkbox styled as switch:

```blade
<input
    type="checkbox"
    id="option-{{ $key }}"
    wire:model.live="options.{{ $key }}"
>
```

### Acceptance criteria

- Toggle field visible.
- Boolean value bound to options.
- Label/help visible.
- Toggle can be changed.
- Тест проходит.

### Definition of Done

- Тест написан.
- Toggle renderer создан.
- Binding работает.
- `composer test` проходит.
- `npm run build` проходит.
- Коммит: `CONV-100: Add toggle field renderer`

### Files likely touched

```txt
resources/views/livewire/dashboard-converter/fields/toggle.blade.php
tests/Feature/Livewire/DashboardConverterSettingsFieldsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-101 — Add Color Field Renderer

**Area:** Dashboard / Livewire / Options  
**Type:** View  
**Priority:** P0  
**Branch:** `feature/CONV-101-add-color-field-renderer`  
**Base branch:** `develop`  
**Depends on:** CONV-100

### Goal

Добавить renderer для `color` field type, нужен для PNG → JPG transparency background.

### TDD step

Livewire test:

```php
it('renders color option field', function () {
    Livewire::test(DashboardConverter::class)
        ->set('step', 'settings')
        ->set('optionsSchema', [
            'background' => [
                'type' => 'color',
                'label' => 'Background color',
                'default' => '#ffffff',
            ],
        ])
        ->set('options', ['background' => '#ffffff'])
        ->assertSee('Background color')
        ->assertSee('#ffffff');
});
```

### Implementation

Создать:

```txt
resources/views/livewire/dashboard-converter/fields/color.blade.php
```

Renderer:

```blade
<input type="color" wire:model.live="options.{{ $key }}">
<input type="text" wire:model.live.debounce.300ms="options.{{ $key }}">
```

Текстовый input нужен, потому что не все пользователи удобно работают с native color picker.

### Acceptance criteria

- Color field visible.
- Native color input exists.
- Text hex input exists.
- Value bound to `options.{key}`.
- Тест проходит.

### Definition of Done

- Тест написан.
- Color renderer создан.
- Binding работает.
- `composer test` проходит.
- `npm run build` проходит.
- Коммит: `CONV-101: Add color field renderer`

### Files likely touched

```txt
resources/views/livewire/dashboard-converter/fields/color.blade.php
tests/Feature/Livewire/DashboardConverterSettingsFieldsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-102 — Add Number And Range Field Renderer

**Area:** Dashboard / Livewire / Options  
**Type:** View  
**Priority:** P1  
**Branch:** `feature/CONV-102-add-number-and-range-field-renderer`  
**Base branch:** `develop`  
**Depends on:** CONV-101

### Goal

Добавить renderers для `number` и `range` field types, чтобы schema уже поддерживала будущие поля вроде width/height/quality percentage/compression.

### TDD step

Livewire test:

```php
it('renders number and range option fields', function () {
    Livewire::test(DashboardConverter::class)
        ->set('step', 'settings')
        ->set('optionsSchema', [
            'width' => [
                'type' => 'number',
                'label' => 'Width',
                'default' => 1200,
                'min' => 1,
                'max' => 10000,
            ],
            'compression' => [
                'type' => 'range',
                'label' => 'Compression',
                'default' => 80,
                'min' => 0,
                'max' => 100,
                'step' => 1,
            ],
        ])
        ->set('options', [
            'width' => 1200,
            'compression' => 80,
        ])
        ->assertSee('Width')
        ->assertSee('Compression');
});
```

### Implementation

Создать:

```txt
resources/views/livewire/dashboard-converter/fields/number.blade.php
resources/views/livewire/dashboard-converter/fields/range.blade.php
```

Number renderer:

```blade
<input
    type="number"
    wire:model.live.debounce.300ms="options.{{ $key }}"
    min="{{ $field['min'] ?? null }}"
    max="{{ $field['max'] ?? null }}"
    step="{{ $field['step'] ?? 1 }}"
>
```

Range renderer:

```blade
<input
    type="range"
    wire:model.live="options.{{ $key }}"
    min="{{ $field['min'] ?? 0 }}"
    max="{{ $field['max'] ?? 100 }}"
    step="{{ $field['step'] ?? 1 }}"
>
<span>{{ data_get($options, $key) }}</span>
```

### Acceptance criteria

- Number field visible.
- Range field visible.
- min/max/step supported.
- Values bound to options.
- Current range value visible.
- Тест проходит.

### Definition of Done

- Тест написан.
- Number renderer создан.
- Range renderer создан.
- `composer test` проходит.
- `npm run build` проходит.
- Коммит: `CONV-102: Add number and range field renderer`

### Files likely touched

```txt
resources/views/livewire/dashboard-converter/fields/number.blade.php
resources/views/livewire/dashboard-converter/fields/range.blade.php
tests/Feature/Livewire/DashboardConverterSettingsFieldsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-103 — Load Converter Options Schema On Target Selection

**Area:** Dashboard / Livewire / Converter Registry  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-103-load-converter-options-schema-on-target-selection`  
**Base branch:** `develop`  
**Depends on:** CONV-102

### Goal

При выборе target format загружать `optionsSchema()` выбранного converter в состояние `DashboardConverter`.

### TDD step

Livewire test:

```php
it('loads options schema when target format is selected', function () {
    $file = FileRecord::factory()->create([
        'extension' => 'png',
        'mime_type' => 'image/png',
    ]);

    Livewire::test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('selectedTargetFormat', 'jpg')
        ->assertSet('step', 'settings')
        ->assertSee('Quality');
});
```

### Implementation

В `selectTargetFormat(string $targetFormat)`:

```php
$converter = $this->converterRegistry->find(
    $this->currentFile->extension,
    $targetFormat,
);

if ($converter === null) {
    $this->addError('target', 'This conversion is not supported.');
    return;
}

$this->selectedTargetFormat = $targetFormat;
$this->selectedConverterKey = $converter->key();
$this->optionsSchema = $converter->optionsSchema();
$this->initializeOptionsFromSchema();
$this->step = 'settings';
```

Не создавать conversion job.

### Acceptance criteria

- Selecting target loads converter.
- `optionsSchema` is populated.
- `selectedConverterKey` is stored.
- Step changes to settings.
- Unsupported target shows error.
- Тест проходит.

### Definition of Done

- Тест написан.
- Schema loading implemented.
- No ConversionJob created.
- `composer test` проходит.
- Коммит: `CONV-103: Load converter options schema on target selection`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
tests/Feature/Livewire/DashboardConverterSettingsStepTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-104 — Initialize Default Converter Options

**Area:** Dashboard / Livewire / Options  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-104-initialize-default-converter-options`  
**Base branch:** `develop`  
**Depends on:** CONV-103

### Goal

При загрузке schema автоматически заполнить `$options` default-значениями из schema.

### TDD step

Livewire test:

```php
it('initializes default options from converter schema', function () {
    $file = FileRecord::factory()->create(['extension' => 'png']);

    Livewire::test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('options.quality', 'high')
        ->assertSet('options.remove_metadata', true);
});
```

Adapt exact defaults to actual PNG → JPG schema.

### Implementation

Добавить метод:

```php
private function initializeOptionsFromSchema(): void
{
    $this->options = [];

    foreach ($this->optionsSchema as $key => $field) {
        if (array_key_exists('default', $field)) {
            $this->options[$key] = $field['default'];
        }
    }
}
```

Если field required без default — оставить null или не добавлять ключ. Рекомендация для MVP: все fields в MVP должны иметь default.

### Acceptance criteria

- Defaults are copied from schema.
- Old options are reset when target changes.
- Missing default handled predictably.
- Тест проходит.

### Definition of Done

- Тест написан.
- Defaults initialization добавлена.
- Target switch resets incompatible options.
- `composer test` проходит.
- Коммит: `CONV-104: Initialize default converter options`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
tests/Feature/Livewire/DashboardConverterSettingsStepTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-105 — Render PNG To JPG Settings

**Area:** Dashboard / Livewire / Options  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-105-render-png-to-jpg-settings`  
**Base branch:** `develop`  
**Depends on:** CONV-104

### Goal

Проверить и довести отображение формы настроек для `PNG → JPG`.

### TDD step

Livewire test:

```php
it('renders png to jpg settings form', function () {
    $file = FileRecord::factory()->create([
        'extension' => 'png',
        'mime_type' => 'image/png',
    ]);

    Livewire::test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->assertSee('Quality')
        ->assertSee('Resize')
        ->assertSee('Background color')
        ->assertSee('Remove metadata');
});
```

### Implementation

Убедиться, что PNG → JPG converter schema содержит минимум:

```txt
quality
resize
background
remove_metadata
```

Убедиться, что все field types поддерживаются renderers.

Если `background` должен показываться только для transparent PNG, пока в MVP можно показывать всегда. Conditional visibility можно добавить позже.

### Acceptance criteria

- PNG → JPG settings render correctly.
- Quality options visible.
- Resize options visible.
- Background color visible.
- Remove metadata toggle visible.
- Defaults initialized.
- Тест проходит.

### Definition of Done

- Тест написан.
- PNG → JPG form рендерится.
- Нет manual if для PNG/JPG в Blade.
- `composer test` проходит.
- `npm run build` проходит.
- Коммит: `CONV-105: Render PNG to JPG settings`

### Files likely touched

```txt
app/Converters/PngToJpgConverter.php
app/Livewire/DashboardConverter.php
resources/views/livewire/dashboard-converter/partials/dynamic-options-form.blade.php
tests/Feature/Livewire/DashboardConverterSettingsStepTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-106 — Render PNG To PDF Settings

**Area:** Dashboard / Livewire / Options  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-106-render-png-to-pdf-settings`  
**Base branch:** `develop`  
**Depends on:** CONV-105

### Goal

Проверить и довести отображение формы настроек для `PNG → PDF`.

### TDD step

Livewire test:

```php
it('renders png to pdf settings form', function () {
    $file = FileRecord::factory()->create([
        'extension' => 'png',
        'mime_type' => 'image/png',
    ]);

    Livewire::test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'pdf')
        ->assertSee('Page size')
        ->assertSee('Orientation')
        ->assertSee('Margin')
        ->assertSee('Fit mode');
});
```

### Implementation

Убедиться, что PNG → PDF converter schema содержит минимум:

```txt
page_size
orientation
margin
fit_mode
compression
```

Если `compression` пока есть в schema, но не критичен для UI, он должен всё равно рендериться через supported renderer.

### Acceptance criteria

- PNG → PDF settings render correctly.
- Page size visible.
- Orientation visible.
- Margin visible.
- Fit mode visible.
- Compression visible if in schema.
- Тест проходит.

### Definition of Done

- Тест написан.
- PNG → PDF form рендерится.
- Нет manual if для PNG/PDF в Blade.
- `composer test` проходит.
- `npm run build` проходит.
- Коммит: `CONV-106: Render PNG to PDF settings`

### Files likely touched

```txt
app/Converters/PngToPdfConverter.php
resources/views/livewire/dashboard-converter/partials/dynamic-options-form.blade.php
tests/Feature/Livewire/DashboardConverterSettingsStepTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-107 — Add Settings Field Validation Errors

**Area:** Dashboard / Livewire / Validation  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-107-add-settings-field-validation-errors`  
**Base branch:** `develop`  
**Depends on:** CONV-106

### Goal

Показывать ошибки валидации рядом с конкретными settings fields.

### TDD step

Livewire test:

```php
it('shows validation error for invalid settings option', function () {
    $file = FileRecord::factory()->create(['extension' => 'png']);

    Livewire::test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->set('options.quality', 'invalid-quality')
        ->call('validateSettings')
        ->assertHasErrors(['options.quality']);
});
```

### Implementation

Добавить method:

```php
public function validateSettings(): void
{
    try {
        $this->normalizedOptions = $this->optionsValidator->validate(
            $this->optionsSchema,
            $this->options,
        );

        $this->resetErrorBag();
    } catch (InvalidConverterOptionsException $e) {
        foreach ($e->fieldErrors() as $field => $message) {
            $this->addError("options.$field", $message);
        }
    }
}
```

Если `OptionsValidator` ещё не возвращает structured field errors, адаптировать минимально:

```txt
- converter option key;
- message.
```

В field partials добавить:

```blade
@error('options.' . $key)
    <p class="text-sm text-red-600">{{ $message }}</p>
@enderror
```

### Acceptance criteria

- Invalid option creates field-level error.
- Error shown near field.
- Valid option clears error.
- Unknown option rejected.
- Тест проходит.

### Definition of Done

- Тест написан.
- Field-level errors добавлены.
- OptionsValidator used.
- Нет manual converter-specific validation.
- `composer test` проходит.
- Коммит: `CONV-107: Add settings field validation errors`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
app/Support/OptionsValidator.php
app/Exceptions/InvalidConverterOptionsException.php
resources/views/livewire/dashboard-converter/fields/*.blade.php
tests/Feature/Livewire/DashboardConverterSettingsValidationTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-108 — Validate Settings Before Convert Step

**Area:** Dashboard / Livewire / Validation  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-108-validate-settings-before-convert-step`  
**Base branch:** `develop`  
**Depends on:** CONV-107

### Goal

При нажатии будущей кнопки продолжения проверять settings и переходить на placeholder convert step только если options валидны.

### TDD step

Livewire test:

```php
it('moves to convert step when settings are valid', function () {
    $file = FileRecord::factory()->create(['extension' => 'png']);

    Livewire::test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->call('continueFromSettings')
        ->assertSet('step', 'convert');
});
```

Invalid test:

```php
it('does not move to convert step when settings are invalid', function () {
    $file = FileRecord::factory()->create(['extension' => 'png']);

    Livewire::test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->set('options.quality', 'bad')
        ->call('continueFromSettings')
        ->assertSet('step', 'settings')
        ->assertHasErrors(['options.quality']);
});
```

### Implementation

Добавить method:

```php
public function continueFromSettings(): void
{
    if ($this->currentFile === null || $this->selectedTargetFormat === null) {
        $this->goToSettingsStep();
        return;
    }

    if (! $this->validateSettingsAndReturnBool()) {
        $this->step = 'settings';
        return;
    }

    $this->step = 'convert';
}
```

`convert` step пока placeholder. Реальное создание job будет в Phase 9.

### Acceptance criteria

- Valid settings move to convert placeholder step.
- Invalid settings stay on settings step.
- Errors are visible.
- No ConversionJob created.
- Тесты проходят.

### Definition of Done

- Тесты написаны.
- continueFromSettings implemented.
- No job/queue/billing logic.
- `composer test` проходит.
- Коммит: `CONV-108: Validate settings before convert step`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
resources/views/livewire/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterSettingsValidationTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-109 — Preserve Settings State During Navigation

**Area:** Dashboard / Livewire / State  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-109-preserve-settings-state-during-navigation`  
**Base branch:** `develop`  
**Depends on:** CONV-108

### Goal

Сохранять введённые settings values при navigation `settings → format → settings`, если пользователь возвращается к тому же target format.

### TDD step

Livewire test:

```php
it('preserves settings when navigating back and forward to same target format', function () {
    $file = FileRecord::factory()->create(['extension' => 'png']);

    Livewire::test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->set('options.quality', 'best')
        ->call('goToFormatStep')
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('options.quality', 'best');
});
```

Second test:

```php
it('resets incompatible settings when target format changes', function () {
    $file = FileRecord::factory()->create(['extension' => 'png']);

    Livewire::test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->set('options.quality', 'best')
        ->call('goToFormatStep')
        ->call('selectTargetFormat', 'pdf')
        ->assertSet('selectedTargetFormat', 'pdf')
        ->assertSet('options.page_size', 'auto')
        ->assertDontSee('Background color');
});
```

### Implementation

Поддержать cache настроек внутри component state:

```php
public array $optionsByTarget = [];
```

При уходе со settings:

```php
$this->optionsByTarget[$this->selectedTargetFormat] = $this->options;
```

При выборе target:

```php
$this->options = $this->optionsByTarget[$targetFormat]
    ?? $this->defaultsFromSchema($schema);
```

Нельзя сохранять это в DB в Phase 8.

### Acceptance criteria

- Same target restores options.
- Different target gets relevant defaults.
- Incompatible options do not leak.
- No DB persistence.
- Тесты проходят.

### Definition of Done

- Тесты написаны.
- Local component state preservation implemented.
- No presets feature added.
- `composer test` проходит.
- Коммит: `CONV-109: Preserve settings state during navigation`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
tests/Feature/Livewire/DashboardConverterSettingsNavigationTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-110 — Add Estimated Cost Placeholder

**Area:** Dashboard / Livewire / UI  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-110-add-estimated-cost-placeholder`  
**Base branch:** `develop`  
**Depends on:** CONV-109

### Goal

Добавить в settings step место под будущий real cost estimator, но без настоящего billing logic.

### TDD step

Livewire test:

```php
it('shows estimated cost placeholder on settings step', function () {
    $file = FileRecord::factory()->create(['extension' => 'png']);

    Livewire::test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->assertSee('Estimated cost')
        ->assertSee('calculated before conversion');
});
```

### Implementation

В settings panel добавить блок:

```blade
<div data-testid="estimated-cost-placeholder">
    <span>Estimated cost</span>
    <p>Credit cost will be calculated before conversion.</p>
</div>
```

Не считать credits.

Не создавать `ConversionCostEstimator`.

### Acceptance criteria

- Estimated cost section visible.
- Copy ясно говорит, что cost будет calculated later.
- Нет hardcoded credit prices.
- Нет billing service.
- Тест проходит.

### Definition of Done

- Тест написан.
- Placeholder добавлен.
- No billing/cost estimator logic.
- `composer test` проходит.
- `npm run build` проходит.
- Коммит: `CONV-110: Add estimated cost placeholder`

### Files likely touched

```txt
resources/views/livewire/dashboard-converter.blade.php
resources/views/livewire/dashboard-converter/partials/settings-panel.blade.php
tests/Feature/Livewire/DashboardConverterSettingsStepTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-111 — Add Dynamic Settings Smoke Tests

**Area:** Dashboard / Livewire / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-111-add-dynamic-settings-smoke-tests`  
**Base branch:** `develop`  
**Depends on:** CONV-110

### Goal

Добавить итоговые smoke tests Phase 8, чтобы зафиксировать весь flow до settings step.

### TDD step

Добавить tests:

```php
it('completes upload target and settings flow for png to jpg', function () {
    $user = User::factory()->create();

    $file = FileRecord::factory()
        ->for($user)
        ->create([
            'extension' => 'png',
            'mime_type' => 'image/png',
        ]);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('step', 'settings')
        ->assertSee('Quality')
        ->set('options.quality', 'best')
        ->call('continueFromSettings')
        ->assertSet('step', 'convert');
});
```

Second smoke:

```php
it('renders different settings for png to pdf than png to jpg', function () {
    $file = FileRecord::factory()->create(['extension' => 'png']);

    Livewire::test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('selectTargetFormat', 'jpg')
        ->assertSee('Background color')
        ->call('goToFormatStep')
        ->call('selectTargetFormat', 'pdf')
        ->assertSee('Page size')
        ->assertDontSee('Background color');
});
```

### Implementation

Только тесты и мелкие исправления, если smoke tests обнаружат проблемы.

Нельзя в этой задаче добавлять новые фичи.

### Acceptance criteria

- Full flow to settings works.
- PNG → JPG settings shown.
- PNG → PDF settings shown.
- Different target formats produce different settings.
- Continue from settings reaches convert placeholder.
- No ConversionJob created.
- Tests pass.

### Definition of Done

- Smoke tests added.
- Все Phase 8 tests pass.
- `composer test` проходит.
- `composer lint` проходит.
- `npm run build` проходит.
- Коммит: `CONV-111: Add dynamic settings smoke tests`

### Files likely touched

```txt
tests/Feature/Livewire/DashboardConverterSettingsSmokeTest.php
app/Livewire/DashboardConverter.php
resources/views/livewire/dashboard-converter.blade.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 8 Completion Criteria

Phase 8 завершена, когда:

```txt
- CONV-096–CONV-111 выполнены;
- settings step requires file and selected target format;
- target selection loads converter options schema;
- default options are initialized from schema;
- DynamicOptionsForm renders fields from schema;
- segmented field renderer works;
- select field renderer works;
- toggle field renderer works;
- color field renderer works;
- number/range renderers work;
- PNG → JPG settings form renders;
- PNG → PDF settings form renders;
- settings validation uses OptionsValidator;
- field-level validation errors are visible;
- valid settings can move to convert placeholder step;
- invalid settings stay on settings step;
- navigation preserves settings for same target;
- changing target resets incompatible settings;
- estimated cost placeholder exists;
- no ConversionJob is created;
- no queue job is dispatched;
- no real credits/billing logic is added;
- no API endpoints are added;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 8

Без отдельной задачи нельзя:

```txt
- создавать conversion_jobs table;
- создавать CreateConversionJobAction;
- dispatch queue jobs;
- запускать реальную конвертацию;
- добавлять image processing drivers;
- добавлять download route;
- добавлять CreditLedger;
- добавлять ConversionCostEstimator;
- устанавливать Laravel Cashier;
- добавлять API endpoints;
- добавлять OpenAPI docs;
- сохранять presets в DB;
- добавлять batch conversion;
- добавлять OCR;
- добавлять React/Vue/Inertia;
- хардкодить forms через if source/target в Blade;
- хардкодить credit prices в UI.
```

---

# 12. Recommended Execution Order

```txt
CONV-096 Add Settings Step Guard
CONV-097 Create Dynamic Options Form Partial
CONV-098 Add Segmented Field Renderer
CONV-099 Add Select Field Renderer
CONV-100 Add Toggle Field Renderer
CONV-101 Add Color Field Renderer
CONV-102 Add Number And Range Field Renderer
CONV-103 Load Converter Options Schema On Target Selection
CONV-104 Initialize Default Converter Options
CONV-105 Render PNG To JPG Settings
CONV-106 Render PNG To PDF Settings
CONV-107 Add Settings Field Validation Errors
CONV-108 Validate Settings Before Convert Step
CONV-109 Preserve Settings State During Navigation
CONV-110 Add Estimated Cost Placeholder
CONV-111 Add Dynamic Settings Smoke Tests
```

---

# 13. Release

После завершения Phase 8:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh --seed

git checkout -b release/v0.1.8-phase08-dynamic-settings-form
git push -u origin release/v0.1.8-phase08-dynamic-settings-form
```

После этого сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.8-phase08-dynamic-settings-form -m "File Converter Phase 8 dynamic settings form"
git push origin v0.1.8-phase08-dynamic-settings-form
```
