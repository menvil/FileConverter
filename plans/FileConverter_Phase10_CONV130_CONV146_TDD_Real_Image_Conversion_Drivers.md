# File Converter — Phase 10 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 10 — Real Image Conversion Drivers**  
Диапазон задач: **CONV-130 → CONV-146**  
Основа нумерации: Phase 9 завершилась на `CONV-129`, поэтому Phase 10 начинается с `CONV-130`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 10 соответствует блоку:

```txt
Phase 10 — Real Image Conversion Drivers
```

Правильный диапазон Phase 10:

```txt
CONV-130 — Install Image Processing Packages
CONV-131 — Add Image Driver Test Fixtures
CONV-132 — Test PNG To JPG Driver
CONV-133 — Implement PNG To JPG Driver
CONV-134 — Test JPG To PNG Driver
CONV-135 — Implement JPG To PNG Driver
CONV-136 — Test PNG To WEBP Driver
CONV-137 — Implement PNG To WEBP Driver
CONV-138 — Test JPG To WEBP Driver
CONV-139 — Implement JPG To WEBP Driver
CONV-140 — Install PDF Generation Package
CONV-141 — Test PNG To PDF Driver
CONV-142 — Implement PNG To PDF Driver
CONV-143 — Test JPG To PDF Driver
CONV-144 — Implement JPG To PDF Driver
CONV-145 — Register Real Image Drivers
CONV-146 — Add Real Driver Integration Smoke Tests
```

Phase 10 добавляет настоящие драйверы конвертации для MVP image-направлений:

```txt
PNG → JPG
JPG → PNG
PNG → WEBP
JPG → WEBP
PNG → PDF
JPG → PDF
```

Важно:

```txt
Phase 04 = converter metadata / capability layer
Phase 09 = conversion job core with fake drivers
Phase 10 = real image conversion drivers
Phase 11 = Convert UI flow
Phase 14/15 = credits and cost estimator
```

В Phase 10 не меняется UX и не подключается кнопка `Convert Now`.  
Эта фаза делает backend drivers, которые уже смогут использоваться `ProcessConversionJob` из Phase 9.

---

# 2. Цель Phase 10

Phase 10 должна заменить fake-only конвертационную инфраструктуру реальными image drivers для MVP.

После Phase 10 система должна уметь на backend-уровне:

```txt
- открыть uploaded PNG/JPG file;
- применить supported options;
- сохранить реальный JPG/PNG/WEBP/PDF result file;
- вернуть ConversionResult DTO;
- зарегистрировать real driver для каждого MVP converter key;
- обработать driver failure cleanly;
- подтвердить через integration tests, что ProcessConversionJob работает с реальными драйверами.
```

Phase 10 не должна заниматься:

```txt
- user-facing convert button;
- Livewire polling;
- download route;
- Recent Conversions table;
- credits;
- billing;
- API;
- batch conversion;
- OCR.
```

---

# 3. Scope Phase 10

## Входит

```txt
- image processing package installation;
- PDF generation package installation;
- reusable image test fixtures;
- PNG → JPG driver;
- JPG → PNG driver;
- PNG → WEBP driver;
- JPG → WEBP driver;
- PNG → PDF driver;
- JPG → PDF driver;
- driver registration in ConverterDriverRegistry;
- real driver unit/feature tests;
- ProcessConversionJob integration smoke tests with real drivers;
- basic output validation: file exists, extension, mime type, size > 0.
```

## Не входит

```txt
- UI conversion flow;
- Livewire Convert button;
- result download route;
- conversion history table;
- credits and cost calculation;
- Cashier;
- API endpoints;
- OpenAPI docs;
- advanced image editing;
- background removal;
- AI upscale;
- multi-page PDF;
- batch conversion;
- video/audio conversion;
- OCR;
- watermarking;
- cloud storage direct upload;
- queue progress based on real driver internals.
```

---

# 4. Critical Decisions

## 4.1. Drivers must implement the existing ConverterDriver interface

Phase 10 не должна создавать альтернативный способ конвертации.

Правильно:

```php
final class PngToJpgDriver implements ConverterDriver
{
    public function convert(ConversionContext $context): ConversionResult
    {
        // real conversion
    }
}
```

Неправильно:

```php
ImageConverterService::convertPngToJpg($file, $options);
```

Если появится второй параллельный слой, API, UI и queue logic быстро разъедутся.

## 4.2. Drivers must not know about Livewire or HTTP

Драйверы работают только с:

```txt
ConversionContext
ConversionResult
filesystem paths
validated options
```

Драйверы не должны принимать:

```txt
UploadedFile
Request
Livewire component state
User input without validation
```

## 4.3. Options must already be validated before driver execution

Phase 9/8 должны валидировать options через converter/options validator.  
Driver может делать defensive validation, но не должен быть основным validation layer.

Правильно:

```txt
CreateConversionJobAction validates options
ProcessConversionJob passes normalized options to driver
Driver trusts normalized options but guards critical assumptions
```

## 4.4. Use simple, portable packages for MVP

Рекомендация для MVP:

```txt
Image conversion: intervention/image
PDF generation: barryvdh/laravel-dompdf or dompdf/dompdf
```

Не надо начинать с heavy setup:

```txt
custom ImageMagick CLI wrappers
Ghostscript dependency
libvips pipeline
Browsershot/Chrome rendering
```

Если проект уже выбрал Imagick ранее — адаптировать задачи под Imagick, но не смешивать несколько image engines без причины.

## 4.5. PDF driver is simple single-image-to-single-page PDF

Phase 10 не делает полноценный PDF engine.

Правильно:

```txt
one image input → one PDF page
page_size
orientation
margin
fit_mode
```

Неправильно:

```txt
multiple images to one PDF
PDF merging
PDF/A
password protection
multi-page UI
OCR/searchable PDF
```

Это будущие фазы.

## 4.6. Output files must be written through the same storage abstraction

Driver должен писать output туда, куда указывает `ConversionContext` или output path service из Phase 9.

Нельзя писать в случайные директории:

```txt
/tmp/file.jpg
public/output.jpg
storage/app/random-name.jpg
```

Правильно:

```txt
storage/app/conversions/{job_id}/result.jpg
```

или путь, который уже задаёт Phase 9.

## 4.7. Tests must use generated fixtures, not committed binary junk

Не надо коммитить пачку случайных картинок.

Лучше в тестах генерировать fixtures:

```php
UploadedFile::fake()->image('source.png', 600, 400)
```

Если нужен PNG с прозрачностью, создать fixture программно в helper/test setup.

---

# 5. Architecture Rules

## 5.1. Driver location

Рекомендуемые пути:

```txt
app/Conversion/Drivers/Image/PngToJpgDriver.php
app/Conversion/Drivers/Image/JpgToPngDriver.php
app/Conversion/Drivers/Image/PngToWebpDriver.php
app/Conversion/Drivers/Image/JpgToWebpDriver.php
app/Conversion/Drivers/Image/PngToPdfDriver.php
app/Conversion/Drivers/Image/JpgToPdfDriver.php
```

Если в проекте уже есть namespace для converters из Phase 3/4/9, использовать существующую структуру, а не плодить новую.

## 5.2. No direct model mutation inside drivers

Driver не должен делать:

```php
$job->update([...]);
$fileRecord = FileRecord::create([...]);
```

Это ответственность `ProcessConversionJob` и `ResultFileRecorderAction` из Phase 9.

Driver возвращает:

```php
ConversionResult
```

## 5.3. Driver tests should be isolated

Unit/feature tests драйвера должны:

```txt
- подготовить source file;
- создать ConversionContext;
- вызвать driver->convert();
- проверить output file exists;
- проверить extension/mime/size;
- не требовать HTTP/UI.
```

## 5.4. Integration smoke tests should prove queue pipeline

Отдельные integration tests должны проверить:

```txt
ConversionJob + ProcessConversionJob + real driver + result FileRecord
```

Это не заменяет unit tests каждого драйвера.

---

# 6. GitFlow для Phase 10

## Base branch

Все задачи Phase 10 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-130-install-image-processing-packages
feature/CONV-133-implement-png-to-jpg-driver
feature/CONV-146-add-real-driver-integration-smoke-tests
```

## Commit format

```txt
CONV-130: Install image processing packages
CONV-133: Implement PNG to JPG driver
CONV-146: Add real driver integration smoke tests
```

## Release branch

После выполнения `CONV-130`–`CONV-146`:

```txt
release/v0.1.10-phase10-real-image-conversion-drivers
```

## Tag

После merge release branch в `main`:

```txt
v0.1.10-phase10-real-image-conversion-drivers
```

---

# 7. TDD Rules for Phase 10

## Для драйверов

Каждый driver делается test-first:

```txt
- test output file exists;
- test output extension/mime;
- test output size > 0;
- test key options where feasible;
- test invalid/missing source fails cleanly if applicable.
```

## Для package setup

Если задача инфраструктурная и прямой test невозможен:

```txt
No direct test — package installation/setup.
```

Но после задачи должны проходить:

```bash
composer test
composer lint
npm run build
```

## Для PDF

PDF tests не должны проверять визуальное совпадение layout pixel-perfect.  
Минимум:

```txt
- generated PDF exists;
- extension = pdf;
- mime type = application/pdf;
- file size > 0;
- optional: first bytes start with %PDF.
```

## Для integration smoke tests

Проверять полный backend path:

```txt
source FileRecord
ConversionJob queued
ProcessConversionJob handles job
real driver creates result
ConversionJob completed
result_file_id set
```

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Conversion / Driver / Tests / Infrastructure
Type: Setup / Test / Feature / Driver / Integration
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
- Driver не мутирует модели напрямую
- Коммит содержит ID задачи

Files likely touched:
- path/to/file
```

---

# 9. Phase 10 Atomic Tasks

---

## CONV-130 — Install Image Processing Packages

**Area:** Conversion / Infrastructure  
**Type:** Setup  
**Priority:** P0  
**Branch:** `feature/CONV-130-install-image-processing-packages`  
**Base branch:** `develop`  
**Depends on:** CONV-129

### Goal

Установить image processing package для реальной PNG/JPG/WEBP конвертации.

### TDD step

No direct test — package installation/setup.

После установки должны проходить:

```bash
composer test
composer lint
npm run build
```

### Implementation

Рекомендованный MVP-вариант:

```bash
composer require intervention/image
```

Если проект заранее выбрал другой image engine, например Imagick-based implementation, зафиксировать это в коротком decision note:

```txt
docs/decisions/image-processing-engine.md
```

Но не ставить несколько engines одновременно без необходимости.

Проверить, что package автозагружается и не ломает тесты.

### Acceptance criteria

- Image processing package установлен.
- `composer test` проходит.
- `composer lint` проходит.
- `npm run build` проходит.
- Не установлен лишний competing image engine.
- Нет driver implementation в этой задаче.

### Definition of Done

- Package установлен.
- Lock file обновлён.
- Тесты проходят.
- Коммит: `CONV-130: Install image processing packages`

### Files likely touched

```txt
composer.json
composer.lock
docs/decisions/image-processing-engine.md
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-131 — Add Image Driver Test Fixtures

**Area:** Conversion / Tests  
**Type:** Test Infrastructure  
**Priority:** P0  
**Branch:** `feature/CONV-131-add-image-driver-test-fixtures`  
**Base branch:** `develop`  
**Depends on:** CONV-130

### Goal

Добавить test helpers/fixtures для создания source PNG/JPG files в driver tests.

### TDD step

Unit/feature test helper smoke test:

```php
it('creates image driver test fixtures', function () {
    $pngPath = ImageFixture::png('source.png', width: 600, height: 400);
    $jpgPath = ImageFixture::jpg('source.jpg', width: 600, height: 400);

    expect(Storage::disk('local')->exists($pngPath))->toBeTrue();
    expect(Storage::disk('local')->exists($jpgPath))->toBeTrue();
});
```

Тест должен упасть до создания helper.

### Implementation

Создать test helper, например:

```txt
tests/Support/ImageFixture.php
```

Helper должен уметь создавать:

```txt
- PNG fixture;
- JPG fixture;
- optional transparent PNG fixture;
- return storage-relative path.
```

Использовать временный test storage.  
Не коммитить binary fixtures без необходимости.

### Acceptance criteria

- PNG fixture создаётся.
- JPG fixture создаётся.
- Helper возвращает путь к файлу.
- Файлы существуют в test storage.
- Test helper не зависит от HTTP/Livewire.
- Тест проходит.

### Definition of Done

- Тест написан первым.
- Helper создан.
- Тест проходит.
- Коммит: `CONV-131: Add image driver test fixtures`

### Files likely touched

```txt
tests/Support/ImageFixture.php
tests/Feature/Conversion/ImageFixtureTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-132 — Test PNG To JPG Driver

**Area:** Conversion / Driver / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-132-test-png-to-jpg-driver`  
**Base branch:** `develop`  
**Depends on:** CONV-131

### Goal

Написать падающий тест для `PngToJpgDriver`.

### TDD step

Driver test:

```php
it('converts png to jpg', function () {
    $sourcePath = ImageFixture::png('source.png', width: 600, height: 400);
    $context = ConversionContextFactory::forSourcePath(
        sourcePath: $sourcePath,
        outputExtension: 'jpg',
        options: [
            'quality' => 'high',
            'background' => '#ffffff',
            'resize' => 'original',
            'remove_metadata' => true,
        ],
    );

    $result = app(PngToJpgDriver::class)->convert($context);

    expect(Storage::disk('local')->exists($result->path))->toBeTrue();
    expect($result->extension)->toBe('jpg');
    expect($result->mimeType)->toBe('image/jpeg');
    expect($result->sizeBytes)->toBeGreaterThan(0);
});
```

Адаптировать factory/context creation под фактические DTO из Phase 9.

Тест должен упасть до CONV-133.

### Implementation

Только добавить тест и minimal test factory/helper, если DTO слишком неудобно создавать вручную.

Не реализовывать driver в этой задаче.

### Acceptance criteria

- Тест существует.
- Тест проверяет real output file.
- Тест проверяет extension/mime/size.
- Тест падает до реализации driver.
- Нет production driver implementation.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-132: Test PNG to JPG driver`

### Files likely touched

```txt
tests/Feature/Conversion/Drivers/PngToJpgDriverTest.php
tests/Support/ConversionContextFactory.php
```

После этого сделай MR в `develop`. Merge допускается как test-only failing task только если workflow проекта допускает test-first failing commits. Если нет — объединить с CONV-133 в один MR, но коммиты оставить отдельными.

---

## CONV-133 — Implement PNG To JPG Driver

**Area:** Conversion / Driver  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-133-implement-png-to-jpg-driver`  
**Base branch:** `develop`  
**Depends on:** CONV-132

### Goal

Реализовать настоящий `PngToJpgDriver`.

### TDD step

Использовать падающий тест из CONV-132.

### Implementation

Создать driver:

```txt
app/Conversion/Drivers/Image/PngToJpgDriver.php
```

Driver должен:

```txt
- открыть source PNG;
- если есть прозрачность, применить background color;
- применить resize option, если реализована поддержка;
- сохранить JPG в output path из ConversionContext;
- вернуть ConversionResult;
- не создавать FileRecord напрямую;
- не менять ConversionJob напрямую.
```

MVP resize support можно ограничить:

```txt
original
max_1920
max_1280
```

Если resize options в Phase 4 имеют другие keys, использовать их.

### Acceptance criteria

- PNG → JPG работает.
- Result file exists.
- Result extension = `jpg`.
- Result mime = `image/jpeg`.
- Transparent PNG не ломает conversion.
- Driver implements `ConverterDriver`.
- Тест CONV-132 проходит.

### Definition of Done

- Driver реализован.
- Не мутирует модели напрямую.
- Тесты проходят.
- Коммит: `CONV-133: Implement PNG to JPG driver`

### Files likely touched

```txt
app/Conversion/Drivers/Image/PngToJpgDriver.php
tests/Feature/Conversion/Drivers/PngToJpgDriverTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-134 — Test JPG To PNG Driver

**Area:** Conversion / Driver / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-134-test-jpg-to-png-driver`  
**Base branch:** `develop`  
**Depends on:** CONV-133

### Goal

Написать падающий тест для `JpgToPngDriver`.

### TDD step

Driver test:

```php
it('converts jpg to png', function () {
    $sourcePath = ImageFixture::jpg('source.jpg', width: 600, height: 400);
    $context = ConversionContextFactory::forSourcePath(
        sourcePath: $sourcePath,
        outputExtension: 'png',
        options: [
            'resize' => 'original',
            'remove_metadata' => true,
        ],
    );

    $result = app(JpgToPngDriver::class)->convert($context);

    expect(Storage::disk('local')->exists($result->path))->toBeTrue();
    expect($result->extension)->toBe('png');
    expect($result->mimeType)->toBe('image/png');
    expect($result->sizeBytes)->toBeGreaterThan(0);
});
```

Тест должен упасть до CONV-135.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Проверяет output file.
- Проверяет extension/mime/size.
- Тест падает до реализации.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-134: Test JPG to PNG driver`

### Files likely touched

```txt
tests/Feature/Conversion/Drivers/JpgToPngDriverTest.php
```

После этого сделай MR в `develop` или объединить с CONV-135 по правилам проекта.

---

## CONV-135 — Implement JPG To PNG Driver

**Area:** Conversion / Driver  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-135-implement-jpg-to-png-driver`  
**Base branch:** `develop`  
**Depends on:** CONV-134

### Goal

Реализовать настоящий `JpgToPngDriver`.

### TDD step

Использовать падающий тест из CONV-134.

### Implementation

Создать:

```txt
app/Conversion/Drivers/Image/JpgToPngDriver.php
```

Driver должен:

```txt
- открыть JPG;
- применить resize option;
- сохранить PNG;
- вернуть ConversionResult;
- не менять модели напрямую.
```

### Acceptance criteria

- JPG → PNG работает.
- Result extension = `png`.
- Result mime = `image/png`.
- Result file size > 0.
- Driver implements `ConverterDriver`.
- Тест проходит.

### Definition of Done

- Driver реализован.
- Тесты проходят.
- Коммит: `CONV-135: Implement JPG to PNG driver`

### Files likely touched

```txt
app/Conversion/Drivers/Image/JpgToPngDriver.php
tests/Feature/Conversion/Drivers/JpgToPngDriverTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-136 — Test PNG To WEBP Driver

**Area:** Conversion / Driver / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-136-test-png-to-webp-driver`  
**Base branch:** `develop`  
**Depends on:** CONV-135

### Goal

Написать падающий тест для `PngToWebpDriver`.

### TDD step

Driver test:

```php
it('converts png to webp', function () {
    $sourcePath = ImageFixture::png('source.png', width: 600, height: 400);
    $context = ConversionContextFactory::forSourcePath(
        sourcePath: $sourcePath,
        outputExtension: 'webp',
        options: [
            'quality' => 'high',
            'resize' => 'original',
            'lossless' => false,
            'remove_metadata' => true,
        ],
    );

    $result = app(PngToWebpDriver::class)->convert($context);

    expect(Storage::disk('local')->exists($result->path))->toBeTrue();
    expect($result->extension)->toBe('webp');
    expect($result->mimeType)->toBe('image/webp');
    expect($result->sizeBytes)->toBeGreaterThan(0);
});
```

Тест должен упасть до CONV-137.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Проверяет output file.
- Проверяет extension/mime/size.
- Тест падает до реализации.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-136: Test PNG to WEBP driver`

### Files likely touched

```txt
tests/Feature/Conversion/Drivers/PngToWebpDriverTest.php
```

После этого сделай MR в `develop` или объединить с CONV-137 по правилам проекта.

---

## CONV-137 — Implement PNG To WEBP Driver

**Area:** Conversion / Driver  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-137-implement-png-to-webp-driver`  
**Base branch:** `develop`  
**Depends on:** CONV-136

### Goal

Реализовать настоящий `PngToWebpDriver`.

### TDD step

Использовать падающий тест из CONV-136.

### Implementation

Создать:

```txt
app/Conversion/Drivers/Image/PngToWebpDriver.php
```

Driver должен:

```txt
- открыть PNG;
- применить resize;
- применить quality/lossless options, насколько поддерживает выбранная библиотека;
- сохранить WEBP;
- вернуть ConversionResult.
```

Если выбранная image library/environment не поддерживает WEBP, задача должна упасть явно и документировать requirement. Нельзя молча сохранять PNG с расширением `.webp`.

### Acceptance criteria

- PNG → WEBP работает.
- Result extension = `webp`.
- Result mime = `image/webp`.
- Result file size > 0.
- WEBP support не fake.
- Тест проходит.

### Definition of Done

- Driver реализован.
- Тесты проходят.
- Коммит: `CONV-137: Implement PNG to WEBP driver`

### Files likely touched

```txt
app/Conversion/Drivers/Image/PngToWebpDriver.php
tests/Feature/Conversion/Drivers/PngToWebpDriverTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-138 — Test JPG To WEBP Driver

**Area:** Conversion / Driver / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-138-test-jpg-to-webp-driver`  
**Base branch:** `develop`  
**Depends on:** CONV-137

### Goal

Написать падающий тест для `JpgToWebpDriver`.

### TDD step

Driver test:

```php
it('converts jpg to webp', function () {
    $sourcePath = ImageFixture::jpg('source.jpg', width: 600, height: 400);
    $context = ConversionContextFactory::forSourcePath(
        sourcePath: $sourcePath,
        outputExtension: 'webp',
        options: [
            'quality' => 'high',
            'resize' => 'original',
            'remove_metadata' => true,
        ],
    );

    $result = app(JpgToWebpDriver::class)->convert($context);

    expect(Storage::disk('local')->exists($result->path))->toBeTrue();
    expect($result->extension)->toBe('webp');
    expect($result->mimeType)->toBe('image/webp');
    expect($result->sizeBytes)->toBeGreaterThan(0);
});
```

Тест должен упасть до CONV-139.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Проверяет output file.
- Проверяет extension/mime/size.
- Тест падает до реализации.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-138: Test JPG to WEBP driver`

### Files likely touched

```txt
tests/Feature/Conversion/Drivers/JpgToWebpDriverTest.php
```

После этого сделай MR в `develop` или объединить с CONV-139 по правилам проекта.

---

## CONV-139 — Implement JPG To WEBP Driver

**Area:** Conversion / Driver  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-139-implement-jpg-to-webp-driver`  
**Base branch:** `develop`  
**Depends on:** CONV-138

### Goal

Реализовать настоящий `JpgToWebpDriver`.

### TDD step

Использовать падающий тест из CONV-138.

### Implementation

Создать:

```txt
app/Conversion/Drivers/Image/JpgToWebpDriver.php
```

Driver должен:

```txt
- открыть JPG;
- применить resize;
- применить quality;
- сохранить WEBP;
- вернуть ConversionResult.
```

### Acceptance criteria

- JPG → WEBP работает.
- Result extension = `webp`.
- Result mime = `image/webp`.
- Result file size > 0.
- Driver implements `ConverterDriver`.
- Тест проходит.

### Definition of Done

- Driver реализован.
- Тесты проходят.
- Коммит: `CONV-139: Implement JPG to WEBP driver`

### Files likely touched

```txt
app/Conversion/Drivers/Image/JpgToWebpDriver.php
tests/Feature/Conversion/Drivers/JpgToWebpDriverTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-140 — Install PDF Generation Package

**Area:** Conversion / PDF / Infrastructure  
**Type:** Setup  
**Priority:** P0  
**Branch:** `feature/CONV-140-install-pdf-generation-package`  
**Base branch:** `develop`  
**Depends on:** CONV-139

### Goal

Установить package для генерации простого PDF из изображения.

### TDD step

No direct test — package installation/setup.

После установки должны проходить:

```bash
composer test
composer lint
npm run build
```

### Implementation

Рекомендованный MVP-вариант:

```bash
composer require barryvdh/laravel-dompdf
```

Альтернатива:

```bash
composer require dompdf/dompdf
```

Выбрать один вариант, не оба.

Если используется Laravel wrapper, опубликовывать config только если реально нужно.  
Не добавлять PDF UI, PDF merge/split, OCR или password protection.

### Acceptance criteria

- PDF generation package установлен.
- `composer test` проходит.
- `composer lint` проходит.
- `npm run build` проходит.
- Нет PDF driver implementation в этой задаче.
- Не добавлены лишние PDF features.

### Definition of Done

- Package установлен.
- Lock file обновлён.
- Тесты проходят.
- Коммит: `CONV-140: Install PDF generation package`

### Files likely touched

```txt
composer.json
composer.lock
config/dompdf.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-141 — Test PNG To PDF Driver

**Area:** Conversion / PDF / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-141-test-png-to-pdf-driver`  
**Base branch:** `develop`  
**Depends on:** CONV-140

### Goal

Написать падающий тест для `PngToPdfDriver`.

### TDD step

Driver test:

```php
it('converts png to pdf', function () {
    $sourcePath = ImageFixture::png('source.png', width: 600, height: 400);
    $context = ConversionContextFactory::forSourcePath(
        sourcePath: $sourcePath,
        outputExtension: 'pdf',
        options: [
            'page_size' => 'a4',
            'orientation' => 'auto',
            'margin' => 'small',
            'fit_mode' => 'contain',
            'compression' => 'balanced',
        ],
    );

    $result = app(PngToPdfDriver::class)->convert($context);

    expect(Storage::disk('local')->exists($result->path))->toBeTrue();
    expect($result->extension)->toBe('pdf');
    expect($result->mimeType)->toBe('application/pdf');
    expect($result->sizeBytes)->toBeGreaterThan(0);

    $contents = Storage::disk('local')->get($result->path);
    expect(str_starts_with($contents, '%PDF'))->toBeTrue();
});
```

Тест должен упасть до CONV-142.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Проверяет PDF exists.
- Проверяет extension/mime/size.
- Проверяет `%PDF` header where feasible.
- Тест падает до реализации.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-141: Test PNG to PDF driver`

### Files likely touched

```txt
tests/Feature/Conversion/Drivers/PngToPdfDriverTest.php
```

После этого сделай MR в `develop` или объединить с CONV-142 по правилам проекта.

---

## CONV-142 — Implement PNG To PDF Driver

**Area:** Conversion / PDF / Driver  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-142-implement-png-to-pdf-driver`  
**Base branch:** `develop`  
**Depends on:** CONV-141

### Goal

Реализовать `PngToPdfDriver` для single-image PDF.

### TDD step

Использовать падающий тест из CONV-141.

### Implementation

Создать:

```txt
app/Conversion/Drivers/Image/PngToPdfDriver.php
resources/views/pdf/single-image.blade.php
```

Driver должен:

```txt
- прочитать source PNG;
- встроить image в PDF как одну страницу;
- применить page_size;
- применить orientation;
- применить margin;
- применить fit_mode contain/cover/original if feasible;
- сохранить PDF в output path;
- вернуть ConversionResult.
```

Для MVP допустимо упростить fit behavior, но нельзя игнорировать options молча, если UI обещает поведение. Если option пока не полностью реализована, указать это явно в test/decision note.

### Acceptance criteria

- PNG → PDF работает.
- PDF file exists.
- PDF starts with `%PDF` where feasible.
- Result extension = `pdf`.
- Result mime = `application/pdf`.
- Driver implements `ConverterDriver`.
- Тест проходит.

### Definition of Done

- Driver реализован.
- PDF Blade/template добавлен, если нужен.
- Тесты проходят.
- Коммит: `CONV-142: Implement PNG to PDF driver`

### Files likely touched

```txt
app/Conversion/Drivers/Image/PngToPdfDriver.php
resources/views/pdf/single-image.blade.php
tests/Feature/Conversion/Drivers/PngToPdfDriverTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-143 — Test JPG To PDF Driver

**Area:** Conversion / PDF / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-143-test-jpg-to-pdf-driver`  
**Base branch:** `develop`  
**Depends on:** CONV-142

### Goal

Написать падающий тест для `JpgToPdfDriver`.

### TDD step

Driver test:

```php
it('converts jpg to pdf', function () {
    $sourcePath = ImageFixture::jpg('source.jpg', width: 600, height: 400);
    $context = ConversionContextFactory::forSourcePath(
        sourcePath: $sourcePath,
        outputExtension: 'pdf',
        options: [
            'page_size' => 'a4',
            'orientation' => 'auto',
            'margin' => 'small',
            'fit_mode' => 'contain',
            'compression' => 'balanced',
        ],
    );

    $result = app(JpgToPdfDriver::class)->convert($context);

    expect(Storage::disk('local')->exists($result->path))->toBeTrue();
    expect($result->extension)->toBe('pdf');
    expect($result->mimeType)->toBe('application/pdf');
    expect($result->sizeBytes)->toBeGreaterThan(0);
});
```

Тест должен упасть до CONV-144.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Проверяет PDF exists.
- Проверяет extension/mime/size.
- Тест падает до реализации.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-143: Test JPG to PDF driver`

### Files likely touched

```txt
tests/Feature/Conversion/Drivers/JpgToPdfDriverTest.php
```

После этого сделай MR в `develop` или объединить с CONV-144 по правилам проекта.

---

## CONV-144 — Implement JPG To PDF Driver

**Area:** Conversion / PDF / Driver  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-144-implement-jpg-to-pdf-driver`  
**Base branch:** `develop`  
**Depends on:** CONV-143

### Goal

Реализовать `JpgToPdfDriver` для single-image PDF.

### TDD step

Использовать падающий тест из CONV-143.

### Implementation

Создать:

```txt
app/Conversion/Drivers/Image/JpgToPdfDriver.php
```

Если `PngToPdfDriver` и `JpgToPdfDriver` почти одинаковые, допустимо вынести shared helper:

```txt
app/Conversion/Drivers/Image/Concerns/RendersSingleImagePdf.php
```

Но не создавать огромный абстрактный framework.  
Минимальная переиспользуемость лучше преждевременной абстракции.

### Acceptance criteria

- JPG → PDF работает.
- PDF file exists.
- Result extension = `pdf`.
- Result mime = `application/pdf`.
- Driver implements `ConverterDriver`.
- Тест проходит.

### Definition of Done

- Driver реализован.
- Shared helper добавлен только если реально уменьшает дублирование.
- Тесты проходят.
- Коммит: `CONV-144: Implement JPG to PDF driver`

### Files likely touched

```txt
app/Conversion/Drivers/Image/JpgToPdfDriver.php
app/Conversion/Drivers/Image/Concerns/RendersSingleImagePdf.php
tests/Feature/Conversion/Drivers/JpgToPdfDriverTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-145 — Register Real Image Drivers

**Area:** Conversion / Registry  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-145-register-real-image-drivers`  
**Base branch:** `develop`  
**Depends on:** CONV-144

### Goal

Зарегистрировать real drivers в `ConverterDriverRegistry` для всех MVP converter keys.

### TDD step

Registry test:

```php
it('resolves real image drivers for mvp converters', function () {
    $registry = app(ConverterDriverRegistry::class);

    expect($registry->find('png_to_jpg'))->toBeInstanceOf(PngToJpgDriver::class);
    expect($registry->find('jpg_to_png'))->toBeInstanceOf(JpgToPngDriver::class);
    expect($registry->find('png_to_webp'))->toBeInstanceOf(PngToWebpDriver::class);
    expect($registry->find('jpg_to_webp'))->toBeInstanceOf(JpgToWebpDriver::class);
    expect($registry->find('png_to_pdf'))->toBeInstanceOf(PngToPdfDriver::class);
    expect($registry->find('jpg_to_pdf'))->toBeInstanceOf(JpgToPdfDriver::class);
});
```

Адаптировать keys под реальные converter keys из Phase 4.

Тест должен упасть до регистрации.

### Implementation

Обновить driver registry binding/config.

Варианты:

```txt
config/converters.php
AppServiceProvider binding
ConverterDriverRegistry constructor injection
```

Использовать один существующий способ из Phase 9. Не создавать второй registry.

### Acceptance criteria

- Все 6 MVP drivers registered.
- Driver keys совпадают с converter capability keys.
- Unsupported key returns null или domain error по правилам Phase 9.
- Тест проходит.

### Definition of Done

- Тест написан.
- Drivers зарегистрированы.
- Тесты проходят.
- Коммит: `CONV-145: Register real image drivers`

### Files likely touched

```txt
config/converters.php
app/Providers/AppServiceProvider.php
app/Conversion/ConverterDriverRegistry.php
tests/Feature/Conversion/ConverterDriverRegistryTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-146 — Add Real Driver Integration Smoke Tests

**Area:** Conversion / Integration Tests  
**Type:** Integration  
**Priority:** P0  
**Branch:** `feature/CONV-146-add-real-driver-integration-smoke-tests`  
**Base branch:** `develop`  
**Depends on:** CONV-145

### Goal

Добавить smoke tests, подтверждающие, что `ProcessConversionJob` работает с реальными drivers, а не только с fake driver.

### TDD step

Integration tests:

```php
it('processes png to jpg conversion job with real driver', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->png()->createWithStoredFixture();

    $job = ConversionJob::factory()
        ->for($user)
        ->forSourceFile($file)
        ->queued()
        ->create([
            'source_format' => 'png',
            'target_format' => 'jpg',
            'converter_key' => 'png_to_jpg',
            'options_json' => [
                'quality' => 'high',
                'background' => '#ffffff',
                'resize' => 'original',
                'remove_metadata' => true,
            ],
        ]);

    app(ProcessConversionJob::class)->handle($job);

    expect($job->fresh()->status)->toBe(ConversionStatus::Completed);
    expect($job->fresh()->result_file_id)->not->toBeNull();
    expect(Storage::disk('local')->exists($job->fresh()->resultFile->stored_path))->toBeTrue();
});
```

Добавить минимум smoke tests:

```txt
PNG → JPG
JPG → WEBP
PNG → PDF
```

Не обязательно делать full integration test для всех 6, если unit tests drivers уже покрывают их.

### Implementation

Добавить integration tests и при необходимости test factory helpers:

```txt
createWithStoredFixture()
forSourceFile()
```

Если `ProcessConversionJob` из Phase 9 принимает job id, а не model, адаптировать тест под реальную сигнатуру.

### Acceptance criteria

- ProcessConversionJob работает с real PNG→JPG driver.
- ProcessConversionJob работает с real JPG→WEBP driver.
- ProcessConversionJob работает с real PNG→PDF driver.
- ConversionJob becomes completed.
- result_file_id set.
- physical result file exists.
- No credits are checked/spent in this phase.
- Tests pass.

### Definition of Done

- Integration smoke tests добавлены.
- Реальные drivers проходят через queue pipeline.
- Тесты проходят.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.
- Коммит: `CONV-146: Add real driver integration smoke tests`

### Files likely touched

```txt
tests/Feature/Conversion/ProcessConversionJobRealDriverTest.php
database/factories/FileRecordFactory.php
database/factories/ConversionJobFactory.php
tests/Support/ImageFixture.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 10 Completion Criteria

Phase 10 завершена, когда:

```txt
- CONV-130–CONV-146 выполнены;
- image processing package installed;
- PDF generation package installed;
- image test fixtures exist;
- PNG → JPG driver works;
- JPG → PNG driver works;
- PNG → WEBP driver works;
- JPG → WEBP driver works;
- PNG → PDF driver works;
- JPG → PDF driver works;
- all MVP real drivers are registered;
- driver keys match converter capability keys;
- ProcessConversionJob works with real drivers;
- result FileRecord is created through ResultFileRecorderAction;
- drivers do not mutate models directly;
- no UI conversion flow was added;
- no billing/credits logic was added;
- no API logic was added;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 10

Без отдельной задачи нельзя:

```txt
- подключать Convert Now button;
- добавлять Livewire converting/completed states;
- создавать result download route;
- создавать Recent Conversions table;
- добавлять CreditLedger;
- добавлять ConversionCostEstimator;
- списывать credits;
- устанавливать Laravel Cashier;
- создавать billing page;
- создавать API endpoints;
- создавать OpenAPI docs;
- добавлять batch conversion;
- делать multi-image to PDF;
- делать PDF merge/split;
- делать OCR;
- делать video/audio conversion;
- делать background removal;
- делать AI upscale;
- добавлять S3 direct upload;
- добавлять React/Vue/Inertia;
- делать pixel-perfect PDF rendering tests.
```

---

# 12. Recommended Execution Order

```txt
CONV-130 Install Image Processing Packages
CONV-131 Add Image Driver Test Fixtures
CONV-132 Test PNG To JPG Driver
CONV-133 Implement PNG To JPG Driver
CONV-134 Test JPG To PNG Driver
CONV-135 Implement JPG To PNG Driver
CONV-136 Test PNG To WEBP Driver
CONV-137 Implement PNG To WEBP Driver
CONV-138 Test JPG To WEBP Driver
CONV-139 Implement JPG To WEBP Driver
CONV-140 Install PDF Generation Package
CONV-141 Test PNG To PDF Driver
CONV-142 Implement PNG To PDF Driver
CONV-143 Test JPG To PDF Driver
CONV-144 Implement JPG To PDF Driver
CONV-145 Register Real Image Drivers
CONV-146 Add Real Driver Integration Smoke Tests
```

---

# 13. Release

После завершения Phase 10:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh --seed

git checkout -b release/v0.1.10-phase10-real-image-conversion-drivers
git push -u origin release/v0.1.10-phase10-real-image-conversion-drivers
```

После этого сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.10-phase10-real-image-conversion-drivers -m "File Converter Phase 10 real image conversion drivers"
git push origin v0.1.10-phase10-real-image-conversion-drivers
```
