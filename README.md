# Laravel API Magic

[![Latest Version on Packagist](https://img.shields.io/packagist/v/arseno25/laravel-api-magic.svg?style=flat-square)](https://packagist.org/packages/arseno25/laravel-api-magic)
[![Total Downloads](https://img.shields.io/packagist/dt/arseno25/laravel-api-magic.svg?style=flat-square)](https://packagist.org/packages/arseno25/laravel-api-magic)

Laravel API Magic adalah package yang powerful untuk generating complete REST API dengan satu command. Package ini secara otomatis membuat Model, Migration, Controller, Form Request, API Resource, dan optional Pest Test.

Fitur utamanya adalah **API Documentation UI** yang modern dan interaktif seperti Swagger, memungkinkan Anda mencoba endpoint langsung dari browser.

## Features

- **One Command API Generation** - Generate complete REST API dengan single artisan command
- **Auto Documentation** - Dokumentasi API otomatis tergenerate
- **Interactive UI** - Swagger-like UI untuk mencoba endpoint langsung dari browser
- **API Versioning Support** - Built-in support untuk API versioning (v1, v2, etc.)
- **Relationship Support** - Easy BelongsTo dan HasMany relationships
- **Pest Testing** - Optional Pest feature test generation
- **Smart Schema Parser** - Parse validation rules otomatis untuk documentation
- **Request Body Examples** - Auto-generate example request bodies untuk POST/PUT/PATCH
- **Beautiful Error Display** - Clean dan informative error responses

## Installation

Install package via composer:

```bash
composer require arseno25/laravel-api-magic
```

Publish dan jalankan migration:

```bash
php artisan vendor:publish --tag="laravel-api-magic-migrations"
php artisan migrate
```

Publish config file (optional):

```bash
php artisan vendor:publish --tag="laravel-api-magic-config"
```

Publish views jika ingin kustomisasi UI:

```bash
php artisan vendor:publish --tag="laravel-api-magic-views"
```

## Usage

### Generate API

Basic usage:

```bash
php artisan api:magic Product --schema="title:string|required,slug:string|required|unique:products,price:integer|min:0,description:text,is_published:boolean"
```

Dengan API versioning:

```bash
php artisan api:magic Product --schema="name:string|required,price:integer|min:0" --v=2
```

Dengan relationships:

```bash
php artisan api:magic Product --schema="name:string|required,category_id:integer|required" --belongsTo="category" --hasMany="reviews"
```

Generate dengan Pest test:

```bash
php artisan api:magic User --schema="name:string|required,email:email|required|unique:users,password:string|required|min:8" --test
```

Overwrite existing files:

```bash
php artisan api:magic Product --schema="..." --force
```

### Schema Format

Schema format: `field_name:type|validation_rules`

| Type | Description |
|------|-------------|
| `string` | VARCHAR field |
| `text` | TEXT field |
| `integer` | INT field |
| `bigint` | BIGINT field |
| `boolean` | BOOLEAN field |
| `decimal` | DECIMAL field |
| `datetime` | DATETIME field |
| `timestamp` | TIMESTAMP field |

Validation rules menggunakan Laravel validation syntax:

```bash
php artisan api:magic Post --schema="
    title:string|required|min:5|max:255,
    slug:string|required|unique:posts,
    content:text|required,
    category_id:integer|required|exists:categories,id,
    published_at:datetime|nullable,
    is_featured:boolean|default:false
"
```

### Register Routes

Setelah generate API, tambahkan route di `routes/api.php`:

```php
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\V2\ProductController as ProductControllerV2;

// API v1 (default)
Route::apiResource('products', ProductController::class);

// API v2
Route::apiResource('v2/products', ProductControllerV2::class);
```

### API Documentation UI

Akses dokumentasi API di:

```
http://your-app.test/api/docs
```

Fitur Documentation UI:

- **Endpoint Browser** - List semua endpoint dengan method badges
- **Search** - Filter endpoints by path atau summary
- **Try It Out** - Kirim request langsung dari UI
- **Authentication** - Set Bearer token untuk authenticated requests
- **Request Body Examples** - Auto-generate example JSON untuk POST/PUT/PATCH
- **Response Display** - Beautiful formatted responses dengan status badges
- **Error Details** - Clean error display dengan validation errors breakdown
- **Request History** - Track request statistics di sidebar

### Cache Documentation

Untuk production, cache documentation schema untuk better performance:

```bash
// Generate cache
php artisan api-magic:cache

// Clear cache
php artisan api-magic:cache --clear

// Custom cache path
php artisan api-magic:cache --path=/path/to/cache.json
```

## Examples

### Blog API

```bash
php artisan api:magic Post --schema="
    title:string|required|min:5|max:255,
    slug:string|required|unique:posts,
    content:text|required,
    excerpt:text|nullable,
    category_id:integer|required|exists:categories,id,
    author_id:integer|required|exists:users,id,
    published_at:datetime|nullable,
    is_featured:boolean|default:false
" --belongsTo="category,author" --hasMany="comments,tags" --test
```

### E-commerce Product API

```bash
php artisan api:magic Product --schema="
    name:string|required|max:255,
    slug:string|required|unique:products,
    description:text|required,
    price:decimal:8,2|required|min:0,
    compare_price:decimal:8,2|nullable|min:0,
    sku:string|required|unique:products,
    barcode:string|nullable|unique:products,
    track_stock:boolean|default:true,
    quantity:integer|default:0,
    category_id:integer|required|exists:categories,id,
    is_active:boolean|default=true
" --belongsTo="category" --hasMany="images,reviews" --v=1
```

### User Profile API

```bash
php artisan api:magic Profile --schema="
    user_id:integer|required|exists:users,id,
    bio:text|nullable,
    avatar:string|nullable|max:255,
    phone:string|nullable|max:20,
    address:text|nullable,
    city:string|nullable|max:100,
    country:string|nullable|max:100,
    postal_code:string|nullable|max:20,
    birth_date:date|nullable
" --v=2
```

## Generated Files

Setiap `api:magic` command akan generate:

| File | Location |
|------|----------|
| Model | `app/Models/{Model}.php` |
| Migration | `database/migrations/YYYY_MM_DD_HHMMSS_create_{table}_table.php` |
| Controller | `app/Http/Controllers/Api/{Model}Controller.php` |
| Form Request | `app/Http/Requests/{Model}Request.php` |
| API Resource | `app/Http/Resources/{Model}Resource.php` |
| Pest Test | `tests/Feature/Api/{Model}Test.php` (optional) |

## API Endpoints

Generated API resource menyediakan endpoints:

| Method | URI | Action |
|--------|-----|--------|
| GET | `/api/{resource}` | index (list all) |
| POST | `/api/{resource}` | store (create new) |
| GET | `/api/{resource}/{id}` | show (detail) |
| PUT/PATCH | `/api/{resource}/{id}` | update (edit) |
| DELETE | `/api/{resource}/{id}` | destroy (delete) |

## Configuration

Publish config untuk kustomisasi:

```bash
php artisan vendor:publish --tag="laravel-api-magic-config"
```

Config file: `config/api-magic.php`

## Testing

Run package tests:

```bash
composer test
```

Run Pest tests untuk generated APIs:

```bash
php artisan test --tests/Feature/Api/ProductTest.php
```

## Troubleshooting

### Documentation UI Blank Page

Jika documentation UI tampil blank:

```bash
php artisan view:clear
php artisan config:clear
php artisan route:clear
```

### Documentation Not Updating

Clear dan regenerate cache:

```bash
php artisan api-magic:cache --clear
php artisan api-magic:cache
```

### Routes 404

Pastikan route terdaftar di `routes/api.php`:

```php
Route::prefix('docs')->group(function () {
    Route::get('/', [Arseno25\LaravelApiMagic\Http\Controllers\DocsController::class, 'index'])->name('api.docs.index');
});
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Arseno25](https://github.com/Arseno25)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
