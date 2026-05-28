# File Converter — Phase 9 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 9 — Conversion Job Core**  
Диапазон задач: **CONV-112 → CONV-129**  
Основа нумерации: Phase 8 завершилась на `CONV-111`, поэтому Phase 9 начинается с `CONV-112`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 9 соответствует блоку:

```txt
Phase 9 — Conversion Job Core
```

Правильный диапазон Phase 9:

```txt
CONV-112 — Add Database Queue Tables
CONV-113 — Create Conversion Status Enum
CONV-114 — Create Conversion Jobs Table
CONV-115 — Add Conversion Job Model Relationships
CONV-116 — Create Conversion Context DTO
CONV-117 — Create Conversion Result DTO
CONV-118 — Create Converter Driver Interface
CONV-119 — Create Converter Driver Registry
CONV-120 — Add Fake Converter Driver For Tests
CONV-121 — Create Result File Recorder Action
CONV-122 — Create Conversion Job Action Skeleton
CONV-123 — Test Conversion Job Creation
CONV-124 — Implement Conversion Job Creation
CONV-125 — Create Process Conversion Job Skeleton
CONV-126 — Test Successful Conversion Job Processing
CONV-127 — Implement Successful Conversion Job Processing
CONV-128 — Test Failed Conversion Job Processing
CONV-129 — Implement Failed Conversion Job Processing
```

Phase 9 создаёт backend-ядро конвертационных задач:

```txt
valid uploaded file + selected converter + valid options
    → ConversionJob queued
    → queued worker job
    → driver execution through interface
    → result FileRecord
    → completed / failed status
```

Важно:

```txt
Phase 8 = dynamic settings form
Phase 9 = conversion job core with fake/test drivers
Phase 10 = real image conversion drivers
Phase 11 = convert UI flow
Phase 14/15 = credits and cost estimator
```

В Phase 9 **не должно быть реальной image/PDF conversion logic**. Эта фаза должна доказать архитектуру jobs/drivers/status lifecycle через fake driver.

---

# 2. Цель Phase 9

Phase 9 добавляет доменную и application-логику для создания и обработки `ConversionJob`.

После Phase 9 система должна уметь на backend-уровне:

```txt
- создать ConversionJob из existing FileRecord, target format и options;
- проверить, что source → target converter существует;
- провалидировать options через converter/options validator;
- поставить ConversionJob в status queued;
- dispatch ProcessConversionJob;
- обработать ConversionJob через ConverterDriver interface;
- создать result FileRecord из ConversionResult;
- связать ConversionJob с result_file_id;
- перевести job в completed;
- корректно обработать exception и перевести job в failed;
- сохранить error_code/error_message;
- покрыть happy path и failure path тестами.
```

Это backend-only фаза. UI кнопка `Convert Now` будет подключена позже.

---

# 3. Scope Phase 9

## Входит

```txt
- database queue tables;
- ConversionStatus enum;
- conversion_jobs migration;
- ConversionJob model;
- ConversionJob factory;
- relationships to user/source file/result file;
- ConversionContext DTO;
- ConversionResult DTO;
- ConverterDriver interface;
- ConverterDriverRegistry;
- FakeConverterDriver for tests;
- ResultFileRecorderAction;
- CreateConversionJobAction;
- ProcessConversionJob queue job;
- success lifecycle tests;
- failure lifecycle tests.
```

## Не входит

```txt
- real PNG → JPG conversion;
- real JPG → PNG conversion;
- real WEBP conversion;
- real Image → PDF generation;
- Livewire Convert Now integration;
- download route;
- Recent Conversions table;
- CreditLedger;
- ConversionCostEstimator;
- Laravel Cashier;
- API endpoints;
- OpenAPI docs;
- batch conversion;
- OCR;
- progress from real drivers;
- user-facing conversion result screen.
```

---

# 4. Critical Decisions

## 4.1. ConversionJob is the authoritative conversion lifecycle record

Нельзя хранить состояние конвертации только во временном Livewire state.

Правильно:

```txt
ConversionJob.status = queued / processing / completed / failed
```

Неправильно:

```txt
Livewire property $isConverting = true as the only source of truth
```

Livewire позже будет только читать состояние `ConversionJob`.

## 4.2. Conversion must run through queue job

Конвертация не должна выполняться в HTTP request/action synchronously.

Правильно:

```txt
CreateConversionJobAction creates DB row and dispatches ProcessConversionJob
```

Неправильно:

```txt
CreateConversionJobAction directly converts file and blocks request
```

Даже если fake driver быстрый, архитектура должна быть queue-first.

## 4.3. Drivers are infrastructure boundary

Application layer не должен знать, как именно работает Imagick/Intervention/FFmpeg.

Правильно:

```php
ConverterDriver::convert(ConversionContext $context): ConversionResult
```

Неправильно:

```php
if ($converterKey === 'png_to_jpg') {
    imagejpeg(...)
}
```

## 4.4. Result file must be represented as FileRecord

Результат конвертации — это тоже файл в системе.

Поэтому успешная конвертация должна создать `FileRecord` для результата и записать:

```txt
conversion_jobs.result_file_id
```

Нельзя просто вернуть path string без модели.

## 4.5. Phase 9 must use fake drivers

Реальные image drivers будут в Phase 10.  
Phase 9 должна тестировать lifecycle, а не image processing details.

Правильно:

```txt
FakeConverterDriver writes fake output file and returns ConversionResult
```

Неправильно:

```txt
install Imagick and implement PNG → JPG in Phase 9
```

## 4.6. No billing in ConversionJob creation yet

В Phase 9 нельзя проверять credits или списывать credits.

Billing будет позже:

```txt
Phase 13/14/15 = FeatureAccess + CreditLedger + ConversionCostEstimator
```

Phase 9 может оставить extension point, но не должна делать pricing.

---

# 5. Architecture Rules

## 5.1. CreateConversionJobAction owns job creation

Нельзя создавать `ConversionJob::create()` напрямую из Livewire/API позже.

Правильно:

```php
app(CreateConversionJobAction::class)->handle(...)
```

## 5.2. ProcessConversionJob owns processing lifecycle

Нельзя вызывать driver из controller/Livewire.

Правильно:

```php
ProcessConversionJob::dispatch($conversionJob->id)
```

## 5.3. Status transitions must be explicit

Минимальные transitions Phase 9:

```txt
queued → processing → completed
queued → processing → failed
```

Не нужно пока строить полноценный FSM-класс, но прямое хаотичное изменение статуса в разных местах запрещено.

## 5.4. Store raw options_json, but validate first

`options_json` сохраняется в `conversion_jobs`, но только после validation/normalization через converter/options validator.

## 5.5. Do not expose queue internals to UI

UI позже должен видеть:

```txt
status
progress
error message
result available
```

но не должен знать:

```txt
job UUID from queue backend
attempt id
worker internals
```

---

# 6. GitFlow для Phase 9

## Base branch

Все задачи Phase 9 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-112-add-database-queue-tables
feature/CONV-124-implement-conversion-job-creation
feature/CONV-129-implement-failed-conversion-job-processing
```

## Commit format

```txt
CONV-112: Add database queue tables
CONV-124: Implement conversion job creation
CONV-129: Implement failed conversion job processing
```

## Release branch

После выполнения `CONV-112`–`CONV-129`:

```txt
release/v0.1.9-phase09-conversion-job-core
```

## Tag

После merge release branch в `main`:

```txt
v0.1.9-phase09-conversion-job-core
```

---

# 7. TDD Rules for Phase 9

## Для ConversionJob

Тестировать:

```txt
- model relationships;
- status cast;
- source/result file relationship;
- options_json cast.
```

## Для CreateConversionJobAction

Test-first:

```txt
- creates queued conversion job;
- saves normalized options;
- rejects unsupported source → target;
- rejects invalid options;
- dispatches ProcessConversionJob.
```

## Для ProcessConversionJob

Test-first:

```txt
- queued job becomes processing;
- fake driver is called;
- result FileRecord is created;
- job becomes completed;
- result_file_id is set;
- driver exception marks job failed;
- error_code/error_message saved.
```

## Для drivers

В Phase 9 тестировать только contract/fake implementation:

```txt
- fake driver returns ConversionResult;
- registry resolves driver by converter_key;
- missing driver throws domain exception.
```

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Backend / Jobs / Models / Tests
Type: Test / Feature / Migration / Action / Queue / DTO
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
- npm run build проходит, если frontend затронут
- Нет функциональности вне scope задачи
- Коммит содержит ID задачи

Files likely touched:
- path/to/file
```

---

# 9. Phase 9 Atomic Tasks

---

## CONV-112 — Add Database Queue Tables

**Area:** Infrastructure / Queue  
**Type:** Migration / Config  
**Priority:** P0  
**Branch:** `feature/CONV-112-add-database-queue-tables`  
**Base branch:** `develop`  
**Depends on:** CONV-111

### Goal

Подготовить database queue backend для будущего `ProcessConversionJob`.

### TDD step

No direct test — Laravel queue infrastructure setup.

Проверка:

```bash
php artisan migrate:fresh --seed
php artisan queue:work --once
composer test
```

### Implementation

Создать queue tables:

```bash
php artisan queue:table
php artisan queue:failed-table
php artisan migrate
```

Убедиться, что `.env.example` уже использует:

```env
QUEUE_CONNECTION=database
```

В тестовой среде queue должна оставаться sync/fake через test setup.

### Acceptance criteria

- Таблица `jobs` создаётся migration-ом.
- Таблица `failed_jobs` создаётся migration-ом.
- `php artisan migrate:fresh --seed` проходит.
- `QUEUE_CONNECTION=database` зафиксирован для local env.
- Тесты не требуют реального worker.

### Definition of Done

- Queue migrations добавлены.
- Config проверен.
- `composer test` проходит.
- Коммит: `CONV-112: Add database queue tables`

### Files likely touched

```txt
.env.example
database/migrations/*_create_jobs_table.php
database/migrations/*_create_failed_jobs_table.php
config/queue.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-113 — Create Conversion Status Enum

**Area:** Backend / Domain  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-113-create-conversion-status-enum`  
**Base branch:** `develop`  
**Depends on:** CONV-112

### Goal

Создать enum статусов конвертационной задачи.

### TDD step

Unit test:

```php
it('defines conversion job statuses', function () {
    expect(ConversionStatus::Queued->value)->toBe('queued');
    expect(ConversionStatus::Processing->value)->toBe('processing');
    expect(ConversionStatus::Completed->value)->toBe('completed');
    expect(ConversionStatus::Failed->value)->toBe('failed');
});
```

Тест должен упасть до создания enum.

### Implementation

Создать:

```txt
app/Enums/ConversionStatus.php
```

Enum:

```php
enum ConversionStatus: string
{
    case Draft = 'draft';
    case Queued = 'queued';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}
```

`Draft` может понадобиться позже, но в Phase 9 основной flow начинается с `queued`.

### Acceptance criteria

- `ConversionStatus` существует.
- Все expected statuses есть.
- Enum values lowercase string.
- Unit test проходит.

### Definition of Done

- Тест написан первым.
- Enum создан.
- Тест проходит.
- Коммит: `CONV-113: Create conversion status enum`

### Files likely touched

```txt
app/Enums/ConversionStatus.php
tests/Unit/Enums/ConversionStatusTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-114 — Create Conversion Jobs Table

**Area:** Backend / Database  
**Type:** Migration / Model  
**Priority:** P0  
**Branch:** `feature/CONV-114-create-conversion-jobs-table`  
**Base branch:** `develop`  
**Depends on:** CONV-113

### Goal

Создать таблицу `conversion_jobs`, модель `ConversionJob` и factory.

### TDD step

Feature/model test:

```php
it('can create conversion job record', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create([
        'extension' => 'png',
    ]);

    $job = ConversionJob::factory()
        ->for($user)
        ->for($file, 'sourceFile')
        ->create([
            'source_format' => 'png',
            'target_format' => 'jpg',
            'converter_key' => 'png_to_jpg',
            'status' => ConversionStatus::Queued,
        ]);

    expect($job->exists)->toBeTrue();
    expect($job->status)->toBe(ConversionStatus::Queued);
});
```

Тест должен упасть до migration/model/factory.

### Implementation

Создать migration:

```txt
conversion_jobs
```

Поля:

```txt
id
user_id
source_file_id
result_file_id nullable
source_format
target_format
converter_key
options_json
status
progress
error_code nullable
error_message nullable
started_at nullable
completed_at nullable
expires_at nullable
created_at
updated_at
```

Рекомендации:

```php
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
$table->foreignId('source_file_id')->constrained('files')->restrictOnDelete();
$table->foreignId('result_file_id')->nullable()->constrained('files')->nullOnDelete();
$table->string('source_format', 20);
$table->string('target_format', 20);
$table->string('converter_key', 100);
$table->json('options_json')->default('{}');
$table->string('status', 30)->index();
$table->unsignedTinyInteger('progress')->default(0);
$table->string('error_code')->nullable();
$table->text('error_message')->nullable();
$table->timestamp('started_at')->nullable();
$table->timestamp('completed_at')->nullable();
$table->timestamp('expires_at')->nullable()->index();
```

Создать:

```txt
app/Models/ConversionJob.php
database/factories/ConversionJobFactory.php
```

### Acceptance criteria

- Таблица `conversion_jobs` существует.
- Модель `ConversionJob` существует.
- Factory существует.
- `status` cast работает через enum.
- `options_json` cast to array.
- Model test проходит.

### Definition of Done

- Тест написан первым.
- Migration/model/factory добавлены.
- Тест проходит.
- Коммит: `CONV-114: Create conversion jobs table`

### Files likely touched

```txt
app/Models/ConversionJob.php
database/factories/ConversionJobFactory.php
database/migrations/*_create_conversion_jobs_table.php
tests/Feature/Models/ConversionJobTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-115 — Add Conversion Job Model Relationships

**Area:** Backend / Models  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-115-add-conversion-job-model-relationships`  
**Base branch:** `develop`  
**Depends on:** CONV-114

### Goal

Добавить relationships для `ConversionJob`.

### TDD step

Model tests:

```php
it('belongs to user and source file', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create();

    $job = ConversionJob::factory()
        ->for($user)
        ->for($file, 'sourceFile')
        ->create();

    expect($job->user->is($user))->toBeTrue();
    expect($job->sourceFile->is($file))->toBeTrue();
});
```

```php
it('can belong to result file', function () {
    $user = User::factory()->create();
    $source = FileRecord::factory()->for($user)->create();
    $result = FileRecord::factory()->for($user)->create();

    $job = ConversionJob::factory()
        ->for($user)
        ->for($source, 'sourceFile')
        ->for($result, 'resultFile')
        ->create();

    expect($job->resultFile->is($result))->toBeTrue();
});
```

### Implementation

В `ConversionJob` добавить:

```php
public function user(): BelongsTo
public function sourceFile(): BelongsTo
public function resultFile(): BelongsTo
```

В `User` можно добавить:

```php
public function conversionJobs(): HasMany
```

В `FileRecord` можно добавить:

```php
public function sourceConversionJobs(): HasMany
public function resultConversionJobs(): HasMany
```

### Acceptance criteria

- `ConversionJob::user()` работает.
- `ConversionJob::sourceFile()` работает.
- `ConversionJob::resultFile()` работает.
- User relation работает, если добавлена.
- Tests pass.

### Definition of Done

- Тесты написаны.
- Relationships добавлены.
- Тесты проходят.
- Коммит: `CONV-115: Add conversion job model relationships`

### Files likely touched

```txt
app/Models/ConversionJob.php
app/Models/User.php
app/Models/FileRecord.php
tests/Feature/Models/ConversionJobTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-116 — Create Conversion Context DTO

**Area:** Backend / DTO  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-116-create-conversion-context-dto`  
**Base branch:** `develop`  
**Depends on:** CONV-115

### Goal

Создать DTO, который передаётся в driver при выполнении конвертации.

### TDD step

Unit test:

```php
it('creates conversion context dto', function () {
    $job = ConversionJob::factory()->make();
    $sourceFile = FileRecord::factory()->make();

    $context = new ConversionContext(
        job: $job,
        sourceFile: $sourceFile,
        options: ['quality' => 'high'],
        outputDirectory: 'conversions/output'
    );

    expect($context->job)->toBe($job);
    expect($context->sourceFile)->toBe($sourceFile);
    expect($context->options)->toBe(['quality' => 'high']);
    expect($context->outputDirectory)->toBe('conversions/output');
});
```

### Implementation

Создать:

```txt
app/DTO/ConversionContext.php
```

DTO:

```php
final readonly class ConversionContext
{
    public function __construct(
        public ConversionJob $job,
        public FileRecord $sourceFile,
        public array $options,
        public string $outputDirectory,
    ) {}
}
```

Если в проекте уже используется namespace `App\Data` или `App\DTOs`, следовать существующему стилю.

### Acceptance criteria

- DTO существует.
- DTO readonly.
- Содержит job/sourceFile/options/outputDirectory.
- Unit test проходит.

### Definition of Done

- Тест написан.
- DTO создан.
- Тест проходит.
- Коммит: `CONV-116: Create conversion context DTO`

### Files likely touched

```txt
app/DTO/ConversionContext.php
tests/Unit/DTO/ConversionContextTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-117 — Create Conversion Result DTO

**Area:** Backend / DTO  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-117-create-conversion-result-dto`  
**Base branch:** `develop`  
**Depends on:** CONV-116

### Goal

Создать DTO результата driver-а.

### TDD step

Unit test:

```php
it('creates conversion result dto', function () {
    $result = new ConversionResult(
        path: 'conversions/results/output.jpg',
        originalName: 'image.jpg',
        mimeType: 'image/jpeg',
        extension: 'jpg',
        sizeBytes: 12345,
        metadata: ['width' => 100, 'height' => 100]
    );

    expect($result->extension)->toBe('jpg');
    expect($result->sizeBytes)->toBe(12345);
    expect($result->metadata['width'])->toBe(100);
});
```

### Implementation

Создать:

```txt
app/DTO/ConversionResult.php
```

DTO:

```php
final readonly class ConversionResult
{
    public function __construct(
        public string $path,
        public string $originalName,
        public string $mimeType,
        public string $extension,
        public int $sizeBytes,
        public array $metadata = [],
    ) {}
}
```

### Acceptance criteria

- DTO существует.
- DTO readonly.
- Path/name/mime/extension/size/metadata доступны.
- Unit test проходит.

### Definition of Done

- Тест написан.
- DTO создан.
- Тест проходит.
- Коммит: `CONV-117: Create conversion result DTO`

### Files likely touched

```txt
app/DTO/ConversionResult.php
tests/Unit/DTO/ConversionResultTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-118 — Create Converter Driver Interface

**Area:** Backend / Drivers  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-118-create-converter-driver-interface`  
**Base branch:** `develop`  
**Depends on:** CONV-117

### Goal

Создать interface для runtime-конвертации.

### TDD step

Unit test через anonymous fake class:

```php
it('defines converter driver contract', function () {
    $driver = new class implements ConverterDriver {
        public function key(): string
        {
            return 'fake_driver';
        }

        public function convert(ConversionContext $context): ConversionResult
        {
            return new ConversionResult(
                path: 'fake/output.txt',
                originalName: 'output.txt',
                mimeType: 'text/plain',
                extension: 'txt',
                sizeBytes: 10,
                metadata: []
            );
        }
    };

    expect($driver->key())->toBe('fake_driver');
});
```

### Implementation

Создать:

```txt
app/Contracts/ConverterDriver.php
```

Interface:

```php
interface ConverterDriver
{
    public function key(): string;

    public function convert(ConversionContext $context): ConversionResult;
}
```

### Acceptance criteria

- Interface exists.
- Has `key()`.
- Has `convert(ConversionContext): ConversionResult`.
- Unit test проходит.

### Definition of Done

- Тест написан.
- Interface создан.
- Тест проходит.
- Коммит: `CONV-118: Create converter driver interface`

### Files likely touched

```txt
app/Contracts/ConverterDriver.php
tests/Unit/Contracts/ConverterDriverTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-119 — Create Converter Driver Registry

**Area:** Backend / Drivers  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-119-create-converter-driver-registry`  
**Base branch:** `develop`  
**Depends on:** CONV-118

### Goal

Создать registry, который находит driver по `converter_key`.

### TDD step

Unit tests:

```php
it('finds converter driver by key', function () {
    $driver = new FakeConverterDriver('png_to_jpg');
    $registry = new ConverterDriverRegistry([$driver]);

    expect($registry->find('png_to_jpg'))->toBe($driver);
});
```

```php
it('throws when driver is missing', function () {
    $registry = new ConverterDriverRegistry([]);

    $registry->findOrFail('missing_driver');
})->throws(MissingConverterDriverException::class);
```

Тесты должны упасть до реализации registry/fake/exception.

### Implementation

Создать:

```txt
app/Services/Converters/ConverterDriverRegistry.php
app/Exceptions/Converters/MissingConverterDriverException.php
```

Методы:

```php
public function all(): array;
public function find(string $key): ?ConverterDriver;
public function findOrFail(string $key): ConverterDriver;
```

Registry должен индексировать drivers по `key()`.

### Acceptance criteria

- Registry принимает список drivers.
- `find()` возвращает driver или null.
- `findOrFail()` throws domain exception.
- Duplicate keys либо rejected, либо последний не должен silently overwrite. Лучше rejected.
- Tests pass.

### Definition of Done

- Тесты написаны.
- Registry создан.
- Missing exception создан.
- Tests pass.
- Коммит: `CONV-119: Create converter driver registry`

### Files likely touched

```txt
app/Services/Converters/ConverterDriverRegistry.php
app/Exceptions/Converters/MissingConverterDriverException.php
tests/Unit/Services/ConverterDriverRegistryTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-120 — Add Fake Converter Driver For Tests

**Area:** Tests / Drivers  
**Type:** Test Utility  
**Priority:** P0  
**Branch:** `feature/CONV-120-add-fake-converter-driver-for-tests`  
**Base branch:** `develop`  
**Depends on:** CONV-119

### Goal

Добавить fake driver, которым можно тестировать lifecycle без реальной конвертации.

### TDD step

Unit test:

```php
it('fake converter driver writes fake result file', function () {
    Storage::fake('local');

    $driver = new FakeConverterDriver(key: 'png_to_jpg');
    $context = new ConversionContext(
        job: ConversionJob::factory()->make(),
        sourceFile: FileRecord::factory()->make(),
        options: [],
        outputDirectory: 'conversions/results'
    );

    $result = $driver->convert($context);

    expect($result->extension)->toBe('txt');
    Storage::disk('local')->assertExists($result->path);
});
```

### Implementation

Создать test utility:

```txt
tests/Fakes/FakeConverterDriver.php
```

Fake driver:

```php
final class FakeConverterDriver implements ConverterDriver
{
    public function __construct(
        private readonly string $key,
        private readonly bool $shouldFail = false,
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function convert(ConversionContext $context): ConversionResult
    {
        if ($this->shouldFail) {
            throw new RuntimeException('Fake conversion failed.');
        }

        $path = $context->outputDirectory.'/fake-result.txt';
        Storage::disk('local')->put($path, 'fake result');

        return new ConversionResult(
            path: $path,
            originalName: 'fake-result.txt',
            mimeType: 'text/plain',
            extension: 'txt',
            sizeBytes: strlen('fake result'),
            metadata: []
        );
    }
}
```

### Acceptance criteria

- Fake driver implements `ConverterDriver`.
- Can succeed.
- Can fail via constructor flag.
- Writes fake output file.
- Test utility is only used in tests.
- Tests pass.

### Definition of Done

- Тест написан.
- Fake driver создан.
- Tests pass.
- Коммит: `CONV-120: Add fake converter driver for tests`

### Files likely touched

```txt
tests/Fakes/FakeConverterDriver.php
tests/Unit/Fakes/FakeConverterDriverTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-121 — Create Result File Recorder Action

**Area:** Backend / Files  
**Type:** Action  
**Priority:** P0  
**Branch:** `feature/CONV-121-create-result-file-recorder-action`  
**Base branch:** `develop`  
**Depends on:** CONV-120

### Goal

Создать action, который превращает `ConversionResult` в `FileRecord` результата.

### TDD step

Feature/action test:

```php
it('records conversion result as file record', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Storage::disk('local')->put('conversions/results/output.jpg', 'fake image');

    $result = new ConversionResult(
        path: 'conversions/results/output.jpg',
        originalName: 'output.jpg',
        mimeType: 'image/jpeg',
        extension: 'jpg',
        sizeBytes: 10,
        metadata: ['width' => 100]
    );

    $file = app(RecordConversionResultFileAction::class)->handle($user, $result);

    expect($file)->toBeInstanceOf(FileRecord::class);
    expect($file->user_id)->toBe($user->id);
    expect($file->extension)->toBe('jpg');
});
```

Тест должен упасть до action.

### Implementation

Создать:

```txt
app/Actions/Files/RecordConversionResultFileAction.php
```

Action должен создать `FileRecord`:

```txt
user_id
original_name
stored_path
mime_type
extension
size_bytes
checksum
metadata_json
status = analyzed/uploaded depending existing FileStatus
expires_at nullable for now or basic retention placeholder
```

Если `FileStatus::Analyzed` существует — использовать его.  
Если нет — использовать ближайший существующий статус из Phase 5.

Checksum можно считать через storage contents:

```php
hash('sha256', Storage::disk('local')->get($result->path))
```

### Acceptance criteria

- Result file becomes FileRecord.
- user_id set.
- stored_path set from DTO.
- extension/mime/size set.
- metadata saved.
- checksum calculated.
- Test passes.

### Definition of Done

- Тест написан первым.
- Action создан.
- Тест проходит.
- Коммит: `CONV-121: Create result file recorder action`

### Files likely touched

```txt
app/Actions/Files/RecordConversionResultFileAction.php
tests/Feature/Actions/RecordConversionResultFileActionTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-122 — Create Conversion Job Action Skeleton

**Area:** Backend / Actions  
**Type:** Action  
**Priority:** P0  
**Branch:** `feature/CONV-122-create-conversion-job-action-skeleton`  
**Base branch:** `develop`  
**Depends on:** CONV-121

### Goal

Создать skeleton `CreateConversionJobAction`.

### TDD step

Unit test:

```php
it('has create conversion job action with handle method', function () {
    $action = app(CreateConversionJobAction::class);

    expect(method_exists($action, 'handle'))->toBeTrue();
});
```

Тест должен упасть до создания action.

### Implementation

Создать:

```txt
app/Actions/Conversions/CreateConversionJobAction.php
```

Skeleton signature:

```php
final class CreateConversionJobAction
{
    public function handle(
        User $user,
        FileRecord $sourceFile,
        string $targetFormat,
        array $options = [],
    ): ConversionJob {
        throw new LogicException('Not implemented yet.');
    }
}
```

### Acceptance criteria

- Action exists.
- `handle(User, FileRecord, string, array): ConversionJob` exists.
- Action resolves from container.
- No business logic yet.
- Test passes.

### Definition of Done

- Тест написан первым.
- Skeleton создан.
- Тест проходит.
- Коммит: `CONV-122: Create conversion job action skeleton`

### Files likely touched

```txt
app/Actions/Conversions/CreateConversionJobAction.php
tests/Unit/Actions/CreateConversionJobActionSkeletonTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-123 — Test Conversion Job Creation

**Area:** Backend / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-123-test-conversion-job-creation`  
**Base branch:** `develop`  
**Depends on:** CONV-122

### Goal

Написать падающие тесты для создания conversion job.

### TDD step

Feature/action test:

```php
it('creates queued conversion job for valid source target and options', function () {
    Queue::fake();

    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create([
        'extension' => 'png',
        'mime_type' => 'image/png',
    ]);

    $job = app(CreateConversionJobAction::class)->handle(
        user: $user,
        sourceFile: $file,
        targetFormat: 'jpg',
        options: ['quality' => 'high']
    );

    expect($job->status)->toBe(ConversionStatus::Queued);
    expect($job->source_format)->toBe('png');
    expect($job->target_format)->toBe('jpg');
    expect($job->converter_key)->toBe('png_to_jpg');

    Queue::assertPushed(ProcessConversionJob::class);
});
```

Unsupported conversion test:

```php
it('rejects unsupported conversion pair', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    app(CreateConversionJobAction::class)->handle($user, $file, 'mp3', []);
})->throws(UnsupportedConversionException::class);
```

Invalid options test:

```php
it('rejects invalid converter options before creating job', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    app(CreateConversionJobAction::class)->handle($user, $file, 'jpg', [
        'quality' => 'invalid',
    ]);
})->throws(InvalidConverterOptionsException::class);
```

Тесты должны упасть до CONV-124.

### Implementation

Только добавить тесты.

### Acceptance criteria

- Тест на happy path существует.
- Тест проверяет queued status.
- Тест проверяет source/target/converter_key.
- Тест проверяет dispatch `ProcessConversionJob`.
- Unsupported pair test существует.
- Invalid options test существует.
- Тесты падают до реализации.

### Definition of Done

- Тесты добавлены.
- Тесты ожидаемо падают.
- Коммит: `CONV-123: Test conversion job creation`

### Files likely touched

```txt
tests/Feature/Actions/CreateConversionJobActionTest.php
```

После этого сделай MR в `develop`. Merge разрешён после подтверждения, что новые тесты падают по ожидаемой причине.

---

## CONV-124 — Implement Conversion Job Creation

**Area:** Backend / Actions  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-124-implement-conversion-job-creation`  
**Base branch:** `develop`  
**Depends on:** CONV-123

### Goal

Реализовать `CreateConversionJobAction`.

### TDD step

Использовать падающие тесты из CONV-123.

### Implementation

`CreateConversionJobAction` должен:

```txt
- получить source format из FileRecord extension;
- найти converter через ConverterRegistry;
- если converter отсутствует — throw UnsupportedConversionException;
- validate/normalize options;
- создать ConversionJob со status queued;
- progress = 0;
- dispatch ProcessConversionJob;
- вернуть ConversionJob.
```

Создать exceptions, если ещё нет:

```txt
app/Exceptions/Converters/UnsupportedConversionException.php
app/Exceptions/Converters/InvalidConverterOptionsException.php
```

Пример skeleton:

```php
$sourceFormat = Format::normalize($sourceFile->extension);
$targetFormat = Format::normalize($targetFormat);

$converter = $this->converterRegistry->find($sourceFormat, $targetFormat);

if ($converter === null) {
    throw UnsupportedConversionException::forPair($sourceFormat, $targetFormat);
}

$normalizedOptions = $converter->validateOptions($options);

$job = ConversionJob::create([...]);

ProcessConversionJob::dispatch($job->id);

return $job;
```

Не проверять credits.  
Не считать cost.  
Не запускать driver напрямую.

### Acceptance criteria

- Valid conversion creates queued job.
- Normalized options saved to `options_json`.
- Unsupported pair rejected.
- Invalid options rejected before DB row creation.
- Queue job dispatched.
- No direct conversion execution.
- Tests from CONV-123 pass.

### Definition of Done

- Action реализован.
- Domain exceptions добавлены.
- Tests pass.
- Коммит: `CONV-124: Implement conversion job creation`

### Files likely touched

```txt
app/Actions/Conversions/CreateConversionJobAction.php
app/Exceptions/Converters/UnsupportedConversionException.php
app/Exceptions/Converters/InvalidConverterOptionsException.php
tests/Feature/Actions/CreateConversionJobActionTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-125 — Create Process Conversion Job Skeleton

**Area:** Backend / Queue  
**Type:** Job  
**Priority:** P0  
**Branch:** `feature/CONV-125-create-process-conversion-job-skeleton`  
**Base branch:** `develop`  
**Depends on:** CONV-124

### Goal

Создать skeleton queue job для обработки `ConversionJob`.

### TDD step

Unit test:

```php
it('has process conversion job with handle method', function () {
    $job = new ProcessConversionJob(conversionJobId: 123);

    expect(method_exists($job, 'handle'))->toBeTrue();
});
```

Тест должен упасть до создания job.

### Implementation

Создать:

```bash
php artisan make:job ProcessConversionJob
```

Constructor:

```php
public function __construct(
    public readonly int $conversionJobId,
) {}
```

`handle()` пока может бросать `LogicException('Not implemented yet.')`, если skeleton test не вызывает handle.

Важно: job принимает ID, а не сериализованную модель. Это снижает риск stale model state.

### Acceptance criteria

- `ProcessConversionJob` существует.
- Constructor accepts conversionJobId.
- Job implements ShouldQueue.
- Test passes.
- No processing logic yet.

### Definition of Done

- Тест написан.
- Skeleton job создан.
- Тест проходит.
- Коммит: `CONV-125: Create process conversion job skeleton`

### Files likely touched

```txt
app/Jobs/ProcessConversionJob.php
tests/Unit/Jobs/ProcessConversionJobSkeletonTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-126 — Test Successful Conversion Job Processing

**Area:** Backend / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-126-test-successful-conversion-job-processing`  
**Base branch:** `develop`  
**Depends on:** CONV-125

### Goal

Написать падающий тест: `ProcessConversionJob` успешно обрабатывает job через fake driver.

### TDD step

Feature/job test:

```php
it('processes conversion job successfully with fake driver', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $sourceFile = FileRecord::factory()->for($user)->create([
        'extension' => 'png',
        'mime_type' => 'image/png',
    ]);

    $conversionJob = ConversionJob::factory()
        ->for($user)
        ->for($sourceFile, 'sourceFile')
        ->create([
            'source_format' => 'png',
            'target_format' => 'jpg',
            'converter_key' => 'png_to_jpg',
            'options_json' => ['quality' => 'high'],
            'status' => ConversionStatus::Queued,
            'progress' => 0,
        ]);

    app()->instance(
        ConverterDriverRegistry::class,
        new ConverterDriverRegistry([
            new FakeConverterDriver('png_to_jpg'),
        ])
    );

    app(ProcessConversionJob::class, [
        'conversionJobId' => $conversionJob->id,
    ])->handle(
        app(ConverterDriverRegistry::class),
        app(RecordConversionResultFileAction::class),
    );

    $fresh = $conversionJob->fresh();

    expect($fresh->status)->toBe(ConversionStatus::Completed);
    expect($fresh->progress)->toBe(100);
    expect($fresh->result_file_id)->not->toBeNull();
    expect($fresh->started_at)->not->toBeNull();
    expect($fresh->completed_at)->not->toBeNull();
});
```

Тест должен упасть до CONV-127.

### Implementation

Только добавить тест.

Если direct `app(ProcessConversionJob::class, ...)` неудобен, можно создать объект напрямую:

```php
$queueJob = new ProcessConversionJob($conversionJob->id);
$queueJob->handle(...);
```

### Acceptance criteria

- Тест существует.
- Fake driver используется.
- Job becomes completed.
- result_file_id set.
- progress = 100.
- started_at/completed_at set.
- Тест падает до реализации.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-126: Test successful conversion job processing`

### Files likely touched

```txt
tests/Feature/Jobs/ProcessConversionJobTest.php
```

После этого сделай MR в `develop`. Merge разрешён после подтверждения, что новый тест падает по ожидаемой причине.

---

## CONV-127 — Implement Successful Conversion Job Processing

**Area:** Backend / Queue  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-127-implement-successful-conversion-job-processing`  
**Base branch:** `develop`  
**Depends on:** CONV-126

### Goal

Реализовать happy path обработки `ConversionJob`.

### TDD step

Использовать падающий тест из CONV-126.

### Implementation

В `ProcessConversionJob::handle()`:

```txt
- найти ConversionJob by id;
- если job не queued — решить policy: skip or throw;
- set status = processing;
- set progress = 5/10;
- set started_at;
- resolve driver by converter_key;
- build ConversionContext;
- call driver->convert($context);
- record result file through RecordConversionResultFileAction;
- set result_file_id;
- set status = completed;
- set progress = 100;
- set completed_at;
```

Пример output directory:

```php
$outputDirectory = "conversions/results/{$conversionJob->id}";
```

Важное правило: driver не создаёт `FileRecord`; driver только возвращает `ConversionResult`.

### Acceptance criteria

- Queued job becomes processing then completed.
- Driver called through registry.
- Result FileRecord created through action.
- result_file_id set.
- progress = 100 on success.
- started_at/completed_at set.
- Test from CONV-126 passes.

### Definition of Done

- Happy path реализован.
- Тест проходит.
- Existing tests pass.
- Коммит: `CONV-127: Implement successful conversion job processing`

### Files likely touched

```txt
app/Jobs/ProcessConversionJob.php
tests/Feature/Jobs/ProcessConversionJobTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-128 — Test Failed Conversion Job Processing

**Area:** Backend / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-128-test-failed-conversion-job-processing`  
**Base branch:** `develop`  
**Depends on:** CONV-127

### Goal

Написать падающий тест: ошибка driver-а переводит `ConversionJob` в `failed` и сохраняет error.

### TDD step

Feature/job test:

```php
it('marks conversion job as failed when driver throws exception', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $sourceFile = FileRecord::factory()->for($user)->create(['extension' => 'png']);

    $conversionJob = ConversionJob::factory()
        ->for($user)
        ->for($sourceFile, 'sourceFile')
        ->create([
            'source_format' => 'png',
            'target_format' => 'jpg',
            'converter_key' => 'png_to_jpg',
            'status' => ConversionStatus::Queued,
            'progress' => 0,
        ]);

    app()->instance(
        ConverterDriverRegistry::class,
        new ConverterDriverRegistry([
            new FakeConverterDriver('png_to_jpg', shouldFail: true),
        ])
    );

    $queueJob = new ProcessConversionJob($conversionJob->id);
    $queueJob->handle(
        app(ConverterDriverRegistry::class),
        app(RecordConversionResultFileAction::class),
    );

    $fresh = $conversionJob->fresh();

    expect($fresh->status)->toBe(ConversionStatus::Failed);
    expect($fresh->error_code)->not->toBeNull();
    expect($fresh->error_message)->toContain('Fake conversion failed');
    expect($fresh->result_file_id)->toBeNull();
});
```

Тест должен упасть до CONV-129, если failure handling ещё не implemented.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Fake failing driver используется.
- Expected failed status.
- Expected error_code/error_message.
- result_file_id remains null.
- Тест падает до реализации.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-128: Test failed conversion job processing`

### Files likely touched

```txt
tests/Feature/Jobs/ProcessConversionJobTest.php
```

После этого сделай MR в `develop`. Merge разрешён после подтверждения, что новый тест падает по ожидаемой причине.

---

## CONV-129 — Implement Failed Conversion Job Processing

**Area:** Backend / Queue  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-129-implement-failed-conversion-job-processing`  
**Base branch:** `develop`  
**Depends on:** CONV-128

### Goal

Реализовать failure handling для `ProcessConversionJob`.

### TDD step

Использовать падающий тест из CONV-128.

### Implementation

В `ProcessConversionJob::handle()` обернуть driver execution в `try/catch`.

При exception:

```txt
- status = failed;
- progress stays current or becomes 0/failed policy;
- error_code = class_basename($exception) or normalized code;
- error_message = safe message;
- completed_at = now() or nullable decision;
- result_file_id remains null;
- exception should not leave job in processing forever.
```

Рекомендация:

```php
catch (Throwable $exception) {
    $conversionJob->forceFill([
        'status' => ConversionStatus::Failed,
        'error_code' => Str::snake(class_basename($exception)),
        'error_message' => $exception->getMessage(),
        'completed_at' => now(),
    ])->save();

    report($exception);

    return;
}
```

Не rethrow в MVP, если тест ожидает controlled failed state.  
Позже можно решить, должен ли Laravel failed_jobs также получать запись.

### Acceptance criteria

- Driver exception does not leave job processing.
- Job becomes failed.
- error_code saved.
- error_message saved.
- result_file_id remains null.
- Success path still works.
- Tests pass.

### Definition of Done

- Failure handling реализован.
- Success/failure tests pass.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.
- Коммит: `CONV-129: Implement failed conversion job processing`

### Files likely touched

```txt
app/Jobs/ProcessConversionJob.php
tests/Feature/Jobs/ProcessConversionJobTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 9 Completion Criteria

Phase 9 завершена, когда:

```txt
- CONV-112–CONV-129 выполнены;
- database queue tables exist;
- ConversionStatus enum exists;
- conversion_jobs table exists;
- ConversionJob model/factory exists;
- ConversionJob relationships work;
- ConversionContext DTO exists;
- ConversionResult DTO exists;
- ConverterDriver interface exists;
- ConverterDriverRegistry exists;
- FakeConverterDriver exists for tests;
- RecordConversionResultFileAction exists;
- CreateConversionJobAction exists;
- valid conversion creates queued ConversionJob;
- unsupported conversion is rejected;
- invalid options are rejected before job creation;
- ProcessConversionJob is dispatched from CreateConversionJobAction;
- ProcessConversionJob can complete a job through fake driver;
- result FileRecord is created on success;
- result_file_id is set on success;
- ProcessConversionJob can fail safely;
- failed jobs have error_code/error_message;
- no real image/PDF conversion driver was added;
- no Livewire Convert Now integration was added;
- no credits/billing logic was added;
- no API endpoints were added;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 9

Без отдельной задачи нельзя:

```txt
- устанавливать image processing packages;
- реализовывать PNG → JPG driver;
- реализовывать JPG → PNG driver;
- реализовывать WEBP driver;
- генерировать PDF из image;
- подключать Convert Now button в Livewire;
- добавлять download route;
- добавлять Recent Conversions table;
- добавлять CreditLedger;
- добавлять ConversionCostEstimator;
- проверять credits перед conversion;
- списывать credits;
- устанавливать Laravel Cashier;
- создавать API endpoints;
- создавать OpenAPI docs;
- добавлять batch conversion;
- добавлять OCR;
- добавлять progress reporting from real drivers;
- добавлять React/Vue/Inertia.
```

---

# 12. Recommended Execution Order

```txt
CONV-112 Add Database Queue Tables
CONV-113 Create Conversion Status Enum
CONV-114 Create Conversion Jobs Table
CONV-115 Add Conversion Job Model Relationships
CONV-116 Create Conversion Context DTO
CONV-117 Create Conversion Result DTO
CONV-118 Create Converter Driver Interface
CONV-119 Create Converter Driver Registry
CONV-120 Add Fake Converter Driver For Tests
CONV-121 Create Result File Recorder Action
CONV-122 Create Conversion Job Action Skeleton
CONV-123 Test Conversion Job Creation
CONV-124 Implement Conversion Job Creation
CONV-125 Create Process Conversion Job Skeleton
CONV-126 Test Successful Conversion Job Processing
CONV-127 Implement Successful Conversion Job Processing
CONV-128 Test Failed Conversion Job Processing
CONV-129 Implement Failed Conversion Job Processing
```

---

# 13. Release

После завершения Phase 9:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh --seed

git checkout -b release/v0.1.9-phase09-conversion-job-core
git push -u origin release/v0.1.9-phase09-conversion-job-core
```

После этого сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.9-phase09-conversion-job-core -m "File Converter Phase 9 conversion job core"
git push origin v0.1.9-phase09-conversion-job-core
```
