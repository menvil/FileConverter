# File Converter — Phase 11 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 11 — Convert UI Flow**  
Диапазон задач: **CONV-147 → CONV-163**  
Основа нумерации: Phase 10 завершилась на `CONV-146`, поэтому Phase 11 начинается с `CONV-147`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 11 соответствует блоку:

```txt
Phase 11 — Convert UI Flow
```

Правильный диапазон Phase 11:

```txt
CONV-147 — Add Convert Button Test
CONV-148 — Connect Convert Button To CreateConversionJobAction
CONV-149 — Add Duplicate Convert Protection
CONV-150 — Add Converting State Test
CONV-151 — Implement Converting State UI
CONV-152 — Add Job Status Polling Test
CONV-153 — Implement Job Status Polling
CONV-154 — Add Completed State Test
CONV-155 — Implement Completed State UI
CONV-156 — Add Failed State Test
CONV-157 — Implement Failed State UI
CONV-158 — Add Result Download Route Test
CONV-159 — Implement Result Download Route
CONV-160 — Add Convert Another Action
CONV-161 — Add Convert With Different Settings Action
CONV-162 — Add Result Expired UI Handling
CONV-163 — Add Convert Flow Integration Test
```

Phase 11 соединяет уже готовые части:

```txt
Phase 06 — uploaded file state
Phase 07 — target format selection
Phase 08 — dynamic settings form
Phase 09 — conversion job core
Phase 10 — real image conversion drivers
```

После Phase 11 пользователь должен пройти полный web-flow:

```txt
Upload file → choose target → configure settings → click Convert → wait → download result
```

Важно: Phase 11 не добавляет billing, credits, pricing, API или history table.

---

# 2. Цель Phase 11

Phase 11 превращает dashboard-конвертер из формы настройки в рабочий UI запуска конвертации.

После Phase 11 authenticated user должен уметь:

```txt
- нажать Convert Now после выбора target format и options;
- создать ConversionJob через CreateConversionJobAction;
- видеть состояние converting;
- видеть обновление статуса job через polling;
- видеть completed state после успешной конвертации;
- скачать result file;
- видеть failed state при ошибке;
- начать новую конвертацию;
- вернуться к настройкам и повторить с другими options.
```

Это UI-flow фаза. Она не должна менять converter registry, real drivers или billing model.

---

# 3. Scope Phase 11

## Входит

```txt
- Convert Now button behavior;
- Livewire call to CreateConversionJobAction;
- current conversion job state in DashboardConverter;
- converting UI state;
- polling job status;
- completed UI state;
- failed UI state;
- result download route;
- convert another action;
- convert with different settings action;
- expired result message handling;
- end-to-end happy path test for web conversion flow.
```

## Не входит

```txt
- Recent Conversions table;
- full history page;
- CreditLedger;
- ConversionCostEstimator;
- Laravel Cashier;
- billing page;
- API endpoints;
- API docs;
- batch conversion;
- cancel conversion;
- retry failed conversion with new job;
- queue progress percentage from real workers;
- websocket/broadcast progress;
- S3 signed download URLs;
- public share links;
- email notification after conversion.
```

Recent conversions будет отдельной фазой.  
Credits/cost estimator будет отдельной фазой.  
API будет отдельной фазой.

---

# 4. Critical Decisions

## 4.1. Livewire controls UI state only

`DashboardConverter` не должен выполнять конвертацию напрямую.

Неправильно:

```php
$this->driver->convert($file, $options);
```

Правильно:

```php
$job = app(CreateConversionJobAction::class)->handle(
    user: auth()->user(),
    file: $this->currentFile,
    targetFormat: $this->targetFormat,
    options: $this->options,
);
```

Конвертация выполняется queue job из Phase 09/10.

## 4.2. Convert button creates a job, not a result

После нажатия `Convert Now` UI должен перейти в:

```txt
converting
```

а не пытаться сразу показать result.

Правильный flow:

```txt
click Convert Now
→ create queued ConversionJob
→ set currentJobId
→ show converting state
→ poll status
→ completed/failed
```

## 4.3. No credits in Phase 11

Даже если в UI уже есть placeholder стоимости, Phase 11 не должна списывать credits.

Нельзя добавлять:

```txt
CreditLedger
ConversionCostEstimator
InsufficientCreditsException
billing CTA
```

Эти задачи будут позже. Если `CreateConversionJobAction` уже имеет extension point для billing, он должен оставаться no-op в Phase 11.

## 4.4. Download route must enforce ownership

Даже в MVP нельзя отдавать result file без проверки владельца.

Правило:

```txt
Only owner of completed conversion can download result.
```

Если result file expired или conversion не completed, download запрещён.

## 4.5. Polling is enough for MVP

Не добавлять websockets/broadcasting.

Правильно:

```blade
<div wire:poll.2s="refreshConversionStatus">
```

или polling только на converting state.

## 4.6. Failed state must be readable

Пользователь не должен видеть raw exception:

```txt
ImagickException: unable to read blob
```

Нужно показывать нормальное сообщение:

```txt
Conversion failed. Try another file or change settings.
```

Raw error можно логировать, но не выводить.

---

# 5. Architecture Rules

## 5.1. DashboardConverter may store only UI state

Допустимые public properties:

```php
public string $step = 'upload';
public ?int $currentFileId = null;
public ?string $targetFormat = null;
public array $options = [];
public ?int $currentConversionJobId = null;
```

Недопустимо:

```php
public ConverterDriver $driver;
public UploadedFile $persistedSourceFile;
public ConversionResult $result;
```

Livewire state должен быть serializable.

## 5.2. Download route belongs to web layer

Route:

```txt
GET /conversions/{conversion}/download
```

Контроллер/route action должен:

```txt
- проверить auth;
- проверить owner;
- проверить status completed;
- проверить result_file_id;
- проверить file exists;
- вернуть download response.
```

## 5.3. Do not add Recent Conversions table here

После completed state job уже существует в базе. Но table/list будет Phase 12.

В Phase 11 можно показывать только current conversion result.

## 5.4. Do not change converter schemas

Если UI settings неудобны, не менять schemas в Phase 11.  
Schema improvements — отдельные задачи в Phase 08/04 или будущей polish-фазе.

## 5.5. No direct model mutation in Livewire

Нельзя в Livewire делать:

```php
ConversionJob::create([...]);
$job->update(['status' => 'completed']);
```

Нужно использовать actions/jobs из Phase 09.

---

# 6. GitFlow для Phase 11

## Base branch

Все задачи Phase 11 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-147-add-convert-button-test
feature/CONV-153-implement-job-status-polling
feature/CONV-159-implement-result-download-route
```

## Commit format

```txt
CONV-147: Add convert button test
CONV-153: Implement job status polling
CONV-159: Implement result download route
```

## Release branch

После выполнения `CONV-147`–`CONV-163`:

```txt
release/v0.1.11-phase11-convert-ui-flow
```

## Tag

После merge release branch в `main`:

```txt
v0.1.11-phase11-convert-ui-flow
```

---

# 7. TDD Rules for Phase 11

## Для Convert button

Тестировать:

```txt
- user can click Convert Now after file/target/options are selected;
- CreateConversionJobAction is called or job is created;
- step changes to converting;
- duplicate clicks do not create duplicate jobs.
```

## Для states

Тестировать:

```txt
- converting state renders;
- completed state renders when job is completed;
- failed state renders when job is failed;
- expired result state renders clear message.
```

## Для polling

Тестировать:

```txt
- refreshConversionStatus updates step from converting to completed;
- refreshConversionStatus updates step from converting to failed;
- polling does nothing if no current job exists.
```

## Для download

Тестировать:

```txt
- owner can download completed result;
- other user cannot download result;
- queued/processing/failed conversion cannot be downloaded;
- expired result cannot be downloaded;
- missing result file is handled safely.
```

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Dashboard / Livewire / Conversion / Download / Tests
Type: Test / Feature / UI / Route / Action
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
- Нет billing/API/history вне scope задачи
- Коммит содержит ID задачи

Files likely touched:
- path/to/file
```

---

# 9. Phase 11 Atomic Tasks

---

## CONV-147 — Add Convert Button Test

**Area:** Dashboard / Livewire / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-147-add-convert-button-test`  
**Base branch:** `develop`  
**Depends on:** CONV-146

### Goal

Написать падающий Livewire-тест: после загрузки файла, выбора target format и валидных options пользователь может нажать `Convert Now`, и создаётся conversion job.

### TDD step

Livewire test:

```php
use Livewire\Livewire;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;

it('creates conversion job when user clicks convert now', function () {
    Queue::fake();

    $user = User::factory()->create();

    $file = UploadedFile::fake()->image('avatar.png', 800, 600);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', $file)
        ->call('storeUploadedFile')
        ->call('selectTargetFormat', 'jpg')
        ->set('options.quality', 'high')
        ->call('convert')
        ->assertSet('step', 'converting');

    expect(ConversionJob::query()->count())->toBe(1);
});
```

Адаптировать property/method names под текущий `DashboardConverter` из Phase 06–08.

Тест должен упасть до CONV-148.

### Implementation

Только добавить тест.

Не реализовывать `convert()` в этой задаче.

### Acceptance criteria

- Тест существует.
- Тест проходит полный подготовительный UI state: upload → target → settings.
- Тест вызывает `convert`.
- Тест ожидает `step = converting`.
- Тест ожидаемо падает до реализации.

### Definition of Done

- Тест написан первым.
- Тест ожидаемо падает.
- Нет реализации.
- Коммит: `CONV-147: Add convert button test`

### Files likely touched

```txt
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён после review падающего test-first изменения, если workflow допускает отдельные red commits; иначе объединить с CONV-148 в одном MR, но сохранить порядок commits.

---

## CONV-148 — Connect Convert Button To CreateConversionJobAction

**Area:** Dashboard / Livewire / Conversion  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-148-connect-convert-button-to-create-conversion-job-action`  
**Base branch:** `develop`  
**Depends on:** CONV-147

### Goal

Реализовать `convert()` в `DashboardConverter`, чтобы он создавал `ConversionJob` через `CreateConversionJobAction` и переводил UI в состояние `converting`.

### TDD step

Использовать падающий тест из CONV-147.

### Implementation

В `DashboardConverter` добавить/обновить метод:

```php
public function convert(CreateConversionJobAction $createConversionJob): void
{
    $this->ensureCanConvert();

    $job = $createConversionJob->handle(
        user: auth()->user(),
        file: $this->currentFile(),
        targetFormat: $this->targetFormat,
        options: $this->options,
    );

    $this->currentConversionJobId = $job->id;
    $this->step = 'converting';
}
```

Если Livewire не inject-ит action в action method в текущей версии, использовать:

```php
$job = app(CreateConversionJobAction::class)->handle(...);
```

Добавить минимальные guards:

```txt
- current file exists;
- target format selected;
- options valid;
- user authenticated.
```

### Acceptance criteria

- `Convert Now` создаёт ConversionJob.
- Job создаётся через `CreateConversionJobAction`.
- `currentConversionJobId` set.
- `step` становится `converting`.
- Никакой прямой конвертации в Livewire.
- Тест CONV-147 проходит.

### Definition of Done

- Реализация минимальная.
- Test passes.
- `composer test` passes.
- Коммит: `CONV-148: Connect convert button to CreateConversionJobAction`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
resources/views/livewire/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-149 — Add Duplicate Convert Protection

**Area:** Dashboard / Livewire / Safety  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-149-add-duplicate-convert-protection`  
**Base branch:** `develop`  
**Depends on:** CONV-148

### Goal

Защитить UI от двойного клика по `Convert Now`, чтобы не создавались duplicate jobs.

### TDD step

Livewire test:

```php
it('does not create duplicate conversion jobs on repeated convert calls', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->setValidUploadedImageState()
        ->call('selectTargetFormat', 'jpg')
        ->set('options.quality', 'high');

    $component->call('convert');
    $component->call('convert');

    expect(ConversionJob::query()->count())->toBe(1);
});
```

Если helper `setValidUploadedImageState()` не существует, подготовить состояние явно.

### Implementation

В `convert()` добавить guard:

```php
if ($this->step === 'converting' || $this->currentConversionJobId !== null) {
    return;
}
```

В Blade:

```blade
<x-button wire:click="convert" wire:loading.attr="disabled" :disabled="$step === 'converting'">
    Convert Now
</x-button>
```

### Acceptance criteria

- Повторный вызов `convert()` не создаёт второй job.
- Button disabled во время Livewire request.
- Button disabled в converting state.
- Тест проходит.

### Definition of Done

- Тест написан.
- Guard добавлен.
- UI disabled state добавлен.
- Tests pass.
- Коммит: `CONV-149: Add duplicate convert protection`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
resources/views/livewire/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-150 — Add Converting State Test

**Area:** Dashboard / Livewire / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-150-add-converting-state-test`  
**Base branch:** `develop`  
**Depends on:** CONV-149

### Goal

Написать тест: когда `DashboardConverter` находится в состоянии `converting`, пользователь видит понятный converting UI.

### TDD step

Livewire test:

```php
it('renders converting state while conversion is processing', function () {
    $user = User::factory()->create();
    $job = ConversionJob::factory()->for($user)->processing()->create([
        'source_format' => 'png',
        'target_format' => 'jpg',
    ]);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('step', 'converting')
        ->set('currentConversionJobId', $job->id)
        ->assertSee('Converting')
        ->assertSee('PNG')
        ->assertSee('JPG');
});
```

Тест должен упасть до CONV-151, если UI ещё не отображает state.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Тест проверяет visible converting message.
- Тест проверяет source/target formats.
- Тест ожидаемо падает до реализации.

### Definition of Done

- Тест написан.
- Тест ожидаемо падает.
- Коммит: `CONV-150: Add converting state test`

### Files likely touched

```txt
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop` или объединить с CONV-151, если команда не принимает red-only MR.

---

## CONV-151 — Implement Converting State UI

**Area:** Dashboard / Livewire / UI  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-151-implement-converting-state-ui`  
**Base branch:** `develop`  
**Depends on:** CONV-150

### Goal

Добавить UI для состояния `converting`.

### TDD step

Использовать падающий тест из CONV-150.

### Implementation

В Blade добавить conditional state:

```blade
@if ($step === 'converting')
    <x-card>
        <div class="space-y-4 text-center">
            <div class="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-purple-200 border-t-purple-600"></div>

            <div>
                <h2>Converting your file</h2>
                <p>{{ strtoupper($currentJob->source_format) }} → {{ strtoupper($currentJob->target_format) }}</p>
            </div>

            <p>Please keep this page open while we prepare your file.</p>
        </div>
    </x-card>
@endif
```

Не показывать progress percentage, если backend не даёт реальный progress. Лучше честный spinner, чем фейковый progress.

### Acceptance criteria

- Converting state visible.
- Shows source → target.
- Shows spinner/loading indicator.
- Does not show fake percentage unless real progress exists.
- Test CONV-150 passes.

### Definition of Done

- UI добавлен.
- Тест проходит.
- `npm run build` passes.
- Коммит: `CONV-151: Implement converting state UI`

### Files likely touched

```txt
resources/views/livewire/dashboard-converter.blade.php
app/Livewire/DashboardConverter.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-152 — Add Job Status Polling Test

**Area:** Dashboard / Livewire / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-152-add-job-status-polling-test`  
**Base branch:** `develop`  
**Depends on:** CONV-151

### Goal

Написать тесты для метода refresh/polling, который переводит UI из `converting` в `completed` или `failed` в зависимости от статуса `ConversionJob`.

### TDD step

Livewire tests:

```php
it('moves to completed step when current job is completed', function () {
    $user = User::factory()->create();
    $job = ConversionJob::factory()->for($user)->completed()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('step', 'converting')
        ->set('currentConversionJobId', $job->id)
        ->call('refreshConversionStatus')
        ->assertSet('step', 'completed');
});
```

```php
it('moves to failed step when current job is failed', function () {
    $user = User::factory()->create();
    $job = ConversionJob::factory()->for($user)->failed()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('step', 'converting')
        ->set('currentConversionJobId', $job->id)
        ->call('refreshConversionStatus')
        ->assertSet('step', 'failed');
});
```

Тесты должны упасть до CONV-153.

### Implementation

Только добавить тесты.

### Acceptance criteria

- Completed polling test exists.
- Failed polling test exists.
- Tests expect state transition.
- Tests fail before implementation.

### Definition of Done

- Тесты написаны.
- Тесты ожидаемо падают.
- Коммит: `CONV-152: Add job status polling test`

### Files likely touched

```txt
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделать MR или объединить с CONV-153.

---

## CONV-153 — Implement Job Status Polling

**Area:** Dashboard / Livewire / Polling  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-153-implement-job-status-polling`  
**Base branch:** `develop`  
**Depends on:** CONV-152

### Goal

Реализовать polling current conversion job status.

### TDD step

Использовать падающие тесты из CONV-152.

### Implementation

В `DashboardConverter`:

```php
public function refreshConversionStatus(): void
{
    if ($this->currentConversionJobId === null) {
        return;
    }

    $job = ConversionJob::query()
        ->where('user_id', auth()->id())
        ->find($this->currentConversionJobId);

    if (! $job) {
        return;
    }

    if ($job->status === ConversionStatus::Completed) {
        $this->step = 'completed';
        return;
    }

    if ($job->status === ConversionStatus::Failed) {
        $this->step = 'failed';
        return;
    }
}
```

В Blade включить polling только для converting state:

```blade
@if ($step === 'converting')
    <div wire:poll.2s="refreshConversionStatus">
        ...
    </div>
@endif
```

### Acceptance criteria

- Completed job changes UI to completed.
- Failed job changes UI to failed.
- Polling ignores missing job safely.
- Polling only active in converting state.
- Tests pass.

### Definition of Done

- Polling method добавлен.
- Blade `wire:poll` добавлен.
- Tests pass.
- Коммит: `CONV-153: Implement job status polling`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
resources/views/livewire/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-154 — Add Completed State Test

**Area:** Dashboard / Livewire / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-154-add-completed-state-test`  
**Base branch:** `develop`  
**Depends on:** CONV-153

### Goal

Написать тест: completed state показывает result file и download action.

### TDD step

Livewire test:

```php
it('renders completed state with download action', function () {
    $user = User::factory()->create();
    $resultFile = FileRecord::factory()->for($user)->create([
        'original_name' => 'avatar.jpg',
        'extension' => 'jpg',
    ]);

    $job = ConversionJob::factory()
        ->for($user)
        ->completed()
        ->create([
            'target_format' => 'jpg',
            'result_file_id' => $resultFile->id,
        ]);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('step', 'completed')
        ->set('currentConversionJobId', $job->id)
        ->assertSee('Done')
        ->assertSee('avatar.jpg')
        ->assertSee('Download');
});
```

Тест должен упасть до CONV-155.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Тест проверяет success message.
- Тест проверяет result file name.
- Тест проверяет download CTA.
- Тест падает до реализации.

### Definition of Done

- Тест написан.
- Тест ожидаемо падает.
- Коммит: `CONV-154: Add completed state test`

### Files likely touched

```txt
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделать MR или объединить с CONV-155.

---

## CONV-155 — Implement Completed State UI

**Area:** Dashboard / Livewire / UI  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-155-implement-completed-state-ui`  
**Base branch:** `develop`  
**Depends on:** CONV-154

### Goal

Добавить completed state UI с result summary и download button.

### TDD step

Использовать падающий тест из CONV-154.

### Implementation

В `DashboardConverter` добавить computed/current job helper:

```php
public function getCurrentJobProperty(): ?ConversionJob
{
    if ($this->currentConversionJobId === null) {
        return null;
    }

    return ConversionJob::query()
        ->with(['sourceFile', 'resultFile'])
        ->where('user_id', auth()->id())
        ->find($this->currentConversionJobId);
}
```

В Blade:

```blade
@if ($step === 'completed' && $this->currentJob)
    <x-card>
        <h2>Done! Your file is ready.</h2>

        <div>
            <x-file-icon :format="$this->currentJob->target_format" />
            <span>{{ $this->currentJob->resultFile?->original_name }}</span>
        </div>

        <x-button href="{{ route('conversions.download', $this->currentJob) }}">
            Download
        </x-button>

        <x-button variant="secondary" wire:click="convertAnother">
            Convert another file
        </x-button>

        <x-button variant="ghost" wire:click="convertWithDifferentSettings">
            Change settings
        </x-button>
    </x-card>
@endif
```

Download route будет реализован в CONV-159. На этом шаге link может существовать, но тест download route будет позже.

### Acceptance criteria

- Completed state visible.
- Result file name visible.
- Download button visible.
- Convert another action visible.
- Change settings action visible.
- Test CONV-154 passes.

### Definition of Done

- Completed UI добавлен.
- Test passes.
- `npm run build` passes.
- Коммит: `CONV-155: Implement completed state UI`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
resources/views/livewire/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-156 — Add Failed State Test

**Area:** Dashboard / Livewire / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-156-add-failed-state-test`  
**Base branch:** `develop`  
**Depends on:** CONV-155

### Goal

Написать тест: failed conversion показывает понятное сообщение и действия для пользователя.

### TDD step

Livewire test:

```php
it('renders failed state with readable message', function () {
    $user = User::factory()->create();

    $job = ConversionJob::factory()->for($user)->failed()->create([
        'error_code' => 'driver_failed',
        'error_message' => 'Imagick internal raw error',
    ]);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('step', 'failed')
        ->set('currentConversionJobId', $job->id)
        ->assertSee('Conversion failed')
        ->assertSee('Try another file')
        ->assertDontSee('Imagick internal raw error');
});
```

Тест должен упасть до CONV-157.

### Implementation

Только добавить тест.

### Acceptance criteria

- Failed state test exists.
- Raw error is not rendered.
- User-friendly message expected.
- Test fails before implementation.

### Definition of Done

- Тест написан.
- Тест ожидаемо падает.
- Коммит: `CONV-156: Add failed state test`

### Files likely touched

```txt
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделать MR или объединить с CONV-157.

---

## CONV-157 — Implement Failed State UI

**Area:** Dashboard / Livewire / UI  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-157-implement-failed-state-ui`  
**Base branch:** `develop`  
**Depends on:** CONV-156

### Goal

Добавить failed state UI без утечки raw exception/error_message.

### TDD step

Использовать падающий тест из CONV-156.

### Implementation

В Blade:

```blade
@if ($step === 'failed')
    <x-card>
        <div class="space-y-4 text-center">
            <x-badge variant="danger">Failed</x-badge>

            <h2>Conversion failed</h2>
            <p>We could not convert this file. Try another file or change settings.</p>

            <div class="flex justify-center gap-3">
                <x-button wire:click="convertWithDifferentSettings">
                    Change settings
                </x-button>

                <x-button variant="secondary" wire:click="convertAnother">
                    Try another file
                </x-button>
            </div>
        </div>
    </x-card>
@endif
```

Не выводить `$job->error_message` пользователю в MVP.  
Можно логировать raw error в backend job из Phase 09/10.

### Acceptance criteria

- Failed state visible.
- User-friendly message visible.
- Raw error message not visible.
- Change settings action visible.
- Try another file action visible.
- Test CONV-156 passes.

### Definition of Done

- Failed UI добавлен.
- Test passes.
- Коммит: `CONV-157: Implement failed state UI`

### Files likely touched

```txt
resources/views/livewire/dashboard-converter.blade.php
app/Livewire/DashboardConverter.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-158 — Add Result Download Route Test

**Area:** Download / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-158-add-result-download-route-test`  
**Base branch:** `develop`  
**Depends on:** CONV-157

### Goal

Написать feature tests для download route.

### TDD step

Feature tests:

```php
it('allows owner to download completed conversion result', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    Storage::disk('local')->put('conversions/result.jpg', 'fake-image');

    $resultFile = FileRecord::factory()->for($user)->create([
        'stored_path' => 'conversions/result.jpg',
        'original_name' => 'result.jpg',
        'mime_type' => 'image/jpeg',
    ]);

    $job = ConversionJob::factory()
        ->for($user)
        ->completed()
        ->create(['result_file_id' => $resultFile->id]);

    $this->actingAs($user)
        ->get(route('conversions.download', $job))
        ->assertOk();
});
```

Security tests:

```php
it('does not allow another user to download result', ...);
it('does not allow downloading non completed conversion', ...);
it('does not allow downloading failed conversion', ...);
```

Тесты должны упасть до CONV-159.

### Implementation

Только добавить tests.

### Acceptance criteria

- Owner download test exists.
- Other user forbidden test exists.
- Non-completed download blocked test exists.
- Tests fail before route implementation.

### Definition of Done

- Тесты написаны.
- Тесты ожидаемо падают.
- Коммит: `CONV-158: Add result download route test`

### Files likely touched

```txt
tests/Feature/ConversionDownloadTest.php
```

После этого сделать MR или объединить с CONV-159.

---

## CONV-159 — Implement Result Download Route

**Area:** Download / Web  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-159-implement-result-download-route`  
**Base branch:** `develop`  
**Depends on:** CONV-158

### Goal

Реализовать безопасный download route для completed conversion result.

### TDD step

Использовать падающие тесты из CONV-158.

### Implementation

Route:

```php
Route::get('/conversions/{conversion}/download', DownloadConversionResultController::class)
    ->middleware('auth')
    ->name('conversions.download');
```

Controller:

```php
final class DownloadConversionResultController
{
    public function __invoke(ConversionJob $conversion)
    {
        abort_unless($conversion->user_id === auth()->id(), 403);
        abort_unless($conversion->status === ConversionStatus::Completed, 404);
        abort_unless($conversion->resultFile !== null, 404);
        abort_if($conversion->resultFile->isExpired(), 410);

        $file = $conversion->resultFile;

        abort_unless(Storage::disk($file->disk ?? 'local')->exists($file->stored_path), 404);

        return Storage::disk($file->disk ?? 'local')->download(
            $file->stored_path,
            $file->original_name,
            ['Content-Type' => $file->mime_type]
        );
    }
}
```

Если `files.disk` ещё нет, использовать configured default disk и не добавлять migration в этой задаче.

### Acceptance criteria

- Owner can download completed result.
- Other user gets 403.
- Queued/processing/failed conversion cannot be downloaded.
- Missing result file returns 404.
- Expired result returns 410 or clear error.
- Tests pass.

### Definition of Done

- Route added.
- Controller added.
- Security checks added.
- Tests pass.
- Коммит: `CONV-159: Implement result download route`

### Files likely touched

```txt
routes/web.php
app/Http/Controllers/DownloadConversionResultController.php
tests/Feature/ConversionDownloadTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-160 — Add Convert Another Action

**Area:** Dashboard / Livewire / UX  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-160-add-convert-another-action`  
**Base branch:** `develop`  
**Depends on:** CONV-159

### Goal

Добавить действие `Convert another file`, которое сбрасывает dashboard flow в начальное upload-состояние.

### TDD step

Livewire test:

```php
it('resets dashboard state when user chooses convert another file', function () {
    $user = User::factory()->create();
    $job = ConversionJob::factory()->for($user)->completed()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('step', 'completed')
        ->set('currentConversionJobId', $job->id)
        ->set('targetFormat', 'jpg')
        ->set('options.quality', 'high')
        ->call('convertAnother')
        ->assertSet('step', 'upload')
        ->assertSet('currentConversionJobId', null)
        ->assertSet('targetFormat', null)
        ->assertSet('options', []);
});
```

### Implementation

В `DashboardConverter`:

```php
public function convertAnother(): void
{
    $this->reset([
        'step',
        'currentFileId',
        'targetFormat',
        'options',
        'currentConversionJobId',
    ]);

    $this->step = 'upload';
}
```

Если upload property нельзя reset-ить через `reset`, очистить явно.

### Acceptance criteria

- Action exists.
- State resets to upload.
- Previous job/file/options no longer selected.
- Test passes.

### Definition of Done

- Тест написан.
- Action implemented.
- Test passes.
- Коммит: `CONV-160: Add convert another action`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
resources/views/livewire/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-161 — Add Convert With Different Settings Action

**Area:** Dashboard / Livewire / UX  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-161-add-convert-with-different-settings-action`  
**Base branch:** `develop`  
**Depends on:** CONV-160

### Goal

Добавить действие `Change settings`, которое возвращает пользователя к settings step с тем же файлом и target format.

### TDD step

Livewire test:

```php
it('returns to settings while keeping file target and options', function () {
    $user = User::factory()->create();
    $file = FileRecord::factory()->for($user)->create(['extension' => 'png']);
    $job = ConversionJob::factory()->for($user)->completed()->create();

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('step', 'completed')
        ->set('currentFileId', $file->id)
        ->set('targetFormat', 'jpg')
        ->set('options.quality', 'high')
        ->set('currentConversionJobId', $job->id)
        ->call('convertWithDifferentSettings')
        ->assertSet('step', 'settings')
        ->assertSet('currentFileId', $file->id)
        ->assertSet('targetFormat', 'jpg')
        ->assertSet('options.quality', 'high')
        ->assertSet('currentConversionJobId', null);
});
```

### Implementation

В `DashboardConverter`:

```php
public function convertWithDifferentSettings(): void
{
    $this->currentConversionJobId = null;
    $this->step = 'settings';
}
```

Guard:

```txt
If current file or target format missing, fallback to upload/format step.
```

### Acceptance criteria

- Action exists.
- Keeps current file.
- Keeps target format.
- Keeps options.
- Clears current conversion job.
- Moves to settings.
- Test passes.

### Definition of Done

- Тест написан.
- Action implemented.
- Test passes.
- Коммит: `CONV-161: Add convert with different settings action`

### Files likely touched

```txt
app/Livewire/DashboardConverter.php
resources/views/livewire/dashboard-converter.blade.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-162 — Add Result Expired UI Handling

**Area:** Dashboard / Livewire / Expiration  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-162-add-result-expired-ui-handling`  
**Base branch:** `develop`  
**Depends on:** CONV-161

### Goal

Показать понятное сообщение, если result file expired/deleted и его уже нельзя скачать.

### TDD step

Livewire test:

```php
it('shows expired result message when completed result file is expired', function () {
    $user = User::factory()->create();
    $resultFile = FileRecord::factory()->for($user)->expired()->create([
        'original_name' => 'old-result.jpg',
    ]);

    $job = ConversionJob::factory()
        ->for($user)
        ->completed()
        ->create(['result_file_id' => $resultFile->id]);

    Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('step', 'completed')
        ->set('currentConversionJobId', $job->id)
        ->assertSee('This result has expired')
        ->assertDontSee('Download');
});
```

Если `expired()` factory state ещё отсутствует, добавить его в задаче.

### Implementation

В completed state:

```blade
@if ($this->currentJob->resultFile?->isExpired())
    <x-card>
        <h2>This result has expired</h2>
        <p>Upload the original file again to create a new result.</p>
        <x-button wire:click="convertAnother">Convert another file</x-button>
    </x-card>
@else
    ...download UI...
@endif
```

В `FileRecord` добавить helper, если его ещё нет:

```php
public function isExpired(): bool
{
    return $this->expires_at !== null && $this->expires_at->isPast();
}
```

### Acceptance criteria

- Expired result shows clear message.
- Download button hidden for expired result.
- Convert another action visible.
- Download route also blocks expired result from CONV-159.
- Test passes.

### Definition of Done

- Тест написан.
- UI handling implemented.
- FileRecord helper added if needed.
- Test passes.
- Коммит: `CONV-162: Add result expired UI handling`

### Files likely touched

```txt
app/Models/FileRecord.php
resources/views/livewire/dashboard-converter.blade.php
database/factories/FileRecordFactory.php
tests/Feature/Livewire/DashboardConverterTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-163 — Add Convert Flow Integration Test

**Area:** Dashboard / Conversion / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-163-add-convert-flow-integration-test`  
**Base branch:** `develop`  
**Depends on:** CONV-162

### Goal

Добавить один полный happy-path integration test для web conversion flow.

### TDD step

Feature/Livewire integration test:

```php
it('allows user to upload configure convert and download image result', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $upload = UploadedFile::fake()->image('avatar.png', 800, 600);

    $component = Livewire::actingAs($user)
        ->test(DashboardConverter::class)
        ->set('upload', $upload)
        ->call('storeUploadedFile')
        ->assertSet('step', 'format')
        ->call('selectTargetFormat', 'jpg')
        ->assertSet('step', 'settings')
        ->set('options.quality', 'high')
        ->call('convert')
        ->assertSet('step', 'converting');

    $job = ConversionJob::query()->firstOrFail();

    app(ProcessConversionJob::class)->handle($job->id);

    $component
        ->call('refreshConversionStatus')
        ->assertSet('step', 'completed')
        ->assertSee('Download');

    $this->actingAs($user)
        ->get(route('conversions.download', $job->fresh()))
        ->assertOk();
});
```

Адаптировать `ProcessConversionJob` invocation под реальную реализацию queue job из Phase 09.

### Implementation

Добавить тест. Если тест показывает мелкие несостыковки между фазами, исправить минимально:

```txt
- method names;
- route names;
- factory states;
- missing relationships;
- storage path assumptions.
```

Не добавлять billing/credits/history/API.

### Acceptance criteria

- Full web flow test exists.
- Upload → target → settings → convert → completed → download works.
- Uses real MVP image driver from Phase 10.
- No credits involved.
- No history table involved.
- Test passes.

### Definition of Done

- Integration test written.
- Minimal fixes applied if needed.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.
- Коммит: `CONV-163: Add convert flow integration test`

### Files likely touched

```txt
tests/Feature/ConversionFlowTest.php
app/Livewire/DashboardConverter.php
routes/web.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 11 Completion Criteria

Phase 11 завершена, когда:

```txt
- CONV-147–CONV-163 выполнены;
- Convert Now creates ConversionJob through CreateConversionJobAction;
- duplicate convert clicks do not create duplicate jobs;
- converting state renders;
- polling updates job status;
- completed state renders;
- failed state renders without raw exception leakage;
- owner can download completed result;
- other user cannot download result;
- non-completed conversion cannot be downloaded;
- expired result is handled in UI and download route;
- convert another resets dashboard state;
- change settings returns to settings state;
- full web conversion happy-path test passes;
- composer test passes;
- composer lint passes;
- npm run build passes;
- no billing/credits/API/history table added.
```

---

# 11. Что нельзя делать в Phase 11

Без отдельной задачи нельзя:

```txt
- создавать Recent Conversions table;
- создавать /history page;
- добавлять CreditLedger;
- добавлять ConversionCostEstimator;
- списывать credits;
- устанавливать Laravel Cashier;
- создавать billing page;
- создавать API routes;
- создавать OpenAPI docs;
- добавлять API keys;
- добавлять batch conversion;
- добавлять cancel conversion;
- добавлять retry queue mechanism;
- добавлять websocket/broadcast progress;
- добавлять fake progress percentage;
- добавлять public share links;
- добавлять email notifications;
- менять converter schemas;
- менять real drivers без failing test;
- добавлять React/Vue/Inertia.
```

---

# 12. Recommended Execution Order

```txt
CONV-147 Add Convert Button Test
CONV-148 Connect Convert Button To CreateConversionJobAction
CONV-149 Add Duplicate Convert Protection
CONV-150 Add Converting State Test
CONV-151 Implement Converting State UI
CONV-152 Add Job Status Polling Test
CONV-153 Implement Job Status Polling
CONV-154 Add Completed State Test
CONV-155 Implement Completed State UI
CONV-156 Add Failed State Test
CONV-157 Implement Failed State UI
CONV-158 Add Result Download Route Test
CONV-159 Implement Result Download Route
CONV-160 Add Convert Another Action
CONV-161 Add Convert With Different Settings Action
CONV-162 Add Result Expired UI Handling
CONV-163 Add Convert Flow Integration Test
```

---

# 13. Release

После завершения Phase 11:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh --seed

git checkout -b release/v0.1.11-phase11-convert-ui-flow
git push -u origin release/v0.1.11-phase11-convert-ui-flow
```

После этого сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.11-phase11-convert-ui-flow -m "File Converter Phase 11 convert UI flow"
git push origin v0.1.11-phase11-convert-ui-flow
```
