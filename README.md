<p align="center">
  <h1 align="center">✨ Laravel API Magic</h1>
  <p align="center">Generate a complete REST API with a single command — Model, Migration, Controller, Request, Resource, and Tests.</p>
</p>

<p align="center">
  <a href="https://packagist.org/packages/arseno25/laravel-api-magic"><img src="https://img.shields.io/packagist/v/arseno25/laravel-api-magic.svg?style=flat-square" alt="Latest Version"></a>
  <a href="https://packagist.org/packages/arseno25/laravel-api-magic"><img src="https://img.shields.io/packagist/dt/arseno25/laravel-api-magic.svg?style=flat-square" alt="Total Downloads"></a>
</p>

---

## ⚡ Installation

```bash
composer require arseno25/laravel-api-magic
```

Publish the config file (optional):

```bash
php artisan vendor:publish --tag="laravel-api-magic-config"
```

---

## 🛠 Features

| # | Feature | Description |
|---|---------|-------------|
| 1 | **One-Command API** | Generate Model, Migration, Controller, Request, Resource & Test in one command |
| 2 | **Schema Parsing** | Define fields as `title:string\|required\|max:255` with automatic validation |
| 3 | **Relationships** | `--belongsTo`, `--hasMany`, `--belongsToMany` with auto foreign keys |
| 4 | **API Versioning** | Multi-version endpoints with `--v=2` flag |
| 5 | **Auto Testing** | Pest Feature test generation with `--test` flag |
| 6 | **Docs UI** | Swagger-like interactive documentation at `/api/docs` |
| 7 | **OpenAPI 3.0 Export** | Export to JSON/YAML for Postman, Insomnia, Redoc |
| 8 | **`#[ApiDeprecated]`** | Mark endpoints deprecated with migration hints |
| 9 | **`#[ApiResponse]`** | Define multiple response schemas per endpoint |
| 10 | **`#[ApiExample]`** | Attach request/response example payloads |
| 11 | **`#[ApiWebhook]`** | Document webhook events & payloads |
| 12 | **Code Snippets** | Auto-generated cURL, JavaScript, PHP, Python examples |
| 13 | **TypeScript SDK** | Full typed API client with `php artisan api-magic:ts --sdk` |
| 14 | **Health Telemetry** | Track response times, error rates per endpoint |
| 15 | **API Changelog** | Schema diff tracking between releases |
| 16 | **Deep Type Extraction** | Auto-extract `JsonResource` properties from `toArray()`, DocBlocks, and Model |
| 17 | **Insomnia Export** | Direct Insomnia v4 collection export with `format=insomnia` |
| 18 | **Request Chaining** | Pipe response values into the next request via `{{response.field}}` |
| 19 | **Request History** | Save, browse, and replay past API calls from the docs UI |
| 20 | **GraphQL Schema** | Auto-generate `.graphql` schema from REST endpoints |

---

## 🚀 Usage

### Interactive Mode

```bash
php artisan api:magic
```

### Command Line Mode

```bash
php artisan api:magic Post \
  "title:string|required|max:255,content:text|required,is_published:boolean|default:false" \
  --belongsTo="User" --hasMany="Comment" --test --v=1
```

### Command Options

| Option | Description | Example |
|--------|-------------|---------|
| `model` | Model name | `Post` |
| `schema` | Fields — `field:type\|rule` | `title:string\|required` |
| `--v=` | API version number | `--v=2` |
| `--belongsTo=` | BelongsTo relationships | `--belongsTo="Category,User"` |
| `--hasMany=` | HasMany relationships | `--hasMany="Comment,Review"` |
| `--belongsToMany=` | BelongsToMany relationships | `--belongsToMany="Tag,Role"` |
| `--test` | Generate Pest Feature test | `--test` |
| `--factory` | Generate Model Factory | `--factory` |
| `--seeder` | Generate Database Seeder | `--seeder` |
| `--soft-deletes` | Add Soft Deletes | `--soft-deletes` |
| `--force` | Overwrite existing files | `--force` |

---

## 📖 API Documentation

Access your interactive docs at:

```
http://your-app.test/api/docs
```

Customize with PHP 8 Attributes directly in your controllers:

```php
use Arseno25\LaravelApiMagic\Attributes\ApiGroup;
use Arseno25\LaravelApiMagic\Attributes\ApiDescription;

#[ApiGroup('User Management')]
#[ApiDescription('Retrieves a paginated list of all users.')]
public function index() { ... }
```

---

## 🏷️ PHP 8 Attributes

> All attributes are in the `Arseno25\LaravelApiMagic\Attributes` namespace.

### `#[ApiGroup]` — Endpoint Grouping

Groups endpoints under a named section in the sidebar.

```php
#[ApiGroup('Order Management')]
public function index() { ... }
```

### `#[ApiDescription]` — Endpoint Description

Adds a detailed description below the endpoint summary. Supports Markdown.

```php
#[ApiDescription('Returns a **paginated** list. Supports `search`, `sort`, and `filter` params.')]
public function index() { ... }
```

### `#[ApiDeprecated]` — Mark as Deprecated

Marks an endpoint as deprecated with a warning banner, strikethrough path, and optional migration hints. Sets `deprecated: true` in the OpenAPI export.

```php
#[ApiDeprecated(
    message: 'This endpoint will be removed in v3.0.',
    since: 'v2.1.0',
    alternative: '/api/v2/users'
)]
public function index() { ... }
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `message` | `string` | Deprecation message |
| `since` | `?string` | Version when deprecated |
| `alternative` | `?string` | Replacement endpoint path |

### `#[ApiResponse]` — Multi-Response Definitions *(Repeatable)*

Define multiple response schemas per endpoint. Each is exported to the OpenAPI spec.

```php
#[ApiResponse(status: 200, description: 'User found', resource: UserResource::class)]
#[ApiResponse(status: 404, description: 'Not found', example: ['message' => 'User not found'])]
#[ApiResponse(status: 422, description: 'Validation error')]
public function show(User $user) { ... }
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | `int` | HTTP status code (default: `200`) |
| `resource` | `?string` | Resource class name |
| `description` | `string` | Response description |
| `example` | `?array` | Example payload |
| `isArray` | `bool` | Collection response |

### `#[ApiExample]` — Request/Response Examples

Attach example payloads displayed side-by-side in the docs UI.

```php
#[ApiExample(
    request: ['name' => 'John Doe', 'email' => 'john@example.com'],
    response: ['id' => 1, 'name' => 'John Doe', 'created_at' => '2024-01-01T00:00:00Z']
)]
public function store(StoreUserRequest $request) { ... }
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `request` | `?array` | Example request body |
| `response` | `?array` | Example response body |

### `#[ApiWebhook]` — Webhook Documentation *(Repeatable)*

Document webhook events. All webhooks are displayed in a dedicated sidebar panel.

```php
#[ApiWebhook(
    event: 'order.completed',
    description: 'Fired when checkout completes',
    payload: ['order_id' => 'integer', 'total' => 'float', 'currency' => 'string']
)]
public function store(StoreOrderRequest $request) { ... }
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `event` | `string` | Event name (e.g., `order.completed`) |
| `description` | `string` | What triggers this webhook |
| `payload` | `?array` | Expected payload structure |

### `#[ApiMock]` — Mock Server

Return fake data based on your schema — no backend logic needed.

```php
#[ApiMock(statusCode: 200, count: 10)]
public function index() { ... }

// Or trigger via header: curl -H "X-Api-Mock: true" http://localhost:8000/api/products
```

> Enable globally via `.env`: `API_MAGIC_MOCK_ENABLED=true`

### `#[ApiCache]` — Response Caching

Automatically cache GET responses with a simple attribute.

```php
#[ApiCache(ttl: 60)] // Cache for 60 seconds
public function index() { ... }
```

> Returns `X-Api-Cache: HIT`/`MISS` header to confirm caching status.

---

## ⚙️ Artisan Commands

| Command | Description |
|---------|-------------|
| `php artisan api:magic` | Generate a complete API stack (Model, Migration, Controller, Request, Resource, Test) |
| `php artisan api-magic:ts` | Generate TypeScript interfaces from your API schema |
| `php artisan api-magic:ts --sdk` | Generate a full TypeScript API client SDK |
| `php artisan api-magic:export` | Export as OpenAPI 3.0 JSON/YAML or Postman Collection |
| `php artisan api-magic:cache` | Cache API documentation schema for production |
| `php artisan api-magic:reverse` | Reverse-engineer database tables into API stack |
| `php artisan api-magic:snapshot` | Save API schema snapshot for changelog tracking |
| `php artisan api-magic:graphql` | Generate GraphQL schema from REST API endpoints |

### TypeScript SDK

```bash
# Interfaces only
php artisan api-magic:ts
php artisan api-magic:ts --output=frontend/src/api-types.d.ts --namespace=Api

# Full SDK with typed methods
php artisan api-magic:ts --sdk
php artisan api-magic:ts --sdk --output=frontend/src/api-client.ts
```

Generated SDK usage:

```typescript
const api = createApiClient('http://localhost:8000', 'your-token');

const users = await api.getUsers({ page: 1, per_page: 15 });
const user  = await api.createUser({ name: 'John', email: 'john@example.com' });
const item  = await api.getProduct(42);
```

### Schema Snapshots & Changelog

```bash
php artisan api-magic:snapshot
```

Output:

```
📸 Taking API schema snapshot...
✅ Snapshot saved: storage/api-magic/changelog/2024-01-15_120000.json
📊 Endpoints captured: 24

📝 Changes since last snapshot:
  + 2 endpoint(s) added
    + GET /api/v2/orders
    + POST /api/v2/orders
  ~ 1 endpoint(s) changed
    ~ GET /api/users
```

> Enable in config: `'changelog' => ['enabled' => true]`

### Reverse Engineering

```bash
php artisan api-magic:reverse --table=products
php artisan api-magic:reverse --all --exclude=users,migrations
php artisan api-magic:reverse --all --v=1 --test --factory --seeder
```

### OpenAPI & Postman Export

```bash
php artisan api-magic:export --format=json
php artisan api-magic:export --format=yaml

# Postman Collection via URL:
curl http://localhost:8000/api/docs/export?format=postman -o postman.json
```

---

## 🔮 Advanced Features

### Code Snippets

Every endpoint has a **"Snippets"** button generating ready-to-use code in:

- **cURL** — Terminal commands
- **JavaScript** — `fetch()` with async/await
- **PHP** — Laravel `Http::` client
- **Python** — `requests` library

> Snippets auto-include your bearer token and request body.

### API Health Telemetry

Track response times, error rates, and usage per endpoint:

```php
Route::middleware(['api.health'])->group(function () {
    Route::apiResource('products', ProductController::class);
});
```

```bash
GET /api/docs/health
```

```json
{
  "metrics": [
    {
      "endpoint": "GET /api/products",
      "total_requests": 1250,
      "avg_response_ms": 45.2,
      "error_rate": 0.24
    }
  ]
}
```

> Enable in config: `'health' => ['enabled' => true]`

### Server Environments

Define multiple server environments for the docs UI dropdown and OpenAPI export:

```php
// config/api-magic.php
'servers' => [
    ['url' => 'https://api.example.com', 'description' => 'Production'],
    ['url' => 'https://staging-api.example.com', 'description' => 'Staging'],
    ['url' => 'http://localhost:8000', 'description' => 'Local'],
],
```

### RBAC Auto-Detection

Automatically detects Spatie Permission middleware and displays badges:

- 🔴 **Auth** — Bearer token required
- 🟣 **Roles** — `role:admin|editor`
- 🟡 **Permissions** — `permission:manage-users`
- 🔵 **Rate Limited** — Throttle middleware detected

### Deep Type Extraction

The `ResourceAnalyzer` automatically extracts real properties from your `JsonResource` classes using 3 strategies:

1. **Source parsing** — reads `$this->field` from `toArray()`
2. **DocBlock** — reads `@property` annotations on the resource class
3. **Model fallback** — uses `$fillable`, `$casts`, and timestamps from the underlying Eloquent model

### Insomnia Collection Export

```bash
curl http://localhost:8000/api/docs/export?format=insomnia -o insomnia.json
```

Exports a full Insomnia v4 collection with workspace, folders, environment variables (`base_url`, `token`), and request bodies.

### Request Chaining

In the docs UI, use `{{response.field}}` in any input field to reference the last API response:

```
# Path param:     {{response.data.id}}
# Body field:     {{response.data.name}}
# Deep access:    {{response.data.user.email}}
```

### Request History

Click **"Request History"** in the sidebar to view past API calls. Each entry shows method, path, status code, and response time. Click any entry to replay it with the original request body.

- Max 50 entries, stored in `localStorage`
- One-click replay with auto-restored body
- Clear all history button

### GraphQL Schema Generation

```bash
php artisan api-magic:graphql
php artisan api-magic:graphql --output=frontend/schema.graphql
```

Auto-generates a complete GraphQL SDL from your REST endpoints:
- GET endpoints → `Query` type
- POST/PUT/DELETE → `Mutation` type
- Request bodies → `input` types
- Response schemas → output `type`s

---

## 📡 API Docs Routes

| Method | Route | Description |
|--------|-------|-------------|
| `GET` | `/api/docs` | Interactive documentation UI |
| `GET` | `/api/docs/json` | Raw JSON schema |
| `GET` | `/api/docs/export` | OpenAPI 3.0 / Postman export |
| `GET` | `/api/docs/health` | Health telemetry metrics |
| `GET` | `/api/docs/changelog` | Schema diff between snapshots |
| `GET` | `/api/docs/code-snippet` | Code snippets for an endpoint |

Export formats: `openapi` (default), `postman`, `insomnia`.

---

## ⚙️ Configuration

```bash
php artisan vendor:publish --tag="laravel-api-magic-config"
```

Key options in `config/api-magic.php`:

```php
return [
    'docs' => [
        'prefix'     => 'docs',       // Route prefix (/api/docs)
        'middleware'  => [],           // Middleware for docs routes
    ],

    'servers' => [
        ['url' => env('APP_URL', 'http://localhost'), 'description' => 'Default Server'],
    ],

    'health' => [
        'enabled' => env('API_MAGIC_HEALTH_ENABLED', false),
    ],

    'changelog' => [
        'enabled'      => env('API_MAGIC_CHANGELOG_ENABLED', false),
        'storage_path' => storage_path('api-magic/changelog'),
    ],
];
```

Customize generated code stubs:

```bash
php artisan vendor:publish --tag="api-magic-stubs"
```

---

## 🧪 Testing

**179 tests** · **446+ assertions** · PHPStan Level 5

```bash
composer test          # or vendor/bin/pest
vendor/bin/phpstan analyse
```

---

## ✅ Recently Completed

- **Deep Type Extraction** — `ResourceAnalyzer` parses toArray(), DocBlocks, and Model properties
- **Insomnia Export** — Direct Insomnia v4 collection export
- **Request Chaining** — Pipe response values between API requests in docs UI
- **Request History** — Save, browse, and replay past API calls
- **GraphQL Support** — Auto-generate `.graphql` schema with `php artisan api-magic:graphql`

---

## 🐛 Issues

If you discover any bugs, missing features, or issues, please [open an issue](https://github.com/Arseno25/laravel-api-generator/issues) on the GitHub repository.

When reporting an issue, please try to include:
- Your Laravel version
- Your `arseno25/laravel-api-magic` package version
- Clear steps to reproduce the issue
- Expected vs actual behavior

---

## 🤝 Contributing

Contributions are completely welcome! Whether it's adding a new feature, fixing a bug, or improving the documentation, we'd love your help.

### How to Contribute:
1. **Fork** the repository.
2. Create a new branch for your feature or bugfix (`git checkout -b feature/amazing-feature`).
3. Make your changes and ensure all tests are passing (`vendor/bin/pest && vendor/bin/phpstan analyse`).
4. Commit your changes with descriptive messages (`git commit -m 'feat: add amazing feature'`).
5. Push to your branch (`git push origin feature/amazing-feature`).
6. Open a **Pull Request** against the main repository.

> **Note:** Please ensure that you write unit/feature tests for any new features or bug fixes to maintain stability.

---

## 📝 License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

---

<p align="center">
  Created with ❤️ by <a href="https://github.com/Arseno25"><strong>Arseno25</strong></a><br>
  <em>"Magic is just beautifully organized code."</em>
</p>
