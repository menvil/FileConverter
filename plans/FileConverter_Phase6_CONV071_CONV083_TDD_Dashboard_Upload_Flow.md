# File Converter — Phase 6 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 6 — Dashboard Upload Flow**  
Диапазон задач: **CONV-071 → CONV-083**  
Основа нумерации: Phase 5 завершилась на `CONV-070`, поэтому Phase 6 начинается с `CONV-071`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 6 соответствует блоку:

```txt
Phase 6 — Dashboard Upload Flow
```

Правильный диапазон Phase 6:

```txt
CONV-071 — Create DashboardConverter Component
CONV-072 — Connect Dashboard Route To Livewire Component
CONV-073 — Render Empty Upload State
CONV-074 — Test Valid File Upload Flow
CONV-075 — Implement Livewire Upload Handling
CONV-076 — Render Uploaded File Summary
CONV-077 — Add Replace Uploaded File Action
CONV-078 — Add Remove Uploaded File Action
CONV-079 — Add Drag Hover Upload UI
CONV-080 — Add Upload Error States
CONV-081 — Add Upload Loading State
CONV-082 — Add Format Step Placeholder
CONV-083 — Add Dashboard Upload Flow Smoke Tests
```

Phase 6 добавляет первый реальный пользовательский flow на dashboard:

```txt
empty upload state → upload valid file → store file → show uploaded file summary → move to format placeholder step
```

Важно:

```txt
Phase 6 = только upload flow на dashboard
Phase 7 = target format cards / choose target format
Phase 8 = dynamic settings form
Phase 9 = conversion jobs
Phase 10 = real conversion drivers
```

То есть в Phase 6 пользователь ещё **не выбирает целевой формат** и ещё **не запускает конвертацию**.

---

# 2. Цель Phase 6

Phase 6 должна превратить статический dashboard skeleton в рабочую Livewire-страницу загрузки файла.

После Phase 6 authenticated user должен уметь:

```txt
- открыть /dashboard;
- увидеть empty upload state;
- выбрать или перетащить файл;
- загрузить supported file;
- получить сохранённый FileRecord;
- увидеть uploaded file summary;
- заменить файл;
- удалить файл из текущего flow;
- увидеть понятную ошибку для unsupported/too large/upload failed;
- перейти в placeholder состояния выбора формата.
```

Эта фаза проверяет связку:

```txt
Livewire UI → StoreUploadedFileAction → FileRecord → Dashboard state
```

---

# 3. Scope Phase 6

## Входит

```txt
- DashboardConverter Livewire component;
- /dashboard route connected to DashboardConverter;
- upload step state;
- empty upload card;
- Livewire file upload;
- call StoreUploadedFileAction;
- uploaded file summary;
- replace uploaded file action;
- remove uploaded file action;
- drag/drop visual hover with Alpine;
- upload loading state;
- upload error states;
- format step placeholder after successful upload;
- Livewire tests for upload flow;
- feature smoke tests for dashboard route.
```

## Не входит

```txt
- target format cards;
- ConverterRegistry UI integration;
- choosing JPG/WEBP/PDF target;
- DynamicOptionsForm;
- conversion jobs;
- real conversion;
- result download;
- recent conversions table with real data;
- credits;
- billing;
- Cashier;
- API endpoints;
- direct-to-S3 upload;
- chunk upload;
- virus scanning;
- OCR;
- batch upload.
```

---

# 4. Critical Decisions

## 4.1. DashboardConverter owns UI state only

`DashboardConverter` не должен содержать бизнес-логику storage/detection/metadata.

Неправильно:

```php
$this->file->store(...);
FileRecord::create(...);
getimagesize(...);
```

Правильно:

```php
$this->currentFile = app(StoreUploadedFileAction::class)->handle(
    user: auth()->user(),
    file: $this->upload,
);
```

Business logic остаётся в Phase 5 actions/services.

## 4.2. Successful upload moves to format placeholder only

После успешной загрузки Phase 6 показывает placeholder следующего шага:

```txt
Choose output format
Target format selection will be added in Phase 7.
```

Нельзя в Phase 6 добавлять cards:

```txt
JPG
WEBP
PDF
```

Это задача Phase 7.

## 4.3. Current file state should use FileRecord id

Livewire-компонент не должен хранить весь model state как рыхлый массив.

Правильно:

```php
public ?int $currentFileId = null;
```

И computed/helper:

```php
public function getCurrentFileProperty(): ?FileRecord
```

Можно хранить DTO-like array для UI, но source of truth — database record.

## 4.4. Remove from flow is not physical deletion

`Remove` в Phase 6 означает:

```txt
убрать файл из текущего dashboard flow
```

Это не обязательно должно физически удалять FileRecord или storage file.

Физическое удаление/cleanup — отдельная retention/cleanup фаза.

## 4.5. Replace means reset current flow

`Replace` должен:

```txt
- сбросить currentFileId;
- сбросить upload property;
- вернуть step в upload;
- очистить upload errors;
```

Нельзя оставлять старые errors или старый current file visible.

## 4.6. Upload errors must be specific

Плохо:

```txt
Upload failed.
```

Хорошо:

```txt
This file type is not supported in beta. Upload PNG, JPG, WEBP or PDF.
```

или:

```txt
This file is too large for your current plan.
```

---

# 5. Architecture Rules

## 5.1. Use existing UI components

Phase 6 должна использовать компоненты из Phase 1:

```txt
x-card
x-button
x-badge
x-file-icon
x-stepper
form controls
```

Если какой-то UI primitive отсутствует, можно добавить минимальный partial только если он нужен для upload flow. Но нельзя расширять UI foundation бесконтрольно.

## 5.2. Use existing file backend from Phase 5

Phase 6 должна использовать:

```txt
StoreUploadedFileAction
FileRecord
FileFormatDetector
ImageMetadataExtractor
FileStatus
```

Если Phase 5 не завершена, Phase 6 не начинается.

## 5.3. Keep upload single-file in MVP

Несмотря на будущий batch conversion, Phase 6 поддерживает только один файл в активном flow.

Нельзя добавлять:

```txt
multiple uploads
batch queue
multi-file options
zip download all
```

Batch — отдельная будущая фаза.

## 5.4. Auth user is required

Так как Phase 2 уже добавила auth foundation, dashboard upload работает только для authenticated users.

`StoreUploadedFileAction` должен получать текущего пользователя.

## 5.5. No conversion cost or billing in upload phase

Нельзя показывать real credits cost в Phase 6. Целевой формат ещё не выбран, значит стоимость неизвестна.

Допустимая надпись:

```txt
Cost will be shown after you choose an output format.
```

---

# 6. GitFlow для Phase 6

## Base branch

Все задачи Phase 6 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-071-create-dashboard-converter-component
feature/CONV-075-implement-livewire-upload-handling
feature/CONV-080-add-upload-error-states
```

## Commit format

```txt
CONV-071: Create DashboardConverter component
CONV-075: Implement Livewire upload handling
CONV-080: Add upload error states
```

## Release branch

После выполнения `CONV-071`–`CONV-083`:

```txt
release/v0.1.6-phase06-dashboard-upload-flow
```

## Tag

После merge release branch в `main`:

```txt
v0.1.6-phase06-dashboard-upload-flow
```

---

# 7. TDD Rules for Phase 6

## Для Livewire component

Test-first:

```txt
- component renders empty upload state;
- valid supported file can be uploaded;
- upload calls StoreUploadedFileAction behavior through resulting FileRecord;
- successful upload moves to format placeholder step;
- uploaded file summary is visible;
- remove resets state;
- replace resets state;
- unsupported upload shows error;
- too large upload shows error.
```

## Для route

Feature/smoke tests:

```txt
- authenticated user can access /dashboard;
- guest is redirected to login;
- dashboard renders DashboardConverter component.
```

## Для UI states

Тестировать то, что важно:

```txt
- empty state text visible;
- uploaded file name visible;
- upload error visible;
- format placeholder visible after success.
```

Не нужно тестировать каждый Tailwind class.

## Если прямой тест сложен

Для Alpine drag-hover допускается:

```txt
No direct backend test — visual Alpine-only behavior.
```

Но view должен быть покрыт smoke/render test.

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Dashboard / Livewire / Upload / Tests
Type: Test / Feature / Component / UI / State
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

# 9. Phase 6 Atomic Tasks

---

## CONV-071 — Create DashboardConverter Component

**Area:** Dashboard / Livewire  
**Type:** Component  
**Priority:** P0  
**Branch:** `feature/CONV-071-create-dashboard-converter-component`  
**Base branch:** develop  
**Depends on:** CONV-070

### Goal

Создать Livewire-компонент `DashboardConverter`, который станет главным рабочим компонентом dashboard upload flow.

### TDD step

Livewire smoke test:

```php
use App\Livewire\Dashboard\DashboardConverter;
use Livewire\Livewire;

it('renders dashboard converter component', function () {
    Livewire::test(DashboardConverter::class)
        ->assertSee('Drop your file here');
});
```

Тест должен упасть до создания компонента.

### Implementation

Создать компонент:

```bash
php artisan make:livewire Dashboard/DashboardConverter
```

Минимальное состояние:

```php
public string $step = 'upload';
public $upload = null;
public ?int $currentFileId = null;
public ?string $uploadError = null;
```

View должен временно показывать empty state:

```txt
Drop your file here
PNG, JPG, WEBP and PDF supported in beta
```

### Acceptance criteria

- `DashboardConverter` существует.
- Component render проходит.
- Empty upload text visible.
- Нет вызова StoreUploadedFileAction пока.
- Нет target format cards.
- Нет conversion logic.

### Definition of Done

- Тест написан первым.
- Component создан.
- Тест проходит.
- `composer test` проходит.
- Коммит: `CONV-071: Create DashboardConverter component`

### Files likely touched

```txt
app/Livewire/Dashboard/DashboardConverter.php
resources/views/livewire/dashboard/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-072 — Connect Dashboard Route To Livewire Component

**Area:** Dashboard / Routes  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-072-connect-dashboard-route-to-livewire-component`  
**Base branch:** develop  
**Depends on:** CONV-071

### Goal

Подключить `/dashboard` к настоящему `DashboardConverter` вместо placeholder view из Phase 0/1.

### TDD step

Feature test:

```php
it('renders dashboard converter on dashboard page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Drop your file here');
});
```

Guest test:

```php
it('redirects guest from dashboard to login', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});
```

### Implementation

Update route/view depending on project convention.

Recommended:

```php
Route::middleware(['auth'])
    ->get('/dashboard', DashboardPage::class)
    ->name('dashboard');
```

or Blade route:

```php
Route::middleware(['auth'])->view('/dashboard', 'dashboard')->name('dashboard');
```

`resources/views/dashboard.blade.php`:

```blade
<x-app-layout>
    <livewire:dashboard.dashboard-converter />
</x-app-layout>
```

### Acceptance criteria

- Authenticated user sees dashboard.
- Guest redirects to login.
- `DashboardConverter` renders on `/dashboard`.
- Old placeholder is replaced or wraps Livewire component.
- No upload logic yet.

### Definition of Done

- Tests written.
- Route connected.
- Tests pass.
- Коммит: `CONV-072: Connect dashboard route to Livewire component`

### Files likely touched

```txt
routes/web.php
resources/views/dashboard.blade.php
tests/Feature/DashboardRouteTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-073 — Render Empty Upload State

**Area:** Dashboard / Upload UI  
**Type:** UI  
**Priority:** P0  
**Branch:** `feature/CONV-073-render-empty-upload-state`  
**Base branch:** develop  
**Depends on:** CONV-072

### Goal

Сделать нормальный empty upload state в стиле текущего дизайна.

### TDD step

Livewire render test:

```php
it('renders empty upload state', function () {
    Livewire::test(DashboardConverter::class)
        ->assertSee('Drop your file here')
        ->assertSee('PNG, JPG, WEBP and PDF supported in beta')
        ->assertSee('Choose file');
});
```

### Implementation

В view компонента добавить upload card:

```txt
- card container;
- cloud/upload icon;
- heading;
- supported formats text;
- Choose file button;
- privacy/security note;
- import source buttons disabled/placeholder optional.
```

Важно: если import sources пока не работают, не делать их активными.

Допустимо показать только:

```txt
Device upload
```

### Acceptance criteria

- Empty upload card выглядит как часть dashboard design.
- Есть file input.
- Есть visible CTA `Choose file`.
- Supported formats честные: PNG/JPG/WEBP/PDF.
- Нет fake Google Drive/Dropbox functionality.
- Render test passes.

### Definition of Done

- Тест написан.
- Empty upload card добавлена.
- Tests pass.
- Коммит: `CONV-073: Render empty upload state`

### Files likely touched

```txt
resources/views/livewire/dashboard/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-074 — Test Valid File Upload Flow

**Area:** Dashboard / Upload / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-074-test-valid-file-upload-flow`  
**Base branch:** develop  
**Depends on:** CONV-073

### Goal

Написать падающий тест: пользователь может загрузить валидный PNG/JPG файл через `DashboardConverter`.

### TDD step

Livewire test:

```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('uploads a valid image file and moves to format step', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', UploadedFile::fake()->image('sample.png', 600, 400))
        ->call('storeUpload')
        ->assertSet('step', 'format')
        ->assertSee('sample.png')
        ->assertSee('Choose output format');

    expect(FileRecord::query()->where('original_name', 'sample.png')->exists())->toBeTrue();
});
```

Тест должен упасть до реализации `storeUpload()`.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Тест проверяет upload through Livewire.
- Тест проверяет FileRecord.
- Тест проверяет transition to `format`.
- Тест падает до CONV-075.

### Definition of Done

- Тест добавлен.
- Тест ожидаемо падает.
- Коммит: `CONV-074: Test valid file upload flow`

### Files likely touched

```txt
tests/Feature/Livewire/DashboardConverterUploadTest.php
```

После этого сделай MR в `develop`. Merge разрешён после подтверждения, что новый тест падает по ожидаемой причине.

---

## CONV-075 — Implement Livewire Upload Handling

**Area:** Dashboard / Upload / Livewire  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-075-implement-livewire-upload-handling`  
**Base branch:** develop  
**Depends on:** CONV-074

### Goal

Реализовать обработку валидного upload через `StoreUploadedFileAction`.

### TDD step

Использовать падающий тест из CONV-074.

### Implementation

В `DashboardConverter` добавить Livewire upload trait:

```php
use Livewire\WithFileUploads;

final class DashboardConverter extends Component
{
    use WithFileUploads;
}
```

Добавить method:

```php
public function storeUpload(StoreUploadedFileAction $storeUploadedFile): void
{
    $this->resetErrorBag();
    $this->uploadError = null;

    $this->validate([
        'upload' => ['required', 'file'],
    ]);

    $fileRecord = $storeUploadedFile->handle(
        user: auth()->user(),
        file: $this->upload,
    );

    $this->currentFileId = $fileRecord->id;
    $this->step = 'format';
}
```

Если action выбрасывает domain exception, обработка будет улучшена в CONV-080.

### Acceptance criteria

- Valid upload creates FileRecord.
- `currentFileId` is set.
- `step` becomes `format`.
- Component does not create FileRecord directly.
- Uses `StoreUploadedFileAction`.
- Test from CONV-074 passes.

### Definition of Done

- Upload handling implemented.
- Test passes.
- No direct storage/model creation in Livewire.
- Коммит: `CONV-075: Implement Livewire upload handling`

### Files likely touched

```txt
app/Livewire/Dashboard/DashboardConverter.php
tests/Feature/Livewire/DashboardConverterUploadTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-076 — Render Uploaded File Summary

**Area:** Dashboard / Upload UI  
**Type:** UI  
**Priority:** P0  
**Branch:** `feature/CONV-076-render-uploaded-file-summary`  
**Base branch:** develop  
**Depends on:** CONV-075

### Goal

После успешной загрузки показывать summary загруженного файла.

### TDD step

Livewire test:

```php
it('shows uploaded file summary after upload', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', UploadedFile::fake()->image('avatar.jpg', 800, 600))
        ->call('storeUpload')
        ->assertSee('avatar.jpg')
        ->assertSee('JPG')
        ->assertSee('Replace')
        ->assertSee('Remove');
});
```

### Implementation

Добавить computed/helper:

```php
public function getCurrentFileProperty(): ?FileRecord
{
    return $this->currentFileId
        ? FileRecord::query()->where('user_id', auth()->id())->find($this->currentFileId)
        : null;
}
```

В view показать:

```txt
- FileIcon;
- original filename;
- extension/format;
- human-readable size;
- image dimensions if available;
- Replace button;
- Remove button.
```

### Acceptance criteria

- Uploaded filename visible.
- Format visible.
- Size visible.
- Replace button visible.
- Remove button visible.
- Uses `x-file-icon`.
- Test passes.

### Definition of Done

- Test written.
- Uploaded file summary rendered.
- Tests pass.
- Коммит: `CONV-076: Render uploaded file summary`

### Files likely touched

```txt
app/Livewire/Dashboard/DashboardConverter.php
resources/views/livewire/dashboard/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterUploadTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-077 — Add Replace Uploaded File Action

**Area:** Dashboard / Upload State  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-077-add-replace-uploaded-file-action`  
**Base branch:** develop  
**Depends on:** CONV-076

### Goal

Добавить действие `Replace`, которое сбрасывает текущий файл и возвращает пользователя в upload step.

### TDD step

Livewire test:

```php
it('resets current file when replacing uploaded file', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', UploadedFile::fake()->image('first.png'))
        ->call('storeUpload')
        ->assertSet('step', 'format')
        ->call('replaceFile')
        ->assertSet('step', 'upload')
        ->assertSet('currentFileId', null)
        ->assertSee('Drop your file here');
});
```

### Implementation

Add method:

```php
public function replaceFile(): void
{
    $this->reset('upload', 'currentFileId', 'uploadError');
    $this->resetErrorBag();
    $this->step = 'upload';
}
```

Wire button:

```blade
<x-button wire:click="replaceFile" variant="secondary">Replace</x-button>
```

### Acceptance criteria

- Replace button resets current file.
- Step returns to upload.
- Old upload errors cleared.
- Physical file is not deleted in this task.
- Test passes.

### Definition of Done

- Test written.
- Replace action implemented.
- Tests pass.
- Коммит: `CONV-077: Add replace uploaded file action`

### Files likely touched

```txt
app/Livewire/Dashboard/DashboardConverter.php
resources/views/livewire/dashboard/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterUploadTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-078 — Add Remove Uploaded File Action

**Area:** Dashboard / Upload State  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-078-add-remove-uploaded-file-action`  
**Base branch:** develop  
**Depends on:** CONV-077

### Goal

Добавить действие `Remove`, которое убирает файл из текущего flow без физического удаления из storage.

### TDD step

Livewire test:

```php
it('removes uploaded file from current flow', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', UploadedFile::fake()->image('remove-me.png'))
        ->call('storeUpload')
        ->assertSet('step', 'format')
        ->call('removeFile')
        ->assertSet('step', 'upload')
        ->assertSet('currentFileId', null)
        ->assertDontSee('remove-me.png');
});
```

### Implementation

Add method:

```php
public function removeFile(): void
{
    $this->reset('upload', 'currentFileId', 'uploadError');
    $this->resetErrorBag();
    $this->step = 'upload';
}
```

Можно reuse same reset private method:

```php
private function resetCurrentUpload(): void
```

### Acceptance criteria

- Remove clears current file from UI.
- Step returns to upload.
- FileRecord is not physically deleted.
- Old errors cleared.
- Test passes.

### Definition of Done

- Test written.
- Remove action implemented.
- Tests pass.
- Коммит: `CONV-078: Add remove uploaded file action`

### Files likely touched

```txt
app/Livewire/Dashboard/DashboardConverter.php
resources/views/livewire/dashboard/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterUploadTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-079 — Add Drag Hover Upload UI

**Area:** Dashboard / Alpine / UI  
**Type:** UI  
**Priority:** P1  
**Branch:** `feature/CONV-079-add-drag-hover-upload-ui`  
**Base branch:** develop  
**Depends on:** CONV-078

### Goal

Добавить визуальную реакцию upload card на drag-over/drag-leave через Alpine.js.

### TDD step

No direct backend test — Alpine visual behavior.

Smoke render test should still assert upload area exists:

```php
it('renders drag and drop upload area', function () {
    Livewire::test(DashboardConverter::class)
        ->assertSee('Drop your file here')
        ->assertSee('Choose file');
});
```

### Implementation

В upload card добавить Alpine state:

```blade
<div
    x-data="{ isDragging: false }"
    x-on:dragover.prevent="isDragging = true"
    x-on:dragleave.prevent="isDragging = false"
    x-on:drop.prevent="isDragging = false"
    x-bind:class="isDragging ? '...' : '...'"
>
```

Важно: Livewire file input всё ещё отвечает за реальный upload. Не делать custom JS upload.

### Acceptance criteria

- Upload card has drag hover state.
- No custom JS file upload implementation.
- File input remains Livewire-controlled.
- `npm run build` passes.
- Smoke test passes.

### Definition of Done

- Alpine drag hover added.
- No backend behavior changed.
- Build/tests pass.
- Коммит: `CONV-079: Add drag hover upload UI`

### Files likely touched

```txt
resources/views/livewire/dashboard/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-080 — Add Upload Error States

**Area:** Dashboard / Upload / Errors  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-080-add-upload-error-states`  
**Base branch:** develop  
**Depends on:** CONV-079

### Goal

Показывать понятные ошибки при failed upload или unsupported file.

### TDD step

Unsupported file test:

```php
it('shows an error for unsupported upload format', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', UploadedFile::fake()->create('notes.txt', 10, 'text/plain'))
        ->call('storeUpload')
        ->assertSet('step', 'upload')
        ->assertSee('not supported');
});
```

Too large test can be added if validation rules from Phase 5 are easy to trigger.

### Implementation

Catch domain exceptions from StoreUploadedFileAction:

```php
try {
    // store upload
} catch (UnsupportedFormatException $e) {
    $this->uploadError = 'This file type is not supported in beta. Upload PNG, JPG, WEBP or PDF.';
    $this->step = 'upload';
} catch (FileTooLargeException $e) {
    $this->uploadError = $e->getMessage();
    $this->step = 'upload';
}
```

If exact exception names differ from Phase 5, adapt to actual domain exceptions.

View:

```blade
@if ($uploadError)
    <x-alert variant="danger">{{ $uploadError }}</x-alert>
@endif
```

If no alert component exists, use a local minimal block.

### Acceptance criteria

- Unsupported format shows specific message.
- Step remains `upload` after failed upload.
- No FileRecord created for invalid upload.
- Existing valid upload flow still works.
- Tests pass.

### Definition of Done

- Tests written.
- Error mapping implemented.
- User sees readable error.
- Tests pass.
- Коммит: `CONV-080: Add upload error states`

### Files likely touched

```txt
app/Livewire/Dashboard/DashboardConverter.php
resources/views/livewire/dashboard/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterUploadTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-081 — Add Upload Loading State

**Area:** Dashboard / Upload UI  
**Type:** UI  
**Priority:** P1  
**Branch:** `feature/CONV-081-add-upload-loading-state`  
**Base branch:** develop  
**Depends on:** CONV-080

### Goal

Добавить loading/disabled state во время upload, чтобы пользователь не мог отправить файл повторно.

### TDD step

No direct reliable backend test for Livewire loading state.

But render/smoke test should confirm loading markup exists if practical:

```php
it('renders upload action controls', function () {
    Livewire::test(DashboardConverter::class)
        ->assertSee('Choose file')
        ->assertSee('Uploading', false);
});
```

Если `assertSee('Uploading')` спорный из-за `wire:loading`, можно не тестировать напрямую.

### Implementation

Add Livewire loading directives:

```blade
<div wire:loading wire:target="upload,storeUpload">
    Uploading...
</div>

<x-button wire:loading.attr="disabled" wire:target="upload,storeUpload">
    Choose file
</x-button>
```

Convert/upload submit button should not be clickable twice.

### Acceptance criteria

- Upload controls visually show loading state.
- Upload button/input disabled during upload/store.
- Existing upload tests still pass.
- Build passes.

### Definition of Done

- Loading state added.
- No behavior regression.
- Tests/build pass.
- Коммит: `CONV-081: Add upload loading state`

### Files likely touched

```txt
resources/views/livewire/dashboard/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-082 — Add Format Step Placeholder

**Area:** Dashboard / Stepper / UI  
**Type:** UI  
**Priority:** P0  
**Branch:** `feature/CONV-082-add-format-step-placeholder`  
**Base branch:** develop  
**Depends on:** CONV-081

### Goal

После успешного upload показать placeholder следующего шага `Choose output format`, не реализуя сам выбор формата.

### TDD step

Livewire test:

```php
it('shows format step placeholder after successful upload', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', UploadedFile::fake()->image('photo.png'))
        ->call('storeUpload')
        ->assertSet('step', 'format')
        ->assertSee('Choose output format')
        ->assertSee('Target format selection will be added in Phase 7');
});
```

### Implementation

Render stepper with current step:

```txt
File → Format → Settings → Convert
```

For `step === 'format'` show:

```txt
Choose output format
Target format selection will be added in Phase 7.
```

Do not render real target cards.

### Acceptance criteria

- Stepper marks File completed and Format active.
- Placeholder text visible after upload.
- No real target format selection yet.
- Uploaded file summary remains visible.
- Test passes.

### Definition of Done

- Test written.
- Format placeholder added.
- Tests pass.
- Коммит: `CONV-082: Add format step placeholder`

### Files likely touched

```txt
resources/views/livewire/dashboard/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterUploadTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-083 — Add Dashboard Upload Flow Smoke Tests

**Area:** Dashboard / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-083-add-dashboard-upload-flow-smoke-tests`  
**Base branch:** develop  
**Depends on:** CONV-082

### Goal

Добавить финальные smoke/regression tests для Phase 6, чтобы закрепить upload flow перед Phase 7.

### TDD step

Добавить/собрать тесты:

```txt
- guest cannot access dashboard;
- auth user sees empty upload state;
- valid PNG upload creates FileRecord;
- successful upload moves to format step;
- uploaded summary displays name/format/size;
- remove resets flow;
- replace resets flow;
- unsupported file shows error.
```

### Implementation

Создать отдельный test file или привести существующие тесты к структуре:

```txt
tests/Feature/Livewire/DashboardConverterUploadTest.php
tests/Feature/DashboardRouteTest.php
```

Проверить, что тесты не зависят от порядка выполнения.

Добавить `RefreshDatabase` там, где нужна БД.

### Acceptance criteria

- Все ключевые сценарии Phase 6 покрыты тестами.
- Тесты независимы друг от друга.
- Нет flaky storage state.
- `Storage::fake()` используется в upload tests.
- `composer test` проходит.
- `composer lint` проходит.
- `npm run build` проходит.

### Definition of Done

- Smoke/regression tests added.
- Full test suite passes.
- Build passes.
- Коммит: `CONV-083: Add dashboard upload flow smoke tests`

### Files likely touched

```txt
tests/Feature/Livewire/DashboardConverterUploadTest.php
tests/Feature/DashboardRouteTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 6 Completion Criteria

Phase 6 завершена, когда:

```txt
- CONV-071–CONV-083 выполнены;
- DashboardConverter exists;
- /dashboard renders DashboardConverter;
- guest redirects from dashboard;
- authenticated user can access dashboard;
- empty upload state is visible;
- Livewire upload works for supported files;
- StoreUploadedFileAction is used;
- valid file creates FileRecord;
- uploaded file summary is visible;
- replace action resets current flow;
- remove action resets current flow;
- unsupported file shows readable error;
- upload loading state exists;
- drag hover UI exists;
- after upload step becomes format;
- format step placeholder is visible;
- no target format cards were added;
- no conversion jobs were created;
- no real conversion was added;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 6

Без отдельной задачи нельзя:

```txt
- добавлять target format cards;
- выбирать JPG/WEBP/PDF target;
- подключать ConverterRegistry к UI;
- создавать DynamicOptionsForm;
- создавать ConversionJob;
- запускать queue job;
- делать реальную конвертацию;
- добавлять download result;
- добавлять credits estimate;
- добавлять Billing/Cashier;
- добавлять API routes;
- добавлять multiple upload / batch queue;
- добавлять S3 direct upload;
- добавлять chunk upload;
- удалять physical files через Remove button;
- делать OCR/video/audio logic;
- добавлять public formats page;
- добавлять React/Vue/Inertia.
```

---

# 12. Recommended Execution Order

```txt
CONV-071 Create DashboardConverter Component
CONV-072 Connect Dashboard Route To Livewire Component
CONV-073 Render Empty Upload State
CONV-074 Test Valid File Upload Flow
CONV-075 Implement Livewire Upload Handling
CONV-076 Render Uploaded File Summary
CONV-077 Add Replace Uploaded File Action
CONV-078 Add Remove Uploaded File Action
CONV-079 Add Drag Hover Upload UI
CONV-080 Add Upload Error States
CONV-081 Add Upload Loading State
CONV-082 Add Format Step Placeholder
CONV-083 Add Dashboard Upload Flow Smoke Tests
```

---

# 13. Release

После завершения Phase 6:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh

git checkout -b release/v0.1.6-phase06-dashboard-upload-flow
git push -u origin release/v0.1.6-phase06-dashboard-upload-flow
```

После этого сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.6-phase06-dashboard-upload-flow -m "File Converter Phase 06 Dashboard Upload Flow"
git push origin v0.1.6-phase06-dashboard-upload-flow
```
