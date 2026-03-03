<?php

use Arseno25\LaravelApiMagic\Commands\GenerateApiCommand;
use Illuminate\Support\Facades\File;

uses()->group('commands', 'generate-api');

beforeEach(function () {
    // Clean up models
    $modelPath = app_path('Models/Product.php');
    $personPath = app_path('Models/Person.php');
    if (File::exists($modelPath)) {
        File::delete($modelPath);
    }
    if (File::exists($personPath)) {
        File::delete($personPath);
    }

    // Clean up controllers
    $controllerPaths = [
        app_path('Http/Controllers/Api/ProductController.php'),
        app_path('Http/Controllers/Api/V2/ProductController.php'),
    ];
    foreach ($controllerPaths as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    // Clean up requests
    $requestPath = app_path('Http/Requests/ProductRequest.php');
    if (File::exists($requestPath)) {
        File::delete($requestPath);
    }

    // Clean up resources
    $resourcePaths = [
        app_path('Http/Resources/ProductResource.php'),
        app_path('Http/Resources/V2/ProductResource.php'),
    ];
    foreach ($resourcePaths as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    // Clean up migrations
    $migrationFiles = array_merge(
        glob(database_path('migrations/*_create_products_table.php')),
        glob(database_path('migrations/*_create_people_table.php'))
    );
    foreach ($migrationFiles as $file) {
        if (File::exists($file)) {
            File::delete($file);
        }
    }

    // Clean up tests
    $testPaths = [
        base_path('tests/Feature/Api/ProductTest.php'),
        base_path('tests/Feature/Api/V2/ProductTest.php'),
    ];
    foreach ($testPaths as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }
});

afterEach(function () {
    // Clean up after each test
    $modelPath = app_path('Models/Product.php');
    $personPath = app_path('Models/Person.php');
    if (File::exists($modelPath)) {
        File::delete($modelPath);
    }
    if (File::exists($personPath)) {
        File::delete($personPath);
    }

    $controllerPaths = [
        app_path('Http/Controllers/Api/ProductController.php'),
        app_path('Http/Controllers/Api/V2/ProductController.php'),
    ];
    foreach ($controllerPaths as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    $requestPath = app_path('Http/Requests/ProductRequest.php');
    if (File::exists($requestPath)) {
        File::delete($requestPath);
    }

    $resourcePaths = [
        app_path('Http/Resources/ProductResource.php'),
        app_path('Http/Resources/V2/ProductResource.php'),
    ];
    foreach ($resourcePaths as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    $migrationFiles = array_merge(
        glob(database_path('migrations/*_create_products_table.php')),
        glob(database_path('migrations/*_create_people_table.php'))
    );
    foreach ($migrationFiles as $file) {
        if (File::exists($file)) {
            File::delete($file);
        }
    }

    $testPaths = [
        base_path('tests/Feature/Api/ProductTest.php'),
        base_path('tests/Feature/Api/V2/ProductTest.php'),
    ];
    foreach ($testPaths as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }
});

it('has the correct signature', function () {
    $command = app(GenerateApiCommand::class);

    expect($command->getSignature())->toBe('api:magic');
});

describe('schema validation', function () {
    it('fails when schema option is not provided', function () {
        $this->artisan('api:magic Product')
            ->expectsOutputToContain('The --schema option is required')
            ->assertExitCode(1);
    });

    it('fails when schema is empty', function () {
        $this->artisan('api:magic Product --schema=')
            ->expectsOutputToContain('The --schema option is required')
            ->assertExitCode(1);
    });
});

describe('file generation', function () {
    it('generates model file correctly', function () {
        $this->artisan('api:magic Product --schema="name:string|required"')
            ->assertExitCode(0);

        expect(app_path('Models/Product.php'))->toFileExist();
        $content = File::get(app_path('Models/Product.php'));

        expect($content)->toContain('class Product extends Model');
        expect($content)->toContain('protected $fillable = [');
        expect($content)->toContain("'name'");
    });

    it('generates migration file correctly', function () {
        $this->artisan('api:magic Product --schema="name:string|required,price:integer|min:0"')
            ->assertExitCode(0);

        $migrationFiles = glob(database_path('migrations/*_create_products_table.php'));
        expect($migrationFiles)->not->toBeEmpty();

        $migrationPath = $migrationFiles[array_key_first($migrationFiles)];
        $content = File::get($migrationPath);

        expect($content)->toContain('Schema::create');
        expect($content)->toContain('$table->string(\'name\')');
        expect($content)->toContain('$table->integer(\'price\')');
        expect($content)->toContain('->min(0)');
    });

    it('generates api controller correctly', function () {
        $this->artisan('api:magic Product --schema="name:string|required"')
            ->assertExitCode(0);

        expect(app_path('Http/Controllers/Api/ProductController.php'))->toFileExist();
        $content = File::get(app_path('Http/Controllers/Api/ProductController.php'));

        expect($content)->toContain('class ProductController extends Controller');
        expect($content)->toContain('public function index(): AnonymousResourceCollection');
        expect($content)->toContain('public function store(ProductRequest');
        expect($content)->toContain('public function show(Product $product)');
        expect($content)->toContain('public function update');
        expect($content)->toContain('public function destroy');
    });

    it('generates form request correctly', function () {
        $this->artisan('api:magic Product --schema="name:string|required"')
            ->assertExitCode(0);

        expect(app_path('Http/Requests/ProductRequest.php'))->toFileExist();
        $content = File::get(app_path('Http/Requests/ProductRequest.php'));

        expect($content)->toContain('class ProductRequest extends FormRequest');
        expect($content)->toContain('public function rules(): array');
        expect($content)->toContain("'name' => 'required'");
    });

    it('generates api resource correctly', function () {
        $this->artisan('api:magic Product --schema="name:string|required"')
            ->assertExitCode(0);

        expect(app_path('Http/Resources/ProductResource.php'))->toFileExist();
        $content = File::get(app_path('Http/Resources/ProductResource.php'));

        expect($content)->toContain('class ProductResource extends JsonResource');
        expect($content)->toContain('public function toArray');
        expect($content)->toContain("'id' => \$this->id");
    });
});

describe('with --test option', function () {
    it('generates pest test file', function () {
        $this->artisan('api:magic Product --schema="name:string|required" --test')
            ->assertExitCode(0);

        expect(base_path('tests/Feature/Api/ProductTest.php'))->toFileExist();
        $content = File::get(base_path('tests/Feature/Api/ProductTest.php'));

        expect($content)->toContain('use App\Models\Product;');
        expect($content)->toContain("use function Pest\Laravel\{get, post, put, delete};");
        expect($content)->toContain("it('can list all products'");
        expect($content)->toContain("get('/api/products')");
    });

    it('uses correct api version in test groups', function () {
        $this->artisan('api:magic Product --schema="name:string|required" --test --v=2')
            ->assertExitCode(0);

        expect(base_path('tests/Feature/Api/V2/ProductTest.php'))->toFileExist();
        $content = File::get(base_path('tests/Feature/Api/V2/ProductTest.php'));

        expect($content)->toContain("uses()->group('api', '2', 'products');");
    });
});

describe('with --belongsTo option', function () {
    it('generates model with belongsTo relation', function () {
        $this->artisan('api:magic Product --schema="name:string|required" --belongsTo=Category')
            ->assertExitCode(0);

        $content = File::get(app_path('Models/Product.php'));

        expect($content)->toContain('public function category(): BelongsTo');
        expect($content)->toContain('return $this->belongsTo(Category::class);');
    });

    it('generates migration with foreign key', function () {
        $this->artisan('api:magic Product --schema="name:string|required" --belongsTo=Category')
            ->assertExitCode(0);

        $migrationFiles = glob(database_path('migrations/*_create_products_table.php'));
        $migrationPath = $migrationFiles[array_key_first($migrationFiles)];
        $content = File::get($migrationPath);

        expect($content)->toContain("foreignId('category_id')->constrained()->cascadeOnDelete()");
    });

    it('adds foreign key to fillable', function () {
        $this->artisan('api:magic Product --schema="name:string|required" --belongsTo=Category')
            ->assertExitCode(0);

        $content = File::get(app_path('Models/Product.php'));

        expect($content)->toContain("'category_id',");
    });
});

describe('with --hasMany option', function () {
    it('generates model with hasMany relation', function () {
        $this->artisan('api:magic Product --schema="name:string|required" --hasMany=Review')
            ->assertExitCode(0);

        $content = File::get(app_path('Models/Product.php'));

        expect($content)->toContain('public function reviews(): HasMany');
        expect($content)->toContain('return $this->hasMany(Review::class);');
    });
});

describe('with --v option (API versioning)', function () {
    it('generates v1 controller by default', function () {
        $this->artisan('api:magic Product --schema="name:string|required"')
            ->assertExitCode(0);

        expect(app_path('Http/Controllers/Api/ProductController.php'))->toFileExist();
        $content = File::get(app_path('Http/Controllers/Api/ProductController.php'));

        expect($content)->toContain('namespace App\Http\Controllers\Api;');
    });

    it('generates v2 controller when --v=2', function () {
        $this->artisan('api:magic Product --schema="name:string|required" --v=2')
            ->assertExitCode(0);

        expect(app_path('Http/Controllers/Api/V2/ProductController.php'))->toFileExist();
        $content = File::get(app_path('Http/Controllers/Api/V2/ProductController.php'));

        expect($content)->toContain('namespace App\Http\Controllers\Api\V2;');
    });

    it('generates v2 resource when --v=2', function () {
        $this->artisan('api:magic Product --schema="name:string|required" --v=2')
            ->assertExitCode(0);

        expect(app_path('Http/Resources/V2/ProductResource.php'))->toFileExist();
        $content = File::get(app_path('Http/Resources/V2/ProductResource.php'));

        expect($content)->toContain('namespace App\Http\Resources\V2;');
    });
});

describe('--force option', function () {
    it('overwrites existing files when --force is used', function () {
        // First create a file
        File::put(app_path('Models/Product.php'), '<?php // existing file');

        $this->artisan('api:magic Product --schema="name:string|required" --force')
            ->assertExitCode(0);

        $content = File::get(app_path('Models/Product.php'));
        expect($content)->not->toContain('// existing file');
    });

    it('skips existing files without --force', function () {
        // First create a file
        File::put(app_path('Models/Product.php'), '<?php // existing file');

        $this->artisan('api:magic Product --schema="name:string|required"')
            ->expectsOutputToContain('Skipped: app/Models/Product.php (already exists)')
            ->assertExitCode(0);

        expect(File::get(app_path('Models/Product.php')))->toBe('<?php // existing file');
    });
});

describe('complex schemas', function () {
    it('handles multiple fields with various types', function () {
        $this->artisan('api:magic Product --schema="name:string|required,price:integer|min:0,description:text|nullable,active:boolean"')
            ->assertExitCode(0);

        $modelContent = File::get(app_path('Models/Product.php'));
        $migrationContent = File::get(glob(database_path('migrations/*_create_products_table.php'))[0]);

        expect($modelContent)->toContain("'name',");
        expect($modelContent)->toContain("'price',");
        expect($modelContent)->toContain("'description',");
        expect($modelContent)->toContain("'active',");
        expect($migrationContent)->toContain('text(\'description\')');
        expect($migrationContent)->toContain('boolean(\'active\')');
        expect($migrationContent)->toContain('->nullable()');
    });

    it('handles validation rules in schema', function () {
        $this->artisan('api:magic Product --schema="email:email|required|unique:products,age:integer|min:18|max:100"')
            ->assertExitCode(0);

        $requestContent = File::get(app_path('Http/Requests/ProductRequest.php'));

        expect($requestContent)->toContain("'email' => 'required|email|unique:products'");
        expect($requestContent)->toContain("'age' => 'integer|min:18|max:100'");
    });
});

describe('pluralization', function () {
    it('correctly pluralizes model names', function () {
        $this->artisan('api:magic Product --schema="name:string|required"')
            ->assertExitCode(0);

        $migrationFiles = glob(database_path('migrations/*_create_products_table.php'));
        $migrationPath = $migrationFiles[array_key_first($migrationFiles)];
        $content = File::get($migrationPath);

        expect($content)->toContain("Schema::create('products'");
    });

    it('handles irregular pluralizations', function () {
        $this->artisan('api:magic Person --schema="name:string|required"')
            ->assertExitCode(0);

        $migrationFiles = glob(database_path('migrations/*_create_people_table.php'));
        $migrationPath = $migrationFiles[array_key_first($migrationFiles)];
        $content = File::get($migrationPath);

        expect($content)->toContain("Schema::create('people'");
    });
});
