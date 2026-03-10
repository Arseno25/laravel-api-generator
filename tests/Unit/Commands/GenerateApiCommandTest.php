<?php

use Arseno25\LaravelApiMagic\Commands\GenerateApiCommand;
use Illuminate\Support\Facades\File;

uses()->group('commands', 'generate-api');

beforeEach(function () {
    // Clean up models
    $modelPaths = [
        app_path('Models/Product.php'),
        app_path('Models/Person.php'),
    ];
    foreach ($modelPaths as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    // Clean up controllers
    $controllerPaths = [
        app_path('Http/Controllers/Api/ProductController.php'),
        app_path('Http/Controllers/Api/V1/ProductController.php'),
        app_path('Http/Controllers/Api/V2/ProductController.php'),
    ];
    foreach ($controllerPaths as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    // Clean up requests
    $requestPaths = [
        app_path('Http/Requests/StoreProductRequest.php'),
        app_path('Http/Requests/UpdateProductRequest.php'),
    ];
    foreach ($requestPaths as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    // Clean up resources
    $resourcePaths = [
        app_path('Http/Resources/ProductResource.php'),
        app_path('Http/Resources/ProductCollection.php'),
        app_path('Http/Resources/V1/ProductResource.php'),
        app_path('Http/Resources/V1/ProductCollection.php'),
        app_path('Http/Resources/V2/ProductResource.php'),
        app_path('Http/Resources/V2/ProductCollection.php'),
    ];
    foreach ($resourcePaths as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    $policyPaths = [
        app_path('Policies/ProductPolicy.php'),
        app_path('Policies/PersonPolicy.php'),
    ];
    foreach ($policyPaths as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    // Clean up migrations
    $migrationFiles = array_merge(
        glob(database_path('migrations/*_create_products_table.php')),
        glob(database_path('migrations/*_create_people_table.php')),
    );
    foreach ($migrationFiles as $file) {
        if (File::exists($file)) {
            File::delete($file);
        }
    }

    // Clean up tests
    $testPaths = [
        base_path('tests/Feature/Api/ProductTest.php'),
        base_path('tests/Feature/Api/V1/ProductTest.php'),
        base_path('tests/Feature/Api/V2/ProductTest.php'),
    ];
    foreach ($testPaths as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    // Clean up factories and seeders
    $factoryPath = database_path('factories/ProductFactory.php');
    $seederPath = database_path('seeders/ProductSeeder.php');
    if (File::exists($factoryPath)) {
        File::delete($factoryPath);
    }
    if (File::exists($seederPath)) {
        File::delete($seederPath);
    }
});

afterEach(function () {
    // Same cleanup as beforeEach
    $paths = array_merge(
        [
            app_path('Models/Product.php'),
            app_path('Models/Person.php'),
            app_path('Http/Controllers/Api/ProductController.php'),
            app_path('Http/Controllers/Api/V1/ProductController.php'),
            app_path('Http/Controllers/Api/V2/ProductController.php'),
            app_path('Http/Requests/StoreProductRequest.php'),
            app_path('Http/Requests/UpdateProductRequest.php'),
            app_path('Http/Resources/ProductResource.php'),
            app_path('Http/Resources/ProductCollection.php'),
            app_path('Http/Resources/V1/ProductResource.php'),
            app_path('Http/Resources/V1/ProductCollection.php'),
            app_path('Http/Resources/V2/ProductResource.php'),
            app_path('Http/Resources/V2/ProductCollection.php'),
            app_path('Policies/ProductPolicy.php'),
            app_path('Policies/PersonPolicy.php'),
            base_path('tests/Feature/Api/ProductTest.php'),
            base_path('tests/Feature/Api/V1/ProductTest.php'),
            base_path('tests/Feature/Api/V2/ProductTest.php'),
            database_path('factories/ProductFactory.php'),
            database_path('seeders/ProductSeeder.php'),
        ],
        glob(database_path('migrations/*_create_products_table.php')),
        glob(database_path('migrations/*_create_people_table.php')),
    );

    foreach ($paths as $path) {
        if (File::exists($path)) {
            File::delete($path);
        }
    }
});

it('has the correct command name', function () {
    $command = app(GenerateApiCommand::class);

    expect($command->getName())->toBe('api:magic');
});

describe('file generation (no versioning by default)', function () {
    it('generates model file correctly', function () {
        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'name:string|required',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        expect(File::exists(app_path('Models/Product.php')))->toBeTrue();
        $content = File::get(app_path('Models/Product.php'));

        expect($content)->toContain('class Product extends Model');
        expect($content)->toContain('protected $fillable = [');
        expect($content)->toContain("'name'");
    });

    it('generates migration file correctly', function () {
        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'name:string|required,price:integer',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        $migrationFiles = glob(
            database_path('migrations/*_create_products_table.php'),
        );
        expect($migrationFiles)->not->toBeEmpty();

        $migrationPath = $migrationFiles[array_key_first($migrationFiles)];
        $content = File::get($migrationPath);

        expect($content)->toContain('Schema::create');
        expect($content)->toContain('$table->string(\'name\')');
        expect($content)->toContain('$table->integer(\'price\')');
    });

    it(
        'generates api controller without version prefix by default',
        function () {
            $this->artisan('api:magic', [
                'model' => 'Product',
                'schema' => 'name:string|required',
                '--no-interaction' => true,
            ])->assertExitCode(0);

            expect(
                File::exists(
                    app_path('Http/Controllers/Api/ProductController.php'),
                ),
            )->toBeTrue();
            $content = File::get(
                app_path('Http/Controllers/Api/ProductController.php'),
            );

            expect($content)->toContain("namespace App\Http\Controllers\Api;");
            expect($content)->toContain(
                'class ProductController extends Controller',
            );
            expect($content)->toContain(
                'public function index(Request $request)',
            );
            expect($content)->toContain(
                'public function store(StoreProductRequest',
            );
            expect($content)->toContain(
                'public function show(Product $product)',
            );
            expect($content)->toContain(
                'public function update(UpdateProductRequest $request, Product $product)',
            );
            expect($content)->toContain('public function destroy');
        },
    );

    it('generates store and update form requests correctly', function () {
        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'name:string|required',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        expect(
            File::exists(app_path('Http/Requests/StoreProductRequest.php')),
        )->toBeTrue();
        expect(
            File::exists(app_path('Http/Requests/UpdateProductRequest.php')),
        )->toBeTrue();
        $storeContent = File::get(
            app_path('Http/Requests/StoreProductRequest.php'),
        );
        $updateContent = File::get(
            app_path('Http/Requests/UpdateProductRequest.php'),
        );

        expect($storeContent)->toContain(
            'class StoreProductRequest extends FormRequest',
        );
        expect($updateContent)->toContain(
            'class UpdateProductRequest extends FormRequest',
        );
        expect($updateContent)->toContain('public function rules(): array');
        expect($updateContent)->toContain("'name' => 'required'");
    });

    it('generates api resource correctly', function () {
        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'name:string|required',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        expect(
            File::exists(app_path('Http/Resources/ProductResource.php')),
        )->toBeTrue();
        $content = File::get(app_path('Http/Resources/ProductResource.php'));

        expect($content)->toContain("namespace App\Http\Resources;");
        expect($content)->toContain(
            'class ProductResource extends JsonResource',
        );
        expect($content)->toContain('public function toArray');
        expect($content)->toContain("'id' => \$this->id");
    });

    it('generates api collection correctly', function () {
        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'name:string|required',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        expect(
            File::exists(app_path('Http/Resources/ProductCollection.php')),
        )->toBeTrue();
    });
});

describe('with --test option (no versioning)', function () {
    it('generates pest test file', function () {
        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'name:string|required',
            '--test' => true,
            '--no-interaction' => true,
        ])->assertExitCode(0);

        expect(
            File::exists(base_path('tests/Feature/Api/ProductTest.php')),
        )->toBeTrue();
        $content = File::get(base_path('tests/Feature/Api/ProductTest.php'));

        expect($content)->toContain("use App\Models\Product;");
        expect($content)->toContain("it('can list all products'");
        expect($content)->toContain("'/api/products'");
    });

    it('generates versioned test with correct URLs when --v=2', function () {
        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'name:string|required',
            '--test' => true,
            '--v' => '2',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        expect(
            File::exists(base_path('tests/Feature/Api/V2/ProductTest.php')),
        )->toBeTrue();
        $content = File::get(base_path('tests/Feature/Api/V2/ProductTest.php'));

        expect($content)->toContain("'/api/v2/products'");
    });
});

describe('with --belongsTo option', function () {
    it('generates model with belongsTo relation', function () {
        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'name:string|required',
            '--belongsTo' => 'Category',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        $content = File::get(app_path('Models/Product.php'));

        expect($content)->toContain('public function category(): BelongsTo');
        expect($content)->toContain(
            'return $this->belongsTo(Category::class);',
        );
    });

    it('generates migration with foreign key', function () {
        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'name:string|required',
            '--belongsTo' => 'Category',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        $migrationFiles = glob(
            database_path('migrations/*_create_products_table.php'),
        );
        $migrationPath = $migrationFiles[array_key_first($migrationFiles)];
        $content = File::get($migrationPath);

        expect($content)->toContain(
            "foreignId('category_id')->constrained()->cascadeOnDelete()",
        );
    });

    it('adds foreign key to fillable', function () {
        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'name:string|required',
            '--belongsTo' => 'Category',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        $content = File::get(app_path('Models/Product.php'));

        expect($content)->toContain("'category_id',");
    });
});

describe('with --hasMany option', function () {
    it('generates model with hasMany relation', function () {
        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'name:string|required',
            '--hasMany' => 'Review',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        $content = File::get(app_path('Models/Product.php'));

        expect($content)->toContain('public function reviews(): HasMany');
        expect($content)->toContain('return $this->hasMany(Review::class);');
    });
});

describe('with --v option (API versioning)', function () {
    it(
        'generates controller without version directory by default',
        function () {
            $this->artisan('api:magic', [
                'model' => 'Product',
                'schema' => 'name:string|required',
                '--no-interaction' => true,
            ])->assertExitCode(0);

            expect(
                File::exists(
                    app_path('Http/Controllers/Api/ProductController.php'),
                ),
            )->toBeTrue();
            $content = File::get(
                app_path('Http/Controllers/Api/ProductController.php'),
            );

            expect($content)->toContain("namespace App\Http\Controllers\Api;");
        },
    );

    it('generates v1 controller in V1 directory when --v=1', function () {
        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'name:string|required',
            '--v' => '1',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        expect(
            File::exists(
                app_path('Http/Controllers/Api/V1/ProductController.php'),
            ),
        )->toBeTrue();
        $content = File::get(
            app_path('Http/Controllers/Api/V1/ProductController.php'),
        );

        expect($content)->toContain("namespace App\Http\Controllers\Api\V1;");
    });

    it('generates v2 controller when --v=2', function () {
        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'name:string|required',
            '--v' => '2',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        expect(
            File::exists(
                app_path('Http/Controllers/Api/V2/ProductController.php'),
            ),
        )->toBeTrue();
        $content = File::get(
            app_path('Http/Controllers/Api/V2/ProductController.php'),
        );

        expect($content)->toContain("namespace App\Http\Controllers\Api\V2;");
    });

    it('generates v2 resource when --v=2', function () {
        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'name:string|required',
            '--v' => '2',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        expect(
            File::exists(app_path('Http/Resources/V2/ProductResource.php')),
        )->toBeTrue();
        $content = File::get(app_path('Http/Resources/V2/ProductResource.php'));

        expect($content)->toContain("namespace App\Http\Resources\V2;");
    });

    it('generates resource without version directory by default', function () {
        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'name:string|required',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        expect(
            File::exists(app_path('Http/Resources/ProductResource.php')),
        )->toBeTrue();
        $content = File::get(app_path('Http/Resources/ProductResource.php'));

        expect($content)->toContain("namespace App\Http\Resources;");
    });
});

describe('--force option', function () {
    it('overwrites existing files when --force is used', function () {
        // First create a file
        File::ensureDirectoryExists(app_path('Models'));
        File::put(app_path('Models/Product.php'), '<?php // existing file');

        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'name:string|required',
            '--force' => true,
            '--no-interaction' => true,
        ])->assertExitCode(0);

        $content = File::get(app_path('Models/Product.php'));
        expect($content)->not->toContain('// existing file');
    });

    it('skips existing files without --force', function () {
        // First create a file
        File::ensureDirectoryExists(app_path('Models'));
        File::put(app_path('Models/Product.php'), '<?php // existing file');

        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'name:string|required',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        expect(File::get(app_path('Models/Product.php')))->toBe(
            '<?php // existing file',
        );
    });
});

describe('complex schemas', function () {
    it('handles multiple fields with various types', function () {
        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'name:string|required,price:integer,description:text|nullable,active:boolean',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        $modelContent = File::get(app_path('Models/Product.php'));
        $migrationContent = File::get(
            glob(database_path('migrations/*_create_products_table.php'))[0],
        );

        expect($modelContent)->toContain("'name',");
        expect($modelContent)->toContain("'price',");
        expect($modelContent)->toContain("'description',");
        expect($modelContent)->toContain("'active',");
        expect($modelContent)->toContain("'price' => 'integer'");
        expect($modelContent)->toContain("'active' => 'boolean'");
        expect($migrationContent)->toContain('text(\'description\')');
        expect($migrationContent)->toContain('boolean(\'active\')');
        expect($migrationContent)->toContain('->nullable()');
    });

    it('handles email validation rule in schema', function () {
        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'email:email|required|unique:products',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        $requestContent = File::get(
            app_path('Http/Requests/StoreProductRequest.php'),
        );

        expect($requestContent)->toContain("'email'");
        expect($requestContent)->toContain('required');
        expect($requestContent)->toContain('unique:products');
    });

    it('generates update-safe unique validation rules', function () {
        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'email:email|required|unique:products',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        $requestContent = File::get(
            app_path('Http/Requests/UpdateProductRequest.php'),
        );

        expect($requestContent)->toContain('use Illuminate\\Validation\\Rule;');
        expect($requestContent)->toContain('Rule::unique($table, $column)');
        expect($requestContent)->toContain('->ignore($product->getKey())');
    });
});

describe('pluralization', function () {
    it('correctly pluralizes model names', function () {
        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'name:string|required',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        $migrationFiles = glob(
            database_path('migrations/*_create_products_table.php'),
        );
        $migrationPath = $migrationFiles[array_key_first($migrationFiles)];
        $content = File::get($migrationPath);

        expect($content)->toContain("Schema::create('products'");
    });

    it('handles irregular pluralizations', function () {
        $this->artisan('api:magic', [
            'model' => 'Person',
            'schema' => 'name:string|required',
            '--no-interaction' => true,
        ])->assertExitCode(0);

        $migrationFiles = glob(
            database_path('migrations/*_create_people_table.php'),
        );
        $migrationPath = $migrationFiles[array_key_first($migrationFiles)];
        $content = File::get($migrationPath);

        expect($content)->toContain("Schema::create('people'");
    });
});

describe('searchable fields', function () {
    it(
        'generates search conditions in controller when schema has searchable fields',
        function () {
            $this->artisan('api:magic', [
                'model' => 'Product',
                'schema' => 'name:string|required,description:text',
                '--no-interaction' => true,
            ])->assertExitCode(0);

            $content = File::get(
                app_path('Http/Controllers/Api/ProductController.php'),
            );

            expect($content)->toContain('$request->filled(\'search\')');
            expect($content)->toContain('$searchTerm');
        },
    );
});

describe('factory and seeder generation', function () {
    it('generates factory when --factory is used', function () {
        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'name:string|required',
            '--factory' => true,
            '--no-interaction' => true,
        ])->assertExitCode(0);

        expect(
            File::exists(database_path('factories/ProductFactory.php')),
        )->toBeTrue();
    });

    it('generates seeder when --seeder is used', function () {
        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'name:string|required',
            '--seeder' => true,
            '--no-interaction' => true,
        ])->assertExitCode(0);

        expect(
            File::exists(database_path('seeders/ProductSeeder.php')),
        )->toBeTrue();
    });
});

describe('policy generation', function () {
    it('generates policy when --policy is used', function () {
        $this->artisan('api:magic', [
            'model' => 'Product',
            'schema' => 'name:string|required',
            '--policy' => true,
            '--no-interaction' => true,
        ])->assertExitCode(0);

        expect(
            File::exists(app_path('Policies/ProductPolicy.php')),
        )->toBeTrue();

        $content = File::get(app_path('Policies/ProductPolicy.php'));

        expect($content)->toContain('class ProductPolicy');
        expect($content)->toContain(
            'public function viewAny(mixed $user): bool',
        );
        expect($content)->toContain(
            'public function update(mixed $user, Product $product): bool',
        );
    });
});
