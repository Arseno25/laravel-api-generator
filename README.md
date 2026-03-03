# Laravel API Magic

[![Latest Version on Packagist](https://img.shields.io/packagist/v/arseno25/laravel-api-magic.svg?style=flat-square)](https://packagist.org/packages/arseno25/laravel-api-magic)
[![Total Downloads](https://img.shields.io/packagist/dt/arseno25/laravel-api-magic.svg?style=flat-square)](https://packagist.org/packages/arseno25/laravel-api-magic)

> Generate complete REST API with a single command - Model, Migration, Controller, Request, Resource, and Tests!

Laravel API Magic adalah package yang powerful untuk generating complete REST API dengan satu artisan command. Package ini secara otomatis membuat Model, Migration, Controller, Form Request, API Resource, dan optional Pest Test dengan interactive prompts.

## Features

### Core Features
- **One Command Generation** - Generate complete REST API dengan single command
- **Interactive Mode** - Guided prompts untuk mudah mengkonfigurasi API
- **Smart Schema Parser** - Parse validation rules otomatis untuk migration dan request
- **Relationship Support** - Easy BelongsTo dan HasMany relationships
- **Pest Testing** - Optional Pest feature test generation

### API Documentation
- **Interactive Documentation UI** - Swagger-like UI untuk mencoba endpoint langsung dari browser
- **Auto Documentation** - Dokumentasi API otomatis tergenerate
- **Search & Filter** - Filter endpoints by path atau summary
- **Try It Out** - Kirim request langsung dari UI
- **Authentication Support** - Set Bearer token untuk authenticated requests
- **Request Body Examples** - Auto-generate example JSON untuk POST/PUT/PATCH
- **Smart Caching** - Cache documentation schema untuk better performance

---

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

---

## Usage

### Interactive Mode (Recommended)

Mode interaktif adalah cara paling mudah untuk generate API. Cukup jalankan command tanpa argumen:

```bash
php artisan api:magic
```

Anda akan diarahkan melalui prompts:

```
✨ API Magic - Interactive Setup

  What is the Model name? > Product

  ➤ Would you like to define fields interactively? Yes

  Field name: title
  Select type: string
  Is required? Yes
  ✓ Added: title (string, required)

  ➤ Add belongsTo relationship? Yes
  Related model name: Category
  ✓ Added belongsTo: Category

  📊 Configuration Summary
  ┌─────────────────────────────────────┐
  │ Component   │ Details                │
  ├─────────────────────────────────────┤
  │ Model       │ Product                │
  │ Fields      │ 2 fields               │
  │ Relations   │ 1 BelongsTo | 1 HasMany │
  └─────────────────────────────────────┘

  ✨ API Generated Successfully!
```

### Command Options

```
Usage:
  api:magic [options] [--] [<model> [<schema>]]

Arguments:
  model                 The name of the Model
  schema                Field schema (e.g., "title:string|required,price:integer|min:0")

Options:
  --test                Generate a Pest feature test
  --belongsTo=          BelongsTo relations (e.g., "category,user")
  --hasMany=            HasMany relations (e.g., "comments,review")
  --v=                  API version number (default: 1)
  --force               Overwrite existing files
  --no-interaction      Run without prompts
```

---

## Schema Format

### Field Types

| Type | Description | Database |
|------|-------------|----------|
| `string` | VARCHAR field | VARCHAR(255) |
| `text` | Long text field | TEXT |
| `integer` | Integer field | INTEGER |
| `decimal` | Decimal field | DECIMAL |
| `boolean` | Boolean field | BOOLEAN |
| `datetime` | DateTime field | DATETIME |
| `date` | Date field | DATE |

### Validation Rules

Gunakan Laravel validation syntax:

```bash
php artisan api:magic Post "title:string|required|min:5|max:255,slug:string|required|unique:posts,content:text|required,category_id:integer|required|exists:categories,id,published_at:datetime|nullable,is_featured:boolean|default:false"
```

---

## Examples

### 1. Interactive Mode

```bash
php artisan api:magic
```

### 2. Quick Generate

```bash
php artisan api:magic Product
```

### 3. With Schema

```bash
php artisan api:magic Product "title:string|required,price:integer|min:0,description:text"
```

### 4. With Relationships

```bash
php artisan api:magic Product "name:string|required,category_id:integer|required" --belongsTo="Category" --hasMany="Review"
```

### 5. API Versioning

```bash
php artisan api:magic Product "name:string|required" --v=2
```

### 6. Complete Blog Post

```bash
php artisan api:magic Post "
  title:string|required|min:5|max:255,
  slug:string|required|unique:posts,
  content:text|required,
  category_id:integer|required|exists:categories,id,
  author_id:integer|required|exists:users,id
" --belongsTo="Category,Author" --hasMany="Comment" --test
```

### 7. Non-Interactive (Automation)

```bash
php artisan api:magic Product "title:string|required" --no-interaction --test
```

---

## Generated Files

| File | Location |
|------|----------|
| **Model** | `app/Models/{Model}.php` |
| **Migration** | `database/migrations/YYYY_MM_DD_HHMMSS_create_{table}_table.php` |
| **Controller** | `app/Http/Controllers/Api/{Model}Controller.php` |
| **V2 Controller** | `app/Http/Controllers/Api/V2/{Model}Controller.php` (if v=2) |
| **Form Request** | `app/Http/Requests/{Model}Request.php` |
| **API Resource** | `app/Http/Resources/{Model}Resource.php` |
| **Pest Test** | `tests/Feature/Api/{Model}Test.php` (with --test) |

---

## Register Routes

Tambahkan route di `routes/api.php`:

```php
use App\Http\Controllers\Api\ProductController;

// API v1
Route::apiResource('products', ProductController::class);

// API v2
Route::apiResource('v2/products', \App\Http\Controllers\Api\V2\ProductController::class);
```

---

## API Documentation UI

Akses dokumentasi API di:

```
http://your-app.test/api/docs
```

### Documentation Features

| Feature | Description |
|---------|-------------|
| **Search** | Filter endpoints by path atau summary |
| **Try It Out** | Kirim request langsung dari UI |
| **Authentication** | Set Bearer token untuk authenticated requests |
| **Request Examples** | Auto-generate example JSON untuk POST/PUT/PATCH |
| **Response Display** | Beautiful formatted responses dengan status badges |
| **Error Details** | Clean error display dengan validation errors breakdown |

---

## Cache Documentation

Untuk production, cache documentation untuk better performance:

```bash
php artisan api-magic:cache
php artisan api-magic:cache --clear
```

---

## API Endpoints

Generated API resource menyediakan standard RESTful endpoints:

| Method | URI | Action | Description |
|--------|-----|--------|-------------|
| `GET` | `/api/{resource}` | `index` | List semua resources |
| `POST` | `/api/{resource}` | `store` | Create new resource |
| `GET` | `/api/{resource}/{id}` | `show` | Get detail resource |
| `PUT/PATCH` | `/api/{resource}/{id}` | `update` | Update resource |
| `DELETE` | `/api/{resource}/{id}` | `destroy` | Delete resource |

---

## Testing

Run generated tests:

```bash
php artisan test tests/Feature/Api/ProductTest.php
```

---

## Configuration

Publish config untuk kustomisasi:

```bash
php artisan vendor:publish --tag="laravel-api-magic-config"
```

---

## Troubleshooting

### Documentation UI Blank Page

```bash
php artisan view:clear
php artisan config:clear
php artisan api-magic:cache --clear
php artisan api-magic:cache
```

### Command Not Found

```bash
composer dump-autoload
php artisan package:discover
```

---

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information.

---

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

---

## Security

Please review [our security policy](../../security/policy) on how to report vulnerabilities.

---

## Credits

- [Arseno25](https://github.com/Arseno25)
- [All Contributors](../../contributors)

---

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
