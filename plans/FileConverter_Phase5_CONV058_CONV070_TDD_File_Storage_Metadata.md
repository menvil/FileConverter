# File Converter — Phase 05 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 05 — File Storage & Metadata**  
Диапазон задач: **CONV-058 → CONV-070**  
Основа нумерации: Phase 04 завершилась на `CONV-057`, поэтому Phase 05 начинается с `CONV-058`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 05 соответствует блоку:

```txt
Phase 05 — File Storage & Metadata
```

Правильный диапазон Phase 05:

```txt
CONV-058 — Create FileRecord Model And Migration
CONV-059 — Add FileStatus Enum
CONV-060 — Test File Format Detection
CONV-061 — Implement FileFormatDetector
CONV-062 — Test Image Metadata Extraction
CONV-063 — Implement ImageMetadataExtractor
CONV-064 — Test Uploaded File Storage
CONV-065 — Implement StoreUploadedFileAction
CONV-066 — Add Uploaded File Validation Rules
CONV-067 — Add File Ownership Rules
CONV-068 — Add File Expiration Defaults
CONV-069 — Add Stored File Cleanup Safety Test
CONV-070 — Add File Storage Smoke Tests
```

Phase 05 добавляет backend-слой для сохранения загруженных файлов и извлечения metadata.

Важно:

```txt
Phase 05 = store uploaded files + detect format + extract metadata
Phase 06 = dashboard upload UI
Phase 09 = conversion jobs
Phase 10 = real conversion drivers
```

То есть в этой фазе появляются:

```txt
files table
FileRecord model
FileStatus enum
FileFormatDetector
ImageMetadataExtractor
StoreUploadedFileAction
basic upload validation rules
file ownership rules
expiration defaults
storage smoke tests
```

Но не появляются:

```txt
Livewire upload UI
DashboardConverter component
conversion jobs
converter execution
queues
billing/credits
API endpoints
```

---

# 2. Цель Phase 05

Phase 05 должна сделать так, чтобы backend мог принять uploaded file, сохранить его, определить формат и записать нормализованную metadata в БД.

После Phase 05 должно быть готово:

```txt
- FileRecord model;
- files table;
- file statuses;
- reliable format detection for PNG/JPG/WEBP/PDF;
- image metadata extraction for PNG/JPG/WEBP;
- StoreUploadedFileAction;
- validation rules for allowed formats and file size;
- user ownership on uploaded files;
- expires_at defaults for uploaded files;
- tests proving physical file storage works.
```

Эта фаза создаёт фундамент для Phase 06, где Livewire dashboard начнёт вызывать `StoreUploadedFileAction`.

---

# 3. Scope Phase 05

## Входит

```txt
- files migration;
- FileRecord model;
- FileRecordFactory;
- FileStatus enum;
- FileFormatDetector;
- ImageMetadataExtractor;
- StoreUploadedFileAction;
- UploadedFile validation rules;
- checksum calculation;
- metadata_json structure;
- expires_at calculation placeholder/default;
- ownership checks in storage action;
- storage disk tests using fake disk.
```

## Не входит

```txt
- Livewire upload component;
- drag-and-drop UI;
- dashboard state machine;
- target format selection UI;
- conversion settings UI;
- conversion job model;
- image conversion drivers;
- result file download route;
- billing limits by plan;
- credits;
- Cashier;
- API file upload endpoint;
- S3 direct upload;
- chunked upload;
- virus scanning;
- OCR/PDF page metadata extraction.
```

Phase 05 is backend-only. Если в этой фазе появляется UI — это scope creep.

---

# 4. Critical Decisions

## 4.1. FileRecord is not a conversion result yet

`FileRecord` должен хранить любой файл, который система контролирует:

```txt
- uploaded source file;
- future conversion result file;
- future imported cloud file;
```

Но в Phase 05 реализуется только uploaded source file.

Не создавать отдельные модели:

```txt
UploadedFileRecord
SourceFile
ResultFile
```

Одна модель `FileRecord` достаточно гибкая для MVP.

## 4.2. Format detection must normalize extensions

`jpeg` и `jpg` должны считаться одним форматом:

```txt
.jpeg → jpg
.jpg  → jpg
```

Иначе registry будет ломаться на паре `jpeg → pdf`, хотя фактически это `jpg → pdf`.

## 4.3. MIME is primary, extension is fallback

Формат определяется так:

```txt
1. trusted MIME detection
2. extension fallback
3. reject unsupported
```

Нельзя доверять только имени файла:

```txt
fake.png может быть text/plain
```

## 4.4. metadata_json must be predictable

Для изображений metadata должна быть стабильной:

```json
{
  "width": 1200,
  "height": 800,
  "has_transparency": true,
  "orientation": null
}
```

Не писать произвольную metadata без схемы.

## 4.5. StoreUploadedFileAction owns storage

Нельзя размазывать storage logic по Livewire/API/controllers.

Правильно:

```php
app(StoreUploadedFileAction::class)->handle($user, $uploadedFile);
```

Неправильно:

```php
$uploadedFile->store(...);
FileRecord::create(...);
```

в Livewire component.

## 4.6. Physical file and DB row must be consistent enough

Для MVP допустима простая последовательность:

```txt
store file → create DB row
```

Если DB create падает, нужно удалить физический файл.

Полноценный transactional storage невозможен с filesystem, но cleanup on failure обязателен.

## 4.7. No plan-based limits yet

В Phase 05 можно иметь базовый глобальный max file size.

Plan-based limits будут позже через FeatureAccessService.

Не добавлять:

```txt
Free max 25MB
Pro max 250MB
Max max 2GB
```

в Phase 05.

---

# 5. Architecture Rules

## 5.1. File storage actions live in application layer

Рекомендуемые пути:

```txt
app/Actions/Files/StoreUploadedFileAction.php
app/Support/Files/FileFormatDetector.php
app/Support/Files/ImageMetadataExtractor.php
app/Models/FileRecord.php
app/Enums/FileStatus.php
```

Если в проекте уже выбран другой namespace convention, адаптировать, но не смешивать всё в `App\Services`.

## 5.2. No direct use of UploadedFile in domain objects

`UploadedFile` — HTTP/framework object. Он допустим в action boundary, но не должен попадать в converter core.

Правильно:

```txt
StoreUploadedFileAction accepts UploadedFile
Converter layer receives FileRecord
```

## 5.3. Tests must use Storage::fake()

Тесты не должны писать реальные файлы в `storage/app`.

Правильно:

```php
Storage::fake('local');
```

## 5.4. Metadata extraction failure must be safe

Если metadata не извлечена, action не должен молча создавать некорректную запись.

Для MVP:

```txt
image metadata extraction failure → FileStorageException
unsupported file → UnsupportedFormatException
```

PDF metadata можно оставить пустой или минимальной.

## 5.5. No conversion in metadata extractor

Extractor только читает metadata. Он не должен:

```txt
resize image
optimize image
convert image
strip metadata
```

---

# 6. GitFlow для Phase 05

## Base branch

Все задачи Phase 05 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-058-create-filerecord-model-and-migration
feature/CONV-061-implement-file-format-detector
feature/CONV-065-implement-store-uploaded-file-action
```

## Commit format

```txt
CONV-058: Create FileRecord model and migration
CONV-061: Implement FileFormatDetector
CONV-065: Implement StoreUploadedFileAction
```

## Release branch

После выполнения `CONV-058`–`CONV-070`:

```txt
release/v0.1.5-phase05-file-storage-metadata
```

## Tag

После merge release branch в `main`:

```txt
v0.1.5-phase05-file-storage-metadata
```

---

# 7. TDD Rules for Phase 05

## Для FileRecord

Тестировать:

```txt
- FileRecord can be created;
- metadata_json is cast to array;
- status is cast to FileStatus;
- user relation exists;
```

## Для FileFormatDetector

Test-first:

```txt
- detects PNG;
- detects JPG;
- normalizes JPEG to JPG;
- detects WEBP;
- detects PDF;
- rejects unsupported file;
```

## Для ImageMetadataExtractor

Test-first:

```txt
- extracts width;
- extracts height;
- detects transparency for PNG where possible;
- handles JPG without transparency;
```

## Для StoreUploadedFileAction

Test-first:

```txt
- stores uploaded file physically;
- creates FileRecord;
- sets user_id;
- sets normalized extension;
- sets size_bytes;
- sets checksum;
- writes metadata_json;
- sets status analyzed;
- sets expires_at;
```

Если тест напрямую невозможен, задача должна явно сказать:

```txt
No direct test — причина.
```

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Backend / Files / Storage / Tests
Type: Test / Feature / Action / Model / Migration / Support
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

# 9. Phase 05 Atomic Tasks

---

## CONV-058 — Create FileRecord Model And Migration

**Area:** Backend / Files / Database  
**Type:** Migration / Model  
**Priority:** P0  
**Branch:** `feature/CONV-058-create-filerecord-model-and-migration`  
**Base branch:** `develop`  
**Depends on:** CONV-057

### Goal

Создать таблицу `files` и модель `FileRecord` для хранения загруженных и будущих result files.

### TDD step

Feature/model test:

```php
use App\Models\FileRecord;
use App\Models\User;

it('can create a file record', function () {
    $user = User::factory()->create();

    $file = FileRecord::factory()->create([
        'user_id' => $user->id,
        'original_name' => 'image.png',
        'stored_path' => 'uploads/test.png',
        'mime_type' => 'image/png',
        'extension' => 'png',
        'size_bytes' => 12345,
        'metadata_json' => ['width' => 100, 'height' => 100],
    ]);

    expect($file->exists)->toBeTrue();
    expect($file->user->is($user))->toBeTrue();
    expect($file->metadata_json)->toBeArray();
});
```

Тест должен упасть до создания migration/model/factory.

### Implementation

Создать модель и migration:

```bash
php artisan make:model FileRecord -mf
```

Migration:

```php
Schema::create('files', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('original_name');
    $table->string('stored_path');
    $table->string('mime_type')->nullable();
    $table->string('extension', 20);
    $table->unsignedBigInteger('size_bytes');
    $table->string('checksum', 64)->nullable();
    $table->json('metadata_json')->nullable();
    $table->string('status', 30)->default('uploaded');
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'created_at']);
    $table->index(['extension']);
    $table->index(['status']);
    $table->index(['expires_at']);
});
```

Model:

```php
final class FileRecord extends Model
{
    protected $fillable = [
        'user_id',
        'original_name',
        'stored_path',
        'mime_type',
        'extension',
        'size_bytes',
        'checksum',
        'metadata_json',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'metadata_json' => 'array',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

Factory should create realistic default file rows.

### Acceptance criteria

- `files` table exists.
- `FileRecord` model exists.
- `FileRecordFactory` exists.
- `metadata_json` casts to array.
- `expires_at` casts to datetime.
- User relation works.
- Test passes.

### Definition of Done

- Тест написан первым.
- Migration/model/factory созданы.
- Тест проходит.
- `composer test` проходит.
- `composer lint` проходит.
- Коммит: `CONV-058: Create FileRecord model and migration`

### Files likely touched

```txt
app/Models/FileRecord.php
database/migrations/*_create_files_table.php
database/factories/FileRecordFactory.php
tests/Feature/Files/FileRecordTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-059 — Add FileStatus Enum

**Area:** Backend / Files  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-059-add-file-status-enum`  
**Base branch:** `develop`  
**Depends on:** CONV-058

### Goal

Добавить enum `FileStatus` и подключить его к `FileRecord`.

### TDD step

Unit/model test:

```php
use App\Enums\FileStatus;
use App\Models\FileRecord;

it('casts file status to enum', function () {
    $file = FileRecord::factory()->create([
        'status' => FileStatus::Analyzed,
    ]);

    expect($file->fresh()->status)->toBe(FileStatus::Analyzed);
});
```

Тест должен упасть до enum/cast.

### Implementation

Создать enum:

```php
namespace App\Enums;

enum FileStatus: string
{
    case Uploaded = 'uploaded';
    case Analyzed = 'analyzed';
    case Failed = 'failed';
    case Expired = 'expired';
    case Deleted = 'deleted';
}
```

В `FileRecord`:

```php
protected $casts = [
    'metadata_json' => 'array',
    'status' => FileStatus::class,
    'expires_at' => 'datetime',
];
```

Обновить factory default:

```php
'status' => FileStatus::Uploaded,
```

### Acceptance criteria

- `FileStatus` enum exists.
- `FileRecord.status` casts to enum.
- Factory uses enum-safe default.
- Existing FileRecord tests pass.

### Definition of Done

- Тест написан первым.
- Enum создан.
- Model cast добавлен.
- Tests pass.
- Коммит: `CONV-059: Add FileStatus enum`

### Files likely touched

```txt
app/Enums/FileStatus.php
app/Models/FileRecord.php
database/factories/FileRecordFactory.php
tests/Feature/Files/FileRecordTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-060 — Test File Format Detection

**Area:** Backend / Files / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-060-test-file-format-detection`  
**Base branch:** `develop`  
**Depends on:** CONV-059

### Goal

Написать падающие тесты для определения формата файла.

### TDD step

Unit tests:

```php
use App\Support\Files\FileFormatDetector;
use Illuminate\Http\UploadedFile;

it('detects png format', function () {
    $file = UploadedFile::fake()->image('test.png');

    expect(app(FileFormatDetector::class)->detect($file))->toBe('png');
});

it('detects jpg format', function () {
    $file = UploadedFile::fake()->image('test.jpg');

    expect(app(FileFormatDetector::class)->detect($file))->toBe('jpg');
});

it('normalizes jpeg format to jpg', function () {
    $file = UploadedFile::fake()->image('test.jpeg');

    expect(app(FileFormatDetector::class)->detect($file))->toBe('jpg');
});

it('rejects unsupported format', function () {
    $file = UploadedFile::fake()->create('test.txt', 10, 'text/plain');

    app(FileFormatDetector::class)->detect($file);
})->throws(UnsupportedFileFormatException::class);
```

Добавить WEBP/PDF tests, если fake helpers позволяют. Если нет, использовать `UploadedFile::fake()->create()` с MIME.

Тесты должны упасть до CONV-061.

### Implementation

Только добавить тесты.

### Acceptance criteria

- Тесты покрывают PNG.
- Тесты покрывают JPG.
- Тесты покрывают JPEG normalization.
- Тесты покрывают WEBP.
- Тесты покрывают PDF.
- Тесты покрывают unsupported file.
- Тесты ожидаемо падают до реализации.

### Definition of Done

- Тесты добавлены.
- Тесты падают по отсутствующему detector/exception.
- Коммит: `CONV-060: Test file format detection`

### Files likely touched

```txt
tests/Unit/Files/FileFormatDetectorTest.php
```

После этого сделай MR в `develop`. Merge допустим с падающими тестами только если команда работает в строгом TDD-потоке. Если pipeline требует green build, выполнить CONV-061 в том же MR нельзя — но тогда нарушается атомарность. Рекомендация: отдельный MR с failing test только если процесс это допускает.

---

## CONV-061 — Implement FileFormatDetector

**Area:** Backend / Files  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-061-implement-file-format-detector`  
**Base branch:** `develop`  
**Depends on:** CONV-060

### Goal

Реализовать `FileFormatDetector`, который определяет и нормализует формат uploaded file.

### TDD step

Использовать падающие тесты из CONV-060.

### Implementation

Создать exception:

```txt
app/Exceptions/Files/UnsupportedFileFormatException.php
```

Создать detector:

```txt
app/Support/Files/FileFormatDetector.php
```

Пример:

```php
final class FileFormatDetector
{
    private const MIME_TO_FORMAT = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    private const EXTENSION_TO_FORMAT = [
        'png' => 'png',
        'jpg' => 'jpg',
        'jpeg' => 'jpg',
        'webp' => 'webp',
        'pdf' => 'pdf',
    ];

    public function detect(UploadedFile $file): string
    {
        $mime = $file->getMimeType();

        if ($mime !== null && isset(self::MIME_TO_FORMAT[$mime])) {
            return self::MIME_TO_FORMAT[$mime];
        }

        $extension = strtolower($file->getClientOriginalExtension());

        if (isset(self::EXTENSION_TO_FORMAT[$extension])) {
            return self::EXTENSION_TO_FORMAT[$extension];
        }

        throw UnsupportedFileFormatException::forFile($file->getClientOriginalName(), $mime);
    }
}
```

Do not use converter registry here. Detector only detects file type.

### Acceptance criteria

- PNG detected.
- JPG detected.
- JPEG normalized to JPG.
- WEBP detected.
- PDF detected.
- Unsupported file throws explicit exception.
- Tests from CONV-060 pass.

### Definition of Done

- Detector implemented.
- Exception implemented.
- Tests pass.
- `composer test` passes.
- `composer lint` passes.
- Коммит: `CONV-061: Implement FileFormatDetector`

### Files likely touched

```txt
app/Support/Files/FileFormatDetector.php
app/Exceptions/Files/UnsupportedFileFormatException.php
tests/Unit/Files/FileFormatDetectorTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-062 — Test Image Metadata Extraction

**Area:** Backend / Files / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-062-test-image-metadata-extraction`  
**Base branch:** `develop`  
**Depends on:** CONV-061

### Goal

Написать падающие тесты для извлечения metadata из изображений.

### TDD step

Unit tests:

```php
use App\Support\Files\ImageMetadataExtractor;
use Illuminate\Http\UploadedFile;

it('extracts png dimensions', function () {
    $file = UploadedFile::fake()->image('image.png', 1200, 800);

    $metadata = app(ImageMetadataExtractor::class)->extract($file, 'png');

    expect($metadata['width'])->toBe(1200);
    expect($metadata['height'])->toBe(800);
});

it('extracts jpg dimensions', function () {
    $file = UploadedFile::fake()->image('image.jpg', 640, 480);

    $metadata = app(ImageMetadataExtractor::class)->extract($file, 'jpg');

    expect($metadata['width'])->toBe(640);
    expect($metadata['height'])->toBe(480);
    expect($metadata['has_transparency'])->toBeFalse();
});
```

PDF behavior test:

```php
it('returns empty metadata for pdf in phase 05', function () {
    $file = UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf');

    $metadata = app(ImageMetadataExtractor::class)->extract($file, 'pdf');

    expect($metadata)->toBeArray();
});
```

Тесты должны упасть до CONV-063.

### Implementation

Только добавить тесты.

### Acceptance criteria

- Тесты покрывают PNG dimensions.
- Тесты покрывают JPG dimensions.
- Тесты фиксируют `has_transparency = false` для JPG.
- Тесты фиксируют безопасное поведение для PDF.
- Тесты ожидаемо падают до реализации.

### Definition of Done

- Тесты добавлены.
- Тесты падают до implementation.
- Коммит: `CONV-062: Test image metadata extraction`

### Files likely touched

```txt
tests/Unit/Files/ImageMetadataExtractorTest.php
```

После этого сделай MR в `develop` только если процесс допускает failing-test MR. Иначе выполнить CONV-063 сразу следующим MR в локальном TDD-цикле.

---

## CONV-063 — Implement ImageMetadataExtractor

**Area:** Backend / Files  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-063-implement-image-metadata-extractor`  
**Base branch:** `develop`  
**Depends on:** CONV-062

### Goal

Реализовать извлечение metadata для MVP image formats.

### TDD step

Использовать падающие тесты из CONV-062.

### Implementation

Создать:

```txt
app/Support/Files/ImageMetadataExtractor.php
app/Exceptions/Files/FileMetadataException.php
```

Пример:

```php
final class ImageMetadataExtractor
{
    public function extract(UploadedFile $file, string $format): array
    {
        if ($format === 'pdf') {
            return [];
        }

        if (! in_array($format, ['png', 'jpg', 'webp'], true)) {
            return [];
        }

        $size = @getimagesize($file->getRealPath());

        if ($size === false) {
            throw FileMetadataException::cannotReadImage($file->getClientOriginalName());
        }

        return [
            'width' => $size[0],
            'height' => $size[1],
            'has_transparency' => $this->hasTransparency($file, $format),
            'orientation' => null,
        ];
    }

    private function hasTransparency(UploadedFile $file, string $format): bool
    {
        if ($format === 'jpg') {
            return false;
        }

        // MVP-safe approximation. Deep alpha scan can be added later.
        return $format === 'png' || $format === 'webp';
    }
}
```

Жёсткое замечание: `has_transparency = true` для всех PNG — это approximation. В metadata можно назвать это `supports_transparency`, если не делаем alpha scan. Лучше честно:

```txt
supports_transparency
```

Но если UI уже ждёт `has_transparency`, оставить `has_transparency` и позже улучшить detector.

### Acceptance criteria

- PNG width/height extracted.
- JPG width/height extracted.
- JPG transparency false.
- PDF returns safe empty metadata.
- Invalid image throws explicit metadata exception.
- Tests pass.

### Definition of Done

- Extractor implemented.
- Exception implemented.
- Tests pass.
- `composer test` passes.
- `composer lint` passes.
- Коммит: `CONV-063: Implement ImageMetadataExtractor`

### Files likely touched

```txt
app/Support/Files/ImageMetadataExtractor.php
app/Exceptions/Files/FileMetadataException.php
tests/Unit/Files/ImageMetadataExtractorTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-064 — Test Uploaded File Storage

**Area:** Backend / Files / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-064-test-uploaded-file-storage`  
**Base branch:** `develop`  
**Depends on:** CONV-063

### Goal

Написать падающий feature test для `StoreUploadedFileAction`.

### TDD step

Feature test:

```php
use App\Actions\Files\StoreUploadedFileAction;
use App\Enums\FileStatus;
use App\Models\FileRecord;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('stores uploaded png file and creates file record', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $upload = UploadedFile::fake()->image('avatar.png', 1200, 800);

    $record = app(StoreUploadedFileAction::class)->handle($user, $upload);

    expect($record)->toBeInstanceOf(FileRecord::class);
    expect($record->user_id)->toBe($user->id);
    expect($record->original_name)->toBe('avatar.png');
    expect($record->extension)->toBe('png');
    expect($record->status)->toBe(FileStatus::Analyzed);
    expect($record->metadata_json['width'])->toBe(1200);
    expect($record->metadata_json['height'])->toBe(800);
    expect($record->checksum)->not->toBeEmpty();
    expect($record->expires_at)->not->toBeNull();

    Storage::disk('local')->assertExists($record->stored_path);
});
```

Тест должен упасть до CONV-065.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест проверяет DB record.
- Тест проверяет physical file exists.
- Тест проверяет metadata.
- Тест проверяет checksum.
- Тест проверяет expires_at.
- Тест падает до implementation.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-064: Test uploaded file storage`

### Files likely touched

```txt
tests/Feature/Files/StoreUploadedFileActionTest.php
```

После этого сделай MR в `develop` только если процесс допускает failing-test MR. Иначе выполнить CONV-065 сразу следующим MR в локальном TDD-цикле.

---

## CONV-065 — Implement StoreUploadedFileAction

**Area:** Backend / Files / Storage  
**Type:** Action  
**Priority:** P0  
**Branch:** `feature/CONV-065-implement-store-uploaded-file-action`  
**Base branch:** `develop`  
**Depends on:** CONV-064

### Goal

Реализовать `StoreUploadedFileAction`, который сохраняет uploaded file и создаёт `FileRecord`.

### TDD step

Использовать падающий тест из CONV-064.

### Implementation

Создать:

```txt
app/Actions/Files/StoreUploadedFileAction.php
app/Exceptions/Files/FileStorageException.php
```

Action:

```php
final class StoreUploadedFileAction
{
    public function __construct(
        private readonly FileFormatDetector $formatDetector,
        private readonly ImageMetadataExtractor $metadataExtractor,
    ) {}

    public function handle(User $user, UploadedFile $file): FileRecord
    {
        $format = $this->formatDetector->detect($file);
        $metadata = $this->metadataExtractor->extract($file, $format);

        $path = $file->store("uploads/{$user->id}", 'local');

        if ($path === false) {
            throw FileStorageException::cannotStore($file->getClientOriginalName());
        }

        try {
            return FileRecord::query()->create([
                'user_id' => $user->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'mime_type' => $file->getMimeType(),
                'extension' => $format,
                'size_bytes' => $file->getSize() ?? 0,
                'checksum' => hash_file('sha256', Storage::disk('local')->path($path)),
                'metadata_json' => $metadata,
                'status' => FileStatus::Analyzed,
                'expires_at' => now()->addDay(),
            ]);
        } catch (Throwable $e) {
            Storage::disk('local')->delete($path);

            throw FileStorageException::cannotCreateRecord($file->getClientOriginalName(), previous: $e);
        }
    }
}
```

В MVP `expires_at = now()->addDay()`. Plan-based retention будет позже.

### Acceptance criteria

- Uploaded file is stored.
- FileRecord is created.
- user_id is set.
- extension is normalized.
- metadata_json is saved.
- checksum is calculated.
- status is `analyzed`.
- expires_at is set.
- Physical file is deleted if DB record creation fails.
- Tests pass.

### Definition of Done

- Action implemented.
- Exceptions implemented.
- Tests pass.
- `composer test` passes.
- `composer lint` passes.
- Коммит: `CONV-065: Implement StoreUploadedFileAction`

### Files likely touched

```txt
app/Actions/Files/StoreUploadedFileAction.php
app/Exceptions/Files/FileStorageException.php
tests/Feature/Files/StoreUploadedFileActionTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-066 — Add Uploaded File Validation Rules

**Area:** Backend / Files / Validation  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-066-add-uploaded-file-validation-rules`  
**Base branch:** `develop`  
**Depends on:** CONV-065

### Goal

Добавить reusable validation rules для uploaded files.

### TDD step

Unit test:

```php
use App\Support\Files\UploadedFileRules;

it('provides upload validation rules for mvp formats', function () {
    $rules = UploadedFileRules::rules();

    expect($rules)->toContain('file');
    expect(implode('|', $rules))->toContain('mimes:png,jpg,jpeg,webp,pdf');
});
```

Feature test for invalid upload can be added through Validator directly:

```php
it('rejects unsupported uploaded file by validation rules', function () {
    $validator = Validator::make([
        'file' => UploadedFile::fake()->create('note.txt', 1, 'text/plain'),
    ], [
        'file' => UploadedFileRules::rules(),
    ]);

    expect($validator->fails())->toBeTrue();
});
```

### Implementation

Создать:

```txt
app/Support/Files/UploadedFileRules.php
```

Пример:

```php
final class UploadedFileRules
{
    public static function rules(): array
    {
        return [
            'required',
            'file',
            'max:10240',
            'mimes:png,jpg,jpeg,webp,pdf',
        ];
    }
}
```

`max:10240` = 10 MB для MVP foundation. Plan-based file size будет позже.

### Acceptance criteria

- Reusable upload rules exist.
- Allowed formats: png, jpg, jpeg, webp, pdf.
- Basic max file size exists.
- Unsupported file fails validation.
- Tests pass.

### Definition of Done

- Validation rules added.
- Tests pass.
- `composer test` passes.
- `composer lint` passes.
- Коммит: `CONV-066: Add uploaded file validation rules`

### Files likely touched

```txt
app/Support/Files/UploadedFileRules.php
tests/Unit/Files/UploadedFileRulesTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-067 — Add File Ownership Rules

**Area:** Backend / Files / Security  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-067-add-file-ownership-rules`  
**Base branch:** `develop`  
**Depends on:** CONV-066

### Goal

Зафиксировать ownership rules для `FileRecord`: файл всегда принадлежит пользователю, который его загрузил.

### TDD step

Feature tests:

```php
it('stores uploaded file for the provided user', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $other = User::factory()->create();

    $record = app(StoreUploadedFileAction::class)->handle(
        $user,
        UploadedFile::fake()->image('image.png')
    );

    expect($record->user_id)->toBe($user->id);
    expect($record->user_id)->not->toBe($other->id);
});
```

Optional query scope test:

```php
it('can scope files to owner', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $own = FileRecord::factory()->for($user)->create();
    $foreign = FileRecord::factory()->for($other)->create();

    expect(FileRecord::query()->forUser($user)->pluck('id')->all())
        ->toBe([$own->id]);
});
```

### Implementation

Добавить scope в `FileRecord`:

```php
public function scopeForUser(Builder $query, User $user): Builder
{
    return $query->where('user_id', $user->id);
}
```

Не добавлять download authorization пока — download route будет позже.

### Acceptance criteria

- Store action always sets provided user_id.
- `FileRecord::forUser($user)` scope exists.
- Scope returns only owner files.
- Tests pass.

### Definition of Done

- Ownership tests added.
- Scope implemented.
- Tests pass.
- `composer test` passes.
- `composer lint` passes.
- Коммит: `CONV-067: Add file ownership rules`

### Files likely touched

```txt
app/Models/FileRecord.php
tests/Feature/Files/FileOwnershipTest.php
tests/Feature/Files/StoreUploadedFileActionTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-068 — Add File Expiration Defaults

**Area:** Backend / Files / Retention  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-068-add-file-expiration-defaults`  
**Base branch:** `develop`  
**Depends on:** CONV-067

### Goal

Вынести default file expiration из action в отдельный helper/service, чтобы позже заменить его на plan-based retention.

### TDD step

Unit test:

```php
use App\Support\Files\FileExpirationPolicy;

it('returns default uploaded file expiration', function () {
    $user = User::factory()->create();

    $expiresAt = app(FileExpirationPolicy::class)->forUploadedFile($user);

    expect($expiresAt)->toBeInstanceOf(\Carbon\CarbonInterface::class);
    expect($expiresAt->greaterThan(now()))->toBeTrue();
});
```

Store action test:

```php
it('uses file expiration policy when storing uploaded file', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $record = app(StoreUploadedFileAction::class)->handle(
        $user,
        UploadedFile::fake()->image('image.png')
    );

    expect($record->expires_at)->not->toBeNull();
});
```

### Implementation

Создать:

```txt
app/Support/Files/FileExpirationPolicy.php
```

Пример:

```php
final class FileExpirationPolicy
{
    public function forUploadedFile(User $user): CarbonInterface
    {
        return now()->addDay();
    }

    public function forResultFile(User $user): CarbonInterface
    {
        return now()->addDay();
    }
}
```

Обновить `StoreUploadedFileAction`, чтобы использовать policy вместо hardcoded `now()->addDay()`.

### Acceptance criteria

- `FileExpirationPolicy` exists.
- Uploaded file expiration comes from policy.
- Default expiration is future timestamp.
- Store action uses policy.
- Tests pass.

### Definition of Done

- Policy added.
- Store action updated.
- Tests pass.
- `composer test` passes.
- `composer lint` passes.
- Коммит: `CONV-068: Add file expiration defaults`

### Files likely touched

```txt
app/Support/Files/FileExpirationPolicy.php
app/Actions/Files/StoreUploadedFileAction.php
tests/Unit/Files/FileExpirationPolicyTest.php
tests/Feature/Files/StoreUploadedFileActionTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-069 — Add Stored File Cleanup Safety Test

**Area:** Backend / Files / Tests  
**Type:** Test  
**Priority:** P1  
**Branch:** `feature/CONV-069-add-stored-file-cleanup-safety-test`  
**Base branch:** `develop`  
**Depends on:** CONV-068

### Goal

Зафиксировать safety behavior: если DB record creation падает после физического сохранения файла, физический файл удаляется.

### TDD step

Feature test.

Один из вариантов — замокать репозиторий не получится, потому что сейчас action напрямую использует model. Поэтому лучше временно проверить через injected factory/repository только если архитектура уже позволяет.

Если прямой тест неудобен, добавить focused test через fake subclass/service binding.

Пример через partial mock нежелателен, но допустим для safety behavior:

```php
it('removes physical file when file record creation fails', function () {
    Storage::fake('local');

    // Implementation-specific test.
    // If current action cannot be tested cleanly, refactor StoreUploadedFileAction
    // to depend on FileRecordCreator and test failure there.
});
```

Если тест слишком хрупкий, задача может вместо этого создать `FileRecordCreator` boundary.

### Implementation

Рекомендуемая минимальная доработка:

Создать:

```txt
app/Support/Files/FileRecordCreator.php
```

Он инкапсулирует:

```php
FileRecord::query()->create($attributes);
```

`StoreUploadedFileAction` зависит от `FileRecordCreator`.

В тесте замокать `FileRecordCreator`, чтобы он бросал exception после physical store, и проверить `Storage::disk('local')->assertMissing($path)`.

### Acceptance criteria

- Failure after physical storage deletes stored file.
- Store action does not leave orphan file on DB failure.
- Normal successful storage still works.
- Tests pass.

### Definition of Done

- Safety test added.
- Minimal boundary added only if needed.
- Store action still simple.
- Tests pass.
- `composer test` passes.
- `composer lint` passes.
- Коммит: `CONV-069: Add stored file cleanup safety test`

### Files likely touched

```txt
app/Actions/Files/StoreUploadedFileAction.php
app/Support/Files/FileRecordCreator.php
tests/Feature/Files/StoreUploadedFileActionTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-070 — Add File Storage Smoke Tests

**Area:** Backend / Files / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-070-add-file-storage-smoke-tests`  
**Base branch:** `develop`  
**Depends on:** CONV-069

### Goal

Добавить финальные smoke tests для Phase 05, доказывающие, что MVP-supported file types могут пройти storage pipeline.

### TDD step

Feature tests:

```php
it('stores mvp supported image uploads', function (string $filename, string $expectedFormat) {
    Storage::fake('local');

    $user = User::factory()->create();
    $upload = UploadedFile::fake()->image($filename, 800, 600);

    $record = app(StoreUploadedFileAction::class)->handle($user, $upload);

    expect($record->extension)->toBe($expectedFormat);
    Storage::disk('local')->assertExists($record->stored_path);
})->with([
    ['image.png', 'png'],
    ['image.jpg', 'jpg'],
    ['image.jpeg', 'jpg'],
]);
```

PDF smoke test:

```php
it('stores pdf upload with empty metadata', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $upload = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $record = app(StoreUploadedFileAction::class)->handle($user, $upload);

    expect($record->extension)->toBe('pdf');
    expect($record->metadata_json)->toBeArray();
    Storage::disk('local')->assertExists($record->stored_path);
});
```

### Implementation

Добавить smoke tests. Исправить мелкие edge cases, если выявятся.

Не добавлять новые форматы.

### Acceptance criteria

- PNG upload passes storage pipeline.
- JPG upload passes storage pipeline.
- JPEG upload normalizes to JPG.
- PDF upload passes storage pipeline.
- Unsupported file still rejected.
- Full Phase 05 test suite passes.

### Definition of Done

- Smoke tests added.
- Edge cases fixed minimally.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.
- Коммит: `CONV-070: Add file storage smoke tests`

### Files likely touched

```txt
tests/Feature/Files/FileStorageSmokeTest.php
app/Actions/Files/StoreUploadedFileAction.php
app/Support/Files/FileFormatDetector.php
app/Support/Files/ImageMetadataExtractor.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 05 Completion Criteria

Phase 05 завершена, когда:

```txt
- CONV-058–CONV-070 выполнены;
- files table exists;
- FileRecord model exists;
- FileRecordFactory exists;
- FileStatus enum exists;
- metadata_json casts to array;
- status casts to FileStatus;
- FileFormatDetector detects PNG/JPG/JPEG/WEBP/PDF;
- JPEG normalizes to JPG;
- unsupported file throws explicit exception;
- ImageMetadataExtractor extracts image dimensions;
- StoreUploadedFileAction stores physical file;
- StoreUploadedFileAction creates FileRecord;
- checksum is stored;
- expires_at is stored;
- file ownership is enforced through user_id;
- FileRecord::forUser scope exists;
- physical file cleanup on record creation failure is covered;
- storage smoke tests pass;
- no Livewire upload UI was added;
- no conversion jobs were added;
- no billing/credits were added;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 05

Без отдельной задачи нельзя:

```txt
- создавать DashboardConverter Livewire component;
- добавлять upload UI;
- добавлять drag/drop form в dashboard;
- создавать target format step;
- создавать DynamicOptionsForm;
- создавать ConversionJob;
- выполнять реальную конвертацию;
- добавлять queues;
- добавлять result download route;
- устанавливать Imagick/Intervention для conversion;
- устанавливать Cashier;
- создавать CreditLedger;
- добавлять FeatureAccessService;
- добавлять API endpoint;
- добавлять S3 direct upload;
- добавлять chunk upload;
- добавлять virus scanning;
- добавлять OCR metadata;
- добавлять public /formats page.
```

---

# 12. Recommended Execution Order

```txt
CONV-058 Create FileRecord Model And Migration
CONV-059 Add FileStatus Enum
CONV-060 Test File Format Detection
CONV-061 Implement FileFormatDetector
CONV-062 Test Image Metadata Extraction
CONV-063 Implement ImageMetadataExtractor
CONV-064 Test Uploaded File Storage
CONV-065 Implement StoreUploadedFileAction
CONV-066 Add Uploaded File Validation Rules
CONV-067 Add File Ownership Rules
CONV-068 Add File Expiration Defaults
CONV-069 Add Stored File Cleanup Safety Test
CONV-070 Add File Storage Smoke Tests
```

---

# 13. Release

После завершения Phase 05:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh

git checkout -b release/v0.1.5-phase05-file-storage-metadata
git push -u origin release/v0.1.5-phase05-file-storage-metadata
```

После этого сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.5-phase05-file-storage-metadata -m "File Converter Phase 05 File Storage Metadata"
git push origin v0.1.5-phase05-file-storage-metadata
```
