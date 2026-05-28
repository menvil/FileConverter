# File Converter — Phase 04 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 04 — MVP Converter Capabilities**  
Диапазон задач: **CONV-046 → CONV-057**  
Основа нумерации: Phase 03 завершилась на `CONV-045`, поэтому Phase 04 начинается с `CONV-046`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 04 соответствует блоку:

```txt
Phase 04 — MVP Converter Capabilities
```

Правильный диапазон Phase 04:

```txt
CONV-046 — Create MVP Converter Capability List
CONV-047 — Test PNG Target Capabilities
CONV-048 — Add PNG To JPG Converter Capability
CONV-049 — Add PNG To WEBP Converter Capability
CONV-050 — Add PNG To PDF Converter Capability
CONV-051 — Test JPG Target Capabilities
CONV-052 — Add JPG To PNG Converter Capability
CONV-053 — Add JPG To WEBP Converter Capability
CONV-054 — Add JPG To PDF Converter Capability
CONV-055 — Add Recommended Target Metadata
CONV-056 — Register MVP Converter Capabilities
CONV-057 — Add Converter Catalog Smoke Tests
```

Phase 04 добавляет **описания первых MVP-конвертеров**, но ещё не выполняет реальную конвертацию файлов.

Важно:

```txt
Phase 04 = converter metadata / capability layer
Phase 10 = real image conversion drivers
```

То есть в этой фазе появляются:

```txt
source format
target format
label
description
recommended flag
options schema
options defaults
validation through existing OptionsValidator
registry registration
```

Но не появляются:

```txt
Imagick
Intervention Image
PDF generation
queues
conversion jobs
file upload
```

---

# 2. Цель Phase 04

Phase 04 должна превратить абстрактное ядро из Phase 03 в конкретный MVP-набор supported conversions.

MVP-набор:

```txt
PNG → JPG
PNG → WEBP
PNG → PDF
JPG → PNG
JPG → WEBP
JPG → PDF
```

После Phase 04 система должна уметь ответить на вопросы:

```txt
- во что можно конвертировать PNG?
- во что можно конвертировать JPG?
- какие настройки есть у PNG → JPG?
- какие настройки есть у PNG → PDF?
- какой target format recommended?
- какая schema defaults валидна?
- какая source/target пара unsupported?
```

Но система ещё не должна:

```txt
- принимать файлы;
- сохранять файлы;
- запускать conversion job;
- создавать output file;
- списывать credits;
- показывать UI target cards.
```

Это чистая capability-фаза.

---

# 3. Scope Phase 04

## Входит

```txt
- PNG → JPG converter capability;
- PNG → WEBP converter capability;
- PNG → PDF converter capability;
- JPG → PNG converter capability;
- JPG → WEBP converter capability;
- JPG → PDF converter capability;
- labels/descriptions for target cards;
- recommended target metadata;
- options schema for each converter pair;
- default option validation;
- registry registration;
- tests for source → targets;
- tests for exact converter lookup;
- tests for unsupported conversion pairs.
```

## Не входит

```txt
- real conversion drivers;
- image processing library installation;
- file upload;
- files table;
- conversion_jobs table;
- queues;
- dashboard upload flow;
- dynamic settings form UI;
- billing/credits;
- API endpoints;
- OpenAPI documentation;
- PDF parsing;
- OCR;
- video/audio/document converters;
- batch conversion.
```

---

# 4. Critical Decisions

## 4.1. Converter capability is not a driver

В Phase 04 нельзя смешивать metadata-конвертер и real conversion driver.

Правильно:

```php
final class PngToJpgConverter implements Converter
{
    public function sourceFormat(): string { return 'png'; }
    public function targetFormat(): string { return 'jpg'; }
    public function optionsSchema(): array { ... }
}
```

Неправильно:

```php
final class PngToJpgConverter
{
    public function convert($file) { ... }
}
```

Реальное выполнение появится позже через `ConverterDriver`.

## 4.2. Source → target pair owns settings

Настройки принадлежат паре `source → target`, а не исходному формату.

Правильно:

```txt
PNG → JPG has background color for transparency
PNG → PDF has page size, margin, fit mode
```

Неправильно:

```txt
PNG has one universal settings form
```

Это критично. Иначе UI быстро превратится в набор бессмысленных generic fields.

## 4.3. Labels and descriptions are product data

Converter metadata должна содержать текст для target selection cards.

Пример:

```txt
JPG
Best for photos and sharing
Recommended
```

Это не просто технический список форматов.

## 4.4. Recommended target is allowed, but only one per source by default

Для MVP:

```txt
PNG recommended target: JPG or WEBP, depending product decision
JPG recommended target: WEBP
```

Рекомендация:

```txt
PNG → JPG recommended for simple users
JPG → WEBP recommended for smaller web images
```

Не делать несколько `recommended = true` для одного source, если нет явной логики.

## 4.5. No fake “100+ formats” in code

В Phase 04 поддерживаются только реальные capability-записи:

```txt
png → jpg/webp/pdf
jpg → png/webp/pdf
```

Не добавлять placeholder converters:

```txt
mp4 → mp3
pdf → docx
docx → pdf
heic → jpg
```

Пока их нет — их нет.

---

# 5. Architecture Rules

## 5.1. Resource location

Рекомендуемая структура:

```txt
app/Converters/Contracts/Converter.php
app/Converters/Registry/ConverterRegistry.php
app/Converters/Image/PngToJpgConverter.php
app/Converters/Image/PngToWebpConverter.php
app/Converters/Image/PngToPdfConverter.php
app/Converters/Image/JpgToPngConverter.php
app/Converters/Image/JpgToWebpConverter.php
app/Converters/Image/JpgToPdfConverter.php
```

Адаптировать под фактическую структуру Phase 03, если там уже зафиксирован другой namespace.

## 5.2. Registration must be explicit

Для MVP лучше explicit registration, а не auto-discovery.

Правильно:

```php
ConverterRegistry::make([
    new PngToJpgConverter(),
    new PngToWebpConverter(),
    new PngToPdfConverter(),
]);
```

Неправильно в MVP:

```txt
scan all classes in app/Converters recursively
```

Auto-discovery можно добавить позже, когда появится много модулей.

## 5.3. Schemas must be testable arrays/DTOs

`optionsSchema()` должен возвращать предсказуемую структуру, которую уже умеет валидировать schema validator из Phase 03.

Нельзя возвращать Blade, HTML или Livewire-specific config.

## 5.4. Defaults must be valid

Для каждого converter capability:

```txt
default options must pass OptionsValidator
```

Если default options невалидны, UI сломается сразу после выбора target format.

## 5.5. Converter descriptions must stay short

Descriptions используются в cards. Не писать длинные маркетинговые абзацы.

Хорошо:

```txt
Best for photos and sharing
Smaller modern image for websites
Create a PDF document from image
```

Плохо:

```txt
A comprehensive professional transformation workflow suitable for high-resolution cross-platform export...
```

---

# 6. GitFlow для Phase 04

## Base branch

Все задачи Phase 04 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-046-create-mvp-converter-capability-list
feature/CONV-048-add-png-to-jpg-converter-capability
feature/CONV-056-register-mvp-converter-capabilities
```

## Commit format

```txt
CONV-046: Create MVP converter capability list
CONV-048: Add PNG to JPG converter capability
CONV-056: Register MVP converter capabilities
```

## Release branch

После выполнения `CONV-046`–`CONV-057`:

```txt
release/v0.1.4-phase04-mvp-converter-capabilities
```

## Tag

После merge release branch в `main`:

```txt
v0.1.4-phase04-mvp-converter-capabilities
```

---

# 7. TDD Rules for Phase 04

## Для converter capabilities

Каждый converter capability должен иметь tests:

```txt
- source format;
- target format;
- key;
- label;
- description;
- options schema exists;
- default options are valid.
```

## Для source target lists

Тестировать:

```txt
- PNG returns JPG, WEBP, PDF;
- JPG returns PNG, WEBP, PDF;
- unsupported source returns empty list;
- unsupported target pair returns null.
```

## Для recommended metadata

Тестировать:

```txt
- recommended target exists for PNG;
- recommended target exists for JPG;
- no more than one recommended target per source unless explicitly allowed.
```

## Для schema

Каждая schema должна проходить через validator из Phase 03.

Если задача не имеет прямого теста, указать:

```txt
No direct test — reason
```

Но почти все задачи Phase 04 тестируемые.

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Converters / Capability / Registry / Tests
Type: Test / Feature / Metadata / Registry
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
- Нет functionality outside scope
- Нет real conversion logic
- Коммит содержит ID задачи

Files likely touched:
- path/to/file
```

---

# 9. Phase 04 Atomic Tasks

---

## CONV-046 — Create MVP Converter Capability List

**Area:** Converters / Product Scope  
**Type:** Config / Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-046-create-mvp-converter-capability-list`  
**Base branch:** `develop`  
**Depends on:** CONV-045

### Goal

Зафиксировать MVP-список supported conversion capabilities в коде или config, чтобы следующие задачи добавляли только утверждённые направления.

### TDD step

Unit test:

```php
it('defines the mvp converter capability list', function () {
    $capabilities = config('converters.mvp_capabilities');

    expect($capabilities)->toContain('png:jpg');
    expect($capabilities)->toContain('png:webp');
    expect($capabilities)->toContain('png:pdf');
    expect($capabilities)->toContain('jpg:png');
    expect($capabilities)->toContain('jpg:webp');
    expect($capabilities)->toContain('jpg:pdf');
});
```

Если проект не использует config для capability list, тест может проверять static provider/registry seed.

### Implementation

Создать config:

```txt
config/converters.php
```

Минимальная структура:

```php
return [
    'mvp_capabilities' => [
        'png:jpg',
        'png:webp',
        'png:pdf',
        'jpg:png',
        'jpg:webp',
        'jpg:pdf',
    ],
];
```

Не регистрировать реальные converter classes в этой задаче.

### Acceptance criteria

- MVP capability list существует.
- Список содержит ровно первые шесть направлений.
- Нет video/audio/document/OCR направлений.
- Тест проходит.

### Definition of Done

- Тест написан первым.
- Config/list создан.
- Тест проходит.
- `composer test` проходит.
- `composer lint` проходит.
- Коммит: `CONV-046: Create MVP converter capability list`

### Files likely touched

```txt
config/converters.php
tests/Unit/Converters/MvpConverterCapabilityListTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-047 — Test PNG Target Capabilities

**Area:** Converters / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-047-test-png-target-capabilities`  
**Base branch:** `develop`  
**Depends on:** CONV-046

### Goal

Написать падающий тест: для `png` registry должен возвращать target formats `jpg`, `webp`, `pdf`.

### TDD step

Unit test:

```php
it('lists png target capabilities', function () {
    $registry = app(ConverterRegistry::class);

    $targets = collect($registry->targetsFor('png'))
        ->map(fn ($target) => $target->format)
        ->all();

    expect($targets)->toContain('jpg');
    expect($targets)->toContain('webp');
    expect($targets)->toContain('pdf');
    expect($targets)->not->toContain('mp3');
});
```

Тест должен упасть до добавления PNG converter capabilities.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Тест проверяет `png → jpg/webp/pdf`.
- Тест проверяет отсутствие мусорного `png → mp3`.
- Тест ожидаемо падает до CONV-048/049/050/056.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-047: Test PNG target capabilities`

### Files likely touched

```txt
tests/Unit/Converters/ConverterRegistryTargetsTest.php
```

После этого сделай MR в `develop`. Merge этой test-only задачи допустим только если в проекте принято мержить падающие TDD-тесты. Если нет — выполнять вместе с CONV-048/049/050/056 в одном рабочем цикле, но коммит оставить отдельным.

---

## CONV-048 — Add PNG To JPG Converter Capability

**Area:** Converters / Image  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-048-add-png-to-jpg-converter-capability`  
**Base branch:** `develop`  
**Depends on:** CONV-047

### Goal

Добавить converter capability `PNG → JPG` с options schema.

### TDD step

Unit test:

```php
it('defines png to jpg converter capability', function () {
    $converter = new PngToJpgConverter();

    expect($converter->key())->toBe('png:jpg');
    expect($converter->sourceFormat())->toBe('png');
    expect($converter->targetFormat())->toBe('jpg');
    expect($converter->label())->toBe('JPG');
    expect($converter->description())->not->toBeEmpty();
});
```

Schema/default test:

```php
it('provides valid default options for png to jpg', function () {
    $converter = new PngToJpgConverter();

    $options = app(OptionsValidator::class)->validate(
        schema: $converter->optionsSchema(),
        options: []
    );

    expect($options)->toHaveKey('quality');
    expect($options)->toHaveKey('background_color');
});
```

### Implementation

Создать converter class:

```txt
app/Converters/Image/PngToJpgConverter.php
```

Options schema:

```txt
quality: segmented/select, default high, options medium/high/best
resize: select/segmented, default original, options original/1920/1280/custom
background_color: color, default #ffffff
remove_metadata: toggle, default true
```

Recommended description:

```txt
Best for photos and sharing
```

Не делать реальную конвертацию.

### Acceptance criteria

- `PngToJpgConverter` существует.
- `key = png:jpg`.
- Source `png`.
- Target `jpg`.
- Schema содержит `quality`, `resize`, `background_color`, `remove_metadata`.
- Defaults проходят OptionsValidator.
- Нет driver/Imagick/Intervention logic.
- Тесты проходят.

### Definition of Done

- Тест написан первым.
- Converter capability добавлен.
- Defaults valid.
- Tests pass.
- Коммит: `CONV-048: Add PNG to JPG converter capability`

### Files likely touched

```txt
app/Converters/Image/PngToJpgConverter.php
tests/Unit/Converters/Image/PngToJpgConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-049 — Add PNG To WEBP Converter Capability

**Area:** Converters / Image  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-049-add-png-to-webp-converter-capability`  
**Base branch:** `develop`  
**Depends on:** CONV-048

### Goal

Добавить converter capability `PNG → WEBP` с options schema.

### TDD step

Unit test:

```php
it('defines png to webp converter capability', function () {
    $converter = new PngToWebpConverter();

    expect($converter->key())->toBe('png:webp');
    expect($converter->sourceFormat())->toBe('png');
    expect($converter->targetFormat())->toBe('webp');
    expect($converter->label())->toBe('WEBP');
});
```

Schema/default test:

```php
it('provides valid default options for png to webp', function () {
    $converter = new PngToWebpConverter();

    $options = app(OptionsValidator::class)->validate(
        schema: $converter->optionsSchema(),
        options: []
    );

    expect($options)->toHaveKey('quality');
    expect($options)->toHaveKey('lossless');
});
```

### Implementation

Создать:

```txt
app/Converters/Image/PngToWebpConverter.php
```

Options schema:

```txt
quality: segmented/select, default high
lossless: toggle, default false
resize: select/segmented, default original
remove_metadata: toggle, default true
```

Description:

```txt
Smaller modern image for websites
```

### Acceptance criteria

- `PngToWebpConverter` существует.
- `key = png:webp`.
- Source `png`.
- Target `webp`.
- Schema содержит `quality`, `lossless`, `resize`, `remove_metadata`.
- Defaults valid.
- No real conversion logic.

### Definition of Done

- Тест написан первым.
- Converter capability добавлен.
- Tests pass.
- Коммит: `CONV-049: Add PNG to WEBP converter capability`

### Files likely touched

```txt
app/Converters/Image/PngToWebpConverter.php
tests/Unit/Converters/Image/PngToWebpConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-050 — Add PNG To PDF Converter Capability

**Area:** Converters / Image  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-050-add-png-to-pdf-converter-capability`  
**Base branch:** `develop`  
**Depends on:** CONV-049

### Goal

Добавить converter capability `PNG → PDF` с settings, отличными от `PNG → JPG`.

### TDD step

Unit test:

```php
it('defines png to pdf converter capability', function () {
    $converter = new PngToPdfConverter();

    expect($converter->key())->toBe('png:pdf');
    expect($converter->sourceFormat())->toBe('png');
    expect($converter->targetFormat())->toBe('pdf');
    expect($converter->label())->toBe('PDF');
});
```

Schema/default test:

```php
it('provides valid default options for png to pdf', function () {
    $converter = new PngToPdfConverter();

    $options = app(OptionsValidator::class)->validate(
        schema: $converter->optionsSchema(),
        options: []
    );

    expect($options)->toHaveKey('page_size');
    expect($options)->toHaveKey('orientation');
    expect($options)->toHaveKey('margin');
    expect($options)->toHaveKey('fit_mode');
});
```

### Implementation

Создать:

```txt
app/Converters/Image/PngToPdfConverter.php
```

Options schema:

```txt
page_size: segmented/select, default auto, options auto/a4/letter
orientation: segmented, default auto, options auto/portrait/landscape
margin: segmented, default small, options none/small/medium
fit_mode: segmented, default contain, options contain/cover/original
compression: segmented, default balanced, options none/balanced/small
```

Description:

```txt
Create a PDF document from image
```

### Acceptance criteria

- `PngToPdfConverter` существует.
- `key = png:pdf`.
- Schema отличается от PNG→JPG.
- Schema содержит PDF-specific fields.
- Defaults valid.
- No PDF generation logic.

### Definition of Done

- Тест написан первым.
- Converter capability добавлен.
- Tests pass.
- Коммит: `CONV-050: Add PNG to PDF converter capability`

### Files likely touched

```txt
app/Converters/Image/PngToPdfConverter.php
tests/Unit/Converters/Image/PngToPdfConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-051 — Test JPG Target Capabilities

**Area:** Converters / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-051-test-jpg-target-capabilities`  
**Base branch:** `develop`  
**Depends on:** CONV-050

### Goal

Написать падающий тест: для `jpg` registry должен возвращать target formats `png`, `webp`, `pdf`.

### TDD step

Unit test:

```php
it('lists jpg target capabilities', function () {
    $registry = app(ConverterRegistry::class);

    $targets = collect($registry->targetsFor('jpg'))
        ->map(fn ($target) => $target->format)
        ->all();

    expect($targets)->toContain('png');
    expect($targets)->toContain('webp');
    expect($targets)->toContain('pdf');
    expect($targets)->not->toContain('mp3');
});
```

Тест должен упасть до добавления JPG converter capabilities и registry registration.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Тест проверяет `jpg → png/webp/pdf`.
- Тест проверяет отсутствие `jpg → mp3`.
- Тест ожидаемо падает до CONV-052/053/054/056.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-051: Test JPG target capabilities`

### Files likely touched

```txt
tests/Unit/Converters/ConverterRegistryTargetsTest.php
```

После этого сделай MR в `develop`. Merge этой test-only задачи допустим только если в проекте принято мержить падающие TDD-тесты. Если нет — выполнять вместе с CONV-052/053/054/056 в одном рабочем цикле, но коммит оставить отдельным.

---

## CONV-052 — Add JPG To PNG Converter Capability

**Area:** Converters / Image  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-052-add-jpg-to-png-converter-capability`  
**Base branch:** `develop`  
**Depends on:** CONV-051

### Goal

Добавить converter capability `JPG → PNG`.

### TDD step

Unit test:

```php
it('defines jpg to png converter capability', function () {
    $converter = new JpgToPngConverter();

    expect($converter->key())->toBe('jpg:png');
    expect($converter->sourceFormat())->toBe('jpg');
    expect($converter->targetFormat())->toBe('png');
    expect($converter->label())->toBe('PNG');
});
```

Schema/default test:

```php
it('provides valid default options for jpg to png', function () {
    $converter = new JpgToPngConverter();

    $options = app(OptionsValidator::class)->validate(
        schema: $converter->optionsSchema(),
        options: []
    );

    expect($options)->toHaveKey('resize');
    expect($options)->toHaveKey('remove_metadata');
});
```

### Implementation

Создать:

```txt
app/Converters/Image/JpgToPngConverter.php
```

Options schema:

```txt
resize: select/segmented, default original
remove_metadata: toggle, default true
```

Description:

```txt
Convert photo to lossless PNG image
```

Не обещать transparency — JPG не имеет transparency.

### Acceptance criteria

- `JpgToPngConverter` существует.
- `key = jpg:png`.
- Source `jpg`.
- Target `png`.
- Schema содержит `resize`, `remove_metadata`.
- Defaults valid.
- No fake transparency option.

### Definition of Done

- Тест написан первым.
- Converter capability добавлен.
- Tests pass.
- Коммит: `CONV-052: Add JPG to PNG converter capability`

### Files likely touched

```txt
app/Converters/Image/JpgToPngConverter.php
tests/Unit/Converters/Image/JpgToPngConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-053 — Add JPG To WEBP Converter Capability

**Area:** Converters / Image  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-053-add-jpg-to-webp-converter-capability`  
**Base branch:** `develop`  
**Depends on:** CONV-052

### Goal

Добавить converter capability `JPG → WEBP`.

### TDD step

Unit test:

```php
it('defines jpg to webp converter capability', function () {
    $converter = new JpgToWebpConverter();

    expect($converter->key())->toBe('jpg:webp');
    expect($converter->sourceFormat())->toBe('jpg');
    expect($converter->targetFormat())->toBe('webp');
    expect($converter->label())->toBe('WEBP');
});
```

Schema/default test:

```php
it('provides valid default options for jpg to webp', function () {
    $converter = new JpgToWebpConverter();

    $options = app(OptionsValidator::class)->validate(
        schema: $converter->optionsSchema(),
        options: []
    );

    expect($options)->toHaveKey('quality');
    expect($options)->toHaveKey('resize');
});
```

### Implementation

Создать:

```txt
app/Converters/Image/JpgToWebpConverter.php
```

Options schema:

```txt
quality: segmented/select, default high
resize: select/segmented, default original
remove_metadata: toggle, default true
```

Description:

```txt
Smaller modern image for websites
```

### Acceptance criteria

- `JpgToWebpConverter` существует.
- `key = jpg:webp`.
- Source `jpg`.
- Target `webp`.
- Schema valid.
- Defaults valid.
- No real conversion logic.

### Definition of Done

- Тест написан первым.
- Converter capability добавлен.
- Tests pass.
- Коммит: `CONV-053: Add JPG to WEBP converter capability`

### Files likely touched

```txt
app/Converters/Image/JpgToWebpConverter.php
tests/Unit/Converters/Image/JpgToWebpConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-054 — Add JPG To PDF Converter Capability

**Area:** Converters / Image  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-054-add-jpg-to-pdf-converter-capability`  
**Base branch:** `develop`  
**Depends on:** CONV-053

### Goal

Добавить converter capability `JPG → PDF`.

### TDD step

Unit test:

```php
it('defines jpg to pdf converter capability', function () {
    $converter = new JpgToPdfConverter();

    expect($converter->key())->toBe('jpg:pdf');
    expect($converter->sourceFormat())->toBe('jpg');
    expect($converter->targetFormat())->toBe('pdf');
    expect($converter->label())->toBe('PDF');
});
```

Schema/default test:

```php
it('provides valid default options for jpg to pdf', function () {
    $converter = new JpgToPdfConverter();

    $options = app(OptionsValidator::class)->validate(
        schema: $converter->optionsSchema(),
        options: []
    );

    expect($options)->toHaveKey('page_size');
    expect($options)->toHaveKey('fit_mode');
});
```

### Implementation

Создать:

```txt
app/Converters/Image/JpgToPdfConverter.php
```

Options schema аналогична PNG→PDF:

```txt
page_size: auto/a4/letter
orientation: auto/portrait/landscape
margin: none/small/medium
fit_mode: contain/cover/original
compression: none/balanced/small
```

Description:

```txt
Create a PDF document from image
```

### Acceptance criteria

- `JpgToPdfConverter` существует.
- `key = jpg:pdf`.
- Source `jpg`.
- Target `pdf`.
- PDF-specific schema exists.
- Defaults valid.
- No PDF generation logic.

### Definition of Done

- Тест написан первым.
- Converter capability добавлен.
- Tests pass.
- Коммит: `CONV-054: Add JPG to PDF converter capability`

### Files likely touched

```txt
app/Converters/Image/JpgToPdfConverter.php
tests/Unit/Converters/Image/JpgToPdfConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-055 — Add Recommended Target Metadata

**Area:** Converters / Product Metadata  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-055-add-recommended-target-metadata`  
**Base branch:** `develop`  
**Depends on:** CONV-054

### Goal

Добавить metadata для recommended target format, чтобы будущий UI мог показывать бейдж `Recommended` на карточках выбора формата.

### TDD step

Unit test:

```php
it('marks one png target as recommended', function () {
    $registry = app(ConverterRegistry::class);

    $recommended = collect($registry->targetsFor('png'))
        ->filter(fn ($target) => $target->recommended === true);

    expect($recommended)->toHaveCount(1);
});
```

JPG test:

```php
it('marks one jpg target as recommended', function () {
    $registry = app(ConverterRegistry::class);

    $recommended = collect($registry->targetsFor('jpg'))
        ->filter(fn ($target) => $target->recommended === true);

    expect($recommended)->toHaveCount(1);
});
```

### Implementation

Добавить в `ConverterTarget` или converter metadata поле:

```php
public bool $recommended = false;
```

Рекомендация для MVP:

```txt
PNG → JPG recommended
JPG → WEBP recommended
```

Если Phase 03 `ConverterTarget DTO` не имеет поля, расширить DTO.

### Acceptance criteria

- Target DTO поддерживает `recommended`.
- Для PNG ровно один recommended target.
- Для JPG ровно один recommended target.
- Recommended metadata берётся из converter metadata, а не hardcoded UI.
- Tests pass.

### Definition of Done

- Тест написан первым.
- DTO/metadata расширены.
- Recommended targets выставлены.
- Tests pass.
- Коммит: `CONV-055: Add recommended target metadata`

### Files likely touched

```txt
app/Converters/DTO/ConverterTarget.php
app/Converters/Image/PngToJpgConverter.php
app/Converters/Image/JpgToWebpConverter.php
tests/Unit/Converters/RecommendedTargetMetadataTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-056 — Register MVP Converter Capabilities

**Area:** Converters / Registry  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-056-register-mvp-converter-capabilities`  
**Base branch:** `develop`  
**Depends on:** CONV-055

### Goal

Зарегистрировать все шесть MVP converter capabilities в `ConverterRegistry`.

### TDD step

Использовать падающие tests из CONV-047 и CONV-051.

Дополнительный exact lookup test:

```php
it('finds exact mvp converter pairs', function () {
    $registry = app(ConverterRegistry::class);

    expect($registry->find('png', 'jpg'))->not->toBeNull();
    expect($registry->find('png', 'webp'))->not->toBeNull();
    expect($registry->find('png', 'pdf'))->not->toBeNull();
    expect($registry->find('jpg', 'png'))->not->toBeNull();
    expect($registry->find('jpg', 'webp'))->not->toBeNull();
    expect($registry->find('jpg', 'pdf'))->not->toBeNull();
});
```

Unsupported test:

```php
it('does not find unsupported converter pairs', function () {
    $registry = app(ConverterRegistry::class);

    expect($registry->find('png', 'mp3'))->toBeNull();
    expect($registry->find('pdf', 'docx'))->toBeNull();
});
```

### Implementation

Зарегистрировать converters через service provider или registry factory.

Пример:

```php
$this->app->singleton(ConverterRegistry::class, function () {
    return new ConverterRegistry([
        new PngToJpgConverter(),
        new PngToWebpConverter(),
        new PngToPdfConverter(),
        new JpgToPngConverter(),
        new JpgToWebpConverter(),
        new JpgToPdfConverter(),
    ]);
});
```

Если Phase 03 уже выбрала другой механизм регистрации, использовать его.

### Acceptance criteria

- PNG targets: JPG, WEBP, PDF.
- JPG targets: PNG, WEBP, PDF.
- Exact lookup works for all six pairs.
- Unsupported pairs return null.
- Registry registration explicit.
- Tests pass.

### Definition of Done

- Registry registration добавлена.
- Тесты CONV-047/051 проходят.
- Exact lookup tests проходят.
- Unsupported tests проходят.
- Коммит: `CONV-056: Register MVP converter capabilities`

### Files likely touched

```txt
app/Providers/AppServiceProvider.php
app/Providers/ConverterServiceProvider.php
app/Converters/Registry/ConverterRegistry.php
tests/Unit/Converters/ConverterRegistryTargetsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-057 — Add Converter Catalog Smoke Tests

**Area:** Converters / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-057-add-converter-catalog-smoke-tests`  
**Base branch:** `develop`  
**Depends on:** CONV-056

### Goal

Добавить финальные smoke tests для всего MVP converter catalog, чтобы Phase 04 имела жёсткий completion safety net.

### TDD step

Catalog test:

```php
it('contains only the expected mvp converter capabilities', function () {
    $registry = app(ConverterRegistry::class);

    $keys = collect($registry->all())
        ->map(fn ($converter) => $converter->key())
        ->sort()
        ->values()
        ->all();

    expect($keys)->toBe([
        'jpg:pdf',
        'jpg:png',
        'jpg:webp',
        'png:jpg',
        'png:pdf',
        'png:webp',
    ]);
});
```

Schema validity test:

```php
it('has valid schemas and default options for every mvp converter', function () {
    $registry = app(ConverterRegistry::class);
    $schemaValidator = app(OptionsSchemaValidator::class);
    $optionsValidator = app(OptionsValidator::class);

    foreach ($registry->all() as $converter) {
        $schemaValidator->validate($converter->optionsSchema());

        $options = $optionsValidator->validate(
            schema: $converter->optionsSchema(),
            options: []
        );

        expect($options)->toBeArray();
    }
});
```

### Implementation

Добавить только tests. Если tests падают — исправлять только реальные несоответствия capability metadata/registration.

Не добавлять новые converters.

### Acceptance criteria

- Catalog содержит ровно шесть MVP converters.
- Все schemas валидны.
- Все default options валидны.
- Unsupported converters отсутствуют.
- Tests pass.

### Definition of Done

- Smoke tests добавлены.
- Все Phase 04 tests проходят.
- `composer test` проходит.
- `composer lint` проходит.
- `npm run build` проходит.
- Коммит: `CONV-057: Add converter catalog smoke tests`

### Files likely touched

```txt
tests/Unit/Converters/MvpConverterCatalogSmokeTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 04 Completion Criteria

Phase 04 завершена, когда:

```txt
- CONV-046–CONV-057 выполнены;
- MVP capability list exists;
- PNG → JPG converter capability exists;
- PNG → WEBP converter capability exists;
- PNG → PDF converter capability exists;
- JPG → PNG converter capability exists;
- JPG → WEBP converter capability exists;
- JPG → PDF converter capability exists;
- PNG targets are JPG, WEBP, PDF;
- JPG targets are PNG, WEBP, PDF;
- recommended target metadata exists;
- every converter has label and description;
- every converter has options schema;
- every converter defaults pass OptionsValidator;
- ConverterRegistry finds all six exact pairs;
- unsupported pairs return null;
- no real conversion drivers were added;
- no file upload was added;
- no jobs/queues were added;
- no billing/API/UI was added;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 04

Без отдельной задачи нельзя:

```txt
- устанавливать Imagick / Intervention Image / DomPDF;
- делать реальную конвертацию файлов;
- создавать ConverterDriver;
- создавать files table;
- создавать conversion_jobs table;
- создавать DashboardConverter Livewire component;
- создавать upload form;
- создавать dynamic settings UI;
- добавлять billing/credits;
- устанавливать Laravel Cashier;
- добавлять API routes;
- добавлять OpenAPI docs;
- добавлять OCR;
- добавлять audio/video/document converters;
- добавлять batch conversion;
- добавлять public /formats page;
- добавлять fake “100+ formats”.
```

---

# 12. Recommended Execution Order

```txt
CONV-046 Create MVP Converter Capability List
CONV-047 Test PNG Target Capabilities
CONV-048 Add PNG To JPG Converter Capability
CONV-049 Add PNG To WEBP Converter Capability
CONV-050 Add PNG To PDF Converter Capability
CONV-051 Test JPG Target Capabilities
CONV-052 Add JPG To PNG Converter Capability
CONV-053 Add JPG To WEBP Converter Capability
CONV-054 Add JPG To PDF Converter Capability
CONV-055 Add Recommended Target Metadata
CONV-056 Register MVP Converter Capabilities
CONV-057 Add Converter Catalog Smoke Tests
```

---

# 13. Release

После завершения Phase 04:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh

git checkout -b release/v0.1.4-phase04-mvp-converter-capabilities
git push -u origin release/v0.1.4-phase04-mvp-converter-capabilities
```

После этого сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.4-phase04-mvp-converter-capabilities -m "File Converter Phase 04 MVP Converter Capabilities"
git push origin v0.1.4-phase04-mvp-converter-capabilities
```
