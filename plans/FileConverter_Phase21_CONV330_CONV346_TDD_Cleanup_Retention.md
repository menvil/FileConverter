# File Converter — Phase 21 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 21 — Cleanup & Retention**  
Диапазон задач: **CONV-330 → CONV-346**  
Основа нумерации: Phase 20 завершилась на `CONV-329`, поэтому Phase 21 начинается с `CONV-330`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 21 соответствует блоку:

```txt
Phase 21 — Cleanup & Retention
```

Правильный диапазон Phase 21:

```txt
CONV-330 — Create FileRetentionPolicy Service
CONV-331 — Test Upload File Expiration By Plan
CONV-332 — Apply Expiration To Uploaded Files
CONV-333 — Test Result File Expiration By Plan
CONV-334 — Apply Expiration To Conversion Result Files
CONV-335 — Add Expired File Status Handling
CONV-336 — Create CleanupExpiredFilesJob Skeleton
CONV-337 — Test Cleanup Deletes Expired Physical Files
CONV-338 — Implement Expired Physical File Deletion
CONV-339 — Test Cleanup Marks File Records Expired
CONV-340 — Implement Expired File Record Marking
CONV-341 — Add Manual Cleanup Command
CONV-342 — Schedule Expired Files Cleanup
CONV-343 — Block Expired Web Downloads
CONV-344 — Block Expired API Downloads
CONV-345 — Show Retention And Expiration Info In UI
CONV-346 — Add Cleanup And Retention Final Smoke Tests
```

Phase 21 не добавляет новые форматы конвертации.  
Phase 21 не меняет pricing model.  
Phase 21 не делает storage billing.  
Phase 21 делает одно: **файлы должны иметь срок жизни, истекшие физические файлы должны удаляться, а скачивание истекших результатов должно быть заблокировано**.

Ключевое правило:

```txt
История conversion jobs остаётся в базе, но физические файлы после expires_at удаляются.
```

---

# 2. Цель Phase 21

Phase 21 добавляет retention lifecycle для uploaded files и conversion result files.

После Phase 21 система должна уметь:

```txt
- назначать expires_at загруженным файлам по тарифу пользователя;
- назначать expires_at result files по тарифу пользователя;
- отличать active/expired/deleted file records;
- удалять физические expired files из storage;
- помечать file records как expired;
- запускать cleanup вручную через artisan command;
- запускать cleanup автоматически через scheduler;
- запрещать web download expired result;
- запрещать API download expired result;
- показывать пользователю срок хранения файла;
- сохранять историю conversion jobs даже после удаления физического файла.
```

Retention — это не косметика. Без него storage быстро превратится в мусорную яму, особенно когда появятся video/PDF/batch conversions.

---

# 3. Scope Phase 21

## Входит

```txt
- FileRetentionPolicy service;
- expiration calculation based on FeatureAccessService;
- expires_at assignment for uploaded files;
- expires_at assignment for result files;
- expired file status handling;
- CleanupExpiredFilesJob;
- physical file deletion through Storage facade;
- file record status update;
- manual artisan cleanup command;
- scheduler registration;
- web download expired guard;
- API download expired guard;
- UI expiration labels;
- tests for retention and cleanup behavior.
```

## Не входит

```txt
- paid storage add-ons;
- user-selectable retention period;
- permanent archive mode;
- S3 lifecycle policies;
- background queue dashboards;
- admin storage analytics;
- file restore from backup;
- trash/recycle bin UI;
- GDPR export/delete account flow;
- team workspace retention policy;
- enterprise custom retention;
- webhooks for file.expired;
- billing based on GB stored.
```

S3 lifecycle policies могут появиться позже как infrastructure optimization.  
В Phase 21 source of truth — приложение и `files.expires_at`.

---

# 4. Critical Decisions

## 4.1. Database history stays, physical files are deleted

Когда файл истёк:

```txt
- physical storage object удаляется;
- files.status становится expired;
- files.stored_path остаётся как historical reference или очищается по policy;
- conversion_jobs остаются в истории;
- result_file_id может продолжать указывать на expired FileRecord;
- download становится невозможным.
```

Нельзя удалять `conversion_jobs`, потому что пользователю нужна история.

## 4.2. Cleanup must be idempotent

Cleanup job может запускаться много раз.

Правильное поведение:

```txt
expired file already deleted physically → no exception
expired record already status=expired → no duplicate side effects
missing file in storage → mark expired and continue
```

Неправильно:

```txt
cleanup падает из-за одного отсутствующего файла
cleanup повторно пытается удалить уже expired file и ломается
cleanup удаляет active file из-за неверного query
```

## 4.3. Never use raw unlink

Файлы удаляются только через Laravel storage abstraction:

```php
Storage::disk($file->disk)->delete($file->stored_path);
```

Если в `files` ещё нет `disk`, использовать configured default disk.  
Но лучше добавить поле `disk` до production, если его нет.

## 4.4. Retention comes from FeatureAccessService

Нельзя hardcode-ить retention в upload action:

```php
now()->addDay();
```

Правильно:

```php
$retentionPolicy->expiresAtFor($user, FileRetentionSubject::Upload)
```

В MVP можно использовать один limit:

```txt
FeatureAccessService::limit($user, 'retention_days')
```

## 4.5. Result files inherit user retention, not source retention

У uploaded file и result file может быть одинаковый retention, но calculation должен выполняться отдельно.

Причина: позже result retention может отличаться от source retention.

```txt
source uploaded at 10:00 expires tomorrow 10:00
result created at 10:10 expires tomorrow 10:10
```

## 4.6. Expired downloads must fail cleanly

Для web:

```txt
This file has expired and was deleted. Upload the source file again to convert it.
```

Для API:

```json
{
  "error": {
    "code": "result_expired",
    "message": "This conversion result has expired and can no longer be downloaded.",
    "details": {
      "expired_at": "2026-06-01T10:00:00Z"
    }
  }
}
```

## 4.7. Do not confuse expired with failed

`failed conversion` означает conversion engine error.  
`expired result` означает result existed but retention window ended.

Нельзя менять `conversion_jobs.status` на failed из-за expiration.

Рекомендуемое поведение:

```txt
conversion_jobs.status remains completed
result file status becomes expired
UI shows completed but expired/download unavailable
```

---

# 5. Architecture Rules

## 5.1. File retention belongs to application service

Рекомендуемое место:

```txt
app/Services/Files/FileRetentionPolicy.php
```

или:

```txt
app/Actions/Files/*
```

Но не в Livewire component и не в controller.

## 5.2. Cleanup job does not calculate business limits itself

Job не должен знать тарифы.  
Job работает только с уже рассчитанными `expires_at`.

Правильно:

```txt
StoreUploadedFileAction calculates expires_at
ProcessConversionJob calculates result expires_at
CleanupExpiredFilesJob deletes records where expires_at <= now()
```

## 5.3. Download guards must be shared

Web download route и API download endpoint должны использовать одинаковую проверку:

```txt
CanDownloadConversionResultAction
```

или минимально общий метод/service.

Нельзя иметь разные правила:

```txt
web blocks expired
API accidentally allows expired
```

## 5.4. No hard deletes for FileRecord in Phase 21

Физический файл удаляется.  
Database record остаётся.

Нельзя делать:

```php
$file->delete();
```

если это удаляет историю.

## 5.5. Cleanup must be testable with fake storage

Тесты должны использовать:

```php
Storage::fake('local');
```

и не трогать реальные файлы.

---

# 6. GitFlow для Phase 21

## Base branch

Все задачи Phase 21 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-330-create-file-retention-policy-service
feature/CONV-336-create-cleanup-expired-files-job-skeleton
feature/CONV-343-block-expired-web-downloads
```

## Commit format

```txt
CONV-330: Create FileRetentionPolicy service
CONV-336: Create CleanupExpiredFilesJob skeleton
CONV-343: Block expired web downloads
```

## Release branch

После выполнения `CONV-330`–`CONV-346`:

```txt
release/v0.1.21-phase21-cleanup-retention
```

## Tag

После merge release branch в `main`:

```txt
v0.1.21-phase21-cleanup-retention
```

---

# 7. TDD Rules for Phase 21

## Для retention policy

Test-first:

```txt
- free user upload expiration uses retention_days limit;
- pro user upload expiration uses retention_days limit;
- result file expiration is calculated from result creation time;
- null/invalid retention limit is rejected or falls back explicitly.
```

## Для cleanup job

Test-first:

```txt
- expired physical file is deleted;
- non-expired physical file is not deleted;
- missing physical file does not crash cleanup;
- expired file record becomes status=expired;
- active file record remains unchanged.
```

## Для downloads

Test-first:

```txt
- completed non-expired result can be downloaded;
- completed expired result cannot be downloaded via web;
- completed expired result cannot be downloaded via API;
- API returns standardized result_expired error.
```

## Для UI

Тестировать минимально:

```txt
- dashboard/recent conversions shows expiration label;
- expired completed result shows disabled download action.
```

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Files / Retention / Cleanup / Downloads / Tests
Type: Test / Feature / Job / Command / UI / API
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
- npm run build проходит, если затронут frontend
- Нет функциональности вне scope задачи
- Коммит содержит ID задачи

Files likely touched:
- path/to/file
```

---

# 9. Phase 21 Atomic Tasks

---

## CONV-330 — Create FileRetentionPolicy Service

**Area:** Files / Retention  
**Type:** Service  
**Priority:** P0  
**Branch:** `feature/CONV-330-create-file-retention-policy-service`  
**Base branch:** `develop`  
**Depends on:** CONV-329

### Goal

Создать сервис, который рассчитывает `expires_at` для uploaded files и conversion result files на основании тарифных лимитов пользователя.

### TDD step

Unit test:

```php
it('calculates expiration date from user retention limit', function () {
    Carbon::setTestNow('2026-06-01 10:00:00');

    $user = User::factory()->create([
        'plan' => Plan::Free,
    ]);

    $featureAccess = Mockery::mock(FeatureAccessService::class);
    $featureAccess->shouldReceive('limit')
        ->with($user, 'retention_days')
        ->andReturn(1);

    $policy = new FileRetentionPolicy($featureAccess);

    expect($policy->expiresAtFor($user))->toEqual(now()->addDay());
});
```

Тест должен упасть до создания сервиса.

### Implementation

Создать:

```txt
app/Services/Files/FileRetentionPolicy.php
```

Пример API:

```php
final class FileRetentionPolicy
{
    public function __construct(
        private readonly FeatureAccessService $features,
    ) {}

    public function expiresAtFor(User $user, ?CarbonInterface $from = null): CarbonInterface
    {
        $days = (int) $this->features->limit($user, 'retention_days');

        if ($days <= 0) {
            throw new InvalidRetentionPolicyException('Retention days must be positive.');
        }

        return ($from ?? now())->addDays($days);
    }
}
```

Если `InvalidRetentionPolicyException` кажется избыточным, можно использовать domain exception:

```txt
app/Exceptions/Files/InvalidRetentionPolicyException.php
```

### Acceptance criteria

- `FileRetentionPolicy` существует.
- Expiration считается через `FeatureAccessService`.
- Нет hardcode тарифов в сервисе.
- Нулевой/отрицательный retention rejected.
- Unit tests проходят.

### Definition of Done

- Тест написан первым.
- Сервис создан.
- Domain exception добавлен, если нужен.
- Тесты проходят.
- Коммит: `CONV-330: Create FileRetentionPolicy service`

### Files likely touched

```txt
app/Services/Files/FileRetentionPolicy.php
app/Exceptions/Files/InvalidRetentionPolicyException.php
tests/Unit/Services/FileRetentionPolicyTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-331 — Test Upload File Expiration By Plan

**Area:** Files / Upload / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-331-test-upload-file-expiration-by-plan`  
**Base branch:** `develop`  
**Depends on:** CONV-330

### Goal

Написать падающий тест: uploaded file получает `expires_at` согласно тарифному retention limit пользователя.

### TDD step

Feature test:

```php
it('assigns expiration date to uploaded file based on user plan', function () {
    Carbon::setTestNow('2026-06-01 10:00:00');

    Storage::fake('local');

    $user = User::factory()->create([
        'plan' => Plan::Free,
    ]);

    $file = UploadedFile::fake()->image('sample.png', 100, 100);

    $record = app(StoreUploadedFileAction::class)->handle($user, $file);

    expect($record->expires_at)->not->toBeNull();
    expect($record->expires_at->toDateTimeString())->toBe('2026-06-02 10:00:00');
});
```

Тест должен упасть до CONV-332.

### Implementation

Только добавить тест.  
Не менять `StoreUploadedFileAction` в этой задаче.

### Acceptance criteria

- Тест существует.
- Тест проверяет `expires_at` uploaded file.
- Тест использует `Storage::fake()`.
- Тест фиксирует текущее время через `Carbon::setTestNow()`.
- Тест падает до реализации.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-331: Test upload file expiration by plan`

### Files likely touched

```txt
tests/Feature/Actions/StoreUploadedFileActionTest.php
```

После этого сделай MR в `develop`. Merge разрешён, если общий suite ожидаемо падает только на новом тесте до реализации или task идет парой с CONV-332.

---

## CONV-332 — Apply Expiration To Uploaded Files

**Area:** Files / Upload  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-332-apply-expiration-to-uploaded-files`  
**Base branch:** `develop`  
**Depends on:** CONV-331

### Goal

Назначать `expires_at` uploaded files при сохранении файла.

### TDD step

Использовать падающий тест из CONV-331.

### Implementation

В `StoreUploadedFileAction` внедрить `FileRetentionPolicy`:

```php
public function __construct(
    private readonly FileRetentionPolicy $retentionPolicy,
) {}
```

При создании `FileRecord`:

```php
'expires_at' => $this->retentionPolicy->expiresAtFor($user),
```

Если `expires_at` ещё не cast в модели, добавить:

```php
protected $casts = [
    'metadata_json' => 'array',
    'expires_at' => 'datetime',
];
```

### Acceptance criteria

- Uploaded file получает `expires_at`.
- Expiration рассчитывается через `FileRetentionPolicy`.
- Модель cast-ит `expires_at` как datetime.
- Existing upload tests проходят.
- Тест CONV-331 проходит.

### Definition of Done

- `StoreUploadedFileAction` обновлён.
- Нет hardcode retention в action.
- Тесты проходят.
- Коммит: `CONV-332: Apply expiration to uploaded files`

### Files likely touched

```txt
app/Actions/Files/StoreUploadedFileAction.php
app/Models/FileRecord.php
tests/Feature/Actions/StoreUploadedFileActionTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-333 — Test Result File Expiration By Plan

**Area:** Conversion / Results / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-333-test-result-file-expiration-by-plan`  
**Base branch:** `develop`  
**Depends on:** CONV-332

### Goal

Написать падающий тест: result file получает `expires_at` на основании retention policy в момент успешной конвертации.

### TDD step

Feature test с fake driver:

```php
it('assigns expiration date to conversion result file based on user plan', function () {
    Carbon::setTestNow('2026-06-01 10:00:00');

    Storage::fake('local');

    $user = User::factory()->create([
        'plan' => Plan::Free,
    ]);

    $source = FileRecord::factory()->for($user)->create([
        'extension' => 'png',
        'expires_at' => now()->addDay(),
    ]);

    $job = ConversionJob::factory()->for($user)->create([
        'source_file_id' => $source->id,
        'source_format' => 'png',
        'target_format' => 'jpg',
        'status' => ConversionStatus::Queued,
    ]);

    app(ProcessConversionJob::class)->handle($job->id);

    $result = $job->fresh()->resultFile;

    expect($result)->not->toBeNull();
    expect($result->expires_at->toDateTimeString())->toBe('2026-06-02 10:00:00');
});
```

Adapt to actual queue job signature.

Тест должен упасть до CONV-334.

### Implementation

Только добавить тест.  
Если real driver makes the test heavy, use fake driver binding.

### Acceptance criteria

- Тест существует.
- Тест проверяет result file `expires_at`.
- Тест использует fake storage.
- Тест не зависит от реальной тяжёлой конвертации.
- Тест падает до реализации.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-333: Test result file expiration by plan`

### Files likely touched

```txt
tests/Feature/Jobs/ProcessConversionJobTest.php
```

После этого сделай MR в `develop`. Merge разрешён, если task идет парой с CONV-334 или новый тест ожидаемо падает до реализации.

---

## CONV-334 — Apply Expiration To Conversion Result Files

**Area:** Conversion / Results  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-334-apply-expiration-to-conversion-result-files`  
**Base branch:** `develop`  
**Depends on:** CONV-333

### Goal

Назначать `expires_at` result file при успешной конвертации.

### TDD step

Использовать падающий тест из CONV-333.

### Implementation

В месте, где `ProcessConversionJob` создаёт result `FileRecord`, внедрить `FileRetentionPolicy`.

Пример:

```php
$resultFile = FileRecord::create([
    'user_id' => $job->user_id,
    'original_name' => $resultName,
    'stored_path' => $result->path,
    'mime_type' => $result->mimeType,
    'extension' => $result->extension,
    'size_bytes' => $result->sizeBytes,
    'metadata_json' => $result->metadata,
    'status' => FileStatus::Analyzed,
    'expires_at' => $this->retentionPolicy->expiresAtFor($job->user),
]);
```

Если в проекте result file создаётся через отдельный action, expiration добавлять туда, а не прямо в job.

### Acceptance criteria

- Result file получает `expires_at`.
- Expiration считается от времени создания результата.
- Source file expiration не переиспользуется для result file.
- CONV-333 test passes.
- Existing conversion tests pass.

### Definition of Done

- Result file creation обновлена.
- Нет hardcode retention.
- Тесты проходят.
- Коммит: `CONV-334: Apply expiration to conversion result files`

### Files likely touched

```txt
app/Jobs/ProcessConversionJob.php
app/Actions/Conversions/*
app/Models/FileRecord.php
tests/Feature/Jobs/ProcessConversionJobTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-335 — Add Expired File Status Handling

**Area:** Files / Domain  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-335-add-expired-file-status-handling`  
**Base branch:** `develop`  
**Depends on:** CONV-334

### Goal

Убедиться, что `FileStatus::Expired` существует и корректно используется.

### TDD step

Unit/model test:

```php
it('supports expired file status', function () {
    $file = FileRecord::factory()->create([
        'status' => FileStatus::Expired,
    ]);

    expect($file->fresh()->status)->toBe(FileStatus::Expired);
});
```

Если статус уже существует, тест должен пройти без изменений. Если нет — упасть.

### Implementation

В `FileStatus` enum добавить:

```php
case Expired = 'expired';
```

Добавить helper в `FileRecord`:

```php
public function isExpired(): bool
{
    return $this->status === FileStatus::Expired
        || ($this->expires_at !== null && $this->expires_at->isPast());
}
```

Важно: `isExpired()` может вернуть true по времени, даже если cleanup ещё не успел поставить status.

### Acceptance criteria

- `FileStatus::Expired` существует.
- `FileRecord::isExpired()` работает.
- Expired by status returns true.
- Expired by `expires_at <= now()` returns true.
- Future `expires_at` returns false.
- Tests pass.

### Definition of Done

- Enum/helper добавлены.
- Tests pass.
- Коммит: `CONV-335: Add expired file status handling`

### Files likely touched

```txt
app/Enums/FileStatus.php
app/Models/FileRecord.php
tests/Unit/Models/FileRecordTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-336 — Create CleanupExpiredFilesJob Skeleton

**Area:** Files / Cleanup / Queue  
**Type:** Job  
**Priority:** P0  
**Branch:** `feature/CONV-336-create-cleanup-expired-files-job-skeleton`  
**Base branch:** `develop`  
**Depends on:** CONV-335

### Goal

Создать skeleton job для cleanup expired files.

### TDD step

Unit test:

```php
it('has cleanup expired files job handle method', function () {
    $job = app(CleanupExpiredFilesJob::class);

    expect(method_exists($job, 'handle'))->toBeTrue();
});
```

Тест должен упасть до создания job.

### Implementation

Создать job:

```bash
php artisan make:job CleanupExpiredFilesJob
```

Skeleton:

```php
final class CleanupExpiredFilesJob implements ShouldQueue
{
    public function handle(): void
    {
        throw new LogicException('Not implemented yet.');
    }
}
```

Если cleanup будет запускаться schedule directly, можно сделать job `ShouldQueue` всё равно: это нормально для production.

### Acceptance criteria

- `CleanupExpiredFilesJob` существует.
- Job implements `ShouldQueue`.
- Есть `handle()`.
- Нет cleanup logic в skeleton.
- Test passes.

### Definition of Done

- Тест написан первым.
- Job skeleton создан.
- Тест проходит.
- Коммит: `CONV-336: Create CleanupExpiredFilesJob skeleton`

### Files likely touched

```txt
app/Jobs/CleanupExpiredFilesJob.php
tests/Unit/Jobs/CleanupExpiredFilesJobSkeletonTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-337 — Test Cleanup Deletes Expired Physical Files

**Area:** Files / Cleanup / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-337-test-cleanup-deletes-expired-physical-files`  
**Base branch:** `develop`  
**Depends on:** CONV-336

### Goal

Написать падающий тест: cleanup удаляет физические файлы, у которых `expires_at` уже прошёл.

### TDD step

Feature test:

```php
it('deletes expired physical files from storage', function () {
    Carbon::setTestNow('2026-06-02 10:00:00');

    Storage::fake('local');
    Storage::disk('local')->put('uploads/expired.png', 'fake-content');

    FileRecord::factory()->create([
        'stored_path' => 'uploads/expired.png',
        'status' => FileStatus::Analyzed,
        'expires_at' => now()->subMinute(),
    ]);

    app(CleanupExpiredFilesJob::class)->handle();

    Storage::disk('local')->assertMissing('uploads/expired.png');
});
```

Также добавить non-expired protection test:

```php
it('does not delete non expired physical files', function () {
    Storage::fake('local');
    Storage::disk('local')->put('uploads/active.png', 'fake-content');

    FileRecord::factory()->create([
        'stored_path' => 'uploads/active.png',
        'status' => FileStatus::Analyzed,
        'expires_at' => now()->addDay(),
    ]);

    app(CleanupExpiredFilesJob::class)->handle();

    Storage::disk('local')->assertExists('uploads/active.png');
});
```

Тесты должны упасть до CONV-338.

### Implementation

Только добавить тесты.

### Acceptance criteria

- Expired physical file deletion test exists.
- Non-expired protection test exists.
- Tests use `Storage::fake()`.
- Tests fail before implementation.

### Definition of Done

- Тесты добавлены.
- Тесты ожидаемо падают.
- Коммит: `CONV-337: Test cleanup deletes expired physical files`

### Files likely touched

```txt
tests/Feature/Jobs/CleanupExpiredFilesJobTest.php
```

После этого сделай MR в `develop`. Merge разрешён, если task идет парой с CONV-338 или новый тест ожидаемо падает до реализации.

---

## CONV-338 — Implement Expired Physical File Deletion

**Area:** Files / Cleanup  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-338-implement-expired-physical-file-deletion`  
**Base branch:** `develop`  
**Depends on:** CONV-337

### Goal

Реализовать удаление физических expired files из storage.

### TDD step

Использовать падающие тесты из CONV-337.

### Implementation

В `CleanupExpiredFilesJob::handle()`:

```php
FileRecord::query()
    ->whereNotNull('expires_at')
    ->where('expires_at', '<=', now())
    ->whereNotIn('status', [FileStatus::Expired, FileStatus::Deleted])
    ->chunkById(100, function ($files): void {
        foreach ($files as $file) {
            if ($file->stored_path) {
                Storage::disk($file->disk ?? config('filesystems.default'))
                    ->delete($file->stored_path);
            }
        }
    });
```

Если `disk` поля ещё нет, использовать default disk.  
Не использовать raw filesystem functions.

### Acceptance criteria

- Expired physical files deleted.
- Non-expired files not deleted.
- Missing physical file does not crash cleanup.
- Uses Storage facade.
- Tests from CONV-337 pass.

### Definition of Done

- Physical deletion implemented.
- Tests pass.
- Коммит: `CONV-338: Implement expired physical file deletion`

### Files likely touched

```txt
app/Jobs/CleanupExpiredFilesJob.php
tests/Feature/Jobs/CleanupExpiredFilesJobTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-339 — Test Cleanup Marks File Records Expired

**Area:** Files / Cleanup / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-339-test-cleanup-marks-file-records-expired`  
**Base branch:** `develop`  
**Depends on:** CONV-338

### Goal

Написать падающий тест: cleanup после удаления physical file помечает `FileRecord` как expired.

### TDD step

Feature test:

```php
it('marks expired file records as expired after cleanup', function () {
    Carbon::setTestNow('2026-06-02 10:00:00');

    Storage::fake('local');
    Storage::disk('local')->put('uploads/expired.png', 'fake-content');

    $file = FileRecord::factory()->create([
        'stored_path' => 'uploads/expired.png',
        'status' => FileStatus::Analyzed,
        'expires_at' => now()->subMinute(),
    ]);

    app(CleanupExpiredFilesJob::class)->handle();

    expect($file->fresh()->status)->toBe(FileStatus::Expired);
});
```

Также проверить non-expired record:

```php
it('does not mark active file records as expired', ...);
```

Тест должен упасть до CONV-340.

### Implementation

Только добавить тесты.

### Acceptance criteria

- Тест на status=expired существует.
- Тест на active record protection существует.
- Тесты падают до реализации.

### Definition of Done

- Тесты добавлены.
- Тесты ожидаемо падают.
- Коммит: `CONV-339: Test cleanup marks file records expired`

### Files likely touched

```txt
tests/Feature/Jobs/CleanupExpiredFilesJobTest.php
```

После этого сделай MR в `develop`. Merge разрешён, если task идет парой с CONV-340 или новый тест ожидаемо падает до реализации.

---

## CONV-340 — Implement Expired File Record Marking

**Area:** Files / Cleanup  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-340-implement-expired-file-record-marking`  
**Base branch:** `develop`  
**Depends on:** CONV-339

### Goal

После physical deletion помечать expired file records как `FileStatus::Expired`.

### TDD step

Использовать падающие тесты из CONV-339.

### Implementation

В `CleanupExpiredFilesJob` после delete:

```php
$file->forceFill([
    'status' => FileStatus::Expired,
])->save();
```

Если нужно очистить `stored_path`, не делать это в Phase 21 без отдельного решения.  
Оставленный path полезен для debugging/history.

Job должен быть idempotent:

```txt
file already status expired → skip
file missing in storage → mark expired anyway
```

### Acceptance criteria

- Expired files become `FileStatus::Expired`.
- Active files remain unchanged.
- Missing physical file does not block status update.
- Cleanup can be safely re-run.
- Tests pass.

### Definition of Done

- Status marking implemented.
- Idempotency preserved.
- Tests pass.
- Коммит: `CONV-340: Implement expired file record marking`

### Files likely touched

```txt
app/Jobs/CleanupExpiredFilesJob.php
tests/Feature/Jobs/CleanupExpiredFilesJobTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-341 — Add Manual Cleanup Command

**Area:** Console / Cleanup  
**Type:** Command  
**Priority:** P1  
**Branch:** `feature/CONV-341-add-manual-cleanup-command`  
**Base branch:** `develop`  
**Depends on:** CONV-340

### Goal

Добавить artisan command для ручного запуска cleanup expired files.

### TDD step

Feature/console test:

```php
it('runs expired files cleanup command successfully', function () {
    $this->artisan('files:cleanup-expired')
        ->expectsOutputToContain('Expired files cleanup completed')
        ->assertExitCode(0);
});
```

Тест должен упасть до создания command.

### Implementation

Создать command:

```bash
php artisan make:command CleanupExpiredFilesCommand
```

Signature:

```php
protected $signature = 'files:cleanup-expired {--sync : Run cleanup synchronously instead of dispatching queue job}';
```

Implementation:

```php
if ($this->option('sync')) {
    app(CleanupExpiredFilesJob::class)->handle();
} else {
    CleanupExpiredFilesJob::dispatch();
}

$this->info('Expired files cleanup completed.');
```

Можно выбрать sync-only для MVP, но queue dispatch лучше соответствует production.

### Acceptance criteria

- Command `files:cleanup-expired` существует.
- Command может dispatch job.
- `--sync` запускает cleanup сразу.
- Console test passes.

### Definition of Done

- Тест написан первым.
- Command создан.
- Tests pass.
- Коммит: `CONV-341: Add manual cleanup command`

### Files likely touched

```txt
app/Console/Commands/CleanupExpiredFilesCommand.php
tests/Feature/Console/CleanupExpiredFilesCommandTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-342 — Schedule Expired Files Cleanup

**Area:** Scheduler / Cleanup  
**Type:** Config  
**Priority:** P1  
**Branch:** `feature/CONV-342-schedule-expired-files-cleanup`  
**Base branch:** `develop`  
**Depends on:** CONV-341

### Goal

Зарегистрировать автоматический запуск cleanup expired files через Laravel scheduler.

### TDD step

No direct test — scheduler registration is infrastructure-level and hard to assert reliably without coupling to Laravel internals.

Manual check:

```bash
php artisan schedule:list
```

### Implementation

В зависимости от Laravel version:

Для `routes/console.php`:

```php
Schedule::command('files:cleanup-expired')->hourly()->withoutOverlapping();
```

или в `app/Console/Kernel.php`:

```php
$schedule->command('files:cleanup-expired')
    ->hourly()
    ->withoutOverlapping();
```

Добавить в deployment checklist будущей release-фазы:

```txt
* * * * * php artisan schedule:run
```

Если checklist уже существует, обновить его. Если нет — оставить короткий комментарий в README.

### Acceptance criteria

- Cleanup command scheduled hourly.
- `withoutOverlapping()` используется.
- `php artisan schedule:list` показывает command.
- README/deployment note обновлена, если соответствующий файл уже есть.
- No business logic added.

### Definition of Done

- Scheduler configured.
- Manual check done.
- `composer test` passes.
- `composer lint` passes.
- Коммит: `CONV-342: Schedule expired files cleanup`

### Files likely touched

```txt
routes/console.php
app/Console/Kernel.php
README.md
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-343 — Block Expired Web Downloads

**Area:** Downloads / Web  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-343-block-expired-web-downloads`  
**Base branch:** `develop`  
**Depends on:** CONV-342

### Goal

Запретить скачивание expired conversion result через web download route.

### TDD step

Feature test:

```php
it('does not allow downloading expired conversion result from web route', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $result = FileRecord::factory()->for($user)->create([
        'status' => FileStatus::Expired,
        'stored_path' => 'results/expired.jpg',
        'expires_at' => now()->subDay(),
    ]);

    $conversion = ConversionJob::factory()->for($user)->create([
        'status' => ConversionStatus::Completed,
        'result_file_id' => $result->id,
    ]);

    $this->actingAs($user)
        ->get(route('conversions.download', $conversion))
        ->assertStatus(410)
        ->assertSee('expired');
});
```

Также проверить happy path:

```php
it('allows downloading non expired conversion result from web route', ...);
```

Тест должен упасть до реализации.

### Implementation

В download route/controller/action добавить guard:

```php
if ($resultFile->isExpired()) {
    throw ResultExpiredException::forFile($resultFile);
}
```

Для web можно мапить на HTTP 410 Gone:

```txt
410 Gone
```

Создать exception:

```txt
app/Exceptions/Conversions/ResultExpiredException.php
```

или использовать существующую domain exception, если уже есть.

### Acceptance criteria

- Expired result download returns 410.
- Non-expired completed result downloads normally.
- Owner authorization still applies.
- Failed/incomplete conversion still blocked by existing rules.
- Tests pass.

### Definition of Done

- Тест написан первым.
- Web download guard добавлен.
- Expired result maps to 410.
- Tests pass.
- Коммит: `CONV-343: Block expired web downloads`

### Files likely touched

```txt
routes/web.php
app/Http/Controllers/ConversionDownloadController.php
app/Actions/Conversions/DownloadConversionResultAction.php
app/Exceptions/Conversions/ResultExpiredException.php
tests/Feature/Downloads/ConversionDownloadTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-344 — Block Expired API Downloads

**Area:** Downloads / API  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-344-block-expired-api-downloads`  
**Base branch:** `develop`  
**Depends on:** CONV-343

### Goal

Запретить скачивание expired conversion result через API endpoint с единым JSON error format.

### TDD step

API feature test:

```php
it('returns result_expired error for expired API download', function () {
    Storage::fake('local');

    $user = User::factory()->create([
        'plan' => Plan::Pro,
    ]);

    $apiKey = ApiKey::factory()->for($user)->createValidToken('test-token');

    $result = FileRecord::factory()->for($user)->create([
        'status' => FileStatus::Expired,
        'stored_path' => 'results/expired.jpg',
        'expires_at' => now()->subDay(),
    ]);

    $conversion = ConversionJob::factory()->for($user)->create([
        'status' => ConversionStatus::Completed,
        'result_file_id' => $result->id,
    ]);

    $this->withToken('test-token')
        ->getJson("/api/v1/conversions/{$conversion->id}/download")
        ->assertStatus(410)
        ->assertJsonPath('error.code', 'result_expired');
});
```

Adapt API key helper to actual implementation.

### Implementation

API download endpoint должен использовать тот же guard/action, что и web download.

В API exception mapper добавить:

```php
ResultExpiredException::class => [
    'status' => 410,
    'code' => 'result_expired',
]
```

JSON format:

```json
{
  "error": {
    "code": "result_expired",
    "message": "This conversion result has expired and can no longer be downloaded.",
    "details": {
      "expired_at": "2026-06-01T10:00:00Z"
    }
  }
}
```

### Acceptance criteria

- API expired download returns 410.
- API error code is `result_expired`.
- Error format matches Phase 19/20 API standard.
- Non-expired API download still works.
- Tests pass.

### Definition of Done

- API test written first.
- API error mapping added.
- Shared download guard reused.
- Tests pass.
- Коммит: `CONV-344: Block expired API downloads`

### Files likely touched

```txt
routes/api.php
app/Http/Controllers/Api/V1/ConversionDownloadController.php
app/Http/Responses/ApiErrorResponse.php
app/Exceptions/Conversions/ResultExpiredException.php
tests/Feature/Api/V1/ConversionDownloadApiTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `composer lint` проходят.

---

## CONV-345 — Show Retention And Expiration Info In UI

**Area:** UI / Dashboard / History  
**Type:** UI  
**Priority:** P1  
**Branch:** `feature/CONV-345-show-retention-and-expiration-info-in-ui`  
**Base branch:** `develop`  
**Depends on:** CONV-344

### Goal

Показать пользователю срок хранения файла/result и корректно отключать download action для expired results.

### TDD step

Livewire/table test:

```php
it('shows expiration info for completed conversion result', function () {
    $user = User::factory()->create();

    $result = FileRecord::factory()->for($user)->create([
        'expires_at' => now()->addDay(),
        'status' => FileStatus::Analyzed,
    ]);

    ConversionJob::factory()->for($user)->create([
        'status' => ConversionStatus::Completed,
        'result_file_id' => $result->id,
    ]);

    Livewire::actingAs($user)
        ->test(RecentConversionsTable::class)
        ->assertSee('Expires');
});
```

Expired action test:

```php
it('does not show download action for expired conversion result', ...);
```

Adapt to existing RecentConversionsTable test helpers.

### Implementation

В RecentConversionsTable / dashboard result state добавить:

```txt
Expires in 23h
Expires on Jun 2, 2026
Expired
```

Для expired completed result:

```txt
Download disabled
Tooltip/copy: This file expired and was deleted.
```

Не перегружать таблицу. Если места мало, показывать в actions dropdown или status subtext.

### Acceptance criteria

- Non-expired result shows expiration info.
- Expired result shows `Expired` state.
- Download action hidden/disabled for expired result.
- UI text is clear.
- Tests pass.
- `npm run build` passes.

### Definition of Done

- Tests written.
- UI updated.
- Expired downloads not offered as active action.
- Tests/build pass.
- Коммит: `CONV-345: Show retention and expiration info in UI`

### Files likely touched

```txt
app/Livewire/RecentConversionsTable.php
resources/views/livewire/recent-conversions-table.blade.php
resources/views/livewire/dashboard-converter.blade.php
tests/Feature/Livewire/RecentConversionsTableTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-346 — Add Cleanup And Retention Final Smoke Tests

**Area:** Tests / Retention / Cleanup  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-346-add-cleanup-and-retention-final-smoke-tests`  
**Base branch:** `develop`  
**Depends on:** CONV-345

### Goal

Добавить финальные smoke tests Phase 21, которые проверяют полный retention lifecycle.

### TDD step

Feature smoke test:

```php
it('handles full file retention lifecycle', function () {
    Carbon::setTestNow('2026-06-01 10:00:00');
    Storage::fake('local');

    $user = User::factory()->create([
        'plan' => Plan::Free,
    ]);

    $upload = UploadedFile::fake()->image('sample.png', 100, 100);

    $source = app(StoreUploadedFileAction::class)->handle($user, $upload);

    expect($source->expires_at)->not->toBeNull();

    Carbon::setTestNow($source->expires_at->copy()->addMinute());

    app(CleanupExpiredFilesJob::class)->handle();

    expect($source->fresh()->status)->toBe(FileStatus::Expired);

    Storage::disk('local')->assertMissing($source->stored_path);
});
```

Download smoke test:

```php
it('keeps completed conversion history but blocks download after result expires', ...);
```

### Implementation

Только добавить финальные smoke tests и поправить мелкие дефекты, если они вскроются.

Не добавлять новую функциональность.

### Acceptance criteria

- Full retention lifecycle test exists.
- Completed conversion history remains after result expiration.
- Expired result download blocked.
- Cleanup is idempotent.
- Full test suite passes.

### Definition of Done

- Smoke tests added.
- No new features added.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.
- Коммит: `CONV-346: Add cleanup and retention final smoke tests`

### Files likely touched

```txt
tests/Feature/Retention/FileRetentionLifecycleTest.php
tests/Feature/Jobs/CleanupExpiredFilesJobTest.php
tests/Feature/Downloads/ConversionDownloadTest.php
tests/Feature/Api/V1/ConversionDownloadApiTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 21 Completion Criteria

Phase 21 завершена, когда:

```txt
- CONV-330–CONV-346 выполнены;
- FileRetentionPolicy exists;
- uploaded files receive expires_at by plan;
- result files receive expires_at by plan;
- FileStatus::Expired exists;
- FileRecord::isExpired() works;
- CleanupExpiredFilesJob exists;
- expired physical files are deleted through Storage facade;
- non-expired physical files are not deleted;
- expired records are marked as expired;
- cleanup is idempotent;
- manual cleanup command exists;
- cleanup is scheduled;
- expired web downloads return 410;
- expired API downloads return standardized result_expired JSON error;
- UI shows expiration/expired state;
- completed conversion history remains visible after result expiration;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 21

Без отдельной задачи нельзя:

```txt
- добавлять paid storage add-ons;
- менять тарифную модель;
- менять credit pricing;
- добавлять S3 lifecycle policies;
- удалять conversion_jobs;
- hard delete FileRecord rows;
- добавлять restore expired file feature;
- добавлять recycle bin/trash UI;
- делать admin storage dashboard;
- делать user-selectable retention period;
- добавлять enterprise custom retention;
- добавлять webhooks file.expired;
- добавлять storage usage billing;
- добавлять новые converters;
- менять API endpoints кроме expired download behavior;
- добавлять новые frontend frameworks.
```

---

# 12. Recommended Execution Order

```txt
CONV-330 Create FileRetentionPolicy Service
CONV-331 Test Upload File Expiration By Plan
CONV-332 Apply Expiration To Uploaded Files
CONV-333 Test Result File Expiration By Plan
CONV-334 Apply Expiration To Conversion Result Files
CONV-335 Add Expired File Status Handling
CONV-336 Create CleanupExpiredFilesJob Skeleton
CONV-337 Test Cleanup Deletes Expired Physical Files
CONV-338 Implement Expired Physical File Deletion
CONV-339 Test Cleanup Marks File Records Expired
CONV-340 Implement Expired File Record Marking
CONV-341 Add Manual Cleanup Command
CONV-342 Schedule Expired Files Cleanup
CONV-343 Block Expired Web Downloads
CONV-344 Block Expired API Downloads
CONV-345 Show Retention And Expiration Info In UI
CONV-346 Add Cleanup And Retention Final Smoke Tests
```

---

# 13. Release

После завершения Phase 21:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh --seed
php artisan schedule:list

git checkout -b release/v0.1.21-phase21-cleanup-retention
git push -u origin release/v0.1.21-phase21-cleanup-retention
```

После этого сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.21-phase21-cleanup-retention -m "File Converter Phase 21 cleanup and retention"
git push origin v0.1.21-phase21-cleanup-retention
```
