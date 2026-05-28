# File Converter — Phase 03 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 03 — Converter Domain Core**  
Диапазон задач: **CONV-031 → CONV-045**  
Основа нумерации: Phase 00 содержит `CONV-001 → CONV-008`, Phase 01 содержит `CONV-009 → CONV-020`, Phase 02 содержит `CONV-021 → CONV-030`, поэтому Phase 03 начинается с `CONV-031`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 03 соответствует блоку:

```txt
Phase 03 — Converter Domain Core
```

Правильный диапазон Phase 03:

```txt
CONV-031 — Create Format Enum Skeleton
CONV-032 — Test Format Normalization Rules
CONV-033 — Implement Format Normalization
CONV-034 — Create Converter Contract
CONV-035 — Create Converter Target DTO
CONV-036 — Create Fake Converter Implementations
CONV-037 — Create Converter Registry Skeleton
CONV-038 — Test Registry Lists Converters By Source
CONV-039 — Implement Registry Lookup Methods
CONV-040 — Create Options Schema Field Structure
CONV-041 — Test Options Schema Validation
CONV-042 — Implement Options Schema Validator
CONV-043 — Create Options Validator Skeleton
CONV-044 — Test Options Defaults And Invalid Values
CONV-045 — Implement Options Validation
```

Phase 03 создаёт **ядро описания конвертеров**, но ещё не создаёт реальные конвертеры `PNG → JPG`, `JPG → WEBP`, `PNG → PDF` и так далее.

Реальные MVP converter capabilities будут в следующей фазе.

---

# 2. Цель Phase 03

Phase 03 должна создать базовую доменную модель, на которой потом будут строиться все конвертеры.

Ключевая архитектурная идея:

```txt
converter = source_format + target_format + options_schema + validation
```

После Phase 03 должно быть готово:

```txt
- Format enum/value object;
- format normalization rules;
- Converter contract;
- ConverterTarget DTO;
- fake converters for tests;
- ConverterRegistry;
- registry lookup by source format;
- exact source → target lookup;
- options schema field structure;
- options schema validator;
- options validator with defaults and allowed values;
- unit tests for all core rules.
```

Эта фаза не делает file upload, dashboard UI, real conversion drivers, jobs, queues, billing, API или storage.

---

# 3. Scope Phase 03

## Входит

```txt
- App\Enums\FileFormat or App\Support\Converters\FileFormat;
- format normalization: jpeg → jpg;
- supported MVP formats: png, jpg, webp, pdf;
- unsupported format handling;
- Converter interface;
- ConverterTarget DTO;
- fake converters used only for tests;
- ConverterRegistry;
- registry methods: all, forSource, find, targetsFor;
- OptionsSchema field structure;
- OptionsSchemaValidator;
- OptionsValidator;
- defaults normalization;
- allowed option validation;
- tests for registry and options behavior.
```

## Не входит

```txt
- real PNG → JPG converter;
- real JPG → PNG converter;
- real PNG/JPG → WEBP converter;
- real PNG/JPG → PDF converter;
- image processing packages;
- file upload;
- FileRecord model;
- ConversionJob model;
- queues;
- dashboard stepper logic;
- dynamic Livewire settings form;
- billing;
- credits;
- Cashier;
- API routes;
- OpenAPI docs;
- storage cleanup;
- user-facing converter pages.
```

---

# 4. Critical Decisions

## 4.1. Build around conversion capabilities, not file categories

Нельзя строить систему как:

```txt
ImageConverter
DocumentConverter
AudioConverter
```

Это быстро сломается, потому что настройки зависят не от категории, а от пары:

```txt
PNG → JPG
PNG → PDF
JPG → WEBP
PDF → JPG
```

Правильная модель:

```txt
source_format + target_format + options_schema + converter_key
```

## 4.2. Format normalization is mandatory

Пользователь и MIME detector могут дать:

```txt
jpg
jpeg
image/jpeg
.JPG
```

Внутри системы это должно превращаться в один canonical format:

```txt
jpg
```

Если этого не сделать сразу, registry будет содержать хаос:

```txt
jpeg → png
jpg → png
image/jpeg → png
```

## 4.3. Registry must not instantiate random classes dynamically

Плохой путь:

```php
$class = 'App\\Converters\\' . ucfirst($source) . 'To' . ucfirst($target);
return new $class();
```

Правильный путь:

```php
ConverterRegistry receives explicit list of Converter instances.
```

На первом этапе можно регистрировать через service provider/config. Автоматический discovery не нужен.

## 4.4. Options schema is not a UI concern

Schema нужна не только Livewire.

Она будет использоваться:

```txt
- dashboard UI;
- API docs;
- API validation;
- cost estimator;
- future presets;
- future converter pages.
```

Поэтому schema не должна быть Blade-specific или Livewire-specific.

## 4.5. Options validator must be strict

Нельзя молча принимать неизвестные options.

Плохо:

```php
$options['unknown'] ignored silently
```

Правильно:

```txt
unknown option rejected
invalid allowed value rejected
default values applied explicitly
```

Иначе API-клиенты и UI будут думать, что настройка применена, хотя она не работает.

## 4.6. No real converter drivers in Phase 03

Phase 03 — это domain core. Реальные драйверы будут позже.

Нельзя устанавливать:

```txt
Intervention Image
Imagick wrapper
Dompdf
FFmpeg
LibreOffice integration
```

В этой фазе нужны только fake converters для проверки registry/options behavior.

---

# 5. Architecture Rules

## 5.1. Suggested namespace

Рекомендуемая структура:

```txt
app/Support/Converters/Contracts/Converter.php
app/Support/Converters/DTO/ConverterTarget.php
app/Support/Converters/DTO/OptionsSchemaField.php
app/Support/Converters/Exceptions/InvalidConverterOptionsException.php
app/Support/Converters/Exceptions/InvalidOptionsSchemaException.php
app/Support/Converters/Exceptions/UnsupportedFormatException.php
app/Support/Converters/ConverterRegistry.php
app/Support/Converters/OptionsSchemaValidator.php
app/Support/Converters/OptionsValidator.php
app/Enums/FileFormat.php
```

Если проект предпочитает `app/Domain/Converters`, можно использовать его, но не смешивать с Livewire/UI.

## 5.2. Converter contract must be small

В Phase 03 `Converter` описывает capability, но ещё не выполняет conversion.

Минимальный contract:

```php
interface Converter
{
    public function key(): string;

    public function sourceFormat(): string;

    public function targetFormat(): string;

    public function label(): string;

    public function description(): string;

    public function optionsSchema(): array;

    public function validateOptions(array $options): array;
}
```

Выполнение реальной конвертации будет отдельным `ConverterDriver` в будущей фазе.

## 5.3. Registry should return target data for UI/API

UI не должен сам собирать названия карточек.

Registry должен уметь вернуть:

```txt
format
label
description
recommended
converter_key
```

## 5.4. Tests should not depend on real converters

В Phase 03 использовать fake converters:

```txt
FakePngToJpgConverter
FakePngToPdfConverter
FakeJpgToWebpConverter
```

Их можно держать в `tests/Fakes`.

## 5.5. No database required in Phase 03

Phase 03 должна быть почти полностью unit-testable.

Нельзя создавать migrations/models ради registry или options schema.

---

# 6. GitFlow для Phase 03

## Base branch

Все задачи Phase 03 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-031-create-format-enum-skeleton
feature/CONV-035-create-converter-target-dto
feature/CONV-045-implement-options-validation
```

## Commit format

```txt
CONV-031: Create Format enum skeleton
CONV-035: Create ConverterTarget DTO
CONV-045: Implement options validation
```

## Release branch

После выполнения `CONV-031`–`CONV-045`:

```txt
release/v0.1.3-phase03-converter-domain-core
```

## Tag

После merge release branch в `main`:

```txt
v0.1.3-phase03-converter-domain-core
```

---

# 7. TDD Rules for Phase 03

## Для FileFormat

Test-first:

```txt
- png is supported;
- jpg is supported;
- jpeg normalizes to jpg;
- webp is supported;
- pdf is supported;
- unsupported format is rejected;
- uppercase input normalizes to lowercase.
```

## Для ConverterRegistry

Test-first:

```txt
- registry returns all converters;
- registry returns converters for source png;
- registry finds exact png → jpg converter;
- registry returns null for unsupported png → mp3;
- registry returns target cards for source format.
```

## Для OptionsSchemaValidator

Test-first:

```txt
- valid schema passes;
- field without key fails;
- field without type fails;
- unsupported field type fails;
- select/segmented fields require options;
- duplicate keys fail.
```

## Для OptionsValidator

Test-first:

```txt
- default values are applied;
- valid user options override defaults;
- invalid allowed value is rejected;
- unknown option is rejected;
- toggle values normalize to boolean;
- required fields without default/user value fail.
```

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Domain / Converter Core / Tests
Type: Test / Feature / Contract / DTO / Validator
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

# 9. Phase 03 Atomic Tasks

---

## CONV-031 — Create Format Enum Skeleton

**Area:** Domain / Formats  
**Type:** Contract  
**Priority:** P0  
**Branch:** `feature/CONV-031-create-format-enum-skeleton`  
**Base branch:** `develop`  
**Depends on:** CONV-030

### Goal

Создать базовый enum/value object для форматов файлов.

### TDD step

Unit test:

```php
it('has file format enum with MVP cases', function () {
    expect(FileFormat::Png->value)->toBe('png');
    expect(FileFormat::Jpg->value)->toBe('jpg');
    expect(FileFormat::Webp->value)->toBe('webp');
    expect(FileFormat::Pdf->value)->toBe('pdf');
});
```

Тест должен упасть до создания enum.

### Implementation

Создать:

```txt
app/Enums/FileFormat.php
```

Enum:

```php
namespace App\Enums;

enum FileFormat: string
{
    case Png = 'png';
    case Jpg = 'jpg';
    case Webp = 'webp';
    case Pdf = 'pdf';
}
```

Не добавлять пока аудио/видео/docx/xlsx. Они не входят в MVP Phase 03.

### Acceptance criteria

- `FileFormat` существует.
- Есть cases: `Png`, `Jpg`, `Webp`, `Pdf`.
- Значения lowercase.
- Тест проходит.
- Нет converter logic.

### Definition of Done

- Тест написан первым.
- Enum создан.
- `composer test` проходит.
- `composer lint` проходит.
- Коммит: `CONV-031: Create Format enum skeleton`

### Files likely touched

```txt
app/Enums/FileFormat.php
tests/Unit/Converters/FileFormatTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-032 — Test Format Normalization Rules

**Area:** Domain / Formats / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-032-test-format-normalization-rules`  
**Base branch:** `develop`  
**Depends on:** CONV-031

### Goal

Написать тесты для нормализации форматов.

### TDD step

Unit tests:

```php
it('normalizes jpeg to jpg', function () {
    expect(FileFormat::normalize('jpeg'))->toBe('jpg');
});

it('normalizes uppercase input', function () {
    expect(FileFormat::normalize('PNG'))->toBe('png');
});

it('normalizes extension with leading dot', function () {
    expect(FileFormat::normalize('.webp'))->toBe('webp');
});

it('rejects unsupported format', function () {
    FileFormat::normalize('mp3');
})->throws(UnsupportedFormatException::class);
```

Тесты должны упасть до CONV-033.

### Implementation

Только добавить тесты.

Не реализовывать normalization в этой задаче.

### Acceptance criteria

- Тесты для `jpeg → jpg` добавлены.
- Тесты для uppercase добавлены.
- Тесты для leading dot добавлены.
- Тест для unsupported format добавлен.
- Тесты ожидаемо падают до реализации.

### Definition of Done

- Тесты написаны.
- Тесты падают до CONV-033.
- Коммит: `CONV-032: Test format normalization rules`

### Files likely touched

```txt
tests/Unit/Converters/FileFormatTest.php
```

После этого сделай MR в `develop`. Merge разрешён после подтверждения, что новые тесты падают ожидаемо или будут сразу закрыты CONV-033 в последовательном PR workflow.

---

## CONV-033 — Implement Format Normalization

**Area:** Domain / Formats  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-033-implement-format-normalization`  
**Base branch:** `develop`  
**Depends on:** CONV-032

### Goal

Реализовать нормализацию форматов и explicit exception для unsupported formats.

### TDD step

Использовать падающие тесты из CONV-032.

### Implementation

Создать exception:

```txt
app/Support/Converters/Exceptions/UnsupportedFormatException.php
```

Пример:

```php
namespace App\Support\Converters\Exceptions;

use DomainException;

final class UnsupportedFormatException extends DomainException
{
    public static function forInput(string $input): self
    {
        return new self("Unsupported file format: {$input}");
    }
}
```

Добавить в `FileFormat`:

```php
public static function normalize(string $input): string
{
    $value = strtolower(trim($input));
    $value = ltrim($value, '.');

    if ($value === 'jpeg') {
        return self::Jpg->value;
    }

    foreach (self::cases() as $case) {
        if ($case->value === $value) {
            return $case->value;
        }
    }

    throw UnsupportedFormatException::forInput($input);
}
```

Можно добавить helper:

```php
public static function isSupported(string $input): bool
```

Но только если нужен тестом.

### Acceptance criteria

- `jpeg` нормализуется в `jpg`.
- Uppercase input нормализуется.
- Leading dot удаляется.
- Unsupported format бросает `UnsupportedFormatException`.
- Все тесты FileFormat проходят.

### Definition of Done

- Normalization implemented.
- Exception added.
- Tests pass.
- `composer test` проходит.
- `composer lint` проходит.
- Коммит: `CONV-033: Implement format normalization`

### Files likely touched

```txt
app/Enums/FileFormat.php
app/Support/Converters/Exceptions/UnsupportedFormatException.php
tests/Unit/Converters/FileFormatTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-034 — Create Converter Contract

**Area:** Domain / Converter Core  
**Type:** Contract  
**Priority:** P0  
**Branch:** `feature/CONV-034-create-converter-contract`  
**Base branch:** `develop`  
**Depends on:** CONV-033

### Goal

Создать contract, описывающий conversion capability.

### TDD step

Unit test:

```php
it('has converter contract with required methods', function () {
    $reflection = new ReflectionClass(Converter::class);

    expect($reflection->isInterface())->toBeTrue();

    foreach ([
        'key',
        'sourceFormat',
        'targetFormat',
        'label',
        'description',
        'optionsSchema',
        'validateOptions',
    ] as $method) {
        expect($reflection->hasMethod($method))->toBeTrue();
    }
});
```

Тест должен упасть до создания interface.

### Implementation

Создать:

```txt
app/Support/Converters/Contracts/Converter.php
```

Interface:

```php
namespace App\Support\Converters\Contracts;

interface Converter
{
    public function key(): string;

    public function sourceFormat(): string;

    public function targetFormat(): string;

    public function label(): string;

    public function description(): string;

    public function optionsSchema(): array;

    public function validateOptions(array $options): array;
}
```

Не добавлять метод `convert()` в этот contract. Реальное выполнение будет через future `ConverterDriver`.

### Acceptance criteria

- `Converter` interface существует.
- Все required methods есть.
- Нет `convert()` method.
- Interface не зависит от Livewire/HTTP/DB.
- Тест проходит.

### Definition of Done

- Тест написан первым.
- Contract создан.
- Tests pass.
- Коммит: `CONV-034: Create Converter contract`

### Files likely touched

```txt
app/Support/Converters/Contracts/Converter.php
tests/Unit/Converters/ConverterContractTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-035 — Create Converter Target DTO

**Area:** Domain / Converter Core / DTO  
**Type:** DTO  
**Priority:** P0  
**Branch:** `feature/CONV-035-create-converter-target-dto`  
**Base branch:** `develop`  
**Depends on:** CONV-034

### Goal

Создать DTO, который registry будет возвращать для выбора target format в UI/API.

### TDD step

Unit test:

```php
it('creates converter target dto', function () {
    $target = new ConverterTarget(
        format: 'jpg',
        label: 'JPG',
        description: 'Best for photos and sharing',
        converterKey: 'png_to_jpg',
        recommended: true,
    );

    expect($target->format)->toBe('jpg');
    expect($target->label)->toBe('JPG');
    expect($target->recommended)->toBeTrue();
});
```

### Implementation

Создать:

```txt
app/Support/Converters/DTO/ConverterTarget.php
```

DTO:

```php
namespace App\Support\Converters\DTO;

final readonly class ConverterTarget
{
    public function __construct(
        public string $format,
        public string $label,
        public string $description,
        public string $converterKey,
        public bool $recommended = false,
    ) {}

    public function toArray(): array
    {
        return [
            'format' => $this->format,
            'label' => $this->label,
            'description' => $this->description,
            'converter_key' => $this->converterKey,
            'recommended' => $this->recommended,
        ];
    }
}
```

### Acceptance criteria

- `ConverterTarget` exists.
- DTO is immutable/readonly.
- Has `toArray()` method.
- Contains format/label/description/converterKey/recommended.
- Test passes.

### Definition of Done

- Тест написан.
- DTO создан.
- Tests pass.
- Коммит: `CONV-035: Create ConverterTarget DTO`

### Files likely touched

```txt
app/Support/Converters/DTO/ConverterTarget.php
tests/Unit/Converters/ConverterTargetTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-036 — Create Fake Converter Implementations

**Area:** Tests / Converter Core  
**Type:** Test Support  
**Priority:** P0  
**Branch:** `feature/CONV-036-create-fake-converter-implementations`  
**Base branch:** `develop`  
**Depends on:** CONV-035

### Goal

Создать fake converters для unit-тестов registry/options без реальных conversion drivers.

### TDD step

Unit test:

```php
it('has fake png to jpg converter', function () {
    $converter = new FakePngToJpgConverter();

    expect($converter->key())->toBe('png_to_jpg');
    expect($converter->sourceFormat())->toBe('png');
    expect($converter->targetFormat())->toBe('jpg');
});
```

### Implementation

Создать test-only fakes:

```txt
tests/Fakes/Converters/FakePngToJpgConverter.php
tests/Fakes/Converters/FakePngToPdfConverter.php
tests/Fakes/Converters/FakeJpgToWebpConverter.php
```

Каждый fake implements `Converter`.

Пример:

```php
final class FakePngToJpgConverter implements Converter
{
    public function key(): string
    {
        return 'png_to_jpg';
    }

    public function sourceFormat(): string
    {
        return 'png';
    }

    public function targetFormat(): string
    {
        return 'jpg';
    }

    public function label(): string
    {
        return 'JPG';
    }

    public function description(): string
    {
        return 'Best for photos and sharing';
    }

    public function optionsSchema(): array
    {
        return [];
    }

    public function validateOptions(array $options): array
    {
        return $options;
    }
}
```

### Acceptance criteria

- Fake converters exist under `tests/Fakes`.
- Fake converters implement `Converter`.
- Fake PNG→JPG works.
- Fake PNG→PDF works.
- Fake JPG→WEBP works.
- No production converter capabilities added yet.

### Definition of Done

- Test fakes created.
- Tests pass.
- Composer autoload can resolve test fakes.
- Коммит: `CONV-036: Create fake converter implementations`

### Files likely touched

```txt
tests/Fakes/Converters/FakePngToJpgConverter.php
tests/Fakes/Converters/FakePngToPdfConverter.php
tests/Fakes/Converters/FakeJpgToWebpConverter.php
tests/Unit/Converters/FakeConverterTest.php
composer.json
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-037 — Create Converter Registry Skeleton

**Area:** Domain / Converter Core  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-037-create-converter-registry-skeleton`  
**Base branch:** `develop`  
**Depends on:** CONV-036

### Goal

Создать skeleton `ConverterRegistry`.

### TDD step

Unit test:

```php
it('can instantiate converter registry with converters', function () {
    $registry = new ConverterRegistry([
        new FakePngToJpgConverter(),
    ]);

    expect($registry)->toBeInstanceOf(ConverterRegistry::class);
});
```

Тест должен упасть до создания registry.

### Implementation

Создать:

```txt
app/Support/Converters/ConverterRegistry.php
```

Skeleton:

```php
namespace App\Support\Converters;

use App\Support\Converters\Contracts\Converter;

final class ConverterRegistry
{
    /** @param list<Converter> $converters */
    public function __construct(
        private readonly array $converters = [],
    ) {}
}
```

Пока без lookup logic. Это будет в CONV-039.

### Acceptance criteria

- `ConverterRegistry` exists.
- Accepts list of converters.
- No dynamic class discovery.
- No service provider binding yet unless necessary.
- Test passes.

### Definition of Done

- Тест написан.
- Registry skeleton создан.
- Tests pass.
- Коммит: `CONV-037: Create ConverterRegistry skeleton`

### Files likely touched

```txt
app/Support/Converters/ConverterRegistry.php
tests/Unit/Converters/ConverterRegistryTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-038 — Test Registry Lists Converters By Source

**Area:** Domain / Converter Core / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-038-test-registry-lists-converters-by-source`  
**Base branch:** `develop`  
**Depends on:** CONV-037

### Goal

Написать тесты для lookup behavior в `ConverterRegistry`.

### TDD step

Unit tests:

```php
it('returns all converters', function () {
    $registry = new ConverterRegistry([
        new FakePngToJpgConverter(),
        new FakePngToPdfConverter(),
    ]);

    expect($registry->all())->toHaveCount(2);
});

it('returns converters for source format', function () {
    $registry = new ConverterRegistry([
        new FakePngToJpgConverter(),
        new FakePngToPdfConverter(),
        new FakeJpgToWebpConverter(),
    ]);

    expect($registry->forSource('png'))->toHaveCount(2);
    expect($registry->forSource('jpg'))->toHaveCount(1);
});

it('finds exact source target converter', function () {
    $registry = new ConverterRegistry([
        new FakePngToJpgConverter(),
        new FakePngToPdfConverter(),
    ]);

    expect($registry->find('png', 'jpg'))->toBeInstanceOf(FakePngToJpgConverter::class);
});

it('returns null for unsupported conversion pair', function () {
    $registry = new ConverterRegistry([
        new FakePngToJpgConverter(),
    ]);

    expect($registry->find('png', 'mp3'))->toBeNull();
});

it('returns target cards for source format', function () {
    $registry = new ConverterRegistry([
        new FakePngToJpgConverter(),
        new FakePngToPdfConverter(),
    ]);

    $targets = $registry->targetsFor('png');

    expect($targets)->toHaveCount(2);
    expect($targets[0])->toBeInstanceOf(ConverterTarget::class);
});
```

Тесты должны упасть до CONV-039.

### Implementation

Только добавить тесты.

### Acceptance criteria

- Tests for `all()` added.
- Tests for `forSource()` added.
- Tests for `find()` added.
- Tests for unsupported pair added.
- Tests for `targetsFor()` added.
- Tests fail before implementation.

### Definition of Done

- Тесты написаны.
- Тесты ожидаемо падают до CONV-039.
- Коммит: `CONV-038: Test registry lists converters by source`

### Files likely touched

```txt
tests/Unit/Converters/ConverterRegistryTest.php
```

После этого сделай MR в `develop`. Merge разрешён после подтверждения, что новые тесты падают ожидаемо или будут сразу закрыты CONV-039 в последовательном PR workflow.

---

## CONV-039 — Implement Registry Lookup Methods

**Area:** Domain / Converter Core  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-039-implement-registry-lookup-methods`  
**Base branch:** `develop`  
**Depends on:** CONV-038

### Goal

Реализовать lookup methods в `ConverterRegistry`.

### TDD step

Использовать падающие тесты из CONV-038.

### Implementation

В `ConverterRegistry` добавить:

```php
/** @return list<Converter> */
public function all(): array
{
    return array_values($this->converters);
}

/** @return list<Converter> */
public function forSource(string $source): array
{
    $source = FileFormat::normalize($source);

    return array_values(array_filter(
        $this->converters,
        fn (Converter $converter): bool => $converter->sourceFormat() === $source,
    ));
}

public function find(string $source, string $target): ?Converter
{
    $source = FileFormat::normalize($source);
    $target = FileFormat::normalize($target);

    foreach ($this->converters as $converter) {
        if ($converter->sourceFormat() === $source && $converter->targetFormat() === $target) {
            return $converter;
        }
    }

    return null;
}

/** @return list<ConverterTarget> */
public function targetsFor(string $source): array
{
    return array_map(
        fn (Converter $converter): ConverterTarget => new ConverterTarget(
            format: $converter->targetFormat(),
            label: $converter->label(),
            description: $converter->description(),
            converterKey: $converter->key(),
            recommended: false,
        ),
        $this->forSource($source),
    );
}
```

Если `targetFormat()` возвращает unsupported format, это должно быть поймано тестами converter capabilities в будущей фазе.

### Acceptance criteria

- `all()` returns all converters.
- `forSource()` filters by normalized source.
- `find()` finds exact pair.
- Unsupported pair returns null.
- `targetsFor()` returns `ConverterTarget` DTOs.
- Tests pass.

### Definition of Done

- Lookup methods implemented.
- Tests pass.
- `composer test` passes.
- `composer lint` passes.
- Коммит: `CONV-039: Implement registry lookup methods`

### Files likely touched

```txt
app/Support/Converters/ConverterRegistry.php
tests/Unit/Converters/ConverterRegistryTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-040 — Create Options Schema Field Structure

**Area:** Domain / Options Schema  
**Type:** DTO / Contract  
**Priority:** P0  
**Branch:** `feature/CONV-040-create-options-schema-field-structure`  
**Base branch:** `develop`  
**Depends on:** CONV-039

### Goal

Зафиксировать структуру field в options schema.

### TDD step

Unit test:

```php
it('creates options schema field dto', function () {
    $field = new OptionsSchemaField(
        key: 'quality',
        type: 'segmented',
        label: 'Quality',
        default: 'high',
        options: [
            ['value' => 'medium', 'label' => 'Medium'],
            ['value' => 'high', 'label' => 'High'],
        ],
        required: false,
    );

    expect($field->key)->toBe('quality');
    expect($field->type)->toBe('segmented');
    expect($field->default)->toBe('high');
});
```

### Implementation

Создать:

```txt
app/Support/Converters/DTO/OptionsSchemaField.php
```

DTO:

```php
final readonly class OptionsSchemaField
{
    public function __construct(
        public string $key,
        public string $type,
        public string $label,
        public mixed $default = null,
        public array $options = [],
        public bool $required = false,
        public ?string $help = null,
    ) {}

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'type' => $this->type,
            'label' => $this->label,
            'default' => $this->default,
            'options' => $this->options,
            'required' => $this->required,
            'help' => $this->help,
        ];
    }
}
```

В Phase 03 converters still return array schema. DTO нужен как структура/документация и для будущей нормализации.

### Acceptance criteria

- `OptionsSchemaField` exists.
- DTO is readonly.
- Supports key/type/label/default/options/required/help.
- Has `toArray()`.
- Test passes.

### Definition of Done

- Тест написан.
- DTO создан.
- Tests pass.
- Коммит: `CONV-040: Create options schema field structure`

### Files likely touched

```txt
app/Support/Converters/DTO/OptionsSchemaField.php
tests/Unit/Converters/OptionsSchemaFieldTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-041 — Test Options Schema Validation

**Area:** Domain / Options Schema / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-041-test-options-schema-validation`  
**Base branch:** `develop`  
**Depends on:** CONV-040

### Goal

Написать тесты для validation структуры options schema.

### TDD step

Unit tests:

```php
it('accepts valid options schema', function () {
    $schema = [
        [
            'key' => 'quality',
            'type' => 'segmented',
            'label' => 'Quality',
            'default' => 'high',
            'options' => [
                ['value' => 'medium', 'label' => 'Medium'],
                ['value' => 'high', 'label' => 'High'],
            ],
        ],
    ];

    app(OptionsSchemaValidator::class)->validate($schema);

    expect(true)->toBeTrue();
});

it('rejects field without key', function () {
    app(OptionsSchemaValidator::class)->validate([
        ['type' => 'select', 'label' => 'Quality'],
    ]);
})->throws(InvalidOptionsSchemaException::class);

it('rejects unsupported field type', function () {
    app(OptionsSchemaValidator::class)->validate([
        ['key' => 'quality', 'type' => 'magic', 'label' => 'Quality'],
    ]);
})->throws(InvalidOptionsSchemaException::class);

it('rejects duplicate field keys', function () {
    app(OptionsSchemaValidator::class)->validate([
        ['key' => 'quality', 'type' => 'toggle', 'label' => 'Quality'],
        ['key' => 'quality', 'type' => 'toggle', 'label' => 'Quality Again'],
    ]);
})->throws(InvalidOptionsSchemaException::class);

it('requires options for select and segmented fields', function () {
    app(OptionsSchemaValidator::class)->validate([
        ['key' => 'quality', 'type' => 'select', 'label' => 'Quality'],
    ]);
})->throws(InvalidOptionsSchemaException::class);
```

Тесты должны упасть до CONV-042.

### Implementation

Только добавить тесты.

### Acceptance criteria

- Valid schema test added.
- Missing key test added.
- Unsupported type test added.
- Duplicate keys test added.
- Select/segmented options requirement test added.
- Tests fail before implementation.

### Definition of Done

- Тесты написаны.
- Тесты ожидаемо падают до CONV-042.
- Коммит: `CONV-041: Test options schema validation`

### Files likely touched

```txt
tests/Unit/Converters/OptionsSchemaValidatorTest.php
```

После этого сделай MR в `develop`. Merge разрешён после подтверждения, что новые тесты падают ожидаемо или будут сразу закрыты CONV-042 в последовательном PR workflow.

---

## CONV-042 — Implement Options Schema Validator

**Area:** Domain / Options Schema  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-042-implement-options-schema-validator`  
**Base branch:** `develop`  
**Depends on:** CONV-041

### Goal

Реализовать validator для структуры options schema.

### TDD step

Использовать падающие тесты из CONV-041.

### Implementation

Создать exception:

```txt
app/Support/Converters/Exceptions/InvalidOptionsSchemaException.php
```

Создать validator:

```txt
app/Support/Converters/OptionsSchemaValidator.php
```

Supported field types:

```txt
select
segmented
toggle
color
number
range
```

Validation rules:

```txt
- schema must be array;
- each field must have key;
- each field must have type;
- each field must have label;
- key must be unique;
- type must be supported;
- select/segmented require non-empty options;
- options must contain value and label;
- required is optional boolean;
- default is optional.
```

Example implementation shape:

```php
final class OptionsSchemaValidator
{
    private const SUPPORTED_TYPES = [
        'select',
        'segmented',
        'toggle',
        'color',
        'number',
        'range',
    ];

    public function validate(array $schema): void
    {
        $keys = [];

        foreach ($schema as $field) {
            if (! is_array($field)) {
                throw InvalidOptionsSchemaException::becauseFieldIsInvalid();
            }

            $key = $field['key'] ?? null;
            $type = $field['type'] ?? null;
            $label = $field['label'] ?? null;

            if (! is_string($key) || $key === '') {
                throw InvalidOptionsSchemaException::becauseKeyIsMissing();
            }

            if (in_array($key, $keys, true)) {
                throw InvalidOptionsSchemaException::becauseKeyIsDuplicated($key);
            }

            $keys[] = $key;

            if (! is_string($type) || ! in_array($type, self::SUPPORTED_TYPES, true)) {
                throw InvalidOptionsSchemaException::becauseTypeIsUnsupported((string) $type);
            }

            if (! is_string($label) || $label === '') {
                throw InvalidOptionsSchemaException::becauseLabelIsMissing($key);
            }

            if (in_array($type, ['select', 'segmented'], true)) {
                $this->validateOptions($field);
            }
        }
    }
}
```

### Acceptance criteria

- Valid schema passes.
- Missing key rejected.
- Unsupported type rejected.
- Duplicate keys rejected.
- Select/segmented without options rejected.
- Tests pass.

### Definition of Done

- Exception created.
- Validator created.
- Tests pass.
- `composer test` passes.
- `composer lint` passes.
- Коммит: `CONV-042: Implement options schema validator`

### Files likely touched

```txt
app/Support/Converters/Exceptions/InvalidOptionsSchemaException.php
app/Support/Converters/OptionsSchemaValidator.php
tests/Unit/Converters/OptionsSchemaValidatorTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-043 — Create Options Validator Skeleton

**Area:** Domain / Options Validation  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-043-create-options-validator-skeleton`  
**Base branch:** `develop`  
**Depends on:** CONV-042

### Goal

Создать skeleton `OptionsValidator`, который будет валидировать пользовательские options против schema.

### TDD step

Unit test:

```php
it('can instantiate options validator', function () {
    $validator = app(OptionsValidator::class);

    expect($validator)->toBeInstanceOf(OptionsValidator::class);
    expect(method_exists($validator, 'validate'))->toBeTrue();
});
```

Тест должен упасть до создания class.

### Implementation

Создать:

```txt
app/Support/Converters/OptionsValidator.php
app/Support/Converters/Exceptions/InvalidConverterOptionsException.php
```

Skeleton:

```php
final class OptionsValidator
{
    public function __construct(
        private readonly OptionsSchemaValidator $schemaValidator,
    ) {}

    public function validate(array $schema, array $options): array
    {
        throw new LogicException('Not implemented yet.');
    }
}
```

### Acceptance criteria

- `OptionsValidator` exists.
- Has `validate(array $schema, array $options): array`.
- Depends on `OptionsSchemaValidator`.
- `InvalidConverterOptionsException` exists.
- Skeleton test passes.

### Definition of Done

- Тест написан.
- Skeleton создан.
- Tests pass.
- Коммит: `CONV-043: Create OptionsValidator skeleton`

### Files likely touched

```txt
app/Support/Converters/OptionsValidator.php
app/Support/Converters/Exceptions/InvalidConverterOptionsException.php
tests/Unit/Converters/OptionsValidatorTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-044 — Test Options Defaults And Invalid Values

**Area:** Domain / Options Validation / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-044-test-options-defaults-and-invalid-values`  
**Base branch:** `develop`  
**Depends on:** CONV-043

### Goal

Написать тесты поведения `OptionsValidator`.

### TDD step

Unit tests:

```php
it('applies default values from schema', function () {
    $schema = [
        [
            'key' => 'quality',
            'type' => 'segmented',
            'label' => 'Quality',
            'default' => 'high',
            'options' => [
                ['value' => 'medium', 'label' => 'Medium'],
                ['value' => 'high', 'label' => 'High'],
            ],
        ],
    ];

    $normalized = app(OptionsValidator::class)->validate($schema, []);

    expect($normalized)->toBe(['quality' => 'high']);
});

it('allows valid user value to override default', function () {
    $schema = [
        [
            'key' => 'quality',
            'type' => 'segmented',
            'label' => 'Quality',
            'default' => 'high',
            'options' => [
                ['value' => 'medium', 'label' => 'Medium'],
                ['value' => 'high', 'label' => 'High'],
            ],
        ],
    ];

    $normalized = app(OptionsValidator::class)->validate($schema, [
        'quality' => 'medium',
    ]);

    expect($normalized)->toBe(['quality' => 'medium']);
});

it('rejects invalid option value', function () {
    $schema = [
        [
            'key' => 'quality',
            'type' => 'select',
            'label' => 'Quality',
            'default' => 'high',
            'options' => [
                ['value' => 'medium', 'label' => 'Medium'],
                ['value' => 'high', 'label' => 'High'],
            ],
        ],
    ];

    app(OptionsValidator::class)->validate($schema, [
        'quality' => 'ultra',
    ]);
})->throws(InvalidConverterOptionsException::class);

it('rejects unknown option key', function () {
    app(OptionsValidator::class)->validate([], [
        'unknown' => true,
    ]);
})->throws(InvalidConverterOptionsException::class);

it('normalizes toggle value to boolean', function () {
    $schema = [
        [
            'key' => 'remove_metadata',
            'type' => 'toggle',
            'label' => 'Remove metadata',
            'default' => false,
        ],
    ];

    $normalized = app(OptionsValidator::class)->validate($schema, [
        'remove_metadata' => 1,
    ]);

    expect($normalized)->toBe(['remove_metadata' => true]);
});
```

Тесты должны упасть до CONV-045.

### Implementation

Только добавить тесты.

### Acceptance criteria

- Default value test added.
- User override test added.
- Invalid value test added.
- Unknown option test added.
- Toggle normalization test added.
- Tests fail before implementation.

### Definition of Done

- Тесты написаны.
- Тесты ожидаемо падают до CONV-045.
- Коммит: `CONV-044: Test options defaults and invalid values`

### Files likely touched

```txt
tests/Unit/Converters/OptionsValidatorTest.php
```

После этого сделай MR в `develop`. Merge разрешён после подтверждения, что новые тесты падают ожидаемо или будут сразу закрыты CONV-045 в последовательном PR workflow.

---

## CONV-045 — Implement Options Validation

**Area:** Domain / Options Validation  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-045-implement-options-validation`  
**Base branch:** `develop`  
**Depends on:** CONV-044

### Goal

Реализовать `OptionsValidator`: defaults, allowed values, unknown keys, toggle normalization.

### TDD step

Использовать падающие тесты из CONV-044.

### Implementation

В `OptionsValidator::validate()`:

```php
public function validate(array $schema, array $options): array
{
    $this->schemaValidator->validate($schema);

    $schemaByKey = [];

    foreach ($schema as $field) {
        $schemaByKey[$field['key']] = $field;
    }

    foreach (array_keys($options) as $key) {
        if (! array_key_exists($key, $schemaByKey)) {
            throw InvalidConverterOptionsException::becauseOptionIsUnknown($key);
        }
    }

    $normalized = [];

    foreach ($schemaByKey as $key => $field) {
        $hasUserValue = array_key_exists($key, $options);
        $hasDefault = array_key_exists('default', $field);

        if (! $hasUserValue && ! $hasDefault) {
            if (($field['required'] ?? false) === true) {
                throw InvalidConverterOptionsException::becauseOptionIsRequired($key);
            }

            continue;
        }

        $value = $hasUserValue ? $options[$key] : $field['default'];

        $normalized[$key] = $this->normalizeValue($field, $value);
    }

    return $normalized;
}
```

Value rules:

```txt
select/segmented:
- value must be one of field.options[].value

toggle:
- normalize to boolean

color:
- accept hex string for now, reject empty invalid string

number/range:
- numeric value only
```

Do not overbuild min/max validation unless schema already contains min/max tests.

### Acceptance criteria

- Defaults applied.
- Valid user values override defaults.
- Invalid select/segmented value rejected.
- Unknown option key rejected.
- Toggle normalized to boolean.
- Required field without value/default rejected.
- Tests pass.

### Definition of Done

- Options validation implemented.
- Exception methods added.
- Tests pass.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.
- Коммит: `CONV-045: Implement options validation`

### Files likely touched

```txt
app/Support/Converters/OptionsValidator.php
app/Support/Converters/Exceptions/InvalidConverterOptionsException.php
tests/Unit/Converters/OptionsValidatorTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 03 Completion Criteria

Phase 03 завершена, когда:

```txt
- CONV-031–CONV-045 выполнены;
- FileFormat enum exists;
- jpeg normalizes to jpg;
- unsupported formats throw UnsupportedFormatException;
- Converter contract exists;
- ConverterTarget DTO exists;
- fake converters exist for tests;
- ConverterRegistry exists;
- registry returns all converters;
- registry filters converters by source;
- registry finds exact source → target pair;
- registry returns null for unsupported pair;
- registry returns ConverterTarget DTOs;
- OptionsSchemaField DTO exists;
- OptionsSchemaValidator exists;
- invalid schema is rejected;
- duplicate schema keys are rejected;
- unsupported schema field types are rejected;
- OptionsValidator exists;
- defaults are applied;
- invalid option values are rejected;
- unknown option keys are rejected;
- no real converter capabilities were added;
- no file upload was added;
- no billing was added;
- no API was added;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 03

Без отдельной задачи нельзя:

```txt
- создавать PNG → JPG production converter;
- создавать PNG → PDF production converter;
- устанавливать image processing packages;
- создавать FileRecord model;
- создавать ConversionJob model;
- создавать upload UI;
- менять dashboard workflow;
- добавлять Livewire converter component;
- добавлять billing/credits;
- устанавливать Laravel Cashier;
- добавлять API routes;
- добавлять OpenAPI docs;
- добавлять queues;
- добавлять storage cleanup;
- добавлять public converter pages;
- добавлять OCR/video/audio/document converters;
- добавлять React/Vue/Inertia.
```

---

# 12. Recommended Execution Order

```txt
CONV-031 Create Format Enum Skeleton
CONV-032 Test Format Normalization Rules
CONV-033 Implement Format Normalization
CONV-034 Create Converter Contract
CONV-035 Create Converter Target DTO
CONV-036 Create Fake Converter Implementations
CONV-037 Create Converter Registry Skeleton
CONV-038 Test Registry Lists Converters By Source
CONV-039 Implement Registry Lookup Methods
CONV-040 Create Options Schema Field Structure
CONV-041 Test Options Schema Validation
CONV-042 Implement Options Schema Validator
CONV-043 Create Options Validator Skeleton
CONV-044 Test Options Defaults And Invalid Values
CONV-045 Implement Options Validation
```

---

# 13. Release

После завершения Phase 03:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh

git checkout -b release/v0.1.3-phase03-converter-domain-core
git push -u origin release/v0.1.3-phase03-converter-domain-core
```

После этого сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.3-phase03-converter-domain-core -m "File Converter Phase 03 Converter Domain Core"
git push origin v0.1.3-phase03-converter-domain-core
```
