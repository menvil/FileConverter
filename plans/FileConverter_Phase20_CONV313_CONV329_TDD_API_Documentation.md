# File Converter — Phase 20 Implementation Plan

Версия: 1.0  
Проект: **File Converter**  
Фаза: **Phase 20 — API Documentation**  
Диапазон задач: **CONV-313 → CONV-329**  
Основа нумерации: Phase 19 завершилась на `CONV-312`, поэтому Phase 20 начинается с `CONV-313`.  
Язык заголовков задач: **English**  
Язык описаний задач: **русский**

---

# 1. Главная фиксация

Phase 20 соответствует блоку:

```txt
Phase 20 — API Documentation
```

Правильный диапазон Phase 20:

```txt
CONV-313 — Create OpenAPI Specification Skeleton
CONV-314 — Add OpenAPI Validation Test
CONV-315 — Document API Authentication
CONV-316 — Document Standard API Errors
CONV-317 — Document Converters Index Endpoint
CONV-318 — Document Converter Schema Endpoint
CONV-319 — Document File Upload Endpoint
CONV-320 — Document File Target Formats Endpoint
CONV-321 — Document Conversion Cost Estimate Endpoint
CONV-322 — Document Create Conversion Endpoint
CONV-323 — Document Conversion Status Endpoint
CONV-324 — Document Conversion Download Endpoint
CONV-325 — Document Credits Balance Endpoint
CONV-326 — Add API Documentation Page
CONV-327 — Add Developer Quickstart Section
CONV-328 — Add API Examples And Curl Snippets
CONV-329 — Add API Documentation Final Smoke Tests
```

Phase 20 не меняет API-поведение.  
Она фиксирует публичный контракт API, созданного в Phase 19.

Главное правило:

```txt
Документация должна описывать реальный API, а не желаемый будущий API.
```

Если endpoint, поле, feature, webhook или scope ещё не реализованы — их нельзя документировать как рабочие.

---

# 2. Цель Phase 20

Phase 20 добавляет рабочую API-документацию для разработчиков.

После Phase 20 внешний разработчик должен уметь:

```txt
- понять, как аутентифицироваться через API key;
- увидеть стандартный формат ошибок;
- получить список доступных converters;
- получить options schema для converter pair;
- загрузить файл;
- получить target formats для файла;
- оценить стоимость conversion в credits;
- создать conversion job;
- проверить status conversion job;
- скачать результат;
- проверить credits balance;
- пройти полный happy path по документации без чтения исходного кода.
```

Документация должна быть основана на OpenAPI:

```txt
docs/api/openapi.yaml
```

и отображаться на странице:

```txt
/docs/api
```

---

# 3. Scope Phase 20

## Входит

```txt
- OpenAPI 3.1 или 3.0 specification file;
- API metadata: title, version, servers;
- Bearer API key authentication scheme;
- standard error response schema;
- endpoint documentation for all Phase 19 endpoints;
- request/response schemas;
- example requests;
- example responses;
- curl snippets / quickstart examples;
- docs route /docs/api;
- Redoc or Swagger UI rendering;
- tests that OpenAPI file exists and parses;
- smoke test that /docs/api returns OK;
- final docs/API consistency smoke tests.
```

## Не входит

```txt
- changing API endpoint behavior;
- adding new API endpoints;
- webhooks;
- OAuth;
- SDK generation;
- Postman collection generation;
- public developer portal account area;
- API key management UI;
- API usage analytics UI;
- API changelog automation;
- automatic docs generation from controllers;
- Scribe/Scramble installation unless explicitly decided later;
- multi-language docs;
- versioning beyond /api/v1;
- documenting future converters not implemented in registry.
```

Phase 20 — documentation only.  
Если во время документации обнаружится несоответствие API-контракта реализации, исправление должно быть минимальным и тестируемым, но нельзя расширять scope.

---

# 4. Critical Decisions

## 4.1. Manual OpenAPI is preferred for MVP

Для MVP документацию лучше вести вручную:

```txt
docs/api/openapi.yaml
```

Причина: API ещё молодое, автоматические генераторы могут навязать структуру и смешать документацию с implementation details.

Не ставить в Phase 20:

```txt
knuckleswtf/scribe
dedoc/scramble
custom generator
```

Это можно рассмотреть позже, когда API стабилизируется.

## 4.2. Documentation must not lie

Нельзя писать:

```txt
300+ formats supported
Webhooks supported
Batch conversions supported
OAuth supported
Direct S3 upload supported
```

если этого нет в реализации.

Для MVP документация должна честно отражать текущие converters:

```txt
PNG → JPG
JPG → PNG
PNG → WEBP
JPG → WEBP
PNG → PDF
JPG → PDF
```

Если registry уже содержит больше — документировать то, что реально покрыто тестами.

## 4.3. Error contract is first-class API documentation

Ошибки должны быть документированы не хуже happy path.

Стандартный формат:

```json
{
  "error": {
    "code": "unsupported_conversion",
    "message": "PNG to MP3 is not supported.",
    "details": {}
  }
}
```

Коды ошибок не должны быть случайными строками из exception messages.

## 4.4. API docs page is public unless decided otherwise

`/docs/api` можно оставить публичной.

Документация не должна раскрывать:

```txt
real API keys;
internal storage paths;
private Stripe ids;
server filesystem paths;
stack traces;
implementation secrets.
```

Но сам API contract скрывать не нужно.

## 4.5. Examples must be executable

Примеры должны быть пригодны для копирования:

```bash
curl -X GET "https://example.com/api/v1/converters" \
  -H "Authorization: Bearer fc_live_xxx"
```

Нельзя давать псевдокод, который нельзя запустить.

## 4.6. OpenAPI schemas should reuse components

Не дублировать одни и те же response schemas в каждом endpoint.

Правильно:

```txt
components.schemas.ErrorResponse
components.schemas.File
components.schemas.Converter
components.schemas.Conversion
components.schemas.CreditBalance
```

Неправильно:

```txt
копировать одинаковый JSON shape в каждый endpoint без reusable schema.
```

---

# 5. Architecture Rules

## 5.1. Docs live under docs/api

Файлы Phase 20 должны лежать в predictable paths:

```txt
docs/api/openapi.yaml
docs/api/examples/* optional
resources/views/docs/api.blade.php
```

Не складывать OpenAPI в `public/` как единственный источник правды.  
Если нужен public asset, он должен ссылаться на исходный файл или публиковаться явно.

## 5.2. Docs route must not depend on authentication

Если `/docs/api` публичный, он не должен использовать auth middleware.

Если позже появится private developer portal, это отдельный route.

## 5.3. Documentation tests should be cheap

Тесты документации не должны запускать реальные conversions или обращаться к Stripe.

Достаточно:

```txt
- YAML parses;
- required paths exist;
- required schemas exist;
- /docs/api returns 200;
- examples do not reference unknown endpoints.
```

## 5.4. API behavior tests stay in Phase 19

В Phase 20 не надо переписывать endpoint tests из Phase 19.

Phase 20 тестирует docs layer, а не повторно весь API.

## 5.5. Documentation should be version-aware

OpenAPI title/version должны отражать:

```txt
File Converter API
v1
```

Route:

```txt
/api/v1
```

Документация не должна смешивать v1 и будущий v2.

---

# 6. GitFlow для Phase 20

## Base branch

Все задачи Phase 20 создаются от:

```txt
develop
```

## Branch format

```txt
feature/CONV-313-create-openapi-specification-skeleton
feature/CONV-316-document-standard-api-errors
feature/CONV-326-add-api-documentation-page
```

## Commit format

```txt
CONV-313: Create OpenAPI specification skeleton
CONV-316: Document standard API errors
CONV-326: Add API documentation page
```

## Release branch

После выполнения `CONV-313`–`CONV-329`:

```txt
release/v0.1.20-phase20-api-documentation
```

## Tag

После merge release branch в `main`:

```txt
v0.1.20-phase20-api-documentation
```

---

# 7. TDD Rules for Phase 20

## Для OpenAPI file

Тестировать:

```txt
- file exists;
- YAML parses;
- openapi version exists;
- info.title exists;
- paths contain required endpoints;
- components.schemas contain required schemas.
```

## Для docs page

Тестировать:

```txt
- /docs/api returns 200;
- page contains File Converter API;
- page references openapi.yaml or embedded spec;
- page does not require auth.
```

## Для endpoint documentation

Минимально проверять через spec test:

```txt
- required path exists;
- required method exists;
- operationId exists;
- success response exists;
- error response exists where applicable.
```

## Для examples

Проверять:

```txt
- examples use /api/v1;
- examples use Authorization: Bearer;
- examples do not include real secrets;
- examples reference documented endpoints.
```

---

# 8. Universal Task Template

```txt
ID: CONV-XXX
Title: English title
Area: Docs / OpenAPI / API / Tests / Frontend
Type: Documentation / Test / Feature / Config
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
- OpenAPI остаётся валидным
- composer test проходит
- composer lint проходит
- npm run build проходит, если затронут frontend
- Нет API behavior changes вне scope задачи
- Коммит содержит ID задачи

Files likely touched:
- path/to/file
```

---

# 9. Phase 20 Atomic Tasks

---

## CONV-313 — Create OpenAPI Specification Skeleton

**Area:** Docs / OpenAPI  
**Type:** Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-313-create-openapi-specification-skeleton`  
**Base branch:** `develop`  
**Depends on:** CONV-312

### Goal

Создать базовый `docs/api/openapi.yaml` для File Converter API v1.

### TDD step

Добавить падающий тест:

```php
it('has an openapi specification file', function () {
    expect(base_path('docs/api/openapi.yaml'))->toBeFile();
});
```

Тест должен упасть до создания файла.

### Implementation

Создать:

```txt
docs/api/openapi.yaml
```

Минимальный skeleton:

```yaml
openapi: 3.1.0
info:
  title: File Converter API
  version: 1.0.0
  description: API for uploading files, creating conversion jobs, checking status, and downloading results.
servers:
  - url: https://example.com/api/v1
    description: Production
  - url: http://localhost/api/v1
    description: Local development
paths: {}
components:
  securitySchemes:
    ApiKeyBearer:
      type: http
      scheme: bearer
      bearerFormat: API key
  schemas: {}
security:
  - ApiKeyBearer: []
```

Не документировать endpoints в этой задаче.

### Acceptance criteria

- `docs/api/openapi.yaml` существует.
- OpenAPI version задана.
- API title задан.
- `/api/v1` отражён в server URL.
- Bearer security scheme добавлен.
- Endpoint paths пока могут быть пустыми.
- Тест проходит.

### Definition of Done

- Тест написан первым.
- OpenAPI skeleton создан.
- Тест проходит.
- `composer test` проходит.
- Коммит: `CONV-313: Create OpenAPI specification skeleton`

### Files likely touched

```txt
docs/api/openapi.yaml
tests/Feature/Docs/OpenApiSpecificationTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-314 — Add OpenAPI Validation Test

**Area:** Docs / Tests  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-314-add-openapi-validation-test`  
**Base branch:** `develop`  
**Depends on:** CONV-313

### Goal

Добавить тест, который проверяет, что OpenAPI YAML парсится и содержит обязательные корневые секции.

### TDD step

Feature test:

```php
it('parses openapi specification', function () {
    $spec = yaml_parse_file(base_path('docs/api/openapi.yaml'));

    expect($spec)->toBeArray()
        ->and($spec)->toHaveKey('openapi')
        ->and($spec)->toHaveKey('info')
        ->and($spec)->toHaveKey('paths')
        ->and($spec)->toHaveKey('components');
});
```

Если PHP yaml extension недоступен, использовать Symfony YAML:

```bash
composer require symfony/yaml --dev
```

### Implementation

Добавить dev dependency только если нужно:

```bash
composer require symfony/yaml --dev
```

Тестировать через:

```php
use Symfony\Component\Yaml\Yaml;

$spec = Yaml::parseFile(base_path('docs/api/openapi.yaml'));
```

### Acceptance criteria

- OpenAPI file парсится.
- Проверяются `openapi`, `info`, `paths`, `components`.
- Тест падает на invalid YAML.
- Нет runtime dependency в production, если можно оставить dev-only.
- `composer test` проходит.

### Definition of Done

- Validation test добавлен.
- YAML parser dependency добавлен только если нужен.
- Тест проходит.
- Коммит: `CONV-314: Add OpenAPI validation test`

### Files likely touched

```txt
composer.json
composer.lock
tests/Feature/Docs/OpenApiSpecificationTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-315 — Document API Authentication

**Area:** Docs / OpenAPI / Auth  
**Type:** Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-315-document-api-authentication`  
**Base branch:** `develop`  
**Depends on:** CONV-314

### Goal

Описать API key authentication через Bearer token.

### TDD step

Добавить OpenAPI assertion:

```php
it('documents bearer api key authentication', function () {
    $spec = openApiSpec();

    expect($spec['components']['securitySchemes'])->toHaveKey('ApiKeyBearer')
        ->and($spec['components']['securitySchemes']['ApiKeyBearer']['type'])->toBe('http')
        ->and($spec['components']['securitySchemes']['ApiKeyBearer']['scheme'])->toBe('bearer');
});
```

### Implementation

В `components.securitySchemes` зафиксировать:

```yaml
ApiKeyBearer:
  type: http
  scheme: bearer
  bearerFormat: API key
  description: Use an API key generated in your account. Send it as `Authorization: Bearer <token>`.
```

Добавить security requirement на корневом уровне:

```yaml
security:
  - ApiKeyBearer: []
```

Добавить в description короткий пример:

```txt
Authorization: Bearer fc_live_xxx
```

Не документировать OAuth.

### Acceptance criteria

- Bearer auth documented.
- Header format documented.
- No OAuth mentioned as implemented.
- Root security requirement exists.
- Test passes.

### Definition of Done

- Тест написан.
- Auth docs добавлены.
- OpenAPI valid.
- Коммит: `CONV-315: Document API authentication`

### Files likely touched

```txt
docs/api/openapi.yaml
tests/Feature/Docs/OpenApiSpecificationTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-316 — Document Standard API Errors

**Area:** Docs / OpenAPI / Errors  
**Type:** Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-316-document-standard-api-errors`  
**Base branch:** `develop`  
**Depends on:** CONV-315

### Goal

Задокументировать единый формат ошибок API.

### TDD step

OpenAPI test:

```php
it('documents standard api error response schema', function () {
    $schemas = openApiSpec()['components']['schemas'];

    expect($schemas)->toHaveKey('ErrorResponse')
        ->and($schemas['ErrorResponse']['properties'])->toHaveKey('error');
});
```

### Implementation

Добавить schemas:

```yaml
ErrorResponse:
  type: object
  required:
    - error
  properties:
    error:
      $ref: '#/components/schemas/ErrorObject'

ErrorObject:
  type: object
  required:
    - code
    - message
  properties:
    code:
      type: string
      example: unsupported_conversion
    message:
      type: string
      example: PNG to MP3 is not supported.
    details:
      type: object
      additionalProperties: true
      example:
        source_format: png
        target_format: mp3
```

Добавить reusable responses:

```yaml
components:
  responses:
    UnauthorizedError:
      description: Missing or invalid API key.
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/ErrorResponse'
    ValidationError:
      description: Request validation failed.
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/ErrorResponse'
```

Коды ошибок описать в description:

```txt
unauthorized
api_not_available
unsupported_format
unsupported_conversion
invalid_options
file_too_large
insufficient_credits
conversion_failed
result_expired
rate_limited
```

### Acceptance criteria

- `ErrorResponse` schema exists.
- `ErrorObject` schema exists.
- Standard error codes documented.
- Reusable error responses exist.
- OpenAPI validation passes.

### Definition of Done

- Тест написан.
- Error schemas added.
- Standard error examples added.
- Коммит: `CONV-316: Document standard API errors`

### Files likely touched

```txt
docs/api/openapi.yaml
tests/Feature/Docs/OpenApiSpecificationTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-317 — Document Converters Index Endpoint

**Area:** Docs / OpenAPI / Converters  
**Type:** Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-317-document-converters-index-endpoint`  
**Base branch:** `develop`  
**Depends on:** CONV-316

### Goal

Задокументировать:

```txt
GET /converters
```

### TDD step

OpenAPI path test:

```php
it('documents converters index endpoint', function () {
    $paths = openApiSpec()['paths'];

    expect($paths)->toHaveKey('/converters')
        ->and($paths['/converters'])->toHaveKey('get');
});
```

### Implementation

Добавить path:

```yaml
/converters:
  get:
    operationId: listConverters
    summary: List available converters
    description: Returns all currently supported source → target conversion capabilities.
    tags:
      - Converters
    responses:
      '200':
        description: List of converters.
        content:
          application/json:
            schema:
              type: object
              required:
                - data
              properties:
                data:
                  type: array
                  items:
                    $ref: '#/components/schemas/Converter'
      '401':
        $ref: '#/components/responses/UnauthorizedError'
```

Добавить schema:

```yaml
Converter:
  type: object
  required:
    - key
    - source_format
    - target_format
    - label
    - description
  properties:
    key:
      type: string
      example: png_to_jpg
    source_format:
      type: string
      example: png
    target_format:
      type: string
      example: jpg
    label:
      type: string
      example: PNG to JPG
    description:
      type: string
      example: Convert PNG images to JPG format.
    recommended:
      type: boolean
      example: true
```

### Acceptance criteria

- `/converters` documented.
- `operationId` exists.
- Success response documented.
- Unauthorized response documented.
- Converter schema exists.
- Test passes.

### Definition of Done

- Тест написан.
- Endpoint docs added.
- OpenAPI valid.
- Коммит: `CONV-317: Document converters index endpoint`

### Files likely touched

```txt
docs/api/openapi.yaml
tests/Feature/Docs/OpenApiPathsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-318 — Document Converter Schema Endpoint

**Area:** Docs / OpenAPI / Converters  
**Type:** Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-318-document-converter-schema-endpoint`  
**Base branch:** `develop`  
**Depends on:** CONV-317

### Goal

Задокументировать:

```txt
GET /converters/{source}/{target}/schema
```

### TDD step

OpenAPI path test:

```php
it('documents converter schema endpoint', function () {
    $paths = openApiSpec()['paths'];

    expect($paths)->toHaveKey('/converters/{source}/{target}/schema')
        ->and($paths['/converters/{source}/{target}/schema'])->toHaveKey('get');
});
```

### Implementation

Добавить path с parameters:

```yaml
/converters/{source}/{target}/schema:
  get:
    operationId: getConverterSchema
    summary: Get converter options schema
    tags:
      - Converters
    parameters:
      - name: source
        in: path
        required: true
        schema:
          type: string
          example: png
      - name: target
        in: path
        required: true
        schema:
          type: string
          example: jpg
    responses:
      '200':
        description: Converter options schema.
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/ConverterSchemaResponse'
      '401':
        $ref: '#/components/responses/UnauthorizedError'
      '404':
        $ref: '#/components/responses/NotFoundError'
```

Добавить schemas:

```yaml
ConverterSchemaResponse:
  type: object
  required:
    - source_format
    - target_format
    - options
  properties:
    source_format:
      type: string
      example: png
    target_format:
      type: string
      example: jpg
    options:
      type: array
      items:
        $ref: '#/components/schemas/OptionField'

OptionField:
  type: object
  required:
    - key
    - type
    - label
  properties:
    key:
      type: string
      example: quality
    type:
      type: string
      enum: [select, segmented, toggle, color, number, range]
      example: segmented
    label:
      type: string
      example: Quality
    default:
      nullable: true
      example: high
    options:
      type: array
      items:
        $ref: '#/components/schemas/OptionChoice'

OptionChoice:
  type: object
  required:
    - value
    - label
  properties:
    value:
      type: string
      example: high
    label:
      type: string
      example: High
```

### Acceptance criteria

- Converter schema endpoint documented.
- Source/target path params documented.
- Option schema documented.
- 404 documented for unsupported pair.
- Test passes.

### Definition of Done

- Тест написан.
- Endpoint docs added.
- Option schemas reusable.
- Коммит: `CONV-318: Document converter schema endpoint`

### Files likely touched

```txt
docs/api/openapi.yaml
tests/Feature/Docs/OpenApiPathsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-319 — Document File Upload Endpoint

**Area:** Docs / OpenAPI / Files  
**Type:** Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-319-document-file-upload-endpoint`  
**Base branch:** `develop`  
**Depends on:** CONV-318

### Goal

Задокументировать:

```txt
POST /files
```

multipart upload endpoint.

### TDD step

OpenAPI path test:

```php
it('documents file upload endpoint', function () {
    $paths = openApiSpec()['paths'];

    expect($paths)->toHaveKey('/files')
        ->and($paths['/files'])->toHaveKey('post');
});
```

### Implementation

Добавить path:

```yaml
/files:
  post:
    operationId: uploadFile
    summary: Upload a file
    description: Uploads a supported file and returns detected format and metadata.
    tags:
      - Files
    requestBody:
      required: true
      content:
        multipart/form-data:
          schema:
            type: object
            required:
              - file
            properties:
              file:
                type: string
                format: binary
    responses:
      '201':
        description: File uploaded.
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/FileResponse'
      '401':
        $ref: '#/components/responses/UnauthorizedError'
      '422':
        $ref: '#/components/responses/ValidationError'
```

Добавить schemas:

```yaml
FileResponse:
  type: object
  required:
    - data
  properties:
    data:
      $ref: '#/components/schemas/File'

File:
  type: object
  required:
    - id
    - original_name
    - format
    - size_bytes
    - metadata
  properties:
    id:
      type: string
      example: file_01HX7...
    original_name:
      type: string
      example: product-photo.png
    format:
      type: string
      example: png
    mime_type:
      type: string
      example: image/png
    size_bytes:
      type: integer
      example: 955000
    metadata:
      type: object
      additionalProperties: true
      example:
        width: 1200
        height: 900
        has_transparency: true
```

### Acceptance criteria

- Multipart upload documented.
- File response schema exists.
- Validation errors documented.
- File size/format limitations referenced in description.
- Test passes.

### Definition of Done

- Тест написан.
- File upload docs added.
- OpenAPI valid.
- Коммит: `CONV-319: Document file upload endpoint`

### Files likely touched

```txt
docs/api/openapi.yaml
tests/Feature/Docs/OpenApiPathsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-320 — Document File Target Formats Endpoint

**Area:** Docs / OpenAPI / Files  
**Type:** Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-320-document-file-target-formats-endpoint`  
**Base branch:** `develop`  
**Depends on:** CONV-319

### Goal

Задокументировать:

```txt
GET /files/{file}/targets
```

### TDD step

OpenAPI path test:

```php
it('documents file target formats endpoint', function () {
    $paths = openApiSpec()['paths'];

    expect($paths)->toHaveKey('/files/{file}/targets')
        ->and($paths['/files/{file}/targets'])->toHaveKey('get');
});
```

### Implementation

Добавить path:

```yaml
/files/{file}/targets:
  get:
    operationId: listFileTargetFormats
    summary: List target formats for an uploaded file
    tags:
      - Files
    parameters:
      - name: file
        in: path
        required: true
        schema:
          type: string
          example: file_01HX7...
    responses:
      '200':
        description: Available target formats.
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/FileTargetsResponse'
      '401':
        $ref: '#/components/responses/UnauthorizedError'
      '404':
        $ref: '#/components/responses/NotFoundError'
```

Schema:

```yaml
FileTargetsResponse:
  type: object
  required:
    - data
  properties:
    data:
      type: object
      required:
        - file_id
        - source_format
        - targets
      properties:
        file_id:
          type: string
          example: file_01HX7...
        source_format:
          type: string
          example: png
        targets:
          type: array
          items:
            $ref: '#/components/schemas/TargetFormat'

TargetFormat:
  type: object
  required:
    - format
    - label
    - description
  properties:
    format:
      type: string
      example: jpg
    label:
      type: string
      example: JPG
    description:
      type: string
      example: Best for photos and sharing.
    recommended:
      type: boolean
      example: true
```

### Acceptance criteria

- File target formats endpoint documented.
- Owner-only 404/403 behavior documented as applicable.
- TargetFormat schema exists.
- Test passes.

### Definition of Done

- Тест написан.
- Endpoint docs added.
- OpenAPI valid.
- Коммит: `CONV-320: Document file target formats endpoint`

### Files likely touched

```txt
docs/api/openapi.yaml
tests/Feature/Docs/OpenApiPathsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-321 — Document Conversion Cost Estimate Endpoint

**Area:** Docs / OpenAPI / Conversions / Credits  
**Type:** Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-321-document-conversion-cost-estimate-endpoint`  
**Base branch:** `develop`  
**Depends on:** CONV-320

### Goal

Задокументировать:

```txt
POST /conversions/estimate
```

### TDD step

OpenAPI path test:

```php
it('documents conversion cost estimate endpoint', function () {
    $paths = openApiSpec()['paths'];

    expect($paths)->toHaveKey('/conversions/estimate')
        ->and($paths['/conversions/estimate'])->toHaveKey('post');
});
```

### Implementation

Добавить request schema:

```yaml
EstimateConversionRequest:
  type: object
  required:
    - file_id
    - target_format
  properties:
    file_id:
      type: string
      example: file_01HX7...
    target_format:
      type: string
      example: jpg
    options:
      type: object
      additionalProperties: true
      example:
        quality: high
        background: '#ffffff'
```

Response schema:

```yaml
CreditCost:
  type: object
  required:
    - amount
    - breakdown
  properties:
    amount:
      type: integer
      example: 1
    unit:
      type: string
      example: credits
    breakdown:
      type: object
      additionalProperties: true
      example:
        base: 1
        size: 0
        features: 0
        total: 1
```

Path:

```yaml
/conversions/estimate:
  post:
    operationId: estimateConversionCost
    summary: Estimate conversion credit cost
    tags:
      - Conversions
      - Credits
    requestBody:
      required: true
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/EstimateConversionRequest'
    responses:
      '200':
        description: Estimated credit cost.
        content:
          application/json:
            schema:
              type: object
              required: [data]
              properties:
                data:
                  $ref: '#/components/schemas/CreditCost'
      '401':
        $ref: '#/components/responses/UnauthorizedError'
      '422':
        $ref: '#/components/responses/ValidationError'
```

### Acceptance criteria

- Cost estimate endpoint documented.
- Request body documented.
- CreditCost schema exists.
- Does not imply job creation.
- Test passes.

### Definition of Done

- Тест написан.
- Endpoint docs added.
- OpenAPI valid.
- Коммит: `CONV-321: Document conversion cost estimate endpoint`

### Files likely touched

```txt
docs/api/openapi.yaml
tests/Feature/Docs/OpenApiPathsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-322 — Document Create Conversion Endpoint

**Area:** Docs / OpenAPI / Conversions  
**Type:** Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-322-document-create-conversion-endpoint`  
**Base branch:** `develop`  
**Depends on:** CONV-321

### Goal

Задокументировать:

```txt
POST /conversions
```

### TDD step

OpenAPI path test:

```php
it('documents create conversion endpoint', function () {
    $paths = openApiSpec()['paths'];

    expect($paths)->toHaveKey('/conversions')
        ->and($paths['/conversions'])->toHaveKey('post');
});
```

### Implementation

Request schema:

```yaml
CreateConversionRequest:
  type: object
  required:
    - file_id
    - target_format
  properties:
    file_id:
      type: string
      example: file_01HX7...
    target_format:
      type: string
      example: jpg
    options:
      type: object
      additionalProperties: true
      example:
        quality: high
        background: '#ffffff'
```

Conversion schema:

```yaml
Conversion:
  type: object
  required:
    - id
    - status
    - source_format
    - target_format
    - credits
  properties:
    id:
      type: string
      example: conv_01HX8...
    status:
      type: string
      enum: [queued, processing, completed, failed, cancelled, expired]
      example: queued
    source_format:
      type: string
      example: png
    target_format:
      type: string
      example: jpg
    progress:
      type: integer
      minimum: 0
      maximum: 100
      example: 0
    credits:
      $ref: '#/components/schemas/CreditCost'
    result_file:
      nullable: true
      $ref: '#/components/schemas/File'
    created_at:
      type: string
      format: date-time
```

Path:

```yaml
/conversions:
  post:
    operationId: createConversion
    summary: Create a conversion job
    tags:
      - Conversions
    requestBody:
      required: true
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/CreateConversionRequest'
    responses:
      '201':
        description: Conversion job created.
        content:
          application/json:
            schema:
              type: object
              required: [data]
              properties:
                data:
                  $ref: '#/components/schemas/Conversion'
      '401':
        $ref: '#/components/responses/UnauthorizedError'
      '422':
        $ref: '#/components/responses/ValidationError'
```

Документировать `insufficient_credits` как возможную `422` или `402`, в зависимости от Phase 19 реализации. Не выдумывать новый status code.

### Acceptance criteria

- Create conversion endpoint documented.
- Request/response schemas exist.
- Insufficient credits behavior mentioned.
- Does not document reserve/capture if not implemented.
- Test passes.

### Definition of Done

- Тест написан.
- Endpoint docs added.
- OpenAPI valid.
- Коммит: `CONV-322: Document create conversion endpoint`

### Files likely touched

```txt
docs/api/openapi.yaml
tests/Feature/Docs/OpenApiPathsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-323 — Document Conversion Status Endpoint

**Area:** Docs / OpenAPI / Conversions  
**Type:** Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-323-document-conversion-status-endpoint`  
**Base branch:** `develop`  
**Depends on:** CONV-322

### Goal

Задокументировать:

```txt
GET /conversions/{conversion}
```

### TDD step

OpenAPI path test:

```php
it('documents conversion status endpoint', function () {
    $paths = openApiSpec()['paths'];

    expect($paths)->toHaveKey('/conversions/{conversion}')
        ->and($paths['/conversions/{conversion}'])->toHaveKey('get');
});
```

### Implementation

Path:

```yaml
/conversions/{conversion}:
  get:
    operationId: getConversionStatus
    summary: Get conversion status
    tags:
      - Conversions
    parameters:
      - name: conversion
        in: path
        required: true
        schema:
          type: string
          example: conv_01HX8...
    responses:
      '200':
        description: Conversion status.
        content:
          application/json:
            schema:
              type: object
              required: [data]
              properties:
                data:
                  $ref: '#/components/schemas/Conversion'
      '401':
        $ref: '#/components/responses/UnauthorizedError'
      '404':
        $ref: '#/components/responses/NotFoundError'
```

Описание должно явно сказать:

```txt
Poll this endpoint until status is completed or failed.
```

### Acceptance criteria

- Status endpoint documented.
- Polling behavior explained.
- Completed/failed statuses documented.
- 404 documented.
- Test passes.

### Definition of Done

- Тест написан.
- Endpoint docs added.
- OpenAPI valid.
- Коммит: `CONV-323: Document conversion status endpoint`

### Files likely touched

```txt
docs/api/openapi.yaml
tests/Feature/Docs/OpenApiPathsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-324 — Document Conversion Download Endpoint

**Area:** Docs / OpenAPI / Conversions  
**Type:** Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-324-document-conversion-download-endpoint`  
**Base branch:** `develop`  
**Depends on:** CONV-323

### Goal

Задокументировать:

```txt
GET /conversions/{conversion}/download
```

### TDD step

OpenAPI path test:

```php
it('documents conversion download endpoint', function () {
    $paths = openApiSpec()['paths'];

    expect($paths)->toHaveKey('/conversions/{conversion}/download')
        ->and($paths['/conversions/{conversion}/download'])->toHaveKey('get');
});
```

### Implementation

Path:

```yaml
/conversions/{conversion}/download:
  get:
    operationId: downloadConversionResult
    summary: Download conversion result
    description: Downloads the result file for a completed conversion. Failed, processing, expired, or unauthorized conversions cannot be downloaded.
    tags:
      - Conversions
    parameters:
      - name: conversion
        in: path
        required: true
        schema:
          type: string
          example: conv_01HX8...
    responses:
      '200':
        description: Result file binary.
        content:
          application/octet-stream:
            schema:
              type: string
              format: binary
      '401':
        $ref: '#/components/responses/UnauthorizedError'
      '404':
        $ref: '#/components/responses/NotFoundError'
      '410':
        description: Result expired.
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/ErrorResponse'
```

Если реализация Phase 19 использует redirect/signed URL, документировать фактическое поведение, а не binary response.

### Acceptance criteria

- Download endpoint documented.
- Binary response documented or actual redirect behavior documented.
- Expired result behavior documented.
- Test passes.

### Definition of Done

- Тест написан.
- Endpoint docs added.
- OpenAPI valid.
- Коммит: `CONV-324: Document conversion download endpoint`

### Files likely touched

```txt
docs/api/openapi.yaml
tests/Feature/Docs/OpenApiPathsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-325 — Document Credits Balance Endpoint

**Area:** Docs / OpenAPI / Credits  
**Type:** Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-325-document-credits-balance-endpoint`  
**Base branch:** `develop`  
**Depends on:** CONV-324

### Goal

Задокументировать:

```txt
GET /credits/balance
```

### TDD step

OpenAPI path test:

```php
it('documents credits balance endpoint', function () {
    $paths = openApiSpec()['paths'];

    expect($paths)->toHaveKey('/credits/balance')
        ->and($paths['/credits/balance'])->toHaveKey('get');
});
```

### Implementation

Schema:

```yaml
CreditBalance:
  type: object
  required:
    - balance
    - unit
  properties:
    balance:
      type: integer
      example: 948
    unit:
      type: string
      example: credits
```

Path:

```yaml
/credits/balance:
  get:
    operationId: getCreditsBalance
    summary: Get current credits balance
    tags:
      - Credits
    responses:
      '200':
        description: Current credits balance.
        content:
          application/json:
            schema:
              type: object
              required: [data]
              properties:
                data:
                  $ref: '#/components/schemas/CreditBalance'
      '401':
        $ref: '#/components/responses/UnauthorizedError'
```

### Acceptance criteria

- Credits balance endpoint documented.
- CreditBalance schema exists.
- Unauthorized response documented.
- Test passes.

### Definition of Done

- Тест написан.
- Endpoint docs added.
- OpenAPI valid.
- Коммит: `CONV-325: Document credits balance endpoint`

### Files likely touched

```txt
docs/api/openapi.yaml
tests/Feature/Docs/OpenApiPathsTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-326 — Add API Documentation Page

**Area:** Docs / Frontend / Routes  
**Type:** Feature  
**Priority:** P0  
**Branch:** `feature/CONV-326-add-api-documentation-page`  
**Base branch:** `develop`  
**Depends on:** CONV-325

### Goal

Добавить страницу:

```txt
/docs/api
```

которая отображает OpenAPI-документацию.

### TDD step

Feature test:

```php
it('renders api documentation page', function () {
    $this->get('/docs/api')
        ->assertOk()
        ->assertSee('File Converter API');
});
```

Тест должен упасть до добавления route/view.

### Implementation

Добавить route:

```php
Route::view('/docs/api', 'docs.api')->name('docs.api');
```

Создать view:

```txt
resources/views/docs/api.blade.php
```

Рекомендация для MVP: Redoc через CDN допустим, если проект не требует offline docs:

```html
<redoc spec-url="/docs/api/openapi.yaml"></redoc>
<script src="https://cdn.redoc.ly/redoc/latest/bundles/redoc.standalone.js"></script>
```

Но `/docs/api/openapi.yaml` должен быть доступен. Можно добавить route:

```php
Route::get('/docs/api/openapi.yaml', function () {
    return response()->file(base_path('docs/api/openapi.yaml'), [
        'Content-Type' => 'application/yaml',
    ]);
});
```

Если CDN нежелателен, использовать простую Blade-страницу со ссылкой на YAML и quickstart. Но лучше иметь rendered docs.

### Acceptance criteria

- `/docs/api` returns 200.
- Page contains `File Converter API`.
- OpenAPI YAML доступен по route.
- Page does not require auth.
- No real API keys exposed.
- Test passes.

### Definition of Done

- Тест написан первым.
- Docs route/view added.
- YAML route added if needed.
- `composer test` passes.
- `npm run build` passes if frontend touched.
- Коммит: `CONV-326: Add API documentation page`

### Files likely touched

```txt
routes/web.php
resources/views/docs/api.blade.php
tests/Feature/Docs/ApiDocumentationPageTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` и `npm run build` проходят.

---

## CONV-327 — Add Developer Quickstart Section

**Area:** Docs / Developer Experience  
**Type:** Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-327-add-developer-quickstart-section`  
**Base branch:** `develop`  
**Depends on:** CONV-326

### Goal

Добавить quickstart для разработчика: полный путь от API key до скачивания результата.

### TDD step

Docs page test:

```php
it('shows developer quickstart on api docs page', function () {
    $this->get('/docs/api')
        ->assertOk()
        ->assertSee('Developer quickstart')
        ->assertSee('Upload a file')
        ->assertSee('Create a conversion')
        ->assertSee('Download the result');
});
```

### Implementation

В `resources/views/docs/api.blade.php` добавить секцию:

```txt
Developer quickstart
1. Create an API key in your account.
2. Upload a file.
3. List available target formats.
4. Estimate credits.
5. Create conversion.
6. Poll status.
7. Download result.
```

Не документировать API key UI как существующий, если его нет. Формулировка:

```txt
Create an API key from your account settings once API key management UI is enabled, or use a generated key from the current internal/admin flow.
```

Если API key UI уже есть — написать точный путь.

### Acceptance criteria

- Quickstart section visible.
- Steps match real API flow.
- No fake UI paths if API key UI not implemented.
- Test passes.

### Definition of Done

- Тест написан.
- Quickstart section added.
- No false product claims.
- Коммит: `CONV-327: Add developer quickstart section`

### Files likely touched

```txt
resources/views/docs/api.blade.php
tests/Feature/Docs/ApiDocumentationPageTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-328 — Add API Examples And Curl Snippets

**Area:** Docs / Examples  
**Type:** Documentation  
**Priority:** P0  
**Branch:** `feature/CONV-328-add-api-examples-and-curl-snippets`  
**Base branch:** `develop`  
**Depends on:** CONV-327

### Goal

Добавить исполняемые curl-примеры для основных endpoint-ов.

### TDD step

Feature test:

```php
it('shows curl examples on api docs page', function () {
    $this->get('/docs/api')
        ->assertOk()
        ->assertSee('curl')
        ->assertSee('/api/v1/files')
        ->assertSee('/api/v1/conversions');
});
```

### Implementation

Добавить examples section:

```bash
curl -X POST "https://example.com/api/v1/files" \
  -H "Authorization: Bearer fc_live_xxx" \
  -F "file=@product-photo.png"
```

```bash
curl -X GET "https://example.com/api/v1/files/file_01HX7/targets" \
  -H "Authorization: Bearer fc_live_xxx"
```

```bash
curl -X POST "https://example.com/api/v1/conversions" \
  -H "Authorization: Bearer fc_live_xxx" \
  -H "Content-Type: application/json" \
  -d '{
    "file_id": "file_01HX7",
    "target_format": "jpg",
    "options": {
      "quality": "high",
      "background": "#ffffff"
    }
  }'
```

```bash
curl -X GET "https://example.com/api/v1/conversions/conv_01HX8" \
  -H "Authorization: Bearer fc_live_xxx"
```

```bash
curl -L -X GET "https://example.com/api/v1/conversions/conv_01HX8/download" \
  -H "Authorization: Bearer fc_live_xxx" \
  -o result.jpg
```

Не использовать реальные токены.

### Acceptance criteria

- Curl examples visible.
- Examples use `/api/v1`.
- Examples use `Authorization: Bearer`.
- No real secrets.
- Examples cover upload/create/status/download.
- Test passes.

### Definition of Done

- Тест написан.
- Curl examples added.
- Examples align with OpenAPI paths.
- Коммит: `CONV-328: Add API examples and curl snippets`

### Files likely touched

```txt
resources/views/docs/api.blade.php
docs/api/openapi.yaml optional
tests/Feature/Docs/ApiDocumentationPageTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test` проходит.

---

## CONV-329 — Add API Documentation Final Smoke Tests

**Area:** Docs / Tests / Quality  
**Type:** Test  
**Priority:** P0  
**Branch:** `feature/CONV-329-add-api-documentation-final-smoke-tests`  
**Base branch:** `develop`  
**Depends on:** CONV-328

### Goal

Добавить финальные smoke tests, подтверждающие, что API documentation complete enough для Phase 20.

### TDD step

Добавить тест, который проверяет обязательные paths:

```php
it('documents all phase 19 api endpoints', function () {
    $paths = openApiSpec()['paths'];

    foreach ([
        '/converters',
        '/converters/{source}/{target}/schema',
        '/files',
        '/files/{file}/targets',
        '/conversions/estimate',
        '/conversions',
        '/conversions/{conversion}',
        '/conversions/{conversion}/download',
        '/credits/balance',
    ] as $path) {
        expect($paths)->toHaveKey($path);
    }
});
```

Добавить schema smoke test:

```php
it('defines required api schemas', function () {
    $schemas = openApiSpec()['components']['schemas'];

    foreach ([
        'ErrorResponse',
        'Converter',
        'ConverterSchemaResponse',
        'File',
        'Conversion',
        'CreditCost',
        'CreditBalance',
    ] as $schema) {
        expect($schemas)->toHaveKey($schema);
    }
});
```

### Implementation

Доработать OpenAPI, если smoke tests выявят пропуски.

Добавить final test for docs page:

```php
it('renders public api docs without authentication', function () {
    $this->get('/docs/api')->assertOk();
});
```

Запустить:

```bash
composer test
composer lint
npm run build
```

### Acceptance criteria

- All Phase 19 endpoints documented.
- Required schemas exist.
- `/docs/api` public page works.
- OpenAPI file parses.
- No undocumented fake endpoints added.
- `composer test` passes.
- `composer lint` passes.
- `npm run build` passes.

### Definition of Done

- Final smoke tests added.
- Missing docs fixed.
- Full test suite passes.
- Build passes.
- Коммит: `CONV-329: Add API documentation final smoke tests`

### Files likely touched

```txt
docs/api/openapi.yaml
tests/Feature/Docs/OpenApiSpecificationTest.php
tests/Feature/Docs/OpenApiPathsTest.php
tests/Feature/Docs/ApiDocumentationPageTest.php
```

После этого сделай MR в `develop`. Merge разрешён только если `composer test`, `composer lint`, `npm run build` проходят.

---

# 10. Phase 20 Completion Criteria

Phase 20 завершена, когда:

```txt
- CONV-313–CONV-329 выполнены;
- docs/api/openapi.yaml exists;
- OpenAPI YAML parses;
- API authentication documented;
- standard API error format documented;
- GET /converters documented;
- GET /converters/{source}/{target}/schema documented;
- POST /files documented;
- GET /files/{file}/targets documented;
- POST /conversions/estimate documented;
- POST /conversions documented;
- GET /conversions/{conversion} documented;
- GET /conversions/{conversion}/download documented;
- GET /credits/balance documented;
- reusable schemas exist;
- /docs/api route exists;
- /docs/api renders documentation;
- developer quickstart exists;
- curl examples exist;
- no future endpoints are documented as implemented;
- no real secrets appear in docs;
- composer test passes;
- composer lint passes;
- npm run build passes.
```

---

# 11. Что нельзя делать в Phase 20

Без отдельной задачи нельзя:

```txt
- менять API behavior;
- добавлять API endpoints;
- добавлять webhooks;
- добавлять OAuth;
- добавлять API key management UI;
- добавлять SDK generation;
- добавлять Scribe/Scramble;
- добавлять Postman collection;
- добавлять batch conversion docs as implemented;
- документировать OCR/video/PDF-DOCX converters, если они не реализованы;
- документировать 300+ formats;
- добавлять Stripe/Cashier docs в API docs;
- добавлять private developer portal;
- добавлять team/scoped API keys;
- добавлять direct-to-S3 upload;
- добавлять chunked upload;
- смешивать API v1 и будущий API v2.
```

---

# 12. Recommended Execution Order

```txt
CONV-313 Create OpenAPI Specification Skeleton
CONV-314 Add OpenAPI Validation Test
CONV-315 Document API Authentication
CONV-316 Document Standard API Errors
CONV-317 Document Converters Index Endpoint
CONV-318 Document Converter Schema Endpoint
CONV-319 Document File Upload Endpoint
CONV-320 Document File Target Formats Endpoint
CONV-321 Document Conversion Cost Estimate Endpoint
CONV-322 Document Create Conversion Endpoint
CONV-323 Document Conversion Status Endpoint
CONV-324 Document Conversion Download Endpoint
CONV-325 Document Credits Balance Endpoint
CONV-326 Add API Documentation Page
CONV-327 Add Developer Quickstart Section
CONV-328 Add API Examples And Curl Snippets
CONV-329 Add API Documentation Final Smoke Tests
```

---

# 13. Release

После завершения Phase 20:

```bash
git checkout develop
git pull origin develop

composer test
composer lint
npm run build

# optional: verify docs route locally
php artisan serve
# open http://localhost:8000/docs/api

git checkout -b release/v0.1.20-phase20-api-documentation
git push -u origin release/v0.1.20-phase20-api-documentation
```

После этого сделать MR в `main` branch и остановиться.

После review и merge в `main`:

```bash
git checkout main
git pull origin main

git tag -a v0.1.20-phase20-api-documentation -m "File Converter Phase 20 API documentation"
git push origin v0.1.20-phase20-api-documentation
```
