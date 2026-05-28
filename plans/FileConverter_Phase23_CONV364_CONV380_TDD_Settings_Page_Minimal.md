# File Converter — Phase 23 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 23 — Settings Page Minimal**  
Диапазон задач: **CONV-364 → CONV-380**  
Основа нумерации: Phase 22 завершилась на `CONV-363`, поэтому Phase 23 начинается с `CONV-364`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 23 соответствует блоку:

```txt
Phase 23 — Settings Page Minimal
```

Правильный диапазон Phase 23:

```txt
CONV-364 — Create Settings Page Route And Shell
CONV-365 — Create AccountSettingsForm Component Skeleton
CONV-366 — Test Settings Shows Current Profile Data
CONV-367 — Implement Profile Data Binding
CONV-368 — Test User Can Update Profile Name
CONV-369 — Implement Profile Name Update
CONV-370 — Test Email Is Displayed Read-Only
CONV-371 — Implement Read-Only Email Display
CONV-372 — Create Conversion Preferences Schema
CONV-373 — Test Default Image Quality Preference
CONV-374 — Implement Default Image Quality Preference
CONV-375 — Test Remove Metadata Preference
CONV-376 — Implement Remove Metadata Preference
CONV-377 — Test Preferences Apply To Converter Defaults
CONV-378 — Implement Preferences Application To Options Schema
CONV-379 — Add Settings Success And Validation UI
CONV-380 — Add Settings Page Final Smoke Tests
```

Phase 23 добавляет минимальную страницу пользовательских настроек.

Главное правило:

```txt
Settings page is not an account-management monster.
It only covers profile name and default conversion preferences needed by MVP.
```

---

# 2. Цель Phase 23

Phase 23 добавляет `/settings`, где пользователь может:

```txt
- видеть имя и email аккаунта;
- изменить display name;
- видеть email как read-only поле;
- задать default image quality;
- задать default remove metadata preference;
- сохранить настройки в users.settings;
- получить success/error feedback;
- увидеть, что эти defaults применяются в converter settings step.
```

Это минимальная settings page для MVP.

Phase 23 не должна становиться биллингом, security center, API dashboard, device manager или profile social page.

---

# 3. Scope Phase 23

## Входит

```txt
- /settings route;
- settings Blade shell;
- AccountSettingsForm Livewire component;
- current user profile data display;
- editable user name;
- read-only email display;
- users.settings JSON usage;
- default image quality preference;
- default remove metadata preference;
- validation and success messages;
- applying user defaults to converter options schema;
- final smoke tests.
```

## Не входит

```txt
- password change;
- email change;
- email verification flow;
- two-factor authentication;
- OAuth/social accounts;
- delete account;
- export personal data;
- billing settings;
- invoice settings;
- API key settings;
- device/session management;
- notification preferences beyond conversion defaults;
- team/workspace settings;
- admin settings;
- new converters;
- new billing rules;
- new frontend framework.
```

Password/security settings будут отдельной фазой, если понадобятся.  
Billing settings уже относятся к Billing Page.  
API keys уже относятся к API Foundation / API Keys area.

---

# 4. Critical Decisions

## 4.1. Email is read-only in MVP

Не делать смену email в Phase 23.

Смена email требует:

```txt
- validation uniqueness;
- email verification;
- confirmation flow;
- security notification;
- possible session invalidation;
- edge cases around pending email.
```

Это слишком много для минимальной settings page.

В Phase 23 email только показывается:

```txt
Email
alex@example.com
```

## 4.2. Name update is allowed

Display name можно менять безопасно.

Правило:

```txt
name required
name max 255
trim before save
```

Если в проекте используется другое поле (`username`, `display_name`), адаптировать задачу к фактической модели. Не создавать новое поле без необходимости.

## 4.3. Preferences live in users.settings JSON

Default conversion preferences хранятся в:

```txt
users.settings
```

Ожидаемый shape:

```json
{
  "conversion": {
    "image_quality": "high",
    "remove_metadata": true
  }
}
```

Не создавать отдельную таблицу `user_preferences` в Phase 23.

## 4.4. Preferences must affect real converter forms

Настройки не должны быть декоративными.

Если пользователь выбрал:

```txt
image_quality = best
remove_metadata = true
```

то при открытии PNG → JPG / JPG → WEBP / PNG → WEBP настройки converter form должны получать эти defaults.

Нельзя просто сохранить JSON и нигде его не использовать.

## 4.5. Preferences must not override explicit converter options

User preferences применяются только как defaults.

Правильно:

```txt
User default image_quality = best
Converter form opens with quality = best
User manually changes quality = medium
Job stores medium
```

Неправильно:

```txt
User manually changes quality = medium
Backend silently overrides to best because user default is best
```

## 4.6. Settings page must use existing UI foundation

Использовать существующие компоненты:

```txt
<x-card>
<x-button>
<x-badge>
form controls from UI foundation
```

Не создавать отдельный визуальный стиль.

## 4.7. Settings must be user-scoped

Пользователь может читать и менять только свои настройки.

Нельзя принимать `user_id` из request/Livewire state.

Правильно:

```php
$user = auth()->user();
```

---

# 5. Architecture Rules

## 5.1. Dedicated Livewire component

Рекомендуемый компонент:

```txt
app/Livewire/AccountSettingsForm.php
resources/views/livewire/account-settings-form.blade.php
```

Не добавлять settings logic в DashboardConverter.

## 5.2. Settings read/write should be encapsulated

Минимально допустимо работать с `$user->settings` прямо в компоненте.

Но предпочтительно добавить небольшой helper/action:

```txt
UpdateUserSettingsAction
```

Если action добавляется, он должен быть маленьким и не превращаться в SettingsService на всё.

## 5.3. Converter defaults should be applied close to schema creation

User preferences должны применяться там, где формируется options schema/defaults.

Правильно:

```txt
GetConverterOptionsSchemaAction
или ConverterOptionsDefaultsResolver
```

Неправильно:

```txt
Blade component вручную проверяет auth()->user()->settings и подменяет поля.
```

UI должен получать уже подготовленную schema/defaults.

## 5.4. No billing mutation in settings

Settings page не меняет:

```txt
plan
credits
subscription
credit packs
invoices
```

Только profile name и conversion preferences.

## 5.5. No API key management in settings

API keys — отдельный раздел. Не смешивать.

---

# 6. GitFlow для Phase 23

## Base branch

Все задачи Phase 23 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-364-create-settings-page-route-and-shell
feature/CONV-369-implement-profile-name-update
feature/CONV-378-implement-preferences-application-to-options-schema
```

## Commit format

```txt
CONV-364: Create settings page route and shell
CONV-369: Implement profile name update
CONV-378: Implement preferences application to options schema
```

## Release branch

После выполнения `CONV-364`–`CONV-380`:

```txt
release/v0.1.23-phase23-settings-page-minimal
```

## Tag

После merge release branch в `main`:

```txt
v0.1.23-phase23-settings-page-minimal
```

---

# 7. TDD Rules for Phase 23

## Для доступа

Тестировать:

```txt
- guest cannot access /settings;
- authenticated user can access /settings;
- page renders AccountSettingsForm.
```

## Для profile data

Тестировать:

```txt
- settings page shows current name;
- settings page shows current email;
- email is read-only;
- user can update own name;
- name is trimmed;
- invalid name rejected.
```

## Для preferences

Тестировать:

```txt
- user can save default image quality;
- invalid image quality rejected;
- user can save remove metadata default;
- preferences persist in users.settings;
- saved preferences apply to converter schema defaults;
- explicit user-selected conversion options are not overwritten after selection.
```

## Для UI feedback

Тестировать:

```txt
- success message appears after save;
- validation errors appear for invalid input;
- settings form remains user-scoped.
```

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Settings / Livewire / Profile / Preferences / Tests
Type: Test / Feature / Component / Validation / Integration
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

# 9. Phase 23 Atomic Tasks

---

## CONV-364 — Create Settings Page Route And Shell

**Area:** Settings / Routes / Blade  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-364-create-settings-page-route-and-shell`  
**Base branch:** `develop`  
**Depends on:** CONV-363

### Goal

Создать защищённую страницу `/settings` с базовым shell.

### TDD step

Feature test:

```php
it('allows authenticated user to access settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings')
        ->assertOk()
        ->assertSee('Settings');
});
```

Guest test:

```php
it('redirects guest from settings page to login', function () {
    $this->get('/settings')
        ->assertRedirect('/login');
});
```

Тесты должны упасть до реализации route/view.

### Implementation

Добавить protected route:

```php
Route::middleware(['auth'])
    ->view('/settings', 'settings.index')
    ->name('settings');
```

Создать view:

```txt
resources/views/settings/index.blade.php
```

Минимальный контент:

```blade
<x-app-layout>
    <x-page-header title="Settings" />

    <livewire:account-settings-form />
</x-app-layout>
```

Если `x-page-header` ещё нет, использовать обычный heading.

### Acceptance criteria

- `/settings` exists.
- Guest redirected to login.
- Authenticated user gets 200.
- Page contains `Settings`.
- Page uses existing app layout.
- No settings form logic yet beyond placeholder.

### Definition of Done

- Тест написан первым.
- Route добавлен.
- View добавлена.
- Tests pass.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.
- Коммит: `CONV-364: Create settings page route and shell`

### Files likely touched

```txt
routes/web.php
resources/views/settings/index.blade.php
tests/Feature/Settings/SettingsPageTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-365 — Create AccountSettingsForm Component Skeleton

**Area:** Settings / Livewire  
**Type:** Component  
**Priority:** P0  
**Branch:** `feature/CONV-365-create-account-settings-form-component-skeleton`  
**Base branch:** `develop`  
**Depends on:** CONV-364

### Goal

Создать skeleton Livewire-компонента `AccountSettingsForm`.

### TDD step

Livewire test:

```php
it('renders account settings form component', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->assertSee('Account Settings');
});
```

Тест должен упасть до создания компонента.

### Implementation

Создать компонент:

```bash
php artisan make:livewire AccountSettingsForm
```

Component skeleton:

```php
final class AccountSettingsForm extends Component
{
    public function render(): View
    {
        return view('livewire.account-settings-form');
    }
}
```

View skeleton:

```blade
<x-card>
    <h2>Account Settings</h2>
</x-card>
```

Подключить компонент на `/settings`, если ещё не подключён.

### Acceptance criteria

- `AccountSettingsForm` exists.
- Component renders.
- `/settings` includes component.
- No profile update logic yet.
- Livewire test passes.

### Definition of Done

- Тест написан первым.
- Component создан.
- View создана.
- Tests pass.
- Коммит: `CONV-365: Create AccountSettingsForm component skeleton`

### Files likely touched

```txt
app/Livewire/AccountSettingsForm.php
resources/views/livewire/account-settings-form.blade.php
resources/views/settings/index.blade.php
tests/Feature/Livewire/AccountSettingsFormTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-366 — Test Settings Shows Current Profile Data

**Area:** Settings / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-366-test-settings-shows-current-profile-data`  
**Base branch:** `develop`  
**Depends on:** CONV-365

### Goal

Написать падающий тест: settings form показывает текущее имя и email пользователя.

### TDD step

Livewire test:

```php
it('shows current user profile data in settings form', function () {
    $user = User::factory()->create([
        'name' => 'Alex Johnson',
        'email' => 'alex@example.com',
    ]);

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->assertSet('name', 'Alex Johnson')
        ->assertSee('alex@example.com');
});
```

Тест должен упасть до CONV-367, если component ещё не bind-ит user data.

### Implementation

Только добавить тест.

### Acceptance criteria

- Тест существует.
- Проверяет current user name.
- Проверяет current user email visible.
- Тест ожидаемо падает до реализации.

### Definition of Done

- Тест добавлен.
- Тест падает до реализации.
- Коммит: `CONV-366: Test settings shows current profile data`

### Files likely touched

```txt
tests/Feature/Livewire/AccountSettingsFormTest.php
```

После этого сделай MR в `develop`. Merge разрешён после подтверждения, что новый тест падает ожидаемо.

---

## CONV-367 — Implement Profile Data Binding

**Area:** Settings / Livewire / Profile  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-367-implement-profile-data-binding`  
**Base branch:** `develop`  
**Depends on:** CONV-366

### Goal

Загрузить текущие данные пользователя в `AccountSettingsForm`.

### TDD step

Использовать падающий тест из CONV-366.

### Implementation

В компоненте:

```php
public string $name = '';
public string $email = '';

public function mount(): void
{
    $user = auth()->user();

    $this->name = $user->name ?? '';
    $this->email = $user->email ?? '';
}
```

В view:

```blade
<label>Name</label>
<input wire:model="name" type="text">

<label>Email</label>
<input value="{{ $email }}" type="email" readonly disabled>
```

Email пока можно рендерить как readonly input или static text. Полный read-only behaviour проверит отдельная задача.

### Acceptance criteria

- Component loads current user name.
- Component loads current user email.
- Name field rendered.
- Email visible.
- Test CONV-366 passes.

### Definition of Done

- Profile binding реализован.
- Тест проходит.
- Коммит: `CONV-367: Implement profile data binding`

### Files likely touched

```txt
app/Livewire/AccountSettingsForm.php
resources/views/livewire/account-settings-form.blade.php
tests/Feature/Livewire/AccountSettingsFormTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-368 — Test User Can Update Profile Name

**Area:** Settings / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-368-test-user-can-update-profile-name`  
**Base branch:** `develop`  
**Depends on:** CONV-367

### Goal

Написать падающие тесты на обновление имени пользователя.

### TDD step

Livewire test:

```php
it('allows user to update profile name', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
    ]);

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->set('name', 'New Name')
        ->call('saveProfile')
        ->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('New Name');
});
```

Trim test:

```php
it('trims profile name before saving', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
    ]);

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->set('name', '  New Name  ')
        ->call('saveProfile');

    expect($user->fresh()->name)->toBe('New Name');
});
```

Validation test:

```php
it('requires profile name', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->set('name', '')
        ->call('saveProfile')
        ->assertHasErrors(['name' => 'required']);
});
```

Тесты должны упасть до CONV-369.

### Implementation

Только добавить тесты.

### Acceptance criteria

- Тест успешного обновления существует.
- Тест trim существует.
- Тест required validation существует.
- Тесты ожидаемо падают до реализации.

### Definition of Done

- Тесты добавлены.
- Тесты падают до реализации.
- Коммит: `CONV-368: Test user can update profile name`

### Files likely touched

```txt
tests/Feature/Livewire/AccountSettingsFormTest.php
```

После этого сделай MR в `develop`. Merge разрешён после подтверждения, что новые тесты падают ожидаемо.

---

## CONV-369 — Implement Profile Name Update

**Area:** Settings / Livewire / Profile  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-369-implement-profile-name-update`  
**Base branch:** `develop`  
**Depends on:** CONV-368

### Goal

Реализовать сохранение имени пользователя.

### TDD step

Использовать падающие тесты из CONV-368.

### Implementation

В `AccountSettingsForm`:

```php
public function saveProfile(): void
{
    $validated = $this->validate([
        'name' => ['required', 'string', 'max:255'],
    ]);

    $user = auth()->user();

    $user->forceFill([
        'name' => trim($validated['name']),
    ])->save();

    $this->dispatch('settings-saved', section: 'profile');
}
```

Если используется action:

```txt
app/Actions/Users/UpdateProfileAction.php
```

то Livewire вызывает action, но не обязательно усложнять MVP.

### Acceptance criteria

- User can update own name.
- Name is trimmed before save.
- Empty name rejected.
- Name longer than 255 rejected.
- Email not changed.
- Tests pass.

### Definition of Done

- Profile save реализован.
- Validation добавлена.
- Tests pass.
- Коммит: `CONV-369: Implement profile name update`

### Files likely touched

```txt
app/Livewire/AccountSettingsForm.php
resources/views/livewire/account-settings-form.blade.php
tests/Feature/Livewire/AccountSettingsFormTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-370 — Test Email Is Displayed Read-Only

**Area:** Settings / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-370-test-email-is-displayed-read-only`  
**Base branch:** `develop`  
**Depends on:** CONV-369

### Goal

Написать тесты, фиксирующие, что email показывается, но не редактируется в Phase 23.

### TDD step

Render test:

```php
it('displays email as read only on settings page', function () {
    $user = User::factory()->create([
        'email' => 'alex@example.com',
    ]);

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->assertSee('alex@example.com')
        ->assertSee('Email');
});
```

Mutation protection test:

```php
it('does not update email from settings form', function () {
    $user = User::factory()->create([
        'email' => 'alex@example.com',
    ]);

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->set('email', 'changed@example.com')
        ->call('saveProfile');

    expect($user->fresh()->email)->toBe('alex@example.com');
});
```

Если component не имеет public `$email` setter, адаптировать тест: проверить, что saveProfile не валидирует/не сохраняет email.

### Implementation

Только добавить тесты.

### Acceptance criteria

- Email visible.
- Email not editable by normal settings save.
- Email is not persisted from Livewire state.
- Тесты ожидаемо падают, если email сейчас меняется.

### Definition of Done

- Тесты добавлены.
- Тесты фиксируют read-only behavior.
- Коммит: `CONV-370: Test email is displayed read-only`

### Files likely touched

```txt
tests/Feature/Livewire/AccountSettingsFormTest.php
```

После этого сделай MR в `develop`. Merge разрешён после проверки ожидаемого поведения тестов.

---

## CONV-371 — Implement Read-Only Email Display

**Area:** Settings / Livewire / Profile  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-371-implement-read-only-email-display`  
**Base branch:** `develop`  
**Depends on:** CONV-370

### Goal

Сделать email явно read-only в UI и защитить от сохранения через component state.

### TDD step

Использовать тесты из CONV-370.

### Implementation

В component:

```php
public string $email = '';
```

Но в `saveProfile()` не использовать `$this->email`.

В Blade:

```blade
<label for="settings-email">Email</label>
<input
    id="settings-email"
    type="email"
    value="{{ $email }}"
    readonly
    disabled
>
<p>Email changes are not available in this MVP.</p>
```

Не добавлять email update form.

### Acceptance criteria

- Email displayed.
- Email input is disabled/readonly or rendered as static text.
- `saveProfile()` never saves email.
- Tests pass.

### Definition of Done

- Read-only email UI реализован.
- Save logic не трогает email.
- Tests pass.
- Коммит: `CONV-371: Implement read-only email display`

### Files likely touched

```txt
app/Livewire/AccountSettingsForm.php
resources/views/livewire/account-settings-form.blade.php
tests/Feature/Livewire/AccountSettingsFormTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-372 — Create Conversion Preferences Schema

**Area:** Settings / Preferences  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-372-create-conversion-preferences-schema`  
**Base branch:** `develop`  
**Depends on:** CONV-371

### Goal

Зафиксировать структуру `users.settings` для default conversion preferences.

### TDD step

Unit test:

```php
it('has default conversion preferences schema', function () {
    $defaults = config('converter.user_defaults');

    expect($defaults)->toHaveKey('image_quality');
    expect($defaults)->toHaveKey('remove_metadata');
});
```

Если config не используется, написать test на helper/service:

```php
it('returns default conversion preferences', function () {
    $preferences = app(UserConversionPreferences::class)->defaults();

    expect($preferences['image_quality'])->toBe('high');
    expect($preferences['remove_metadata'])->toBeTrue();
});
```

### Implementation

Добавить config:

```txt
config/converter.php
```

Пример:

```php
return [
    'user_defaults' => [
        'image_quality' => 'high',
        'remove_metadata' => true,
    ],

    'allowed_image_quality_values' => [
        'medium',
        'high',
        'best',
    ],
];
```

Или создать небольшой service:

```txt
app/Settings/UserConversionPreferences.php
```

Не создавать DB migration, если `users.settings` уже есть из Auth Foundation.

### Acceptance criteria

- Conversion preferences schema exists.
- Defaults defined.
- Allowed image quality values defined.
- No new DB table.
- Test passes.

### Definition of Done

- Schema/config добавлена.
- Tests pass.
- Коммит: `CONV-372: Create conversion preferences schema`

### Files likely touched

```txt
config/converter.php
app/Settings/UserConversionPreferences.php
tests/Unit/Settings/UserConversionPreferencesTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-373 — Test Default Image Quality Preference

**Area:** Settings / Preferences / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-373-test-default-image-quality-preference`  
**Base branch:** `develop`  
**Depends on:** CONV-372

### Goal

Написать падающие тесты: пользователь может сохранить default image quality.

### TDD step

Livewire test:

```php
it('allows user to save default image quality preference', function () {
    $user = User::factory()->create([
        'settings' => [],
    ]);

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->set('imageQuality', 'best')
        ->call('saveConversionPreferences')
        ->assertHasNoErrors();

    expect($user->fresh()->settings['conversion']['image_quality'])->toBe('best');
});
```

Validation test:

```php
it('rejects invalid default image quality preference', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->set('imageQuality', 'ultra_fake')
        ->call('saveConversionPreferences')
        ->assertHasErrors(['imageQuality']);
});
```

Тесты должны упасть до CONV-374.

### Implementation

Только добавить тесты.

### Acceptance criteria

- Тест save image quality exists.
- Тест invalid image quality exists.
- Tests fail before implementation.

### Definition of Done

- Тесты добавлены.
- Тесты ожидаемо падают.
- Коммит: `CONV-373: Test default image quality preference`

### Files likely touched

```txt
tests/Feature/Livewire/AccountSettingsFormTest.php
```

После этого сделай MR в `develop`. Merge разрешён после подтверждения, что новые тесты падают ожидаемо.

---

## CONV-374 — Implement Default Image Quality Preference

**Area:** Settings / Preferences / Livewire  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-374-implement-default-image-quality-preference`  
**Base branch:** `develop`  
**Depends on:** CONV-373

### Goal

Реализовать сохранение default image quality в `users.settings`.

### TDD step

Использовать падающие тесты из CONV-373.

### Implementation

В `AccountSettingsForm`:

```php
public string $imageQuality = 'high';

public function mount(): void
{
    $user = auth()->user();
    $settings = $user->settings ?? [];

    $this->imageQuality = data_get(
        $settings,
        'conversion.image_quality',
        config('converter.user_defaults.image_quality')
    );
}
```

Save method:

```php
public function saveConversionPreferences(): void
{
    $validated = $this->validate([
        'imageQuality' => ['required', Rule::in(config('converter.allowed_image_quality_values'))],
    ]);

    $user = auth()->user();
    $settings = $user->settings ?? [];

    data_set($settings, 'conversion.image_quality', $validated['imageQuality']);

    $user->forceFill([
        'settings' => $settings,
    ])->save();

    $this->dispatch('settings-saved', section: 'conversion');
}
```

Если `removeMetadata` будет добавлен позже, не ломать existing save method.

### Acceptance criteria

- User can save image quality.
- Allowed values enforced.
- Preference stored under `settings.conversion.image_quality`.
- Existing settings keys preserved.
- Tests pass.

### Definition of Done

- Preference save реализован.
- Validation добавлена.
- Tests pass.
- Коммит: `CONV-374: Implement default image quality preference`

### Files likely touched

```txt
app/Livewire/AccountSettingsForm.php
resources/views/livewire/account-settings-form.blade.php
config/converter.php
tests/Feature/Livewire/AccountSettingsFormTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-375 — Test Remove Metadata Preference

**Area:** Settings / Preferences / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-375-test-remove-metadata-preference`  
**Base branch:** `develop`  
**Depends on:** CONV-374

### Goal

Написать падающий тест: пользователь может сохранить default remove metadata preference.

### TDD step

Livewire test:

```php
it('allows user to save remove metadata preference', function () {
    $user = User::factory()->create([
        'settings' => [],
    ]);

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->set('removeMetadata', false)
        ->call('saveConversionPreferences')
        ->assertHasNoErrors();

    expect($user->fresh()->settings['conversion']['remove_metadata'])->toBeFalse();
});
```

Persistence test:

```php
it('loads existing remove metadata preference on mount', function () {
    $user = User::factory()->create([
        'settings' => [
            'conversion' => [
                'remove_metadata' => false,
            ],
        ],
    ]);

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->assertSet('removeMetadata', false);
});
```

Тесты должны упасть до CONV-376.

### Implementation

Только добавить тесты.

### Acceptance criteria

- Save test exists.
- Load existing preference test exists.
- Tests fail before implementation.

### Definition of Done

- Тесты добавлены.
- Тесты ожидаемо падают.
- Коммит: `CONV-375: Test remove metadata preference`

### Files likely touched

```txt
tests/Feature/Livewire/AccountSettingsFormTest.php
```

После этого сделай MR в `develop`. Merge разрешён после подтверждения, что новые тесты падают ожидаемо.

---

## CONV-376 — Implement Remove Metadata Preference

**Area:** Settings / Preferences / Livewire  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-376-implement-remove-metadata-preference`  
**Base branch:** `develop`  
**Depends on:** CONV-375

### Goal

Реализовать сохранение default remove metadata preference.

### TDD step

Использовать падающие тесты из CONV-375.

### Implementation

В component:

```php
public bool $removeMetadata = true;
```

В mount:

```php
$this->removeMetadata = (bool) data_get(
    $settings,
    'conversion.remove_metadata',
    config('converter.user_defaults.remove_metadata')
);
```

В validation/save:

```php
$validated = $this->validate([
    'imageQuality' => ['required', Rule::in(config('converter.allowed_image_quality_values'))],
    'removeMetadata' => ['boolean'],
]);

settings data:

data_set($settings, 'conversion.remove_metadata', (bool) $validated['removeMetadata']);
```

View:

```blade
<label>
    <input type="checkbox" wire:model="removeMetadata">
    Remove metadata by default
</label>
```

### Acceptance criteria

- User can save `remove_metadata` true/false.
- Existing preference loads on mount.
- Stored under `settings.conversion.remove_metadata`.
- Image quality preference still works.
- Tests pass.

### Definition of Done

- Preference реализована.
- UI toggle добавлен.
- Tests pass.
- Коммит: `CONV-376: Implement remove metadata preference`

### Files likely touched

```txt
app/Livewire/AccountSettingsForm.php
resources/views/livewire/account-settings-form.blade.php
config/converter.php
tests/Feature/Livewire/AccountSettingsFormTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-377 — Test Preferences Apply To Converter Defaults

**Area:** Settings / Converter Integration / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-377-test-preferences-apply-to-converter-defaults`  
**Base branch:** `develop`  
**Depends on:** CONV-376

### Goal

Написать падающий тест: сохранённые user preferences применяются как defaults в converter options schema.

### TDD step

Unit/application test:

```php
it('applies user image quality preference to converter options defaults', function () {
    $user = User::factory()->create([
        'settings' => [
            'conversion' => [
                'image_quality' => 'best',
                'remove_metadata' => true,
            ],
        ],
    ]);

    $schema = app(GetConverterOptionsSchemaAction::class)->handle(
        user: $user,
        sourceFormat: 'png',
        targetFormat: 'jpg',
    );

    expect($schema->field('quality')->default)->toBe('best');
    expect($schema->field('remove_metadata')->default)->toBeTrue();
});
```

Если schema пока array:

```php
expect(data_get($schema, 'fields.quality.default'))->toBe('best');
expect(data_get($schema, 'fields.remove_metadata.default'))->toBeTrue();
```

Также добавить fallback test:

```php
it('uses system defaults when user has no conversion preferences', ...);
```

Тесты должны упасть до CONV-378.

### Implementation

Только добавить тесты.

### Acceptance criteria

- Test checks image quality default.
- Test checks remove metadata default.
- Test checks fallback to system defaults.
- Tests fail before implementation.

### Definition of Done

- Тесты добавлены.
- Тесты ожидаемо падают.
- Коммит: `CONV-377: Test preferences apply to converter defaults`

### Files likely touched

```txt
tests/Feature/Converters/ConverterOptionsDefaultsTest.php
tests/Unit/Converters/ConverterOptionsDefaultsTest.php
```

После этого сделай MR в `develop`. Merge разрешён после подтверждения, что новые тесты падают ожидаемо.

---

## CONV-378 — Implement Preferences Application To Options Schema

**Area:** Settings / Converter Integration  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-378-implement-preferences-application-to-options-schema`  
**Base branch:** `develop`  
**Depends on:** CONV-377

### Goal

Применить user preferences к options schema/defaults для image converters.

### TDD step

Использовать падающие тесты из CONV-377.

### Implementation

Создать resolver, если его ещё нет:

```txt
app/Converters/Options/UserConverterDefaultsResolver.php
```

Пример ответственности:

```php
final class UserConverterDefaultsResolver
{
    public function apply(User $user, array $schema): array
    {
        $settings = $user->settings ?? [];

        $imageQuality = data_get(
            $settings,
            'conversion.image_quality',
            config('converter.user_defaults.image_quality')
        );

        $removeMetadata = data_get(
            $settings,
            'conversion.remove_metadata',
            config('converter.user_defaults.remove_metadata')
        );

        return $this->withDefaults($schema, [
            'quality' => $imageQuality,
            'remove_metadata' => (bool) $removeMetadata,
        ]);
    }
}
```

Интегрировать в action/schema provider, который уже используется DashboardConverter:

```txt
GetConverterOptionsSchemaAction
```

Если action ещё называется иначе, адаптировать к фактическому коду.

Критично: применять defaults только если field существует в schema.

Например PNG → PDF может не иметь `quality`, но может иметь `compression`. Не вставлять несуществующие поля.

### Acceptance criteria

- User image quality preference applies to converters with `quality` field.
- User remove metadata preference applies to converters with `remove_metadata` field.
- Fields not present in converter schema are not added blindly.
- System defaults used when user has no settings.
- Existing explicit options validation still works.
- Tests pass.

### Definition of Done

- Defaults resolver реализован.
- Schema action использует resolver.
- Tests pass.
- Коммит: `CONV-378: Implement preferences application to options schema`

### Files likely touched

```txt
app/Converters/Options/UserConverterDefaultsResolver.php
app/Actions/Converters/GetConverterOptionsSchemaAction.php
app/Livewire/DashboardConverter.php
tests/Feature/Converters/ConverterOptionsDefaultsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-379 — Add Settings Success And Validation UI

**Area:** Settings / UI / Livewire  
**Type:** Feature  
**Priority:** P1  
**Branch:** `feature/CONV-379-add-settings-success-and-validation-ui`  
**Base branch:** `develop`  
**Depends on:** CONV-378

### Goal

Добавить нормальный UX для сохранения настроек: success message, validation errors, loading states.

### TDD step

Livewire test:

```php
it('shows success message after saving profile settings', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->set('name', 'Updated Name')
        ->call('saveProfile')
        ->assertSee('Profile settings saved');
});
```

Preferences success test:

```php
it('shows success message after saving conversion preferences', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(AccountSettingsForm::class)
        ->set('imageQuality', 'high')
        ->set('removeMetadata', true)
        ->call('saveConversionPreferences')
        ->assertSee('Conversion preferences saved');
});
```

### Implementation

Add state:

```php
public ?string $profileSavedMessage = null;
public ?string $preferencesSavedMessage = null;
```

Or use session flash:

```php
session()->flash('settings.profile_saved', 'Profile settings saved.');
```

Add Blade error rendering:

```blade
@error('name') <p>{{ $message }}</p> @enderror
```

Add loading state:

```blade
<x-button wire:loading.attr="disabled" wire:target="saveProfile">
    Save profile
</x-button>
```

### Acceptance criteria

- Profile save shows success message.
- Conversion preferences save shows success message.
- Validation errors visible.
- Buttons disable while saving.
- Tests pass.

### Definition of Done

- Success UI added.
- Validation UI added.
- Loading states added.
- Tests pass.
- Коммит: `CONV-379: Add settings success and validation UI`

### Files likely touched

```txt
app/Livewire/AccountSettingsForm.php
resources/views/livewire/account-settings-form.blade.php
tests/Feature/Livewire/AccountSettingsFormTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

## CONV-380 — Add Settings Page Final Smoke Tests

**Area:** Settings / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-380-add-settings-page-final-smoke-tests`  
**Base branch:** `develop`  
**Depends on:** CONV-379

### Goal

Добавить финальные smoke/regression tests для Phase 23.

### TDD step

Feature test:

```php
it('renders complete settings page for authenticated user', function () {
    $user = User::factory()->create([
        'name' => 'Alex Johnson',
        'email' => 'alex@example.com',
    ]);

    $this->actingAs($user)
        ->get('/settings')
        ->assertOk()
        ->assertSee('Settings')
        ->assertSee('Account Settings')
        ->assertSee('Alex Johnson')
        ->assertSee('alex@example.com')
        ->assertSee('Conversion Preferences');
});
```

Regression test:

```php
it('does not expose billing api or device settings on minimal settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings')
        ->assertOk()
        ->assertDontSee('API Keys')
        ->assertDontSee('Invoices')
        ->assertDontSee('Devices')
        ->assertDontSee('Two-factor authentication');
});
```

### Implementation

Только добавить final tests и исправить найденные мелкие несоответствия.

Не добавлять новые фичи.

### Acceptance criteria

- Settings page final smoke test passes.
- Minimal scope regression test passes.
- Page does not include out-of-scope sections.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.

### Definition of Done

- Final smoke tests added.
- No new feature creep.
- Full test suite passes.
- Build passes.
- Коммит: `CONV-380: Add settings page final smoke tests`

### Files likely touched

```txt
tests/Feature/Settings/SettingsPageTest.php
tests/Feature/Livewire/AccountSettingsFormTest.php
resources/views/settings/index.blade.php
resources/views/livewire/account-settings-form.blade.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 23 Completion Criteria

Phase 23 завершена, когда:

```txt
- CONV-364–CONV-380 выполнены;
- /settings route exists;
- guest cannot access /settings;
- authenticated user can access /settings;
- AccountSettingsForm exists;
- current user name is shown;
- current user email is shown;
- email is read-only;
- user can update own name;
- name is trimmed before save;
- invalid name is rejected;
- conversion preferences schema exists;
- user can save default image quality;
- invalid image quality is rejected;
- user can save remove_metadata preference;
- preferences are stored in users.settings;
- preferences apply to converter options defaults;
- explicit conversion options are not overwritten after user selection;
- success messages appear after save;
- validation errors are visible;
- no billing settings added;
- no API key settings added;
- no device/security settings added;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 23

Без отдельной задачи нельзя:

```txt
- делать password change;
- делать email change;
- делать email verification;
- делать 2FA;
- делать delete account;
- делать billing settings;
- делать invoice settings;
- делать API key management;
- делать device/session management;
- делать notification center;
- делать team/workspace settings;
- делать admin settings;
- добавлять новые converters;
- менять credit/billing rules;
- менять API endpoints;
- добавлять React/Vue/Inertia;
- создавать отдельную user_preferences table;
- сохранять настройки другого пользователя по user_id из request.
```

---

# 12. Recommended Execution Order

```txt
CONV-364 Create Settings Page Route And Shell
CONV-365 Create AccountSettingsForm Component Skeleton
CONV-366 Test Settings Shows Current Profile Data
CONV-367 Implement Profile Data Binding
CONV-368 Test User Can Update Profile Name
CONV-369 Implement Profile Name Update
CONV-370 Test Email Is Displayed Read-Only
CONV-371 Implement Read-Only Email Display
CONV-372 Create Conversion Preferences Schema
CONV-373 Test Default Image Quality Preference
CONV-374 Implement Default Image Quality Preference
CONV-375 Test Remove Metadata Preference
CONV-376 Implement Remove Metadata Preference
CONV-377 Test Preferences Apply To Converter Defaults
CONV-378 Implement Preferences Application To Options Schema
CONV-379 Add Settings Success And Validation UI
CONV-380 Add Settings Page Final Smoke Tests
```

---

# 13. Release

После завершения Phase 23:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build
php artisan migrate:fresh --seed

git checkout -b release/v0.1.23-phase23-settings-page-minimal
git push -u origin release/v0.1.23-phase23-settings-page-minimal
```

После этого сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.23-phase23-settings-page-minimal -m "File Converter Phase 23 settings page minimal"
git push origin v0.1.23-phase23-settings-page-minimal
```
