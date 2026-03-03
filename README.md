<div align="center">
  <h1 style="color: #FF2D20;">✨ Laravel API Magic</h1>
  <p style="color: #E5E7EB; font-size: 1.1em;">Generate a complete REST API with a single command — Model, Migration, Controller, Request, Resource, and Tests.</p>
  
  <p>
    <a href="https://packagist.org/packages/arseno25/laravel-api-magic"><img src="https://img.shields.io/packagist/v/arseno25/laravel-api-magic.svg?style=flat-square" alt="Latest Version"></a>
    <a href="https://packagist.org/packages/arseno25/laravel-api-magic"><img src="https://img.shields.io/packagist/dt/arseno25/laravel-api-magic.svg?style=flat-square" alt="Total Downloads"></a>
  </p>
</div>

<hr style="border: 1px solid #374151;" />

<h2 style="color: #F9FAFB;">⚡ Installation</h2>

<p style="color: #D1D5DB;">Install the package via Composer:</p>

```bash
composer require arseno25/laravel-api-magic
```

<hr style="border: 1px solid #374151;" />

<h2 style="color: #F9FAFB;">🛠 Main Features</h2>

<ul style="color: #D1D5DB; line-height: 1.6;">
  <li><strong style="color: #EF4444;">One Command API Setup:</strong> Generate fully functional API endpoints in seconds.</li>
  <li><strong style="color: #3B82F6;">Flexible Schema Parsing:</strong> Define column types and validation rules simply (e.g., <code style="color: #F9FAFB; background-color: #1F2937; padding: 2px 4px; border-radius: 4px;">title:string|required</code>).</li>
  <li><strong style="color: #10B981;">Smart Relationships:</strong> Define <code style="color: #F9FAFB; background-color: #1F2937; padding: 2px 4px; border-radius: 4px;">BelongsTo</code> and <code style="color: #F9FAFB; background-color: #1F2937; padding: 2px 4px; border-radius: 4px;">HasMany</code> relations cleanly in the command.</li>
  <li><strong style="color: #A78BFA;">API Versioning:</strong> Easily scaffold multi-version endpoints using flags like <code style="color: #F9FAFB; background-color: #1F2937; padding: 2px 4px; border-radius: 4px;">--v=2</code>.</li>
  <li><strong style="color: #FCD34D;">Auto Feature Testing:</strong> Includes Pest Test scaffolding fully integrated with your API.</li>
  <li><strong style="color: #F472B6;">Built-in API Documentation UI:</strong> Swagger-like UI dynamically generating docs for all endpoints at <code style="color: #F9FAFB; background-color: #1F2937; padding: 2px 4px; border-radius: 4px;">/api/docs</code>.</li>
  <li><strong style="color: #14B8A6;">Advanced OpenAPI Specs:</strong> Supports file uploads (<code>multipart/form-data</code>), Enum UI dropdowns, accurate JSON Resource bindings, and static exporting.</li>
</ul>

<hr style="border: 1px solid #374151;" />

<h2 style="color: #F9FAFB;">🚀 Usage</h2>

<h3 style="color: #E5E7EB;">Interactive Mode</h3>

<p style="color: #D1D5DB;">The easiest way to generate an API is by running the command with no arguments. It will guide you via interactive prompts.</p>

```bash
php artisan api:magic
```

<h3 style="color: #E5E7EB;">Command Line Mode</h3>

<p style="color: #D1D5DB;">You can bypass prompts for fast scaffolding by providing arguments.</p>

```bash
php artisan api:magic Post "title:string|required|max:255,content:text|required,is_published:boolean|default:false" --belongsTo="User" --hasMany="Comment" --test --v=1
```

<h4 style="color: #D1D5DB;">Command Options:</h4>
<table border="1" cellpadding="10" cellspacing="0" width="100%" style="border-collapse: collapse; border-color: #374151; text-align: left; color: #D1D5DB;">
  <thead style="background-color: #1F2937; color: #F9FAFB;">
    <tr>
      <th align="left">Option</th>
      <th align="left">Description</th>
      <th align="left">Example</th>
    </tr>
  </thead>
  <tbody style="background-color: #111827;">
    <tr>
      <td style="color: #EF4444; font-weight: 500;"><code>model</code></td>
      <td>Name of the Model to generate.</td>
      <td style="background-color: #1F2937;"><code>Post</code></td>
    </tr>
    <tr>
      <td style="color: #EF4444; font-weight: 500;"><code>schema</code></td>
      <td>Field schema format: <code>field:type|rule</code>. Available types: <code>string</code>, <code>text</code>, <code>integer</code>, <code>boolean</code>, <code>date</code>, <code>datetime</code>, <code>decimal</code>.</td>
      <td style="background-color: #1F2937;"><code>title:string|required</code></td>
    </tr>
    <tr>
      <td style="color: #3B82F6; font-weight: 500;"><code>--v=</code></td>
      <td>Specify API version number.</td>
      <td style="background-color: #1F2937;"><code>--v=2</code></td>
    </tr>
    <tr>
      <td style="color: #10B981; font-weight: 500;"><code>--belongsTo=</code></td>
      <td>Comma-separated BelongsTo relationships.</td>
      <td style="background-color: #1F2937;"><code>--belongsTo="Category,User"</code></td>
    </tr>
    <tr>
      <td style="color: #10B981; font-weight: 500;"><code>--hasMany=</code></td>
      <td>Comma-separated HasMany relationships.</td>
      <td style="background-color: #1F2937;"><code>--hasMany="Comment,Review"</code></td>
    </tr>
    <tr style="background-color: #111827;">
      <td style="color: #10B981; font-weight: 500;"><code>--belongsToMany=</code></td>
      <td>Comma-separated BelongsToMany relationships.</td>
      <td style="background-color: #1F2937;"><code>--belongsToMany="Tag,Role"</code></td>
    </tr>
    <tr>
      <td style="color: #FCD34D; font-weight: 500;"><code>--test</code></td>
      <td>Generate a Pest Feature test alongside the API.</td>
      <td style="background-color: #1F2937;"><code>--test</code></td>
    </tr>
    <tr style="background-color: #111827;">
      <td style="color: #FCD34D; font-weight: 500;"><code>--factory</code></td>
      <td>Generate a Model Factory.</td>
      <td style="background-color: #1F2937;"><code>--factory</code></td>
    </tr>
    <tr>
      <td style="color: #FCD34D; font-weight: 500;"><code>--seeder</code></td>
      <td>Generate a Database Seeder class.</td>
      <td style="background-color: #1F2937;"><code>--seeder</code></td>
    </tr>
    <tr style="background-color: #111827;">
      <td style="color: #A78BFA; font-weight: 500;"><code>--soft-deletes</code></td>
      <td>Add Soft Deletes to the model and migration.</td>
      <td style="background-color: #1F2937;"><code>--soft-deletes</code></td>
    </tr>
    <tr>
      <td style="color: #9CA3AF; font-weight: 500;"><code>--force</code></td>
      <td>Overwrite existing generated files.</td>
      <td style="background-color: #1F2937;"><code>--force</code></td>
    </tr>
  </tbody>
</table>

<h3 style="color: #E5E7EB; margin-top: 24px;">API Documentation</h3>

<p style="color: #D1D5DB;">View your auto-generated endpoints, test APIs from the browser, and view schema definitions by visiting:</p>

```bash
http://your-app.test/api/docs
```

<p style="color: #D1D5DB; margin-top: 16px;">Customize your API documentation using the provided PHP 8 Attributes directly in your controllers:</p>

```php
use Arseno25\LaravelApiMagic\Attributes\ApiGroup;
use Arseno25\LaravelApiMagic\Attributes\ApiDescription;

#[ApiGroup('User Management')]
#[ApiDescription('Retrieves a paginated list of all active users.')]
public function index() { ... }
```

<p style="color: #D1D5DB; margin-top: 16px;">Export your complete OpenAPI schema to a static JSON or YAML file for tools like Postman, Insomnia, or Redoc:</p>

```bash
php artisan api-magic:export --format=json
php artisan api-magic:export --format=yaml
```

<p style="color: #D1D5DB; margin-top: 16px;">To optimize for production, cache your schema:</p>

```bash
php artisan api-magic:cache
```

<h3 style="color: #E5E7EB; margin-top: 24px;">⚙️ Configuration & Customization</h3>

<p style="color: #D1D5DB;">You can publish the configuration file to customize API documentation routes, middleware, and default generation settings (like seeder count):</p>

```bash
php artisan vendor:publish --tag="laravel-api-magic-config"
```

<p style="color: #D1D5DB; margin-top: 16px;">Need to change the generated code structure? Publish the package stubs to customize Models, Controllers, and Requests:</p>

```bash
php artisan vendor:publish --tag="api-magic-stubs"
```

<hr style="border: 1px solid #374151;" />

<h2 style="color: #F9FAFB;">🗺️ Roadmap & Future Features</h2>

<p style="color: #D1D5DB;">We're continuously improving the package to match strict Enterprise OpenAPI specs. Upcoming features include:</p>

<ul style="color: #D1D5DB; line-height: 1.6;">
  <li><strong>Deprecation Attributes:</strong> `#[ApiDeprecated]` support to visually strikethrough retired endpoints in Swagger UI.</li>
  <li><strong>Deep Type Extraction:</strong> Utilizing DocBlock parsing safely to fully evaluate `JsonResource` nested properties without Laravel Model instantiation.</li>
</ul>

<hr style="border: 1px solid #374151;" />

<h2 style="color: #F9FAFB;">🧪 Testing</h2>

<p style="color: #D1D5DB;">This package comes with a comprehensive <a href="https://pestphp.com/" style="color: #60A5FA; text-decoration: none;">Pest</a> test suite mapped across 130+ assertions to ensure stability. Run the tests using:</p>

```bash
composer test
# or
vendor/bin/pest
```

<hr style="border: 1px solid #374151;" />

<h2 style="color: #F9FAFB;">📝 License</h2>

<p style="color: #D1D5DB;">
  This package is open-sourced software licensed under the <strong><a href="LICENSE.md" style="color: #60A5FA; text-decoration: none;">MIT license</a></strong>.
</p>

<hr style="border: 1px solid #374151; margin: 40px 0 20px 0;" />

<div align="center" style="font-family: Arial, sans-serif; margin-top: 40px;">
  <p style="font-size: 1.1em; margin-bottom: 8px; color: #E5E7EB;">Created with ❤️ by <strong><a href="https://github.com/Arseno25" style="color: #EF4444; text-decoration: none;">Arseno25</a></strong></p>
  <p style="font-size: 0.9em; max-width: 500px; margin: 0 auto; line-height: 1.5; color: #D1D5DB;">
    Empowering Laravel developers to build APIs faster and write less boilerplate. <br>
    <em style="color: #9CA3AF;">"Magic is just beautifully organized code."</em>
  </p>
</div>
