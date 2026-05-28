# File Converter — Phase 7 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 7 — Target Format Step**  
Диапазон задач: **CONV-084 → CONV-095**  
Основа нумерации: Phase 6 завершилась на `CONV-083`, поэтому Phase 7 начинается с `CONV-084`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 7 соответствует блоку:

```txt
Phase 7 — Target Format Step
```

Правильный диапазон Phase 7:

```txt
CONV-084 — Add Format Step Guard
CONV-085 — Load Available Target Formats
CONV-086 — Create Target Format Card View Model
CONV-087 — Render Target Format Cards
CONV-088 — Add Recommended Target Marker
CONV-089 — Add Unsupported Source Format Empty State
CONV-090 — Test Target Format Selection
CONV-091 — Implement Target Format Selection
CONV-092 — Persist Selected Target Format In Component State
CONV-093 — Add Back Navigation From Format Step
CONV-094 — Add Target Format Step Loading And Error States
CONV-095 — Add Target Format Step Smoke Tests
```

Phase 7 добавляет второй шаг основного flow:

```txt
File uploaded → Choose target format
```

Важно:

```txt
Phase 6 = upload flow
Phase 7 = target format selection
Phase 8 = dynamic settings form
Phase 9 = conversion jobs
Phase 10 = real conversion drivers
```

То есть в Phase 7 пользователь уже выбирает целевой формат, но ещё **не видит настоящую форму настроек** и ещё **не запускает конвертацию**.

---

# 2. Цель Phase 7

Phase 7 должна превратить placeholder выбора формата из Phase 6 в реальный шаг выбора target format.

После Phase 7 authenticated user должен уметь:

```txt
- загрузить supported file на dashboard;
- попасть на шаг выбора формата;
- увидеть список доступных target formats для исходного файла;
- понять смысл каждого target format через label/description;
- увидеть recommended option, если он есть;
- выбрать target format;
- сохранить selected target format в состоянии компонента;
- перейти на settings placeholder step;
- вернуться назад к upload step без потери текущего file state;
- увидеть empty/error state, если для source format нет доступных converters.
```

Эта фаза проверяет связку:

```txt
DashboardConverter → FileRecord source format → ConverterRegistry → target format cards → selected target state
```

---

# 3. Scope Phase 7

## Входит

```txt
- format step guard;
- loading target formats from ConverterRegistry;
- target format card view model;
- target format cards UI;
- recommended target marker;
- converter label/description rendering;
- unsupported source format empty state;
- select target format action;
- selected target format component state;
- transition to settings placeholder step;
- back navigation from format step;
- loading/error states for target step;
- Livewire tests for target format rendering and selection;
- dashboard smoke tests for upload → format flow.
```

## Не входит

```txt
- dynamic settings form;
- rendering converter options schema;
- validating converter options;
- conversion jobs;
- real conversion execution;
- result download;
- recent conversions table implementation;
- credits/cost estimate;
- billing;
- Cashier;
- API endpoints;
- batch conversion;
- advanced search across all formats;
- /formats page;
- OCR page;
- Tools page.
```

---

# 4. Critical Decisions

## 4.1. Target format selection is card-first, not dropdown-first

Для MVP не использовать большой dropdown с форматами.

Неправильно:

```txt
Target format: [select with 200 options]
```

Правильно:

```txt
Convert PNG to:
[JPG]  Best for photos and sharing
[WEBP] Smaller modern web image
[PDF]  Create a PDF document
```

Причина: пользователь выбирает не просто extension, а сценарий конвертации.

## 4.2. Available targets must come from ConverterRegistry

Нельзя захардкодить cards в Blade:

```blade
JPG
WEBP
PDF
```

Правильно:

```php
app(ConverterRegistry::class)->targetsFor($sourceFormat)
```

UI должен быть следствием зарегистрированных converter capabilities.

## 4.3. Source of truth is current FileRecord

`DashboardConverter` должен брать source format из текущего `FileRecord`, а не из frontend state.

Правильно:

```php
$sourceFormat = $this->currentFile->extension;
```

или из normalized field, если оно есть:

```php
$sourceFormat = $this->currentFile->format;
```

Неправильно:

```php
$sourceFormat = $this->uploadedFileExtensionFromBrowser;
```

Browser-provided extension нельзя считать источником правды.

## 4.4. Selecting target does not create ConversionJob

В Phase 7 выбор target format только обновляет UI state:

```txt
selectedTargetFormat = jpg
step = settings
```

Нельзя создавать `conversion_jobs` в этой фазе. Это Phase 9.

## 4.5. Settings step is placeholder only

После выбора target format пользователь попадает на placeholder:

```txt
Settings for PNG → JPG will be added in Phase 8.
```

Нельзя в Phase 7 рендерить:

```txt
quality
resize
background color
page size
margin
```

Это Phase 8.

## 4.6. Unsupported source format must fail gracefully

Если source format поддержан upload-слоем, но нет доступных converters, UI не должен падать.

Правильно:

```txt
No conversion targets available for this file yet.
Upload another file or check supported formats later.
```

Неправильно:

```txt
Undefined array key
Call to null
Blank screen
```

## 4.7. Back navigation must preserve current file

Кнопка `Back` с format step должна возвращать на upload summary state, а не удалять файл.

Удаление файла — отдельное действие `Remove`.

---

# 5. Architecture Rules

## 5.1. DashboardConverter can orchestrate, but not own converter definitions

`DashboardConverter` может:

```txt
- спросить registry о target formats;
- сохранить selectedTargetFormat;
- переключить step.
```

Он не должен:

```txt
- знать все пары PNG→JPG, PNG→PDF;
- хранить descriptions в компоненте;
- валидировать converter options;
- запускать конвертацию.
```

## 5.2. Converter metadata belongs to converter capability classes

Label/description/recommended должны приходить из converter capability metadata или из thin view model, собранного из converter.

Допустимо:

```php
$converter->label()
$converter->description()
$converter->isRecommended()
```

Если `isRecommended()` ещё нет, Phase 7 может добавить его в interface и converter classes.

## 5.3. Use a TargetFormatCardViewModel if Blade becomes messy

Если в Blade начинается логика:

```php
$converter->targetFormat() === 'jpg' ? '...' : '...'
```

это надо вынести в:

```txt
app/ViewModels/TargetFormatCardViewModel.php
```

или application DTO.

## 5.4. No direct DB queries for converters

Converters в MVP — code/config registry, не database records.

Нельзя создавать `converters` table в Phase 7.

## 5.5. Tests must cover UI behavior, not implementation details only

Нужно тестировать:

```txt
- PNG shows JPG/WEBP/PDF;
- JPG shows PNG/WEBP/PDF;
- selecting JPG moves to settings placeholder;
- unsupported source shows empty state;
- back navigation preserves file.
```

Недостаточно тестировать только registry unit tests — они уже должны быть покрыты в Phase 3/4.

---

# 6. GitFlow для Phase 7

## Base branch

Все задачи Phase 7 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-084-add-format-step-guard
feature/CONV-087-render-target-format-cards
feature/CONV-091-implement-target-format-selection
```

## Commit format

```txt
CONV-084: Add format step guard
CONV-087: Render target format cards
CONV-091: Implement target format selection
```

## Release branch

После выполнения `CONV-084`–`CONV-095`:

```txt
release/v0.1.7-phase7-target-format-step
```

## Tag

После merge release branch в `main`:

```txt
v0.1.7-phase7-target-format-step
```

---

# 7. TDD Rules for Phase 7

## Для format step guard

Тестировать:

```txt
- format step cannot be opened without uploaded file;
- component redirects/returns to upload step if currentFileId is null;
- stale currentFileId is handled safely.
```

## Для target format rendering

Тестировать:

```txt
- PNG file shows JPG, WEBP, PDF target cards;
- JPG file shows PNG, WEBP, PDF target cards;
- unsupported source shows empty state;
- card labels/descriptions are visible.
```

## Для target selection

Тестировать:

```txt
- selecting supported target sets selectedTargetFormat;
- selecting supported target moves to settings step;
- selecting unsupported target is rejected;
- no ConversionJob is created.
```

## Для navigation

Тестировать:

```txt
- back from format returns to upload step;
- currentFileId remains unchanged;
- selectedTargetFormat resets when going back if appropriate.
```

Если тест напрямую невозможен, задача должна явно написать:

```txt
No direct test — причина.
```

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Dashboard / Livewire / Converter UI / Tests
Type: Test / Feature / UI / State / ViewModel
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

# 9. Phase 7 Atomic Tasks

---

## CONV-084 — Add Format Step Guard

**Area:** Dashboard / Livewire / State  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-084-add-format-step-guard`  
**Base branch:** `develop`  
**Depends on:** CONV-083

### Goal

Добавить защиту format step: пользователь не может попасть на выбор target format без загруженного файла.

### TDD step

Livewire test:

```php
use App\Livewire\DashboardConverter;
use Livewire\Livewire;

it('does not allow format step without uploaded file', function () {
    Livewire::test(DashboardConverter::class)
        ->set('step', 'format')
        ->call('ensureValidStep')
        ->assertSet('step', 'upload');
});
```

Если в компоненте нет public `ensureValidStep()`, тестировать через action, который открывает format step:

```php
it('returns to upload step when current file is missing', function () {
    Livewire::test(DashboardConverter::class)
        ->call('goToFormatStep')
        ->assertSet('step', 'upload')
        ->assertSee('Drop your files here');
});
```

Тест должен упасть до реализации guard.

### Implementation

В `DashboardConverter` добавить guard:

```php
public function goToFormatStep(): void
{
    if ($this->currentFileId === null || $this->currentFile === null) {
        $this->resetTargetSelection();
        $this->step = 'upload';
        return;
    }

    $this->step = 'format';
}
```

Если используется lifecycle hook:

```php
public function updatedStep(string $step): void
{
    if ($step === 'format' && $this->currentFile === null) {
        $this->step = 'upload';
    }
}
```

Не создавать target cards в этой задаче.

### Acceptance criteria

- Format step requires current file.
- Missing current file returns user to upload step.
- Stale currentFileId handled without exception.
- selectedTargetFormat is reset when file is missing.
- Тест проходит.

### Definition of Done

- Тест написан первым.
- Guard добавлен.
- Тест проходит.
- `composer test` проходит.
- `composer lint` проходит.
- Коммит: `CONV-084: Add format step guard`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-085 — Load Available Target Formats

**Area:** Dashboard / Livewire / Converter Registry  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-085-load-available-target-formats`  
**Base branch:** `develop`  
**Depends on:** CONV-084

### Goal

На format step загрузить доступные target formats для текущего `FileRecord` через `ConverterRegistry`.

### TDD step

Livewire test для PNG:

```php
it('loads available target formats for uploaded png file', function () {
    $user = User::factory()->create();

    $file = FileRecord::factory()->for($user)->create([
        'extension' => 'png',
    ]);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->assertSet('step', 'format')
        ->assertSee('JPG')
        ->assertSee('WEBP')
        ->assertSee('PDF');
});
```

Тест должен упасть до интеграции registry с компонентом.

### Implementation

В `DashboardConverter` добавить computed/helper:

```php
public function getAvailableTargetConvertersProperty(): array
{
    if (! $this->currentFile) {
        return [];
    }

    return app(ConverterRegistry::class)
        ->forSource($this->currentFile->extension)
        ->all();
}
```

Или метод:

```php
public function availableTargetConverters(): Collection
```

Важно: не делать direct hardcode по PNG/JPG в Livewire.

### Acceptance criteria

- Available target formats загружаются из ConverterRegistry.
- PNG получает JPG/WEBP/PDF.
- JPG получает PNG/WEBP/PDF.
- Missing current file returns empty list.
- Нет hardcoded target list в Blade/Livewire.
- Тест проходит.

### Definition of Done

- Тест написан первым.
- Registry подключён к DashboardConverter.
- Тест проходит.
- `composer test` проходит.
- `composer lint` проходит.
- Коммит: `CONV-085: Load available target formats`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
resources/views/livewire/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-086 — Create Target Format Card View Model

**Area:** Dashboard / ViewModel  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-086-create-target-format-card-view-model`  
**Base branch:** `develop`  
**Depends on:** CONV-085

### Goal

Создать view model/DTO для target format card, чтобы Blade не содержал лишнюю логику форматирования.

### TDD step

Unit test:

```php
it('creates target format card data from converter metadata', function () {
    $converter = app(ConverterRegistry::class)->find('png', 'jpg');

    $card = TargetFormatCardViewModel::fromConverter($converter);

    expect($card->targetFormat)->toBe('jpg');
    expect($card->label)->toBe('JPG');
    expect($card->description)->not->toBeEmpty();
});
```

Тест должен упасть до создания view model.

### Implementation

Создать:

```txt
app/ViewModels/TargetFormatCardViewModel.php
```

Пример:

```php
final readonly class TargetFormatCardViewModel
{
    public function __construct(
        public string $targetFormat,
        public string $label,
        public string $description,
        public bool $recommended = false,
        public ?string $badge = null,
    ) {}

    public static function fromConverter(Converter $converter): self
    {
        return new self(
            targetFormat: $converter->targetFormat(),
            label: strtoupper($converter->targetFormat()),
            description: $converter->description(),
            recommended: method_exists($converter, 'isRecommended') && $converter->isRecommended(),
        );
    }
}
```

Если `isRecommended()` отсутствует, оставить `false` и добавить recommended позже в CONV-088.

### Acceptance criteria

- ViewModel exists.
- ViewModel создаётся из Converter.
- Label/description/target format доступны как typed properties.
- Blade может использовать ViewModel без condition soup.
- Unit test passes.

### Definition of Done

- Тест написан первым.
- ViewModel создан.
- Тест проходит.
- `composer test` проходит.
- `composer lint` проходит.
- Коммит: `CONV-086: Create target format card view model`

### Files likely touched

```txt
app/ViewModels/TargetFormatCardViewModel.php
tests/Unit/ViewModels/TargetFormatCardViewModelTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-087 — Render Target Format Cards

**Area:** Dashboard / Livewire / UI  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-087-render-target-format-cards`  
**Base branch:** `develop`  
**Depends on:** CONV-086

### Goal

Отрендерить cards выбора целевого формата на format step.

### TDD step

Livewire test:

```php
it('renders target format cards for png file', function () {
    $user = User::factory()->create();

    $file = FileRecord::factory()->for($user)->create([
        'extension' => 'png',
    ]);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->assertSee('Convert PNG to')
        ->assertSee('JPG')
        ->assertSee('WEBP')
        ->assertSee('PDF');
});
```

### Implementation

В Blade для `step === 'format'` заменить placeholder на cards:

```blade
<h2>Convert {{ strtoupper($this->currentFile->extension) }} to</h2>

@foreach($this->targetFormatCards as $card)
    <button wire:click="selectTargetFormat('{{ $card->targetFormat }}')">
        <x-file-icon :format="$card->targetFormat" />
        <span>{{ $card->label }}</span>
        <p>{{ $card->description }}</p>
    </button>
@endforeach
```

Добавить computed:

```php
public function getTargetFormatCardsProperty(): array
{
    return $this->availableTargetConverters
        ->map(fn (Converter $converter) => TargetFormatCardViewModel::fromConverter($converter))
        ->all();
}
```

Если в проекте нет computed property style — использовать обычный public method.

### Acceptance criteria

- Format step shows heading `Convert PNG to` or equivalent.
- Target cards render from registry/view models.
- PNG shows JPG/WEBP/PDF.
- JPG shows PNG/WEBP/PDF.
- Cards are clickable but selection may still be implemented in CONV-091.
- Test passes.

### Definition of Done

- Тест написан первым.
- Target cards rendered.
- No hardcoded cards in Blade.
- Тест проходит.
- `composer test` проходит.
- `composer lint` проходит.
- `npm run build` проходит.
- Коммит: `CONV-087: Render target format cards`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
resources/views/livewire/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-088 — Add Recommended Target Marker

**Area:** Converter Metadata / UI  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-088-add-recommended-target-marker`  
**Base branch:** `develop`  
**Depends on:** CONV-087

### Goal

Добавить recommended marker для наиболее очевидного target format.

### TDD step

Unit test для converter metadata:

```php
it('marks png to jpg as recommended when converter defines it', function () {
    $converter = app(ConverterRegistry::class)->find('png', 'jpg');

    expect($converter->isRecommended())->toBeTrue();
});
```

Livewire/UI test:

```php
it('renders recommended badge for recommended target format', function () {
    $user = User::factory()->create();

    $file = FileRecord::factory()->for($user)->create([
        'extension' => 'png',
    ]);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->assertSee('Recommended');
});
```

### Implementation

Добавить в `Converter` interface:

```php
public function isRecommended(): bool;
```

Во все MVP converters добавить реализацию.

Рекомендация MVP:

```txt
PNG → JPG: recommended для sharing/photos
JPG → WEBP: recommended для web optimization
PNG → PDF: not default recommended unless source scenario is document-like
```

Если обязательное добавление метода в interface создаёт слишком много churn, можно сделать `recommended()` в metadata array, но interface чище.

В card UI:

```blade
@if($card->recommended)
    <x-badge variant="purple">Recommended</x-badge>
@endif
```

### Acceptance criteria

- Converter interface exposes recommendation state.
- At least one target for PNG/JPG can be recommended.
- Recommended badge renders in card.
- Non-recommended cards do not show badge.
- Tests pass.

### Definition of Done

- Тесты написаны.
- Recommended metadata добавлена.
- UI badge added.
- Тесты проходят.
- `composer test` проходит.
- `composer lint` проходит.
- `npm run build` проходит.
- Коммит: `CONV-088: Add recommended target marker`

### Files likely touched

```txt
app/Contracts/Converter.php
app/Converters/*
app/ViewModels/TargetFormatCardViewModel.php
resources/views/livewire/dashboard-converter.blade.php
tests/Unit/Converters/*Test.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-089 — Add Unsupported Source Format Empty State

**Area:** Dashboard / Livewire / UI  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-089-add-unsupported-source-format-empty-state`  
**Base branch:** `develop`  
**Depends on:** CONV-088

### Goal

Показать корректный empty state, если для текущего source format нет доступных target converters.

### TDD step

Livewire test:

```php
it('shows empty state when source format has no available targets', function () {
    $user = User::factory()->create();

    $file = FileRecord::factory()->for($user)->create([
        'extension' => 'pdf',
    ]);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->assertSee('No conversion targets available')
        ->assertSee('Upload another file');
});
```

Если PDF already has converters in current MVP, use fake unsupported extension in factory only if model allows it. Если model validation blocks it, use a supported-but-not-registered test format if available.

### Implementation

В format step Blade:

```blade
@if(empty($this->targetFormatCards))
    <x-card>
        <h2>No conversion targets available</h2>
        <p>We cannot convert this file type yet.</p>
        <x-button wire:click="backToUpload">Upload another file</x-button>
    </x-card>
@else
    {{-- render cards --}}
@endif
```

Не падать на пустом registry response.

### Acceptance criteria

- Empty target list does not crash UI.
- User sees clear message.
- User can go back/upload another file.
- No target cards render if list is empty.
- Test passes.

### Definition of Done

- Тест написан первым.
- Empty state added.
- Тест проходит.
- `composer test` проходит.
- `composer lint` проходит.
- `npm run build` проходит.
- Коммит: `CONV-089: Add unsupported source format empty state`

### Files likely touched

```txt
resources/views/livewire/dashboard-converter.blade.php
app/Livewire/DashboardConverter.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-090 — Test Target Format Selection

**Area:** Dashboard / Livewire / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-090-test-target-format-selection`  
**Base branch:** `develop`  
**Depends on:** CONV-089

### Goal

Написать падающие тесты для выбора target format.

### TDD step

Livewire test для successful selection:

```php
it('selects supported target format and moves to settings step', function () {
    $user = User::factory()->create();

    $file = FileRecord::factory()->for($user)->create([
        'extension' => 'png',
    ]);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('selectedTargetFormat', 'jpg')
        ->assertSet('step', 'settings')
        ->assertSee('Settings for PNG to JPG');
});
```

Test для unsupported target:

```php
it('rejects unsupported target format selection', function () {
    $user = User::factory()->create();

    $file = FileRecord::factory()->for($user)->create([
        'extension' => 'png',
    ]);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->call('selectTargetFormat', 'mp3')
        ->assertSet('step', 'format')
        ->assertSee('This conversion is not supported');
});
```

Тесты должны упасть до CONV-091.

### Implementation

Только добавить тесты.

Не реализовывать `selectTargetFormat()` в этой задаче, кроме stub если нужен для падения другого типа.

### Acceptance criteria

- Test for supported target selection exists.
- Test for unsupported target selection exists.
- Supported selection expects settings placeholder.
- Unsupported selection expects format step + error.
- Tests fail before implementation.

### Definition of Done

- Тесты добавлены.
- Тесты ожидаемо падают.
- Коммит: `CONV-090: Test target format selection`

### Files likely touched

```txt
tests/Feature/Livewire/DashboardConverterTargetFormatTest.php
```

После этого сделай MR в `develop`. Merge разрешён после review тестов. Допустимо, что новые тесты падают, если это принято в TDD-flow проекта. Если политика CI не допускает failing tests, объединить CONV-090 и CONV-091 в один MR нельзя без явного решения.

---

## CONV-091 — Implement Target Format Selection

**Area:** Dashboard / Livewire / State  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-091-implement-target-format-selection`  
**Base branch:** `develop`  
**Depends on:** CONV-090

### Goal

Реализовать выбор target format и переход на settings placeholder step.

### TDD step

Использовать падающие тесты из CONV-090.

### Implementation

В `DashboardConverter` добавить:

```php
public ?string $selectedTargetFormat = null;
public ?string $targetFormatError = null;
```

Метод:

```php
public function selectTargetFormat(string $targetFormat): void
{
    $this->targetFormatError = null;

    if (! $this->currentFile) {
        $this->step = 'upload';
        return;
    }

    $converter = app(ConverterRegistry::class)->find(
        $this->currentFile->extension,
        $targetFormat,
    );

    if ($converter === null) {
        $this->selectedTargetFormat = null;
        $this->targetFormatError = 'This conversion is not supported yet.';
        $this->step = 'format';
        return;
    }

    $this->selectedTargetFormat = $converter->targetFormat();
    $this->step = 'settings';
}
```

В settings step показать placeholder:

```blade
Settings for {{ strtoupper($this->currentFile->extension) }} to {{ strtoupper($selectedTargetFormat) }} will be added in Phase 8.
```

Не рендерить options schema.

Не создавать ConversionJob.

### Acceptance criteria

- Supported target sets selectedTargetFormat.
- Supported target moves step to settings.
- Settings placeholder visible.
- Unsupported target keeps user on format step.
- Unsupported target shows readable error.
- No ConversionJob created.
- Tests from CONV-090 pass.

### Definition of Done

- Selection implemented.
- Tests pass.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.
- Коммит: `CONV-091: Implement target format selection`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
resources/views/livewire/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterTargetFormatTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-092 — Persist Selected Target Format In Component State

**Area:** Dashboard / Livewire / State  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-092-persist-selected-target-format-in-component-state`  
**Base branch:** `develop`  
**Depends on:** CONV-091

### Goal

Уточнить и стабилизировать поведение `selectedTargetFormat` при переходах между шагами, replace/remove file и повторном выборе.

### TDD step

Livewire tests:

```php
it('resets selected target format when current file is removed', function () {
    $user = User::factory()->create();

    $file = FileRecord::factory()->for($user)->create([
        'extension' => 'png',
    ]);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->call('selectTargetFormat', 'jpg')
        ->call('removeCurrentFile')
        ->assertSet('selectedTargetFormat', null)
        ->assertSet('step', 'upload');
});
```

```php
it('resets selected target format when replacing file', function () {
    // setup file, select target, call replace action, assert null
});
```

### Implementation

Добавить helper:

```php
private function resetTargetSelection(): void
{
    $this->selectedTargetFormat = null;
    $this->targetFormatError = null;
}
```

Вызвать его в:

```txt
removeCurrentFile
replaceCurrentFile
failed upload
new successful upload before target selection
```

Не сбрасывать target при simple back from settings to format, если UX требует highlight selected card. Для MVP можно сбрасывать при back to upload, но не при back to format.

### Acceptance criteria

- selectedTargetFormat resets when file removed.
- selectedTargetFormat resets when replacing file.
- target error resets when choosing valid target.
- stale target from previous file cannot survive into new file flow.
- Tests pass.

### Definition of Done

- Тесты написаны.
- State reset behavior implemented.
- Tests pass.
- `composer test` passes.
- `composer lint` passes.
- Коммит: `CONV-092: Persist selected target format in component state`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
tests/Feature/Livewire/DashboardConverterTargetFormatTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-093 — Add Back Navigation From Format Step

**Area:** Dashboard / Livewire / Navigation  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-093-add-back-navigation-from-format-step`  
**Base branch:** `develop`  
**Depends on:** CONV-092

### Goal

Добавить корректную back navigation между upload summary, format step и settings placeholder.

### TDD step

Livewire test:

```php
it('goes back from format step to uploaded file summary without removing file', function () {
    $user = User::factory()->create();

    $file = FileRecord::factory()->for($user)->create([
        'extension' => 'png',
        'original_name' => 'image.png',
    ]);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->call('backToUploadSummary')
        ->assertSet('step', 'upload')
        ->assertSet('currentFileId', $file->id)
        ->assertSee('image.png');
});
```

Test from settings back to format:

```php
it('goes back from settings placeholder to format step', function () {
    // select target, call backToFormatStep, assert step format and file preserved
});
```

### Implementation

Добавить методы:

```php
public function backToUploadSummary(): void
{
    if (! $this->currentFile) {
        $this->step = 'upload';
        return;
    }

    $this->resetTargetSelection();
    $this->step = 'upload';
}

public function backToFormatStep(): void
{
    if (! $this->currentFile) {
        $this->step = 'upload';
        return;
    }

    $this->step = 'format';
}
```

В Blade добавить back buttons:

```blade
<x-button variant="ghost" wire:click="backToUploadSummary">Back</x-button>
<x-button variant="ghost" wire:click="backToFormatStep">Back</x-button>
```

### Acceptance criteria

- Back from format preserves current file.
- Back from settings returns to format.
- Back does not physically delete FileRecord.
- Back resets target only where appropriate.
- Tests pass.

### Definition of Done

- Тесты написаны.
- Back navigation implemented.
- Tests pass.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.
- Коммит: `CONV-093: Add back navigation from format step`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
resources/views/livewire/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterTargetFormatTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-094 — Add Target Format Step Loading And Error States

**Area:** Dashboard / Livewire / UX  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-094-add-target-format-step-loading-and-error-states`  
**Base branch:** `develop`  
**Depends on:** CONV-093

### Goal

Добавить аккуратные loading/error states для target format step и выбора target.

### TDD step

Livewire test for unsupported error already exists. Add UI assertion:

```php
it('shows unsupported conversion error message on invalid target selection', function () {
    $user = User::factory()->create();

    $file = FileRecord::factory()->for($user)->create([
        'extension' => 'png',
    ]);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('currentFileId', $file->id)
        ->call('goToFormatStep')
        ->call('selectTargetFormat', 'mp3')
        ->assertSee('This conversion is not supported yet.');
});
```

Loading state is mostly frontend/Livewire behavior; direct test optional.

### Implementation

В Blade:

```blade
<div wire:loading wire:target="selectTargetFormat">
    Loading converter settings...
</div>
```

Disable target cards while selecting:

```blade
<button wire:loading.attr="disabled" wire:target="selectTargetFormat">
```

Show error:

```blade
@if($targetFormatError)
    <x-alert variant="danger">{{ $targetFormatError }}</x-alert>
@endif
```

Если нет alert component, использовать existing card/badge или простой div. Не создавать большую notification system в этой фазе.

### Acceptance criteria

- Unsupported target error visible.
- Cards disabled while target selection is processing.
- Loading text/spinner visible during selection.
- Error clears after valid selection.
- Tests pass.

### Definition of Done

- Тест на error state есть.
- Loading/error UI added.
- Tests pass.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.
- Коммит: `CONV-094: Add target format step loading and error states`

### Files likely touched

```txt
resources/views/livewire/dashboard-converter.blade.php
app/Livewire/DashboardConverter.php
tests/Feature/Livewire/DashboardConverterTargetFormatTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-095 — Add Target Format Step Smoke Tests

**Area:** Tests / Dashboard / Regression  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-095-add-target-format-step-smoke-tests`  
**Base branch:** `develop`  
**Depends on:** CONV-094

### Goal

Добавить финальные smoke/regression tests, фиксирующие весь Phase 7 flow.

### TDD step

Feature/Livewire smoke test:

```php
it('completes upload to target format selection flow for png file', function () {
    $user = User::factory()->create();

    $upload = UploadedFile::fake()->image('avatar.png', 600, 400);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', $upload)
        ->call('uploadFile')
        ->assertSet('step', 'format')
        ->assertSee('Convert PNG to')
        ->assertSee('JPG')
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('selectedTargetFormat', 'jpg')
        ->assertSet('step', 'settings')
        ->assertSee('Settings for PNG to JPG');
});
```

Additional smoke:

```php
it('does not create conversion job during target selection', function () {
    // select target and assert ConversionJob::count() === 0
});
```

Если `ConversionJob` table ещё не существует в Phase 7, второй тест нельзя писать. В этом случае документировать:

```txt
No direct test — ConversionJob model does not exist before Phase 9.
```

### Implementation

Добавить smoke tests. Исправить только мелкие regression issues, если тесты выявили несовместимость.

Не добавлять новую функциональность.

### Acceptance criteria

- Full upload → format → settings placeholder flow covered.
- PNG target cards verified.
- Target selection verified.
- Settings placeholder verified.
- No Phase 8/9 functionality added.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.

### Definition of Done

- Smoke tests added.
- All Phase 7 tests pass.
- No new feature beyond tests/fixes.
- Коммит: `CONV-095: Add target format step smoke tests`

### Files likely touched

```txt
tests/Feature/Livewire/DashboardConverterTargetFormatSmokeTest.php
app/Livewire/DashboardConverter.php
resources/views/livewire/dashboard-converter.blade.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 7 Completion Criteria

Phase 7 завершена, когда:

```txt
- CONV-084–CONV-095 выполнены;
- user cannot open format step without uploaded file;
- available targets are loaded from ConverterRegistry;
- target format cards render from converter metadata/view models;
- PNG shows JPG/WEBP/PDF targets;
- JPG shows PNG/WEBP/PDF targets;
- recommended marker renders where configured;
- unsupported/no-target state is handled gracefully;
- selecting supported target sets selectedTargetFormat;
- selecting supported target moves to settings placeholder;
- selecting unsupported target shows readable error;
- back navigation works;
- current file is preserved when navigating back;
- selected target resets when file is removed/replaced;
- no ConversionJob is created;
- no dynamic settings form is implemented;
- no conversion execution is implemented;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 7

Без отдельной задачи нельзя:

```txt
- создавать DynamicOptionsForm;
- рендерить quality/resize/page size/margin settings;
- валидировать converter options;
- создавать conversion_jobs table;
- создавать CreateConversionJobAction;
- запускать конвертацию;
- создавать result download route;
- добавлять credits/cost estimator;
- добавлять billing;
- добавлять Cashier;
- добавлять API endpoints;
- делать /formats page;
- делать OCR page;
- делать Tools page;
- делать batch conversion;
- добавлять direct-to-S3 upload;
- добавлять image processing drivers.
```

---

# 12. Recommended Execution Order

```txt
CONV-084 Add Format Step Guard
CONV-085 Load Available Target Formats
CONV-086 Create Target Format Card View Model
CONV-087 Render Target Format Cards
CONV-088 Add Recommended Target Marker
CONV-089 Add Unsupported Source Format Empty State
CONV-090 Test Target Format Selection
CONV-091 Implement Target Format Selection
CONV-092 Persist Selected Target Format In Component State
CONV-093 Add Back Navigation From Format Step
CONV-094 Add Target Format Step Loading And Error States
CONV-095 Add Target Format Step Smoke Tests
```

---

# 13. Release

После завершения Phase 7:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh --seed

git checkout -b release/v0.1.7-phase7-target-format-step
git push -u origin release/v0.1.7-phase7-target-format-step
```

После этого сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.7-phase7-target-format-step -m "File Converter Phase 7 target format step"
git push origin v0.1.7-phase7-target-format-step
```
